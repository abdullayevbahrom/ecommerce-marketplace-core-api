<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;

use app\models\Settings;
use app\models\user\User;
use app\models\Category;

class SettingsController extends Controller{
    public $user;

    public function beforeAction($action) {
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

            if (!in_array('settings', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionLogo() {
        $model = Settings::find()->with('image')->where(['type'=>'logo'])->one();

        if (!$model) {
            $model = new Settings;
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveLogo()) {
                Yii::$app->session->setFlash('logo_saved', 'Saved');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        return $this->render('logo', [
            'model' => $model
        ]);
    }

    public function actionCallCenter() {
        $model = Settings::findOne(['type'=>'call_center']);
        if (!$model) {
            $model = new Settings;
        }


        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->type = 'call_center';
            if ($model->save()) {
                Yii::$app->session->setFlash('call_center_saved', 'Saved');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        return $this->render('call_center', [
            'model' => $model
        ]);
    }

    public function actionDidox() {
        $didoxService = new \app\services\DidoxService();
        $pfxValidation = $didoxService->validateConfiguredPfx();
        $currentTokenStatus = $didoxService->getTokenStatus();
        if (
            !$pfxValidation['success']
            && in_array(($currentTokenStatus['status'] ?? 'manual'), ['active', 'failed'], true)
        ) {
            $didoxService->cleanupInvalidAutoRefreshState($pfxValidation['error']);
        }

        // Handle manual token refresh
        if (Yii::$app->request->post('refresh_token')) {
            $result = $didoxService->refreshAndStoreToken();
            
            if ($result['success']) {
                Yii::$app->session->setFlash('didox_saved', 'Token refreshed successfully! Expires at: ' . $result['expires_at']);
            } elseif (!empty($result['skipped'])) {
                Yii::$app->session->setFlash('didox_saved', $result['error']);
            } else {
                Yii::$app->session->setFlash('error', 'Token refresh failed: ' . $result['error']);
            }
            return $this->redirect(['didox']);
        }
        
        // Handle toggle auto-refresh status
        $toggleAuto = Yii::$app->request->post('toggle_auto');
        if ($toggleAuto) {
            if ($toggleAuto === 'enable') {
                if (!$pfxValidation['success']) {
                    $message = $didoxService->cleanupInvalidAutoRefreshState($pfxValidation['error']);
                    Yii::$app->session->setFlash('didox_saved', $message);
                    return $this->redirect(['didox']);
                }
            }

            $newStatus = $toggleAuto === 'enable' ? 'active' : 'disabled';
            $model = \app\models\Settings::findOne(['type' => 'didox_auto_refresh_status']);
            if (!$model) {
                $model = new \app\models\Settings();
                $model->type = 'didox_auto_refresh_status';
            }
            $model->content = $newStatus;
            $model->date = date('Y-m-d H:i:s');
            if ($model->save()) {
                Yii::$app->session->setFlash('didox_saved', 'Auto-refresh ' . ($toggleAuto === 'enable' ? 'enabled' : 'disabled'));
            } else {
                Yii::$app->session->setFlash('error', 'Failed to update auto-refresh status');
            }
            return $this->redirect(['didox']);
        }
        
        // Update types array to include auto-refresh fields
        $types = [
            'didox_seller_inn', 
            'didox_seller_name', 
            'didox_seller_address', 
            'didox_seller_account', 
            'didox_seller_mfo',
            'didox_seller_vat_reg_code',
            'didox_eimzo_tax_id',
            'didox_eimzo_token',
            'didox_eimzo_last_login',
            'didox_eimzo_certificate',
            'didox_pfx_path',
            'didox_pfx_password',
            'didox_signer_url',
            'didox_token_expires_at',
            'didox_auto_refresh_status',
            'didox_auto_refresh_error',
            'didox_auto_refresh_last_attempt'
        ];
        
        $settings = Settings::find()->where(['type' => $types])->all();
        $models = [];
        
        // Initialize models for all types, ensuring they exist
        foreach ($types as $type) {
            $found = false;
            foreach ($settings as $setting) {
                if ($setting->type === $type) {
                    $models[$type] = $setting;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $models[$type] = new Settings(['type' => $type]);
            }
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post('Settings');
            
            // Handle PFX file upload
            $uploadedFile = \yii\web\UploadedFile::getInstanceByName('didox_pfx_file');
            if ($uploadedFile) {
                $uploadDir = Yii::getAlias('@app/keys');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $filename = 'key_' . date('Ymd_His') . '.' . $uploadedFile->extension;
                $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                
                if ($uploadedFile->saveAs($filePath)) {
                    if (isset($models['didox_pfx_path'])) {
                        $models['didox_pfx_path']->content = $filename;
                        $models['didox_pfx_path']->date = date('Y-m-d H:i:s');
                        $models['didox_pfx_path']->save();
                    } else {
                         // Should exist from init loop, but just in case
                         $models['didox_pfx_path'] = new Settings(['type' => 'didox_pfx_path']);
                         $models['didox_pfx_path']->content = $filename;
                         $models['didox_pfx_path']->date = date('Y-m-d H:i:s');
                         $models['didox_pfx_path']->save();
                    }
                    Yii::$app->session->setFlash('pfx_saved', 'PFX Key uploaded successfully.');
                }
            }

            // Handle "Copy from Session" action
            if (Yii::$app->request->post('copy_from_session')) {
                // ... (existing code) ...
                $session = Yii::$app->session;
                
                // If authenticated in session, copy values
                if ($session->get('didox_authenticated')) {
                    $token = $session->get('didox_token');
                    $updates = [
                        'didox_eimzo_tax_id' => $session->get('didox_tax_id'),
                        'didox_eimzo_token' => $token,
                        'didox_eimzo_last_login' => date('Y-m-d H:i:s'),
                        // Certificate info might be an array, encode it
                        'didox_eimzo_certificate' => is_array($session->get('didox_certificate_info')) 
                            ? json_encode($session->get('didox_certificate_info')) 
                            : ($session->get('didox_certificate_info') ?: '')
                    ];

                    // Fetch Profile for Seller Info
                    try {
                        $didoxService = new \app\services\DidoxService();
                        $profile = $didoxService->getUserProfile($token);
                        
                        if ($profile['success'] && !empty($profile['data'])) {
                            $data = $profile['data'];
                            $updates['didox_seller_inn'] = $data['tin'] ?? $updates['didox_eimzo_tax_id'];
                            // Try multiple keys for name as API response format might vary
                            $updates['didox_seller_name'] = $data['name'] ?? $data['companyName'] ?? $data['owner'] ?? '';
                            $updates['didox_seller_address'] = $data['address'] ?? '';
                            // Note: Account/MFO are usually not provided in the basic profile endpoint or vary, 
                            // so we preserve existing values or manual entry.
                        } else {
                            // If profile fetch fails but we are authenticated, at least sync the Tax ID
                            $updates['didox_seller_inn'] = $updates['didox_eimzo_tax_id'];
                        }
                    } catch (\Exception $e) {
                        Yii::warning("Failed to fetch Didox profile during settings copy: " . $e->getMessage());
                    }
                    
                    foreach ($updates as $type => $value) {
                        if (isset($models[$type])) {
                            $model = $models[$type];
                            $model->content = (string)$value;
                            $model->date = date('Y-m-d H:i:s');
                            $model->save();
                        }
                    }
                    Yii::$app->session->setFlash('didox_saved', 'Credentials and Seller Info copied from current session successfully.');
                    return $this->redirect(['didox']);
                } else {
                    Yii::$app->session->setFlash('error', 'No active Didox session found.');
                }
            } 
            // Handle regular form save
            elseif ($post && is_array($post)) {
                $saved = true;
                foreach ($post as $type => $data) {
                    if ($type == 'didox_pfx_path') continue; // Skip path update from text input if any, handled by upload usually or separate display
                    
                    if (isset($models[$type])) {
                        $models[$type]->content = $data['content'];
                        $models[$type]->date = date('Y-m-d H:i:s');
                        if (!$models[$type]->save()) {
                            $saved = false;
                        }
                    }
                }
                
                if ($saved) {
                    Yii::$app->session->setFlash('didox_saved', 'Settings saved successfully.');
                    return $this->redirect(['didox']);
                }
            }
        }

        return $this->render('didox', [
            'models' => $models,
            'pfxValidation' => $pfxValidation,
        ]);
    }
}
