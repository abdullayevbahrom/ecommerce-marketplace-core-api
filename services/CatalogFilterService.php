<?php

namespace app\services;

use app\models\brand\CategoryBrand;
use app\models\Category;
use app\models\filter\Filter;
use app\models\product\Product;
use app\models\product\ProductFilter;
use app\models\shop\Shop;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\Inflector;
use yii\web\Request;

class CatalogFilterService
{
    private array $filterOptionMapCache = [];

    public function parseState(Request $request): array
    {
        $attributes = $request->get('attributes');
        $legacyFilter = $request->get('filter');

        return [
            'category_id' => $this->toNullableInt($request->get('category_id')),
            'brand_ids' => $this->parseIdList($request->get('brand_ids'), $request->get('brand_id')),
            'store_ids' => $this->parseIdList($request->get('store_ids'), $request->get('shop_id')),
            'price_min' => $this->toNullableFloat($request->get('price_min')),
            'price_max' => $this->toNullableFloat($request->get('price_max')),
            'attribute_logic' => strtolower((string)$request->get('filter_logic', 'and')) === 'or' ? 'or' : 'and',
            'attribute_filters' => $this->normalizeAttributeFilters(
                is_array($attributes) ? $attributes : [],
                is_array($legacyFilter) ? $legacyFilter : [],
                $this->toNullableInt($request->get('category_id'))
            ),
        ];
    }

    public function buildProductsQuery(array $state, array $options = []): ActiveQuery
    {
        $query = Product::find()
            ->alias('product')
            ->where(['product.status' => 1])
            ->where(['product.deleted_at' => null])
            ->marketplaceVisible('product');

        $ignoreBrand = (bool)($options['ignoreBrand'] ?? false);
        $ignoreStore = (bool)($options['ignoreStore'] ?? false);
        $ignorePrice = (bool)($options['ignorePrice'] ?? false);
        $ignoreAttributeFilterIds = array_map('intval', $options['ignoreAttributeFilterIds'] ?? []);

        if (!empty($state['category_id'])) {
            $query->andWhere(['product.category_id' => $this->getCategoryScopeIds((int)$state['category_id'])]);
        }

        if (!$ignoreBrand && !empty($state['brand_ids'])) {
            $query->andWhere(['product.brand_id' => $state['brand_ids']]);
        }

        if (!$ignoreStore && !empty($state['store_ids'])) {
            $query->andWhere(['product.shop_id' => $state['store_ids']]);
        }

        if (!$ignorePrice) {
            if ($state['price_min'] !== null) {
                $query->andWhere(['>=', 'product.price', $state['price_min']]);
            }

            if ($state['price_max'] !== null) {
                $query->andWhere(['<=', 'product.price', $state['price_max']]);
            }
        }

        $this->applyAttributeFilters(
            $query,
            $state['attribute_filters'],
            $ignoreAttributeFilterIds,
            $state['attribute_logic'] ?? 'and'
        );

        return $query;
    }

    public function buildFacets(array $state): array
    {
        if (empty($state['category_id'])) {
            return [
                'brands' => [],
                'stores' => [],
                'price' => ['min' => 0, 'max' => 0],
                'attributes' => [],
            ];
        }

        return [
            'brands' => $this->buildBrandFacet($state),
            'stores' => $this->buildStoreFacet($state),
            'price' => $this->buildPriceFacet($state),
            'attributes' => $this->buildAttributeFacets($state),
        ];
    }

