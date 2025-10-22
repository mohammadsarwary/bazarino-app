<?php
/**
 * App Builder REST API Class
 * 
 * Provides REST API endpoints for the App Builder functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_App_Builder_API {
    
    private static $instance = null;
    private $database_schema;
    private $namespace = 'bazarino/v1';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->database_schema = Bazarino_Database_Schema::get_instance();
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes for App Builder
     */
    public function register_routes() {
        
        // === Screen Management Endpoints ===
        
        // Get all screens
        register_rest_route($this->namespace, '/app-builder/screens', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_screens'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
        
        // Get single screen
        register_rest_route($this->namespace, '/app-builder/screens/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_screen'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Screen ID'
                ),
            ),
        ));
        
        // Create screen
        register_rest_route($this->namespace, '/app-builder/screens', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_screen'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'name' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Screen name'
                ),
                'route' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Screen route'
                ),
                'screen_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'custom',
                    'description' => 'Screen type (home, category, product, custom)'
                ),
                'layout' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'scroll',
                    'description' => 'Layout type (scroll, grid, list)'
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'active',
                    'description' => 'Screen status (active, inactive)'
                ),
            ),
        ));
        
        // Update screen
        register_rest_route($this->namespace, '/app-builder/screens/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_screen'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Screen ID'
                ),
                'name' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Screen name'
                ),
                'route' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Screen route'
                ),
                'screen_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Screen type'
                ),
                'layout' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Layout type'
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Screen status'
                ),
            ),
        ));
        
        // Delete screen
        register_rest_route($this->namespace, '/app-builder/screens/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_screen'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Screen ID'
                ),
            ),
        ));
        
        // === Widget Management Endpoints ===
        
        // Get all widgets
        register_rest_route($this->namespace, '/app-builder/widgets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_widgets'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
        
        // Get widgets for a specific screen
        register_rest_route($this->namespace, '/app-builder/screens/(?P<screen_id>\d+)/widgets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_screen_widgets'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'screen_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Screen ID'
                ),
            ),
        ));
        
        // Create widget
        register_rest_route($this->namespace, '/app-builder/widgets', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_widget'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'screen_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Screen ID'
                ),
                'widget_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Widget type (slider, banner, category, product, etc.)'
                ),
                'title' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Widget title'
                ),
                'config' => array(
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Widget configuration'
                ),
                'position' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                    'description' => 'Widget position'
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'active',
                    'description' => 'Widget status'
                ),
            ),
        ));
        
        // Update widget
        register_rest_route($this->namespace, '/app-builder/widgets/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_widget'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Widget ID'
                ),
                'widget_type' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Widget type'
                ),
                'title' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Widget title'
                ),
                'config' => array(
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Widget configuration'
                ),
                'position' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Widget position'
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Widget status'
                ),
            ),
        ));
        
        // Delete widget
        register_rest_route($this->namespace, '/app-builder/widgets/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_widget'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Widget ID'
                ),
            ),
        ));
        
        // Reorder widgets
        register_rest_route($this->namespace, '/app-builder/widgets/reorder', array(
            'methods' => 'POST',
            'callback' => array($this, 'reorder_widgets'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'widgets' => array(
                    'required' => true,
                    'type' => 'array',
                    'description' => 'Array of widget IDs in new order'
                ),
            ),
        ));
        
        // === Theme Management Endpoints ===
        
        // Get theme configuration
        register_rest_route($this->namespace, '/app-builder/theme', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_theme'),
            'permission_callback' => '__return_true',
        ));
        
        // Update theme configuration
        register_rest_route($this->namespace, '/app-builder/theme', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_theme'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'primary_color' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Primary color'
                ),
                'accent_color' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Accent color'
                ),
                'background_color' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Background color'
                ),
                'text_color' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Text color'
                ),
                'font_family' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Font family'
                ),
                'border_radius' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Border radius'
                ),
            ),
        ));
        
        // === Navigation Management Endpoints ===
        
        // Get navigation configuration
        register_rest_route($this->namespace, '/app-builder/navigation', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_navigation'),
            'permission_callback' => '__return_true',
        ));
        
        // Update navigation configuration
        register_rest_route($this->namespace, '/app-builder/navigation', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_navigation'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'config' => array(
                    'required' => true,
                    'type' => 'object',
                    'description' => 'Navigation configuration'
                ),
            ),
        ));
        
        // === App Configuration Endpoints ===
        
        // Get complete app configuration for mobile app
        register_rest_route($this->namespace, '/app-builder/config', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_app_config'),
            'permission_callback' => '__return_true',
        ));
        
        // Get app features
        register_rest_route($this->namespace, '/app-builder/features', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_features'),
            'permission_callback' => '__return_true',
        ));
        
        // Update app features
        register_rest_route($this->namespace, '/app-builder/features', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_features'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'features' => array(
                    'required' => true,
                    'type' => 'object',
                    'description' => 'Features configuration'
                ),
            ),
        ));
        
        // === Build Management Endpoints ===
        
        // Get build history
        register_rest_route($this->namespace, '/app-builder/builds', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_builds'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
        
        // Create build record
        register_rest_route($this->namespace, '/app-builder/builds', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_build'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => array(
                'version' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Build version'
                ),
                'platform' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Platform (android, ios)'
                ),
                'build_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Build type (debug, release)'
                ),
            ),
        ));
    }
    
    /**
     * Check admin permission
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    // === Screen Management Methods ===
    
    /**
     * Get all screens
     */
    public function get_screens($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_screens';
        $screens = $wpdb->get_results("SELECT * FROM $table_name ORDER BY updated_at DESC");
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $screens
        ), 200);
    }
    
    /**
     * Get single screen
     */
    public function get_screen($request) {
        global $wpdb;
        
        $screen_id = intval($request['id']);
        $table_name = $wpdb->prefix . 'bazarino_app_screens';
        
        $screen = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $screen_id
        ));
        
        if (!$screen) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'screen_not_found',
                    'message' => 'Screen not found'
                )
            ), 404);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $screen
        ), 200);
    }
    
    /**
     * Create screen
     */
    public function create_screen($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_screens';
        
        $data = array(
            'screen_id' => uniqid('screen_'),
            'name' => $request['name'],
            'route' => $request['route'],
            'description' => '',
            'settings' => json_encode(array(
                'screen_type' => $request['screen_type'],
                'layout' => $request['layout'],
                'status' => $request['status']
            )),
            'is_active' => $request['status'] === 'active' ? 1 : 0,
            'is_default' => 0,
            'sort_order' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $format = array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s');
        
        $result = $wpdb->insert($table_name, $data, $format);
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'create_failed',
                    'message' => 'Failed to create screen'
                )
            ), 500);
        }
        
        $screen_id = $wpdb->insert_id;
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Screen created successfully',
            'data' => array(
                'id' => $screen_id,
                'name' => $request['name'],
                'route' => $request['route']
            )
        ), 201);
    }
    
    /**
     * Update screen
     */
    public function update_screen($request) {
        global $wpdb;
        
        $screen_id = intval($request['id']);
        $table_name = $wpdb->prefix . 'bazarino_app_screens';
        
        // Check if screen exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $screen_id
        ));
        
        if (!$existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'screen_not_found',
                    'message' => 'Screen not found'
                )
            ), 404);
        }
        
        // Get current settings
        $current_screen = $wpdb->get_row($wpdb->prepare(
            "SELECT settings FROM $table_name WHERE id = %d",
            $screen_id
        ));
        
        $settings = json_decode($current_screen->settings, true) ?: array();
        
        // Update settings with new values
        if (isset($request['screen_type'])) {
            $settings['screen_type'] = $request['screen_type'];
        }
        if (isset($request['layout'])) {
            $settings['layout'] = $request['layout'];
        }
        if (isset($request['status'])) {
            $settings['status'] = $request['status'];
        }
        
        $data = array(
            'settings' => json_encode($settings),
            'is_active' => isset($request['status']) && $request['status'] === 'active' ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        $format = array('%s', '%d', '%s');
        
        // Add optional fields
        if (isset($request['name'])) {
            $data['name'] = $request['name'];
            $format[] = '%s';
        }
        if (isset($request['route'])) {
            $data['route'] = $request['route'];
            $format[] = '%s';
        }
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $screen_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'update_failed',
                    'message' => 'Failed to update screen'
                )
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Screen updated successfully'
        ), 200);
    }
    
    /**
     * Delete screen
     */
    public function delete_screen($request) {
        global $wpdb;
        
        $screen_id = intval($request['id']);
        $table_name = $wpdb->prefix . 'bazarino_app_screens';
        
        // Check if screen exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $screen_id
        ));
        
        if (!$existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'screen_not_found',
                    'message' => 'Screen not found'
                )
            ), 404);
        }
        
        // Delete associated widgets first
        $widgets_table = $wpdb->prefix . 'bazarino_app_widgets';
        $wpdb->delete(
            $widgets_table,
            array('screen_id' => $screen_id),
            array('%d')
        );
        
        // Delete screen
        $result = $wpdb->delete(
            $table_name,
            array('id' => $screen_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'delete_failed',
                    'message' => 'Failed to delete screen'
                )
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Screen deleted successfully'
        ), 200);
    }
    
    // === Widget Management Methods ===
    
    /**
     * Get all widgets
     */
    public function get_widgets($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_widgets';
        $widgets = $wpdb->get_results(
            "SELECT w.*, s.name as screen_name, s.route as screen_route
             FROM $table_name w
             LEFT JOIN {$wpdb->prefix}bazarino_app_screens s ON w.screen_id = s.screen_id
             ORDER BY w.screen_id, w.sort_order"
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $widgets
        ), 200);
    }
    
    /**
     * Get widgets for a specific screen
     */
    public function get_screen_widgets($request) {
        global $wpdb;
        
        $screen_id = intval($request['screen_id']);
        $table_name = $wpdb->prefix . 'bazarino_app_widgets';
        
        $widgets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE screen_id = %d ORDER BY sort_order",
            $screen_id
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $widgets
        ), 200);
    }
    
    /**
     * Create widget
     */
    public function create_widget($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_widgets';
        
        $data = array(
            'widget_id' => uniqid('widget_'),
            'screen_id' => $request['screen_id'],
            'widget_type' => $request['widget_type'],
            'name' => $request['title'] ?: '',
            'settings' => json_encode(array_merge(
                $request['config'] ?: array(),
                array('status' => $request['status'])
            )),
            'is_visible' => $request['status'] === 'active' ? 1 : 0,
            'sort_order' => $request['position'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $format = array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s');
        
        $result = $wpdb->insert($table_name, $data, $format);
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'create_failed',
                    'message' => 'Failed to create widget'
                )
            ), 500);
        }
        
        $widget_id = $wpdb->insert_id;
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Widget created successfully',
            'data' => array(
                'id' => $widget_id,
                'widget_type' => $request['widget_type'],
                'screen_id' => $request['screen_id']
            )
        ), 201);
    }
    
    /**
     * Update widget
     */
    public function update_widget($request) {
        global $wpdb;
        
        $widget_id = intval($request['id']);
        $table_name = $wpdb->prefix . 'bazarino_app_widgets';
        
        // Check if widget exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $widget_id
        ));
        
        if (!$existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'widget_not_found',
                    'message' => 'Widget not found'
                )
            ), 404);
        }
        
        // Get current settings
        $current_widget = $wpdb->get_row($wpdb->prepare(
            "SELECT settings FROM $table_name WHERE id = %d",
            $widget_id
        ));
        
        $settings = json_decode($current_widget->settings, true) ?: array();
        
        // Update settings with new values
        if (isset($request['config'])) {
            $settings = array_merge($settings, $request['config']);
        }
        if (isset($request['status'])) {
            $settings['status'] = $request['status'];
        }
        
        $data = array(
            'settings' => json_encode($settings),
            'is_visible' => isset($request['status']) && $request['status'] === 'active' ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        $format = array('%s', '%d', '%s');
        
        // Add optional fields
        if (isset($request['widget_type'])) {
            $data['widget_type'] = $request['widget_type'];
            $format[] = '%s';
        }
        if (isset($request['title'])) {
            $data['name'] = $request['title'];
            $format[] = '%s';
        }
        if (isset($request['position'])) {
            $data['sort_order'] = $request['position'];
            $format[] = '%d';
        }
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $widget_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'update_failed',
                    'message' => 'Failed to update widget'
                )
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Widget updated successfully'
        ), 200);
    }
    
    /**
     * Delete widget
     */
    public function delete_widget($request) {
        global $wpdb;
        
        $widget_id = intval($request['id']);
        $table_name = $wpdb->prefix . 'bazarino_app_widgets';
        
        // Check if widget exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $widget_id
        ));
        
        if (!$existing) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'widget_not_found',
                    'message' => 'Widget not found'
                )
            ), 404);
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $widget_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'delete_failed',
                    'message' => 'Failed to delete widget'
                )
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Widget deleted successfully'
        ), 200);
    }
    
    /**
     * Reorder widgets
     */
    public function reorder_widgets($request) {
        global $wpdb;
        
        $widgets = $request['widgets'];
        $table_name = $wpdb->prefix . 'bazarino_app_widgets';
        
        foreach ($widgets as $position => $widget_id) {
            $wpdb->update(
                $table_name,
                array('sort_order' => $position, 'updated_at' => current_time('mysql')),
                array('id' => $widget_id),
                array('%d', '%s'),
                array('%d')
            );
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Widgets reordered successfully'
        ), 200);
    }
    
    // === Theme Management Methods ===
    
    /**
     * Get theme configuration
     */
    public function get_theme($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_theme';
        $theme = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
        
        if (!$theme) {
            // Return default theme if none exists
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'primary_color' => '#FF6B35',
                    'accent_color' => '#004E89',
                    'background_color' => '#FFFFFF',
                    'text_color' => '#333333',
                    'font_family' => 'Roboto',
                    'border_radius' => '8px'
                )
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $theme
        ), 200);
    }
    
    /**
     * Update theme configuration
     */
    public function update_theme($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_theme';
        
        // Check if theme exists
        $existing = $wpdb->get_var("SELECT id FROM $table_name LIMIT 1");
        
        $data = array(
            'updated_at' => current_time('mysql')
        );
        $format = array('%s');
        
        // Add optional fields
        $optional_fields = array('primary_color', 'accent_color', 'background_color', 'text_color', 'font_family', 'border_radius');
        foreach ($optional_fields as $field) {
            if (isset($request[$field])) {
                $data[$field] = $request[$field];
                $format[] = '%s';
            }
        }
        
        if ($existing) {
            // Update existing theme
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing),
                $format,
                array('%d')
            );
        } else {
            // Create new theme
            $data['created_at'] = current_time('mysql');
            array_unshift($format, '%s');
            $result = $wpdb->insert($table_name, $data, $format);
        }
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'update_failed',
                    'message' => 'Failed to update theme'
                )
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Theme updated successfully'
        ), 200);
    }
    
    // === Navigation Management Methods ===
    
    /**
     * Get navigation configuration
     */
    public function get_navigation($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_navigation';
        $navigation = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
        
        if (!$navigation) {
            // Return default navigation if none exists
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'config' => json_encode(array(
                        'bottom_nav' => array(
                            array('id' => 'home', 'title' => 'Home', 'icon' => 'home', 'route' => '/'),
                            array('id' => 'categories', 'title' => 'Categories', 'icon' => 'grid', 'route' => '/categories'),
                            array('id' => 'search', 'title' => 'Search', 'icon' => 'search', 'route' => '/search'),
                            array('id' => 'cart', 'title' => 'Cart', 'icon' => 'shopping_cart', 'route' => '/cart'),
                            array('id' => 'profile', 'title' => 'Profile', 'icon' => 'person', 'route' => '/profile')
                        )
                    ))
                )
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $navigation
        ), 200);
    }
    
    /**
     * Update navigation configuration
     */
    public function update_navigation($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_navigation';
        
        // Check if navigation exists
        $existing = $wpdb->get_var("SELECT id FROM $table_name LIMIT 1");
        
        $data = array(
            'config' => json_encode($request['config']),
            'updated_at' => current_time('mysql')
        );
        $format = array('%s', '%s');
        
        if ($existing) {
            // Update existing navigation
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing),
                $format,
                array('%d')
            );
        } else {
            // Create new navigation
            $data['created_at'] = current_time('mysql');
            array_unshift($format, '%s');
            $result = $wpdb->insert($table_name, $data, $format);
        }
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'update_failed',
                    'message' => 'Failed to update navigation'
                )
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Navigation updated successfully'
        ), 200);
    }
    
    // === App Configuration Methods ===
    
    /**
     * Get complete app configuration for mobile app
     */
    public function get_app_config($request) {
        global $wpdb;
        
        // Get screens
        $screens_table = $wpdb->prefix . 'bazarino_app_screens';
        $screens = $wpdb->get_results(
            "SELECT * FROM $screens_table WHERE is_active = 1 ORDER BY sort_order"
        );
        
        // Get widgets for each screen
        $widgets_table = $wpdb->prefix . 'bazarino_app_widgets';
        foreach ($screens as &$screen) {
            $screen->widgets = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $widgets_table WHERE screen_id = %d AND is_visible = 1 ORDER BY sort_order",
                $screen->id
            ));
            
            // Decode config JSON for each widget
            foreach ($screen->widgets as &$widget) {
                $widget->config = json_decode($widget->settings, true) ?: array();
                $widget->title = $widget->name;
            }
        }
        
        // Get theme
        $theme_table = $wpdb->prefix . 'bazarino_app_theme';
        $theme = $wpdb->get_row("SELECT * FROM $theme_table LIMIT 1");
        
        // Get navigation
        $navigation_table = $wpdb->prefix . 'bazarino_app_navigation';
        $navigation = $wpdb->get_row("SELECT * FROM $navigation_table LIMIT 1");
        
        // Get features
        $features_table = $wpdb->prefix . 'bazarino_app_features';
        $features = $wpdb->get_row("SELECT * FROM $features_table LIMIT 1");
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'screens' => $screens,
                'theme' => $theme,
                'navigation' => $navigation ? json_decode($navigation->config, true) : array(),
                'features' => $features ? json_decode($features->features, true) : array(),
                'version' => BAZARINO_APP_CONFIG_VERSION,
                'timestamp' => time()
            )
        ), 200);
    }
    
    /**
     * Get app features
     */
    public function get_features($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_features';
        $features = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
        
        if (!$features) {
            // Return default features if none exists
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'features' => json_encode(array(
                        'dynamic_screens' => true,
                        'theme_customization' => true,
                        'push_notifications' => true,
                        'offline_mode' => false,
                        'multi_language' => false,
                        'rtl_support' => true
                    ))
                )
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $features
        ), 200);
    }
    
    /**
     * Update app features
     */
    public function update_features($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_features';
        
        // Check if features exist
        $existing = $wpdb->get_var("SELECT id FROM $table_name LIMIT 1");
        
        $data = array(
            'features' => json_encode($request['features']),
            'updated_at' => current_time('mysql')
        );
        $format = array('%s', '%s');
        
        if ($existing) {
            // Update existing features
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing),
                $format,
                array('%d')
            );
        } else {
            // Create new features
            $data['created_at'] = current_time('mysql');
            array_unshift($format, '%s');
            $result = $wpdb->insert($table_name, $data, $format);
        }
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'update_failed',
                    'message' => 'Failed to update features'
                )
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Features updated successfully'
        ), 200);
    }
    
    // === Build Management Methods ===
    
    /**
     * Get build history
     */
    public function get_builds($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_builds';
        $builds = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $builds
        ), 200);
    }
    
    /**
     * Create build record
     */
    public function create_build($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_builds';
        
        $data = array(
            'version' => $request['version'],
            'platform' => $request['platform'],
            'build_type' => $request['build_type'],
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $format = array('%s', '%s', '%s', '%s', '%s', '%s');
        
        $result = $wpdb->insert($table_name, $data, $format);
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => array(
                    'code' => 'create_failed',
                    'message' => 'Failed to create build record'
                )
            ), 500);
        }
        
        $build_id = $wpdb->insert_id;
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Build record created successfully',
            'data' => array(
                'id' => $build_id,
                'version' => $request['version'],
                'platform' => $request['platform']
            )
        ), 201);
    }
}