<?php
/**
 * Banners Widget Class
 *
 * @package Bazarino_App_Builder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Banners Widget
 */
class Bazarino_Widget_Banners {
    
    /**
     * Widget type identifier
     */
    const TYPE = 'banners';
    
    /**
     * Get widget metadata
     */
    public static function get_metadata() {
        return array(
            'type' => self::TYPE,
            'name' => __('Banners', 'bazarino-app-config'),
            'description' => __('Display promotional banners', 'bazarino-app-config'),
            'icon' => 'dashicons-format-gallery',
            'category' => 'media',
        );
    }
    
    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'layout' => 'single', // single, double, grid
            'auto_scroll' => false,
            'scroll_interval' => 5,
            'aspect_ratio' => '16:9', // 16:9, 4:3, 1:1, 21:9
            'border_radius' => 12,
            'spacing' => 16,
        );
    }
    
    /**
     * Get settings schema
     */
    public static function get_settings_schema() {
        return array(
            'layout' => array(
                'type' => 'select',
                'label' => __('Layout', 'bazarino-app-config'),
                'default' => 'single',
                'options' => array(
                    'single' => __('Single Banner', 'bazarino-app-config'),
                    'double' => __('Double Banners', 'bazarino-app-config'),
                    'grid' => __('Grid Layout', 'bazarino-app-config'),
                ),
            ),
            'auto_scroll' => array(
                'type' => 'boolean',
                'label' => __('Auto Scroll', 'bazarino-app-config'),
                'default' => false,
            ),
            'scroll_interval' => array(
                'type' => 'number',
                'label' => __('Scroll Interval (seconds)', 'bazarino-app-config'),
                'default' => 5,
                'min' => 2,
                'max' => 30,
            ),
            'aspect_ratio' => array(
                'type' => 'select',
                'label' => __('Aspect Ratio', 'bazarino-app-config'),
                'default' => '16:9',
                'options' => array(
                    '16:9' => '16:9 (Widescreen)',
                    '4:3' => '4:3 (Standard)',
                    '1:1' => '1:1 (Square)',
                    '21:9' => '21:9 (Ultra Wide)',
                ),
            ),
            'border_radius' => array(
                'type' => 'number',
                'label' => __('Border Radius', 'bazarino-app-config'),
                'default' => 12,
                'min' => 0,
                'max' => 50,
            ),
            'spacing' => array(
                'type' => 'number',
                'label' => __('Spacing (px)', 'bazarino-app-config'),
                'default' => 16,
                'min' => 0,
                'max' => 48,
            ),
        );
    }
    
    /**
     * Validate settings
     */
    public static function validate_settings($settings) {
        $validated = array();
        
        // Layout
        $allowed_layouts = array('single', 'double', 'grid');
        $validated['layout'] = isset($settings['layout']) && in_array($settings['layout'], $allowed_layouts) ? 
            $settings['layout'] : 'single';
        
        // Auto scroll
        $validated['auto_scroll'] = isset($settings['auto_scroll']) ? 
            (bool) $settings['auto_scroll'] : false;
        
        // Scroll interval (2-30 seconds)
        $validated['scroll_interval'] = isset($settings['scroll_interval']) ? 
            max(2, min(30, (int) $settings['scroll_interval'])) : 5;
        
        // Aspect ratio
        $allowed_ratios = array('16:9', '4:3', '1:1', '21:9');
        $validated['aspect_ratio'] = isset($settings['aspect_ratio']) && in_array($settings['aspect_ratio'], $allowed_ratios) ? 
            $settings['aspect_ratio'] : '16:9';
        
        // Border radius (0-50)
        $validated['border_radius'] = isset($settings['border_radius']) ? 
            max(0, min(50, (int) $settings['border_radius'])) : 12;
        
        // Spacing (0-48)
        $validated['spacing'] = isset($settings['spacing']) ? 
            max(0, min(48, (int) $settings['spacing'])) : 16;
        
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
        <div class="widget-settings banners-settings">
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
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select 
                            id="setting-<?php echo esc_attr($key); ?>" 
                            name="settings[<?php echo esc_attr($key); ?>]"
                        >
                            <?php foreach ($field['options'] as $value => $label): ?>
                                <option 
                                    value="<?php echo esc_attr($value); ?>"
                                    <?php selected($settings[$key], $value); ?>
                                >
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