    public function getCategoryRootFilters(int $categoryId): array
    {
        $categoryIds = [$categoryId];
        $category = Category::findOne($categoryId);

        if ($category && $category->parent_id) {
            $categoryIds[] = (int)$category->parent_id;
        }

        return Filter::find()
            ->where(['parent_id' => 0, 'status' => 1])
            ->andWhere(['category_id' => array_values(array_unique($categoryIds))])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    public function getAttributeFilterState(array $state): array
    {
        return $state['attribute_filters'] ?? [];
    }

    public function validateState(array $state): ?array
    {
        if (
            $state['price_min'] !== null
            && $state['price_max'] !== null
            && $state['price_min'] > $state['price_max']
        ) {
            return [
                'message' => 'price_min cannot be greater than price_max',
                'field' => 'price',
            ];
        }

        return null;
    }

    public function validateRequest(Request $request, array $state, bool $requireCategory = true): ?array
    {
        if ($requireCategory && empty($state['category_id'])) {
            return [
                'message' => 'category_id is required',
                'field' => 'category_id',
            ];
        }

        if (count($state['brand_ids'] ?? []) > 20) {
            return [
                'message' => 'brand_ids: maximum 20 values allowed',
                'field' => 'brand_ids',
            ];
        }

        if (count($state['store_ids'] ?? []) > 20) {
            return [
                'message' => 'store_ids: maximum 20 values allowed',
                'field' => 'store_ids',
            ];
        }

        $rawAttributes = $this->collectRawAttributePayload($request);
        if (count($rawAttributes) > 10) {
            return [
                'message' => 'attributes: maximum 10 keys allowed',
                'field' => 'attributes',
            ];
        }

        foreach ($rawAttributes as $rawKey => $rawValues) {
            if (mb_strlen(trim((string)$rawKey)) > 100) {
                return [
                    'message' => 'attributes: maximum string length is 100',
                    'field' => 'attributes',
                ];
            }

            $values = $this->normalizeValueList($rawValues);
            if (count($values) > 10) {
                return [
                    'message' => "attributes[{$rawKey}]: maximum 10 values allowed",
                    'field' => 'attributes',
                ];
            }

            foreach ($values as $value) {
                if (mb_strlen(trim((string)$value)) > 100) {
                    return [
                        'message' => 'attributes: maximum string length is 100',
                        'field' => 'attributes',
                    ];
                }
            }
        }

        return $this->validateState($state);
    }

    private function buildBrandFacet(array $state): array
    {
        $rows = $this->buildProductsQuery($state, ['ignoreBrand' => true])
            ->select([
                'id' => 'product.brand_id',
                'name_ru' => 'brand.name_ru',
                'name_uz' => 'brand.name_uz',
                'name_en' => 'brand.name_en',
                'count' => new Expression('COUNT(DISTINCT product.id)'),
            ])
            ->innerJoin(['brand' => CategoryBrand::tableName()], 'brand.id = product.brand_id')
            ->groupBy(['product.brand_id', 'brand.name_ru', 'brand.name_uz', 'brand.name_en'])
            ->having(['>', new Expression('COUNT(DISTINCT product.id)'), 0])
            ->orderBy(['count' => SORT_DESC, 'brand.name_ru' => SORT_ASC])
            ->asArray()
            ->all();

        return array_map(function (array $row) {
            return [
                'id' => (int)$row['id'],
                'name' => $this->pickLocalizedValue($row['name_ru'] ?? null, $row['name_uz'] ?? null, $row['name_en'] ?? null) ?? '',
                'count' => (int)$row['count'],
            ];
        }, $rows);
    }

    private function buildStoreFacet(array $state): array
    {
        $rows = $this->buildProductsQuery($state, ['ignoreStore' => true])
            ->select([
                'id' => 'product.shop_id',
                'name_ru' => 'shop.name_ru',
                'name_uz' => 'shop.name_uz',
                'name_en' => 'shop.name_en',
                'count' => new Expression('COUNT(DISTINCT product.id)'),
            ])
            ->innerJoin(['shop' => Shop::tableName()], 'shop.id = product.shop_id')
            ->groupBy(['product.shop_id', 'shop.name_ru', 'shop.name_uz', 'shop.name_en'])
            ->having(['>', new Expression('COUNT(DISTINCT product.id)'), 0])
            ->orderBy(['count' => SORT_DESC, 'shop.name_ru' => SORT_ASC])
            ->asArray()
            ->all();

        return array_map(function (array $row) {
            return [
                'id' => (int)$row['id'],
                'name' => $this->pickLocalizedValue($row['name_ru'] ?? null, $row['name_uz'] ?? null, $row['name_en'] ?? null) ?? '',
                'count' => (int)$row['count'],
            ];
        }, $rows);
    }

    private function buildPriceFacet(array $state): array
    {
        $query = $this->buildProductsQuery($state, ['ignorePrice' => true]);

        $minQuery = clone $query;
        $maxQuery = clone $query;

        $min = $minQuery->min('product.price');
        $max = $maxQuery->max('product.price');

        return [
            'min' => $min !== null ? (float)$min : 0,
            'max' => $max !== null ? (float)$max : 0,
        ];
    }

    private function buildAttributeFacets(array $state): array
    {
        $filters = $this->getCategoryRootFilters((int)$state['category_id']);
        $result = [];

        foreach ($filters as $filter) {
            $optionMap = $this->getFilterOptionMap((int)$filter->id);
            $rows = $this->buildProductsQuery($state, ['ignoreAttributeFilterIds' => [$filter->id]])
                ->select([
                    'value_ru' => 'facet_filter.value_ru',
                    'value_uz' => 'facet_filter.value_uz',
                    'value_en' => 'facet_filter.value_en',
                    'count' => new Expression('COUNT(DISTINCT product.id)'),
                ])
                ->innerJoin(['facet_filter' => ProductFilter::tableName()], 'facet_filter.product_id = product.id AND facet_filter.filter_id = :filterId', [
                    ':filterId' => $filter->id,
                ])
                ->groupBy(['facet_filter.value_ru', 'facet_filter.value_uz', 'facet_filter.value_en'])
                ->having(['>', new Expression('COUNT(DISTINCT product.id)'), 0])
                ->orderBy(['count' => SORT_DESC, 'facet_filter.value_ru' => SORT_ASC])
                ->asArray()
                ->all();

            $options = [];
            foreach ($rows as $row) {
                $rawValue = $this->pickLocalizedValue($row['value_ru'] ?? null, $row['value_uz'] ?? null, $row['value_en'] ?? null);
                if ($rawValue === null || $rawValue === '') {
                    continue;
                }

                $matchedOption = $this->resolveAttributeOptionByRawValue($optionMap, $row);
                $label = $matchedOption !== null
                    ? $this->pickLocalizedValue($matchedOption['value_ru'] ?? null, $matchedOption['value_uz'] ?? null, $matchedOption['value_en'] ?? null)
                    : $rawValue;

                $optionValue = $matchedOption !== null
                    ? (int)$matchedOption['id']
                    : $this->encodeRawFacetValue($rawValue);
                $optionKey = is_int($optionValue) ? 'id:' . $optionValue : 'raw:' . $optionValue;

                if (!isset($options[$optionKey])) {
                    $options[$optionKey] = [
                        'value' => $optionValue,
                        'label' => $label ?: $rawValue,
                        'count' => 0,
                    ];
                }

                $options[$optionKey]['count'] += (int)$row['count'];
            }

            if (empty($options)) {
                continue;
            }

            usort($options, static function (array $left, array $right) {
                $countComparison = $right['count'] <=> $left['count'];
                if ($countComparison !== 0) {
                    return $countComparison;
                }

                return strcmp((string)$left['label'], (string)$right['label']);
            });

            $result[] = [
                'key' => $this->buildFilterKey($filter),
                'label' => $this->pickLocalizedValue($filter->name_ru, $filter->name_uz, $filter->name_en) ?? '',
                'type' => 'multiselect',
                'options' => array_values($options),
            ];
        }

        return $result;
    }

    private function applyAttributeFilters(ActiveQuery $query, array $attributeFilters, array $ignoreFilterIds = [], string $logic = 'and'): void
    {
        $existsConditions = [];

        foreach ($attributeFilters as $filterId => $values) {
            $filterId = (int)$filterId;
            if ($filterId <= 0 || in_array($filterId, $ignoreFilterIds, true)) {
                continue;
            }

            $values = array_values(array_filter(array_map(static function ($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }

                return $value === '' ? null : $value;
            }, (array)$values), static fn($value) => $value !== null));

            if (empty($values)) {
                continue;
            }

            $subQuery = ProductFilter::find()
                ->alias('pf')
                ->select(new Expression('1'))
                ->where('pf.product_id = product.id')
                ->andWhere(['pf.filter_id' => $filterId]);

            $stringValues = $this->expandAttributeFilterValues($filterId, $values);
            if (empty($stringValues)) {
                continue;
            }

            $orConditions = ['or'];
            $orConditions[] = ['pf.value_ru' => $stringValues];
            $orConditions[] = ['pf.value_uz' => $stringValues];
            $orConditions[] = ['pf.value_en' => $stringValues];

            foreach ($stringValues as $stringValue) {
                $orConditions[] = ['like', 'pf.value_ru', $stringValue];
                $orConditions[] = ['like', 'pf.value_uz', $stringValue];
                $orConditions[] = ['like', 'pf.value_en', $stringValue];
            }

            $subQuery->andWhere($orConditions);
            $existsConditions[] = ['exists', $subQuery];
        }

        if (empty($existsConditions)) {
            return;
        }

        if ($logic === 'or') {
            array_unshift($existsConditions, 'or');
            $query->andWhere($existsConditions);
            return;
        }

        foreach ($existsConditions as $condition) {
            $query->andWhere($condition);
        }
    }

