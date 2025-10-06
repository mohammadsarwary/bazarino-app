<?php
/**
 * Configuration Manager Class
 * 
 * Manages homepage configuration and provides methods to get/update settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_Config_Manager {
    
    private static $instance = null;
    private $option_name = 'bazarino_homepage_config';
    
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
     * Get homepage configuration
     * 
     * @return array Configuration array
     */
    public function get_config() {
        $default = $this->get_default_config();
        $saved = get_option($this->option_name, array());
        
        return wp_parse_args($saved, $default);
    }
    
    /**
     * Update homepage configuration
     * 
     * @param array $config New configuration
     * @return bool Success status
     */
    public function update_config($config) {
        $sanitized = $this->sanitize_config($config);
        return update_option($this->option_name, $sanitized);
    }
    
    /**
     * Get default configuration
     * 
     * @return array Default configuration
     */
    public function get_default_config() {
        return array(
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
                'widget_order' => array(
                    'slider',
                    'categories',
                    'flash_sales',
                    'banners',
                    'popular_products'
                )
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
    }
    
    /**
     * Sanitize configuration
     * 
     * @param array $config Configuration to sanitize
     * @return array Sanitized configuration
     */
    private function sanitize_config($config) {
        $sanitized = array();
        
        // Sanitize theme
        if (isset($config['theme'])) {
            $sanitized['theme'] = array(
                'primary_color' => sanitize_hex_color($config['theme']['primary_color'] ?? '#FF6B35'),
                'accent_color' => sanitize_hex_color($config['theme']['accent_color'] ?? '#004E89'),
                'background_color' => sanitize_hex_color($config['theme']['background_color'] ?? '#FFFFFF')
            );
        }
        
        // Sanitize layout
        if (isset($config['layout'])) {
            $sanitized['layout'] = array(
                'show_slider' => (bool) ($config['layout']['show_slider'] ?? true),
                'show_categories' => (bool) ($config['layout']['show_categories'] ?? true),
                'show_flash_sales' => (bool) ($config['layout']['show_flash_sales'] ?? true),
                'show_banners' => (bool) ($config['layout']['show_banners'] ?? true),
                'show_popular_products' => (bool) ($config['layout']['show_popular_products'] ?? true),
                'widget_order' => isset($config['layout']['widget_order']) ? 
                    array_map('sanitize_text_field', $config['layout']['widget_order']) : 
                    array()
            );
        }
        
        // Sanitize slider
        if (isset($config['slider'])) {
            $sanitized['slider'] = array(
                'auto_play' => (bool) ($config['slider']['auto_play'] ?? true),
                'interval' => absint($config['slider']['interval'] ?? 5),
                'height' => absint($config['slider']['height'] ?? 200)
            );
        }
        
        // Sanitize categories
        if (isset($config['categories'])) {
            $sanitized['categories'] = array(
                'display_type' => sanitize_text_field($config['categories']['display_type'] ?? 'grid'),
                'items_per_row' => absint($config['categories']['items_per_row'] ?? 4)
            );
        }
        
        return $sanitized;
    }
    
    /**
     * Reset to default configuration
     * 
     * @return bool Success status
     */
    public function reset_to_default() {
        return update_option($this->option_name, $this->get_default_config());
    }
}

