<?php

namespace app\components\Bts;

use Yii;
use yii\base\Component;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Exception;
use yii\caching\CacheInterface;

class BtsComponent extends Component
{
    private ?string $url = null;
    private ?string $login = null;
    private ?string $password = null;
    private ?string $version = null;
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?bool $isRetry = false;

    private const string ACCESS_TOKEN_CACHE_KEY = 'bts_access_token';
    private const string REFRESH_TOKEN_CACHE_KEY = 'bts_refresh_token';
    private const int ACCESS_TOKEN_TTL = 86400;
    private const int REFRESH_TOKEN_TTL = 2592000;

    public function init()
    {
        parent::init();

        $this->url = Yii::$app->params['bts']['url'] ?? 'https://apitest.logistics.example.com:28345';
        $this->version = Yii::$app->params['bts']['version'] ?? 'v1';
        $this->login = Yii::$app->params['bts']['login'] ?? null;
        $this->password = Yii::$app->params['bts']['password'] ?? null;

        if (empty($this->url) || empty($this->login) || empty($this->password) || empty($this->version)) {
            throw new \InvalidArgumentException('BTS component configuration is incomplete. Please check your params.php settings.');
        }
    }

    /**
     * @param array{
     *  senderCityCode: string,
     *  receiverCityCode: string,
     *  pickup_type: string,
     *  dropoff_type: string,
     *  weight: int|float,
     * } $data
     */
    public function calculateOrder(array $data): int
    {
        $response = $this->makeRequest('POST', 'order-calculate/index', $data);

        return isset($response['all_cost']) ? (int) $response['all_cost'] : 0;
    }

    /**
     * @param array{
     *  clientId: int,
     *  pickup_type: 'courier'|'pickup'|'self',
     *  dropoff_type: 'courier'|'pickup'|'self',
     *  sender: array{
     *      name: string,
     *      phone: string,
     *      address: string,
     *      city_code: string
     *  },
     *  receiver: array{
     *      name: string,
     *      phone: string,
     *      address: string,
     *      city_code: string
     *  },
     *  cargo: array{
     *      weight: int|float,
     *      volume: int|float,
     *      piece: int
     *  }
     * } $data 
     */
    public function createOrder(array $data): array
    {
        return $this->makeRequest('POST', 'order/add', $data);
    }

    /**
     * @param array{
     *  clientId: int,
     *  pickup_type: 'courier'|'pickup'|'self',
     *  dropoff_type: 'courier'|'pickup'|'self',
     *  sender: array{
     *      name: string,
     *      phone: string,
     *      address: string,
     *      latitude: float,
     *      longitude: float,
     *      city_code: string,
     *      branch_code: string
     *  },
     *  receiver: array{
     *      name: string,
     *      phone: string,
     *      address: string,
     *      latitude: float,
     *      longitude: float,
     *      city_code: string,
     *      branch_code: string
     *  },
     *  cargo: array{
     *      weight: int|float,
     *      volume: int|float,
     *      piece: int
     *  }
     * } $data 
     */
    public function editOrder(int $orderId, array $data): array
    {
        return $this->makeRequest('POST', "order/edit?orderId={$orderId}", $data);
    }

    public function getSticker(int $orderId): array
    {
        return $this->makeRequest('GET', "order/sticker?orderId={$orderId}");
    }

    public function getOrderInfo(int $orderId): array
    {
        return $this->makeRequest('GET', "order/detail?orderId={$orderId}");
    }

    /**
     * @param string $from 2026-05-20
     * @param string $to 2026-06-17
     */
    public function getOrders(string $from, string $to): array
    {
        return $this->makeRequest('GET', "order/detail?beginDate={$from}&endDate={$to}");
    }

    public function cancelOrder(int $orderId): array
    {
        $orderStatusData = $this->getOrderStatus($orderId);
        if (empty($orderStatusData['status']) || empty($orderStatusData['status']['code']) || $orderStatusData['status']['code'] !== 100) {
            throw new \RuntimeException('Order status is not available or invalid. Status code is not 100. Status data: '. json_encode($orderStatusData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE), 400);
        }

        return $this->makeRequest('GET', "order-cancel/index?orderId={$orderId}");
    }

