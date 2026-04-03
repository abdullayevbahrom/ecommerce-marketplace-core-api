<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\order\Order;
use app\models\didox\DidoxDocument;
use app\models\didox\DidoxDocumentSearch;
use app\services\DidoxService;

/**
 * DidoxController handles DIDOX document management with E-IMZO authentication
 */
class DidoxController extends Controller
{
    public $user;
    
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'logout' => ['POST'],
                    'sign' => ['POST'],
                    'cancel' => ['POST'],
                    'save-token' => ['POST'],
                    'authenticate-password' => ['POST'],
                    'authenticate-signer' => ['POST'],
                    'login-to-company' => ['POST'],
                ],
            ],
        ];
    }
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (!$this->user) {
            Yii::$app->user->logout(false);
            return $this->redirect(["/admin/default"]);
        }

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('didox', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        // Check E-IMZO authentication only for actions that interact with DIDOX platform
        $didoxRequiredActions = ['sign', 'sync', 'get-sign-data'];
        $createUpdateActions = ['create', 'update']; // These will check during form submission
        
        if (in_array($action->id, $didoxRequiredActions)) {
            if (!$this->isDidoxAuthenticated()) {
                Yii::$app->session->setFlash('error', 'DIDOX authentication required for this action');
                return $this->redirect(['login']);
            }
        }

        return parent::beforeAction($action);
    }

    /**
     * Check if user is authenticated via E-IMZO for DIDOX
     */
    private function isDidoxAuthenticated() {
        $session = Yii::$app->session;
        return $session->has('didox_authenticated') && 
               $session->get('didox_authenticated') === true &&
               $session->has('didox_token') &&
               !empty($session->get('didox_token'));
    }

    private function restoreDidoxSessionFromStoredToken(): bool
    {
        if ($this->isDidoxAuthenticated()) {
            return true;
        }

        $didoxService = new DidoxService();
        $tokenStatus = $didoxService->getTokenStatus();
        if (empty($tokenStatus['has_token']) || !empty($tokenStatus['is_expired'])) {
            return false;
        }

        $settings = \app\models\Settings::find()
            ->where(['type' => ['didox_eimzo_token', 'didox_seller_inn']])
            ->all();
        $settingMap = ArrayHelper::map($settings, 'type', 'content');

        $token = trim((string)($settingMap['didox_eimzo_token'] ?? ''));
        if ($token === '') {
            return false;
        }

        $taxId = trim((string)($settingMap['didox_seller_inn'] ?? ''));
        if ($taxId === '') {
            $taxId = trim((string)($this->user->eimzo_tax_id ?? ''));
        }

        $session = Yii::$app->session;
        $session->set('didox_authenticated', true);
        $session->set('didox_token', $token);
        $session->set('didox_tax_id', $taxId);
        $session->set('didox_connection_type', 'token');
        $session->set('didox_auth_method', 'stored_token');
        $session->set('didox_user_data', []);

        return true;
    }

    /**
     * Lists all DidoxDocument models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DidoxDocumentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single DidoxDocument model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new DidoxDocument model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param integer $orderId Optional order ID to auto-populate invoice data
     * @return mixed
     */
    public function actionCreate($orderId = null)
    {
        $model = new DidoxDocument();
        $invoiceModel = new \app\models\didox\DidoxDocumentInvoice();
        $users = $this->getUsersWithEimzoTaxId();
        $order = null;

        // Set default DIDOX document type for invoice
        if ($model->isNewRecord && empty($model->didox_doc_type)) {
            $model->didox_doc_type = '002'; // Default for invoice
        }

        // Load order if provided and validate it's not already connected
        if ($orderId) {
            $order = Order::findOne($orderId);
            if (!$order) {
                Yii::$app->session->setFlash('error', 'Заказ не найден');
                return $this->redirect(['index']);
            }
            
            // Check if order is already connected to any DIDOX document
            $existingDocument = DidoxDocument::find()
                ->where(['order_id' => $orderId])
                ->one();
                
            if ($existingDocument) {
                $documentTypeLabel = $existingDocument->getDocumentTypeLabel();
                Yii::$app->session->setFlash('error', 
                    "Заказ #{$orderId} уже связан с документом \"{$existingDocument->name}\" ({$documentTypeLabel}). " .
                    "Один заказ может быть связан только с одним документом DIDOX."
                );
                return $this->redirect(['index']);
            }
            
            // Set order_id for the document
            $model->order_id = $orderId;
            
            // Auto-populate invoice data from order
            if ($order->user) {
                $invoiceModel->buyer_tin = $order->user->inn ?? $order->user->phone ?? '';
                $invoiceModel->buyer_name = trim(($order->user->name ?? '') . ' ' . ($order->user->lastname ?? ''));
                
                // Get address from user addresses or order
                $address = '';
                if ($order->user->addresses) {
                    $defaultAddress = $order->user->addresses[0] ?? null;
                    if ($defaultAddress) {
                        $address = $defaultAddress->address;
                    }
                }
                if (empty($address)) {
                    $address = $order->user->last_address ?: $order->address ?: '';
                }
                $invoiceModel->buyer_address = $address;
            } else {
                // Handle orders without users (guest orders)
                $invoiceModel->buyer_tin = $order->phone ?? '';
                $invoiceModel->buyer_name = trim(($order->name ?? '') . ' ' . ($order->lastname ?? ''));
                $invoiceModel->buyer_address = $order->address ?: '';
            }
            
            // Set seller information from current admin
            $invoiceModel->seller_tin = $this->user->eimzo_tax_id ?? '';
            $invoiceModel->seller_name = 'Ваша компания'; // You can get this from settings
            $invoiceModel->seller_address = 'Адрес вашей компании'; // You can get this from settings
        }

        if ($model->load(Yii::$app->request->post()) && $invoiceModel->load(Yii::$app->request->post())) {
            // Handle order connection from form
            $selectedOrderId = Yii::$app->request->post('selected_order_id');
            if ($selectedOrderId) {
                // Check if this order is already connected to another document
                $existingDocument = DidoxDocument::find()
                    ->where(['order_id' => $selectedOrderId])
                    ->andWhere(['!=', 'id', $model->id]) // Exclude current document if updating
                    ->one();
                    
                if ($existingDocument) {
                    $documentTypeLabel = $existingDocument->getDocumentTypeLabel();
                    Yii::$app->session->setFlash('error', 
                        "Заказ #{$selectedOrderId} уже связан с документом \"{$existingDocument->name}\" ({$documentTypeLabel}). " .
                        "Выберите другой заказ или отмените связь."
                    );
                    // Don't save, return to form
                    return $this->render('create', [
                        'model' => $model,
                        'users' => $users,
                        'order' => $order,
                        'currentAdminTin' => $this->user->eimzo_tax_id,
                    ]);
                }
                
                $model->order_id = $selectedOrderId;
            }
            
            // Clear any previous errors
            $model->clearDidoxErrors();
            
            // Start transaction
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Link invoice model to document
                    $invoiceModel->document_id = $model->id;
                    
                    if ($invoiceModel->save()) {
                        // Save products
                        $this->saveDocumentProducts($model);
                        
                        // Try to create document in DIDOX if authenticated, otherwise save locally only
                        $didoxCreated = false;
                        if ($this->isDidoxAuthenticated()) {
                            $didoxCreated = $this->createDidoxDocument($model, $invoiceModel, null);
                        }
                        
                        // Update model with any DIDOX changes
                        $model->save(false);
                        
                        $transaction->commit();
                        
                        if ($didoxCreated) {
                            Yii::$app->session->setFlash('success', 'Счет-фактура создана и отправлена в DIDOX');
                        } else {
                            if ($this->isDidoxAuthenticated()) {
                                if ($model->hasDidoxErrors()) {
                                    Yii::$app->session->setFlash('warning', 'Счет-фактура сохранена локально, но отправка в DIDOX не удалась. Проверьте детали ошибки ниже.');
                                } else {
                                    Yii::$app->session->setFlash('warning', 'Счет-фактура сохранена локально, но отправка в DIDOX не удалась');
                                }
                            } else {
                                Yii::$app->session->setFlash('info', 'Счет-фактура сохранена локально. Войдите в DIDOX для отправки на платформу');
                            }
                        }
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка сохранения счет-фактуры: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'users' => $users,
            'order' => $order,
            'currentAdminTin' => $this->user->eimzo_tax_id,
        ]);
    }

    /**
     * Creates a new arbitrary contract document.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param integer $orderId Optional order ID to auto-populate contract data
     * @return mixed
     */
    public function actionCreateArbitrary($orderId = null)
    {
        $model = new DidoxDocument();
        $arbitraryModel = new \app\models\didox\DidoxDocumentArbitrary();
        $order = null;
        $users = $this->getUsersWithEimzoTaxId();
        
        // Set default DIDOX document type for arbitrary contract
        $model->didox_doc_type = '000'; // DIDOX type for arbitrary documents
        $model->document_type = DidoxDocument::DOCUMENT_TYPE_ARBITRARY;

        // Load order if provided via URL parameter
        if ($orderId) {
            $order = Order::findOne($orderId);
            if (!$order) {
                Yii::$app->session->setFlash('error', 'Заказ не найден');
                return $this->redirect(['index']);
            }
            
            // Check if order is already connected to another DIDOX document
            $existingConnection = DidoxDocument::isOrderConnectedToDidox($orderId);
            if ($existingConnection) {
                Yii::$app->session->setFlash('error', "Заказ #{$orderId} уже связан с документом \"{$existingConnection['name']}\" ({$existingConnection['document_type_label']})");
                return $this->redirect(['index']);
            }
            
            // Set order_id for the document
            $model->order_id = $orderId;
            
            // Auto-populate contract data from order
            $adminData = [
                'tin' => $this->user->eimzo_tax_id ?? '',
                'name' => 'Ваша компания', // You can get this from settings or user profile
                'address' => 'Адрес вашей компании', // You can get this from settings
            ];
            
            $arbitraryModel->populateFromOrder($order, $adminData);
        }

        if ($model->load(Yii::$app->request->post()) && $arbitraryModel->load(Yii::$app->request->post())) {
            // Handle order selection from form POST data
            $selectedOrderId = Yii::$app->request->post('selected_order_id');
            if ($selectedOrderId && !$model->order_id) {
                // Check if selected order is already connected to another DIDOX document
                $existingConnection = DidoxDocument::isOrderConnectedToDidox($selectedOrderId);
                if ($existingConnection) {
                    Yii::$app->session->setFlash('error', "Заказ #{$selectedOrderId} уже связан с документом \"{$existingConnection['name']}\" ({$existingConnection['document_type_label']})");
                    // Re-render form with error
                    return $this->render('create-arbitrary', [
                        'model' => $model,
                        'arbitraryModel' => $arbitraryModel,
                        'order' => $order,
                        'users' => $users,
                        'currentAdminTin' => $this->user->eimzo_tax_id,
                    ]);
                }
                
                // Load the selected order
                $order = Order::findOne($selectedOrderId);
                if ($order) {
                    $model->order_id = $selectedOrderId;
                }
            }
            
            // Ensure didox_doc_type and document_type are correctly set
            $model->didox_doc_type = '000';
            $model->document_type = DidoxDocument::DOCUMENT_TYPE_ARBITRARY;
            
            // Clear any previous errors
            $model->clearDidoxErrors();
            
            // Start transaction
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Link arbitrary model to document
                    $arbitraryModel->document_id = $model->id;
                    
                    if ($arbitraryModel->save()) {
                        // Generate PDF automatically
                        $arbitraryModel->generateContractPdf($order);
                        $arbitraryModel->save(false); // Save PDF data
                        
                        // Try to create document in DIDOX if authenticated
                        $didoxCreated = false;
                        if ($this->isDidoxAuthenticated()) {
                            $didoxCreated = $this->createDidoxDocument($model, null, $arbitraryModel);
                        }
                        
                        // Update model with any DIDOX changes
                        $model->save(false);
                        
                        $transaction->commit();
                        
                        if ($didoxCreated) {
                            Yii::$app->session->setFlash('success', 'Произвольный договор создан и отправлен в DIDOX');
                        } else {
                            if ($this->isDidoxAuthenticated()) {
                                if ($model->hasDidoxErrors()) {
                                    Yii::$app->session->setFlash('warning', 'Договор сохранен локально, но отправка в DIDOX не удалась. Проверьте детали ошибки ниже.');
                                } else {
                                    Yii::$app->session->setFlash('warning', 'Договор сохранен локально, но отправка в DIDOX не удалась');
                                }
                            } else {
                                Yii::$app->session->setFlash('info', 'Договор сохранен локально. Войдите в DIDOX для отправки на платформу');
                            }
                        }
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка сохранения договора: ' . $e->getMessage());
            }
        }

        return $this->render('create-arbitrary', [
            'model' => $model,
            'arbitraryModel' => $arbitraryModel,
            'order' => $order,
            'users' => $users,
            'currentAdminTin' => $this->user->eimzo_tax_id,
        ]);
    }

    /**
     * Updates an existing DidoxDocument model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // Handle different document types
        if ($model->isArbitrary()) {
            return $this->updateArbitraryDocument($model);
        } else {
            return $this->updateInvoiceDocument($model);
        }
    }
    
    /**
     * Updates an invoice-type DIDOX document
     */
    private function updateInvoiceDocument($model)
    {
        $invoiceModel = $model->invoice ?: new \app\models\didox\DidoxDocumentInvoice(['document_id' => $model->id]);
        $users = $this->getUsersWithEimzoTaxId();
        $order = $model->order_id ? Order::findOne($model->order_id) : null;
        
        // Initialize variables to avoid linter warnings
        $didoxUpdated = false;
        $didoxCreated = false;

        if ($model->load(Yii::$app->request->post()) && $invoiceModel->load(Yii::$app->request->post())) {
            // Handle order selection from form POST data
            $selectedOrderId = Yii::$app->request->post('selected_order_id');
            if ($selectedOrderId && $selectedOrderId != $model->order_id) {
                // Check if selected order is already connected to another DIDOX document
                $existingConnection = DidoxDocument::isOrderConnectedToDidox($selectedOrderId, $model->id);
                if ($existingConnection) {
                    Yii::$app->session->setFlash('error', "Заказ #{$selectedOrderId} уже связан с документом \"{$existingConnection['name']}\" ({$existingConnection['document_type_label']})");
                    // Re-render form with error
                    return $this->render('update', [
                        'model' => $model,
                        'users' => $users,
                        'order' => $order,
                        'currentAdminTin' => $this->user->eimzo_tax_id,
                    ]);
                }
                
                // Update order connection
                $model->order_id = $selectedOrderId;
                $order = Order::findOne($selectedOrderId);
            }
            
            // Clear any previous errors when updating
            $model->clearDidoxErrors();
            
            // Start transaction
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Ensure invoice model is linked to document
                    $invoiceModel->document_id = $model->id;
                    
                    if ($invoiceModel->save()) {
                        // Save products
                        $this->saveDocumentProducts($model);
                        
                        // Handle DIDOX integration based on document state
                        if ($this->isDidoxAuthenticated()) {
                            if ($model->isDidoxDocument()) {
                                // Document exists in DIDOX - update it
                                $didoxUpdated = $this->updateDidoxDocument($model, $invoiceModel);
                            } else {
                                // Document doesn't exist in DIDOX - try to create it
                                $didoxCreated = $this->createDidoxDocument($model, $invoiceModel, null);
                            }
                        }
                        
                        // Update model with any DIDOX changes
                        $model->save(false);
                        
                        $transaction->commit();
                        
                        // Set appropriate flash messages based on what happened
                        if ($didoxCreated) {
                            Yii::$app->session->setFlash('success', 'Счет-фактура обновлена и успешно отправлена в DIDOX');
                        } elseif ($didoxUpdated) {
                            Yii::$app->session->setFlash('success', 'Счет-фактура успешно обновлена в локальной базе и на платформе DIDOX');
                        } elseif ($model->isDidoxDocument() && !$this->isDidoxAuthenticated()) {
                            Yii::$app->session->setFlash('warning', 'Счет-фактура обновлена локально. Войдите в DIDOX для синхронизации изменений');
                        } elseif (!$this->isDidoxAuthenticated()) {
                            Yii::$app->session->setFlash('info', 'Счет-фактура обновлена локально. Войдите в DIDOX для отправки на платформу');
                        } else {
                            // DIDOX authenticated but update/create failed
                            if ($model->hasDidoxErrors()) {
                                Yii::$app->session->setFlash('warning', 'Счет-фактура обновлена локально, но обновление в DIDOX не удалось. Проверьте детали ошибки ниже.');
                            } else {
                                Yii::$app->session->setFlash('success', 'Счет-фактура успешно обновлена');
                            }
                        }
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка обновления счет-фактуры: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'users' => $users,
            'order' => $order,
            'currentAdminTin' => $this->user->eimzo_tax_id,
        ]);
    }
    
    /**
     * Updates an arbitrary-type DIDOX document
     */
    private function updateArbitraryDocument($model)
    {
        $arbitraryModel = $model->arbitrary ?: new \app\models\didox\DidoxDocumentArbitrary(['document_id' => $model->id]);
        $order = $model->order_id ? Order::findOne($model->order_id) : null;
        $users = $this->getUsersWithEimzoTaxId();
        // Initialize variables to avoid linter warnings
        $didoxUpdated = false;
        $didoxCreated = false;

        if ($model->load(Yii::$app->request->post()) && $arbitraryModel->load(Yii::$app->request->post())) {
            // Handle order selection from form POST data
            $selectedOrderId = Yii::$app->request->post('selected_order_id');
            if ($selectedOrderId && $selectedOrderId != $model->order_id) {
                // Check if selected order is already connected to another DIDOX document
                $existingConnection = DidoxDocument::isOrderConnectedToDidox($selectedOrderId, $model->id);
                if ($existingConnection) {
                    Yii::$app->session->setFlash('error', "Заказ #{$selectedOrderId} уже связан с документом \"{$existingConnection['name']}\" ({$existingConnection['document_type_label']})");
                    // Re-render form with error
                    return $this->render('update-arbitrary', [
                        'model' => $model,
                        'arbitraryModel' => $arbitraryModel,
                        'order' => $order,
                        'users' => $users,
                        'currentAdminTin' => $this->user->eimzo_tax_id,
                    ]);
                }
                
                // Update order connection
                $model->order_id = $selectedOrderId;
                $order = Order::findOne($selectedOrderId);
            }
            
            // Ensure didox_doc_type and document_type are correctly set
            $model->didox_doc_type = '000';
            $model->document_type = DidoxDocument::DOCUMENT_TYPE_ARBITRARY;
            
            // Clear any previous errors when updating
            $model->clearDidoxErrors();
            
            // Start transaction
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    // Ensure arbitrary model is linked to document
                    $arbitraryModel->document_id = $model->id;
                    
                    if ($arbitraryModel->save()) {
                        // Generate PDF automatically
                        $arbitraryModel->generateContractPdf($order);
                        $arbitraryModel->save(false); // Save PDF data
                        
                        // Handle DIDOX integration based on document state
                        if ($this->isDidoxAuthenticated()) {
                            if ($model->isDidoxDocument()) {
                                // Document exists in DIDOX - update it
                                $didoxUpdated = $this->updateDidoxDocument($model, null, $arbitraryModel);
                            } else {
                                // Document doesn't exist in DIDOX - try to create it
                                $didoxCreated = $this->createDidoxDocument($model, null, $arbitraryModel);
                            }
                        }
                        
                        // Update model with any DIDOX changes
                        $model->save(false);
                        
                        $transaction->commit();
                        
                        // Set appropriate flash messages based on what happened
                        if ($didoxCreated) {
                            Yii::$app->session->setFlash('success', 'Произвольный договор обновлен и успешно отправлен в DIDOX');
                        } elseif ($didoxUpdated) {
                            Yii::$app->session->setFlash('success', 'Произвольный договор успешно обновлен в локальной базе и на платформе DIDOX');
                        } elseif ($model->isDidoxDocument() && !$this->isDidoxAuthenticated()) {
                            Yii::$app->session->setFlash('warning', 'Произвольный договор обновлен локально. Войдите в DIDOX для синхронизации изменений');
                        } elseif (!$this->isDidoxAuthenticated()) {
                            Yii::$app->session->setFlash('info', 'Произвольный договор обновлен локально. Войдите в DIDOX для отправки на платформу');
                        } else {
                            // DIDOX authenticated but update/create failed
                            if ($model->hasDidoxErrors()) {
                                Yii::$app->session->setFlash('warning', 'Произвольный договор обновлен локально, но обновление в DIDOX не удалось. Проверьте детали ошибки ниже.');
                            } else {
                                Yii::$app->session->setFlash('success', 'Произвольный договор успешно обновлен');
                            }
                        }
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка обновления произвольного договора: ' . $e->getMessage());
            }
        }

        return $this->render('update-arbitrary', [
            'model' => $model,
            'arbitraryModel' => $arbitraryModel,
            'order' => $order,
            'users' => $users,
            'currentAdminTin' => $this->user->eimzo_tax_id,
        ]);
    }

    /**
     * Deletes an existing DidoxDocument model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Cancel document in DIDOX if it exists and can be canceled
        if ($model->isDidoxDocument() && $model->canBeCanceledInDidox()) {
            $this->cancelDidoxDocument($model);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Document deleted successfully');

        return $this->redirect(['index']);
    }

    /**
     * Sign DIDOX document
     */
    public function actionSign($id) {
        $model = $this->findModel($id);
        
        if (!$model->isDidoxDocument()) {
            Yii::$app->session->setFlash('error', 'This document is not connected to DIDOX');
            return $this->redirect(['view', 'id' => $id]);
        }

        if (!$model->canBeSignedInDidox()) {
            Yii::$app->session->setFlash('error', 'Document cannot be signed in current status');
            return $this->redirect(['view', 'id' => $id]);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            
            if (empty($post['signature'])) {
                Yii::$app->session->setFlash('error', 'Signature is required');
                return $this->redirect(['view', 'id' => $id]);
            }

            try {
                $didoxService = new DidoxService();
                $session = Yii::$app->session;
                $userKey = $session->get('didox_token', '');
                
                $result = $didoxService->signDocument($model->didox_id, $post['signature'], $userKey);

                if ($result['success']) {
                    // Extract status from Didox response
                    $responseData = $result['data'];
                    $documentData = isset($responseData['data']['document']) ? $responseData['data']['document'] : 
                                   (isset($responseData['document']) ? $responseData['document'] : $responseData);
                    
                    // Get status from response (prefer doc_status, fallback to status, default to 1 = signed)
                    $newStatus = 1; // Default: signed by seller
                    if (isset($documentData['doc_status'])) {
                        $newStatus = (int)$documentData['doc_status'];
                    } elseif (isset($documentData['status'])) {
                        $newStatus = (int)$documentData['status'];
                    }
                    
                    $model->didox_status = $newStatus;
                    $model->didox_signed_at = date('Y-m-d H:i:s');
                    
                    // Extract document ID if not already set
                    $model->extractAndSetDidoxDocumentId($result['data']);
                    
                    // Merge new response data with existing DIDOX data
                    $existingData = $model->getDidoxDataArray();
                    $mergedData = array_merge($existingData, $result['data']);
                    $model->setDidoxData($mergedData);

                    if ($model->save(false)) {
                        // Auto-download PDF after signing (silent)
                        try {
                            $model->downloadPdfFromDidox(['uz', 'ru']);
                        } catch (\Exception $pdfEx) {
                            \app\models\Log::log('didox_pdf', "PDF download failed after signing document #{$model->id}", $pdfEx->getMessage(), 'warning');
                        }
                        
                        // After signing, automatically send to partner if requested
                        $autoSend = isset($post['auto_send_to_partner']) && $post['auto_send_to_partner'];
                        if ($autoSend) {
                            $sendResult = $didoxService->sendDocumentToPartner($model->didox_id, $userKey);
                            if ($sendResult['success']) {
                                // Extract status from send response
                                $sendResponseData = $sendResult['data'];
                                $sendDocData = isset($sendResponseData['data']['document']) ? $sendResponseData['data']['document'] : 
                                              (isset($sendResponseData['document']) ? $sendResponseData['document'] : $sendResponseData);
                                
                                if (isset($sendDocData['doc_status'])) {
                                    $model->didox_status = (int)$sendDocData['doc_status'];
                                } elseif (isset($sendDocData['status'])) {
                                    $model->didox_status = (int)$sendDocData['status'];
                                } else {
                                    $model->didox_status = 1; // Waiting for partner signature
                                }
                                
                                // Extract document ID if not already set from send response
                                $model->extractAndSetDidoxDocumentId($sendResult['data']);
                                
                                // Merge send response data with existing DIDOX data
                                $existingData = $model->getDidoxDataArray();
                                $mergedData = array_merge($existingData, $sendResult['data']);
                                $model->setDidoxData($mergedData);
                                $model->save(false);
                                Yii::$app->session->setFlash('success', "Document signed (status: {$newStatus}) and sent to partner");
                            } else {
                                Yii::$app->session->setFlash('warning', 'Document signed successfully, but failed to send to partner: ' . (isset($sendResult['error']) ? $sendResult['error'] : 'Unknown error'));
                            }
                        } else {
                            Yii::$app->session->setFlash('success', "Document signed successfully (status: {$newStatus}). You can now send it to the partner.");
                        }
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to sign document: ' . (isset($result['error']) ? $result['error'] : 'Unknown error'));
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Get signable data.json for outgoing E-IMZO signing (step 1)
     * AJAX endpoint to fetch DIDOX owner=1 payload and convert data.json to base64
     */
    public function actionGetDocumentForSigning() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            return ['success' => false, 'message' => 'Only POST requests allowed'];
        }
        
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => 'Invalid JSON data'];
        }
        
        $didoxId = isset($data['didox_id']) ? $data['didox_id'] : null;
        
        if (!$didoxId) {
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false, 
                'message' => 'Missing didox_id parameter',
                'required' => ['didox_id']
            ];
        }
        
        try {
            // Find document by didox_id instead of local document ID
            $model = DidoxDocument::find()->where(['didox_id' => $didoxId])->one();
            
            if (!$model) {
                Yii::$app->response->statusCode = 404;
                return [
                    'success' => false, 
                    'message' => 'Document not found with didox_id: ' . $didoxId,
                    'error_details' => [
                        'didox_id' => $didoxId
                    ]
                ];
            }
            
            // Validate document can be signed
            if (!$model->isDidoxDocument()) {
                Yii::$app->response->statusCode = 422;
                return [
                    'success' => false, 
                    'message' => 'Document is not connected to DIDOX',
                    'error_details' => [
                        'document_id' => $model->id,
                        'didox_id' => $model->didox_id,
                        'is_didox_document' => false
                    ]
                ];
            }
            
            if (!$model->canBeSignedInDidox()) {
                Yii::$app->response->statusCode = 422;
                return [
                    'success' => false, 
                    'message' => 'Document cannot be signed in current status',
                    'error_details' => [
                        'document_id' => $model->id,
                        'current_status' => $model->didox_status,
                        'status_label' => $model->getDidoxStatusLabel()
                    ]
                ];
            }
            
            // Initialize DIDOX service
            $didoxService = new DidoxService();
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            if (empty($userKey)) {
                Yii::$app->response->statusCode = 401;
                return [
                    'success' => false, 
                    'message' => 'User not authenticated with DIDOX. Please login first.',
                    'error_details' => [
                        'authentication_required' => true,
                        'login_url' => '/admin/didox/login'
                    ]
                ];
            }
            
            // Get document data from DIDOX API using the provided didox_id
            // This calls: GET /v1/documents/{didox_id}?owner=1
            Yii::info('Making DIDOX API request: GET /v1/documents/' . $didoxId . '?owner=1', 'didox-debug');
            $result = $didoxService->getDocumentForSigning($didoxId, $userKey);
            
            // Log the DIDOX API response for debugging
            Yii::info('DIDOX API response: ' . json_encode($result), 'didox-debug');
            
            if ($result['success']) {
                $payload = $didoxService->buildOutgoingDocumentSignaturePayload($result['data']);
                if (!$payload['success']) {
                    Yii::$app->response->statusCode = 422;
                    return [
                        'success' => false,
                        'message' => $payload['error'],
                        'didox_details' => [
                            'endpoint' => '/v1/documents/' . $model->didox_id . '?owner=1',
                            'response' => $result['data'],
                        ]
                    ];
                }

                $documentJson = $payload['documentJson'];
                $documentBase64 = $payload['documentBase64'];

                Yii::info('Successfully converted DIDOX data.json to base64. Length: ' . strlen($documentBase64), 'didox-debug');
                
                return [
                    'success' => true,
                    'message' => 'Document data retrieved successfully from DIDOX API',
                    'data' => [
                        'document_id' => $model->id,
                        'didox_id' => $didoxId,
                        'didox_endpoint' => '/v1/documents/' . $didoxId . '?owner=1',
                        'document_json' => $documentJson, // This is DIDOX data.json converted to JSON
                        'document_base64' => $documentBase64, // This is DIDOX data.json converted to base64
                        'base64_length' => strlen($documentBase64),
                        'source' => 'DIDOX_API_DIRECT_CALL',
                        'sign_source' => 'data.json'
                    ]
                ];
            } else {
                // Handle DIDOX API errors
                $didoxHttpCode = isset($result['httpCode']) ? $result['httpCode'] : null;
                $didoxData = isset($result['data']) ? $result['data'] : null;
                $didoxError = isset($result['error']) ? $result['error'] : null;
                
                if ($didoxHttpCode) {
                    Yii::$app->response->statusCode = $didoxHttpCode;
                } else {
                    Yii::$app->response->statusCode = 422;
                }
                
                $errorMessage = $didoxError ?? 'Failed to get document data';
                if ($didoxData && isset($didoxData['message'])) {
                    $errorMessage = $didoxData['message'];
                }
                
                return [
                    'success' => false,
                    'message' => 'Failed to get document for signing: ' . $errorMessage,
                    'didox_details' => [
                        'http_code' => $didoxHttpCode,
                        'response' => $didoxData,
                        'endpoint' => '/v1/documents/' . $model->didox_id . '?owner=1'
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500;
            Yii::error('Get document for signing error: ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), 'didox');
            
            return [
                'success' => false, 
                'message' => 'System error: ' . $e->getMessage(),
                'error_details' => [
                    'type' => 'system_exception',
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * Sign document with E-IMZO signature (step 2)
     * AJAX endpoint for E-IMZO signing with timeStampTokenB64
     */
    public function actionSignDocument() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405; // Method Not Allowed
            return ['success' => false, 'message' => 'Only POST requests allowed'];
        }
        
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (!$data) {
            Yii::$app->response->statusCode = 400; // Bad Request
            return ['success' => false, 'message' => 'Invalid JSON data'];
        }
        
        $documentId = $data['documentId'] ?? null;
        $signature = $data['signature'] ?? null;
        $taxId = $data['taxId'] ?? null;
        $certificateInfo = $data['certificateInfo'] ?? null;
        
        if (!$documentId || !$signature || !$taxId) {
            Yii::$app->response->statusCode = 400; // Bad Request
            return [
                'success' => false, 
                'message' => 'Missing required parameters',
                'required' => ['documentId', 'signature', 'taxId'],
                'received' => [
                    'documentId' => !empty($documentId),
                    'signature' => !empty($signature),
                    'taxId' => !empty($taxId)
                ]
            ];
        }
        
        try {
            $model = $this->findModel($documentId);
            
            // Validate document can be signed
            if (!$model->isDidoxDocument()) {
                Yii::$app->response->statusCode = 422; // Unprocessable Entity
                return [
                    'success' => false, 
                    'message' => 'Document is not connected to DIDOX',
                    'error_details' => [
                        'document_id' => $model->id,
                        'didox_id' => $model->didox_id,
                        'is_didox_document' => false
                    ]
                ];
            }
            
            if (!$model->canBeSignedInDidox()) {
                Yii::$app->response->statusCode = 422; // Unprocessable Entity
                return [
                    'success' => false, 
                    'message' => 'Document cannot be signed in current status',
                    'error_details' => [
                        'document_id' => $model->id,
                        'current_status' => $model->didox_status,
                        'status_label' => $model->getDidoxStatusLabel()
                    ]
                ];
            }
            
            // Initialize DIDOX service
            $didoxService = new DidoxService();
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            if (empty($userKey)) {
                Yii::$app->response->statusCode = 401; // Unauthorized
                return [
                    'success' => false, 
                    'message' => 'User not authenticated with DIDOX. Please login first.',
                    'error_details' => [
                        'authentication_required' => true,
                        'login_url' => '/admin/didox/login'
                    ]
                ];
            }
            
            // Sign document using DIDOX service
            $result = $didoxService->signDocument($model->didox_id, $signature, $userKey);
            
            // Log the full DIDOX response for debugging
            Yii::info('DIDOX signDocument response: ' . json_encode($result), 'didox-debug');
            \app\models\Log::log('didox_sign', "Sign response for document #{$model->id}", $result, 'info');
            
            if ($result['success']) {
                // Extract status from Didox response
                $responseData = $result['data'];
                $documentData = isset($responseData['data']['document']) ? $responseData['data']['document'] : 
                               (isset($responseData['document']) ? $responseData['document'] : $responseData);
                
                // Get status from response (prefer doc_status, fallback to status, default to 1 = signed)
                $newStatus = 1; // Default: signed by seller
                if (isset($documentData['doc_status'])) {
                    $newStatus = (int)$documentData['doc_status'];
                } elseif (isset($documentData['status'])) {
                    $newStatus = (int)$documentData['status'];
                }
                
                // Update document status from Didox response
                $model->didox_status = $newStatus;
                $model->didox_signed_at = date('Y-m-d H:i:s');
                
                // Store certificate info if provided
                if ($certificateInfo) {
                    $existingData = $model->getDidoxDataArray();
                    $existingData['certificate_info'] = $certificateInfo;
                    $existingData['signer_tax_id'] = $taxId;
                    $existingData['signed_at'] = date('Y-m-d H:i:s');
                    $model->setDidoxData($existingData);
                }
                
                // Extract document ID if not already set
                $model->extractAndSetDidoxDocumentId($result['data']);
                
                // Merge response data with existing DIDOX data
                $existingData = $model->getDidoxDataArray();
                $mergedData = array_merge($existingData, $result['data']);
                $model->setDidoxData($mergedData);
                
                if ($model->save(false)) {
                    // Auto-download PDF after signing (silent - errors logged)
                    $pdfDownloaded = false;
                    try {
                        $pdfResult = $model->downloadPdfFromDidox(['uz', 'ru']);
                        foreach ($pdfResult as $lang => $res) {
                            if (isset($res['success']) && $res['success'] && empty($res['cached'])) {
                                $pdfDownloaded = true;
                            }
                        }
                    } catch (\Exception $pdfEx) {
                        \app\models\Log::log('didox_pdf', "PDF download failed after signing document #{$model->id}", $pdfEx->getMessage(), 'warning');
                    }
                    
                    // Success - HTTP 200
                    Yii::$app->response->statusCode = 200;
                    return [
                        'success' => true, 
                        'message' => 'Document signed successfully',
                        'data' => [
                            'document_id' => $model->id,
                            'didox_id' => $model->didox_id,
                            'status' => $model->didox_status,
                            'status_from_didox' => $newStatus,
                            'signed_at' => $model->didox_signed_at,
                            'pdf_downloaded' => $pdfDownloaded
                        ]
                    ];
                } else {
                    // Database save error - HTTP 500
                    Yii::$app->response->statusCode = 500;
                    $errorDetails = [
                        'model_errors' => $model->getErrors(),
                        'attributes' => $model->getAttributes()
                    ];
                    
                    return [
                        'success' => false, 
                        'message' => 'Failed to save document after signing',
                        'debug_info' => $errorDetails
                    ];
                }
            } else {
                // Extract detailed DIDOX response information
                $didoxHttpCode = isset($result['httpCode']) ? $result['httpCode'] : null;
                $didoxData = isset($result['data']) ? $result['data'] : null;
                $didoxError = isset($result['error']) ? $result['error'] : null;
                
                // Determine appropriate HTTP status code based on DIDOX response
                if ($didoxHttpCode) {
                    // Mirror DIDOX HTTP status code
                    Yii::$app->response->statusCode = $didoxHttpCode;
                } else {
                    // Default to 422 for API errors
                    Yii::$app->response->statusCode = 422;
                }
                
                // Extract error message from various sources
                $errorMessage = $didoxError ?? 'Unknown signing error';
                if ($didoxData && isset($didoxData['message'])) {
                    $errorMessage = $didoxData['message'];
                } elseif ($didoxData && isset($didoxData['error'])) {
                    $errorMessage = $didoxData['error'];
                } elseif ($didoxData && is_string($didoxData)) {
                    $errorMessage = $didoxData;
                }
                
                // Store comprehensive error in document for debugging
                $debugInfo = isset($result['debug']) ? $result['debug'] : null;
                $errorData = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'action' => 'sign_document',
                    'error_message' => $errorMessage,
                    'didox_http_code' => $didoxHttpCode,
                    'didox_response' => $didoxData,
                    'full_result' => $result,
                    'tax_id' => $taxId,
                    'document_id' => $model->didox_id,
                    'signature_full' => $signature,
                    'signature_length' => strlen($signature),
                    'request_url' => '/v1/documents/' . $model->didox_id . '/sign',
                    'debug_info' => $debugInfo
                ];
                
                $model->didox_error_data = json_encode($errorData, JSON_PRETTY_PRINT);
                $model->save(false);
                
                return [
                    'success' => false, 
                    'message' => 'Failed to sign document: ' . $errorMessage,
                    'didox_details' => [
                        'http_code' => $didoxHttpCode,
                        'response' => $didoxData,
                        'endpoint' => '/v1/documents/' . $model->didox_id . '/sign',
                        'document_id' => $model->didox_id
                    ],
                    'error_details' => [
                        'user_key_exists' => !empty($userKey),
                        'signature_length' => strlen($signature),
                        'signature_full' => $signature,
                        'tax_id' => $taxId
                    ],
                    'debug_info' => [
                        'full_didox_result' => $result,
                        'request_sent' => [
                            'url' => '/v1/documents/' . $model->didox_id . '/sign',
                            'method' => 'POST',
                            'headers' => [
                                'Partner-Authorization' => 'CONFIGURED',
                                'user-key' => !empty($userKey) ? 'SET' : 'MISSING'
                            ],
                            'payload' => [
                                'signature' => $signature
                            ]
                        ],
                        'didox_debug' => $debugInfo
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            // System error - HTTP 500
            Yii::$app->response->statusCode = 500;
            
            // Log error and return response
            Yii::error('E-IMZO signing error: ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), 'didox');
            
            // Try to store error in document if we have it
            if (isset($model)) {
                $errorData = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'action' => 'sign_document',
                    'error_type' => 'system_exception',
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'tax_id' => $taxId ?? 'unknown',
                    'document_id' => $model->didox_id ?? 'unknown'
                ];
                
                $model->didox_error_data = json_encode($errorData, JSON_PRETTY_PRINT);
                $model->save(false);
            }
            
            return [
                'success' => false, 
                'message' => 'System error: ' . $e->getMessage(),
                'error_details' => [
                    'type' => 'system_exception',
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'code' => $e->getCode()
                ]
            ];
        }
    }

    /**
     * Send signed document to partner
     */
    public function actionSendToPartner($id) {
        $model = $this->findModel($id);
        
        if (!$model->isDidoxDocument()) {
            Yii::$app->session->setFlash('error', 'This document is not connected to DIDOX');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->didox_status != 3) { // Not signed
            Yii::$app->session->setFlash('error', 'Document must be signed before sending to partner');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $didoxService = new DidoxService();
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            $result = $didoxService->sendDocumentToPartner($model->didox_id, $userKey);

            if ($result['success']) {
                $model->didox_status = 1; // Waiting for partner signature
                
                // Extract document ID if not already set
                $model->extractAndSetDidoxDocumentId($result['data']);
                
                // Merge new response data with existing DIDOX data
                $existingData = $model->getDidoxDataArray();
                $mergedData = array_merge($existingData, $result['data']);
                $model->setDidoxData($mergedData);
                
                if ($model->save(false)) {
                    Yii::$app->session->setFlash('success', 'Document sent to partner successfully');
                }
            } else {
                Yii::$app->session->setFlash('error', 'Failed to send document to partner: ' . (isset($result['error']) ? $result['error'] : 'Unknown error'));
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Sync DIDOX document status
     */
    public function actionSync($id) {
        $model = $this->findModel($id);
        
        if (!$model->isDidoxDocument()) {
            Yii::$app->session->setFlash('error', 'This document is not connected to DIDOX');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $didoxService = new DidoxService();
            
            // Get user-key from session first
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            $tokenSource = 'session';
            
            // Fallback to DB setting didox_eimzo_token if session is empty
            if (empty($userKey)) {
                $sysSettings = \app\models\Settings::find()
                    ->where(['type' => 'didox_eimzo_token'])
                    ->one();
                
                if ($sysSettings && !empty($sysSettings->content)) {
                    $userKey = $sysSettings->content;
                    $tokenSource = 'database (didox_eimzo_token)';
                }
            }
            
            $result = $didoxService->getDocument($model->didox_id, $userKey);

            if ($result['success']) {
                $didoxData = $result['data'];
                
                // Extract document info from nested structure
                // Response structure: { "data": { "document": { "status": 1, ... }, "json": {...} } }
                $documentData = null;
                if (isset($didoxData['data']['document'])) {
                    // v1 API: data.data.document
                    $documentData = $didoxData['data']['document'];
                } elseif (isset($didoxData['document'])) {
                    // Alternative: data.document
                    $documentData = $didoxData['document'];
                } else {
                    // Fallback to root
                    $documentData = $didoxData;
                }
                
                // Get status from document object (prefer 'doc_status', fallback to 'status')
                $newStatus = null;
                if (isset($documentData['doc_status'])) {
                    $newStatus = (int)$documentData['doc_status'];
                } elseif (isset($documentData['status'])) {
                    $newStatus = (int)$documentData['status'];
                }
                
                $oldStatus = $model->didox_status;
                
                // Update model with latest DIDOX data
                if ($newStatus !== null) {
                    $model->didox_status = $newStatus;
                }
                $model->setDidoxData($didoxData);

                if ($model->save(false)) {
                    // Didox status code to label mapping
                    $didoxStatusLabels = [
                        0 => 'Draft (Черновик)',
                        1 => 'Signed by Seller (Подписан продавцом)',
                        2 => 'Signed by Both (Подписан обеими сторонами)',
                        3 => 'Rejected (Отклонен)',
                        4 => 'Cancelled (Отменен)',
                        10 => 'Sent (Отправлен)',
                        15 => 'Delivered (Доставлен)',
                        17 => 'Accepted (Принят)',
                        20 => 'Completed (Завершен)',
                        120 => 'Cancelled (Аннулирован)',
                    ];
                    
                    // Get raw status values from Didox response (cast to int for proper comparison)
                    $didoxStatus = isset($documentData['status']) ? (int)$documentData['status'] : null;
                    $didoxDocStatus = isset($documentData['doc_status']) ? (int)$documentData['doc_status'] : null;
                    $didoxStatusLabel = ($didoxStatus !== null && isset($didoxStatusLabels[$didoxStatus])) ? $didoxStatusLabels[$didoxStatus] : "Unknown ({$didoxStatus})";
                    
                    $statusChanged = ($oldStatus != $model->didox_status);
                    
                    // Build info message with Didox response details
                    $infoMessage = "<strong>DIDOX Response Status:</strong>";
                    $infoMessage .= "<br>&nbsp;&nbsp;• status: <code>{$didoxStatus}</code> - {$didoxStatusLabel}";
                    if ($didoxDocStatus !== null && $didoxDocStatus !== $didoxStatus) {
                        $infoMessage .= "<br>&nbsp;&nbsp;• doc_status: <code>{$didoxDocStatus}</code>";
                    }
                    
                    // Show local status update
                    $localStatusLabel = $model->getDidoxStatusLabel();
                    $infoMessage .= "<br><br><strong>Local Status:</strong> {$localStatusLabel} (Code: {$model->didox_status})";
                    if ($statusChanged) {
                        $infoMessage .= " <span class='label label-success'>Updated from {$oldStatus}</span>";
                    } else {
                        $infoMessage .= " <span class='label label-default'>No change</span>";
                    }
                    
                    // Get doc_id from document object
                    $docId = isset($documentData['doc_id']) ? $documentData['doc_id'] : (isset($documentData['_id']) ? $documentData['_id'] : null);
                    if ($docId) {
                        $infoMessage .= "<br><br><strong>Didox ID:</strong> {$docId}";
                    }
                    
                    // Get document name
                    if (isset($documentData['name'])) {
                        $infoMessage .= "<br><strong>Document Name:</strong> {$documentData['name']}";
                    }
                    
                    // Get doctype
                    if (isset($documentData['doctype'])) {
                        $infoMessage .= "<br><strong>Doc Type:</strong> {$documentData['doctype']}";
                    }
                    
                    // Get timestamp/updated from document object
                    $timestamp = isset($documentData['updated']) ? $documentData['updated'] : (isset($documentData['created']) ? $documentData['created'] : null);
                    if ($timestamp) {
                        $infoMessage .= "<br><strong>Last Updated:</strong> {$timestamp}";
                    }
                    
                    // Show signature info if document is signed
                    if (isset($documentData['signature']) && !empty($documentData['signature'])) {
                        $signatures = json_decode($documentData['signature'], true);
                        if (is_array($signatures) && count($signatures) > 0) {
                            $infoMessage .= "<br><br><strong>Signatures (" . count($signatures) . "):</strong>";
                            foreach ($signatures as $sig) {
                                $signerName = isset($sig['fullName']) ? $sig['fullName'] : (isset($sig['company']) ? $sig['company'] : 'Unknown');
                                $signerTin = isset($sig['taxId']) ? $sig['taxId'] : '';
                                $signingTime = isset($sig['signingTime']) ? $sig['signingTime'] : '';
                                $infoMessage .= "<br>&nbsp;&nbsp;• {$signerName}";
                                if ($signerTin) {
                                    $infoMessage .= " (TIN: {$signerTin})";
                                }
                                if ($signingTime) {
                                    $infoMessage .= "<br>&nbsp;&nbsp;&nbsp;&nbsp;Signed: {$signingTime}";
                                }
                            }
                        }
                    }
                    
                    $infoMessage .= "<br><br><small class='text-muted'>Token source: {$tokenSource}</small>";
                    
                    // Auto-download PDF after sync (silent - errors logged, doesn't break flow)
                    try {
                        $pdfResult = $model->downloadPdfFromDidox(['uz', 'ru']);
                        $pdfDownloaded = false;
                        foreach ($pdfResult as $lang => $res) {
                            if (isset($res['success']) && $res['success'] && empty($res['cached'])) {
                                $pdfDownloaded = true;
                            }
                        }
                        if ($pdfDownloaded) {
                            $infoMessage .= "<br><span class='label label-success'>PDF downloaded</span>";
                        }
                    } catch (\Exception $pdfEx) {
                        \app\models\Log::log('didox_pdf', "PDF download failed after sync for document #{$model->id}", $pdfEx->getMessage(), 'warning');
                    }
                    
                    Yii::$app->session->setFlash('info', "<strong>Sync Successful!</strong><br>{$infoMessage}");
                }
            } else {
                // If sync fails, show the error message from Didox with debug info
                $errorMessage = isset($result['error']) ? (is_array($result['error']) ? json_encode($result['error'], JSON_UNESCAPED_UNICODE) : $result['error']) : 'Unknown error';
                $httpCode = isset($result['httpCode']) ? $result['httpCode'] : 'N/A';
                
                $debugHtml = "<strong>Sync Failed!</strong><br>";
                $debugHtml .= "<strong>HTTP Code:</strong> {$httpCode}<br>";
                $debugHtml .= "<strong>Error:</strong> " . htmlspecialchars($errorMessage) . "<br>";
                $debugHtml .= "<strong>Token Source:</strong> " . ($userKey ? $tokenSource : 'none (empty token)') . "<br>";
                
                // Add debug info if available
                if (isset($result['debug'])) {
                    $debug = $result['debug'];
                    
                    if (isset($debug['request_url'])) {
                        $debugHtml .= "<br><strong>Request URL:</strong><br><code style='word-break:break-all;'>" . htmlspecialchars($debug['request_url']) . "</code>";
                    }
                    if (isset($debug['request_method'])) {
                        $debugHtml .= "<br><strong>Method:</strong> <code>" . htmlspecialchars($debug['request_method']) . "</code>";
                    }
                    if (isset($debug['request_body']) && $debug['request_body']) {
                        $debugHtml .= "<br><strong>Request Body:</strong><pre style='background:#f5f5f5;padding:5px;margin:5px 0;max-height:150px;overflow:auto;font-size:11px;word-break:break-all;'>" . htmlspecialchars($debug['request_body']) . "</pre>";
                    } else {
                        $debugHtml .= "<br><strong>Request Body:</strong> <em>empty (GET request)</em>";
                    }
                    if (isset($debug['response_raw'])) {
                        $responsePreview = $debug['response_raw'];
                        if (strlen($responsePreview) > 1000) {
                            $responsePreview = substr($responsePreview, 0, 1000) . '... [truncated]';
                        }
                        $debugHtml .= "<br><strong>Response Body:</strong><pre style='background:#fff3cd;padding:5px;margin:5px 0;max-height:200px;overflow:auto;font-size:11px;word-break:break-all;'>" . htmlspecialchars($responsePreview) . "</pre>";
                    }
                    
                    // Generate curl command for debugging
                    $curlCmd = $this->generateCurlCommand($debug, $userKey);
                    $debugHtml .= "<br><strong>cURL Command (for debugging):</strong><pre style='background:#e8f4e8;padding:8px;margin:5px 0;max-height:150px;overflow:auto;font-size:11px;word-break:break-all;white-space:pre-wrap;'>" . htmlspecialchars($curlCmd) . "</pre>";
                    $debugHtml .= "<button type='button' class='btn btn-xs btn-default' onclick='navigator.clipboard.writeText(this.previousElementSibling.textContent).then(function(){alert(\"Copied!\")});'><i class='fa fa-copy'></i> Copy cURL</button>";
                } else {
                    // No debug info available - show what we know with fallback curl
                    $partnerToken = isset(Yii::$app->params['didoxPartnerToken']) ? Yii::$app->params['didoxPartnerToken'] : '';
                    $baseUrl = YII_ENV_DEV ? 'https://stage.goodsign.biz' : 'https://api.goodsign.biz';
                    
                    $debugHtml .= "<br><strong>Request URL:</strong> <code>GET /v1/documents/{$model->didox_id}</code>";
                    $debugHtml .= "<br><strong>Request Body:</strong> <em>empty (GET request)</em>";
                    $debugHtml .= "<br><em>No detailed response body available.</em>";
                    
                    // Generate fallback curl command
                    $curlCmd = "curl -X GET \\\n";
                    $curlCmd .= "  \"{$baseUrl}/v1/documents/{$model->didox_id}\" \\\n";
                    $curlCmd .= "  -H \"Content-Type: application/json\" \\\n";
                    $curlCmd .= "  -H \"Partner-Authorization: {$partnerToken}\"";
                    if ($userKey) {
                        $curlCmd .= " \\\n  -H \"user-key: {$userKey}\"";
                    }
                    $debugHtml .= "<br><strong>cURL Command (for debugging):</strong><pre style='background:#e8f4e8;padding:8px;margin:5px 0;max-height:150px;overflow:auto;font-size:11px;word-break:break-all;white-space:pre-wrap;'>" . htmlspecialchars($curlCmd) . "</pre>";
                    $debugHtml .= "<button type='button' class='btn btn-xs btn-default' onclick='navigator.clipboard.writeText(this.previousElementSibling.textContent).then(function(){alert(\"Copied!\")});'><i class='fa fa-copy'></i> Copy cURL</button>";
                }
                
                Yii::$app->session->setFlash('error', $debugHtml);
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Download/View PDF of DIDOX document
     * @param int $id - Local document ID
     * @param string $lang - Language (uz, ru, en)
     * @param bool $refresh - Force refresh from Didox (ignore local cache)
     * @return mixed
     */
    public function actionPdf($id, $lang = 'uz', $refresh = false)
    {
        $model = $this->findModel($id);
        
        if (!$model->didox_id) {
            Yii::$app->session->setFlash('error', 'Document has no DIDOX ID. Please send to DIDOX first.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Validate language
        $lang = in_array($lang, ['uz', 'ru', 'en']) ? $lang : 'uz';

        try {
            // Check if PDF exists locally and refresh is not requested
            if (!$refresh && $model->hasPdf($lang)) {
                $fullPath = $model->getPdfFullPath($lang);
                
                $response = Yii::$app->response;
                $response->format = \yii\web\Response::FORMAT_RAW;
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'inline; filename="' . $model->didox_id . '_' . $lang . '.pdf"');
                $response->headers->set('Cache-Control', 'public, max-age=3600');
                $response->data = file_get_contents($fullPath);
                
                return $response;
            }

            // Fetch from Didox
            $didoxService = new DidoxService();
            
            // Get user-key from session first, fallback to DB setting
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            if (empty($userKey)) {
                $sysSettings = \app\models\Settings::find()
                    ->where(['type' => 'didox_eimzo_token'])
                    ->one();
                
                if ($sysSettings && !empty($sysSettings->content)) {
                    $userKey = $sysSettings->content;
                }
            }
            
            $result = $didoxService->getDocumentPdf($model->didox_id, $userKey, $lang);

            if ($result['success']) {
                // Save PDF locally for future API requests
                $model->savePdfLocally($result['data'], $lang);
                
                // Return PDF as response
                $response = Yii::$app->response;
                $response->format = \yii\web\Response::FORMAT_RAW;
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'inline; filename="' . $model->didox_id . '_' . $lang . '.pdf"');
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->data = $result['data'];
                
                return $response;
            } else {
                $errorMessage = isset($result['error']) ? $result['error'] : 'Unknown error';
                $debugInfo = '';
                
                if (isset($result['debug'])) {
                    $debugInfo = "<br><strong>Request URL:</strong> <code>" . htmlspecialchars($result['debug']['request_url']) . "</code>";
                    if (isset($result['debug']['response_raw'])) {
                        $debugInfo .= "<br><strong>Response:</strong> <pre style='background:#fff3cd;padding:5px;font-size:11px;'>" . htmlspecialchars($result['debug']['response_raw']) . "</pre>";
                    }
                }
                
                Yii::$app->session->setFlash('error', "<strong>Failed to get PDF!</strong><br>Error: {$errorMessage}{$debugInfo}");
                return $this->redirect(['view', 'id' => $id]);
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error getting PDF: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Download PDF of DIDOX document (force download)
     * @param int $id - Local document ID
     * @param string $lang - Language (uz, ru, en)
     * @param bool $refresh - Force refresh from Didox (ignore local cache)
     * @return mixed
     */
    public function actionDownloadPdf($id, $lang = 'uz', $refresh = false)
    {
        $model = $this->findModel($id);
        
        if (!$model->didox_id) {
            Yii::$app->session->setFlash('error', 'Document has no DIDOX ID. Please send to DIDOX first.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Validate language
        $lang = in_array($lang, ['uz', 'ru', 'en']) ? $lang : 'uz';

        try {
            // Check if PDF exists locally and refresh is not requested
            if (!$refresh && $model->hasPdf($lang)) {
                $fullPath = $model->getPdfFullPath($lang);
                
                $response = Yii::$app->response;
                $response->format = \yii\web\Response::FORMAT_RAW;
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $model->name . '_' . $lang . '.pdf"');
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->data = file_get_contents($fullPath);
                
                return $response;
            }

            // Fetch from Didox
            $didoxService = new DidoxService();
            
            // Get user-key from session first, fallback to DB setting
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            if (empty($userKey)) {
                $sysSettings = \app\models\Settings::find()
                    ->where(['type' => 'didox_eimzo_token'])
                    ->one();
                
                if ($sysSettings && !empty($sysSettings->content)) {
                    $userKey = $sysSettings->content;
                }
            }
            
            $result = $didoxService->getDocumentPdf($model->didox_id, $userKey, $lang);

            if ($result['success']) {
                // Save PDF locally for future API requests
                $model->savePdfLocally($result['data'], $lang);
                
                // Return PDF as download
                $response = Yii::$app->response;
                $response->format = \yii\web\Response::FORMAT_RAW;
                $response->headers->set('Content-Type', 'application/pdf');
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $model->name . '_' . $lang . '.pdf"');
                $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->data = $result['data'];
                
                return $response;
            } else {
                Yii::$app->session->setFlash('error', 'Failed to download PDF: ' . (isset($result['error']) ? $result['error'] : 'Unknown error'));
                return $this->redirect(['view', 'id' => $id]);
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error downloading PDF: ' . $e->getMessage());
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    /**
     * Force download PDFs for a document
     * @param int $id - Document ID
     * @return mixed
     */
    public function actionFetchPdf($id)
    {
        $model = $this->findModel($id);
        
        if (!$model->didox_id) {
            Yii::$app->session->setFlash('error', 'Document has no DIDOX ID. Please send to DIDOX first.');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $result = $model->downloadPdfFromDidox(['uz', 'ru']);
            
            $successLangs = [];
            $failedLangs = [];
            
            foreach ($result as $lang => $res) {
                if (isset($res['success']) && $res['success']) {
                    $successLangs[] = $lang . (isset($res['cached']) && $res['cached'] ? ' (cached)' : ' (downloaded)');
                } else {
                    $failedLangs[] = $lang . ': ' . (isset($res['error']) ? $res['error'] : 'Unknown error');
                }
            }
            
            if (!empty($successLangs)) {
                Yii::$app->session->setFlash('success', 'PDF fetched: ' . implode(', ', $successLangs));
            }
            if (!empty($failedLangs)) {
                Yii::$app->session->setFlash('warning', 'PDF fetch failed: ' . implode('; ', $failedLangs));
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error fetching PDF: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * E-IMZO authentication login page
     */
    public function actionLogin() {
        if ($this->restoreDidoxSessionFromStoredToken()) {
            return $this->redirect(['index']);
        }

        return $this->render('login');
    }

    /**
     * Handle E-IMZO authentication
     */
    public function actionAuthenticate() {
        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $post = Yii::$app->request->post();
        $taxId = isset($post['taxId']) ? $post['taxId'] : null;
        $signature = isset($post['signature']) ? $post['signature'] : null;

        if (!$taxId || !$signature) {
            return [
                'success' => false,
                'error' => 'Tax ID and signature are required'
            ];
        }

        try {
            $didoxService = new DidoxService();
            $result = $didoxService->authenticateWithEimzo($taxId, $signature);

            if ($result['success'] && isset($result['token'])) {
                // Store authentication in session
                $session = Yii::$app->session;
                $session->set('didox_authenticated', true);
                $session->set('didox_token', $result['token']);
                $session->set('didox_tax_id', $taxId);
                $session->set('didox_user_data', $result['data'] ?? []);

                // Update user's DIDOX token in database
                $this->user->eimzo_didox_token = $result['token'];
                $this->user->eimzo_tax_id = $taxId;
                $this->user->save(false);

                return [
                    'success' => true,
                    'message' => 'Authentication successful',
                    'redirect' => Yii::$app->urlManager->createUrl(['/admin/didox/index'])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => isset($result['error']) ? $result['error'] : 'Authentication failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Authentication error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Save token obtained from frontend DIDOX authentication
     */
    public function actionSaveToken() {
        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $post = Yii::$app->request->post();
        $taxId = isset($post['taxId']) ? $post['taxId'] : null;
        $token = isset($post['token']) ? $post['token'] : null;
        $connectionType = isset($post['connectionType']) ? $post['connectionType'] : 'fiz';
        $certificateInfo = isset($post['certificateInfo']) ? $post['certificateInfo'] : null;

        if (!$taxId || !$token) {
            return [
                'success' => false,
                'error' => 'Tax ID and token are required'
            ];
        }

        try {
            // Store authentication in session
            $session = Yii::$app->session;
            $session->set('didox_authenticated', true);
            $session->set('didox_token', $token);
            $session->set('didox_tax_id', $taxId);
            $session->set('didox_connection_type', $connectionType);
            $session->set('didox_user_data', []); // We don't have additional user data from frontend
            
            // Store certificate info if provided
            if ($certificateInfo) {
                $session->set('didox_certificate_info', $certificateInfo);
            }

            // Update user's DIDOX token in database
            $this->user->eimzo_didox_token = $token;
            $this->user->eimzo_tax_id = $taxId;
            $this->user->eimzo_last_login = date('Y-m-d H:i:s');
            $this->user->save(false);

            $message = $connectionType === 'yur' ? 'Company authentication successful' : 'Individual authentication successful';

            return [
                'success' => true,
                'message' => $message,
                'redirect' => Yii::$app->urlManager->createUrl(['/admin/didox/index'])
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error saving token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate with password via DIDOX API
     */
    public function actionAuthenticatePassword() {
        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $post = Yii::$app->request->post();
        $taxId = isset($post['taxId']) ? $post['taxId'] : null;
        $password = isset($post['password']) ? $post['password'] : null;
        $connectionType = isset($post['connectionType']) ? $post['connectionType'] : 'fiz';
        $companyTaxId = isset($post['companyTaxId']) ? $post['companyTaxId'] : null;

        if (!$taxId || !$password) {
            return [
                'success' => false,
                'error' => 'Tax ID and password are required'
            ];
        }

        try {
            $didoxService = new DidoxService();
            $finalToken = null;
            $finalTaxId = null;
            $finalMessage = '';
            $relatedCompanies = null;
            
            if ($connectionType === 'fiz') {
                // Direct individual authentication
                $authResult = $didoxService->authenticateWithPassword($taxId, $password);

                if (!$authResult['success']) {
                    return [
                        'success' => false,
                        'error' => $authResult['error']
                    ];
                }

                $finalToken = $authResult['token'];
                $finalTaxId = $taxId;
                $finalMessage = 'Individual authentication successful';
                $relatedCompanies = $authResult['related_companies'] ?? null;
                
            } else {
                // Company authentication - taxId is individual INN, companyTaxId is company INN
                if (!$companyTaxId) {
                    return [
                        'success' => false,
                        'error' => 'Company Tax ID is required for company login'
                    ];
                }
                
                // Step 1: Authenticate individual with password
                $authResult = $didoxService->authenticateWithPassword($taxId, $password);

                if (!$authResult['success']) {
                    return [
                        'success' => false,
                        'error' => $authResult['error']
                    ];
                }

                // Step 2: Login to company
                $companyLoginResult = $didoxService->loginToCompany($companyTaxId, $authResult['token']);
                
                if (!$companyLoginResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Individual authentication succeeded, but company login failed: ' . $companyLoginResult['error']
                    ];
                }
                
                $finalToken = $companyLoginResult['token'];
                $finalTaxId = $companyTaxId;
                $finalMessage = 'Company login successful';
                $relatedCompanies = $authResult['related_companies'] ?? null;
            }

            // Store authentication in session
            $session = Yii::$app->session;
            $session->set('didox_authenticated', true);
            $session->set('didox_token', $finalToken);
            $session->set('didox_tax_id', $finalTaxId);
            $session->set('didox_connection_type', $connectionType);
            $session->set('didox_auth_method', 'password');
            $session->set('didox_user_data', [
                'related_companies' => $relatedCompanies,
                'original_individual_taxid' => $taxId
            ]);

            // Update user's DIDOX token in database
            $this->user->eimzo_didox_token = $finalToken;
            $this->user->eimzo_tax_id = $finalTaxId;
            $this->user->eimzo_last_login = date('Y-m-d H:i:s');
            $this->user->save(false);

            return [
                'success' => true,
                'message' => $finalMessage,
                'redirect' => Yii::$app->urlManager->createUrl(['/admin/didox/index'])
            ];

        } catch (\Exception $e) {
            Yii::error('Password authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Authentication error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Authenticate into Didox using the configured server-side signer instead of local E-IMZO.
     */
    public function actionAuthenticateSigner() {
        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if ($this->restoreDidoxSessionFromStoredToken()) {
            return [
                'success' => true,
                'message' => 'Authenticated with stored Didox token',
                'redirect' => Yii::$app->urlManager->createUrl(['/admin/didox/index'])
            ];
        }

        $post = Yii::$app->request->post();
        $taxId = trim((string)($post['taxId'] ?? ''));
        $connectionType = trim((string)($post['connectionType'] ?? 'fiz'));

        if ($taxId === '') {
            return [
                'success' => false,
                'error' => 'Tax ID is required'
            ];
        }

        try {
            $didoxService = new DidoxService();
            $result = $connectionType === 'yur'
                ? $didoxService->authenticateCompanyWithConfiguredPfx($taxId)
                : $didoxService->authenticateWithConfiguredPfx($taxId);

            if (empty($result['success']) || empty($result['token'])) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Signer authentication failed'
                ];
            }

            $session = Yii::$app->session;
            $session->set('didox_authenticated', true);
            $session->set('didox_token', $result['token']);
            $session->set('didox_tax_id', $result['taxId'] ?? $taxId);
            $session->set('didox_connection_type', $connectionType);
            $session->set('didox_auth_method', 'signer');
            $session->set('didox_user_data', [
                'permissions' => $result['permissions'] ?? null,
                'individual_tax_id' => $result['individualTaxId'] ?? null,
            ]);

            $this->user->eimzo_didox_token = $result['token'];
            $this->user->eimzo_tax_id = $result['taxId'] ?? $taxId;
            $this->user->eimzo_last_login = date('Y-m-d H:i:s');
            $this->user->save(false);

            return [
                'success' => true,
                'message' => $connectionType === 'yur'
                    ? 'Company authentication successful via signer'
                    : 'Didox authentication successful via signer',
                'redirect' => Yii::$app->urlManager->createUrl(['/admin/didox/index'])
            ];
        } catch (\Throwable $e) {
            Yii::error('Signer authentication error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Authentication error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Login to company as individual person (for E-IMZO authentication)
     */
    public function actionLoginToCompany() {
        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $post = Yii::$app->request->post();
        $individualTaxId = isset($post['individualTaxId']) ? $post['individualTaxId'] : null;
        $individualToken = isset($post['individualToken']) ? $post['individualToken'] : null;
        $companyTaxId = isset($post['companyTaxId']) ? $post['companyTaxId'] : null;

        if (!$individualTaxId || !$individualToken || !$companyTaxId) {
            return [
                'success' => false,
                'error' => 'Individual Tax ID, individual token, and company Tax ID are required'
            ];
        }

        try {
            $didoxService = new DidoxService();
            
            // Login to company using individual's token
            $companyLoginResult = $didoxService->loginToCompany($companyTaxId, $individualToken);
            
            if (!$companyLoginResult['success']) {
                return [
                    'success' => false,
                    'error' => $companyLoginResult['error']
                ];
            }

            // Store authentication in session
            $session = Yii::$app->session;
            $session->set('didox_authenticated', true);
            $session->set('didox_token', $companyLoginResult['token']);
            $session->set('didox_tax_id', $companyTaxId);
            $session->set('didox_connection_type', 'yur');
            $session->set('didox_auth_method', 'eimzo');
            $session->set('didox_user_data', [
                'original_individual_taxid' => $individualTaxId,
                'permissions' => $companyLoginResult['permissions'] ?? null
            ]);

            // Update user's DIDOX token in database
            $this->user->eimzo_didox_token = $companyLoginResult['token'];
            $this->user->eimzo_tax_id = $companyTaxId;
            $this->user->eimzo_last_login = date('Y-m-d H:i:s');
            $this->user->save(false);

            return [
                'success' => true,
                'message' => 'Company authentication successful',
                'redirect' => Yii::$app->urlManager->createUrl(['/admin/didox/index'])
            ];

        } catch (\Exception $e) {
            Yii::error('Company login error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => 'Company login error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check authentication status
     */
    public function actionAuthStatus() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        return [
            'authenticated' => $this->isDidoxAuthenticated(),
            'taxId' => Yii::$app->session->get('didox_tax_id', null)
        ];
    }

    /**
     * Get user details by ID (for AJAX calls)
     */
    public function actionGetUserDetails($id) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $user = User::findOne($id);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found'
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'eimzo_tax_id' => $user->eimzo_tax_id,
            ]
        ];
    }

    /**
     * Generate contract preview HTML (same template as PDF)
     */
    public function actionPreviewContract()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $post = Yii::$app->request->post();
        
        // Create temporary models with posted data
        $arbitraryModel = new \app\models\didox\DidoxDocumentArbitrary();
        $arbitraryModel->load($post);
        
        $documentModel = new \app\models\didox\DidoxDocument();
        $documentModel->load($post);
        
        // Get order if specified
        $order = null;
        $orderId = $post['selected_order_id'] ?? null;
        if ($orderId) {
            $order = Order::find()
                ->with(['user', 'user.addresses', 'orderProducts', 'orderProducts.product', 'delivery'])
                ->where(['id' => $orderId])
                ->one();
        }
        
        try {
            // Use the same template as PDF generation
            $html = $this->renderPartial('@app/modules/admin/views/didox/_contract_template', [
                'arbitrary' => $arbitraryModel,
                'order' => $order,
                'document' => $documentModel,
            ]);
            
            return [
                'success' => true,
                'html' => $html
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Ошибка генерации предварительного просмотра: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retry DIDOX document creation
     */
    public function actionRetryDidox($id) {
        $model = $this->findModel($id);
        
        if (!$model->canRetryDidox()) {
            Yii::$app->session->setFlash('error', 'Document cannot be retried or is already in DIDOX');
            return $this->redirect(['view', 'id' => $id]);
        }

        if (!$this->isDidoxAuthenticated()) {
            Yii::$app->session->setFlash('error', 'DIDOX authentication required to retry submission');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Clear previous errors and retry
        $model->clearDidoxErrors();
        
        $didoxCreated = $this->createDidoxDocument($model);
        
        if ($didoxCreated) {
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Document successfully submitted to DIDOX platform');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Failed to submit document to DIDOX. Check error details below.');
            $model->save(false); // Save error data
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * E-IMZO logout
     */
    public function actionEimzoLogout() {
        $session = Yii::$app->session;
        $session->remove('didox_authenticated');
        $session->remove('didox_token');
        $session->remove('didox_tax_id');
        $session->remove('didox_user_data');

        Yii::$app->session->setFlash('info', 'Logged out from DIDOX successfully');
        return $this->redirect(['login']);
    }

    /**
     * ИКПУ Management - List linked product class codes
     */
    public function actionIkpu()
    {
        if (!$this->isDidoxAuthenticated()) {
            Yii::$app->session->setFlash('error', 'DIDOX authentication required');
            return $this->redirect(['login']);
        }

        $didoxService = new DidoxService();
        $session = Yii::$app->session;
        $userKey = $session->get('didox_token', '');

        // Get linked ИКПУ codes
        $linkedCodes = [];
        $linkedResult = $didoxService->getProfileProductClassCodes($userKey);
        if ($linkedResult['success']) {
            // DIDOX API returns paginated data with 'data' array containing the actual codes
            $responseData = $linkedResult['data'] ?? [];
            $linkedCodes = $responseData['data'] ?? $responseData ?? [];
        }

        // Handle search for available codes
        $searchResults = [];
        $searchQuery = Yii::$app->request->get('search', '');
        $page = (int)Yii::$app->request->get('page', 1);
        $lang = Yii::$app->request->get('lang', 'ru');

        if (!empty($searchQuery) || Yii::$app->request->get('show_all', false)) {
            $searchResult = $didoxService->searchProductClasses($page, $lang, $searchQuery, $userKey);
            if ($searchResult['success']) {
                // DIDOX API returns paginated data with 'data' array containing the actual results
                $responseData = $searchResult['data'] ?? [];
                $searchResults = $responseData['data'] ?? $responseData ?? [];
            }
        }

        return $this->render('ikpu', [
            'linkedCodes' => $linkedCodes,
            'searchResults' => $searchResults,
            'searchQuery' => $searchQuery,
            'currentPage' => $page,
            'language' => $lang
        ]);
    }

    /**
     * Add ИКПУ code to profile (AJAX)
     */
    public function actionIkpuAdd()
    {
        if (!$this->isDidoxAuthenticated()) {
            return $this->asJson(['success' => false, 'error' => 'Authentication required']);
        }

        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'error' => 'POST method required']);
        }

        $classCode = Yii::$app->request->post('classCode');
        $className = Yii::$app->request->post('className', '');
        
        if (empty($classCode)) {
            return $this->asJson(['success' => false, 'error' => 'Class code is required']);
        }

        $didoxService = new DidoxService();
        $session = Yii::$app->session;
        $userKey = $session->get('didox_token', '');

        $classData = [
            'classCode' => $classCode,
            'className' => $className
        ];

        $result = $didoxService->addProfileProductClass($classData, $userKey);

        if ($result['success']) {
            return $this->asJson([
                'success' => true, 
                'message' => 'ИКПУ код успешно добавлен к профилю'
            ]);
        } else {
            return $this->asJson([
                'success' => false, 
                'error' => $result['error'] ?? 'Ошибка добавления ИКПУ кода'
            ]);
        }
    }

    /**
     * Remove ИКПУ code from profile (AJAX)
     */
    public function actionIkpuRemove($classCode)
    {
        if (!$this->isDidoxAuthenticated()) {
            return $this->asJson(['success' => false, 'error' => 'Authentication required']);
        }

        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'error' => 'POST method required']);
        }

        if (empty($classCode)) {
            return $this->asJson(['success' => false, 'error' => 'Class code is required']);
        }

        $didoxService = new DidoxService();
        $session = Yii::$app->session;
        $userKey = $session->get('didox_token', '');

        $result = $didoxService->removeProfileProductClass($classCode, $userKey);

        if ($result['success']) {
            return $this->asJson([
                'success' => true, 
                'message' => 'ИКПУ код успешно удален из профиля'
            ]);
        } else {
            return $this->asJson([
                'success' => false, 
                'error' => $result['error'] ?? 'Ошибка удаления ИКПУ кода'
            ]);
        }
    }

    /**
     * Search ИКПУ codes (AJAX)
     */
    public function actionIkpuSearch()
    {
        if (!$this->isDidoxAuthenticated()) {
            return $this->asJson(['success' => false, 'error' => 'Authentication required']);
        }

        $searchQuery = Yii::$app->request->get('search', '');
        $page = (int)Yii::$app->request->get('page', 1);
        $lang = Yii::$app->request->get('lang', 'ru');

        $didoxService = new DidoxService();
        $session = Yii::$app->session;
        $userKey = $session->get('didox_token', '');

        $result = $didoxService->searchProductClasses($page, $lang, $searchQuery, $userKey);

        if ($result['success']) {
            // DIDOX API returns paginated data with 'data' array containing the actual results
            $responseData = $result['data'] ?? [];
            $searchData = $responseData['data'] ?? $responseData ?? [];
            
            return $this->asJson([
                'success' => true,
                'data' => $searchData
            ]);
        } else {
            return $this->asJson([
                'success' => false,
                'error' => $result['error'] ?? 'Ошибка поиска ИКПУ кодов'
            ]);
        }
    }

    /**
     * Check specific ИКПУ code status by number (AJAX)
     */
    public function actionIkpuCheck()
    {
        if (!$this->isDidoxAuthenticated()) {
            return $this->asJson(['success' => false, 'error' => 'Authentication required']);
        }

        $ikpuCode = Yii::$app->request->get('ikpuCode', '');
        $lang = Yii::$app->request->get('lang', 'ru');

        if (empty($ikpuCode)) {
            return $this->asJson(['success' => false, 'error' => 'ИКПУ код не указан']);
        }

        // Validate ИКПУ code format (17 digits)
        if (!preg_match('/^\d{17}$/', $ikpuCode)) {
            return $this->asJson(['success' => false, 'error' => 'Неверный формат ИКПУ кода. Должно быть 17 цифр.']);
        }

        $didoxService = new DidoxService();
        $session = Yii::$app->session;
        $userKey = $session->get('didox_token', '');

        $result = $didoxService->checkProductClassByCode($ikpuCode, $lang, $userKey);

        if ($result['success']) {
            return $this->asJson([
                'success' => true,
                'data' => $result['data']
            ]);
        } else {
            return $this->asJson([
                'success' => false,
                'error' => $result['error'] ?? 'Ошибка проверки ИКПУ кода'
            ]);
        }
    }

    /**
     * Get document data for signing
     */
    public function actionGetSignData($id) {
        if (!Yii::$app->request->isAjax) {
            throw new HttpException(404, 'Page not found');
        }

        $model = $this->findModel($id);
        
        if (!$model->isDidoxDocument()) {
            return json_encode(['success' => false, 'error' => 'Document not found']);
        }

        try {
            $didoxService = new DidoxService();
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            $result = $didoxService->getDocumentToSign($model->didox_id, 'accept', $userKey);

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => $result['success'],
                'data' => isset($result['data']) ? $result['data'] : null,
                'error' => isset($result['error']) ? $result['error'] : null
            ];
        } catch (\Exception $e) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Save document products from form submission
     * 
     * @param DidoxDocument $model
     * @return void
     */
    protected function saveDocumentProducts($model)
    {
        $productsData = Yii::$app->request->post('products', []);
        
        // Delete existing products for this document
        \app\models\didox\DidoxDocumentIncludedProducts::deleteAll(['document_id' => $model->id]);
        
        if (!empty($productsData)) {
            foreach ($productsData as $index => $productData) {
                // Skip empty products
                if (empty($productData['name']) || empty($productData['catalog_code'])) {
                    continue;
                }
                
                $product = new \app\models\didox\DidoxDocumentIncludedProducts();
                $product->document_id = $model->id;
                $product->ord_no = $index + 1;
                $product->name = $productData['name'] ?? '';
                $product->catalog_code = $productData['catalog_code'] ?? '';
                $product->catalog_name = $productData['catalog_name'] ?? '';
                $product->package_code = $productData['package_code'] ?? '';
                $product->package_name = $productData['package_name'] ?? '';
                $product->count = (float)($productData['count'] ?? 1);
                $product->summa = (float)($productData['summa'] ?? 0);
                $product->vat_rate = (float)($productData['vat_rate'] ?? 12);
                $product->origin = (int)($productData['origin'] ?? 4);
                $product->barcode = $productData['barcode'] ?? '';
                $product->marks = $productData['marks'] ?? '';
                
                // Calculate totals automatically (this is done in beforeSave)
                $product->save();
            }
            
            // Update invoice totals
            if ($model->invoice) {
                $model->invoice->calculateTotals();
                $model->invoice->save(false);
            }
        }
    }

    /**
     * Finds the DidoxDocument model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DidoxDocument the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DidoxDocument::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Get users with E-IMZO Tax ID for assignment
     */
    protected function getUsersWithEimzoTaxId()
    {
        try {
            $users = User::find()
                ->where(['!=', 'eimzo_tax_id', ''])
                ->andWhere(['is not', 'eimzo_tax_id', null])
                ->all();
                
            $result = ArrayHelper::map(
                $users,
                'id',
                function($model) {
                    $name = trim($model->name ?? '');
                    $phone = trim($model->phone ?? '');
                    $displayName = !empty($name) ? $name : (!empty($phone) ? $phone : "User #{$model->id}");
                    return '#' . $model->id. ': ' . $displayName . ' (' . $model->eimzo_tax_id . ') ' . $phone;
                }
            );
            
            return $result ?: []; // Always return array, never null
            
        } catch (\Exception $e) {
            Yii::error("Error in getUsersWithEimzoTaxId: " . $e->getMessage(), __METHOD__);
            return []; // Return empty array on error
        }
    }

    /**
     * Create document in DIDOX system
     */
    protected function createDidoxDocument($model, $invoiceModel = null, $arbitraryModel = null)
    {
        try {
            $didoxService = new DidoxService();
            
            // For invoice documents, use the invoice model to generate DIDOX JSON
            if ($model->isInvoice() && $invoiceModel) {
                $documentData = $invoiceModel->generateDidoxJson();
                // Ensure doctype is set from model
                $documentData['doctype'] = $model->didox_doc_type ?: '002';
            } elseif ($model->isArbitrary() && $arbitraryModel) {
                // For arbitrary documents, use the arbitrary model to generate DIDOX JSON
                $documentData = $arbitraryModel->generateDidoxApiStructure();
                // No need to set doctype for arbitrary documents (type 7)
            } else {
                // Fallback for non-invoice documents (future document types)
                $documentData = [
                    'doctype' => $model->didox_doc_type ?: '002', // Use model's doctype or default to invoice
                    'buyerTin' => $invoiceModel ? $invoiceModel->buyer_tin : '',
                    'sellerTin' => $invoiceModel ? $invoiceModel->seller_tin : '',
                    'name' => $model->name,
                    'docDate' => date('Y-m-d'),
                    'contractNumber' => $invoiceModel ? $invoiceModel->contract_number : '',
                    'contractDate' => $invoiceModel ? $invoiceModel->contract_date : date('Y-m-d'),
                    'totalSum' => $invoiceModel ? floatval($invoiceModel->total_sum) : 0,
                    'totalDeliverySum' => $invoiceModel ? floatval($invoiceModel->total_sum) : 0,
                    'totalVatSum' => $invoiceModel ? floatval($invoiceModel->total_vat_sum ?: 0) : 0,
                    'hasVat' => $invoiceModel ? (bool)$invoiceModel->has_vat : false,
                    'hasLgota' => $invoiceModel ? (bool)$invoiceModel->has_lgota : false,
                    'hasMarks' => $invoiceModel ? (bool)$invoiceModel->has_marking : false,
                    'oneside' => false
                ];
            }


            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            $result = $didoxService->createDocument($documentData, $userKey);

            if ($result['success']) {
                // Extract and set DIDOX document ID from response using helper method
                $model->extractAndSetDidoxDocumentId($result['data']);
                
                $model->didox_status = 0; // Draft
                $model->didox_created_at = date('Y-m-d H:i:s');
                $model->didox_user_key = $userKey;
                
                // Save the complete DIDOX API response for future reference
                $model->setDidoxData($result['data']);
                
                // Clear any previous errors
                $model->clearDidoxErrors();
                
                // Log successful creation for debugging
                Yii::info("DIDOX document created successfully. Local ID: {$model->id}, DIDOX ID: {$model->didox_id}", __METHOD__);
                
                return true;
            } else {
                // Store detailed error information
                $errorData = [
                    'error' => isset($result['error']) ? $result['error'] : 'Unknown error',
                    'full_response' => $result,
                    'sent_data' => $documentData,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_key' => $userKey
                ];
                $model->setDidoxErrorData($errorData);
                
                Yii::$app->session->setFlash('error', 'Failed to create DIDOX document: ' . (isset($result['error']) ? $result['error'] : 'Unknown error'));
            }
        } catch (\Exception $e) {
            // Store exception details
            $errorData = [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $model->setDidoxErrorData($errorData);
            
            Yii::$app->session->setFlash('error', 'Error: ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Update document in DIDOX system
     */
    protected function updateDidoxDocument($model, $invoiceModel = null, $arbitraryModel = null)
    {
        try {
            $didoxService = new DidoxService();
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            // For invoice documents, use the invoice model to generate DIDOX JSON
            if ($model->isInvoice() && $invoiceModel) {
                $documentData = $invoiceModel->generateDidoxJson();
                // Ensure doctype is set from model
                $documentData['doctype'] = $model->didox_doc_type ?: '002';
            } elseif ($model->isArbitrary() && $arbitraryModel) {
                // For arbitrary documents, use the arbitrary model to generate DIDOX JSON
                $documentData = $arbitraryModel->generateDidoxJson();
                // Ensure doctype is set from model
                $documentData['doctype'] = $model->didox_doc_type ?: '000';
            } else {
                // Fallback for documents without proper models
                $documentData = [
                    'doctype' => $model->didox_doc_type ?: ($model->isArbitrary() ? '000' : '002'),
                    'buyerTin' => $invoiceModel ? $invoiceModel->buyer_tin : ($arbitraryModel ? $arbitraryModel->buyer_tin : ''),
                    'sellerTin' => $invoiceModel ? $invoiceModel->seller_tin : ($arbitraryModel ? $arbitraryModel->seller_tin : ''),
                    'name' => $model->name,
                    'docDate' => date('Y-m-d'),
                    'contractNumber' => $invoiceModel ? $invoiceModel->contract_number : ($arbitraryModel ? $arbitraryModel->contract_number : 'No contract'),
                    'contractDate' => $invoiceModel ? $invoiceModel->contract_date : ($arbitraryModel ? $arbitraryModel->contract_date : date('Y-m-d')),
                    'totalSum' => $invoiceModel ? floatval($invoiceModel->total_sum) : ($arbitraryModel ? floatval($arbitraryModel->total_sum) : 0),
                    'totalDeliverySum' => $invoiceModel ? floatval($invoiceModel->total_sum) : ($arbitraryModel ? floatval($arbitraryModel->total_sum) : 0),
                    'totalVatSum' => $invoiceModel ? floatval($invoiceModel->total_vat_sum ?: 0) : 0,
                    'hasVat' => $invoiceModel ? (bool)$invoiceModel->has_vat : false,
                    'hasLgota' => $invoiceModel ? (bool)$invoiceModel->has_lgota : false,
                    'hasMarks' => $invoiceModel ? (bool)$invoiceModel->has_marking : false,
                    'oneside' => false,
                    'items' => [[
                        'name' => $model->name,
                        'measureId' => '796', // Pieces
                        'count' => 1,
                        'price' => $invoiceModel ? floatval($invoiceModel->total_sum) : ($arbitraryModel ? floatval($arbitraryModel->total_sum) : 0),
                        'sum' => $invoiceModel ? floatval($invoiceModel->total_sum) : ($arbitraryModel ? floatval($arbitraryModel->total_sum) : 0),
                        'vatPercent' => 0,
                        'vatSum' => $invoiceModel ? floatval($invoiceModel->total_vat_sum ?: 0) : 0
                    ]]
                ];
            }
            
            // Try to update the document in DIDOX
            $result = $didoxService->updateDocument($model->didox_id, $documentData, $userKey);

            if ($result['success']) {
                // Update successful - sync latest data and extract document ID if present
                $model->extractAndSetDidoxDocumentId($result['data']);
                
                // Save the complete DIDOX API response
                $model->setDidoxData($result['data']);
                $model->clearDidoxErrors();
                
                // Log successful update for debugging
                Yii::info("DIDOX document updated successfully. Local ID: {$model->id}, DIDOX ID: {$model->didox_id}", __METHOD__);
                
                return true;
            } else {
                // Update failed - store error details and try to sync current status
                $errorData = [
                    'error' => isset($result['error']) ? $result['error'] : 'Update failed',
                    'full_response' => $result,
                    'sent_data' => $documentData,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_key' => $userKey,
                    'operation' => 'update'
                ];
                $model->setDidoxErrorData($errorData);
                
                // Try to sync current document status anyway
                $syncResult = $didoxService->getDocument($model->didox_id, $userKey);
                if ($syncResult['success']) {
                    $didoxData = $syncResult['data'];
                    $model->didox_status = isset($didoxData['doc_status']) ? $didoxData['doc_status'] : $model->didox_status;
                    $model->setDidoxData($didoxData);
                }
                
                return false;
            }
            
        } catch (\Exception $e) {
            // Store exception details
            $errorData = [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s'),
                'operation' => 'update'
            ];
            $model->setDidoxErrorData($errorData);
            
            Yii::$app->session->setFlash('warning', 'Could not update document in DIDOX: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel document in DIDOX system
     */
    protected function cancelDidoxDocument($model)
    {
        try {
            $didoxService = new DidoxService();
            $session = Yii::$app->session;
            $userKey = $session->get('didox_token', '');
            
            $result = $didoxService->cancelDocument($model->didox_id, $userKey);

            if ($result['success']) {
                $model->didox_status = 120; // Canceled
                $model->setDidoxData(array_merge($model->getDidoxDataArray(), $result['data']));
                $model->save(false);
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('warning', 'Could not cancel in DIDOX: ' . $e->getMessage());
        }
    }

    /**
     * Generate curl command from debug info for debugging purposes
     * @param array $debug - Debug info from makeRequestWithHeaders
     * @param string $userKey - User key for authentication
     * @return string - Curl command
     */
    protected function generateCurlCommand($debug, $userKey = '')
    {
        $method = isset($debug['request_method']) ? $debug['request_method'] : 'GET';
        $url = isset($debug['request_url']) ? $debug['request_url'] : '';
        $body = isset($debug['request_body']) ? $debug['request_body'] : null;
        $headers = isset($debug['request_headers']) ? $debug['request_headers'] : [];
        
        // Get partner token for header
        $partnerToken = isset(Yii::$app->params['didoxPartnerToken']) ? Yii::$app->params['didoxPartnerToken'] : '';
        
        $curl = "curl -X {$method} \\\n";
        $curl .= "  \"{$url}\" \\\n";
        $curl .= "  -H \"Content-Type: application/json\" \\\n";
        $curl .= "  -H \"Partner-Authorization: {$partnerToken}\"";
        
        if ($userKey) {
            $curl .= " \\\n  -H \"user-key: {$userKey}\"";
        }
        
        if ($body && $method !== 'GET') {
            // Escape single quotes in body for shell
            $escapedBody = str_replace("'", "'\\''", $body);
            $curl .= " \\\n  -d '{$escapedBody}'";
        }
        
        return $curl;
    }
} 
