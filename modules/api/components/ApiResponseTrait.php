<?php

namespace app\modules\api\components;

use Yii;

trait ApiResponseTrait
{
    /**
     * Send an error response with standardized structure
     * 
     * @param int $code The error code from ErrorCodes class
     * @param string|null $message Custom error message or null to use default
     * @param array $errors Validation errors or other details
     * @param int|null $statusCode HTTP status code. If null, determined by error code.
     * @return array
     */
    protected function sendError($code, $message = null, $errors = [], $statusCode = null)
    {
        if ($statusCode === null) {
            $statusCode = ErrorCodes::getHttpCode($code);
        }

        Yii::$app->response->statusCode = $statusCode;
        
        if ($message === null) {
            $message = ErrorCodes::getMessage($code);
        }

        $response = [
            'message' => $message,
            'error_code' => $code,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Send a success response
     * 
     * @param array $data Data to return
     * @param string $message Success message
     * @return array
     */
    protected function sendSuccess($data = [], $message = 'Success')
    {
        Yii::$app->response->statusCode = 200;
        
        return [
            'message' => $message,
            'error_code' => ErrorCodes::SUCCESS,
            'data' => $data
        ];
    }
}