    private function normalizeAttributeFilters(array $attributes, array $legacyFilter, ?int $categoryId): array
    {
        $result = [];
        $availableFilters = [];

        if ($categoryId) {
            foreach ($this->getCategoryRootFilters($categoryId) as $filter) {
                $availableFilters[(string)$filter->id] = (int)$filter->id;
                if ($filter->code) {
                    $availableFilters[$filter->code] = (int)$filter->id;
                }

                foreach ($this->buildLegacyFilterKeys($filter) as $legacyKey) {
                    $availableFilters[$legacyKey] = (int)$filter->id;
                }
            }
        }

        foreach ($attributes as $rawKey => $rawValues) {
            $filterId = $this->resolveFilterId($rawKey, $availableFilters);
            if ($filterId === null) {
                Yii::warning('Ignoring unknown attribute filter key: ' . $rawKey, __METHOD__);
                continue;
            }

            $values = $this->normalizeValueList($rawValues);
            if (!empty($values)) {
                $result[$filterId] = $values;
            }
        }

        foreach ($legacyFilter as $rawKey => $rawValues) {
            $filterId = $this->resolveFilterId($rawKey, $availableFilters);
            if ($filterId === null) {
                Yii::warning('Ignoring unknown legacy filter key: ' . $rawKey, __METHOD__);
                continue;
            }

            $values = $this->normalizeValueList($rawValues);
            if (!empty($values)) {
                $result[$filterId] = $values;
            }
        }

        ksort($result);

        return $result;
    }

