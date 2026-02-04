<?php

namespace app\services;

use Yii;
use app\models\order\Order;
use app\models\didox\DidoxDocument;
use app\models\didox\DidoxDocumentInvoice;
use app\models\didox\DidoxDocumentArbitrary;
use app\models\didox\DidoxDocumentIncludedProducts;
use app\models\shop\seller\ShopSeller;
use app\models\user\User;
use app\models\Log;

/**
 * Service for handling automated Didox document creation from Orders
 */
class DidoxOrderService
{
    /**
     * Create Didox documents (Invoice and Arbitrary Contract) for an order
     * 
     * @param Order $order
     * @return array Result status ['success' => bool, 'messages' => array]
     */
    public static function createDocuments(Order $order)
    {
        $result = [
            'success' => true,
            'messages' => [],
            'documents' => []
        ];

        Log::log('didox_order', "Starting Didox document creation for Order #{$order->id}");

        // 2. Data Retrieval: Seller Info (Global Settings)
        $settings = \app\models\Settings::find()
            ->where(['type' => [
                'didox_seller_inn', 
                'didox_seller_name', 
                'didox_seller_address', 
                'didox_seller_account', 
                'didox_seller_mfo',
                'didox_seller_vat_reg_code'
            ]])
            ->all();
        $settingsMap = \yii\helpers\ArrayHelper::map($settings, 'type', 'content');

        // Prepare common data
        // TODO: Change default vat_reg_code value for production
        $sellerInfo = [
            'tin' => $settingsMap['didox_seller_inn'] ?? '',
            'name' => $settingsMap['didox_seller_name'] ?? '',
            'address' => $settingsMap['didox_seller_address'] ?? '',
            'account' => $settingsMap['didox_seller_account'] ?? '',
            'bank_id' => $settingsMap['didox_seller_mfo'] ?? '',
            'director' => '',
            'accountant' => '',
            'vat_reg_code' => $settingsMap['didox_seller_vat_reg_code'] ?? '300000000001', // Default '0' for testing
        ];

        // Fallback for name if empty in settings (though user provided specific name)
        if (empty($sellerInfo['name']) && $order->shop) {
             $sellerInfo['name'] = $order->shop->name_ru;
        }

        // Check essential seller fields
        if (empty($sellerInfo['tin'])) {
             Yii::warning("Order #{$order->id}: Global Seller Settings have no TIN (didox_seller_inn). Skipping Didox creation.", 'didox_order');
             Log::log('didox_order', "Order #{$order->id}: Global Seller Settings have no TIN (didox_seller_inn). Skipping Didox creation.", $sellerInfo, 'warning');
             $result['success'] = false;
             $result['messages'][] = "Global Seller Settings has no TIN. Skipped.";
             return $result;
        }

        // 3. Data Retrieval: Buyer Info
        $buyer = $order->user;
        $buyerInfo = self::getBuyerInfo($order, $buyer);
        Log::log('didox_order', "Buyer Info for Order #{$order->id}", $buyerInfo);

        // Didox Service for upload
        $didoxService = new \app\services\DidoxService();
        $userKey = Yii::$app->session->get('didox_token', ''); // Get token if available, otherwise empty

        // If no session token, try to use System Credentials or Auto-Auth
        if (empty($userKey)) {
            // 1. Try Auto-Auth using PFX (if configured)
            $autoAuth = $didoxService->getAuthTokenFromPfx();
            if ($autoAuth['success']) {
                $userKey = $autoAuth['token'];
                Yii::info("Using Auto-Generated Didox Token from PFX for order #{$order->id}.", 'didox_order');
            } else {
                // 2. Fallback to manually stored System Token
                if (!empty($autoAuth['error'])) {
                     Yii::warning("Auto-Auth failed: " . $autoAuth['error'], 'didox_order');
                }
                
                $sysSettings = \app\models\Settings::find()
                    ->where(['type' => 'didox_eimzo_token'])
                    ->one();
                
                if ($sysSettings && !empty($sysSettings->content)) {
                    $userKey = $sysSettings->content;
                    Yii::info("Using System Didox Token (Stored) for order #{$order->id}.", 'didox_order');
                }
            }
        }

        // --- UPDATE SELLER INFO FROM DIDOX PROFILE IF TOKEN IS VALID ---
        if ($userKey) {
            $profileResponse = $didoxService->getUserProfile($userKey);
            if ($profileResponse['success'] && !empty($profileResponse['data'])) {
                $profileData = $profileResponse['data'];
                // Update TIN
                if (!empty($profileData['tin'])) {
                    $sellerInfo['tin'] = $profileData['tin'];
                }
                // Update Name
                if (!empty($profileData['name'])) {
                    $sellerInfo['name'] = $profileData['name'];
                }
                // Update Address if available
                if (!empty($profileData['address'])) {
                    $sellerInfo['address'] = $profileData['address'];
                }
                 Yii::info("Updated Seller Info from Didox Profile: TIN={$sellerInfo['tin']}, Name={$sellerInfo['name']}", 'didox_order');
            }
        }

        // --- VALIDATION OF TOKEN OWNER VS SELLER SETTINGS ---
        // If we are using Auto-Auth or System Token, we must ensure the token belongs to the configured Seller TIN.
        // Otherwise Didox rejects with "Owners do not match".
        // Ideally we should decode the token or check 'didox_eimzo_tax_id' setting.
        
        $tokenTaxId = null;
        $settingsTaxId = \app\models\Settings::find()->where(['type' => 'didox_eimzo_tax_id'])->one();
        if ($settingsTaxId) {
            $tokenTaxId = $settingsTaxId->content;
        }
        
        if ($tokenTaxId && $tokenTaxId != $sellerInfo['tin']) {
             // Mismatch!
             // Force override Seller Info with Token Owner Info to avoid "Owners do not match" error?
             // Or fail?
             // User asked: "make the arbitrary contract from the same as the seller setting file"
             // But if the PFX is different, it will fail.
             // Warning: We proceed, but this is the likely cause of "Owners do not match".
             Yii::warning("Didox Token Owner ({$tokenTaxId}) does not match Configured Seller ({$sellerInfo['tin']}). This may cause API errors.", 'didox_order');
        }

        // 4. Create Invoice Document
        if (!DidoxDocument::find()->where(['order_id' => $order->id, 'document_type' => DidoxDocument::DOCUMENT_TYPE_INVOICE])->exists()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                Log::log('didox_order', "Creating Invoice Document for Order #{$order->id}");
                $invoiceDoc = self::createInvoiceDocument($order, $sellerInfo, $buyerInfo);
                if ($invoiceDoc) {
                    // Attempt upload to DIDOX
                    try {
                        $apiData = $invoiceDoc->generateDidoxApiStructure();
                        // Ensure doctype is set
                        $apiData['doctype'] = $invoiceDoc->didox_doc_type ?: '002';
                        
                        // Log REQUEST
                        Log::log('didox_order', "[INVOICE REQUEST] Order #{$order->id}", [
                            'order_id' => $order->id,
                            'doctype' => $apiData['doctype'],
                            'request_data' => $apiData
                        ]);
                        
                        $uploadResult = $didoxService->createDocument($apiData, $userKey);
                        
                        // Log RESPONSE
                        Log::log('didox_order', "[INVOICE RESPONSE] Order #{$order->id}", [
                            'order_id' => $order->id,
                            'success' => $uploadResult['success'] ?? false,
                            'response_data' => $uploadResult
                        ], $uploadResult['success'] ? 'info' : 'error');
                        
                        if ($uploadResult['success']) {
                            $invoiceDoc->extractAndSetDidoxDocumentId($uploadResult['data']);
                            $invoiceDoc->didox_status = DidoxDocument::STATUS_DRAFT;
                            $invoiceDoc->didox_created_at = date('Y-m-d H:i:s');
                            $invoiceDoc->setDidoxData($uploadResult['data']);
                            $invoiceDoc->save(false);
                            
                            $transaction->commit();
                            $result['documents'][] = 'invoice';
                            $result['messages'][] = "Invoice uploaded to DIDOX successfully (ID: {$invoiceDoc->didox_id}).";
                            
                            // Auto-download PDF (silent - errors logged, doesn't break flow)
                            try {
                                $invoiceDoc->downloadPdfFromDidox(['uz', 'ru']);
                            } catch (\Exception $pdfEx) {
                                Log::log('didox_order', "PDF download failed for Invoice #{$invoiceDoc->id}", $pdfEx->getMessage(), 'warning');
                            }
                        } else {
                            // Helper to stringify error
                            $errorVal = $uploadResult['error'];
                            if (is_array($errorVal)) {
                                $errorMsg = json_encode($errorVal, JSON_UNESCAPED_UNICODE);
                            } else {
                                $errorMsg = (string)$errorVal;
                            }
                            if (empty($errorMsg)) $errorMsg = 'Unknown error (empty response)';

                            $transaction->rollBack();
                            $result['messages'][] = "Invoice creation failed at Didox: " . $errorMsg;
                            Yii::error("Didox Upload Failed (Invoice): " . $errorMsg, 'didox_order');
                            Log::log('didox_order', "Didox Upload Failed (Invoice) for Order #{$order->id}", $errorMsg, 'error');
                        }
                    } catch (\Exception $uploadEx) {
                        $transaction->rollBack();
                        Yii::error("DIDOX upload exception (Invoice): " . $uploadEx->getMessage(), 'didox_order');
                        $result['messages'][] = "Invoice creation exception: " . $uploadEx->getMessage();
                        Log::log('didox_order', "Invoice creation exception for Order #{$order->id}", $uploadEx->getMessage(), 'error');
                    }
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error("Didox invoice creation failed for Order #{$order->id}: " . $e->getMessage(), 'didox_order');
                $result['messages'][] = "Invoice Error: " . $e->getMessage();
                Log::log('didox_order', "Invoice Error for Order #{$order->id}", $e->getMessage(), 'error');
            }
        } else {
            $result['messages'][] = "Invoice already exists.";
        }

        // 5. Create Arbitrary Contract Document
        if (!DidoxDocument::find()->where(['order_id' => $order->id, 'document_type' => DidoxDocument::DOCUMENT_TYPE_ARBITRARY])->exists()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                Log::log('didox_order', "Creating Arbitrary Contract for Order #{$order->id}");
                $contractDoc = self::createContractDocument($order, $sellerInfo, $buyerInfo);
                if ($contractDoc) {
                    // Attempt upload to DIDOX
                    try {
                        $apiData = $contractDoc->generateDidoxApiStructure();
                        // Ensure doctype is set
                        $apiData['doctype'] = $contractDoc->didox_doc_type ?: '000';
                        
                        // Log REQUEST (exclude PDF content for readability, it's too large)
                        $requestLogData = $apiData;
                        if (isset($requestLogData['document']) && strlen($requestLogData['document']) > 200) {
                            $requestLogData['document'] = substr($requestLogData['document'], 0, 200) . '... [PDF BASE64 TRUNCATED]';
                        }
                        Log::log('didox_order', "[CONTRACT REQUEST] Order #{$order->id}", [
                            'order_id' => $order->id,
                            'doctype' => $apiData['doctype'],
                            'request_data' => $requestLogData
                        ]);
                        
                        $uploadResult = $didoxService->createDocument($apiData, $userKey);
                        
                        // Log RESPONSE
                        Log::log('didox_order', "[CONTRACT RESPONSE] Order #{$order->id}", [
                            'order_id' => $order->id,
                            'success' => $uploadResult['success'] ?? false,
                            'response_data' => $uploadResult
                        ], $uploadResult['success'] ? 'info' : 'error');
                        
                        if ($uploadResult['success']) {
                            $contractDoc->extractAndSetDidoxDocumentId($uploadResult['data']);
                            $contractDoc->didox_status = DidoxDocument::STATUS_DRAFT;
                            $contractDoc->didox_created_at = date('Y-m-d H:i:s');
                            $contractDoc->setDidoxData($uploadResult['data']);
                            $contractDoc->save(false);
                            
                            $transaction->commit();
                            $result['documents'][] = 'contract';
                            $result['messages'][] = "Contract uploaded to DIDOX successfully (ID: {$contractDoc->didox_id}).";
                            
                            // Auto-download PDF (silent - errors logged, doesn't break flow)
                            try {
                                $contractDoc->downloadPdfFromDidox(['uz', 'ru']);
                            } catch (\Exception $pdfEx) {
                                Log::log('didox_order', "PDF download failed for Contract #{$contractDoc->id}", $pdfEx->getMessage(), 'warning');
                            }
                        } else {
                            // Helper to stringify error
                            $errorVal = $uploadResult['error'];
                            if (is_array($errorVal)) {
                                $errorMsg = json_encode($errorVal, JSON_UNESCAPED_UNICODE);
                            } else {
                                $errorMsg = (string)$errorVal;
                            }
                            if (empty($errorMsg)) $errorMsg = 'Unknown error (empty response)';
                            
                            // Debugging: Log payload that caused error
                            $debugPayload = json_encode($apiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            Yii::error("Didox Contract Upload Failed. Payload: " . $debugPayload, 'didox_order');
                            
                            $transaction->rollBack();
                            $sellerDetails = "Seller: " . ($sellerInfo['name'] ?? 'N/A') . " (INN: " . ($sellerInfo['tin'] ?? 'N/A') . ")";
                            $buyerDetails = "Buyer: " . ($buyerInfo['name'] ?? 'N/A') . " (INN: " . ($buyerInfo['tin'] ?? 'N/A') . ")";
                            $result['messages'][] = "Contract creation failed at Didox: " . $errorMsg . ". " . $sellerDetails . " | " . $buyerDetails;
                            
                            Log::log('didox_order', "Didox Contract Upload Failed for Order #{$order->id}. {$sellerDetails} | {$buyerDetails}", [
                                'error' => $errorMsg,
                                'payload' => $apiData
                            ], 'error');
                        }
                    } catch (\Exception $uploadEx) {
                        $transaction->rollBack();
                        Yii::error("DIDOX upload exception (Contract): " . $uploadEx->getMessage(), 'didox_order');
                        $result['messages'][] = "Contract creation exception: " . $uploadEx->getMessage();
                        Log::log('didox_order', "Contract creation exception for Order #{$order->id}", $uploadEx->getMessage(), 'error');
                    }
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error("Didox contract creation failed for Order #{$order->id}: " . $e->getMessage(), 'didox_order');
                $result['messages'][] = "Contract Error: " . $e->getMessage();
                Log::log('didox_order', "Contract Error for Order #{$order->id}", $e->getMessage(), 'error');
            }
        } else {
            $result['messages'][] = "Contract already exists.";
        }

        if (empty($result['documents']) && empty($result['messages'])) {
             $result['messages'][] = "No documents created (already exist or skipped).";
        } elseif (!empty($result['documents'])) {
             $result['messages'][] = "Didox documents created: " . implode(', ', $result['documents']);
        }

        return $result;
    }

    /**
     * Create only Invoice Document for an order
     * 
     * @param Order $order
     * @return array Result status ['success' => bool, 'messages' => array]
     */
    public static function createInvoice(Order $order)
    {
        $result = [
            'success' => true,
            'messages' => [],
            'documents' => []
        ];

        Log::log('didox_order', "[AUTO INVOICE] Starting Invoice creation for Order #{$order->id}", [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'price' => $order->price
        ]);

        // Get common data
        try {
            $commonData = self::getCommonData($order);
            if (!$commonData['success']) {
                Log::log('didox_order', "[AUTO INVOICE] Failed to get common data for Order #{$order->id}", $commonData, 'error');
                return $commonData;
            }
        } catch (\Exception $e) {
            Log::log('didox_order', "[AUTO INVOICE] Exception getting common data for Order #{$order->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'error');
            $result['success'] = false;
            $result['messages'][] = "Failed to get common data: " . $e->getMessage();
            return $result;
        }

        $sellerInfo = $commonData['sellerInfo'];
        $buyerInfo = $commonData['buyerInfo'];
        $didoxService = $commonData['didoxService'];
        $userKey = $commonData['userKey'];

        Log::log('didox_order', "[AUTO INVOICE] Common data retrieved for Order #{$order->id}", [
            'sellerInfo' => $sellerInfo,
            'buyerInfo' => $buyerInfo,
            'hasToken' => !empty($userKey)
        ]);

        // Check if invoice already exists
        if (DidoxDocument::find()->where(['order_id' => $order->id, 'document_type' => DidoxDocument::DOCUMENT_TYPE_INVOICE])->exists()) {
            $result['messages'][] = "Invoice already exists for this order.";
            Log::log('didox_order', "[AUTO INVOICE] Invoice already exists for Order #{$order->id}");
            return $result;
        }

        // Create Invoice Document
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Log::log('didox_order', "[AUTO INVOICE] Creating Invoice Document for Order #{$order->id}");
            
            $invoiceDoc = self::createInvoiceDocument($order, $sellerInfo, $buyerInfo);
            
            if (!$invoiceDoc) {
                $transaction->rollBack();
                $result['success'] = false;
                $result['messages'][] = "Failed to create invoice document object.";
                Log::log('didox_order', "[AUTO INVOICE] createInvoiceDocument returned null for Order #{$order->id}", null, 'error');
                return $result;
            }

            Log::log('didox_order', "[AUTO INVOICE] Invoice document created locally for Order #{$order->id}", [
                'invoice_id' => $invoiceDoc->id,
                'invoice_name' => $invoiceDoc->name ?? 'N/A'
            ]);

            try {
                // Refresh document to load relations
                $invoiceDoc->refresh();
                
                $apiData = $invoiceDoc->generateDidoxApiStructure();
                $apiData['doctype'] = $invoiceDoc->didox_doc_type ?: '002';
                
                // Log full API structure including products for debugging
                $productsCount = isset($apiData['ProductList']['Products']) ? count($apiData['ProductList']['Products']) : 0;
                
                Log::log('didox_order', "[AUTO INVOICE REQUEST] Order #{$order->id}", [
                    'order_id' => $order->id,
                    'doctype' => $apiData['doctype'],
                    'products_count' => $productsCount,
                    'seller_tin' => $apiData['SellerTin'] ?? 'N/A',
                    'buyer_tin' => $apiData['BuyerTin'] ?? 'N/A',
                    'seller_bank_id' => $apiData['Seller']['BankId'] ?? 'N/A',
                    'seller_account' => $apiData['Seller']['Account'] ?? 'N/A',
                    'products_sample' => isset($apiData['ProductList']['Products'][0]) ? $apiData['ProductList']['Products'][0] : 'NO PRODUCTS',
                    'full_request' => $apiData
                ]);
                
                $uploadResult = $didoxService->createDocument($apiData, $userKey);
                
                Log::log('didox_order', "[AUTO INVOICE RESPONSE] Order #{$order->id}", [
                    'order_id' => $order->id,
                    'success' => $uploadResult['success'] ?? false,
                    'response_data' => $uploadResult
                ], ($uploadResult['success'] ?? false) ? 'info' : 'error');
                
                if ($uploadResult['success'] ?? false) {
                    $invoiceDoc->extractAndSetDidoxDocumentId($uploadResult['data']);
                    $invoiceDoc->didox_status = DidoxDocument::STATUS_DRAFT;
                    $invoiceDoc->didox_created_at = date('Y-m-d H:i:s');
                    $invoiceDoc->setDidoxData($uploadResult['data']);
                    $invoiceDoc->save(false);
                    
                    $transaction->commit();
                    $result['documents'][] = 'invoice';
                    $result['messages'][] = "Invoice uploaded to DIDOX successfully (ID: {$invoiceDoc->didox_id}).";
                    Log::log('didox_order', "[AUTO INVOICE] Success for Order #{$order->id}", [
                        'didox_id' => $invoiceDoc->didox_id
                    ]);
                } else {
                    $errorMsg = self::formatError($uploadResult['error'] ?? 'Unknown error');
                    $transaction->rollBack();
                    $result['success'] = false;
                    $result['messages'][] = "Invoice creation failed at Didox: " . $errorMsg;
                    Log::log('didox_order', "[AUTO INVOICE] Didox Upload Failed for Order #{$order->id}", [
                        'error' => $errorMsg,
                        'full_response' => $uploadResult
                    ], 'error');
                }
            } catch (\Exception $uploadEx) {
                $transaction->rollBack();
                $result['success'] = false;
                $result['messages'][] = "Invoice creation exception: " . $uploadEx->getMessage();
                Log::log('didox_order', "[AUTO INVOICE] Upload exception for Order #{$order->id}", [
                    'error' => $uploadEx->getMessage(),
                    'file' => $uploadEx->getFile(),
                    'line' => $uploadEx->getLine(),
                    'trace' => $uploadEx->getTraceAsString()
                ], 'error');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            $result['success'] = false;
            $result['messages'][] = "Invoice Error: " . $e->getMessage();
            Log::log('didox_order', "[AUTO INVOICE] Error for Order #{$order->id}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 'error');
        }

        return $result;
    }

    /**
     * Create only Arbitrary Contract Document for an order
     * 
     * @param Order $order
     * @return array Result status ['success' => bool, 'messages' => array]
     */
    public static function createArbitrary(Order $order)
    {
        $result = [
            'success' => true,
            'messages' => [],
            'documents' => []
        ];

        Log::log('didox_order', "Starting Arbitrary Contract creation for Order #{$order->id}");

        // Get common data
        $commonData = self::getCommonData($order);
        if (!$commonData['success']) {
            return $commonData;
        }

        $sellerInfo = $commonData['sellerInfo'];
        $buyerInfo = $commonData['buyerInfo'];
        $didoxService = $commonData['didoxService'];
        $userKey = $commonData['userKey'];

        // Check if contract already exists
        if (DidoxDocument::find()->where(['order_id' => $order->id, 'document_type' => DidoxDocument::DOCUMENT_TYPE_ARBITRARY])->exists()) {
            $result['messages'][] = "Arbitrary contract already exists for this order.";
            return $result;
        }

        // Create Arbitrary Contract Document
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Log::log('didox_order', "Creating Arbitrary Contract for Order #{$order->id}");
            $contractDoc = self::createContractDocument($order, $sellerInfo, $buyerInfo);
            if ($contractDoc) {
                try {
                    $apiData = $contractDoc->generateDidoxApiStructure();
                    $apiData['doctype'] = $contractDoc->didox_doc_type ?: '000';
                    
                    // Log REQUEST (exclude PDF content for readability)
                    $requestLogData = $apiData;
                    if (isset($requestLogData['document']) && strlen($requestLogData['document']) > 200) {
                        $requestLogData['document'] = substr($requestLogData['document'], 0, 200) . '... [PDF BASE64 TRUNCATED]';
                    }
                    Log::log('didox_order', "[CONTRACT REQUEST] Order #{$order->id}", [
                        'order_id' => $order->id,
                        'doctype' => $apiData['doctype'],
                        'request_data' => $requestLogData
                    ]);
                    
                    $uploadResult = $didoxService->createDocument($apiData, $userKey);
                    
                    Log::log('didox_order', "[CONTRACT RESPONSE] Order #{$order->id}", [
                        'order_id' => $order->id,
                        'success' => $uploadResult['success'] ?? false,
                        'response_data' => $uploadResult
                    ], $uploadResult['success'] ? 'info' : 'error');
                    
                    if ($uploadResult['success']) {
                        $contractDoc->extractAndSetDidoxDocumentId($uploadResult['data']);
                        $contractDoc->didox_status = DidoxDocument::STATUS_DRAFT;
                        $contractDoc->didox_created_at = date('Y-m-d H:i:s');
                        $contractDoc->setDidoxData($uploadResult['data']);
                        $contractDoc->save(false);
                        
                        $transaction->commit();
                        $result['documents'][] = 'contract';
                        $result['messages'][] = "Contract uploaded to DIDOX successfully (ID: {$contractDoc->didox_id}).";
                    } else {
                        $errorMsg = self::formatError($uploadResult['error']);
                        $transaction->rollBack();
                        $result['success'] = false;
                        $sellerDetails = "Seller: " . ($sellerInfo['name'] ?? 'N/A') . " (INN: " . ($sellerInfo['tin'] ?? 'N/A') . ")";
                        $buyerDetails = "Buyer: " . ($buyerInfo['name'] ?? 'N/A') . " (INN: " . ($buyerInfo['tin'] ?? 'N/A') . ")";
                        $result['messages'][] = "Contract creation failed at Didox: " . $errorMsg . ". " . $sellerDetails . " | " . $buyerDetails;
                        Log::log('didox_order', "Didox Contract Upload Failed for Order #{$order->id}. {$sellerDetails} | {$buyerDetails}", [
                            'error' => $errorMsg,
                            'payload' => $apiData
                        ], 'error');
                    }
                } catch (\Exception $uploadEx) {
                    $transaction->rollBack();
                    $result['success'] = false;
                    $result['messages'][] = "Contract creation exception: " . $uploadEx->getMessage();
                    Log::log('didox_order', "Contract creation exception for Order #{$order->id}", $uploadEx->getMessage(), 'error');
                }
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            $result['success'] = false;
            $result['messages'][] = "Contract Error: " . $e->getMessage();
            Log::log('didox_order', "Contract Error for Order #{$order->id}", $e->getMessage(), 'error');
        }

        return $result;
    }

    /**
     * Get common data needed for document creation (seller info, buyer info, didox service, token)
     * 
     * @param Order $order
     * @return array
     */
    protected static function getCommonData(Order $order)
    {
        $result = [
            'success' => true,
            'messages' => [],
            'sellerInfo' => [],
            'buyerInfo' => [],
            'didoxService' => null,
            'userKey' => ''
        ];

        // Get Seller Info from Settings
        $settings = \app\models\Settings::find()
            ->where(['type' => [
                'didox_seller_inn', 
                'didox_seller_name', 
                'didox_seller_address', 
                'didox_seller_account', 
                'didox_seller_mfo',
                'didox_seller_vat_reg_code'
            ]])
            ->all();
        $settingsMap = \yii\helpers\ArrayHelper::map($settings, 'type', 'content');

        $sellerInfo = [
            'tin' => $settingsMap['didox_seller_inn'] ?? '',
            'name' => $settingsMap['didox_seller_name'] ?? '',
            'address' => $settingsMap['didox_seller_address'] ?? '',
            'account' => $settingsMap['didox_seller_account'] ?? '',
            'bank_id' => $settingsMap['didox_seller_mfo'] ?? '',
            'director' => '',
            'accountant' => '',
            'vat_reg_code' => $settingsMap['didox_seller_vat_reg_code'] ?? '300000000001',
        ];

        if (empty($sellerInfo['name']) && $order->shop) {
            $sellerInfo['name'] = $order->shop->name_ru;
        }

        if (empty($sellerInfo['tin'])) {
            $result['success'] = false;
            $result['messages'][] = "Global Seller Settings has no TIN. Skipped.";
            Log::log('didox_order', "Order #{$order->id}: Global Seller Settings have no TIN (didox_seller_inn). Skipping.", $sellerInfo, 'warning');
            return $result;
        }

        // Get Buyer Info
        $buyer = $order->user;
        $buyerInfo = self::getBuyerInfo($order, $buyer);
        Log::log('didox_order', "Buyer Info for Order #{$order->id}", $buyerInfo);

        // Didox Service and Token
        $didoxService = new \app\services\DidoxService();
        $userKey = Yii::$app->session->get('didox_token', '');

        if (empty($userKey)) {
            $autoAuth = $didoxService->getAuthTokenFromPfx();
            if ($autoAuth['success']) {
                $userKey = $autoAuth['token'];
            } else {
                $sysSettings = \app\models\Settings::find()
                    ->where(['type' => 'didox_eimzo_token'])
                    ->one();
                if ($sysSettings && !empty($sysSettings->content)) {
                    $userKey = $sysSettings->content;
                }
            }
        }

        // Update seller info from Didox profile if token is valid
        if ($userKey) {
            $profileResponse = $didoxService->getUserProfile($userKey);
            if ($profileResponse['success'] && !empty($profileResponse['data'])) {
                $profileData = $profileResponse['data'];
                if (!empty($profileData['tin'])) {
                    $sellerInfo['tin'] = $profileData['tin'];
                }
                if (!empty($profileData['name'])) {
                    $sellerInfo['name'] = $profileData['name'];
                }
                if (!empty($profileData['address'])) {
                    $sellerInfo['address'] = $profileData['address'];
                }
            }
        }

        $result['sellerInfo'] = $sellerInfo;
        $result['buyerInfo'] = $buyerInfo;
        $result['didoxService'] = $didoxService;
        $result['userKey'] = $userKey;

        return $result;
    }

    /**
     * Format error message from API response
     * 
     * @param mixed $errorVal
     * @return string
     */
    protected static function formatError($errorVal)
    {
        if (is_array($errorVal)) {
            return json_encode($errorVal, JSON_UNESCAPED_UNICODE);
        }
        $errorMsg = (string)$errorVal;
        return empty($errorMsg) ? 'Unknown error (empty response)' : $errorMsg;
    }

    /**
     * Get buyer information from Order and User
     * 
     * @param Order $order
     * @param User|null $user
     * @return array
     */
    protected static function getBuyerInfo(Order $order, $user = null)
    {
        $info = [
            'tin' => '',
            'name' => '',
            'address' => '',
            'account' => '',
            'bank_id' => '',
            'vat_reg_code' => '', // Add this
        ];

        if ($user) {
            // First try to use Order's stored fields
            if (!empty($order->inn)) {
                $info['tin'] = $order->inn;
            } else {
                $info['tin'] = $user->eimzo_tax_id ?: ($user->inn ?: '');
            }

            if (!empty($order->account)) {
                $info['account'] = $order->account;
            } else {
                $info['account'] = $user->account ?? '';
            }

            if (!empty($order->bank_id)) {
                $info['bank_id'] = $order->bank_id;
            } else {
                $info['bank_id'] = $user->mfo ?? '';
            }
            
            $fullName = array_filter([$user->name, $user->lastname]);
            $info['name'] = implode(' ', $fullName) ?: ($user->organization_name ?? 'Client');
            
            // Address logic similar to DidoxController
            $address = '';
            if ($user->addresses) {
                $defaultAddress = $user->addresses[0] ?? null;
                if ($defaultAddress) {
                    $address = $defaultAddress->address;
                }
            }
            if (empty($address)) {
                $address = $user->last_address ?: $order->address ?: 'Unknown';
            }
            $info['address'] = $address;
        } else {
            // Guest order
            $info['tin'] = $order->inn ?? '';
            $info['account'] = $order->account ?? '';
            $info['bank_id'] = $order->bank_id ?? '';

            if (empty($info['tin'])) {
                 $info['tin'] = '123456789'; // Only use default if no TIN available at all and guest
            }
            
            $info['name'] = trim(($order->name ?? '') . ' ' . ($order->lastname ?? '')) ?: 'Client';
            $info['address'] = $order->address ?: 'Unknown';
        }
        
        // Provide a default for buyer VAT reg code if not known, often required by validation even if 0
        if (empty($info['vat_reg_code'])) {
            $info['vat_reg_code'] = '0'; // Default to '0' if unknown to pass validation
        }

        return $info;
    }

    /**
     * Create Invoice Document
     */
    protected static function createInvoiceDocument(Order $order, $sellerInfo, $buyerInfo)
    {
        Log::log('didox_order', "[CREATE INVOICE DOC] Starting for Order #{$order->id}", [
            'sellerInfo' => $sellerInfo,
            'buyerInfo' => $buyerInfo
        ]);

        try {
            $doc = new DidoxDocument();
            $doc->name = "Счет-фактура по заказу #{$order->id}";
            $doc->document_type = DidoxDocument::DOCUMENT_TYPE_INVOICE;
            $doc->didox_doc_type = '002'; // Invoice
            $doc->order_id = $order->id;
            $doc->to_user_id = $order->user_id; // Assign to buyer
            $doc->status = DidoxDocument::LOCAL_STATUS_ACTIVE;
            $doc->didox_status = DidoxDocument::STATUS_DRAFT; // Start as local draft
            
            if (!$doc->save()) {
                Log::log('didox_order', "[CREATE INVOICE DOC] Failed to save DidoxDocument", [
                    'errors' => $doc->errors
                ], 'error');
                throw new \Exception("Failed to save DidoxDocument (Invoice): " . json_encode($doc->errors));
            }
            
            Log::log('didox_order', "[CREATE INVOICE DOC] DidoxDocument saved, id={$doc->id}");

            $invoice = new DidoxDocumentInvoice();
            $invoice->document_id = $doc->id;
            $invoice->invoice_number = "INV-{$order->id}-" . time(); // Unique number to avoid duplication errors during testing
            $invoice->invoice_date = date('Y-m-d');
            $invoice->contract_number = "CNT-{$order->id}";
            $invoice->contract_date = date('Y-m-d', strtotime($order->date ?: 'now'));
            
            // Seller
            $invoice->seller_tin = $sellerInfo['tin'] ?? '';
            $invoice->seller_name = $sellerInfo['name'] ?? '';
            $invoice->seller_address = $sellerInfo['address'] ?? '';
            $invoice->seller_account = $sellerInfo['account'] ?? '';
            $invoice->seller_bank_id = $sellerInfo['bank_id'] ?? '';
            $invoice->seller_vat_reg_code = $sellerInfo['vat_reg_code'] ?? '';
            
            // Buyer
            $invoice->buyer_tin = $buyerInfo['tin'] ?? '';
            $invoice->buyer_name = $buyerInfo['name'] ?? '';
            $invoice->buyer_address = $buyerInfo['address'] ?? '';
            $invoice->buyer_account = $buyerInfo['account'] ?? '';
            $invoice->buyer_bank_id = $buyerInfo['bank_id'] ?? '';
            $invoice->buyer_vat_reg_code = $buyerInfo['vat_reg_code'] ?? '';

            // Pre-calculate totals (fixes total_sum cannot be blank error)
            $tempTotal = 0;
            $orderProducts = $order->orderProducts;
            if ($orderProducts) {
                foreach ($orderProducts as $op) {
                    if ($op && isset($op->price)) {
                        $tempTotal += $op->price;
                    }
                }
            }
            $invoice->total_sum = $tempTotal > 0 ? $tempTotal : 1; // Ensure > 0
            
            Log::log('didox_order', "[CREATE INVOICE DOC] Invoice data prepared", [
                'document_id' => $doc->id,
                'invoice_number' => $invoice->invoice_number,
                'total_sum' => $invoice->total_sum,
                'products_count' => count($orderProducts ?: [])
            ]);
            
            if (!$invoice->save()) {
                Log::log('didox_order', "[CREATE INVOICE DOC] Failed to save DidoxDocumentInvoice", [
                    'errors' => $invoice->errors
                ], 'error');
                throw new \Exception("Failed to save DidoxDocumentInvoice: " . json_encode($invoice->errors));
            }
            
            Log::log('didox_order', "[CREATE INVOICE DOC] Invoice saved, id={$invoice->id}");

            // Products
            self::createIncludedProducts($doc, $order);
            
            Log::log('didox_order', "[CREATE INVOICE DOC] Products created, recalculating totals");
            
            // Check if we have any products
            $doc->refresh();
            $productsCount = $doc->getIncludedProducts()->count();
            Log::log('didox_order', "[CREATE INVOICE DOC] Products count: {$productsCount}");
            
            if ($productsCount == 0) {
                Log::log('didox_order', "[CREATE INVOICE DOC] WARNING: No products created for invoice!", null, 'warning');
            }
            
            // Recalculate totals - refresh invoice from DB first
            $invoice->refresh();
            $invoice->calculateTotals();
            $invoice->save(false);
            
            Log::log('didox_order', "[CREATE INVOICE DOC] Completed for Order #{$order->id}", [
                'doc_id' => $doc->id,
                'invoice_id' => $invoice->id,
                'products_count' => $productsCount,
                'total_sum' => $invoice->total_sum
            ]);

            return $doc;
            
        } catch (\Exception $e) {
            Log::log('didox_order', "[CREATE INVOICE DOC] Exception for Order #{$order->id}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Create Arbitrary Contract Document
     */
    protected static function createContractDocument(Order $order, $sellerInfo, $buyerInfo)
    {
        $doc = new DidoxDocument();
        $doc->name = "Договор по заказу #{$order->id}";
        $doc->document_type = DidoxDocument::DOCUMENT_TYPE_ARBITRARY;
        $doc->didox_doc_type = '000'; // Arbitrary
        $doc->order_id = $order->id;
        $doc->to_user_id = $order->user_id; // Assign to buyer
        $doc->status = DidoxDocument::LOCAL_STATUS_ACTIVE;
        $doc->didox_status = DidoxDocument::STATUS_DRAFT;

        if (!$doc->save()) {
            throw new \Exception("Failed to save DidoxDocument (Contract): " . json_encode($doc->errors));
        }

        $contract = new DidoxDocumentArbitrary();
        $contract->document_id = $doc->id;
        
        // For testing/arbitrary, if "buyer" is not a real organization, Didox might reject mismatch owners.
        // If contract is "arbitrary", Didox allows 2-way with any Tin if properly set?
        // "Owners do not match" usually means the user creating the doc (token owner) 
        // does not match the 'Seller' or 'Owner' field in the JSON payload.
        // We are using 'sellerInfo' (from settings) as the creator.
        
        // If the user wants to set the arbitrary contract to use "same as seller" for both sides? No, that makes no sense.
        // But "Owners do not match" means the token belongs to TIN X, but the document JSON says "SellerTin": Y.
        // Ensure sellerInfo['tin'] MATCHES the TIN of the PFX/Token used.
        
        // Populate arbitrary doc
        // Check if buyer TIN is present, if not use default
        $buyerTin = $buyerInfo['tin'];
        if (empty($buyerTin) || strlen($buyerTin) < 9) {
            $buyerTin = '123456789';
        }

        $contract->populateFromOrder($order, [
            'tin' => $sellerInfo['tin'],
            'name' => $sellerInfo['name'],
            'address' => $sellerInfo['address'],
            'buyer_tin' => $buyerTin,
            'buyer_name' => $buyerInfo['name'],
            'buyer_address' => $buyerInfo['address']
            // 'branch_code' => ... if available
        ]);
        
        // FOR ARBITRARY: The 'Owner' is the creator.
        // In DidoxDocumentArbitrary::generateDidoxJson(), it maps 'Owner' to these fields.
        // Let's ensure the 'Owner' tin matches the Seller TIN.
        
        // Additionally, for "Invalid document json", we might be missing fields.
        
        if (!$contract->save()) {
            throw new \Exception("Failed to save DidoxDocumentArbitrary: " . json_encode($contract->errors));
        }

        // Generate PDF
        try {
            $contract->generateContractPdf($order);
            $contract->save(false);
        } catch (\Exception $e) {
            Yii::error("Failed to generate contract PDF for order #{$order->id}: " . $e->getMessage(), 'didox_order');
            // Continue without failing the whole process, PDF can be regenerated later
        }

        return $doc;
    }

    /**
     * Create Included Products from Order Products
     */
    protected static function createIncludedProducts(DidoxDocument $doc, Order $order)
    {
        $orderProducts = $order->orderProducts;
        
        Log::log('didox_order', "[PRODUCTS] Creating included products for doc #{$doc->id}", [
            'order_id' => $order->id,
            'products_count' => count($orderProducts)
        ]);
        
        foreach ($orderProducts as $index => $op) {
            if (!$op) {
                Log::log('didox_order', "[PRODUCTS] Skipping null order product at index {$index}", null, 'warning');
                continue;
            }
            
            $product = $op->product;
            if (!$product) {
                Log::log('didox_order', "[PRODUCTS] Skipping order product #{$op->id} - product is null", [
                    'order_product_id' => $op->id,
                    'product_id' => $op->product_id ?? 'N/A'
                ], 'warning');
                continue;
            }

            try {
                $included = new DidoxDocumentIncludedProducts();
                $included->document_id = $doc->id;
                $included->ord_no = $index + 1;
                $included->name = $product->name_ru ?: $product->name_uz ?: 'Product';
                
                // Map IKPU - use product's IKPU code or default
                // Default IKPU: 06912001001000000 - Прочие готовые пищевые продукты (or use your preferred default)
                $ikpuCode = '';
                $ikpuName = '';
                
                if (!empty($product->ikpu_code)) {
                    $ikpuCode = $product->ikpu_code;
                    $ikpuName = $product->ikpu_name ?: ($product->ikpu ? $product->ikpu->name_ru : '');
                }
                
                // If still empty, use a default IKPU code for "Other goods"
                if (empty($ikpuCode)) {
                    $ikpuCode = '10309001003000000'; // Default: Прочие товары
                    $ikpuName = 'Прочие товары';
                    Log::log('didox_order', "[PRODUCTS] Using default IKPU for product #{$product->id}", [
                        'product_name' => $product->name_ru
                    ], 'warning');
                }
                
                $included->catalog_code = $ikpuCode;
                $included->catalog_name = $ikpuName;
                
                // Map Package - use defaults if not set
                $included->package_code = isset($product->package_code) && !empty($product->package_code) 
                    ? $product->package_code 
                    : '1516231'; // Default package code for "шт" (pieces)
                $included->package_name = isset($product->package_name) && !empty($product->package_name) 
                    ? $product->package_name 
                    : 'шт';
                
                $included->count = (float)($op->amount ?: 1);
                
                // Safe division - ensure amount is not zero
                $amount = $op->amount ?: 1;
                $unitPrice = $op->price / $amount;
                $included->summa = $op->price ?: 0; // Total price
                
                // VAT Logic (assuming 12% standard if not specified)
                $included->vat_rate = 12; // Default
                // Calculate VAT sum from total
                $included->vat_sum = $included->summa * $included->vat_rate / (100 + $included->vat_rate);
                
                $included->origin = DidoxDocumentInvoice::PRODUCT_ORIGIN_DOMESTIC; // Default
                
                Log::log('didox_order', "[PRODUCTS] Creating product #{$index}", [
                    'document_id' => $doc->id,
                    'product_id' => $product->id,
                    'name' => $included->name,
                    'catalog_code' => $included->catalog_code,
                    'count' => $included->count,
                    'summa' => $included->summa
                ]);
                
                if (!$included->save()) {
                    Log::log('didox_order', "[PRODUCTS] Failed to save IncludedProduct", [
                        'document_id' => $doc->id,
                        'product_id' => $product->id,
                        'errors' => $included->errors
                    ], 'error');
                    Yii::warning("Failed to save IncludedProduct for Didox: " . json_encode($included->errors), 'didox_order');
                }
            } catch (\Exception $e) {
                Log::log('didox_order', "[PRODUCTS] Exception creating included product", [
                    'document_id' => $doc->id,
                    'product_id' => $product->id ?? 'N/A',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 'error');
            }
        }
    }
}
