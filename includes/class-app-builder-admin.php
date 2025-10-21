<?php
/**
 * App Builder Admin Panel Class
 * 
 * Creates WordPress admin interface for the App Builder functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_App_Builder_Admin {
    
    private static $instance = null;
    private $app_builder_api;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->app_builder_api = Bazarino_App_Builder_API::get_instance();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_bazarino_get_screens', array($this, 'ajax_get_screens'));
        add_action('wp_ajax_bazarino_save_screen', array($this, 'ajax_save_screen'));
        add_action('wp_ajax_bazarino_delete_screen', array($this, 'ajax_delete_screen'));
        add_action('wp_ajax_bazarino_get_widgets', array($this, 'ajax_get_widgets'));
        add_action('wp_ajax_bazarino_save_widget', array($this, 'ajax_save_widget'));
        add_action('wp_ajax_bazarino_delete_widget', array($this, 'ajax_delete_widget'));
        add_action('wp_ajax_bazarino_reorder_widgets', array($this, 'ajax_reorder_widgets'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'bazarino-app-config',
            __('App Builder', 'bazarino-app-config'),
            __('App Builder', 'bazarino-app-config'),
            'manage_options',
            'bazarino-app-builder',
            array($this, 'render_admin_page'),
            10
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('bazarino-app-config_page_bazarino-app-builder' !== $hook) {
            return;
        }
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Enqueue jQuery UI Sortable
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue custom admin styles
        wp_enqueue_style(
            'bazarino-app-builder-css',
            BAZARINO_APP_CONFIG_PLUGIN_URL . 'admin/css/app-builder-style.css',
            array('wp-color-picker'),
            BAZARINO_APP_CONFIG_VERSION
        );
        
        // Enqueue custom admin script
        wp_enqueue_script(
            'bazarino-app-builder-js',
            BAZARINO_APP_CONFIG_PLUGIN_URL . 'admin/js/app-builder-script.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'wp-color-picker'),
            BAZARINO_APP_CONFIG_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('bazarino-app-builder-js', 'bazarinoAppBuilder', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bazarino_app_builder_nonce'),
            'strings' => array(
                'confirm_delete_screen' => __('Are you sure you want to delete this screen?', 'bazarino-app-config'),
                'confirm_delete_widget' => __('Are you sure you want to delete this widget?', 'bazarino-app-config'),
                'screen_name_required' => __('Screen name is required', 'bazarino-app-config'),
                'screen_route_required' => __('Screen route is required', 'bazarino-app-config'),
                'widget_title_required' => __('Widget title is required', 'bazarino-app-config'),
                'save_success' => __('Saved successfully!', 'bazarino-app-config'),
                'save_error' => __('Error saving data. Please try again.', 'bazarino-app-config'),
            ),
            'widget_types' => array(
                'slider' => __('Slider', 'bazarino-app-config'),
                'banner' => __('Banner', 'bazarino-app-config'),
                'categories' => __('Categories', 'bazarino-app-config'),
                'products' => __('Products', 'bazarino-app-config'),
                'flash_sale' => __('Flash Sale', 'bazarino-app-config'),
                'text_block' => __('Text Block', 'bazarino-app-config'),
                'image_block' => __('Image Block', 'bazarino-app-config'),
                'featured_products' => __('Featured Products', 'bazarino-app-config'),
                'recent_products' => __('Recent Products', 'bazarino-app-config'),
                'sale_products' => __('Sale Products', 'bazarino-app-config'),
            )
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap bazarino-app-builder">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div id="bazarino-app-builder-notices"></div>
            
            <div class="bazarino-app-builder-container">
                <div class="bazarino-app-builder-sidebar">
                    <div class="bazarino-app-builder-section">
                        <h2><?php _e('Screens', 'bazarino-app-config'); ?></h2>
                        <div class="bazarino-app-builder-actions">
                            <button type="button" id="add-new-screen" class="button button-primary">
                                <?php _e('Add New Screen', 'bazarino-app-config'); ?>
                            </button>
                        </div>
                        <div id="screens-list" class="bazarino-screens-list">
                            <!-- Screens will be loaded here via AJAX -->
                        </div>
                    </div>
                    
                    <div class="bazarino-app-builder-section">
                        <h2><?php _e('Widgets Library', 'bazarino-app-config'); ?></h2>
                        <div id="widgets-library" class="bazarino-widgets-library">
                            <!-- Widgets will be loaded here via AJAX -->
                        </div>
                    </div>
                </div>
                
                <div class="bazarino-app-builder-main">
                    <div id="screen-builder" class="bazarino-screen-builder" style="display: none;">
                        <div class="bazarino-screen-builder-header">
                            <h2 id="screen-title"><?php _e('Screen Builder', 'bazarino-app-config'); ?></h2>
                            <div class="bazarino-screen-builder-actions">
                                <button type="button" id="save-screen" class="button button-primary">
                                    <?php _e('Save Screen', 'bazarino-app-config'); ?>
                                </button>
                                <button type="button" id="preview-screen" class="button">
                                    <?php _e('Preview', 'bazarino-app-config'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="bazarino-screen-builder-content">
                            <div class="bazarino-screen-settings">
                                <h3><?php _e('Screen Settings', 'bazarino-app-config'); ?></h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="screen-name"><?php _e('Screen Name', 'bazarino-app-config'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="screen-name" name="screen_name" class="regular-text" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="screen-route"><?php _e('Screen Route', 'bazarino-app-config'); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="screen-route" name="screen_route" class="regular-text" />
                                            <p class="description"><?php _e('e.g. /home, /products, /categories', 'bazarino-app-config'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="screen-type"><?php _e('Screen Type', 'bazarino-app-config'); ?></label>
                                        </th>
                                        <td>
                                            <select id="screen-type" name="screen_type">
                                                <option value="custom"><?php _e('Custom', 'bazarino-app-config'); ?></option>
                                                <option value="home"><?php _e('Home', 'bazarino-app-config'); ?></option>
                                                <option value="category"><?php _e('Category', 'bazarino-app-config'); ?></option>
                                                <option value="product"><?php _e('Product', 'bazarino-app-config'); ?></option>
                                                <option value="search"><?php _e('Search', 'bazarino-app-config'); ?></option>
                                                <option value="profile"><?php _e('Profile', 'bazarino-app-config'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="screen-layout"><?php _e('Layout', 'bazarino-app-config'); ?></label>
                                        </th>
                                        <td>
                                            <select id="screen-layout" name="screen_layout">
                                                <option value="scroll"><?php _e('Scroll', 'bazarino-app-config'); ?></option>
                                                <option value="grid"><?php _e('Grid', 'bazarino-app-config'); ?></option>
                                                <option value="list"><?php _e('List', 'bazarino-app-config'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="screen-status"><?php _e('Status', 'bazarino-app-config'); ?></label>
                                        </th>
                                        <td>
                                            <select id="screen-status" name="screen_status">
                                                <option value="active"><?php _e('Active', 'bazarino-app-config'); ?></option>
                                                <option value="inactive"><?php _e('Inactive', 'bazarino-app-config'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="bazarino-widgets-container">
                                <h3><?php _e('Widgets', 'bazarino-app-config'); ?></h3>
                                <div class="bazarino-widgets-dropzone" id="widgets-dropzone">
                                    <div class="bazarino-dropzone-placeholder">
                                        <?php _e('Drag widgets here to add them to the screen', 'bazarino-app-config'); ?>
                                    </div>
                                    <div id="screen-widgets" class="bazarino-screen-widgets">
                                        <!-- Screen widgets will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="welcome-screen" class="bazarino-welcome-screen">
                        <div class="bazarino-welcome-content">
                            <h2><?php _e('Welcome to App Builder', 'bazarino-app-config'); ?></h2>
                            <p><?php _e('Create custom screens and widgets for your mobile app using the drag and drop interface.', 'bazarino-app-config'); ?></p>
                            <p><?php _e('Get started by creating a new screen or selecting an existing one from the sidebar.', 'bazarino-app-config'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Widget Modal -->
        <div id="widget-modal" class="bazarino-modal" style="display: none;">
            <div class="bazarino-modal-content">
                <div class="bazarino-modal-header">
                    <h3 id="widget-modal-title"><?php _e('Widget Settings', 'bazarino-app-config'); ?></h3>
                    <button type="button" class="bazarino-modal-close">&times;</button>
                </div>
                <div class="bazarino-modal-body">
                    <div id="widget-form-container">
                        <!-- Widget form will be loaded here -->
                    </div>
                </div>
                <div class="bazarino-modal-footer">
                    <button type="button" id="save-widget" class="button button-primary">
                        <?php _e('Save Widget', 'bazarino-app-config'); ?>
                    </button>
                    <button type="button" class="bazarino-modal-close button">
                        <?php _e('Cancel', 'bazarino-app-config'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Preview Modal -->
        <div id="preview-modal" class="bazarino-modal" style="display: none;">
            <div class="bazarino-modal-content bazarino-preview-modal">
                <div class="bazarino-modal-header">
                    <h3><?php _e('Screen Preview', 'bazarino-app-config'); ?></h3>
                    <button type="button" class="bazarino-modal-close">&times;</button>
                </div>
                <div class="bazarino-modal-body">
                    <div class="bazarino-preview-container">
                        <div class="bazarino-preview-device">
                            <div id="preview-content" class="bazarino-preview-content">
                                <!-- Preview content will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Get screens
     */
    public function ajax_get_screens() {
        check_ajax_referer('bazarino_app_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $request = new WP_REST_Request('GET', '/bazarino/v1/app-builder/screens');
        $response = $this->app_builder_api->get_screens($request);
        
        if ($response->get_status() === 200) {
            $data = $response->get_data();
            wp_send_json_success($data['data']);
        } else {
            wp_send_json_error(__('Failed to load screens', 'bazarino-app-config'));
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Save screen
     */
    public function ajax_save_screen() {
        check_ajax_referer('bazarino_app_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $screen_id = isset($_POST['screen_id']) ? intval($_POST['screen_id']) : 0;
        $screen_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'route' => isset($_POST['route']) ? sanitize_text_field($_POST['route']) : '',
            'screen_type' => isset($_POST['screen_type']) ? sanitize_text_field($_POST['screen_type']) : 'custom',
            'layout' => isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'scroll',
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
        );
        
        if (empty($screen_data['name'])) {
            wp_send_json_error(__('Screen name is required', 'bazarino-app-config'));
        }
        
        if (empty($screen_data['route'])) {
            wp_send_json_error(__('Screen route is required', 'bazarino-app-config'));
        }
        
        if ($screen_id > 0) {
            // Update existing screen
            $request = new WP_REST_Request('PUT', '/bazarino/v1/app-builder/screens/' . $screen_id);
            $request->set_body_params($screen_data);
            $response = $this->app_builder_api->update_screen($request);
        } else {
            // Create new screen
            $request = new WP_REST_Request('POST', '/bazarino/v1/app-builder/screens');
            $request->set_body_params($screen_data);
            $response = $this->app_builder_api->create_screen($request);
        }
        
        if ($response->get_status() === 200 || $response->get_status() === 201) {
            $data = $response->get_data();
            wp_send_json_success($data);
        } else {
            $data = $response->get_data();
            wp_send_json_error($data['error']['message']);
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Delete screen
     */
    public function ajax_delete_screen() {
        check_ajax_referer('bazarino_app_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $screen_id = isset($_POST['screen_id']) ? intval($_POST['screen_id']) : 0;
        
        if ($screen_id <= 0) {
            wp_send_json_error(__('Invalid screen ID', 'bazarino-app-config'));
        }
        
        $request = new WP_REST_Request('DELETE', '/bazarino/v1/app-builder/screens/' . $screen_id);
        $response = $this->app_builder_api->delete_screen($request);
        
        if ($response->get_status() === 200) {
            wp_send_json_success();
        } else {
            $data = $response->get_data();
            wp_send_json_error($data['error']['message']);
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Get widgets
     */
    public function ajax_get_widgets() {
        check_ajax_referer('bazarino_app_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $screen_id = isset($_POST['screen_id']) ? intval($_POST['screen_id']) : 0;
        
        if ($screen_id > 0) {
            // Get widgets for specific screen
            $request = new WP_REST_Request('GET', '/bazarino/v1/app-builder/screens/' . $screen_id . '/widgets');
            $response = $this->app_builder_api->get_screen_widgets($request);
        } else {
            // Get all widgets
            $request = new WP_REST_Request('GET', '/bazarino/v1/app-builder/widgets');
            $response = $this->app_builder_api->get_widgets($request);
        }
        
        if ($response->get_status() === 200) {
            $data = $response->get_data();
            wp_send_json_success($data['data']);
        } else {
            wp_send_json_error(__('Failed to load widgets', 'bazarino-app-config'));
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Save widget
     */
    public function ajax_save_widget() {
        check_ajax_referer('bazarino_app_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $widget_id = isset($_POST['widget_id']) ? intval($_POST['widget_id']) : 0;
        $widget_data = array(
            'screen_id' => isset($_POST['screen_id']) ? intval($_POST['screen_id']) : 0,
            'widget_type' => isset($_POST['widget_type']) ? sanitize_text_field($_POST['widget_type']) : '',
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'config' => isset($_POST['config']) ? json_decode(stripslashes($_POST['config']), true) : array(),
            'position' => isset($_POST['position']) ? intval($_POST['position']) : 0,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
        );
        
        if (empty($widget_data['widget_type'])) {
            wp_send_json_error(__('Widget type is required', 'bazarino-app-config'));
        }
        
        if ($widget_data['screen_id'] <= 0) {
            wp_send_json_error(__('Screen ID is required', 'bazarino-app-config'));
        }
        
        if ($widget_id > 0) {
            // Update existing widget
            $request = new WP_REST_Request('PUT', '/bazarino/v1/app-builder/widgets/' . $widget_id);
            $request->set_body_params($widget_data);
            $response = $this->app_builder_api->update_widget($request);
        } else {
            // Create new widget
            $request = new WP_REST_Request('POST', '/bazarino/v1/app-builder/widgets');
            $request->set_body_params($widget_data);
            $response = $this->app_builder_api->create_widget($request);
        }
        
        if ($response->get_status() === 200 || $response->get_status() === 201) {
            $data = $response->get_data();
            wp_send_json_success($data);
        } else {
            $data = $response->get_data();
            wp_send_json_error($data['error']['message']);
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Delete widget
     */
    public function ajax_delete_widget() {
        check_ajax_referer('bazarino_app_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $widget_id = isset($_POST['widget_id']) ? intval($_POST['widget_id']) : 0;
        
        if ($widget_id <= 0) {
            wp_send_json_error(__('Invalid widget ID', 'bazarino-app-config'));
        }
        
        $request = new WP_REST_Request('DELETE', '/bazarino/v1/app-builder/widgets/' . $widget_id);
        $response = $this->app_builder_api->delete_widget($request);
        
        if ($response->get_status() === 200) {
            wp_send_json_success();
        } else {
            $data = $response->get_data();
            wp_send_json_error($data['error']['message']);
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Reorder widgets
     */
    public function ajax_reorder_widgets() {
        check_ajax_referer('bazarino_app_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $widgets = isset($_POST['widgets']) ? $_POST['widgets'] : array();
        
        if (empty($widgets) || !is_array($widgets)) {
            wp_send_json_error(__('Invalid widgets data', 'bazarino-app-config'));
        }
        
        $request = new WP_REST_Request('POST', '/bazarino/v1/app-builder/widgets/reorder');
        $request->set_body_params(array('widgets' => $widgets));
        $response = $this->app_builder_api->reorder_widgets($request);
        
        if ($response->get_status() === 200) {
            wp_send_json_success();
        } else {
            $data = $response->get_data();
            wp_send_json_error($data['error']['message']);
        }
        
        wp_die();
    }
}