    /**
     * @param array{
     *  orderId: int,
     *  statusCode: int,
     * } $data 
     */
    public function testUpdateOrderStatus(array $data): array
    {
        return $this->makeRequest('POST', "order/test-update-status", $data);
    }

    public function getOrderStatus(int $orderId): array
    {
        return $this->makeRequest('GET', "order/track?orderId={$orderId}");
    }

    public function getOrderCancellationReason(int $orderId): array
    {
        return $this->makeRequest('GET', "order/cancellation?id={$orderId}");
    }

    /**
     * @param array{
     *  webhook_url: string,
     *  environment: 'production'|'test',
     * } $data 
     */
    public function setWebhook(array $data): array
    {
        return $this->makeRequest('GET', "webhook/webhook-config", $data);
    }

    public static function getRegions(string $lang = 'ru'): array
    {
        $lang = self::validateLang($lang);

        $regions = [];
        foreach (BtsCatalog::REGIONS as $id => $region) {
            $regions[$id] = $region['name'][$lang];
        }

        return $regions;
    }

    public static function fetchRegions(): array
    {
        return BtsCatalog::REGIONS;
    }

    public static function getRegionByCode(string $regionCode): ?array
    {
        $region = null;

        if (self::existsRegionByCode($regionCode)) {
            $found = BtsCatalog::REGIONS[$regionCode];
            $region = [
                'code' => $regionCode,
                'name_ru' => $found['name']['ru'],
                'name_uz' => $found['name']['uz'],
                'name_en' => $found['name']['en'],
            ];
        }

        return $region;
    }

    public static function existsRegionByCode(string $regionCode): bool
    {
        return \array_key_exists($regionCode, BtsCatalog::REGIONS);
    }

    public static function getRegionName(string $regionCode, string $lang = 'ru'): ?string
    {
        $regionName = null;

        if (self::existsRegionByCode($regionCode)) {
            $lang = self::validateLang($lang);
            $found = BtsCatalog::REGIONS[$regionCode];
            $regionName = $found['name'][$lang];
        }

        return $regionName;
    }

    public static function getCities(string $regionCode): array
    {
        if (! self::existsRegionByCode($regionCode)) {
            return [];
        }

        return BtsCatalog::CITIES[$regionCode];
    }

    public static function existsCityByRegionCodeAndCityCode(string $regionCode, string $cityCode): bool
    {
        return \array_key_exists($regionCode, BtsCatalog::CITIES) && \array_key_exists($cityCode, BtsCatalog::CITIES[$regionCode]);
    }

    public static function getCityByCode(string $regionCode, string $cityCode, string $lang = 'ru'): ?array
    {
        $city = null;

        if (self::existsCityByRegionCodeAndCityCode($regionCode, $cityCode)) {
            $lang = self::validateLang($lang);
            $found = BtsCatalog::CITIES[$regionCode][$cityCode];
            $city = [
                'region_id' => $found['region_code'],
                'name' => $found['name'][$lang]
            ];
        }

        return $city;
    }

    public static function getCityName(string $regionCode, string $cityCode, string $lang = 'ru'): ?string
    {
        $cityName = null;

        if (self::existsCityByRegionCodeAndCityCode($regionCode, $cityCode)) {
            $lang = self::validateLang($lang);
            $found = BtsCatalog::CITIES[$regionCode][$cityCode];
            $cityName = $found['name'][$lang];
        }

        return $cityName;
    }

