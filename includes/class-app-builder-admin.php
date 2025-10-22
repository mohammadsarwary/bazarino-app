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
        add_action('wp_ajax_bazarino_recreate_tables', array($this, 'ajax_recreate_tables'));
        add_action('wp_ajax_bazarino_get_table_structure', array($this, 'ajax_get_table_structure'));
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
        
        add_submenu_page(
            'bazarino-app-config',
            __('App Builder Debug', 'bazarino-app-config'),
            __('App Builder Debug', 'bazarino-app-config'),
            'manage_options',
            'bazarino-app-builder-debug',
            array($this, 'render_debug_page'),
            20
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'bazarino-app-builder') === false) { return; }
        
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
            <!-- Header -->
            <div class="bazarino-builder-header">
                <div class="bazarino-builder-header-content">
                    <div class="bazarino-builder-branding">
                        <span class="dashicons dashicons-smartphone bazarino-builder-icon"></span>
                        <h1><?php _e('Visual App Builder', 'bazarino-app-config'); ?></h1>
                        <span class="bazarino-builder-version">v1.0.0</span>
                    </div>
                    <div class="bazarino-builder-header-actions">
                        <button type="button" id="preview-app" class="bazarino-btn bazarino-btn-secondary">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('Preview App', 'bazarino-app-config'); ?>
                        </button>
                        <button type="button" id="save-all" class="bazarino-btn bazarino-btn-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Save All Changes', 'bazarino-app-config'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div id="bazarino-app-builder-notices"></div>
            
            <!-- Main Container - 3 Column Layout -->
            <div class="bazarino-builder-container">
                
                <!-- Left Sidebar: Screens Management -->
                <div class="bazarino-builder-sidebar bazarino-builder-sidebar-left">
                    <div class="bazarino-sidebar-section">
                        <div class="bazarino-section-header">
                            <h2>
                                <span class="dashicons dashicons-admin-page"></span>
                                <?php _e('Screens', 'bazarino-app-config'); ?>
                            </h2>
                            <button type="button" id="add-new-screen" class="bazarino-btn-icon" title="<?php _e('Add Screen', 'bazarino-app-config'); ?>">
                                <span class="dashicons dashicons-plus-alt"></span>
                            </button>
                        </div>
                        
                        <div class="bazarino-screens-search">
                            <input type="text" id="screens-search" placeholder="<?php _e('Search screens...', 'bazarino-app-config'); ?>" />
                            <span class="dashicons dashicons-search"></span>
                        </div>
                        
                        <div id="screens-list" class="bazarino-screens-list">
                            <div class="bazarino-loading">
                                <div class="bazarino-spinner"></div>
                                <p><?php _e('Loading screens...', 'bazarino-app-config'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bazarino-sidebar-section">
                        <div class="bazarino-section-header">
                            <h2>
                                <span class="dashicons dashicons-layout"></span>
                                <?php _e('Quick Stats', 'bazarino-app-config'); ?>
                            </h2>
                        </div>
                        <div class="bazarino-stats">
                            <div class="bazarino-stat-item">
                                <div class="bazarino-stat-value" id="total-screens">0</div>
                                <div class="bazarino-stat-label"><?php _e('Total Screens', 'bazarino-app-config'); ?></div>
                            </div>
                            <div class="bazarino-stat-item">
                                <div class="bazarino-stat-value" id="total-widgets">0</div>
                                <div class="bazarino-stat-label"><?php _e('Total Widgets', 'bazarino-app-config'); ?></div>
                            </div>
                            <div class="bazarino-stat-item">
                                <div class="bazarino-stat-value" id="active-screens">0</div>
                                <div class="bazarino-stat-label"><?php _e('Active Screens', 'bazarino-app-config'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Center: Canvas/Builder Area -->
                <div class="bazarino-builder-canvas">
                    <!-- Welcome State -->
                    <div id="welcome-state" class="bazarino-welcome-state">
                        <div class="bazarino-welcome-content">
                            <span class="dashicons dashicons-smartphone bazarino-welcome-icon"></span>
                            <h2><?php _e('Welcome to Visual App Builder', 'bazarino-app-config'); ?></h2>
                            <p><?php _e('Create beautiful mobile apps without writing any code!', 'bazarino-app-config'); ?></p>
                            <button type="button" class="bazarino-btn bazarino-btn-primary bazarino-btn-large" id="create-first-screen">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('Create Your First Screen', 'bazarino-app-config'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Screen Builder -->
                    <div id="screen-builder" class="bazarino-screen-builder" style="display: none;">
                        <div class="bazarino-builder-toolbar">
                            <div class="bazarino-toolbar-left">
                                <button type="button" id="close-builder" class="bazarino-btn-icon" title="<?php _e('Close', 'bazarino-app-config'); ?>">
                                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                                </button>
                                <div class="bazarino-screen-info">
                                    <input type="text" id="screen-name" class="bazarino-screen-name-input" placeholder="<?php _e('Screen Name', 'bazarino-app-config'); ?>" />
                                    <span class="bazarino-screen-route" id="screen-route-display"></span>
                                </div>
                            </div>
                            <div class="bazarino-toolbar-right">
                                <button type="button" id="screen-settings-toggle" class="bazarino-btn-icon" title="<?php _e('Settings', 'bazarino-app-config'); ?>">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </button>
                                <button type="button" id="preview-screen" class="bazarino-btn bazarino-btn-secondary">
                                    <span class="dashicons dashicons-smartphone"></span>
                                    <?php _e('Preview', 'bazarino-app-config'); ?>
                                </button>
                                <button type="button" id="save-screen" class="bazarino-btn bazarino-btn-primary">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Save Screen', 'bazarino-app-config'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Screen Settings Panel (Sliding) -->
                        <div id="screen-settings-panel" class="bazarino-settings-panel" style="display: none;">
                            <div class="bazarino-settings-header">
                                <h3><?php _e('Screen Settings', 'bazarino-app-config'); ?></h3>
                                <button type="button" class="bazarino-btn-icon bazarino-close-settings">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>
                            <div class="bazarino-settings-content">
                                <div class="bazarino-setting-group">
                                    <label><?php _e('Screen Route', 'bazarino-app-config'); ?></label>
                                    <input type="text" id="screen-route" placeholder="/home" />
                                    <small><?php _e('URL path for this screen', 'bazarino-app-config'); ?></small>
                                </div>
                                
                                <div class="bazarino-setting-group">
                                    <label><?php _e('Screen Type', 'bazarino-app-config'); ?></label>
                                    <select id="screen-type">
                                        <option value="custom"><?php _e('Custom', 'bazarino-app-config'); ?></option>
                                        <option value="home"><?php _e('Home', 'bazarino-app-config'); ?></option>
                                        <option value="category"><?php _e('Category', 'bazarino-app-config'); ?></option>
                                        <option value="product"><?php _e('Product List', 'bazarino-app-config'); ?></option>
                                        <option value="search"><?php _e('Search', 'bazarino-app-config'); ?></option>
                                        <option value="profile"><?php _e('Profile', 'bazarino-app-config'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="bazarino-setting-group">
                                    <label><?php _e('Layout Type', 'bazarino-app-config'); ?></label>
                                    <div class="bazarino-layout-options">
                                        <label class="bazarino-layout-option">
                                            <input type="radio" name="screen_layout" value="scroll" checked />
                                            <span class="bazarino-layout-card">
                                                <span class="dashicons dashicons-menu-alt"></span>
                                                <span><?php _e('Scroll', 'bazarino-app-config'); ?></span>
                                            </span>
                                        </label>
                                        <label class="bazarino-layout-option">
                                            <input type="radio" name="screen_layout" value="grid" />
                                            <span class="bazarino-layout-card">
                                                <span class="dashicons dashicons-grid-view"></span>
                                                <span><?php _e('Grid', 'bazarino-app-config'); ?></span>
                                            </span>
                                        </label>
                                        <label class="bazarino-layout-option">
                                            <input type="radio" name="screen_layout" value="list" />
                                            <span class="bazarino-layout-card">
                                                <span class="dashicons dashicons-list-view"></span>
                                                <span><?php _e('List', 'bazarino-app-config'); ?></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="bazarino-setting-group">
                                    <label class="bazarino-toggle-label">
                                        <input type="checkbox" id="screen-status" />
                                        <span class="bazarino-toggle-switch"></span>
                                        <span><?php _e('Active Screen', 'bazarino-app-config'); ?></span>
                                    </label>
                                    <small><?php _e('Enable or disable this screen in the app', 'bazarino-app-config'); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Canvas: Phone Preview -->
                        <div class="bazarino-canvas-area">
                            <div class="bazarino-phone-frame">
                                <div class="bazarino-phone-notch"></div>
                                <div class="bazarino-phone-content">
                                    <div class="bazarino-phone-statusbar">
                                        <span class="bazarino-time">9:41</span>
                                        <div class="bazarino-phone-icons">
                                            <span class="dashicons dashicons-smartphone"></span>
                                            <span class="dashicons dashicons-wifi"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="bazarino-phone-appbar">
                                        <span class="bazarino-appbar-title" id="canvas-screen-name"><?php _e('Screen Name', 'bazarino-app-config'); ?></span>
                                    </div>
                                    
                                    <div id="widgets-dropzone" class="bazarino-widgets-dropzone">
                                        <div class="bazarino-dropzone-placeholder">
                                            <span class="dashicons dashicons-admin-customizer"></span>
                                            <p><?php _e('Drag widgets here to build your screen', 'bazarino-app-config'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bazarino-phone-bottom"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Sidebar: Widgets Library & Properties -->
                <div class="bazarino-builder-sidebar bazarino-builder-sidebar-right">
                    <div class="bazarino-sidebar-tabs">
                        <button type="button" class="bazarino-tab-btn active" data-tab="widgets">
                            <span class="dashicons dashicons-editor-table"></span>
                            <?php _e('Widgets', 'bazarino-app-config'); ?>
                        </button>
                        <button type="button" class="bazarino-tab-btn" data-tab="properties">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Properties', 'bazarino-app-config'); ?>
                        </button>
                    </div>
                    
                    <!-- Widgets Library Tab -->
                    <div id="widgets-tab" class="bazarino-tab-content active">
                        <div class="bazarino-widgets-search">
                            <input type="text" id="widgets-search" placeholder="<?php _e('Search widgets...', 'bazarino-app-config'); ?>" />
                            <span class="dashicons dashicons-search"></span>
                        </div>
                        
                        <div id="widgets-library" class="bazarino-widgets-library">
                            <div class="bazarino-loading">
                                <div class="bazarino-spinner"></div>
                                <p><?php _e('Loading widgets...', 'bazarino-app-config'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Widget Properties Tab -->
                    <div id="properties-tab" class="bazarino-tab-content">
                        <div id="widget-properties" class="bazarino-widget-properties">
                            <div class="bazarino-properties-empty">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <p><?php _e('Select a widget to edit its properties', 'bazarino-app-config'); ?></p>
                            </div>
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
     * Render debug page
     */
    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $debug = Bazarino_App_Builder_Debug::get_instance();
        $status = $debug->get_debug_info();
        
        ?>
        <div class="wrap bazarino-app-builder-debug">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="bazarino-debug-actions">
                <button type="button" id="check-status" class="button button-primary">
                    <?php _e('Check System Status', 'bazarino-app-config'); ?>
                </button>
                <button type="button" id="recreate-tables" class="button">
                    <?php _e('Recreate Database Tables', 'bazarino-app-config'); ?>
                </button>
            </div>
            
            <div id="bazarino-debug-notices"></div>
            
            <div class="bazarino-debug-sections">
                <!-- Database Status -->
                <div class="bazarino-debug-section">
                    <h2><?php _e('Database Status', 'bazarino-app-config'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Table', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Status', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Rows', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Actions', 'bazarino-app-config'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($status['database'])): ?>
                                <?php foreach ($status['database'] as $table_name => $table_info): ?>
                                    <tr>
                                        <td><?php echo esc_html($table_name); ?></td>
                                        <td>
                                            <?php if ($table_info['exists']): ?>
                                                <span class="status-active"><?php _e('Exists', 'bazarino-app-config'); ?></span>
                                            <?php else: ?>
                                                <span class="status-error"><?php _e('Missing', 'bazarino-app-config'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($table_info['exists']): ?>
                                                <?php echo esc_html($table_info['row_count']); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($table_info['exists']): ?>
                                                <button type="button" class="button button-small view-table" data-table="<?php echo esc_attr($table_name); ?>">
                                                    <?php _e('View Structure', 'bazarino-app-config'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- API Status -->
                <div class="bazarino-debug-section">
                    <h2><?php _e('API Status', 'bazarino-app-config'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Component', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Status', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Details', 'bazarino-app-config'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($status['api'])): ?>
                                <?php foreach ($status['api'] as $component => $info): ?>
                                    <tr>
                                        <td><?php echo esc_html($component); ?></td>
                                        <td>
                                            <?php if ($info['working']): ?>
                                                <span class="status-active"><?php _e('Working', 'bazarino-app-config'); ?></span>
                                            <?php else: ?>
                                                <span class="status-error"><?php _e('Error', 'bazarino-app-config'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($info['error'])): ?>
                                                <?php echo esc_html($info['error']); ?>
                                            <?php elseif (isset($info['status_code'])): ?>
                                                <?php _e('Status Code:', 'bazarino-app-config'); ?> <?php echo esc_html($info['status_code']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- File Permissions -->
                <div class="bazarino-debug-section">
                    <h2><?php _e('File Permissions', 'bazarino-app-config'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('File', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Exists', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Readable', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Writable', 'bazarino-app-config'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($status['permissions'])): ?>
                                <?php foreach ($status['permissions'] as $file_name => $file_info): ?>
                                    <tr>
                                        <td><?php echo esc_html($file_name); ?></td>
                                        <td>
                                            <?php if ($file_info['exists']): ?>
                                                <span class="status-active"><?php _e('Yes', 'bazarino-app-config'); ?></span>
                                            <?php else: ?>
                                                <span class="status-error"><?php _e('No', 'bazarino-app-config'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($file_info['readable']) && $file_info['readable']): ?>
                                                <span class="status-active"><?php _e('Yes', 'bazarino-app-config'); ?></span>
                                            <?php else: ?>
                                                <span class="status-error"><?php _e('No', 'bazarino-app-config'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($file_info['writable']) && $file_info['writable']): ?>
                                                <span class="status-active"><?php _e('Yes', 'bazarino-app-config'); ?></span>
                                            <?php else: ?>
                                                <span class="status-error"><?php _e('No', 'bazarino-app-config'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Table Structure Modal -->
            <div id="table-structure-modal" class="bazarino-modal" style="display: none;">
                <div class="bazarino-modal-content">
                    <div class="bazarino-modal-header">
                        <h3><?php _e('Table Structure', 'bazarino-app-config'); ?></h3>
                        <button type="button" class="bazarino-modal-close">&times;</button>
                    </div>
                    <div class="bazarino-modal-body">
                        <div id="table-structure-content">
                            <!-- Table structure will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .bazarino-app-builder-debug {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .bazarino-debug-actions {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        
        .bazarino-debug-sections {
            margin-top: 20px;
        }
        
        .bazarino-debug-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        }
        
        .bazarino-debug-section h2 {
            margin: 0;
            padding: 12px 15px;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 1px solid #ccd0d4;
            background: #f8f9f9;
        }
        
        .bazarino-debug-section table {
            margin: 0;
            border: none;
        }
        
        .status-active {
            color: #46b450;
            font-weight: 600;
        }
        
        .status-error {
            color: #dc3232;
            font-weight: 600;
        }
        
        .bazarino-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .bazarino-modal-content {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .bazarino-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bazarino-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .bazarino-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .bazarino-modal-close:hover {
            background: #f0f0f1;
        }
        
        .bazarino-modal-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .bazarino-modal-body table {
            margin: 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Check status button
            $('#check-status').on('click', function() {
                location.reload();
            });
            
            // Recreate tables button
            $('#recreate-tables').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to recreate all database tables? This will delete all existing data.', 'bazarino-app-config'); ?>')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bazarino_recreate_tables',
                            nonce: '<?php echo wp_create_nonce('bazarino_recreate_tables_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#bazarino-debug-notices').html('<div class="notice notice-success"><p><?php _e('Database tables recreated successfully!', 'bazarino-app-config'); ?></p></div>');
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                $('#bazarino-debug-notices').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                            }
                        },
                        error: function() {
                            $('#bazarino-debug-notices').html('<div class="notice notice-error"><p><?php _e('Failed to recreate tables. Please try again.', 'bazarino-app-config'); ?></p></div>');
                        }
                    });
                }
            });
            
            // View table structure
            $('.view-table').on('click', function() {
                var tableName = $(this).data('table');
                var $modal = $('#table-structure-modal');
                var $content = $('#table-structure-content');
                
                $content.html('<p><?php _e('Loading...', 'bazarino-app-config'); ?></p>');
                $modal.show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bazarino_get_table_structure',
                        table: tableName,
                        nonce: '<?php echo wp_create_nonce('bazarino_get_table_structure_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $content.html(response.data);
                        } else {
                            $content.html('<p><?php _e('Failed to load table structure.', 'bazarino-app-config'); ?></p>');
                        }
                    },
                    error: function() {
                        $content.html('<p><?php _e('Failed to load table structure.', 'bazarino-app-config'); ?></p>');
                    }
                });
            });
            
            // Modal close
            $('.bazarino-modal-close').on('click', function() {
                $(this).closest('.bazarino-modal').hide();
            });
            
            $('.bazarino-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        });
        </script>
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
    
    /**
     * AJAX: Recreate tables
     */
    public function ajax_recreate_tables() {
        check_ajax_referer('bazarino_recreate_tables_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $debug = Bazarino_App_Builder_Debug::get_instance();
        $result = $debug->recreate_tables();
        
        if ($result) {
            wp_send_json_success(__('Database tables recreated successfully', 'bazarino-app-config'));
        } else {
            wp_send_json_error(__('Failed to recreate database tables', 'bazarino-app-config'));
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Get table structure
     */
    public function ajax_get_table_structure() {
        check_ajax_referer('bazarino_get_table_structure_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $table_name = isset($_POST['table']) ? sanitize_text_field($_POST['table']) : '';
        
        if (empty($table_name)) {
            wp_send_json_error(__('Table name is required', 'bazarino-app-config'));
        }
        
        global $wpdb;
        $debug = Bazarino_App_Builder_Debug::get_instance();
        $status = $debug->get_debug_info();
        
        if (!isset($status['database'][$table_name]) || !$status['database'][$table_name]['exists']) {
            wp_send_json_error(__('Table does not exist', 'bazarino-app-config'));
        }
        
        $table_info = $status['database'][$table_name];
        $columns = $table_info['columns'];
        
        $html = '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr><th>' . __('Column', 'bazarino-app-config') . '</th><th>' . __('Type', 'bazarino-app-config') . '</th><th>' . __('Null', 'bazarino-app-config') . '</th><th>' . __('Key', 'bazarino-app-config') . '</th><th>' . __('Default', 'bazarino-app-config') . '</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($columns as $column) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($column->Field) . '</td>';
            $html .= '<td>' . esc_html($column->Type) . '</td>';
            $html .= '<td>' . esc_html($column->Null) . '</td>';
            $html .= '<td>' . esc_html($column->Key) . '</td>';
            $html .= '<td>' . esc_html($column->Default) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        wp_send_json_success($html);
        wp_die();
    }
}