    private function resolveFilterId($rawKey, array $availableFilters): ?int
    {
        $normalized = trim((string)$rawKey);
        if ($normalized === '') {
            return null;
        }

        if (isset($availableFilters[$normalized])) {
            return $availableFilters[$normalized];
        }

        if (ctype_digit($normalized)) {
            return (int)$normalized;
        }

        $slug = Inflector::slug($normalized);

        return $availableFilters[$slug] ?? null;
    }

    private function normalizeValueList($rawValues): array
    {
        if (is_array($rawValues)) {
            $values = [];
            foreach ($rawValues as $value) {
                foreach ($this->normalizeValueList($value) as $innerValue) {
                    $values[] = $innerValue;
                }
            }

            return array_values(array_unique($values, SORT_REGULAR));
        }

        if (is_string($rawValues) && str_contains($rawValues, ',')) {
            return $this->normalizeValueList(array_map('trim', explode(',', $rawValues)));
        }

        if ($rawValues === null) {
            return [];
        }

        $value = is_string($rawValues) ? trim($rawValues) : $rawValues;
        if ($value === '') {
            return [];
        }

        return [$value];
    }

    private function parseIdList($rawValue, $fallbackSingle = null): array
    {
        if ($rawValue === null || $rawValue === '') {
            $rawValue = $fallbackSingle;
        }

        $values = $this->normalizeValueList($rawValue);

        return array_values(array_unique(array_filter(array_map(function ($value) {
            return $this->toNullableInt($value);
        }, $values), static fn($value) => $value !== null)));
    }

    private function getCategoryScopeIds(int $categoryId): array
    {
        $categoryIds = [$categoryId];
        $subcategories = Category::find()
            ->select('id')
            ->where(['parent_id' => $categoryId])
            ->column();

        foreach ($subcategories as $subcategoryId) {
            $categoryIds[] = (int)$subcategoryId;
        }

        return array_values(array_unique($categoryIds));
    }

    public function expandAttributeFilterValues(int $filterId, array $values): array
    {
        $expanded = [];
        $optionMap = $this->getFilterOptionMap($filterId);

        foreach ($values as $value) {
            if (is_numeric($value) && (string)(int)$value === (string)$value) {
                $optionId = (int)$value;
                if (isset($optionMap['by_id'][$optionId])) {
                    $option = $optionMap['by_id'][$optionId];
                    foreach (['name_ru', 'name_uz', 'name_en', 'value_ru', 'value_uz', 'value_en'] as $field) {
                        foreach ($this->expandComparableStrings((string)($option[$field] ?? '')) as $candidate) {
                            $expanded[] = $candidate;
                        }
                    }
                    continue;
                }
            }

            if (is_string($value)) {
                $decodedValue = $this->decodeRawFacetValue($value);
                if ($decodedValue !== null) {
                    foreach ($this->expandComparableStrings($decodedValue) as $candidate) {
                        $expanded[] = $candidate;
                    }
                    continue;
                }
            }

            foreach ($this->expandComparableStrings((string)$value) as $candidate) {
                $expanded[] = $candidate;
            }
        }

        return array_values(array_unique($expanded));
    }

