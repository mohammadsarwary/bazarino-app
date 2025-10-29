<?php
/**
 * Products Grid Widget Class
 *
 * @package Bazarino_App_Builder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Products Grid Widget
 */
class Bazarino_Widget_Products {
    
    /**
     * Widget type identifier
     */
    const TYPE = 'products_grid';
    
    /**
     * Get widget metadata
     */
    public static function get_metadata() {
        return array(
            'type' => self::TYPE,
            'name' => __('Products Grid', 'bazarino-app-config'),
            'description' => __('Display products in a grid layout with filters', 'bazarino-app-config'),
            'icon' => 'dashicons-products',
            'category' => 'ecommerce',
        );
    }
    
    /**
     * Get default settings
     */
    public static function get_default_settings() {
        return array(
            'columns' => 2,
            'per_page' => 10,
            'filter_type' => 'all', // all, category, tag, featured, sale, new
            'category_id' => null,
            'sort_by' => 'date', // date, popularity, rating, price
            'sort_order' => 'desc', // asc, desc
            'show_filters' => true,
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
                'default' => 2,
                'min' => 1,
                'max' => 4,
            ),
            'per_page' => array(
                'type' => 'number',
                'label' => __('Products Per Page', 'bazarino-app-config'),
                'default' => 10,
                'min' => 4,
                'max' => 50,
            ),
            'filter_type' => array(
                'type' => 'select',
                'label' => __('Filter Type', 'bazarino-app-config'),
                'default' => 'all',
                'options' => array(
                    'all' => __('All Products', 'bazarino-app-config'),
                    'category' => __('By Category', 'bazarino-app-config'),
                    'tag' => __('By Tag', 'bazarino-app-config'),
                    'featured' => __('Featured', 'bazarino-app-config'),
                    'sale' => __('On Sale', 'bazarino-app-config'),
                    'new' => __('New Arrivals', 'bazarino-app-config'),
                ),
            ),
            'category_id' => array(
                'type' => 'number',
                'label' => __('Category ID (if filter is category)', 'bazarino-app-config'),
                'default' => null,
            ),
            'sort_by' => array(
                'type' => 'select',
                'label' => __('Sort By', 'bazarino-app-config'),
                'default' => 'date',
                'options' => array(
                    'date' => __('Date', 'bazarino-app-config'),
                    'popularity' => __('Popularity', 'bazarino-app-config'),
                    'rating' => __('Rating', 'bazarino-app-config'),
                    'price' => __('Price', 'bazarino-app-config'),
                ),
            ),
            'sort_order' => array(
                'type' => 'select',
                'label' => __('Sort Order', 'bazarino-app-config'),
                'default' => 'desc',
                'options' => array(
                    'asc' => __('Ascending', 'bazarino-app-config'),
                    'desc' => __('Descending', 'bazarino-app-config'),
                ),
            ),
            'show_filters' => array(
                'type' => 'boolean',
                'label' => __('Show Filter Options', 'bazarino-app-config'),
                'default' => true,
            ),
        );
    }
    
    /**
     * Validate settings
     */
    public static function validate_settings($settings) {
        $validated = array();
        
        // Columns (1-4)
        $validated['columns'] = isset($settings['columns']) ? 
            max(1, min(4, (int) $settings['columns'])) : 2;
        
        // Per page (4-50)
        $validated['per_page'] = isset($settings['per_page']) ? 
            max(4, min(50, (int) $settings['per_page'])) : 10;
        
        // Filter type
        $allowed_filters = array('all', 'category', 'tag', 'featured', 'sale', 'new');
        $validated['filter_type'] = isset($settings['filter_type']) && in_array($settings['filter_type'], $allowed_filters) ? 
            $settings['filter_type'] : 'all';
        
        // Category ID
        $validated['category_id'] = isset($settings['category_id']) && is_numeric($settings['category_id']) ? 
            (int) $settings['category_id'] : null;
        
        // Sort by
        $allowed_sort = array('date', 'popularity', 'rating', 'price');
        $validated['sort_by'] = isset($settings['sort_by']) && in_array($settings['sort_by'], $allowed_sort) ? 
            $settings['sort_by'] : 'date';
        
        // Sort order
        $allowed_order = array('asc', 'desc');
        $validated['sort_order'] = isset($settings['sort_order']) && in_array($settings['sort_order'], $allowed_order) ? 
            $settings['sort_order'] : 'desc';
        
        // Show filters
        $validated['show_filters'] = isset($settings['show_filters']) ? 
            (bool) $settings['show_filters'] : true;
        
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
        <div class="widget-settings products-settings">
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
                            value="<?php echo esc_attr($settings[$key] ?? ''); ?>"
                            <?php if (isset($field['min'])): ?>
                                min="<?php echo esc_attr($field['min']); ?>"
                            <?php endif; ?>
                            <?php if (isset($field['max'])): ?>
                                max="<?php echo esc_attr($field['max']); ?>"
                            <?php endif; ?>
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

