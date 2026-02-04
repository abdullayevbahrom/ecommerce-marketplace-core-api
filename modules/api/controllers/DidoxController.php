<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\web\Response;
use yii\data\ActiveDataProvider;

use app\models\didox\DidoxDocument;
use app\models\user\User;

/**
 * DIDOX API Controller for frontend
 * Handles document retrieval for assigned users
 */
class DidoxController extends Controller
{
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Use HttpBearerAuth - requires 'Authorization: Bearer {token}' header
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['options', 'test']
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
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
     * Handle CORS preflight requests
     */
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [];
    }

    /**
     * Test endpoint to verify CORS is working
     * 
     * @return array
     */
    public function actionTest()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return [
            'success' => true,
            'message' => 'CORS is working! DidoxController is accessible.',
            'timestamp' => date('Y-m-d H:i:s'),
            'request_method' => Yii::$app->request->method,
            'request_headers' => [
                'origin' => Yii::$app->request->headers->get('origin'),
                'user-agent' => Yii::$app->request->headers->get('user-agent'),
                'authorization' => Yii::$app->request->headers->get('authorization') ? 'Bearer token present' : 'No authorization header'
            ]
        ];
    }

    /**
     * Get all DIDOX documents assigned to the logged-in user or related to their orders
     * 
     * @return array
     */
    public function actionMyDocuments()
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        try {
            $query = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->with(['createdBy', 'toUser'])
                ->where(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->orderBy(['d.created_at' => SORT_DESC]);

            // Optional filtering
            $status = Yii::$app->request->get('status');
            if ($status !== null) {
                $query->andWhere(['d.didox_status' => $status]);
            }

            $documentType = Yii::$app->request->get('document_type');
            if ($documentType) {
                $query->andWhere(['d.document_type' => $documentType]);
            }
            
            $orderId = Yii::$app->request->get('order_id');
            if ($orderId) {
                $query->andWhere(['d.order_id' => $orderId]);
            }

            // Pagination
            $page = (int)Yii::$app->request->get('page', 1);
            $limit = (int)Yii::$app->request->get('limit', 20);
            $offset = ($page - 1) * $limit;

            $total = $query->count();
            $documents = $query->limit($limit)->offset($offset)->all();

            // Build response with PDF URLs for each document
            $baseUrl = Yii::$app->request->hostInfo;
            $documentsWithPdf = [];
            
            foreach ($documents as $doc) {
                $docData = $doc->toArray();
                
                // Add PDF URLs if document has didox_id
                if ($doc->didox_id) {
                    $docData['pdf_urls'] = [
                        'uz' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $doc->didox_id . '&lang=uz',
                        'ru' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $doc->didox_id . '&lang=ru',
                    ];
                    $docData['pdf_cached'] = [
                        'uz' => $doc->hasPdf('uz'),
                        'ru' => $doc->hasPdf('ru'),
                    ];
                } else {
                    $docData['pdf_urls'] = null;
                    $docData['pdf_cached'] = null;
                }
                
                $documentsWithPdf[] = $docData;
            }

            return [
                'data' => $documentsWithPdf,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Get user documents error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to retrieve documents');
        }
    }

    /**
     * Get a specific document by didox_id (assigned to current user or related to their order)
     * 
     * @param string $didox_id
     * @return DidoxDocument
     * @throws HttpException
     */
    public function actionGetDocument($didox_id)
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        if (empty($didox_id)) {
            throw new HttpException(400, 'didox_id parameter is required');
        }

        try {
            $document = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->with(['createdBy', 'toUser', 'invoice', 'arbitrary', 'includedProducts'])
                ->where(['d.didox_id' => $didox_id])
                ->andWhere(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->one();

            if (!$document) {
                throw new HttpException(404, 'Document not found or not accessible');
            }

            // Build response with PDF URLs
            $baseUrl = Yii::$app->request->hostInfo;
            $pdfUrls = null;
            
            if ($document->didox_id) {
                $pdfUrls = [
                    'uz' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=uz',
                    'ru' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=ru',
                    'en' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=en',
                ];
            }

            return [
                'data' => $document,
                'pdf_urls' => $pdfUrls
            ];

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Get document error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to retrieve document');
        }
    }

    /**
     * Get a specific document by ID (assigned to current user or related to their order)
     * 
     * @param int $id
     * @return DidoxDocument
     * @throws HttpException
     */
    public function actionGetDocumentById($id)
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        if (empty($id)) {
            throw new HttpException(400, 'id parameter is required');
        }

        try {
            $document = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->with(['createdBy', 'toUser', 'invoice', 'arbitrary', 'includedProducts'])
                ->where(['d.id' => $id])
                ->andWhere(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->one();

            if (!$document) {
                throw new HttpException(404, 'Document not found or not accessible');
            }

            // Build response with PDF URLs
            $baseUrl = Yii::$app->request->hostInfo;
            $pdfUrls = null;
            
            if ($document->didox_id) {
                $pdfUrls = [
                    'uz' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=uz',
                    'ru' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=ru',
                    'en' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=en',
                ];
            }

            return [
                'data' => $document,
                'pdf_urls' => $pdfUrls
            ];

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Get document by ID error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to retrieve document');
        }
    }

    /**
     * Get document PDF (locally saved or fetched from Didox)
     * 
     * @param string $didox_id - Didox document ID
     * @param string $lang - Language (uz, ru, en), default 'uz'
     * @return mixed - PDF file or JSON response
     * @throws HttpException
     */
    public function actionGetDocumentPdf($didox_id, $lang = 'uz')
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        if (empty($didox_id)) {
            throw new HttpException(400, 'didox_id parameter is required');
        }

        // Validate language
        $lang = in_array($lang, ['uz', 'ru', 'en']) ? $lang : 'uz';

        try {
            // Find document and verify access
            $document = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->where(['d.didox_id' => $didox_id])
                ->andWhere(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->one();

            if (!$document) {
                throw new HttpException(404, 'Document not found or not accessible');
            }

            // Check if PDF exists locally
            if ($document->hasPdf($lang)) {
                $fullPath = $document->getPdfFullPath($lang);
                
                // Return PDF file
                $response = Yii::$app->response;
                $response->format = Response::FORMAT_RAW;
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'inline; filename="' . $document->didox_id . '_' . $lang . '.pdf"');
                $response->headers->set('Cache-Control', 'public, max-age=3600');
                $response->data = file_get_contents($fullPath);
                
                return $response;
            }

            // PDF not found locally - try to fetch from Didox and save
            $didoxService = new \app\services\DidoxService();
            
            // Get user-key from DB setting
            $userKey = '';
            $sysSettings = \app\models\Settings::find()
                ->where(['type' => 'didox_eimzo_token'])
                ->one();
            
            if ($sysSettings && !empty($sysSettings->content)) {
                $userKey = $sysSettings->content;
            }

            $result = $didoxService->getDocumentPdf($document->didox_id, $userKey, $lang);

            if ($result['success']) {
                // Save PDF locally for future requests
                $document->savePdfLocally($result['data'], $lang);

                // Return PDF file
                $response = Yii::$app->response;
                $response->format = Response::FORMAT_RAW;
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'inline; filename="' . $document->didox_id . '_' . $lang . '.pdf"');
                $response->headers->set('Cache-Control', 'public, max-age=3600');
                $response->data = $result['data'];
                
                return $response;
            } else {
                throw new HttpException(502, 'Failed to fetch PDF from Didox: ' . (isset($result['error']) ? $result['error'] : 'Unknown error'));
            }

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Get document PDF error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to retrieve document PDF');
        }
    }

    /**
     * Get document PDF info (URLs for available PDFs)
     * 
     * @param string $didox_id - Didox document ID
     * @return array - PDF URLs and availability
     * @throws HttpException
     */
    public function actionGetDocumentPdfInfo($didox_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        if (empty($didox_id)) {
            throw new HttpException(400, 'didox_id parameter is required');
        }

        try {
            // Find document and verify access
            $document = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->where(['d.didox_id' => $didox_id])
                ->andWhere(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->one();

            if (!$document) {
                throw new HttpException(404, 'Document not found or not accessible');
            }

            $baseUrl = Yii::$app->request->hostInfo;
            
            return [
                'data' => [
                    'didox_id' => $document->didox_id,
                    'document_id' => $document->id,
                    'document_name' => $document->name,
                    'pdf' => [
                        'uz' => [
                            'available' => $document->hasPdf('uz'),
                            'cached' => $document->hasPdf('uz'),
                            'url' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=uz'
                        ],
                        'ru' => [
                            'available' => $document->hasPdf('ru'),
                            'cached' => $document->hasPdf('ru'),
                            'url' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=ru'
                        ],
                        'en' => [
                            'available' => $document->hasPdf('en'),
                            'cached' => $document->hasPdf('en'),
                            'url' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $document->didox_id . '&lang=en'
                        ]
                    ]
                ]
            ];

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Get document PDF info error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to retrieve document PDF info');
        }
    }

    /**
     * Get document statistics for the current user
     * 
     * @return array
     */
    public function actionStats()
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        try {
            $baseQuery = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->where(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ]);

            $stats = [
                'total' => (clone $baseQuery)->count(),
                'by_status' => [
                    'draft' => (clone $baseQuery)->andWhere(['d.didox_status' => DidoxDocument::STATUS_DRAFT])->count(),
                    'waiting_signature' => (clone $baseQuery)->andWhere(['d.didox_status' => [
                        DidoxDocument::STATUS_WAITING_PARTNER_SIGNATURE,
                        DidoxDocument::STATUS_WAITING_YOUR_SIGNATURE
                    ]])->count(),
                    'signed' => (clone $baseQuery)->andWhere(['d.didox_status' => DidoxDocument::STATUS_SIGNED])->count(),
                    'rejected' => (clone $baseQuery)->andWhere(['d.didox_status' => DidoxDocument::STATUS_REJECTED])->count(),
                    'canceled' => (clone $baseQuery)->andWhere(['d.didox_status' => DidoxDocument::STATUS_CANCELED])->count(),
                ],
                'by_type' => [
                    'invoice' => (clone $baseQuery)->andWhere(['d.document_type' => DidoxDocument::DOCUMENT_TYPE_INVOICE])->count(),
                    'arbitrary' => (clone $baseQuery)->andWhere(['d.document_type' => DidoxDocument::DOCUMENT_TYPE_ARBITRARY])->count(),
                ]
            ];

            return ['data' => $stats];

        } catch (\Exception $e) {
            Yii::error('Get document stats error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to retrieve statistics');
        }
    }

    /**
     * Search documents assigned to current user
     * 
     * @return array
     */
    public function actionSearch()
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        $searchTerm = Yii::$app->request->get('q', '');
        
        if (empty($searchTerm)) {
            throw new HttpException(400, 'Search term (q) is required');
        }

        try {
            $query = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->with(['createdBy', 'toUser'])
                ->where(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->andWhere([
                    'or',
                    ['like', 'd.name', $searchTerm],
                    ['like', 'd.didox_id', $searchTerm],
                ])
                ->orderBy(['d.created_at' => SORT_DESC]);

            // Pagination
            $page = (int)Yii::$app->request->get('page', 1);
            $limit = (int)Yii::$app->request->get('limit', 20);
            $offset = ($page - 1) * $limit;

            $total = $query->count();
            $documents = $query->limit($limit)->offset($offset)->all();

            return [
                'data' => $documents,
                'search_term' => $searchTerm,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Search documents error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to search documents');
        }
    }

    /**
     * Get incoming document data for signing (Step 3 from DIDOX documentation)
     * This gets the toSign value from GET /v1/documents/{doc_id}?owner=0
     * 
     * @return array
     */
    public function actionGetIncomingDocumentForSigning()
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        $didoxId = Yii::$app->request->post('didox_id');
        
        if (empty($didoxId)) {
            throw new HttpException(400, 'didox_id parameter is required');
        }

        try {
            // Find document and verify it's assigned to current user
            $document = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->where(['d.didox_id' => $didoxId])
                ->andWhere(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->one();

            if (!$document) {
                throw new HttpException(404, 'Document not found or not assigned to you');
            }

            // Check if document can be signed
            if (!$document->isDidoxDocument()) {
                throw new HttpException(422, 'Document is not connected to DIDOX');
            }

            // Check if document is in correct status for signing (STATUS_WAITING_YOUR_SIGNATURE = 2)
            if ($document->didox_status != 1) {
                throw new HttpException(422, 'Document is not waiting for your signature. Current status: ' . $document->getDidoxStatusLabel());
            }

            // Get user's DIDOX token
            if (empty($user->eimzo_didox_token)) {
                throw new HttpException(401, 'User not authenticated with DIDOX. Please login with E-IMZO first.');
            }

            // Get document data from DIDOX API for incoming documents
            $didoxService = new \app\services\DidoxService();
            $result = $didoxService->getIncomingDocumentForSigning($didoxId, $user->eimzo_didox_token);

            if ($result['success']) {
                // Extract the toSign value from DIDOX response
                $toSignValue = $result['data']['toSign'] ?? null;
                
                if (empty($toSignValue)) {
                    throw new HttpException(422, 'No toSign value found in DIDOX response. Document may not be ready for signing.');
                }

                return [
                    'success' => true,
                    'message' => 'Document toSign value retrieved successfully from DIDOX',
                    'data' => [
                        'document_id' => $document->id,
                        'didox_id' => $didoxId,
                        'to_sign_value' => $toSignValue, // This is base64 data with sender's signature
                        'owner' => 0, // Incoming document
                        'status' => $document->didox_status,
                        'status_label' => $document->getDidoxStatusLabel(),
                        'debug_info' => $result['debug'] ?? null
                    ]
                ];
                } else {
                    $errorMessage = 'Failed to get document from DIDOX: ';
                    if (isset($result['error'])) {
                        $errorMessage .= $this->formatErrorMessage($result['error']);
                    } else {
                        $errorMessage .= 'Unknown error';
                    }
                    throw new HttpException(422, $errorMessage);
                }

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Get incoming document for signing error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to retrieve document for signing');
        }
    }

    /**
     * Accept/Sign incoming document (Final step - send timeStampTokenB64)
     * 
     * @return array
     */
    public function actionAcceptIncomingDocument()
    {
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        $post = Yii::$app->request->post();
        $didoxId = $post['didox_id'] ?? null;
        $signature = $post['signature'] ?? null; // This is the timeStampTokenB64

        if (empty($didoxId) || empty($signature)) {
            throw new HttpException(400, 'didox_id and signature parameters are required');
        }

        try {
            // Find document and verify it's assigned to current user
            $document = DidoxDocument::find()
                ->alias('d')
                ->joinWith(['order o'])
                ->where(['d.didox_id' => $didoxId])
                // ->andWhere(['status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
                ->andWhere([
                    'or',
                    ['d.to_user_id' => $user->id],
                    ['o.user_id' => $user->id]
                ])
                ->one();

            if (!$document) {
                throw new HttpException(404, 'Document not found or not assigned to you');
            }

            // Check if document can be signed
            if ($document->didox_status != 1) {
                throw new HttpException(422, 'Document is not waiting for your signature. Current status: ' . $document->getDidoxStatusLabel());
            }

            // Get user's DIDOX token
            if (empty($user->eimzo_didox_token)) {
                throw new HttpException(401, 'User not authenticated with DIDOX. Please login with E-IMZO first.');
            }

            // Accept document in DIDOX using the signature
            $didoxService = new \app\services\DidoxService();
            $result = $didoxService->acceptIncomingDocument($didoxId, $signature, $user->eimzo_didox_token);

            if ($result['success']) {
                // Update document status
                $document->didox_status = DidoxDocument::STATUS_SIGNED; // Status 3 = Signed
                $document->didox_signed_at = date('Y-m-d H:i:s');

                // Store the response data
                $existingData = $document->getDidoxDataArray();
                $mergedData = array_merge($existingData, $result['data']);
                $document->setDidoxData($mergedData);

                if ($document->save(false)) {
                    return [
                        'success' => true,
                        'message' => 'Document successfully signed and accepted',
                        'data' => [
                            'document_id' => $document->id,
                            'didox_id' => $didoxId,
                            'status' => $document->didox_status,
                            'status_label' => $document->getDidoxStatusLabel(),
                            'signed_at' => $document->didox_signed_at
                        ]
                    ];
                } else {
                    throw new HttpException(500, 'Failed to update document status');
                }
            } else {
                $errorMessage = 'Failed to accept document in DIDOX: ';
                if (isset($result['error'])) {
                    $errorMessage .= $this->formatErrorMessage($result['error']);
                } else {
                    $errorMessage .= 'Unknown error';
                }
                throw new HttpException(422, $errorMessage);
            }

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Accept incoming document error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to accept document');
        }
    }
}