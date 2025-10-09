<?php
/**
 * Notification Admin Panel Class
 * 
 * Creates WordPress admin interface for managing push notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class Bazarino_Notification_Admin {
    
    private static $instance = null;
    private $notification_manager;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->notification_manager = Bazarino_Notification_Manager::get_instance();
        
        add_action('admin_menu', array($this, 'add_notification_menu'));
        add_action('admin_init', array($this, 'register_notification_settings'));
        add_action('admin_post_bazarino_send_notification', array($this, 'handle_send_notification'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_notification_assets'));
    }
    
    /**
     * Add notification menu to WordPress admin
     */
    public function add_notification_menu() {
        add_submenu_page(
            'bazarino-app-config',
            __('Push Notifications', 'bazarino-app-config'),
            __('Notifications', 'bazarino-app-config'),
            'manage_options',
            'bazarino-notifications',
            array($this, 'render_notifications_page')
        );
        
        add_submenu_page(
            'bazarino-app-config',
            __('Notification Settings', 'bazarino-app-config'),
            __('Notification Settings', 'bazarino-app-config'),
            'manage_options',
            'bazarino-notification-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register notification settings
     */
    public function register_notification_settings() {
        register_setting('bazarino_notification_settings', 'bazarino_fcm_service_account');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_notification_assets($hook) {
        if (strpos($hook, 'bazarino-notification') === false) {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_style(
            'bazarino-notification-css',
            BAZARINO_APP_CONFIG_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            BAZARINO_APP_CONFIG_VERSION
        );
        
        wp_enqueue_script(
            'bazarino-notification-js',
            BAZARINO_APP_CONFIG_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery'),
            BAZARINO_APP_CONFIG_VERSION,
            true
        );
    }
    
    /**
     * Render notifications page
     */
    public function render_notifications_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $stats = $this->notification_manager->get_statistics();
        $history = $this->notification_manager->get_notifications_history(10);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Push Notifications', 'bazarino-app-config'); ?></h1>
            
            <?php if (isset($_GET['sent']) && $_GET['sent'] === 'success'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php 
                        $sent = isset($_GET['sent_count']) ? intval($_GET['sent_count']) : 0;
                        $failed = isset($_GET['failed_count']) ? intval($_GET['failed_count']) : 0;
                        printf(
                            __('Notification sent successfully! Sent: %d, Failed: %d', 'bazarino-app-config'),
                            $sent,
                            $failed
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="bazarino-stats-container" style="margin: 20px 0;">
                <div class="bazarino-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="bazarino-stat-card" style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666;"><?php _e('Total Devices', 'bazarino-app-config'); ?></h3>
                        <p style="font-size: 32px; font-weight: bold; margin: 0; color: #0073aa;"><?php echo esc_html($stats['total_devices']); ?></p>
                    </div>
                    
                    <div class="bazarino-stat-card" style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666;"><?php _e('Android Devices', 'bazarino-app-config'); ?></h3>
                        <p style="font-size: 32px; font-weight: bold; margin: 0; color: #3ddc84;"><?php echo esc_html($stats['android_devices']); ?></p>
                    </div>
                    
                    <div class="bazarino-stat-card" style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666;"><?php _e('iOS Devices', 'bazarino-app-config'); ?></h3>
                        <p style="font-size: 32px; font-weight: bold; margin: 0; color: #007aff;"><?php echo esc_html($stats['ios_devices']); ?></p>
                    </div>
                    
                    <div class="bazarino-stat-card" style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <h3 style="margin: 0 0 10px 0; color: #666;"><?php _e('Total Sent', 'bazarino-app-config'); ?></h3>
                        <p style="font-size: 32px; font-weight: bold; margin: 0; color: #46b450;"><?php echo esc_html($stats['total_sent']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Send Notification Form -->
            <div class="bazarino-admin-section" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;">
                <h2><?php _e('Send New Notification', 'bazarino-app-config'); ?></h2>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="bazarino-notification-form">
                    <input type="hidden" name="action" value="bazarino_send_notification">
                    <?php wp_nonce_field('bazarino_send_notification', 'bazarino_notification_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="notification_title"><?php _e('Title', 'bazarino-app-config'); ?> *</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="notification_title"
                                       name="notification_title" 
                                       class="regular-text" 
                                       required 
                                       placeholder="<?php esc_attr_e('Notification title', 'bazarino-app-config'); ?>" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="notification_body"><?php _e('Message', 'bazarino-app-config'); ?> *</label>
                            </th>
                            <td>
                                <textarea id="notification_body"
                                          name="notification_body" 
                                          rows="4" 
                                          class="large-text" 
                                          required
                                          placeholder="<?php esc_attr_e('Notification message', 'bazarino-app-config'); ?>"></textarea>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="notification_image"><?php _e('Image URL', 'bazarino-app-config'); ?></label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="notification_image"
                                       name="notification_image" 
                                       class="regular-text" 
                                       placeholder="<?php esc_attr_e('https://example.com/image.jpg', 'bazarino-app-config'); ?>" />
                                <button type="button" class="button" id="bazarino-upload-image"><?php _e('Upload Image', 'bazarino-app-config'); ?></button>
                                <p class="description"><?php _e('Optional: Add an image to the notification', 'bazarino-app-config'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="target_platform"><?php _e('Target Platform', 'bazarino-app-config'); ?></label>
                            </th>
                            <td>
                                <select name="target_platform" id="target_platform">
                                    <option value=""><?php _e('All Platforms', 'bazarino-app-config'); ?></option>
                                    <option value="android"><?php _e('Android Only', 'bazarino-app-config'); ?></option>
                                    <option value="ios"><?php _e('iOS Only', 'bazarino-app-config'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="notification_action"><?php _e('Action Type', 'bazarino-app-config'); ?></label>
                            </th>
                            <td>
                                <select name="notification_action" id="notification_action">
                                    <option value="none"><?php _e('No Action', 'bazarino-app-config'); ?></option>
                                    <option value="open_app"><?php _e('Open App', 'bazarino-app-config'); ?></option>
                                    <option value="open_url"><?php _e('Open URL', 'bazarino-app-config'); ?></option>
                                    <option value="open_product"><?php _e('Open Product', 'bazarino-app-config'); ?></option>
                                    <option value="open_category"><?php _e('Open Category', 'bazarino-app-config'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr id="action_value_row" style="display: none;">
                            <th scope="row">
                                <label for="action_value"><?php _e('Action Value', 'bazarino-app-config'); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="action_value"
                                       name="action_value" 
                                       class="regular-text" 
                                       placeholder="<?php esc_attr_e('Product ID, Category ID, or URL', 'bazarino-app-config'); ?>" />
                                <p class="description"><?php _e('Enter the product ID, category ID, or URL based on action type', 'bazarino-app-config'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Send Notification', 'bazarino-app-config'), 'primary', 'submit', true, array('id' => 'bazarino-send-btn')); ?>
                </form>
            </div>
            
            <!-- Notifications History -->
            <div class="bazarino-admin-section" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;">
                <h2><?php _e('Recent Notifications', 'bazarino-app-config'); ?></h2>
                
                <?php if (empty($history)): ?>
                    <p><?php _e('No notifications sent yet.', 'bazarino-app-config'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Message', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Sent', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Failed', 'bazarino-app-config'); ?></th>
                                <th><?php _e('Date', 'bazarino-app-config'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $notification): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($notification->title); ?></strong></td>
                                    <td><?php echo esc_html(wp_trim_words($notification->body, 10)); ?></td>
                                    <td><span style="color: #46b450;"><?php echo esc_html($notification->sent_count); ?></span></td>
                                    <td><span style="color: #dc3232;"><?php echo esc_html($notification->failed_count); ?></span></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->sent_at))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Upload image button
            $('#bazarino-upload-image').on('click', function(e) {
                e.preventDefault();
                var mediaUploader;
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choose Image', 'bazarino-app-config'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'bazarino-app-config'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#notification_image').val(attachment.url);
                });
                
                mediaUploader.open();
            });
            
            // Show/hide action value field
            $('#notification_action').on('change', function() {
                var val = $(this).val();
                if (val === 'none' || val === 'open_app') {
                    $('#action_value_row').hide();
                } else {
                    $('#action_value_row').show();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Notification Settings', 'bazarino-app-config'); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'bazarino-app-config'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('bazarino_notification_settings'); ?>
                
                <div class="bazarino-admin-section" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;">
                    <h2><?php _e('Firebase Cloud Messaging (FCM) HTTP v1', 'bazarino-app-config'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="bazarino_fcm_service_account"><?php _e('Service Account JSON', 'bazarino-app-config'); ?></label>
                            </th>
                            <td>
                                <textarea id="bazarino_fcm_service_account"
                                          name="bazarino_fcm_service_account" 
                                          rows="10" 
                                          class="large-text code" 
                                          placeholder="<?php esc_attr_e('Paste your Service Account JSON here', 'bazarino-app-config'); ?>"><?php echo esc_textarea(get_option('bazarino_fcm_service_account')); ?></textarea>
                                <p class="description">
                                    <?php _e('Get your Service Account JSON from Firebase Console > Project Settings > Service Accounts', 'bazarino-app-config'); ?>
                                    <br>
                                    <a href="https://console.firebase.google.com/" target="_blank"><?php _e('Open Firebase Console', 'bazarino-app-config'); ?></a>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="bazarino-info-box" style="background: #e7f5fe; border-left: 4px solid #0073aa; padding: 15px; margin-top: 20px;">
                        <h3 style="margin-top: 0;"><?php _e('How to get Service Account JSON:', 'bazarino-app-config'); ?></h3>
                        <ol style="margin: 10px 0 0 20px;">
                            <li><?php _e('Go to Firebase Console', 'bazarino-app-config'); ?></li>
                            <li><?php _e('Select your project', 'bazarino-app-config'); ?></li>
                            <li><?php _e('Click on Settings (gear icon) > Project Settings', 'bazarino-app-config'); ?></li>
                            <li><?php _e('Go to "Service accounts" tab', 'bazarino-app-config'); ?></li>
                            <li><?php _e('Click "Generate new private key"', 'bazarino-app-config'); ?></li>
                            <li><?php _e('Download the JSON file and paste its content here', 'bazarino-app-config'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="bazarino-warning-box" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 20px;">
                        <h3 style="margin-top: 0;"><?php _e('Important Security Note:', 'bazarino-app-config'); ?></h3>
                        <p style="margin: 10px 0 0 0;">
                            <?php _e('The Service Account JSON contains sensitive information. Make sure your WordPress site is secure and only authorized users can access this admin panel.', 'bazarino-app-config'); ?>
                        </p>
                    </div>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle send notification form submission
     */
    public function handle_send_notification() {
        check_admin_referer('bazarino_send_notification', 'bazarino_notification_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }
        
        $title = sanitize_text_field($_POST['notification_title']);
        $body = sanitize_textarea_field($_POST['notification_body']);
        $image_url = isset($_POST['notification_image']) ? esc_url_raw($_POST['notification_image']) : null;
        $platform = isset($_POST['target_platform']) && !empty($_POST['target_platform']) ? sanitize_text_field($_POST['target_platform']) : null;
        
        // Prepare data payload
        $data = array();
        if (isset($_POST['notification_action']) && $_POST['notification_action'] !== 'none') {
            $data['action'] = sanitize_text_field($_POST['notification_action']);
            if (isset($_POST['action_value']) && !empty($_POST['action_value'])) {
                $data['action_value'] = sanitize_text_field($_POST['action_value']);
            }
        }
        
        $result = $this->notification_manager->send_notification(
            $title,
            $body,
            $image_url,
            !empty($data) ? $data : null,
            'all',
            null,
            $platform
        );
        
        $redirect_args = array('page' => 'bazarino-notifications');
        
        if ($result['success']) {
            $redirect_args['sent'] = 'success';
            $redirect_args['sent_count'] = $result['sent'];
            $redirect_args['failed_count'] = $result['failed'];
        } else {
            $redirect_args['sent'] = 'error';
        }
        
        wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }
}

