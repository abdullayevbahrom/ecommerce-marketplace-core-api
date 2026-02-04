<?php

namespace app\services;

use Yii;
use yii\web\HttpException;

class DidoxService
{
    const STAGE_URL = 'https://stage.goodsign.biz';
    const PROD_URL = 'https://api.einvoice.example.com';
    
    private $baseUrl;
    private $partnerToken;
    
    public function __construct()
    {
        // Set base URL based on environment
        $this->baseUrl = YII_ENV_DEV ? self::STAGE_URL : self::PROD_URL;
        
        // Get partner token from params or config
        $this->partnerToken = isset(Yii::$app->params['didoxPartnerToken']) ? Yii::$app->params['didoxPartnerToken'] : '';
    }
    
    /**
     * Get automated authentication token using local PFX and Signer Service
     * Mirroring logic from Python: manager.py -> get_token
     * @return array ['success' => bool, 'token' => string|null, 'error' => string|null]
     */
    public function getAuthTokenFromPfx()
    {
        // Load Settings
        $settings = \app\models\Settings::find()
            ->where(['type' => ['didox_pfx_path', 'didox_pfx_password', 'didox_signer_url', 'didox_seller_inn']])
            ->all();
        $config = \yii\helpers\ArrayHelper::map($settings, 'type', 'content');
        
        $pfxPath = $config['didox_pfx_path'] ?? '';
        $password = $config['didox_pfx_password'] ?? '';
        $signerUrl = $config['didox_signer_url'] ?? 'http://127.0.0.1:8080/generate';
        $jshir = $config['didox_seller_inn'] ?? ''; 
        
        if (empty($pfxPath) || empty($password) || !file_exists($pfxPath)) {
            return ['success' => false, 'error' => 'PFX file not configured or missing.'];
        }

        if (empty($jshir)) {
             return ['success' => false, 'error' => 'Seller INN/JSHIR not configured.'];
        }

        $signerData = [
            'pfxFilePath' => $pfxPath,
            'password' => $password,
            'alias' => '', 
            'data' => $jshir,
            'attached' => true
        ];
        
        try {
            // Call Signer Service
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $signerUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($signerData),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 10
            ]);
            $signerResponse = curl_exec($ch);
            $signerHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $signerError = curl_error($ch);
            curl_close($ch);

            if ($signerError || $signerHttpCode != 200) {
                return ['success' => false, 'error' => 'Signer Service failed: ' . ($signerError ?: "HTTP $signerHttpCode")];
            }

            $tokenData = json_decode($signerResponse, true); 
            if (!isset($tokenData['pkcs7']) || !isset($tokenData['signature'])) {
                return ['success' => false, 'error' => 'Invalid response from Signer Service'];
            }

            // 2. Get Timestamp from Didox
            $timestampRes = $this->createTimestamp($tokenData['pkcs7'], $tokenData['signature']);
            if (!$timestampRes['success']) {
                return ['success' => false, 'error' => 'Failed to get timestamp: ' . json_encode($timestampRes)];
            }
            
            $timestampToken = $timestampRes['data']['timeStampTokenB64'];

            // 3. Get Token from Didox
            $authUrl = "/v1/auth/{$jshir}/token/ru";
            $authRes = $this->makeRequest('POST', $authUrl, ['signature' => $timestampToken]);
            