    public static function searchCities(string $regionCode, string $searchTerm, string $lang = 'ru'): array
    {
        $results = [];
        $searchTerm = mb_strtolower(trim($searchTerm));
        $stopWords = ['shahar', 'shahri', 'tuman', 'tumani', 'город', 'район', 'city', 'district'];
        $cleanSearch = trim(str_replace($stopWords, '', $searchTerm));

        if (empty($searchTerm) || empty(BtsCatalog::CITIES[$regionCode]) || empty($cleanSearch)) {
            return $results;
        }

        $lang = self::validateLang($lang);
        $maxAllowedDistance = mb_strlen($cleanSearch) > 6 ? 2 : 1;

        foreach (BtsCatalog::CITIES[$regionCode] as $cityId => $city) {
            $name = $city['name'][$lang];
            $nameLower = mb_strtolower($name);

            if (mb_strpos($nameLower, $searchTerm) !== false) {
                $results[$cityId] = [
                    'distance' => 0,
                    'matched_name' => $name
                ] + $city;

                continue;
            }

            $cleanName = trim(str_replace($stopWords, '', $nameLower));
            $distance = self::mbLevenshtein($cleanSearch, $cleanName);

            if ($distance <= $maxAllowedDistance) {
                $results[$cityId] = [
                    'distance' => $distance,
                    'matched_name' => $name
                ] + $city;
            }
        }

        uasort(
            $results,
            fn($a, $b) =>
            ($a['distance'] !== $b['distance'])
            ? $a['distance'] <=> $b['distance']
            : mb_strlen($a['matched_name']) <=> mb_strlen($b['matched_name'])
        );

        return $results;
    }

    public static function getAddressInfo(string $cityCode, $lang = 'ru'): ?array
    {
        $lang = self::validateLang($lang);

        foreach (BtsCatalog::REGIONS as $regionCode => $region) {
            if (empty($region['cities']) || !\array_key_exists($cityCode, $region['cities'])) {
                continue;
            }

            $city = $region['cities'][$cityCode];
            $regionName = $region['name'][$lang] ?? '';
            $cityName = $city['name'][$lang] ?? '';
            $fullAddress = $regionName !== '' && $cityName !== ''
                ? "{$regionName}, {$cityName}"
                : ($cityName ?: $regionName);

            return [
                'city_id' => $cityCode,
                'city_name' => $cityName,
                'region_id' => $regionCode,
                'region_name' => $regionName,
                'full_address' => $fullAddress
            ];
        }

        return null;
    }

    public static function getPackageTypes(): array
    {
        return BtsCatalog::PACKAGES;
    }

    public static function getPostTypes(): array
    {
        return BtsCatalog::INSIDES;
    }

    public static function getStatuses(): array
    {
        return BtsCatalog::STATUSES;
    }

    public static function getStatusColorClass(string $status): ?string
    {
        if (empty(BtsCatalog::STATUSES[$status])) {
            return 'label-default';
        }

        return BtsCatalog::STATUSES[$status]['color_class'];
    }

    public static function getStatusLabel(string $status, string $lang = 'ru'): ?string
    {
        if (empty(BtsCatalog::STATUSES[$status])) {
            return null;
        }

        $lang = self::validateLang($lang);
        $status = BtsCatalog::STATUSES[$status];

        return $status['name'][$lang];
    }

    public function validateOrderData(array $data): array
    {
        $errors = [];

        if (!isset($data['pickup_type']) || !\in_array($data['pickup_type'], ['courier', 'self', 'branch'])) {
            $errors['pickup_type'] = "pickup_type is required (courier, self, or branch)";
        }
        if (!isset($data['dropoff_type']) || !\in_array($data['dropoff_type'], ['courier', 'self', 'branch'])) {
            $errors['dropoff_type'] = "dropoff_type is required (courier, self, or branch)";
        }

        if (!isset($data['sender']) || !\is_array($data['sender'])) {
            $errors['sender'] = "sender object is required";
        } else {
            foreach (['name', 'phone', 'address', 'city_code'] as $field) {
                if (empty($data['sender'][$field])) {
                    $errors["sender.{$field}"] = "sender.{$field} is required";
                }
            }
        }

        if (!isset($data['receiver']) || !\is_array($data['receiver'])) {
            $errors['receiver'] = "receiver object is required";
        } else {
            foreach (['name', 'phone', 'address', 'city_code'] as $field) {
                if (empty($data['receiver'][$field])) {
                    $errors["receiver.{$field}"] = "receiver.{$field} is required";
                }
            }
        }

        if (!isset($data['cargo']) || !\is_array($data['cargo'])) {
            $errors['cargo'] = "cargo object is required";
        } else {
            if (empty($data['cargo']['weight'])) {
                $errors['cargo.weight'] = "cargo.weight is required";
            }
            if (empty($data['cargo']['piece'])) {
                $errors['cargo.piece'] = "cargo.piece is required";
            }
        }

        return $errors;
    }

