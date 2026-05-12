<?php

namespace app\modules\api\controllers;

use app\models\didox\DidoxDocument;
use app\models\didox\DidoxDocumentSignature;
use app\services\DidoxDocFlowService;
use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;

class DidoxDocFlowController extends Controller
{
    public function beforeAction($action)
    {
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

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['options', 'login-eimzo', 'incoming-tosign', 'incoming-accept', 'incoming-documents'],
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ],
        ];

        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }

    public function actionLoginEimzo()
    {
        $taxId = (string)\Yii::$app->request->post('taxId', '');
        $signature = (string)\Yii::$app->request->post('signature', '');

        $service = new DidoxDocFlowService();
        $result = $service->loginWithEimzo($taxId, $signature);
        if (empty($result['success'])) {
            throw new HttpException(422, $result['error'] ?? 'DIDOX login failed');
        }

        return $result;
    }

    public function actionIncomingTosign()
    {
        $didoxId = (string)\Yii::$app->request->post('didox_id', '');
        $userKey = (string)\Yii::$app->request->post('user_key', '');

        $service = new DidoxDocFlowService();
        $result = $service->getIncomingToSign($didoxId, $userKey);
        if (empty($result['success'])) {
            $msg = $result['error'] ?? 'Failed to get incoming payload';
            if (!empty($result['debug'])) {
                $msg .= ' | debug: ' . json_encode($result['debug'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            throw new HttpException(422, $msg);
        }

        return $result;
    }

    public function actionIncomingDocuments()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        $page = (int)Yii::$app->request->get('page', 1);
        $limit = (int)Yii::$app->request->get('limit', 20);
        $offset = ($page - 1) * $limit;

        $query = DidoxDocument::find()
            ->alias('d')
            ->where(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
            ->andWhere(['d.didox_status' => DidoxDocument::STATUS_WAITING_PARTNER_SIGNATURE])
            // Incoming accept flow should only show docs explicitly assigned to signer.
            ->andWhere(['d.to_user_id' => $user->id])
            ->orderBy(['d.created_at' => SORT_DESC]);

        $total = (clone $query)->count();
        $documents = $query->limit($limit)->offset($offset)->all();

        $baseUrl = rtrim((string)(Yii::$app->params['baseUrl'] ?? ''), '/');
        $rows = [];
        foreach ($documents as $doc) {
            $row = $doc->toArray();
            $row['didox_status_label'] = $doc->getDidoxStatusLabel();
            $row['document_type_label'] = $doc->getDocumentTypeLabel();
            if ($doc->didox_id && $baseUrl !== '') {
                $row['pdf_urls'] = [
                    'uz' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $doc->didox_id . '&lang=uz',
                    'ru' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $doc->didox_id . '&lang=ru',
                ];
                $row['pdf_cached'] = [
                    'uz' => $doc->hasPdf('uz'),
                    'ru' => $doc->hasPdf('ru'),
                ];
            } else {
                $row['pdf_urls'] = null;
                $row['pdf_cached'] = null;
            }
            $rows[] = $row;
        }

        return [
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / max(1, $limit)),
            ],
            'filters' => [
                'didox_status' => DidoxDocument::STATUS_WAITING_PARTNER_SIGNATURE,
                'didox_status_label' => 'Ожидает подписи партнера',
            ],
        ];
    }

    public function actionIncomingAccept()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        $didoxId = (string)\Yii::$app->request->post('didox_id', '');
        $userKey = (string)\Yii::$app->request->post('user_key', '');
        $signature1 = (string)\Yii::$app->request->post('signature1', '');
        $signature2 = (string)\Yii::$app->request->post('signature2', '');
        $signature1Candidates = \Yii::$app->request->post('signature1_candidates', []);
        $signature2Candidates = \Yii::$app->request->post('signature2_candidates', []);
        if (!is_array($signature1Candidates)) {
            $signature1Candidates = [];
        }
        if (!is_array($signature2Candidates)) {
            $signature2Candidates = [];
        }
        $signature = (string)\Yii::$app->request->post('signature', '');
        $signatureCandidates = \Yii::$app->request->post('signature_candidates', []);
        if (!is_array($signatureCandidates)) {
            $signatureCandidates = [];
        }

        $document = DidoxDocument::find()
            ->alias('d')
            ->where(['d.didox_id' => $didoxId])
            ->andWhere(['d.status' => DidoxDocument::LOCAL_STATUS_ACTIVE])
            ->andWhere(['d.to_user_id' => $user->id])
            ->one();

        if (!$document) {
            throw new HttpException(404, 'Document not found or not assigned to current signer (to_user_id mismatch)');
        }

        $service = new DidoxDocFlowService();
        $flowMode = 'join';
        if (trim($signature1) === '' || trim($signature2) === '') {
            throw new HttpException(422, 'Single signature mode is not supported for incoming accept. Use join flow with signature1 and signature2.');
        }
        $result = $service->acceptIncomingByJoin($didoxId, $signature1, $signature2, $userKey, $signature2Candidates, $signature1Candidates);
        if (empty($result['success'])) {
            $msg = $result['error'] ?? 'Failed to accept incoming document';
            if (!empty($result['debug'])) {
                $msg .= ' | debug: ' . json_encode($result['debug'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            throw new HttpException(422, $msg);
        }

        // Keep local DB in sync after successful incoming accept.
        if ($document) {
            $document->didox_status = DidoxDocument::STATUS_SIGNED;
            $document->didox_signed_at = date('Y-m-d H:i:s');

            $existingData = $document->getDidoxDataArray();
            $incomingData = is_array($result['data'] ?? null) ? $result['data'] : [];
            $document->setDidoxData(array_merge($existingData, $incomingData));
            $document->save(false);

            $signatureForAudit = trim($signature);
            if ($signatureForAudit === '') {
                $signatureForAudit = trim($signature2) !== '' ? trim($signature2) : trim($signature1);
            }
            try {
                DidoxDocumentSignature::createFromAccept(
                    $document,
                    $user,
                    $signatureForAudit,
                    $incomingData
                );
            } catch (\Throwable $e) {
                // Keep accept flow successful even if audit table is missing in current environment.
                Yii::warning(
                    'Skipping didox_document_signatures write: ' . $e->getMessage(),
                    __METHOD__
                );
            }
        }

        if (!isset($result['debug']) || !is_array($result['debug'])) {
            $result['debug'] = [];
        }
        $result['debug']['flow_mode'] = $flowMode;
        return $result;
    }
}
