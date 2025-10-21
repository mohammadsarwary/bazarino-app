<?php
/**
 * Database Schema Class
 * 
 * Creates and manages database tables for App Builder functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_Database_Schema {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize
    }
    
    /**
     * Create all App Builder tables
     * 
     * @return bool Success status
     */
    public function create_tables() {
        global $wpdb;
        
        // Enable error reporting for debugging
        $wpdb->show_errors = true;
        
        $created = true;
        
        try {
            // Create app_screens table
            $created &= $this->create_app_screens_table();
            
            // Create app_widgets table
            $created &= $this->create_app_widgets_table();
            
            // Create app_theme table
            $created &= $this->create_app_theme_table();
            
            // Create app_navigation table
            $created &= $this->create_app_navigation_table();
            
            // Create app_features table
            $created &= $this->create_app_features_table();
            
            // Create app_builds table (for future use)
            $created &= $this->create_app_builds_table();
            
        } catch (Exception $e) {
            error_log('Bazarino Database Schema Error: ' . $e->getMessage());
            return false;
        }
        
        return $created;
    }
    
    /**
     * Create app_screens table
     * 
     * @return bool Success status
     */
    private function create_app_screens_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_screens';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            screen_id varchar(50) NOT NULL,
            name varchar(200) NOT NULL,
            route varchar(200) NOT NULL,
            description text DEFAULT NULL,
            settings longtext DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY screen_id (screen_id),
            KEY route (route),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        return $this->execute_sql($sql);
    }
    
    /**
     * Create app_widgets table
     * 
     * @return bool Success status
     */
    private function create_app_widgets_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_widgets';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            widget_id varchar(50) NOT NULL,
            screen_id varchar(50) NOT NULL,
            widget_type varchar(50) NOT NULL,
            name varchar(200) NOT NULL,
            settings longtext DEFAULT NULL,
            is_visible tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY widget_screen (widget_id, screen_id),
            KEY screen_id (screen_id),
            KEY widget_type (widget_type),
            KEY is_visible (is_visible),
            KEY sort_order (sort_order),
            FOREIGN KEY (screen_id) REFERENCES {$wpdb->prefix}bazarino_app_screens(screen_id) ON DELETE CASCADE
        ) $charset_collate;";
        
        return $this->execute_sql($sql);
    }
    
    /**
     * Create app_theme table
     * 
     * @return bool Success status
     */
    private function create_app_theme_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_theme';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            theme_id varchar(50) NOT NULL DEFAULT 'default',
            name varchar(200) NOT NULL DEFAULT 'Default Theme',
            colors longtext DEFAULT NULL,
            typography longtext DEFAULT NULL,
            shapes longtext DEFAULT NULL,
            components longtext DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY theme_id (theme_id),
            KEY is_active (is_active),
            KEY is_default (is_default)
        ) $charset_collate;";
        
        return $this->execute_sql($sql);
    }
    
    /**
     * Create app_navigation table
     * 
     * @return bool Success status
     */
    private function create_app_navigation_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_navigation';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            nav_id varchar(50) NOT NULL DEFAULT 'default',
            nav_type varchar(20) NOT NULL DEFAULT 'bottom',
            name varchar(200) NOT NULL DEFAULT 'Bottom Navigation',
            items longtext DEFAULT NULL,
            colors longtext DEFAULT NULL,
            styles longtext DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_default tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nav_type (nav_type),
            KEY is_active (is_active),
            KEY is_default (is_default)
        ) $charset_collate;";
        
        return $this->execute_sql($sql);
    }
    
    /**
     * Create app_features table
     * 
     * @return bool Success status
     */
    private function create_app_features_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_features';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            feature_key varchar(100) NOT NULL,
            feature_name varchar(200) NOT NULL,
            description text DEFAULT NULL,
            is_enabled tinyint(1) DEFAULT 1,
            category varchar(50) DEFAULT 'general',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY feature_key (feature_key),
            KEY category (category),
            KEY is_enabled (is_enabled)
        ) $charset_collate;";
        
        return $this->execute_sql($sql);
    }
    
    /**
     * Create app_builds table (for future use)
     * 
     * @return bool Success status
     */
    private function create_app_builds_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bazarino_app_builds';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            build_id varchar(50) NOT NULL,
            version varchar(20) NOT NULL,
            build_type varchar(20) NOT NULL DEFAULT 'development',
            platform varchar(20) NOT NULL DEFAULT 'android',
            file_path varchar(500) DEFAULT NULL,
            file_size bigint(20) DEFAULT 0,
            download_count int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            release_notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY build_id (build_id),
            KEY version (version),
            KEY platform (platform),
            KEY build_type (build_type),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        return $this->execute_sql($sql);
    }
    
    /**
     * Execute SQL with error handling
     * 
     * @param string $sql SQL query
     * @return bool Success status
     */
    private function execute_sql($sql) {
        global $wpdb;
        
        try {
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log('Database Schema SQL Error: ' . $wpdb->last_error);
                error_log('Failed SQL: ' . $sql);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Database Schema Exception: ' . $e->getMessage());
            error_log('Failed SQL: ' . $sql);
            return false;
        }
    }
    
    /**
     * Drop all App Builder tables (for testing/uninstall)
     * 
     * @return bool Success status
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'bazarino_app_widgets',
            $wpdb->prefix . 'bazarino_app_screens',
            $wpdb->prefix . 'bazarino_app_theme',
            $wpdb->prefix . 'bazarino_app_navigation',
            $wpdb->prefix . 'bazarino_app_features',
            $wpdb->prefix . 'bazarino_app_builds'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        return true;
    }
    
    /**
     * Insert default data
     * 
     * @return bool Success status
     */
    public function insert_default_data() {
        global $wpdb;
        
        $success = true;
        
        try {
            // Insert default screens
            $this->insert_default_screens();
            
            // Insert default widgets
            $this->insert_default_widgets();
            
            // Insert default theme
            $this->insert_default_theme();
            
            // Insert default navigation
            $this->insert_default_navigation();
            
            // Insert default features
            $this->insert_default_features();
            
        } catch (Exception $e) {
            error_log('Default Data Insert Error: ' . $e->getMessage());
            return false;
        }
        
        return $success;
    }
    
    /**
     * Insert default screens
     */
    private function insert_default_screens() {
        global $wpdb;
        
        $screens_table = $wpdb->prefix . 'bazarino_app_screens';
        
        $default_screens = array(
            array(
                'screen_id' => 'home',
                'name' => 'Home Screen',
                'route' => '/home',
                'description' => 'Main home screen with widgets',
                'settings' => json_encode(array(
                    'has_app_bar' => true,
                    'app_bar_title' => 'Bazarino',
                    'has_bottom_nav' => true,
                    'pull_to_refresh' => true,
                    'enable_search' => true
                )),
                'is_default' => 1,
                'sort_order' => 1
            ),
            array(
                'screen_id' => 'products',
                'name' => 'Products Screen',
                'route' => '/products',
                'description' => 'Products listing screen',
                'settings' => json_encode(array(
                    'has_app_bar' => true,
                    'app_bar_title' => 'Products',
                    'has_bottom_nav' => true,
                    'enable_search' => true,
                    'enable_filter' => true
                )),
                'is_default' => 0,
                'sort_order' => 2
            ),
            array(
                'screen_id' => 'categories',
                'name' => 'Categories Screen',
                'route' => '/categories',
                'description' => 'Categories listing screen',
                'settings' => json_encode(array(
                    'has_app_bar' => true,
                    'app_bar_title' => 'Categories',
                    'has_bottom_nav' => true,
                    'grid_columns' => 2
                )),
                'is_default' => 0,
                'sort_order' => 3
            ),
            array(
                'screen_id' => 'cart',
                'name' => 'Cart Screen',
                'route' => '/cart',
                'description' => 'Shopping cart screen',
                'settings' => json_encode(array(
                    'has_app_bar' => true,
                    'app_bar_title' => 'Cart',
                    'has_bottom_nav' => true,
                    'enable_coupon' => true
                )),
                'is_default' => 0,
                'sort_order' => 4
            ),
            array(
                'screen_id' => 'profile',
                'name' => 'Profile Screen',
                'route' => '/profile',
                'description' => 'User profile screen',
                'settings' => json_encode(array(
                    'has_app_bar' => true,
                    'app_bar_title' => 'Profile',
                    'has_bottom_nav' => true
                )),
                'is_default' => 0,
                'sort_order' => 5
            )
        );
        
        foreach ($default_screens as $screen) {
            $wpdb->insert($screens_table, $screen);
        }
    }
    
    /**
     * Insert default widgets
     */
    private function insert_default_widgets() {
        global $wpdb;
        
        $widgets_table = $wpdb->prefix . 'bazarino_app_widgets';
        
        $default_widgets = array(
            // Home screen widgets
            array(
                'widget_id' => 'home_slider',
                'screen_id' => 'home',
                'widget_type' => 'slider',
                'name' => 'Home Slider',
                'settings' => json_encode(array(
                    'auto_play' => true,
                    'interval' => 5,
                    'height' => 200,
                    'show_dots' => true,
                    'show_arrows' => false
                )),
                'is_visible' => 1,
                'sort_order' => 1
            ),
            array(
                'widget_id' => 'home_categories',
                'screen_id' => 'home',
                'widget_type' => 'categories_grid',
                'name' => 'Categories Grid',
                'settings' => json_encode(array(
                    'columns' => 4,
                    'show_count' => 8,
                    'hide_empty' => true,
                    'show_all_button' => true
                )),
                'is_visible' => 1,
                'sort_order' => 2
            ),
            array(
                'widget_id' => 'home_flash_sales',
                'screen_id' => 'home',
                'widget_type' => 'flash_sales',
                'name' => 'Flash Sales',
                'settings' => json_encode(array(
                    'layout' => 'horizontal',
                    'show_timer' => true,
                    'items_count' => 10,
                    'show_discount' => true
                )),
                'is_visible' => 1,
                'sort_order' => 3
            ),
            array(
                'widget_id' => 'home_products',
                'screen_id' => 'home',
                'widget_type' => 'products_grid',
                'name' => 'Popular Products',
                'settings' => json_encode(array(
                    'columns' => 2,
                    'items_count' => 8,
                    'category_filter' => 'all',
                    'sort_by' => 'popularity'
                )),
                'is_visible' => 1,
                'sort_order' => 4
            ),
            array(
                'widget_id' => 'home_banners',
                'screen_id' => 'home',
                'widget_type' => 'banners',
                'name' => 'Promotional Banners',
                'settings' => json_encode(array(
                    'layout' => 'grid',
                    'columns' => 2,
                    'auto_scroll' => false,
                    'interval' => 3
                )),
                'is_visible' => 1,
                'sort_order' => 5
            )
        );
        
        foreach ($default_widgets as $widget) {
            $wpdb->insert($widgets_table, $widget);
        }
    }
    
    /**
     * Insert default theme
     */
    private function insert_default_theme() {
        global $wpdb;
        
        $theme_table = $wpdb->prefix . 'bazarino_app_theme';
        
        $default_theme = array(
            'theme_id' => 'default',
            'name' => 'Default Theme',
            'colors' => json_encode(array(
                'primary' => '#FF6B35',
                'secondary' => '#004E89',
                'accent' => '#F77F00',
                'background' => '#FFFFFF',
                'surface' => '#F5F5F5',
                'error' => '#D32F2F',
                'success' => '#4CAF50',
                'warning' => '#FF9800',
                'info' => '#2196F3'
            )),
            'typography' => json_encode(array(
                'font_family' => 'Iranian Sans',
                'font_size_base' => 14,
                'font_weights' => array(
                    'regular' => 400,
                    'medium' => 500,
                    'bold' => 700
                ),
                'line_height' => 1.4
            )),
            'shapes' => json_encode(array(
                'border_radius' => 12,
                'card_elevation' => 2,
                'button_radius' => 8,
                'input_radius' => 8
            )),
            'components' => json_encode(array(
                'app_bar_height' => 56,
                'bottom_nav_height' => 60,
                'card_padding' => 16,
                'button_height' => 48
            ))
        );
        
        $wpdb->insert($theme_table, $default_theme);
    }
    
    /**
     * Insert default navigation
     */
    private function insert_default_navigation() {
        global $wpdb;
        
        $nav_table = $wpdb->prefix . 'bazarino_app_navigation';
        
        $default_nav = array(
            'nav_id' => 'default',
            'nav_type' => 'bottom',
            'name' => 'Bottom Navigation',
            'items' => json_encode(array(
                array(
                    'id' => 'home',
                    'label' => 'خانه',
                    'icon' => 'home',
                    'route' => '/home',
                    'badge' => false,
                    'active' => true
                ),
                array(
                    'id' => 'categories',
                    'label' => 'دسته‌بندی',
                    'icon' => 'category',
                    'route' => '/categories',
                    'badge' => false,
                    'active' => true
                ),
                array(
                    'id' => 'cart',
                    'label' => 'سبد خرید',
                    'icon' => 'shopping_cart',
                    'route' => '/cart',
                    'badge' => true,
                    'active' => true
                ),
                array(
                    'id' => 'profile',
                    'label' => 'پروفایل',
                    'icon' => 'person',
                    'route' => '/profile',
                    'badge' => false,
                    'active' => true
                )
            )),
            'colors' => json_encode(array(
                'active_color' => '#FF6B35',
                'inactive_color' => '#9E9E9E',
                'background_color' => '#FFFFFF'
            )),
            'styles' => json_encode(array(
                'icon_size' => 24,
                'label_size' => 12,
                'selected_font_weight' => 600,
                'unselected_font_weight' => 400
            ))
        );
        
        $wpdb->insert($nav_table, $default_nav);
    }
    
    /**
     * Insert default features
     */
    private function insert_default_features() {
        global $wpdb;
        
        $features_table = $wpdb->prefix . 'bazarino_app_features';
        
        $default_features = array(
            // Core features
            array('feature_key' => 'products', 'feature_name' => 'Products', 'description' => 'Browse and search products', 'category' => 'core'),
            array('feature_key' => 'categories', 'feature_name' => 'Categories', 'description' => 'Product categories', 'category' => 'core'),
            array('feature_key' => 'search', 'feature_name' => 'Search', 'description' => 'Product search functionality', 'category' => 'core'),
            array('feature_key' => 'cart', 'feature_name' => 'Shopping Cart', 'description' => 'Add to cart and checkout', 'category' => 'core'),
            
            // User features
            array('feature_key' => 'favorites', 'feature_name' => 'Favorites', 'description' => 'Save favorite products', 'category' => 'user'),
            array('feature_key' => 'orders', 'feature_name' => 'Orders', 'description' => 'Order history and tracking', 'category' => 'user'),
            array('feature_key' => 'profile', 'feature_name' => 'User Profile', 'description' => 'User account management', 'category' => 'user'),
            
            // Advanced features
            array('feature_key' => 'reviews', 'feature_name' => 'Product Reviews', 'description' => 'Customer reviews and ratings', 'category' => 'advanced'),
            array('feature_key' => 'notifications', 'feature_name' => 'Push Notifications', 'description' => 'Push notification system', 'category' => 'advanced'),
            array('feature_key' => 'flash_sales', 'feature_name' => 'Flash Sales', 'description' => 'Limited time offers', 'category' => 'advanced'),
            array('feature_key' => 'coupons', 'feature_name' => 'Coupons', 'description' => 'Discount coupon system', 'category' => 'advanced'),
            array('feature_key' => 'product_compare', 'feature_name' => 'Product Compare', 'description' => 'Compare multiple products', 'category' => 'advanced'),
            array('feature_key' => 'recently_viewed', 'feature_name' => 'Recently Viewed', 'description' => 'Track viewed products', 'category' => 'advanced'),
            array('feature_key' => 'social_share', 'feature_name' => 'Social Share', 'description' => 'Share products on social media', 'category' => 'advanced'),
            array('feature_key' => 'guest_checkout', 'feature_name' => 'Guest Checkout', 'description' => 'Checkout without registration', 'category' => 'advanced')
        );
        
        foreach ($default_features as $feature) {
            $wpdb->insert($features_table, $feature);
        }
    }
    
    /**
     * Check if tables exist
     * 
     * @return array Table status
     */
    public function check_tables() {
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
            $status[$name] = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
        }
        
        return $status;
    }
}