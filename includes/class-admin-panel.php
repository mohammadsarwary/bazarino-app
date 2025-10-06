<?php
/**
 * Admin Panel Class
 * 
 * Creates WordPress admin interface for configuring the mobile app
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_Admin_Panel {
    
    private static $instance = null;
    private $config_manager;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config_manager = Bazarino_Config_Manager::get_instance();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_post_bazarino_save_config', array($this, 'save_config'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Bazarino App Config', 'bazarino-app-config'),
            __('App Config', 'bazarino-app-config'),
            'manage_options',
            'bazarino-app-config',
            array($this, 'render_admin_page'),
            'dashicons-smartphone',
            25
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bazarino_app_config', 'bazarino_homepage_config');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_bazarino-app-config' !== $hook) {
            return;
        }
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue jQuery UI Sortable
        wp_enqueue_script('jquery-ui-sortable');
        
        // Enqueue custom admin styles
        wp_enqueue_style(
            'bazarino-admin-css',
            BAZARINO_APP_CONFIG_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            BAZARINO_APP_CONFIG_VERSION
        );
        
        // Enqueue custom admin script
        wp_enqueue_script(
            'bazarino-admin-js',
            BAZARINO_APP_CONFIG_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            BAZARINO_APP_CONFIG_VERSION,
            true
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $config = $this->config_manager->get_config();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'bazarino-app-config'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="bazarino_save_config">
                <?php wp_nonce_field('bazarino_save_config', 'bazarino_config_nonce'); ?>
                
                <div class="bazarino-admin-container">
                    
                    <!-- Theme Settings -->
                    <div class="bazarino-admin-section">
                        <h2><?php _e('Theme Colors', 'bazarino-app-config'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Primary Color', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="config[theme][primary_color]" 
                                           value="<?php echo esc_attr($config['theme']['primary_color']); ?>" 
                                           class="bazarino-color-picker" />
                                    <p class="description"><?php _e('Main color for buttons and highlights', 'bazarino-app-config'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Accent Color', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="config[theme][accent_color]" 
                                           value="<?php echo esc_attr($config['theme']['accent_color']); ?>" 
                                           class="bazarino-color-picker" />
                                    <p class="description"><?php _e('Secondary color for accents', 'bazarino-app-config'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Background Color', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           name="config[theme][background_color]" 
                                           value="<?php echo esc_attr($config['theme']['background_color']); ?>" 
                                           class="bazarino-color-picker" />
                                    <p class="description"><?php _e('Main background color', 'bazarino-app-config'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Layout Settings -->
                    <div class="bazarino-admin-section">
                        <h2><?php _e('Homepage Layout', 'bazarino-app-config'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Visible Sections', 'bazarino-app-config'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" 
                                               name="config[layout][show_slider]" 
                                               value="1" 
                                               <?php checked($config['layout']['show_slider'], true); ?> />
                                        <?php _e('Show Slider', 'bazarino-app-config'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" 
                                               name="config[layout][show_categories]" 
                                               value="1" 
                                               <?php checked($config['layout']['show_categories'], true); ?> />
                                        <?php _e('Show Categories', 'bazarino-app-config'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" 
                                               name="config[layout][show_flash_sales]" 
                                               value="1" 
                                               <?php checked($config['layout']['show_flash_sales'], true); ?> />
                                        <?php _e('Show Flash Sales', 'bazarino-app-config'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" 
                                               name="config[layout][show_banners]" 
                                               value="1" 
                                               <?php checked($config['layout']['show_banners'], true); ?> />
                                        <?php _e('Show Banners', 'bazarino-app-config'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" 
                                               name="config[layout][show_popular_products]" 
                                               value="1" 
                                               <?php checked($config['layout']['show_popular_products'], true); ?> />
                                        <?php _e('Show Popular Products', 'bazarino-app-config'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Widget Order', 'bazarino-app-config'); ?></th>
                                <td>
                                    <p class="description"><?php _e('Drag to reorder widgets', 'bazarino-app-config'); ?></p>
                                    <ul id="widget-order-list" class="bazarino-sortable">
                                        <?php foreach ($config['layout']['widget_order'] as $widget): ?>
                                            <li class="bazarino-widget-item" data-widget="<?php echo esc_attr($widget); ?>">
                                                <span class="dashicons dashicons-menu"></span>
                                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $widget))); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <input type="hidden" id="widget-order-input" name="config[layout][widget_order]" value="" />
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Slider Settings -->
                    <div class="bazarino-admin-section">
                        <h2><?php _e('Slider Settings', 'bazarino-app-config'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Auto Play', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           name="config[slider][auto_play]" 
                                           value="1" 
                                           <?php checked($config['slider']['auto_play'], true); ?> />
                                    <?php _e('Enable auto-play', 'bazarino-app-config'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Interval (seconds)', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           name="config[slider][interval]" 
                                           value="<?php echo esc_attr($config['slider']['interval']); ?>" 
                                           min="1" 
                                           max="30" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Height (pixels)', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           name="config[slider][height]" 
                                           value="<?php echo esc_attr($config['slider']['height']); ?>" 
                                           min="100" 
                                           max="500" />
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Categories Settings -->
                    <div class="bazarino-admin-section">
                        <h2><?php _e('Categories Settings', 'bazarino-app-config'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Display Type', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <select name="config[categories][display_type]">
                                        <option value="grid" <?php selected($config['categories']['display_type'], 'grid'); ?>>
                                            <?php _e('Grid', 'bazarino-app-config'); ?>
                                        </option>
                                        <option value="horizontal" <?php selected($config['categories']['display_type'], 'horizontal'); ?>>
                                            <?php _e('Horizontal List', 'bazarino-app-config'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php _e('Items Per Row', 'bazarino-app-config'); ?></label>
                                </th>
                                <td>
                                    <input type="number" 
                                           name="config[categories][items_per_row]" 
                                           value="<?php echo esc_attr($config['categories']['items_per_row']); ?>" 
                                           min="2" 
                                           max="6" />
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                </div>
                
                <?php submit_button(__('Save Settings', 'bazarino-app-config')); ?>
            </form>
            
            <hr>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top: 20px;">
                <input type="hidden" name="action" value="bazarino_reset_config">
                <?php wp_nonce_field('bazarino_reset_config', 'bazarino_reset_nonce'); ?>
                <?php submit_button(
                    __('Reset to Default Settings', 'bazarino-app-config'), 
                    'secondary', 
                    'submit', 
                    false,
                    array('onclick' => 'return confirm("' . esc_js(__('Are you sure you want to reset all settings to default?', 'bazarino-app-config')) . '");')
                ); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save configuration
     */
    public function save_config() {
        check_admin_referer('bazarino_save_config', 'bazarino_config_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $config = isset($_POST['config']) ? $_POST['config'] : array();
        
        // Parse widget order JSON
        if (isset($_POST['config']['layout']['widget_order']) && !empty($_POST['config']['layout']['widget_order'])) {
            $widget_order = json_decode(stripslashes($_POST['config']['layout']['widget_order']), true);
            $config['layout']['widget_order'] = $widget_order;
        }
        
        $this->config_manager->update_config($config);
        
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=bazarino-app-config')));
        exit;
    }
}

