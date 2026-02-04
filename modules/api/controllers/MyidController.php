<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use app\models\user\User;
use app\models\user\UserMyid;
use app\services\MyidService;

/**
 * MyID API Controller
 * Handles MyID verification for mobile SDK and web SDK flows
 */
class MyidController extends Controller
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
            'class' => HttpBearerAuth::className(),
            'optional' => ['options', 'init-web', 'callback', 'verify', 'register'] // Allow unauthenticated access for registration
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
     * Handle CORS preflight requests
     */
    public function actionOptions()
    {
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [];
    }

    /**
     * Verify user with MyID (Mobile SDK flow)
     * 
     * POST /api/myid/verify
     * Body: {
     *   "code": "auth_code_from_sdk",
     *   "register": false // true to create new user if not exists
     * }
     * 
     * @return array
     * @throws HttpException
     */
    public function actionVerify()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        $post = Yii::$app->request->post();
        $code = $post['code'] ?? null;
        $registerNew = $post['register'] ?? false;

        if (empty($code)) {
            throw new HttpException(400, 'Authorization code is required');
        }

        try {
            $myidService = new MyidService();
            
            // Exchange code for access token
            $tokenResult = $myidService->exchangeCodeForToken($code);
            if (!$tokenResult['success']) {
                throw new HttpException(401, 'Failed to exchange code: ' . ($tokenResult['error'] ?? 'Unknown error'));
            }

            $accessToken = $tokenResult['access_token'];

            // Get user data from MyID
            $userDataResult = $myidService->getUserData($accessToken);
            if (!$userDataResult['success']) {
                throw new HttpException(502, 'Failed to get user data from MyID: ' . ($userDataResult['error'] ?? 'Unknown error'));
            }

            $myidData = $userDataResult['data'];
            $pinfl = $myidData['pinfl'] ?? null;

            if (empty($pinfl)) {
                throw new HttpException(502, 'MyID response missing PINFL');
            }

            // Check if PINFL already exists
            $existingMyid = UserMyid::findByPinfl($pinfl);
            
            // Get current authenticated user (if any)
            $currentUser = Yii::$app->user->identity;
            $userId = $currentUser ? $currentUser->id : null;

            // If PINFL exists and linked to another user
            if ($existingMyid && $existingMyid->user_id && $userId && $existingMyid->user_id !== $userId) {
                throw new HttpException(409, 'This PINFL is already linked to another account');
            }

            // If no authenticated user and not registering, check if PINFL has linked user
            if (!$userId && !$registerNew) {
                if ($existingMyid && $existingMyid->user_id) {
                    // Return existing user
                    $user = $existingMyid->user;
                    if ($user) {
                        return [
                            'success' => true,
                            'message' => 'User found by PINFL',
                            'user' => [
                                'id' => $user->id,
                                'token' => $user->token,
                                'name' => $user->name,
                                'phone' => $user->phone,
                                'myid_verified' => 1,
                            ],
                            'verification' => $existingMyid->toApiArray(),
                            'is_new_user' => false,
                        ];
                    }
                }
                throw new HttpException(404, 'No user linked to this PINFL. Set register=true to create new user.');
            }

            // Register new user if requested
            if (!$userId && $registerNew) {
                $user = new User();
                $user->scenario = User::USER_SIGNUP;
                $user->role = User::ROLE_USER;
                $user->status = 1;
                $user->token = Yii::$app->security->generateRandomString(32);
                $user->name = trim(($myidData['first_name'] ?? '') . ' ' . ($myidData['last_name'] ?? ''));
                $user->myid_verified = 1;
                
                // Generate unique phone placeholder if not provided
                $user->phone = 'myid_' . $pinfl;
                
                if (!$user->save(false)) {
                    Yii::error('Failed to create user from MyID: ' . json_encode($user->errors), __METHOD__);
                    throw new HttpException(500, 'Failed to create user');
                }
                
                $userId = $user->id;
            }

            // Create or update MyID verification record
            $verification = UserMyid::createFromMyidData($myidData, $userId);
            if (!$verification) {
                throw new HttpException(500, 'Failed to save verification data');
            }

            // Get user for response
            $user = $userId ? User::findOne($userId) : null;

            return [
                'success' => true,
                'message' => 'Verification successful',
                'user' => $user ? [
                    'id' => $user->id,
                    'token' => $user->token,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'myid_verified' => $user->myid_verified,
                ] : null,
                'verification' => $verification->toApiArray(),
                'is_new_user' => $registerNew && !$existingMyid,
            ];

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('MyID verify error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Initialize web SDK flow
     * 
     * GET /api/myid/init-web
     * Query: redirect_uri (optional)
     * 
     * @return array
     * @throws HttpException
     */
    public function actionInitWeb()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $myidService = new MyidService();
            
            $redirectUri = Yii::$app->request->get('redirect_uri');
            $result = $myidService->getWebAuthUrl($redirectUri);

            if (!$result['success']) {
                throw new HttpException(500, 'Failed to generate auth URL: ' . ($result['error'] ?? 'Unknown error'));
            }

            // Store state in session for validation
            $session = Yii::$app->session;
            $session->set('myid_state', $result['state']);
            
            // Store current user ID if authenticated
            $currentUser = Yii::$app->user->identity;
            if ($currentUser) {
                $session->set('myid_user_id', $currentUser->id);
            }

            return [
                'success' => true,
                'auth_url' => $result['auth_url'],
                'state' => $result['state'],
            ];

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('MyID init-web error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to initialize web auth');
        }
    }

    /**
     * Handle web SDK callback
     * 
     * POST /api/myid/callback
     * Body: {
     *   "code": "callback_code",
     *   "state": "state_token"
     * }
     * 
     * @return array
     * @throws HttpException
     */
    public function actionCallback()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        $post = Yii::$app->request->post();
        $code = $post['code'] ?? Yii::$app->request->get('code');
        $state = $post['state'] ?? Yii::$app->request->get('state');

        if (empty($code)) {
            throw new HttpException(400, 'Authorization code is required');
        }

        // Validate state
        $session = Yii::$app->session;
        $storedState = $session->get('myid_state');
        
        if ($state && $storedState && $state !== $storedState) {
            throw new HttpException(400, 'Invalid state parameter');
        }

        // Get stored user ID
        $userId = $session->get('myid_user_id');

        try {
            $myidService = new MyidService();
            
            // Complete verification
            $result = $myidService->verifyAndSaveUser($code, $userId);

            if (!$result['success']) {
                throw new HttpException(500, 'Verification failed: ' . ($result['error'] ?? 'Unknown error'));
            }

            // Clear session data
            $session->remove('myid_state');
            $session->remove('myid_user_id');

            // Get user for response
            $user = $result['user_id'] ? User::findOne($result['user_id']) : null;
            $verification = $result['verification'];

            return [
                'success' => true,
                'message' => 'Verification successful',
                'user' => $user ? [
                    'id' => $user->id,
                    'token' => $user->token,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'myid_verified' => $user->myid_verified,
                ] : null,
                'verification' => $verification ? $verification->toApiArray() : null,
            ];

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('MyID callback error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Callback processing failed');
        }
    }

    /**
     * Get current user's MyID verification status
     * 
     * GET /api/myid/status
     * Authorization: Bearer {user_token}
     * 
     * @return array
     * @throws HttpException
     */
    public function actionStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        
        if (!$user) {
            throw new HttpException(401, 'Authentication required');
        }

        try {
            $verification = UserMyid::findByUserId($user->id);

            if (!$verification) {
                return [
                    'verified' => false,
                    'message' => 'User not verified with MyID',
                ];
            }

            return [
                'verified' => $verification->isVerified(),
                'verified_at' => $verification->verified_at,
                'pinfl' => $verification->pinfl,
                'full_name' => $verification->getFullName(),
                'verification' => $verification->toApiArray(),
            ];

        } catch (\Exception $e) {
            Yii::error('MyID status error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to get verification status');
        }
    }

    /**
     * Register new user via MyID
     * 
     * POST /api/myid/register
     * Body: {
     *   "code": "auth_code_from_sdk",
     *   "phone": "998901234567" // optional
     * }
     * 
     * @return array
     * @throws HttpException
     */
    public function actionRegister()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Method not allowed');
        }

        $post = Yii::$app->request->post();
        $code = $post['code'] ?? null;
        $phone = $post['phone'] ?? null;

        if (empty($code)) {
            throw new HttpException(400, 'Authorization code is required');
        }

        try {
            $myidService = new MyidService();
            
            // Exchange code for access token
            $tokenResult = $myidService->exchangeCodeForToken($code);
            if (!$tokenResult['success']) {
                throw new HttpException(401, 'Failed to exchange code: ' . ($tokenResult['error'] ?? 'Unknown error'));
            }

            // Get user data from MyID
            $userDataResult = $myidService->getUserData($tokenResult['access_token']);
            if (!$userDataResult['success']) {
                throw new HttpException(502, 'Failed to get user data from MyID');
            }

            $myidData = $userDataResult['data'];
            $pinfl = $myidData['pinfl'] ?? null;

            if (empty($pinfl)) {
                throw new HttpException(502, 'MyID response missing PINFL');
            }

            // Check if PINFL already registered
            $existingMyid = UserMyid::findByPinfl($pinfl);
            if ($existingMyid && $existingMyid->user_id) {
                $existingUser = $existingMyid->user;
                if ($existingUser) {
                    // Return existing user
                    return [
                        'success' => true,
                        'message' => 'User already registered with this PINFL',
                        'user' => [
                            'id' => $existingUser->id,
                            'token' => $existingUser->token,
                            'name' => $existingUser->name,
                            'phone' => $existingUser->phone,
                            'myid_verified' => $existingUser->myid_verified,
                        ],
                        'verification' => $existingMyid->toApiArray(),
                        'is_new_user' => false,
                    ];
                }
            }

            // Create new user
            $user = new User();
            $user->scenario = User::USER_SIGNUP;
            $user->role = User::ROLE_USER;
            $user->status = 1;
            $user->token = Yii::$app->security->generateRandomString(32);
            $user->name = trim(($myidData['first_name'] ?? '') . ' ' . ($myidData['last_name'] ?? ''));
            $user->lastname = $myidData['last_name'] ?? null;
            $user->myid_verified = 1;
            
            // Use provided phone or generate placeholder
            if ($phone) {
                // Check if phone already exists
                $existingPhone = User::findOne(['phone' => $phone]);
                if ($existingPhone) {
                    throw new HttpException(409, 'Phone number already registered');
                }
                $user->phone = $phone;
            } else {
                $user->phone = 'myid_' . $pinfl;
            }
            
            if (!$user->save(false)) {
                Yii::error('Failed to create user from MyID: ' . json_encode($user->errors), __METHOD__);
                throw new HttpException(500, 'Failed to create user');
            }

            // Create MyID verification record
            $verification = UserMyid::createFromMyidData($myidData, $user->id);
            if (!$verification) {
                // Rollback user creation
                $user->delete();
                throw new HttpException(500, 'Failed to save verification data');
            }

            return [
                'success' => true,
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'token' => $user->token,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'myid_verified' => $user->myid_verified,
                ],
                'verification' => $verification->toApiArray(),
                'is_new_user' => true,
            ];

        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('MyID register error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Get SDK configuration for mobile app
     * 
     * GET /api/myid/sdk-config
     * 
     * @return array
     */
    public function actionSdkConfig()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $myidService = new MyidService();
            
            return [
                'success' => true,
                'config' => [
                    'client_id' => $myidService->getClientId(),
                    'base_url' => $myidService->getBaseUrl(),
                    'scope' => $myidService->getScope(),
                ],
            ];

        } catch (\Exception $e) {
            Yii::error('MyID sdk-config error: ' . $e->getMessage(), __METHOD__);
            throw new HttpException(500, 'Failed to get SDK config');
        }
    }
}
