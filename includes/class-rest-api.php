<?php
/**
 * REST API Class
 * 
 * Provides REST API endpoints for the mobile app to fetch configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_REST_API {
    
    private static $instance = null;
    private $config_manager;
    private $notification_manager;
    private $namespace = 'bazarino/v1';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config_manager = Bazarino_Config_Manager::get_instance();
        $this->notification_manager = Bazarino_Notification_Manager::get_instance();
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get homepage configuration
        register_rest_route($this->namespace, '/app-config/homepage', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_homepage_config'),
            'permission_callback' => '__return_true',
        ));
        
        // Get full app configuration
        register_rest_route($this->namespace, '/app-config', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_full_config'),
            'permission_callback' => '__return_true',
        ));
        
        // Update homepage configuration (Admin only)
        register_rest_route($this->namespace, '/app-config/homepage', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_homepage_config'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'config' => array(
                    'required' => true,
                    'type' => 'object',
                    'description' => 'Configuration object'
                ),
            ),
        ));
        
        // === Notification Endpoints ===
        
        // Save FCM token (from app)
        register_rest_route($this->namespace, '/notifications/register-token', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_fcm_token'),
            'permission_callback' => '__return_true',
            'args' => array(
                'device_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Unique device identifier'
                ),
                'fcm_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'FCM registration token'
                ),
                'platform' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'android',
                    'description' => 'Platform (android/ios)'
                ),
                'app_version' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'App version'
                ),
            ),
        ));
        
        // Send push notification (Admin only)
        register_rest_route($this->namespace, '/notifications/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_push_notification'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'title' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Notification title'
                ),
                'body' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Notification body'
                ),
                'image_url' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Image URL'
                ),
                'data' => array(
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Additional data payload'
                ),
                'target_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'all',
                    'description' => 'Target type: all, users, platform'
                ),
                'platform' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Platform filter (android/ios)'
                ),
            ),
        ));
        
        // Get notifications history (Admin only)
        register_rest_route($this->namespace, '/notifications/history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notifications_history'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
        
        // Get notification statistics (Admin only)
        register_rest_route($this->namespace, '/notifications/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notification_stats'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
        
        // Debug endpoint
        register_rest_route($this->namespace, '/notifications/debug', array(
            'methods' => 'GET',
            'callback' => array($this, 'debug_notifications'),
            'permission_callback' => '__return_true',
        ));
        
        // Create tables endpoint
        register_rest_route($this->namespace, '/notifications/create-tables', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_notification_tables'),
            'permission_callback' => '__return_true',
        ));
        
        // Test FCM endpoint
        register_rest_route($this->namespace, '/notifications/test-fcm', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_fcm_send'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Get homepage configuration
     * 
     * GET /wp-json/bazarino/v1/app-config/homepage
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_homepage_config($request) {
        $config = $this->config_manager->get_config();
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $config,
            'version' => BAZARINO_APP_CONFIG_VERSION,
            'cached_until' => time() + 3600 // Cache for 1 hour
        ), 200);
    }
    
    /**
     * Get full app configuration
     * 
     * GET /wp-json/bazarino/v1/app-config
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_full_config($request) {
        $homepage_config = $this->config_manager->get_config();
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'homepage' => $homepage_config,
                'app_version' => BAZARINO_APP_CONFIG_VERSION,
                'force_update' => false,
                'maintenance_mode' => false,
                'features' => array(
                    'homepage_config' => true,
                    'dynamic_layout' => true,
                    'theme_customization' => true
                )
            ),
            'server_time' => current_time('mysql'),
            'timestamp' => time()
        ), 200);
    }
    
    /**
     * Update homepage configuration
     * 
     * POST /wp-json/bazarino/v1/app-config/homepage
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_homepage_config($request) {
        $config = $request->get_param('config');
        
        if (empty($config)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'missing_config',
                    'message' => 'Configuration data is required'
                )
            ), 400);
        }
        
        $result = $this->config_manager->update_config($config);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Configuration updated successfully',
                'data' => $this->config_manager->get_config()
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'update_failed',
                    'message' => 'Failed to update configuration'
                )
            ), 500);
        }
    }
    
    /**
     * Check admin permission
     * 
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    // === Notification Endpoints Callbacks ===
    
    /**
     * Register FCM token
     * 
     * POST /wp-json/bazarino/v1/notifications/register-token
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function register_fcm_token($request) {
        $device_id = $request->get_param('device_id');
        $fcm_token = $request->get_param('fcm_token');
        $platform = $request->get_param('platform') ?: 'android';
        $app_version = $request->get_param('app_version');
        
        // Get user ID if authenticated
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            $user_id = null;
        }
        
        $result = $this->notification_manager->save_fcm_token(
            $device_id,
            $fcm_token,
            $user_id,
            $platform,
            $app_version
        );
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'FCM token registered successfully'
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'registration_failed',
                    'message' => 'Failed to register FCM token'
                )
            ), 500);
        }
    }
    
    /**
     * Send push notification
     * 
     * POST /wp-json/bazarino/v1/notifications/send
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function send_push_notification($request) {
        $title = $request->get_param('title');
        $body = $request->get_param('body');
        $image_url = $request->get_param('image_url');
        $data = $request->get_param('data');
        $target_type = $request->get_param('target_type') ?: 'all';
        $platform = $request->get_param('platform');
        
        $result = $this->notification_manager->send_notification(
            $title,
            $body,
            $image_url,
            $data,
            $target_type,
            null, // target_users
            $platform
        );
        
        if ($result['success']) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => array(
                    'sent' => $result['sent'],
                    'failed' => $result['failed'],
                    'total_tokens' => $result['total_tokens']
                )
            ), 200);
        } else {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'send_failed',
                    'message' => $result['error']
                )
            ), 500);
        }
    }
    
    /**
     * Get notifications history
     * 
     * GET /wp-json/bazarino/v1/notifications/history
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_notifications_history($request) {
        $limit = $request->get_param('limit') ?: 20;
        $offset = $request->get_param('offset') ?: 0;
        
        $notifications = $this->notification_manager->get_notifications_history($limit, $offset);
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $notifications,
            'count' => count($notifications)
        ), 200);
    }
    
    /**
     * Get notification statistics
     * 
     * GET /wp-json/bazarino/v1/notifications/stats
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_notification_stats($request) {
        $stats = $this->notification_manager->get_statistics();
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats
        ), 200);
    }
    
    /**
     * Debug notifications system
     * 
     * GET /wp-json/bazarino/v1/notifications/debug
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function debug_notifications($request) {
        global $wpdb;
        
        // Check plugin active status with multiple possible paths
        $plugin_paths = array(
            'bazarino-admin-plugin/bazarino-app-config.php',
            'bazarino-app-config.php',
            'bazarino-admin-plugin/bazarino-app-config.php'
        );
        
        $plugin_active = false;
        $active_plugin_path = null;
        foreach ($plugin_paths as $path) {
            if (is_plugin_active($path)) {
                $plugin_active = true;
                $active_plugin_path = $path;
                break;
            }
        }
        
        // Also check if our classes exist (this is more reliable)
        $classes_exist = class_exists('Bazarino_Notification_Manager') && 
                      class_exists('Bazarino_Notification_Admin') &&
                      class_exists('Bazarino_REST_API');
        
        // Check if our REST API endpoints are registered
        $rest_routes_exist = false;
        if (function_exists('rest_get_server')) {
            $server = rest_get_server();
            $routes = $server->get_routes();
            $rest_routes_exist = isset($routes['/bazarino/v1/notifications/debug']);
        }
        
        $debug_info = array(
            'plugin_active' => $plugin_active || $classes_exist || $rest_routes_exist,
            'plugin_paths_checked' => $plugin_paths,
            'active_plugin_path' => $active_plugin_path,
            'classes_exist' => $classes_exist,
            'rest_routes_exist' => $rest_routes_exist,
            'notification_manager_exists' => class_exists('Bazarino_Notification_Manager'),
            'tables_exist' => array(),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'error_log' => array()
        );
        
        // Check if tables exist
        $fcm_table = $wpdb->prefix . 'bazarino_fcm_tokens';
        $notifications_table = $wpdb->prefix . 'bazarino_notifications';
        
        $debug_info['tables_exist']['fcm_tokens'] = $wpdb->get_var("SHOW TABLES LIKE '$fcm_table'") == $fcm_table;
        $debug_info['tables_exist']['notifications'] = $wpdb->get_var("SHOW TABLES LIKE '$notifications_table'") == $notifications_table;
        
        // Test database connection
        try {
            $test_result = $wpdb->get_var("SELECT 1");
            $debug_info['database_connection'] = $test_result == 1;
        } catch (Exception $e) {
            $debug_info['database_connection'] = false;
            $debug_info['database_error'] = $e->getMessage();
        }
        
        // Test notification manager
        try {
            $notification_manager = Bazarino_Notification_Manager::get_instance();
            $debug_info['notification_manager_instance'] = true;
        } catch (Exception $e) {
            $debug_info['notification_manager_instance'] = false;
            $debug_info['notification_manager_error'] = $e->getMessage();
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'debug_info' => $debug_info
        ), 200);
    }
    
    /**
     * Create notification tables
     * 
     * POST /wp-json/bazarino/v1/notifications/create-tables
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function create_notification_tables($request) {
        try {
            $notification_manager = Bazarino_Notification_Manager::get_instance();
            $result = $notification_manager->create_tables();
            
            if ($result) {
                return new WP_REST_Response(array(
                    'success' => true,
                    'message' => 'Notification tables created successfully'
                ), 200);
            } else {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'Failed to create tables'
                ), 500);
            }
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => $e->getMessage()
            ), 500);
        }
    }
    
    /**
     * Test FCM send
     * 
     * POST /wp-json/bazarino/v1/notifications/test-fcm
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function test_fcm_send($request) {
        try {
            $notification_manager = Bazarino_Notification_Manager::get_instance();
            
            // Get test data
            $title = $request->get_param('title') ?: 'Test Notification';
            $body = $request->get_param('body') ?: 'This is a test notification';
            $fcm_token = $request->get_param('fcm_token');
            
            if (empty($fcm_token)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'FCM token is required'
                ), 400);
            }
            
            // Test access token
            $access_token = $notification_manager->get_access_token();
            
            if (empty($access_token)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'error' => 'FCM Service Account not configured or invalid',
                    'debug' => array(
                        'service_account_exists' => !empty(get_option('bazarino_fcm_service_account')),
                        'project_id' => $notification_manager->get_project_id()
                    )
                ), 400);
            }
            
            // Test FCM request
            $fcm_payload = array(
                'message' => array(
                    'token' => $fcm_token,
                    'notification' => array(
                        'title' => $title,
                        'body' => $body
                    ),
                    'data' => array(
                        'test' => 'true',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ),
                    'android' => array(
                        'priority' => 'high',
                        'notification' => array(
                            'priority' => 'high',
                            'default_sound' => true
                        )
                    )
                )
            );
            
            // Send FCM request
            $response = $notification_manager->send_fcm_request($access_token, $fcm_payload);
            
            return new WP_REST_Response(array(
                'success' => $response['success'],
                'message' => $response['success'] ? 'FCM test successful' : 'FCM test failed',
                'debug' => array(
                    'access_token_length' => strlen($access_token),
                    'project_id' => $notification_manager->get_project_id(),
                    'fcm_response' => $response
                )
            ), $response['success'] ? 200 : 500);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ), 500);
        }
    }
}

