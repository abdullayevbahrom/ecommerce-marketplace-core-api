<?php

namespace app\services;

use Yii;

/**
 * ASL Belgisi Service — checks products against the national product registry.
 *
 * Searches by GTIN (barcode) using the public API:
 * GET {base_url}/public/api/v1/product-registry/product/search-by-gtin?gtin={GTIN}
 *
 * Docs: https://xtrace.marking.example.com
 */
class AslBelgisiService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $config = Yii::$app->params['aslBelgisi'] ?? [];
        $this->apiKey = $config['apiKey'] ?? '';
        $this->baseUrl = rtrim($config['baseUrl'] ?? 'https://xtrace.marking.example.com', '/');
    }

    /**
     * Check if the service is configured with an API key.
     */
    public function isConfigured()
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }

    /**
     * Search product by GTIN (barcode) in ASL Belgisi registry.
     *
     * @param string $gtin GTIN barcode (up to 14 digits)
     * @return array ['success' => bool, 'products' => array|null, 'error' => string|null]
     */
    public function searchByGtin($gtin)
    {
        if (empty($gtin)) {
            return [
                'success' => false,
                'error' => 'GTIN is required',
            ];
        }

        $url = $this->baseUrl . '/public/api/v1/product-registry/product/search-by-gtin?gtin=' . urlencode($gtin);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        Yii::info("ASL Belgisi API: GET search-by-gtin gtin={$gtin}", __METHOD__);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlErrno) {
            Yii::error("ASL Belgisi cURL error #{$curlErrno}: {$curlError}", __METHOD__);
            return [
                'success' => false,
                'error' => "Connection error: {$curlError}",
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $detail = $data['detail'] ?? $data['message'] ?? $data['error'] ?? "HTTP {$httpCode}";
            Yii::error("ASL Belgisi API error: {$detail}", __METHOD__);
            return [
                'success' => false,
                'error' => "API error: {$detail}",
            ];
        }

        $products = $data['products'] ?? [];

        return [
            'success' => true,
            'products' => $products,
            'found' => count($products) > 0,
        ];
    }

    /**
     * Parse a single product entry from ASL Belgisi response.
     *
     * @param array $aslProduct One element from the 'products' array
     * @return array Normalized fields for ProductAslBelgisi
     */
    public function parseProduct($aslProduct)
    {
        $productName = $aslProduct['productName'] ?? [];

        return [
            'asl_product_id' => $aslProduct['id'] ?? null,
            'gtin' => $aslProduct['gtin'] ?? null,
            'product_name_ru' => $productName['ru'] ?? null,
            'product_name_uz' => $productName['uz'] ?? null,
            'inn' => $aslProduct['inn'] ?? null,
            'product_group' => isset($aslProduct['pg']['value']) ? $aslProduct['pg']['value'] : null,
            'status' => isset($aslProduct['status']['value']) ? $aslProduct['status']['value'] : null,
        ];
    }
}
