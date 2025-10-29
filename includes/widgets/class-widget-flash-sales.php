<?php
/**
 * Flash Sales Widget Class
 *
 * @package Bazarino_App_Builder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flash Sales Widget
 */
class Bazarino_Widget_Flash_Sales {
    
    /**
     * Widget type identifier
     */
    const TYPE = 'flash_sales';
    
    /**
     * Get widget metadata
     */
    public static function get_metadata() {
        return array(
            'type' => self::TYPE,
            'name' => __('Flash Sales', 'bazarino-app-config'),
            'description' => __('Display products with time-limited discounts', 'bazarino-app-config'),
            'icon' => 'dashicons-tag',
            'category' => 'ecommerce',
        );
    }
    
    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'layout' => 'horizontal', // horizontal, vertical, grid
            'items_count' => 10,
            'show_timer' => true,
            'show_badge' => true,
            'auto_scroll' => true,
            'scroll_interval' => 3,
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
                'default' => 'horizontal',
                'options' => array(
                    'horizontal' => __('Horizontal', 'bazarino-app-config'),
                    'vertical' => __('Vertical', 'bazarino-app-config'),
                    'grid' => __('Grid', 'bazarino-app-config'),
                ),
            ),
            'items_count' => array(
                'type' => 'number',
                'label' => __('Items Count', 'bazarino-app-config'),
                'default' => 10,
                'min' => 2,
                'max' => 50,
            ),
            'show_timer' => array(
                'type' => 'boolean',
                'label' => __('Show Countdown Timer', 'bazarino-app-config'),
                'default' => true,
            ),
            'show_badge' => array(
                'type' => 'boolean',
                'label' => __('Show Discount Badge', 'bazarino-app-config'),
                'default' => true,
            ),
            'auto_scroll' => array(
                'type' => 'boolean',
                'label' => __('Auto Scroll', 'bazarino-app-config'),
                'default' => true,
            ),
            'scroll_interval' => array(
                'type' => 'number',
                'label' => __('Scroll Interval (seconds)', 'bazarino-app-config'),
                'default' => 3,
                'min' => 1,
                'max' => 10,
            ),
        );
    }
    
    /**
     * Validate settings
     */
    public static function validate_settings($settings) {
        $validated = array();
        
        // Layout
        $allowed_layouts = array('horizontal', 'vertical', 'grid');
        $validated['layout'] = isset($settings['layout']) && in_array($settings['layout'], $allowed_layouts) ? 
            $settings['layout'] : 'horizontal';
        
        // Items count (2-50)
        $validated['items_count'] = isset($settings['items_count']) ? 
            max(2, min(50, (int) $settings['items_count'])) : 10;
        
        // Show timer
        $validated['show_timer'] = isset($settings['show_timer']) ? 
            (bool) $settings['show_timer'] : true;
        
        // Show badge
        $validated['show_badge'] = isset($settings['show_badge']) ? 
            (bool) $settings['show_badge'] : true;
        
        // Auto scroll
        $validated['auto_scroll'] = isset($settings['auto_scroll']) ? 
            (bool) $settings['auto_scroll'] : true;
        
        // Scroll interval (1-10 seconds)
        $validated['scroll_interval'] = isset($settings['scroll_interval']) ? 
            max(1, min(10, (int) $settings['scroll_interval'])) : 3;
        
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
        <div class="widget-settings flash-sales-settings">
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

