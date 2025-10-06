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
    private $namespace = 'bazarino/v1';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config_manager = Bazarino_Config_Manager::get_instance();
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
}

