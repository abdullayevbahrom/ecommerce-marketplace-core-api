    /**
     * Create Product (Sklad Integration)
     * POST /api/product/create
     */
    public function actionCreate()
    {
        $this->checkSkladAuth();

        $request = Yii::$app->request;
        $userId = $request->post('user_id');

        if (!$userId) {
            return $this->sendError(400, 'user_id is required');
        }

        $user = User::findOne($userId);
        if (!$user) {
            return $this->sendError(404, 'User not found');
        }

        // Login the user so saveObject logic works
        Yii::$app->user->login($user);

        $model = new Product();
        $post = $request->post();

        // Ensure status is active by default
        if (!isset($post['status'])) {
            $post['status'] = 1;
        }

        // Map Branch to Stock if needed
        if (isset($post['branch_id']) && !isset($post['stock_id'])) {
            $post['stock_id'] = $post['branch_id'];
        }

        // Validate basic load
        Yii::info("ActionCreate received post: " . json_encode($post), 'api');
        if ($model->load($post, '')) {
            // Fix: shop_id is required but auto-detected in saveObject. 
            // We need to set it here manually for $model->validate() to pass.
            if (empty($model->shop_id)) {
                $shop = \app\models\shop\Shop::findOne(['user_id' => $user->id]);
                if (!$shop && $user->shop_id) {
                    $shop = \app\models\shop\Shop::findOne($user->shop_id);
                }
                if ($shop) {
                    $model->shop_id = $shop->id;
                    $post['shop_id'] = $shop->id;
                }
            }

            // Update Request with modified POST data so saveObject sees it (stock_id, shop_id, etc.)
            Yii::$app->request->setBodyParams($post);

            if ($model->validate()) {
                $colors = $post['colors'] ?? ($post['color_id'] ? [$post['color_id']] : []);
                $productTypes = $post['product_types'] ?? [];
                $callbackUrl = $post['callback_url'] ?? null;

                // Fix: Sklad sends product_types as a list of objects [{yii_product_type_id:1, yii_product_type_value_id:35}, ...]
                // We need to convert this to the format expected by generateTypeCombinations: [type_id => [val1, val2]]
                if (!empty($productTypes)) {
                    // Check if first element is an object/array (Sklad format) vs associative map (Admin format)
                    $firstItem = reset($productTypes);
                    $isSkladFormat = is_array($firstItem) || is_object($firstItem);

                    // Also check by looking for 'yii_product_type_id' key
                    if ($isSkladFormat) {
                        $firstItem = (array) $firstItem;
                        $isSkladFormat = isset($firstItem['yii_product_type_id']);
                    }

                    if ($isSkladFormat) {
                        $normalizedTypes = [];
                        foreach ($productTypes as $item) {
                            // Cast to array in case it's stdClass from JSON decode
                            $item = (array) $item;

                            $ptId = $item['yii_product_type_id'] ?? null;
                            if (!$ptId) continue;

                            // Use value_id if present and not null, otherwise custom_value
                            // Note: array key can exist with null value, ?? handles this
                            $val = null;
                            if (isset($item['yii_product_type_value_id']) && $item['yii_product_type_value_id'] !== null) {
                                $val = $item['yii_product_type_value_id'];
                            } elseif (isset($item['custom_value']) && $item['custom_value'] !== null) {
                                $val = $item['custom_value'];
                            }

                            if ($val !== null) {
                                $normalizedTypes[$ptId][] = $val;
                            }
                        }
                        $productTypes = $normalizedTypes;
                    }
                }

                $tokenKey = $post['token_key'] ?? Yii::$app->security->generateRandomString();

                // Calculate expected products count BEFORE sending response
                $typeCombinations = [];
                if (!empty($productTypes)) {
                    $typeCombinations = $this->generateTypeCombinations($productTypes);
                }
                if (empty($typeCombinations)) {
                    $typeCombinations = [null];
                }
                $colorsCount = !empty($colors) ? count($colors) : 1;
                $expectedCount = count($typeCombinations) * $colorsCount;

                // Send SUCCESS response IMMEDIATELY (before creating products)
                $responseData = [
                    'success' => true,
                    'message' => 'Product creation started',
                    'data' => [
                        'status' => 'processing',
                        'token_key' => $tokenKey,
                        'expected_count' => $expectedCount,
                    ]
                ];

                // Set response and send it
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                Yii::$app->response->data = $responseData;
                Yii::$app->response->send();

                // Close connection to client, continue processing in background
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } else {
                    // Fallback for non-FPM environments
                    if (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                    flush();
                }

                // ========== BACKGROUND PROCESSING ==========
                // Client has already received response, now create products

                try {
                    $createdProducts = [];
                    $failedProducts = [];

                    // Iterate Combinations (Types x Colors)
                    foreach ($typeCombinations as $combination) {
                        $colorsToLoop = !empty($colors) ? $colors : [null];

                        foreach ($colorsToLoop as $colorId) {
                            // Pass specific combination of types to saveObject
                            $product = $model->saveObject(true, $colorId, $tokenKey, null, $combination);

                            if ($product) {
                                $createdProducts[] = $product;
                            } else {
                                $failedProducts[] = [
                                    'color_id' => $colorId,
                                    'combination' => $combination,
                                    'errors' => $model->errors
                                ];
                            }
                        }
                    }

                    // Send callback to Sklad with results (if callback_url provided)
                    if ($callbackUrl) {
                        $callbackData = [
                            'status' => 'completed',
                            'user_id' => $userId,
                            'token_key' => $tokenKey,
                            'product_ids' => array_column($createdProducts, 'id'),
                            'products' => $createdProducts,
                            'count' => count($createdProducts),
                            'failed' => $failedProducts,
                        ];
                        $this->sendCallbackToSklad($callbackUrl, $callbackData);
                    }

                    Yii::info("Background product creation completed: " . count($createdProducts) . " products created", 'api');
                } catch (\Exception $e) {
                    Yii::error("Background product creation failed: " . $e->getMessage(), 'api');

                    // Send error callback if URL provided
                    if ($callbackUrl) {
                        $this->sendCallbackToSklad($callbackUrl, [
                            'status' => 'error',
                            'user_id' => $userId,
                            'token_key' => $tokenKey,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Exit to prevent Yii from trying to send response again
                Yii::$app->end();
            } else {
                Yii::info("ActionCreate validation failed: " . json_encode($model->errors), 'api');
            }
        } else {
            Yii::info("ActionCreate load failed", 'api');
        }

        return $this->sendError(422, 'Validation error', $model->errors);
    }
