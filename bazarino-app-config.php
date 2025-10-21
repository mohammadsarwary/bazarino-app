<?php
/**
 * Plugin Name: Bazarino App Config
 * Plugin URI: https://bazarino.com
 * Description: Custom configuration plugin for Bazarino Mobile App. Provides admin panel to configure app appearance, homepage layout, and custom settings.
 * Version: 1.0.0
 * Author: Bazarino Team
 * Author URI: https://bazarino.com
 * Text Domain: bazarino-app-config
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BAZARINO_APP_CONFIG_VERSION', '1.0.0');
define('BAZARINO_APP_CONFIG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BAZARINO_APP_CONFIG_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class Bazarino_App_Config {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-admin-panel.php';
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-config-manager.php';
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-notification-manager.php';
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-notification-admin.php';
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-database-schema.php';
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-app-builder-api.php';
        require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-app-builder-admin.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bazarino-app-config',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize admin panel
        if (is_admin()) {
            Bazarino_Admin_Panel::get_instance();
            Bazarino_Notification_Admin::get_instance();
            Bazarino_App_Builder_Admin::get_instance();
        }
        
        // Initialize REST API
        Bazarino_REST_API::get_instance();
        
        // Initialize App Builder API
        Bazarino_App_Builder_API::get_instance();
        
        // Initialize config manager
        Bazarino_Config_Manager::get_instance();
        
        // Initialize notification manager
        Bazarino_Notification_Manager::get_instance();
        
        // Initialize database schema
        Bazarino_Database_Schema::get_instance();
    }
}

/**
 * Initialize the plugin
 */
function bazarino_app_config_init() {
    return Bazarino_App_Config::get_instance();
}

// Start the plugin
bazarino_app_config_init();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'bazarino_app_config_activate');
function bazarino_app_config_activate() {
    // Set default configuration
    $default_config = array(
        'theme' => array(
            'primary_color' => '#FF6B35',
            'accent_color' => '#004E89',
            'background_color' => '#FFFFFF'
        ),
        'layout' => array(
            'show_slider' => true,
            'show_categories' => true,
            'show_flash_sales' => true,
            'show_banners' => true,
            'show_popular_products' => true,
            'widget_order' => array('slider', 'categories', 'flash_sales', 'banners', 'popular_products')
        ),
        'slider' => array(
            'auto_play' => true,
            'interval' => 5,
            'height' => 200
        ),
        'categories' => array(
            'display_type' => 'grid',
            'items_per_row' => 4
        )
    );
    
    add_option('bazarino_homepage_config', $default_config);
    
    // Create notification tables
    require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-notification-manager.php';
    $notification_manager = Bazarino_Notification_Manager::get_instance();
    $notification_manager->create_tables();
    
    // Create App Builder tables
    require_once BAZARINO_APP_CONFIG_PLUGIN_DIR . 'includes/class-database-schema.php';
    $database_schema = Bazarino_Database_Schema::get_instance();
    $database_schema->create_tables();
    $database_schema->insert_default_data();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'bazarino_app_config_deactivate');
function bazarino_app_config_deactivate() {
    // Cleanup if needed
}