            if ($authRes['isOk'] && isset($authRes['data']['token'])) {
                 return ['success' => true, 'token' => $authRes['data']['token']];
            } else {
                 return ['success' => false, 'error' => 'Didox Auth failed: ' . json_encode($authRes)];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Exception during auto-auth: ' . $e->getMessage()];
        }
    }

    /**
     * Make HTTP request using cURL
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    private function makeRequest($method, $url, $data = [])
    {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->partnerToken
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        return [
            'httpCode' => $httpCode,
            'data' => $decodedResponse,
            'isOk' => $httpCode >= 200 && $httpCode < 300
        ];
    }
    
    /**
     * Validate Didox token
     * @param string $token - Didox token to validate
     * @param string $taxId - Tax ID for additional validation
     * @return bool
     */
    public function validateToken($token, $taxId)
    {
        try {
            // For now, we'll assume the token is valid if it's properly formatted
            // In a real implementation, you might want to call a Didox validation endpoint
            if (empty($token) || strlen($token) < 10) {
                return false;
            }
            
            // Additional validation logic can be added here
            // For example, checking token format, expiration, etc.
            
            return true;
        } catch (\Exception $e) {
            Yii::error('Token validation error: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Extract INN from E-IMZO certificate alias
     * @param string $alias
     * @return string|null
     */
    public function extractInnFromAlias($alias)
    {
        if (preg_match('/1\.2\.860\.3\.16\.1\.1=(\d+)/', $alias, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Create timestamp for signature according to Didox documentation
     * @param string $pkcs7_64 - PKCS7 signature in base64
     * @param string $signature_hex - Signature in hex format
     * @return array
     */
    public function createTimestamp($pkcs7_64, $signature_hex)
    {
        try {
            $timestampData = [
                'pkcs7' => $pkcs7_64,
                'signatureHex' => $signature_hex
            ];
            
            $response = $this->makeRequest('POST', '/v1/dsvs/timestamp', $timestampData);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('Didox timestamp creation error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Register user with Didox following the official documentation
     * @param array $userData - User registration data
     * @return array
     */
    public function registerUser($userData)
    {
        try {
            // Validate required fields according to Didox documentation
            $requiredFields = ['email', 'mobile', 'password', 'accept', 'signature'];
            foreach ($requiredFields as $field) {
                if (!isset($userData[$field]) || empty($userData[$field])) {
                    return [
                        'success' => false,
                        'error' => "Missing required field: $field",
                        'userExists' => false
                    ];
                }
            }
            
            // Prepare registration data exactly as per Didox API
            $registrationData = [
                'email' => $userData['email'],
                'mobile' => $userData['mobile'], 
                'password' => $userData['password'],
                'accept' => $userData['accept'],
                'signature' => $userData['signature'] // Signed INN with attached timestamp in base64
            ];
            
            $response = $this->makeRequest('POST', '/v1/auth/signup', $registrationData);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'userExists' => $response['httpCode'] === 422 && 
                               isset($response['data']['taxId']) && 
                               in_array('validation.unique', $response['data']['taxId'])
            ];
            
        } catch (\Exception $e) {
            Yii::error('Didox registration error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'userExists' => false
            ];
        }
    }

    /**
     * Extract certificate information from E-IMZO token/certificate data
     * @param array $certificateData - Certificate data from E-IMZO
     * @return array
     */
    public function extractCertificateInfo($certificateData)
    {
        $extractedInfo = [];
        
        try {
            // Extract common certificate fields
            if (isset($certificateData['serialNumber'])) {
                $extractedInfo['serial_number'] = $certificateData['serialNumber'];
            }
            
            if (isset($certificateData['validFrom'])) {
                $extractedInfo['valid_from'] = $certificateData['validFrom'];
            }
            
            if (isset($certificateData['validTo'])) {
                $extractedInfo['valid_to'] = $certificateData['validTo'];
            }
            
            if (isset($certificateData['subject'])) {
                $subject = $certificateData['subject'];
                
                // Extract common name
                if (isset($subject['CN'])) {
                    $extractedInfo['common_name'] = $subject['CN'];
                }
                
                // Extract organization
                if (isset($subject['O'])) {
                    $extractedInfo['organization'] = $subject['O'];
                }
                
                // Extract country
                if (isset($subject['C'])) {
                    $extractedInfo['country'] = $subject['C'];
                }
                
                // Extract email
                if (isset($subject['emailAddress'])) {
                    $extractedInfo['email'] = $subject['emailAddress'];
                }
                
                // Extract INN/Tax ID from subject
                if (isset($subject['1.2.860.3.16.1.1'])) {
                    $extractedInfo['inn'] = $subject['1.2.860.3.16.1.1'];
                }
            }
            
            if (isset($certificateData['issuer'])) {
                $extractedInfo['issuer'] = $certificateData['issuer'];
            }
            
            if (isset($certificateData['keyUsage'])) {
                $extractedInfo['key_usage'] = $certificateData['keyUsage'];
            }
            
            if (isset($certificateData['alias'])) {
                $extractedInfo['alias'] = $certificateData['alias'];
                
                // Try to extract INN from alias as fallback
                if (!isset($extractedInfo['inn'])) {
                    $innFromAlias = $this->extractInnFromAlias($certificateData['alias']);
                    if ($innFromAlias) {
                        $extractedInfo['inn'] = $innFromAlias;
                    }
                }
            }
            
            // Extract personal information if available
            if (isset($certificateData['personalInfo'])) {
                $personalInfo = $certificateData['personalInfo'];
                
                if (isset($personalInfo['firstName'])) {
                    $extractedInfo['first_name'] = $personalInfo['firstName'];
                }
                
                if (isset($personalInfo['lastName'])) {
                    $extractedInfo['last_name'] = $personalInfo['lastName'];
                }
                
                if (isset($personalInfo['middleName'])) {
                    $extractedInfo['middle_name'] = $personalInfo['middleName'];
                }
                
                if (isset($personalInfo['fullName'])) {
                    $extractedInfo['full_name'] = $personalInfo['fullName'];
                }
                
                if (isset($personalInfo['passport'])) {
                    $extractedInfo['passport'] = $personalInfo['passport'];
                }
                
                if (isset($personalInfo['birthDate'])) {
                    $extractedInfo['birth_date'] = $personalInfo['birthDate'];
                }
                
                if (isset($personalInfo['gender'])) {
                    $extractedInfo['gender'] = $personalInfo['gender'];
                }
            }
            
            // Store the original certificate data for future reference
            $extractedInfo['original_certificate'] = $certificateData;
            
        } catch (\Exception $e) {
            Yii::error('Certificate info extraction error: ' . $e->getMessage(), __METHOD__);
        }
        
        return $extractedInfo;
    }

    /**
     * Authenticate user with existing Didox account
     * @param string $token - Didox authentication token
     * @param string $taxId - Tax ID for validation
     * @return array
     */
    public function authenticateWithDidox($token, $taxId)
    {
        try {
            // Validate the token format first
            if (!$this->validateToken($token, $taxId)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid token format'
                ];
            }
            
            // In a real implementation, you would call Didox API to validate the token
            // and get user information. For now, we'll simulate this process.
            
            // This would be something like: GET /v1/auth/user with Bearer token
            // $response = $this->makeRequest('GET', '/v1/auth/user', [], $token);
            
            // For now, return success if token format is valid
            return [
                'valid' => true,
                'token' => $token,
                'tax_id' => $taxId,
                'authenticated_at' => date('Y-m-d H:i:s'),
                'user_info' => [
                    'tax_id' => $taxId,
                    'token_valid' => true
                ]
            ];
            
        } catch (\Exception $e) {
            Yii::error('Didox authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate and extract information from Didox token
     * @param string $token - Didox token
     * @param string $taxId - Tax ID for validation
     * @return array
     */
    public function validateAndExtractTokenInfo($token, $taxId)
    {
        try {
            // First validate the token
            if (!$this->validateToken($token, $taxId)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid token'
                ];
            }
            
            // In a real implementation, you would call Didox API to get detailed token info
            // For now, we'll return basic validation result
            return [
                'valid' => true,
                'token' => $token,
                'tax_id' => $taxId,
                'validated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            Yii::error('Token validation and extraction error: ' . $e->getMessage(), __METHOD__);
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get list of documents from DIDOX
     * @param array $params - Search parameters (page, limit, status, etc.)
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getDocuments($params = [], $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $defaultParams = [
                'page' => 1,
                'limit' => 20,
                'owner' => 1 // Outgoing documents by default
            ];
            
            $queryParams = array_merge($defaultParams, $params);
            $url = '/v2/documents?' . http_build_query($queryParams);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new \Exception('cURL Error: ' . $error);
            }
            
            $decodedResponse = json_decode($response, true);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'data' => $decodedResponse,
                'httpCode' => $httpCode
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get documents error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create new document in DIDOX
     * @param array $documentData - Document data according to DIDOX API
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function createDocument($documentData, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // Get document type for the endpoint URL and remove from data
            $docType = isset($documentData['doctype']) ? $documentData['doctype'] : '000';
            
            // Remove doctype from the JSON body as it should be in the URL path
            $createData = $documentData;
            if (isset($createData['doctype'])) {
                unset($createData['doctype']);
            }
            
            // Use correct DIDOX endpoint: /v1/documents/:docType/create
            $response = $this->makeRequestWithHeaders('POST', "/v1/documents/{$docType}/create", $createData, $headers);
            
            // Extract error information if request failed
            $error = null;
            if (!$response['isOk']) {
                if (isset($response['data']['error'])) {
                    $error = $response['data']['error'];
                } elseif (isset($response['data']['message'])) {
                    $error = $response['data']['message'];
                } elseif (isset($response['data']['errors'])) {
                    $error = $response['data']['errors'];
                } else {
                    $error = "HTTP {$response['httpCode']}";
                    if (!empty($response['data'])) {
                        $error .= ": " . json_encode($response['data'], JSON_UNESCAPED_UNICODE);
                    }
                }
            }

            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => $error
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX create document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send document to partner after signing
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function sendDocumentToPartner($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // Send document to partner
            $response = $this->makeRequestWithHeaders('POST', "/v1/documents/{$docId}/send", [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX send document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing document in DIDOX
     * @param string $docId - Document ID
     * @param array $documentData - Updated document data
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function updateDocument($docId, $documentData, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // Get document type for the endpoint URL and remove from data
            $docType = isset($documentData['doctype']) ? $documentData['doctype'] : '002';
            
            // Remove doctype from the JSON body as it should be in the URL path
            $updateData = $documentData;
            if (isset($updateData['doctype'])) {
                unset($updateData['doctype']);
            }
            
            // Use correct DIDOX endpoint: POST /v1/documents/:docId/update/:doctype
            $response = $this->makeRequestWithHeaders('POST', "/v1/documents/{$docId}/update/{$docType}", $updateData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX update document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document details from DIDOX
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getDocument($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('GET', '/v1/documents/' . $docId, [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'debug' => $response['debug'] ?? null,
                'error' => !$response['isOk'] ? ($response['data']['message'] ?? $response['data']['error'] ?? 'Unknown error') : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document PDF from DIDOX
     * URL format: /v1/documents/view/{docId}/pdf/{lang}
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @param string $lang - Language code (uz, ru, en)
     * @return array - Contains 'success', 'data' (PDF binary), 'contentType', 'error'
     */
    public function getDocumentPdf($docId, $userKey = '', $lang = 'uz')
    {
        try {
            $headers = [
                'Partner-Authorization: ' . $this->partnerToken
            ];

            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }

            $url = '/v1/documents/view/' . $docId . '/pdf/' . $lang;
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->baseUrl . $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 60, // Longer timeout for PDF download
                CURLOPT_FOLLOWLOCATION => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception('cURL Error: ' . $error);
            }

            // Check if response is PDF (application/pdf) or error (application/json)
            $isPdf = strpos($contentType, 'application/pdf') !== false;
            
            if ($httpCode >= 200 && $httpCode < 300 && $isPdf) {
                return [
                    'success' => true,
                    'data' => $response,
                    'contentType' => $contentType,
                    'httpCode' => $httpCode
                ];
            } else {
                // Try to decode error response
                $errorData = json_decode($response, true);
                $errorMessage = isset($errorData['message']) ? $errorData['message'] : (isset($errorData['error']) ? $errorData['error'] : 'Failed to get PDF');
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $httpCode,
                    'debug' => [
                        'request_url' => $this->baseUrl . $url,
                        'response_raw' => substr($response, 0, 500)
                    ]
                ];
            }

        } catch (\Exception $e) {
            Yii::error('DIDOX get document PDF error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document data for signing from DIDOX
     * @param string $docId - Document ID
     * @param string $action - Action type (accept, cancel, reject, etc.)
     * @param string $userKey - User key for authentication
     * @param array $additionalData - Additional data like comment
     * @return array
     */
    public function getDocumentToSign($docId, $action = 'accept', $userKey = '', $additionalData = [])
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $requestData = array_merge(['action' => $action], $additionalData);
            
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/tosign', $requestData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get document to sign error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document data for signing (step 1 of DIDOX signing process)
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getDocumentForSigning($docId, $userKey = '')
    {
        try {
            // Prepare headers for DIDOX API request
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $endpoint = '/v1/documents/' . $docId . '?owner=1';
            Yii::info("DIDOX API CALL: GET {$this->baseUrl}{$endpoint}", __METHOD__);
            
            // Make the actual DIDOX API request (Step 3 from documentation)
            $response = $this->makeRequestWithHeaders('GET', $endpoint, [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get document for signing error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sign document in DIDOX (step 2 of DIDOX signing process)
     * @param string $docId - Document ID
     * @param string $signature - timeStampTokenB64 signature
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function signDocument($docId, $signature, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $requestData = ['signature' => $signature];
            
            // FIXED: Use correct endpoint for signing outgoing documents
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/sign', $requestData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'debug' => $response['debug'] ?? null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX sign document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'request_url' => $this->baseUrl . '/v1/documents/' . $docId . '/sign',
                    'request_method' => 'POST',
                    'request_body' => json_encode(['signature' => $signature], JSON_PRETTY_PRINT),
                    'signature_length' => strlen($signature),
                    'exception' => $e->getMessage(),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * Cancel/Delete document in DIDOX
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function cancelDocument($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/cancel', [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode']
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX cancel document error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate user with E-IMZO signature via DIDOX API
     * @param string $taxId
     * @param string $signature
     * @return array
     */
    public function authenticateWithEimzo($taxId, $signature)
    {
        try {
            // Validate input parameters
            if (empty($taxId) || empty($signature)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID and signature are required'
                ];
            }
            
            // Validate tax ID format (should be 9 digits)
            if (!preg_match('/^\d{9}$/', $taxId)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID must be 9 digits'
                ];
            }
            
            // Prepare request data according to DIDOX API documentation
            $requestData = [
                'signature' => $signature
            ];
            
            // Make request to DIDOX authentication endpoint
            $url = "/v1/auth/{$taxId}/token/ru";
            $response = $this->makeRequest('POST', $url, $requestData);
            
            if ($response['isOk'] && isset($response['data']['token'])) {
                return [
                    'success' => true,
                    'token' => $response['data']['token'],
                    'data' => $response['data'],
                    'message' => 'E-IMZO authentication successful'
                ];
            } else {
                // Handle different error scenarios
                $errorMessage = 'Authentication failed';
                
                if (isset($response['data']['message'])) {
                    $errorMessage = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } elseif (isset($response['data']['errors'])) {
                    $errorDetails = is_array($response['data']['errors']) 
                        ? implode(', ', array_values($response['data']['errors'])) 
                        : $response['data']['errors'];
                    $errorMessage = "Validation errors: {$errorDetails}";
                } elseif ($response['httpCode'] === 422) {
                    $errorMessage = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } elseif ($response['httpCode'] === 401) {
                    $errorMessage = 'Authentication failed - invalid credentials';
                } elseif ($response['httpCode'] === 404) {
                    $errorMessage = 'User not found or not registered in DIDOX';
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $response['httpCode'],
                    'data' => $response['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('E-IMZO authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Authentication service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate user with password via DIDOX API
     * According to API documentation: POST /v1/auth/:taxId/password/:locale
     * @param string $taxId
     * @param string $password
     * @param string $locale
     * @return array
     */
    public function authenticateWithPassword($taxId, $password, $locale = 'ru')
    {
        try {
            // Validate input parameters
            if (empty($taxId) || empty($password)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID and password are required'
                ];
            }
            
            // Validate tax ID format (should be 9 digits)
            if (!preg_match('/^\d{9}$/', $taxId)) {
                return [
                    'success' => false,
                    'error' => 'Tax ID must be 9 digits'
                ];
            }
            
            // Prepare request data according to DIDOX API documentation
            $requestData = [
                'password' => $password
            ];
            
            // Make request to DIDOX password authentication endpoint
            $url = "/v1/auth/{$taxId}/password/{$locale}";
            $response = $this->makeRequest('POST', $url, $requestData);
            
            if ($response['isOk'] && isset($response['data']['token'])) {
                return [
                    'success' => true,
                    'token' => $response['data']['token'],
                    'data' => $response['data'],
                    'message' => 'Password authentication successful',
                    'related_companies' => $response['data']['related_companies'] ?? null,
                    'related_branches' => $response['data']['related_branches'] ?? null
                ];
            } else {
                // Handle different error scenarios
                $errorMessage = 'Authentication failed';
                
                if (isset($response['data']['message'])) {
                    $errorMessage = $response['data']['message'];
                } elseif (isset($response['data']['errors'])) {
                    $errorDetails = is_array($response['data']['errors']) 
                        ? implode(', ', array_values($response['data']['errors'])) 
                        : $response['data']['errors'];
                    $errorMessage = "Validation errors: {$errorDetails}";
                } elseif ($response['httpCode'] === 422) {
                    if (isset($response['data']['taxId']) && in_array('validation.exists', $response['data']['taxId'])) {
                        $errorMessage = 'User not registered in DIDOX system';
                    } else {
                        $errorMessage = 'Invalid password or user credentials';
                    }
                } elseif ($response['httpCode'] === 401) {
                    $errorMessage = 'Authentication failed - invalid password';
                } elseif ($response['httpCode'] === 404) {
                    $errorMessage = 'User not found or not registered in DIDOX';
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $response['httpCode'],
                    'data' => $response['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('Password authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Authentication service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Login to company as individual person via DIDOX API
     * According to API documentation: POST /v1/auth/company/:companyTaxId/login/:locale
     * Requires individual's token first (from authenticateWithPassword or authenticateWithEimzo)
     * @param string $companyTaxId
     * @param string $individualToken
     * @param string $locale
     * @return array
     */
    public function loginToCompany($companyTaxId, $individualToken, $locale = 'ru')
    {
        try {
            // Validate input parameters
            if (empty($companyTaxId) || empty($individualToken)) {
                return [
                    'success' => false,
                    'error' => 'Company Tax ID and individual token are required'
                ];
            }
            
            // Validate company tax ID format (should be 9 digits)
            if (!preg_match('/^\d{9}$/', $companyTaxId)) {
                return [
                    'success' => false,
                    'error' => 'Company Tax ID must be 9 digits'
                ];
            }
            
            // Prepare headers with individual's token
            $headers = [
                'Content-Type: application/json',
                'user-key: ' . $individualToken
            ];
            
            // Make request to DIDOX company login endpoint
            $url = "/v1/auth/company/{$companyTaxId}/login/{$locale}";
            $response = $this->makeRequestWithHeaders('POST', $url, [], $headers);
            
            if ($response['isOk'] && isset($response['data']['token'])) {
                return [
                    'success' => true,
                    'token' => $response['data']['token'],
                    'data' => $response['data'],
                    'message' => 'Company login successful',
                    'permissions' => $response['data']['permissions'] ?? null
                ];
            } else {
                // Handle different error scenarios
                $errorMessage = 'Company login failed';
                
                if (isset($response['data']['message'])) {
                    $errorMessage = $response['data']['message'];
                } elseif (isset($response['data']['errors'])) {
                    $errorDetails = is_array($response['data']['errors']) 
                        ? implode(', ', array_values($response['data']['errors'])) 
                        : $response['data']['errors'];
                    $errorMessage = "Validation errors: {$errorDetails}";
                } elseif ($response['httpCode'] === 422) {
                    if (isset($response['data']['taxId']) && in_array('validation.exists', $response['data']['taxId'])) {
                        $errorMessage = 'Company not found or not accessible';
                    } else {
                        $errorMessage = 'No permission to access this company';
                    }
                } elseif ($response['httpCode'] === 401) {
                    $errorMessage = 'Authentication failed - invalid or expired individual token';
                } elseif ($response['httpCode'] === 403) {
                    $errorMessage = 'Access denied - no permission to login to this company';
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'httpCode' => $response['httpCode'],
                    'data' => $response['data'] ?? null
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('Company login error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Company login service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user profile data from DIDOX
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getUserProfile($userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('GET', '/v1/profile', [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format error message from DIDOX API response
     * @param mixed $error - Error response (can be string or array)
     * @return string
     */
    private function formatErrorMessage($error)
    {
        if (is_array($error)) {
            return json_encode($error);
        }
        return (string)$error;
    }

    /**
     * Get incoming document for signing (owner=0)
     * According to DIDOX documentation: GET /v1/documents/{doc_id}?owner=0
     * Returns the toSign value which contains sender's signature in base64
     * @param string $docId - Document ID
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getIncomingDocumentForSigning($docId, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // For incoming documents, use owner=0 to get toSign value
            $response = $this->makeRequestWithHeaders('GET', '/v1/documents/' . $docId . '?owner=0', [], $headers);
            
            error_log('getIncomingDocumentForSigning response:');
            error_log(json_encode($response));
            
            if ($response['isOk']) {
                // Extract toSign value from DIDOX response
                $toSignValue = null;
                if (isset($response['data']['toSign'])) {
                    $toSignValue = $response['data']['toSign'];
                } elseif (isset($response['data']['data']['toSign'])) {
                    $toSignValue = $response['data']['data']['toSign'];
                } elseif (is_string($response['data'])) {
                    // Sometimes DIDOX returns the toSign value directly as a string
                    $toSignValue = $response['data'];
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'toSign' => $toSignValue,
                        'original_response' => $response['data']
                    ],
                    'httpCode' => $response['httpCode'],
                    'debug' => [
                        'endpoint' => '/v1/documents/' . $docId . '?owner=0',
                        'method' => 'GET',
                        'toSign_found' => !empty($toSignValue),
                        'toSign_length' => $toSignValue ? strlen($toSignValue) : 0
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'data' => $response['data'],
                    'httpCode' => $response['httpCode'],
                    'error' => !$response['isOk'] && isset($response['data']['error']) ? 
                        $this->formatErrorMessage($response['data']['error']) : null,
                    'debug' => [
                        'endpoint' => '/v1/documents/' . $docId . '?owner=0',
                        'method' => 'GET'
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Accept/Sign incoming document 
     * @param string $docId - Document ID
     * @param string $signature - Final signature with timestamp
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function acceptIncomingDocument($docId, $signature, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $requestData = [
                'signature' => $signature
            ];
            
            // Use the accept endpoint for incoming documents
            $response = $this->makeRequestWithHeaders('POST', '/v1/documents/' . $docId . '/sign', $requestData, $headers);
            
            error_log('acceptIncomingDocument');
            error_log(json_encode($response));
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? 
                    $this->formatErrorMessage($response['data']['error']) : null,
                'debug' => [
                    'endpoint' => '/v1/documents/' . $docId . '/sign',
                    'method' => 'POST',
                    'request_data' => $requestData
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make HTTP request with custom headers
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    private function makeRequestWithHeaders($method, $url, $data = [], $headers = [])
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        return [
            'httpCode' => $httpCode,
            'data' => $decodedResponse,
            'isOk' => $httpCode >= 200 && $httpCode < 300,
            'debug' => [
                'request_url' => $this->baseUrl . $url,
                'request_method' => $method,
                'request_headers' => $headers,
                'request_body' => !empty($data) ? json_encode($data, JSON_PRETTY_PRINT) : null,
                'response_raw' => $response,
                'curl_error' => $error
            ]
        ];
    }

    /**
     * Get linked ИКПУ codes for current profile
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function getProfileProductClassCodes($userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('GET', '/v1/profile/productClassCodes', [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX get profile product class codes error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add ИКПУ code to current profile
     * @param array $classData - Class code data
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function addProfileProductClass($classData, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('POST', '/v1/profile/productClasses', $classData, $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX add profile product class error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove ИКПУ code from current profile
     * @param string $classCode - Class code to remove
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function removeProfileProductClass($classCode, $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $response = $this->makeRequestWithHeaders('DELETE', '/v1/profile/productClasses/' . urlencode($classCode), [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX remove profile product class error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search available ИКПУ codes
     * @param int $page - Page number
     * @param string $lang - Language (ru/uz)
     * @param string $search - Search query
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function searchProductClasses($page = 1, $lang = 'ru', $search = '', $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            $params = [
                'page' => $page,
                'lang' => $lang
            ];
            
            if (!empty($search)) {
                $params['search'] = $search;
            }
            
            $queryString = http_build_query($params);
            $endpoint = '/v1/profile/productClasses/?' . $queryString;
            
            $response = $this->makeRequestWithHeaders('GET', $endpoint, [], $headers);
            
            return [
                'success' => $response['isOk'],
                'data' => $response['data'],
                'httpCode' => $response['httpCode'],
                'error' => !$response['isOk'] && isset($response['data']['error']) ? $response['data']['error'] : null
            ];
            
        } catch (\Exception $e) {
            Yii::error('DIDOX search product classes error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check specific ИКПУ code by its number
     * @param string $classCode - ИКПУ class code (17 digits)
     * @param string $lang - Language (ru/uz)
     * @param string $userKey - User key for authentication
     * @return array
     */
    public function checkProductClassByCode($classCode, $lang = 'ru', $userKey = '')
    {
        try {
            $headers = [
                'Content-Type: application/json',
                'Partner-Authorization: ' . $this->partnerToken
            ];
            
            if ($userKey) {
                $headers[] = 'user-key: ' . $userKey;
            }
            
            // Use search endpoint with exact code match
            $params = [
                'page' => 1,
                'lang' => $lang,
                'search' => $classCode
            ];
            
            $queryString = http_build_query($params);
            $endpoint = '/v1/profile/productClasses/?' . $queryString;
            
            $response = $this->makeRequestWithHeaders('GET', $endpoint, [], $headers);
            
            if ($response['isOk']) {
                $responseData = $response['data'] ?? [];
                $searchResults = $responseData['data'] ?? $responseData ?? [];
                
                // Find exact match by classCode
                $exactMatch = null;
                if (is_array($searchResults)) {
                    foreach ($searchResults as $result) {
                        $resultCode = $result['classCode'] ?? $result['code'] ?? '';
                        if ($resultCode === $classCode) {
                            $exactMatch = $result;
                            break;
                        }
                    }
                }
                
                if ($exactMatch) {
                    return [
                        'success' => true,
                        'data' => $exactMatch,
                        'httpCode' => $response['httpCode']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'ИКПУ код не найден',
                        'httpCode' => 404
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'data' => $response['data'],
                    'httpCode' => $response['httpCode'],
                    'error' => isset($response['data']['error']) ? $response['data']['error'] : 'Ошибка запроса DIDOX API'
                ];
            }
            
        } catch (\Exception $e) {
            Yii::error('DIDOX check product class by code error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 