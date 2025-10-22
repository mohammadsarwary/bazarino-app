<?php
/**
 * App Builder Debug Class
 * 
 * Helps with debugging and troubleshooting the App Builder functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_App_Builder_Debug {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', array($this, 'check_system_status'));
        add_action('admin_notices', array($this, 'show_debug_notices'));
    }
    
    /**
     * Check system status and requirements
     */
    public function check_system_status() {
        $status = array();
        
        // Check database tables
        $status['database'] = $this->check_database_tables();
        
        // Check API endpoints
        $status['api'] = $this->check_api_endpoints();
        
        // Check file permissions
        $status['permissions'] = $this->check_file_permissions();
        
        // Store status for later use
        update_option('bazarino_app_builder_debug_status', $status);
        
        return $status;
    }
    
    /**
     * Check database tables
     */
    private function check_database_tables() {
        global $wpdb;
        
        $tables = array(
            'app_screens' => $wpdb->prefix . 'bazarino_app_screens',
            'app_widgets' => $wpdb->prefix . 'bazarino_app_widgets',
            'app_theme' => $wpdb->prefix . 'bazarino_app_theme',
            'app_navigation' => $wpdb->prefix . 'bazarino_app_navigation',
            'app_features' => $wpdb->prefix . 'bazarino_app_features',
            'app_builds' => $wpdb->prefix . 'bazarino_app_builds'
        );
        
        $status = array();
        
        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
            $status[$name] = array(
                'exists' => $exists,
                'table' => $table
            );
            
            if ($exists) {
                // Check table structure
                $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
                $status[$name]['columns'] = $columns;
                
                // Check row count
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                $status[$name]['row_count'] = $count;
            }
        }
        
        return $status;
    }
    
    /**
     * Check API endpoints
     */
    private function check_api_endpoints() {
        $status = array();
        
        // Check if REST API is working
        $api_url = rest_url('bazarino/v1/app-builder/config');
        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response)) {
            $status['rest_api'] = array(
                'working' => false,
                'error' => $response->get_error_message()
            );
        } else {
            $status['rest_api'] = array(
                'working' => true,
                'status_code' => wp_remote_retrieve_response_code($response)
            );
        }
        
        return $status;
    }
    
    /**
     * Check file permissions
     */
    private function check_file_permissions() {
        $status = array();
        
        $files = array(
            'plugin_main' => BAZARINO_APP_CONFIG_PLUGIN_DIR . 'bazarino-app-config.php',
            'database_schema' => BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-database-schema.php',
            'api' => BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-app-builder-api.php',
            'admin' => BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-app-builder-admin.php',
            'css' => BAZARINO_APP_CONFIG_PLUGIN_DIR . 'admin/css/app-builder-style.css',
            'js' => BAZARINO_APP_CONFIG_PLUGIN_DIR . 'admin/js/app-builder-script.js'
        );
        
        foreach ($files as $name => $file) {
            if (file_exists($file)) {
                $status[$name] = array(
                    'exists' => true,
                    'readable' => is_readable($file),
                    'writable' => is_writable($file)
                );
            } else {
                $status[$name] = array(
                    'exists' => false
                );
            }
        }
        
        return $status;
    }
    
    /**
     * Show debug notices in admin
     */
    public function show_debug_notices() {
        $screen = get_current_screen();
        
        // Only show on App Builder pages
        if (!$screen || strpos($screen->id, 'bazarino-app-builder') === false) {
            return;
        }
        
        $status = get_option('bazarino_app_builder_debug_status', array());
        
        if (empty($status)) {
            return;
        }
        
        // Check database tables
        if (isset($status['database'])) {
            $missing_tables = array();
            foreach ($status['database'] as $table => $info) {
                if (!$info['exists']) {
                    $missing_tables[] = $table;
                }
            }
            
            if (!empty($missing_tables)) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>Bazarino App Builder:</strong> Missing database tables: ' . implode(', ', $missing_tables) . '</p>';
                echo '<p>Please deactivate and reactivate the plugin to create the missing tables.</p>';
                echo '</div>';
            }
        }
        
        // Check API
        if (isset($status['api']['rest_api']) && !$status['api']['rest_api']['working']) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Bazarino App Builder:</strong> REST API is not working: ' . $status['api']['rest_api']['error'] . '</p>';
            echo '</div>';
        }
        
        // Check file permissions
        if (isset($status['permissions'])) {
            $problem_files = array();
            foreach ($status['permissions'] as $file => $info) {
                if (!$info['exists'] || !$info['readable']) {
                    $problem_files[] = $file;
                }
            }
            
            if (!empty($problem_files)) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>Bazarino App Builder:</strong> Problem with files: ' . implode(', ', $problem_files) . '</p>';
                echo '</div>';
            }
        }
        
        // Show success message if everything is OK
        $all_good = true;
        
        if (isset($status['database'])) {
            foreach ($status['database'] as $table => $info) {
                if (!$info['exists']) {
                    $all_good = false;
                    break;
                }
            }
        }
        
        if ($all_good && isset($status['api']['rest_api']) && $status['api']['rest_api']['working']) {
            echo '<div class="notice notice-success">';
            echo '<p><strong>Bazarino App Builder:</strong> All systems operational!</p>';
            echo '</div>';
        }
    }
    
    /**
     * Get detailed debug information
     */
    public function get_debug_info() {
        $status = get_option('bazarino_app_builder_debug_status', array());
        
        if (empty($status)) {
            $status = $this->check_system_status();
        }
        
        return $status;
    }
    
    /**
     * Recreate database tables
     */
    public function recreate_tables() {
        $schema = Bazarino_Database_Schema::get_instance();
        
        // Drop existing tables
        $schema->drop_tables();
        
        // Create new tables
        $result = $schema->create_tables();
        
        if ($result) {
            // Insert default data
            $schema->insert_default_data();
        }
        
        // Recheck system status
        $this->check_system_status();
        
        return $result;
    }
}