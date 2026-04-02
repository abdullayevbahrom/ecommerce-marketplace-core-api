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
                'name' => $this->pickLocalizedValue($row['name_ru'] ?? null, $row['name_uz'] ?? null, $row['name_en'] ?? null),
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
                'name' => $this->pickLocalizedValue($row['name_ru'] ?? null, $row['name_uz'] ?? null, $row['name_en'] ?? null),
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

                $options[] = [
                    'value' => $rawValue,
                    'label' => $rawValue,
                    'count' => (int)$row['count'],
                ];
            }

            if (empty($options)) {
                continue;
            }

            $result[] = [
                'key' => $this->buildFilterKey($filter),
                'label' => $this->pickLocalizedValue($filter->name_ru, $filter->name_uz, $filter->name_en),
                'type' => $filter->type === 'checkbox' ? 'multiselect' : $filter->type,
                'options' => $options,
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

            $numericValues = [];
            $textValues = [];
            foreach ($values as $value) {
                if (is_numeric($value) && (string)(int)$value === (string)$value) {
                    $numericValues[] = (int)$value;
                }

                $textValues[] = (string)$value;
            }

            $orConditions = ['or'];
            if (!empty($numericValues)) {
                $orConditions[] = ['pf.id' => array_values(array_unique($numericValues))];
            }

            $stringValues = array_values(array_unique($textValues));
            $orConditions[] = ['pf.value_ru' => $stringValues];
            $orConditions[] = ['pf.value_uz' => $stringValues];
            $orConditions[] = ['pf.value_en' => $stringValues];

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
                $availableFilters[$this->buildFilterKey($filter)] = (int)$filter->id;
            }
        }

        foreach ($attributes as $rawKey => $rawValues) {
            $filterId = $this->resolveFilterId($rawKey, $availableFilters);
            if ($filterId === null) {
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

    private function buildFilterKey(Filter $filter): string
    {
        $name = $this->pickLocalizedValue($filter->name_ru, $filter->name_uz, $filter->name_en);
        $slug = Inflector::slug((string)$name);

        return $slug !== '' ? $slug : (string)$filter->id;
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
}
