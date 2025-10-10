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
        
        $result_tokens = dbDelta($sql_tokens);
        $result_notifications = dbDelta($sql_notifications);
        
        // Check if tables were created successfully
        $tokens_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->fcm_table_name}'") == $this->fcm_table_name;
        $notifications_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->notifications_table_name}'") == $this->notifications_table_name;
        
        return $tokens_exists && $notifications_exists;
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
     * Send push notification via FCM HTTP v1
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
        // Get OAuth2 Access Token
        $access_token = $this->get_access_token();
        
        if (empty($access_token)) {
            return array(
                'success' => false,
                'error' => 'FCM Service Account not configured or invalid',
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
        
        // Send to each token individually (HTTP v1 format)
        foreach ($tokens as $token_data) {
            $fcm_payload = array(
                'message' => array(
                    'token' => $token_data->fcm_token,
                    'notification' => $notification_data,
                    'data' => $extra_data,
                    'android' => array(
                        'priority' => 'high',
                        'notification' => array(
                            'priority' => 'high',
                            'default_sound' => true
                        )
                    ),
                    'apns' => array(
                        'payload' => array(
                            'aps' => array(
                                'content_available' => true,
                                'sound' => 'default'
                            )
                        )
                    )
                )
            );
            
            // Send via FCM HTTP v1
            $response = $this->send_fcm_request($access_token, $fcm_payload);
            
            if ($response['success']) {
                $sent_count += $response['success_count'];
            } else {
                $failed_count += 1;
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
     * Send FCM HTTP v1 request
     * 
     * @param string $access_token OAuth2 Access Token
     * @param array $payload FCM payload
     * @return array Response with success status
     */
    public function send_fcm_request($access_token, $payload) {
        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->get_project_id() . '/messages:send';
        
        $headers = array(
            'Authorization: Bearer ' . $access_token,
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
                'success_count' => 1, // HTTP v1 returns single message
                'failure_count' => 0,
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
     * Get OAuth2 Access Token
     * 
     * @return string|false Access token or false on failure
     */
    public function get_access_token() {
        $service_account = get_option('bazarino_fcm_service_account');
        
        if (empty($service_account)) {
            return false;
        }
        
        $service_account_data = json_decode($service_account, true);
        
        if (!$service_account_data) {
            return false;
        }
        
        $jwt_header = json_encode(array(
            'alg' => 'RS256',
            'typ' => 'JWT'
        ));
        
        $now = time();
        $jwt_payload = json_encode(array(
            'iss' => $service_account_data['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ));
        
        $jwt_header_encoded = $this->base64url_encode($jwt_header);
        $jwt_payload_encoded = $this->base64url_encode($jwt_payload);
        
        $jwt_signature = '';
        $signing_input = $jwt_header_encoded . '.' . $jwt_payload_encoded;
        
        if (openssl_sign($signing_input, $jwt_signature, $service_account_data['private_key'], 'SHA256')) {
            $jwt_signature_encoded = $this->base64url_encode($jwt_signature);
            $jwt = $signing_input . '.' . $jwt_signature_encoded;
            
            // Exchange JWT for access token
            $token_url = 'https://oauth2.googleapis.com/token';
            $token_data = array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $token_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $token_result = curl_exec($ch);
            $token_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($token_http_code == 200) {
                $token_response = json_decode($token_result, true);
                return isset($token_response['access_token']) ? $token_response['access_token'] : false;
            }
        }
        
        return false;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Get Firebase Project ID
     */
    private function get_project_id() {
        $service_account = get_option('bazarino_fcm_service_account');
        if (empty($service_account)) {
            return '';
        }
        
        $service_account_data = json_decode($service_account, true);
        return isset($service_account_data['project_id']) ? $service_account_data['project_id'] : '';
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

