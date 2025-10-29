<?php
/**
 * Slider Widget Class
 *
 * @package Bazarino_App_Builder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Slider Widget
 */
class Bazarino_Widget_Slider {
    
    /**
     * Widget type identifier
     */
    const TYPE = 'slider';
    
    /**
     * Get widget metadata
     */
    public static function get_metadata() {
        return array(
            'type' => self::TYPE,
            'name' => __('Slider', 'bazarino-app-config'),
            'description' => __('Image slider with auto-play support', 'bazarino-app-config'),
            'icon' => 'dashicons-images-alt2',
            'category' => 'media',
        );
    }
    
    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'auto_play' => true,
            'interval' => 5,
            'height' => 200,
            'show_indicators' => true,
            'show_navigation' => false,
            'border_radius' => 12,
        );
    }
    
    /**
     * Get settings schema
     */
    public static function get_settings_schema() {
        return array(
            'auto_play' => array(
                'type' => 'boolean',
                'label' => __('Auto Play', 'bazarino-app-config'),
                'default' => true,
            ),
            'interval' => array(
                'type' => 'number',
                'label' => __('Interval (seconds)', 'bazarino-app-config'),
                'default' => 5,
                'min' => 1,
                'max' => 60,
            ),
            'height' => array(
                'type' => 'number',
                'label' => __('Height (px)', 'bazarino-app-config'),
                'default' => 200,
                'min' => 100,
                'max' => 500,
            ),
            'show_indicators' => array(
                'type' => 'boolean',
                'label' => __('Show Indicators', 'bazarino-app-config'),
                'default' => true,
            ),
            'show_navigation' => array(
                'type' => 'boolean',
                'label' => __('Show Navigation Arrows', 'bazarino-app-config'),
                'default' => false,
            ),
            'border_radius' => array(
                'type' => 'number',
                'label' => __('Border Radius', 'bazarino-app-config'),
                'default' => 12,
                'min' => 0,
                'max' => 50,
            ),
        );
    }
    
    /**
     * Validate settings
     */
    public static function validate_settings($settings) {
        $validated = array();
        
        // Auto play
        $validated['auto_play'] = isset($settings['auto_play']) ? 
            (bool) $settings['auto_play'] : true;
        
        // Interval (1-60 seconds)
        $validated['interval'] = isset($settings['interval']) ? 
            max(1, min(60, (int) $settings['interval'])) : 5;
        
        // Height (100-500px)
        $validated['height'] = isset($settings['height']) ? 
            max(100, min(500, (int) $settings['height'])) : 200;
        
        // Show indicators
        $validated['show_indicators'] = isset($settings['show_indicators']) ? 
            (bool) $settings['show_indicators'] : true;
        
        // Show navigation
        $validated['show_navigation'] = isset($settings['show_navigation']) ? 
            (bool) $settings['show_navigation'] : false;
        
        // Border radius
        $validated['border_radius'] = isset($settings['border_radius']) ? 
            max(0, min(50, (int) $settings['border_radius'])) : 12;
        
        return $validated;
    }
    
    /**
     * Render settings form
     */
    public static function render_settings_form($settings = array()) {
        $settings = wp_parse_args($settings, self::get_default_settings());
        $schema = self::get_settings_schema();
        
        ob_start();
        ?>
        <div class="widget-settings slider-settings">
            <?php foreach ($schema as $key => $field): ?>
                <div class="setting-field">
                    <label for="setting-<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($field['label']); ?>
                    </label>
                    
                    <?php if ($field['type'] === 'boolean'): ?>
                        <input 
                            type="checkbox" 
                            id="setting-<?php echo esc_attr($key); ?>" 
                            name="settings[<?php echo esc_attr($key); ?>]" 
                            value="1"
                            <?php checked($settings[$key], true); ?>
                        />
                    <?php elseif ($field['type'] === 'number'): ?>
                        <input 
                            type="number" 
                            id="setting-<?php echo esc_attr($key); ?>" 
                            name="settings[<?php echo esc_attr($key); ?>]" 
                            value="<?php echo esc_attr($settings[$key]); ?>"
                            min="<?php echo esc_attr($field['min']); ?>"
                            max="<?php echo esc_attr($field['max']); ?>"
                        />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

