<?php
/**
 * Categories Grid Widget Class
 *
 * @package Bazarino_App_Builder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Categories Grid Widget
 */
class Bazarino_Widget_Categories {
    
    /**
     * Widget type identifier
     */
    const TYPE = 'categories';
    
    /**
     * Get widget metadata
     */
    public static function get_metadata() {
        return array(
            'type' => self::TYPE,
            'name' => __('Categories Grid', 'bazarino-app-config'),
            'description' => __('Display product categories in a grid layout', 'bazarino-app-config'),
            'icon' => 'dashicons-category',
            'category' => 'ecommerce',
        );
    }
    
    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'columns' => 4,
            'items_count' => 8,
            'show_count' => true,
            'hide_empty' => true,
            'layout' => 'grid', // grid, list, carousel
            'image_shape' => 'circle', // circle, rounded, square
        );
    }
    
    /**
     * Get settings schema
     */
    public static function get_settings_schema() {
        return array(
            'columns' => array(
                'type' => 'number',
                'label' => __('Columns', 'bazarino-app-config'),
                'default' => 4,
                'min' => 2,
                'max' => 6,
            ),
            'items_count' => array(
                'type' => 'number',
                'label' => __('Items Count', 'bazarino-app-config'),
                'default' => 8,
                'min' => 2,
                'max' => 50,
            ),
            'show_count' => array(
                'type' => 'boolean',
                'label' => __('Show Product Count', 'bazarino-app-config'),
                'default' => true,
            ),
            'hide_empty' => array(
                'type' => 'boolean',
                'label' => __('Hide Empty Categories', 'bazarino-app-config'),
                'default' => true,
            ),
            'layout' => array(
                'type' => 'select',
                'label' => __('Layout', 'bazarino-app-config'),
                'default' => 'grid',
                'options' => array(
                    'grid' => __('Grid', 'bazarino-app-config'),
                    'list' => __('List', 'bazarino-app-config'),
                    'carousel' => __('Carousel', 'bazarino-app-config'),
                ),
            ),
            'image_shape' => array(
                'type' => 'select',
                'label' => __('Image Shape', 'bazarino-app-config'),
                'default' => 'circle',
                'options' => array(
                    'circle' => __('Circle', 'bazarino-app-config'),
                    'rounded' => __('Rounded', 'bazarino-app-config'),
                    'square' => __('Square', 'bazarino-app-config'),
                ),
            ),
        );
    }
    
    /**
     * Validate settings
     */
    public static function validate_settings($settings) {
        $validated = array();
        
        // Columns (2-6)
        $validated['columns'] = isset($settings['columns']) ? 
            max(2, min(6, (int) $settings['columns'])) : 4;
        
        // Items count (2-50)
        $validated['items_count'] = isset($settings['items_count']) ? 
            max(2, min(50, (int) $settings['items_count'])) : 8;
        
        // Show count
        $validated['show_count'] = isset($settings['show_count']) ? 
            (bool) $settings['show_count'] : true;
        
        // Hide empty
        $validated['hide_empty'] = isset($settings['hide_empty']) ? 
            (bool) $settings['hide_empty'] : true;
        
        // Layout
        $allowed_layouts = array('grid', 'list', 'carousel');
        $validated['layout'] = isset($settings['layout']) && in_array($settings['layout'], $allowed_layouts) ? 
            $settings['layout'] : 'grid';
        
        // Image shape
        $allowed_shapes = array('circle', 'rounded', 'square');
        $validated['image_shape'] = isset($settings['image_shape']) && in_array($settings['image_shape'], $allowed_shapes) ? 
            $settings['image_shape'] : 'circle';
        
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
        <div class="widget-settings categories-settings">
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