    private function buildFilterKey(Filter $filter): string
    {
        if (!empty($filter->code)) {
            return (string)$filter->code;
        }

        $legacyKeys = $this->buildLegacyFilterKeys($filter);
        $legacyKey = reset($legacyKeys);

        return $legacyKey !== '' ? $legacyKey : (string)$filter->id;
    }

    private function buildLegacyFilterKeys(Filter $filter): array
    {
        $slugs = [];
        foreach ([$filter->name_ru, $filter->name_uz, $filter->name_en] as $name) {
            $slug = Inflector::slug((string)$name);
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        $preferred = $this->pickLocalizedValue($filter->name_ru, $filter->name_uz, $filter->name_en);
        $preferredSlug = Inflector::slug((string)$preferred);
        if ($preferredSlug !== '') {
            array_unshift($slugs, $preferredSlug);
        }

        return array_values(array_unique($slugs));
    }

    private function pickLocalizedValue(?string $ru, ?string $uz, ?string $en): ?string
    {
        foreach ([$ru, $uz, $en] as $value) {
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int)$value;
    }

    private function toNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float)$value;
    }

    private function getFilterOptionMap(int $filterId): array
    {
        if (isset($this->filterOptionMapCache[$filterId])) {
            return $this->filterOptionMapCache[$filterId];
        }

        $rows = Filter::find()
            ->select(['id', 'name_ru', 'name_uz', 'name_en', 'value_ru', 'value_uz', 'value_en'])
            ->where(['parent_id' => $filterId])
            ->asArray()
            ->all();

        $byId = [];
        $byRaw = [];
        $byComparable = [];

        foreach ($rows as $row) {
            $byId[(int)$row['id']] = $row;

            foreach (['name_ru', 'name_uz', 'name_en', 'value_ru', 'value_uz', 'value_en'] as $field) {
                $raw = trim((string)($row[$field] ?? ''));
                if ($raw !== '') {
                    $byRaw[$raw] = $row;
                    foreach ($this->expandComparableStrings($raw) as $comparable) {
                        $byComparable[$comparable] = $row;
                    }
                }
            }
        }

        return $this->filterOptionMapCache[$filterId] = [
            'by_id' => $byId,
            'by_raw' => $byRaw,
            'by_comparable' => $byComparable,
        ];
    }

    private function resolveAttributeOptionByRawValue(array $optionMap, array $row): ?array
    {
        foreach (['value_ru', 'value_uz', 'value_en'] as $field) {
            $raw = trim((string)($row[$field] ?? ''));
            if ($raw !== '' && isset($optionMap['by_raw'][$raw])) {
                return $optionMap['by_raw'][$raw];
            }

            foreach ($this->expandComparableStrings($raw) as $comparable) {
                if ($comparable !== '' && isset($optionMap['by_comparable'][$comparable])) {
                    return $optionMap['by_comparable'][$comparable];
                }
            }
        }

        return null;
    }

    private function encodeRawFacetValue(string $value): string
    {
        $encoded = rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        return 'raw:' . $encoded;
    }

    private function decodeRawFacetValue(string $value): ?string
    {
        if (!str_starts_with($value, 'raw:')) {
            return null;
        }

        $encoded = substr($value, 4);
        if ($encoded === '') {
            return null;
        }

        $padding = strlen($encoded) % 4;
        if ($padding !== 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($decoded === false) {
            return null;
        }

        $decoded = trim($decoded);

        return $decoded === '' ? null : $decoded;
    }

    private function expandComparableStrings(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $variants = [$value];

        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)/u', $value, $matches)) {
            $variants[] = $matches[1];
        }

        return array_values(array_unique(array_filter($variants, static fn($item) => $item !== '')));
    }

    private function collectRawAttributePayload(Request $request): array
    {
        $attributes = $request->get('attributes');
        $legacyFilter = $request->get('filter');

        $payload = [];

        foreach ([is_array($attributes) ? $attributes : [], is_array($legacyFilter) ? $legacyFilter : []] as $source) {
            foreach ($source as $key => $value) {
                $payload[(string)$key] = $value;
            }
        }

        return $payload;
    }
}
