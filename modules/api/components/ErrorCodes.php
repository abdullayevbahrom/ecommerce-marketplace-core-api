<?php

namespace app\modules\api\components;

class ErrorCodes
{
    // Success
    const SUCCESS = 0;

    // Generic Errors
    const ERROR_VALIDATION = -1;
    const ERROR_NOT_FOUND = -2;
    const ERROR_SERVER = -3;
    const ERROR_UNAUTHORIZED = -4;
    const ERROR_FORBIDDEN = -5;

    // User/Auth Errors
    const ERROR_INVALID_PHONE = -10;
    const ERROR_INVALID_CODE = -11;
    const ERROR_USER_EXISTS = -12;
    const ERROR_USER_NOT_FOUND = -13;
    const ERROR_PASSWORD_INCORRECT = -14;
    const ERROR_PASSWORD_MISMATCH = -15;
    const ERROR_USER_SAVE_FAILED = -16;

    // Product/Cart Errors
    const ERROR_NOT_ENOUGH_STOCK = -20;
    const ERROR_MIN_ORDER_QUANTITY = -21;
    const ERROR_CART_ITEM_NOT_FOUND = -22;
    const ERROR_PRODUCT_NOT_FOUND = -23;

    // Error Messages
    public static $messages = [
        self::SUCCESS => 'Success',
        self::ERROR_VALIDATION => 'Validation error',
        self::ERROR_NOT_FOUND => 'Not found',
        self::ERROR_SERVER => 'Server error',
        self::ERROR_UNAUTHORIZED => 'Unauthorized',
        self::ERROR_FORBIDDEN => 'Forbidden',
        
        self::ERROR_INVALID_PHONE => 'Invalid phone number',
        self::ERROR_INVALID_CODE => 'Invalid or expired code',
        self::ERROR_USER_EXISTS => 'User already exists',
        self::ERROR_USER_NOT_FOUND => 'User not found',
        self::ERROR_PASSWORD_INCORRECT => 'Parol xato',
        self::ERROR_PASSWORD_MISMATCH => 'Passwords do not match',
        self::ERROR_USER_SAVE_FAILED => 'Failed to save user',

        self::ERROR_NOT_ENOUGH_STOCK => 'Not enough stock',
        self::ERROR_MIN_ORDER_QUANTITY => 'Minimum order quantity not met',
        self::ERROR_CART_ITEM_NOT_FOUND => 'Cart item not found',
        self::ERROR_PRODUCT_NOT_FOUND => 'Product not found',
    ];

    // HTTP Status Codes Map
    public static $httpCodes = [
        self::SUCCESS => 200,
        self::ERROR_VALIDATION => 422,
        self::ERROR_NOT_FOUND => 404,
        self::ERROR_SERVER => 500,
        self::ERROR_UNAUTHORIZED => 401,
        self::ERROR_FORBIDDEN => 403,
        
        self::ERROR_INVALID_PHONE => 422,
        self::ERROR_INVALID_CODE => 422,
        self::ERROR_USER_EXISTS => 422,
        self::ERROR_USER_NOT_FOUND => 404,
        self::ERROR_PASSWORD_INCORRECT => 422,
        self::ERROR_PASSWORD_MISMATCH => 422,
        self::ERROR_USER_SAVE_FAILED => 500,

        self::ERROR_NOT_ENOUGH_STOCK => 422,
        self::ERROR_MIN_ORDER_QUANTITY => 422,
        self::ERROR_CART_ITEM_NOT_FOUND => 404,
        self::ERROR_PRODUCT_NOT_FOUND => 404,
    ];

    public static function getMessage($code)
    {
        return self::$messages[$code] ?? 'Unknown error';
    }

    public static function getHttpCode($code)
    {
        return self::$httpCodes[$code] ?? 200; // Default to 200 if unknown, or maybe 400? 200 is safer for unknown custom codes unless we want to treat them as errors.
        // The user asked to "return error status... based on the type".
        // If it's a negative code it's likely an error. 
        // But let's stick to the map.
    }
}