    public function validateCalculateData(array $data): array
    {
        $errors = [];

        if (!isset($data['pickup_type']) || !\in_array($data['pickup_type'], ['courier', 'self', 'branch'])) {
            $errors['pickup_type'] = "pickup_type is required (courier, self, or branch)";
        }
        if (!isset($data['dropoff_type']) || !\in_array($data['dropoff_type'], ['courier', 'self', 'branch'])) {
            $errors['dropoff_type'] = "dropoff_type is required (courier, self, or branch)";
        }
        if (!isset($data['weight']) || empty($data['weight'])) {
            $errors['weight'] = "weight is required";
        }
        if (!isset($data['senderCityCode']) || empty($data['senderCityCode'])) {
            $errors['senderCityCode'] = "senderCityCode is required";
        }
        if (!isset($data['receiverCityCode']) || empty($data['receiverCityCode'])) {
            $errors['receiverCityCode'] = "receiverCityCode is required";
        }

        return $errors;
    }

    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $token = $this->ensureAccessToken();
        $url = "{$this->url}/{$this->version}/{$endpoint}";

        /** @var \GuzzleHttp\Client $client */
        $client = Yii::$app->httpClient;
        $options = [
            'headers' => [
                'language' => 'uz',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'http_errors' => false,
        ];

        if ($method === 'GET') {
            $options['query'] = $data;
        } else {
            $options['json'] = $data;
        }

        try {
            $response = $client->request($method, $url, $options);
            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            $res = json_decode($body, true, 512, JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("API dan yaroqsiz JSON qaytdi. Xatolik: " . json_last_error_msg());
            }

            if ($status === 401 || (isset($res['status_code']) && $res['status_code'] === 401)) {
                if ($this->isRetry) {
                    throw new Exception("API avtorizatsiyadan o'tmadi hatto token yangilanganidan keyin ham. Status: {$status}");
                }

                $this->isRetry = true;
                $this->clearTokensFromCache();
                return $this->makeRequest($method, $endpoint, $data);
            }

            if ($status !== 200 || empty($res['status']) || ($res['status_code'] ?? 0) !== 200) {
                $msg = $res['message'] ?? 'Noma\'lum xatolik';
                throw new Exception("API so'rovi xato tugad: {$msg} (Status: {$status})");
            }

            if (!\is_array($res['data'])) {
                throw new Exception("API muvaffaqiyatli javob qaytardi, lekin data array emas.");
            }
            $this->isRetry = false;

            return $res['data'];

        } catch (GuzzleException $e) {
            Yii::error("API Network Error: " . $e->getMessage(), __METHOD__);
            throw new Exception("API serveriga ulanib bo'lmadi: " . $e->getMessage());
        } catch (\Throwable $e) {
            Yii::error("API General Error: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    private function requestTokens(string $endpoint, array $payload): array
    {
        /** @var \GuzzleHttp\Client $client */
        $client = Yii::$app->httpClient;
        $url = "{$this->url}/auth/{$endpoint}";
        try {
            $response = $client->post($url, [
                'json' => $payload,
                'headers' => [
                    'language' => 'uz',
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 10
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            $res = json_decode($body, true, 512, JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("API dan yaroqsiz JSON qaytdi.");
            }

            if ($status !== 200 || empty($res['status']) || ($res['status_code'] ?? 0) !== 200) {
                $msg = $res['message'] ?? 'Noma\'lum xatolik';
                throw new Exception("Token so'rovi xato tugadi: {$msg}");
            }

            if (empty($res['data']['access_token']) || empty($res['data']['refresh_token'])) {
                throw new Exception("Tokenlar topilmadi.");
            }

            $this->accessToken = $res['data']['access_token'];
            $this->refreshToken = $res['data']['refresh_token'];

            return $res['data'];
        } catch (GuzzleException $e) {
            Yii::error("BTS Token Network Error: " . $e->getMessage(), __METHOD__);
            throw new Exception("Token serveriga ulanib bo'lmadi: " . $e->getMessage());
        }
    }

    private function getCache(): ?CacheInterface
    {
        return Yii::$app?->has('cache') ? Yii::$app->cache : null;
    }

    private function fetchTokensFromApi(): array
    {
        return $this->requestTokens('login', ['login' => $this->login, 'password' => $this->password]);
    }

    private function refreshToken(): array
    {
        return $this->requestTokens('refresh', ['refresh_token' => $this->refreshToken]);
    }

    private function saveTokensToCache(array $tokenData): bool
    {
        if (empty($tokenData['access_token']) || empty($tokenData['refresh_token'])) {
            return false;
        }

        $savedAccess = $this->getCache()?->set(self::ACCESS_TOKEN_CACHE_KEY, $tokenData['access_token'], self::ACCESS_TOKEN_TTL);
        $savedRefresh = $this->getCache()?->set(self::REFRESH_TOKEN_CACHE_KEY, $tokenData['refresh_token'], self::REFRESH_TOKEN_TTL);

        return $savedAccess && $savedRefresh;
    }

    private function clearTokensFromCache(): void
    {
        $this->getCache()?->delete(self::ACCESS_TOKEN_CACHE_KEY);
        $this->getCache()?->delete(self::REFRESH_TOKEN_CACHE_KEY);
        $this->accessToken = null;
        $this->refreshToken = null;
    }

    private function loadTokensFromCache(): bool
    {
        $accessToken = $this->getCache()?->get(self::ACCESS_TOKEN_CACHE_KEY);
        $refreshToken = $this->getCache()?->get(self::REFRESH_TOKEN_CACHE_KEY);

        if (!empty($accessToken) && !empty($refreshToken)) {
            $this->accessToken = $accessToken;
            $this->refreshToken = $refreshToken;

            return true;
        } else if (empty($accessToken) && !empty($refreshToken)) {
            Yii::warning('Access token cache expired, but refresh token is still valid. Attempting to refresh tokens.', __METHOD__);
            try {
                $this->accessToken = null;
                $this->refreshToken = $refreshToken;
                $newTokens = $this->refreshToken();

                if (!empty($newTokens['access_token']) && !empty($newTokens['refresh_token'])) {
                    $this->accessToken = $newTokens['access_token'];
                    $this->refreshToken = $newTokens['refresh_token'];

                    return $this->saveTokensToCache($newTokens);
                }
            } catch (\Throwable $e) {
                Yii::error("Failed to refresh BTS tokens: " . $e->getMessage(), __METHOD__);
                $this->clearTokensFromCache();
            }
        }

        return false;
    }

    private function ensureAccessToken(): string
    {
        if ($this->loadTokensFromCache()) {
            return $this->accessToken;
        }

        $tokenData = $this->fetchTokensFromApi();

        $this->saveTokensToCache($tokenData);

        return $this->accessToken;
    }

    private static function validateLang(string $lang = 'ru'): string
    {
        return \in_array(\strtolower(trim($lang)), BtsCatalog::LANGUAGES, true) ? \strtolower(trim($lang)) : 'ru';
    }

    private static function mbLevenshtein(string $str1, string $str2): int
    {
        $l1 = mb_strlen($str1);
        $l2 = mb_strlen($str2);
        if ($l1 === 0)
            return $l2;
        if ($l2 === 0)
            return $l1;

        $p1 = array_fill(0, $l2 + 1, 0);
        $p2 = array_fill(0, $l2 + 1, 0);

        for ($j = 0; $j <= $l2; $j++)
            $p1[$j] = $j;

        for ($i = 0; $i < $l1; $i++) {
            $p2[0] = $i + 1;
            $c1 = mb_substr($str1, $i, 1);
            for ($j = 0; $j < $l2; $j++) {
                $c2 = mb_substr($str2, $j, 1);
                $cost = ($c1 === $c2) ? 0 : 1;
                $p2[$j + 1] = min($p1[$j + 1] + 1, $p2[$j] + 1, $p1[$j] + $cost);
            }
            $p1 = $p2;
        }

        return $p1[$l2];
    }
}