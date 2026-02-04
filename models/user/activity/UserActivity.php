<?php
namespace app\models\user\activity;

use Yii;
use yii\db\ActiveRecord;

class UserActivity extends ActiveRecord {
    
    const TYPE_SEARCH = 'search';
    const TYPE_VIEW = 'view';
    const TYPE_CATEGORY = 'category';
    
    public static function tableName() {
        return 'user_activity';
    }
    
    public function rules() {
        return [
            [['activity_type'], 'required'],
            [['user_id', 'product_id', 'category_id'], 'integer'],
            [['session_id', 'ip'], 'string', 'max' => 255],
            [['search_query'], 'string', 'max' => 255],
            [['activity_type'], 'in', 'range' => [self::TYPE_SEARCH, self::TYPE_VIEW, self::TYPE_CATEGORY]],
        ];
    }
    
    /**
     * Check if user is authenticated
     */
    private static function isUserAuthenticated() {
        try {
            return !Yii::$app->user->isGuest && Yii::$app->user->id;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Safely track user activity without throwing exceptions
     * Only tracks activity for authenticated users
     */
    private static function safeTrack($callback) {
        // Don't track activity for guest users
        if (!self::isUserAuthenticated()) {
            return;
        }
        
        try {
            $callback();
        } catch (\Exception $e) {
            // Log error but don't interrupt the main flow
            Yii::error('Failed to track user activity: ' . $e->getMessage(), __METHOD__);
        }
    }
    
    /**
     * Get user ID safely (returns null if not authenticated)
     */
    private static function getSafeUserId() {
        try {
            return Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get session ID safely
     */
    private static function getSafeSessionId() {
        try {
            if (!Yii::$app->session->getIsActive()) {
                Yii::$app->session->open();
            }
            return Yii::$app->session->id;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get user IP safely
     */
    private static function getSafeUserIP() {
        try {
            return Yii::$app->request->userIP ?? 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
    
    /**
     * Track search query - only for authenticated users
     */
    public static function trackSearch($query) {
        self::safeTrack(function() use ($query) {
            $activity = new self();
            $activity->activity_type = self::TYPE_SEARCH;
            $activity->search_query = $query;
            $activity->user_id = self::getSafeUserId();
            $activity->session_id = self::getSafeSessionId();
            $activity->ip = self::getSafeUserIP();
            $activity->save(false);
        });
    }
    
    /**
     * Track product view - only for authenticated users
     */
    public static function trackView($productId, $categoryId = null) {
        self::safeTrack(function() use ($productId, $categoryId) {
            $activity = new self();
            $activity->activity_type = self::TYPE_VIEW;
            $activity->product_id = $productId;
            $activity->category_id = $categoryId;
            $activity->user_id = self::getSafeUserId();
            $activity->session_id = self::getSafeSessionId();
            $activity->ip = self::getSafeUserIP();
            $activity->save(false);
        });
    }
    
    /**
     * Track category view - only for authenticated users
     */
    public static function trackCategory($categoryId) {
        self::safeTrack(function() use ($categoryId) {
            $activity = new self();
            $activity->activity_type = self::TYPE_CATEGORY;
            $activity->category_id = $categoryId;
            $activity->user_id = self::getSafeUserId();
            $activity->session_id = self::getSafeSessionId();
            $activity->ip = self::getSafeUserIP();
            $activity->save(false);
        });
    }
}