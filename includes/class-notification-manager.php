<?php
/**
 * Notification Manager Class
 * 
 * Manages push notifications via Firebase Cloud Messaging (FCM)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_Notification_Manager {
    
    private static $instance = null;
    private $fcm_table_name;
    private $notifications_table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->fcm_table_name = $wpdb->prefix . 'bazarino_fcm_tokens';
        $this->notifications_table_name = $wpdb->prefix . 'bazarino_notifications';
    }
    
    /**
     * Create database tables for FCM tokens and notifications
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // FCM Tokens table
        $sql_tokens = "CREATE TABLE IF NOT EXISTS {$this->fcm_table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            device_id varchar(255) NOT NULL,
            fcm_token text NOT NULL,
            platform varchar(50) DEFAULT 'android',
            app_version varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY device_id (device_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Notifications history table
        $sql_notifications = "CREATE TABLE IF NOT EXISTS {$this->notifications_table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            body text NOT NULL,
            image_url text DEFAULT NULL,
            data_payload text DEFAULT NULL,
            target_type varchar(50) DEFAULT 'all',
            target_users text DEFAULT NULL,
            sent_count int DEFAULT 0,
            failed_count int DEFAULT 0,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime DEFAULT NULL,
            status varchar(50) DEFAULT 'draft',
            PRIMARY KEY (id),
            KEY created_by (created_by),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tokens);
        dbDelta($sql_notifications);
    }
    
    /**
     * Save or update FCM token for a device
     * 
     * @param string $device_id Unique device identifier
     * @param string $fcm_token FCM token
     * @param int $user_id WordPress user ID (optional)
     * @param string $platform Platform (android/ios)
     * @param string $app_version App version
     * @return bool Success status
     */
    public function save_fcm_token($device_id, $fcm_token, $user_id = null, $platform = 'android', $app_version = null) {
        global $wpdb;
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->fcm_table_name} WHERE device_id = %s",
            $device_id
        ));
        
        $data = array(
            'fcm_token' => $fcm_token,
            'user_id' => $user_id,
            'platform' => $platform,
            'app_version' => $app_version,
            'is_active' => 1,
            'updated_at' => current_time('mysql')
        );
        
        if ($existing) {
            // Update existing token
            $result = $wpdb->update(
                $this->fcm_table_name,
                $data,
                array('device_id' => $device_id),
                array('%s', '%d', '%s', '%s', '%d', '%s'),
                array('%s')
            );
        } else {
            // Insert new token
            $data['device_id'] = $device_id;
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                $this->fcm_table_name,
                $data,
                array('%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Get all active FCM tokens
     * 
     * @param string $platform Filter by platform (optional)
     * @param array $user_ids Filter by user IDs (optional)
     * @return array Array of FCM tokens
     */
    public function get_active_tokens($platform = null, $user_ids = null) {
        global $wpdb;
        
        $where = "WHERE is_active = 1";
        
        if ($platform) {
            $where .= $wpdb->prepare(" AND platform = %s", $platform);
        }
        
        if ($user_ids && is_array($user_ids) && count($user_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
            $where .= $wpdb->prepare(" AND user_id IN ($placeholders)", $user_ids);
        }
        
        $results = $wpdb->get_results(
            "SELECT fcm_token, platform, user_id FROM {$this->fcm_table_name} {$where}"
        );
        
        return $results;
    }
    
    /**
     * Send push notification via FCM
     * 
     * @param string $title Notification title
     * @param string $body Notification body
     * @param string|null $image_url Image URL (optional)
     * @param array|null $data Additional data payload
     * @param string $target_type Target type: 'all', 'users', 'platform'
     * @param array|null $target_users User IDs for targeted notification
     * @param string|null $platform Platform filter (android/ios)
     * @return array Result with sent and failed counts
     */
    public function send_notification($title, $body, $image_url = null, $data = null, $target_type = 'all', $target_users = null, $platform = null) {
        // Get FCM Server Key from options
        $fcm_server_key = get_option('bazarino_fcm_server_key');
        
        if (empty($fcm_server_key)) {
            return array(
                'success' => false,
                'error' => 'FCM Server Key not configured',
                'sent' => 0,
                'failed' => 0
            );
        }
        
        // Get target tokens
        $tokens = $this->get_active_tokens($platform, $target_users);
        
        if (empty($tokens)) {
            return array(
                'success' => false,
                'error' => 'No active tokens found',
                'sent' => 0,
                'failed' => 0
            );
        }
        
        // Prepare notification payload
        $notification_data = array(
            'title' => $title,
            'body' => $body,
        );
        
        if ($image_url) {
            $notification_data['image'] = $image_url;
        }
        
        // Additional data
        $extra_data = $data ? $data : array();
        $extra_data['sent_at'] = current_time('mysql');
        $extra_data['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
        
        $sent_count = 0;
        $failed_count = 0;
        
        // Send to tokens in batches (FCM allows max 1000 per request)
        $token_chunks = array_chunk($tokens, 500);
        
        foreach ($token_chunks as $chunk) {
            $fcm_tokens = array_map(function($item) {
                return $item->fcm_token;
            }, $chunk);
            
            $fcm_payload = array(
                'registration_ids' => $fcm_tokens,
                'notification' => $notification_data,
                'data' => $extra_data,
                'priority' => 'high',
                'content_available' => true
            );
            
            // Send via FCM
            $response = $this->send_fcm_request($fcm_server_key, $fcm_payload);
            
            if ($response['success']) {
                $sent_count += $response['success_count'];
                $failed_count += $response['failure_count'];
            } else {
                $failed_count += count($fcm_tokens);
            }
        }
        
        // Save notification to history
        $this->save_notification_history(
            $title,
            $body,
            $image_url,
            $data,
            $target_type,
            $target_users,
            $sent_count,
            $failed_count
        );
        
        return array(
            'success' => true,
            'sent' => $sent_count,
            'failed' => $failed_count,
            'total_tokens' => count($tokens)
        );
    }
    
    /**
     * Send FCM HTTP request
     * 
     * @param string $server_key FCM Server Key
     * @param array $payload FCM payload
     * @return array Response with success status
     */
    private function send_fcm_request($server_key, $payload) {
        $url = 'https://fcm.googleapis.com/fcm/send';
        
        $headers = array(
            'Authorization: key=' . $server_key,
            'Content-Type: application/json'
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($http_code == 200) {
            $response = json_decode($result, true);
            return array(
                'success' => true,
                'success_count' => isset($response['success']) ? $response['success'] : 0,
                'failure_count' => isset($response['failure']) ? $response['failure'] : 0,
                'response' => $response
            );
        } else {
            return array(
                'success' => false,
                'error' => 'FCM request failed with code: ' . $http_code,
                'response' => $result
            );
        }
    }
    
    /**
     * Save notification to history
     */
    private function save_notification_history($title, $body, $image_url, $data, $target_type, $target_users, $sent_count, $failed_count) {
        global $wpdb;
        
        $wpdb->insert(
            $this->notifications_table_name,
            array(
                'title' => $title,
                'body' => $body,
                'image_url' => $image_url,
                'data_payload' => json_encode($data),
                'target_type' => $target_type,
                'target_users' => json_encode($target_users),
                'sent_count' => $sent_count,
                'failed_count' => $failed_count,
                'created_by' => get_current_user_id(),
                'sent_at' => current_time('mysql'),
                'status' => 'sent'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Get notifications history
     * 
     * @param int $limit Number of records
     * @param int $offset Offset for pagination
     * @return array Notifications
     */
    public function get_notifications_history($limit = 20, $offset = 0) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->notifications_table_name} 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
        
        return $results;
    }
    
    /**
     * Get total count of active devices
     * 
     * @return int Total count
     */
    public function get_active_devices_count() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->fcm_table_name} WHERE is_active = 1"
        );
    }
    
    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $total_devices = $this->get_active_devices_count();
        
        $android_devices = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->fcm_table_name} WHERE is_active = 1 AND platform = 'android'"
        );
        
        $ios_devices = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->fcm_table_name} WHERE is_active = 1 AND platform = 'ios'"
        );
        
        $total_notifications = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->notifications_table_name}"
        );
        
        $total_sent = (int) $wpdb->get_var(
            "SELECT SUM(sent_count) FROM {$this->notifications_table_name}"
        );
        
        return array(
            'total_devices' => $total_devices,
            'android_devices' => $android_devices,
            'ios_devices' => $ios_devices,
            'total_notifications' => $total_notifications,
            'total_sent' => $total_sent
        );
    }
}

