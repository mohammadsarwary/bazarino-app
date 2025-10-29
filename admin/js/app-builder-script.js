/**
 * Bazarino Visual App Builder - JavaScript Enhanced
 * Version: 2.1.0
 * Professional drag & drop interface with advanced features
 */

jQuery(document).ready(function($) {
    'use strict';
    
    /* ===============================================
       GLOBAL VARIABLES
       =============================================== */
    let currentScreen = null;
    let currentWidgets = [];
    let selectedWidget = null;
    let widgetIdCounter = 0;
    
    /* ===============================================
       INITIALIZATION
       =============================================== */
    function init() {
        loadScreens();
        loadWidgetsLibrary();
        initEventHandlers();
        initDragAndDrop();
        initSortable();
        initTabs();
        initColorPickers();
    }
    
    /* ===============================================
       EVENT HANDLERS
       =============================================== */
    function initEventHandlers() {
        // Header actions
        $('#save-all').on('click', handleSaveAll);
        $('#preview-app').on('click', handlePreviewApp);
        $('#export-config').on('click', handleExportConfig);
        $('#import-config').on('click', handleImportConfig);
        
        // Screen management
        $('#add-new-screen, #create-first-screen').on('click', handleAddScreen);
        $('#close-builder').on('click', handleCloseBuilder);
        $('#save-screen').on('click', handleSaveScreen);
        $('#preview-screen').on('click', handlePreviewScreen);
        
        // Settings panel
        $('#screen-settings-toggle').on('click', toggleSettingsPanel);
        $('.bazarino-close-settings').on('click', closeSettingsPanel);
        
        // Screen name input - live update
        $('#screen-name').on('input', function() {
            const name = $(this).val() || 'Screen Name';
            $('#canvas-screen-name').text(name);
            updateScreenRoute();
        });
        
        // Screen route input
        $('#screen-route').on('input', function() {
            $('#screen-route-display').text($(this).val() || '/route');
        });
        
        // Search
        $('#screens-search').on('input', handleScreensSearch);
        $('#widgets-search').on('input', handleWidgetsSearch);
        
        // Click outside settings panel
        $(document).on('click', function(e) {
            if ($('#screen-settings-panel').hasClass('active') && 
                !$(e.target).closest('#screen-settings-panel, #screen-settings-toggle').length) {
                closeSettingsPanel();
            }
        });
        
        // Modal close
        $('.bazarino-modal-close').on('click', function() {
            $(this).closest('.bazarino-modal').fadeOut();
        });
        
        // Import file handling
        $('#import-file').on('change', handleImportFileSelect);
        $('#confirm-import').on('click', handleConfirmImport);
    }
    
    /* ===============================================
       TAB SWITCHING
       =============================================== */
    function initTabs() {
        $('.bazarino-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            
            // Update buttons
            $('.bazarino-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update content
            $('.bazarino-tab-content').removeClass('active');
            $('#' + tab + '-tab').addClass('active');
        });
    }
    
    /* ===============================================
       COLOR PICKERS
       =============================================== */
    function initColorPickers() {
        $(document).on('focus', '.bazarino-color-picker', function() {
            if (!$(this).hasClass('wp-color-picker-initialized')) {
                $(this).wpColorPicker({
                    change: function(event, ui) {
                        $(this).trigger('change');
                    }
                });
                $(this).addClass('wp-color-picker-initialized');
            }
        });
    }
    
    /* ===============================================
       SORTABLE WIDGETS
       =============================================== */
    function initSortable() {
        $('#widgets-dropzone').sortable({
            items: '.bazarino-canvas-widget',
            handle: '.bazarino-widget-header',
            placeholder: 'bazarino-widget-placeholder',
            tolerance: 'pointer',
            cursor: 'move',
            update: function(event, ui) {
                updateWidgetOrder();
            }
        });
    }
    
    function updateWidgetOrder() {
        const $widgets = $('#widgets-dropzone .bazarino-canvas-widget');
        const order = [];
        
        $widgets.each(function(index) {
            const widgetId = $(this).data('widget-id');
            const widget = currentWidgets.find(w => w.widget_id == widgetId);
            if (widget) {
                widget.sort_order = index;
                order.push({
                    widget_id: widgetId,
                    sort_order: index
                });
            }
        });
        
        // Auto-save order
        if (currentScreen && order.length > 0) {
            $.ajax({
                url: bazarinoAppBuilder.ajax_url,
                type: 'POST',
                data: {
                    action: 'bazarino_reorder_widgets',
                    nonce: bazarinoAppBuilder.nonce,
                    screen_id: currentScreen.screen_id,
                    order: JSON.stringify(order)
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', 'Widget order updated', 2000);
                    }
                }
            });
        }
    }
    
    /* ===============================================
       LOAD SCREENS
       =============================================== */
    function loadScreens() {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_screens',
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderScreensList(response.data || []);
                    updateStats(response.data || []);
                } else {
                    showNotice('error', response.data || 'Failed to load screens');
                }
            },
            error: function(xhr, status, error) {
                console.error('Load screens error:', error);
                showNotice('error', 'Failed to load screens. Please try again.');
            }
        });
    }
    
    function renderScreensList(screens) {
        const $list = $('#screens-list');
        $list.empty();
        
        if (!screens || screens.length === 0) {
            $list.html(`
                <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                    <span class="dashicons dashicons-admin-page" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 12px;"></span>
                    <p style="margin: 0; font-size: 13px;">No screens yet.<br>Create your first one!</p>
                </div>
            `);
            return;
        }
        
        screens.forEach(function(screen) {
            const statusClass = screen.is_active ? 'active' : 'inactive';
            const statusText = screen.is_active ? 'Active' : 'Inactive';
            
            const $item = $(`
                <div class="bazarino-screen-item" data-screen-id="${screen.screen_id}">
                    <div>
                        <div class="screen-name">${escapeHtml(screen.name)}</div>
                        <div class="screen-route">${escapeHtml(screen.route || '/screen')}</div>
                    </div>
                    <span class="screen-status ${statusClass}">${statusText}</span>
                </div>
            `);
            
            $item.on('click', function() {
                loadScreen(screen.screen_id);
            });
            
            $list.append($item);
        });
    }
    
    function loadScreen(screenId) {
        console.log('Loading screen:', screenId);
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_screen',
                nonce: bazarinoAppBuilder.nonce,
                screen_id: screenId
            },
            success: function(response) {
                console.log('Load screen response:', response);
                
                if (response.success && response.data) {
                    currentScreen = response.data;
                    renderScreenBuilder(response.data);
                    loadScreenWidgets(screenId);
                } else {
                    console.error('Failed to load screen:', response);
                    showNotice('error', response.data || 'Failed to load screen');
                }
            },
            error: function(xhr, status, error) {
                console.error('Load screen error:', {xhr, status, error});
                showNotice('error', 'Failed to load screen. Check console for details.');
            }
        });
    }
    
    function renderScreenBuilder(screen) {
        // Hide welcome, show builder
        $('#welcome-state').hide();
        $('#screen-builder').show();
        
        // Update active screen in list
        $('.bazarino-screen-item').removeClass('active');
        $(`.bazarino-screen-item[data-screen-id="${screen.screen_id}"]`).addClass('active');
        
        // Populate fields
        $('#screen-name').val(screen.name);
        $('#canvas-screen-name').text(screen.name);
        $('#screen-route').val(screen.route || '');
        $('#screen-route-display').text(screen.route || '/route');
        $('#screen-type').val(screen.screen_type || 'custom');
        $('input[name="screen_layout"][value="' + (screen.layout || 'scroll') + '"]').prop('checked', true);
        $('#screen-status').prop('checked', screen.is_active);
        
        // Clear dropzone
        $('#widgets-dropzone').find('.bazarino-canvas-widget').remove();
    }
    
    /* ===============================================
       LOAD WIDGETS
       =============================================== */
    function loadWidgetsLibrary() {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_available_widgets',
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderWidgetsLibrary(response.data || []);
                } else {
                    renderWidgetsLibrary(getDefaultWidgets());
                }
            },
            error: function() {
                renderWidgetsLibrary(getDefaultWidgets());
            }
        });
    }
    
    function getDefaultWidgets() {
        return [
            // Layout Widgets
            {
                widget_type: 'slider',
                name: 'Image Slider',
                description: 'Carousel with auto-play images',
                icon: 'dashicons-images-alt2',
                category: 'layout'
            },
            {
                widget_type: 'banner',
                name: 'Banner',
                description: 'Promotional banner image',
                icon: 'dashicons-format-image',
                category: 'layout'
            },
            {
                widget_type: 'text_block',
                name: 'Text Block',
                description: 'Rich text content area',
                icon: 'dashicons-text',
                category: 'layout'
            },
            {
                widget_type: 'spacer',
                name: 'Spacer',
                description: 'Add vertical spacing',
                icon: 'dashicons-minus',
                category: 'layout'
            },
            // Product Widgets
            {
                widget_type: 'categories',
                name: 'Categories Grid',
                description: 'Grid of product categories',
                icon: 'dashicons-category',
                category: 'products'
            },
            {
                widget_type: 'products_grid',
                name: 'Products Grid',
                description: 'Grid of featured products',
                icon: 'dashicons-products',
                category: 'products'
            },
            {
                widget_type: 'featured_products',
                name: 'Featured Products',
                description: 'Showcase featured products',
                icon: 'dashicons-star-filled',
                category: 'products'
            },
            {
                widget_type: 'recent_products',
                name: 'Recent Products',
                description: 'Latest added products',
                icon: 'dashicons-clock',
                category: 'products'
            },
            {
                widget_type: 'sale_products',
                name: 'Sale Products',
                description: 'Products on sale',
                icon: 'dashicons-tag',
                category: 'products'
            },
            {
                widget_type: 'flash_sales',
                name: 'Flash Sales',
                description: 'Limited time offers banner',
                icon: 'dashicons-tagcloud',
                category: 'products'
            },
            // Interactive Widgets
            {
                widget_type: 'search_bar',
                name: 'Search Bar',
                description: 'Product search input',
                icon: 'dashicons-search',
                category: 'interactive'
            },
            {
                widget_type: 'button',
                name: 'Button',
                description: 'Clickable action button',
                icon: 'dashicons-button',
                category: 'interactive'
            },
            // Special Widgets
            {
                widget_type: 'countdown',
                name: 'Countdown Timer',
                description: 'Timer for special offers',
                icon: 'dashicons-backup',
                category: 'special'
            },
            {
                widget_type: 'video',
                name: 'Video Player',
                description: 'Embedded video content',
                icon: 'dashicons-video-alt3',
                category: 'special'
            },
            {
                widget_type: 'testimonials',
                name: 'Testimonials',
                description: 'Customer reviews slider',
                icon: 'dashicons-format-quote',
                category: 'special'
            }
        ];
    }
    
    function renderWidgetsLibrary(widgets) {
        const $library = $('#widgets-library');
        $library.empty();
        
        widgets.forEach(function(widget) {
            const $item = $(`
                <div class="bazarino-widget-library-item" draggable="true" data-widget-type="${widget.widget_type}">
                    <div class="bazarino-widget-icon">
                        <span class="dashicons ${widget.icon || 'dashicons-admin-generic'}"></span>
                    </div>
                    <div class="bazarino-widget-info">
                        <div class="bazarino-widget-name">${widget.name}</div>
                        <div class="bazarino-widget-description">${widget.description || ''}</div>
                    </div>
                </div>
            `);
            
            $library.append($item);
        });
    }
    
    function loadScreenWidgets(screenId) {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_widgets',
                nonce: bazarinoAppBuilder.nonce,
                screen_id: screenId
            },
            success: function(response) {
                if (response.success) {
                    currentWidgets = response.data || [];
                    renderCanvasWidgets(currentWidgets);
                    updateStats();
                }
            }
        });
    }
    
    function renderCanvasWidgets(widgets) {
        const $dropzone = $('#widgets-dropzone');
        $dropzone.find('.bazarino-canvas-widget').remove();
        
        if (widgets.length === 0) {
            $dropzone.find('.bazarino-dropzone-placeholder').show();
            return;
        }
        
        $dropzone.find('.bazarino-dropzone-placeholder').hide();
        
        // Sort by order
        widgets.sort((a, b) => a.sort_order - b.sort_order);
        
        widgets.forEach(function(widget) {
            addWidgetToCanvas(widget);
        });
    }
    
    function addWidgetToCanvas(widget) {
        const visibleIcon = widget.is_visible ? 'visibility' : 'hidden';
        
        const $widget = $(`
            <div class="bazarino-canvas-widget" data-widget-id="${widget.widget_id}" data-widget-type="${widget.widget_type}">
                <div class="bazarino-widget-header">
                    <div class="bazarino-widget-title">
                        <span class="dashicons dashicons-menu"></span>
                        ${widget.name}
                    </div>
                    <div class="bazarino-widget-actions">
                        <button type="button" class="bazarino-widget-action widget-visibility" title="${widget.is_visible ? 'Hide' : 'Show'}">
                            <span class="dashicons dashicons-${visibleIcon}"></span>
                        </button>
                        <button type="button" class="bazarino-widget-action widget-settings" title="Settings">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </button>
                        <button type="button" class="bazarino-widget-action widget-delete" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="bazarino-widget-preview">
                    ${getWidgetPreview(widget)}
                </div>
            </div>
        `);
        
        // Widget click - select
        $widget.on('click', function(e) {
            if (!$(e.target).closest('.bazarino-widget-actions').length) {
                selectWidget(widget);
            }
        });
        
        // Visibility toggle
        $widget.find('.widget-visibility').on('click', function(e) {
            e.stopPropagation();
            toggleWidgetVisibility(widget);
        });
        
        // Settings button
        $widget.find('.widget-settings').on('click', function(e) {
            e.stopPropagation();
            showWidgetProperties(widget);
        });
        
        // Delete button
        $widget.find('.widget-delete').on('click', function(e) {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this widget?')) {
                deleteWidget(widget.widget_id);
            }
        });
        
        $('#widgets-dropzone').append($widget);
    }
    
    function getWidgetPreview(widget) {
        const type = widget.widget_type;
        const previews = {
            'slider': '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 80px; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">Image Slider</div>',
            'categories': '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px;">' + '<div style="background: #f3f4f6; height: 40px; border-radius: 4px;"></div>'.repeat(4) + '</div>',
            'flash_sales': '<div style="background: #fef3c7; border: 2px dashed #f59e0b; height: 60px; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #92400e; font-size: 11px; font-weight: 600;">⚡ FLASH SALE</div>',
            'products_grid': '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">' + '<div style="background: #f9fafb; border: 1px solid #e5e7eb; height: 80px; border-radius: 4px;"></div>'.repeat(4) + '</div>',
            'banners': '<div style="background: linear-gradient(45deg, #f59e0b 0%, #ef4444 100%); height: 100px; border-radius: 6px;"></div>',
            'text_block': '<div style="padding: 12px; background: #f9fafb; border-radius: 6px; font-size: 11px; line-height: 1.6;">Lorem ipsum dolor sit amet, consectetur adipiscing elit...</div>',
            'spacer': '<div style="height: 40px; border: 1px dashed #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 10px;">SPACER</div>',
            'divider': '<div style="height: 1px; background: #e5e7eb; margin: 20px 0;"></div>'
        };
        
        return previews[type] || `<div style="padding: 12px; background: #f9fafb; border-radius: 6px; font-size: 12px; color: #6b7280; text-align: center;">${type} widget</div>`;
    }
    
    function selectWidget(widget) {
        selectedWidget = widget;
        
        $('.bazarino-canvas-widget').removeClass('active');
        $(`.bazarino-canvas-widget[data-widget-id="${widget.widget_id}"]`).addClass('active');
        
        showWidgetProperties(widget);
    }
    
    function toggleWidgetVisibility(widget) {
        widget.is_visible = !widget.is_visible;
        
        // Update icon
        const $widget = $(`.bazarino-canvas-widget[data-widget-id="${widget.widget_id}"]`);
        const icon = widget.is_visible ? 'visibility' : 'hidden';
        $widget.find('.widget-visibility .dashicons').attr('class', 'dashicons dashicons-' + icon);
        $widget.find('.widget-visibility').attr('title', widget.is_visible ? 'Hide' : 'Show');
        
        // Add visual feedback
        $widget.css('opacity', widget.is_visible ? '1' : '0.5');
        
        // Auto-save
        saveWidgetProperty(widget.widget_id, 'is_visible', widget.is_visible);
    }
    
    function showWidgetProperties(widget) {
        // Switch to properties tab
        $('.bazarino-tab-btn[data-tab="properties"]').click();
        
        // Render properties based on widget type
        const $properties = $('#widget-properties');
        let propertiesHTML = `
            <div class="bazarino-properties-header">
                <h3>${widget.name} Settings</h3>
                <small>Widget Type: ${widget.widget_type}</small>
            </div>
            
            <div class="bazarino-property-group">
                <label class="bazarino-property-label">Widget Name</label>
                <input type="text" class="bazarino-property-input" value="${widget.name}" data-property="name" data-widget-id="${widget.widget_id}" />
            </div>
            
            <div class="bazarino-property-group">
                <label class="bazarino-toggle-label">
                    <input type="checkbox" ${widget.is_visible ? 'checked' : ''} data-property="visible" data-widget-id="${widget.widget_id}" />
                    <span class="bazarino-toggle-switch"></span>
                    <span>Visible</span>
                </label>
                <small>Show or hide this widget in the app</small>
            </div>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;" />
            
            ${getWidgetSpecificProperties(widget)}
        `;
        
        $properties.html(propertiesHTML);
        
        // Add property change handlers
        $properties.find('[data-property][data-widget-id]').on('change input', function() {
            const property = $(this).data('property');
            const widgetId = $(this).data('widget-id');
            const value = $(this).is(':checkbox') ? $(this).prop('checked') : $(this).val();
            updateWidgetProperty(widgetId, property, value);
        });
        
        // Re-init color pickers
        initColorPickers();
    }
    
    function getWidgetSpecificProperties(widget) {
        const settings = widget.settings || {};
        
        switch(widget.widget_type) {
            case 'slider':
                return `
                    <h4 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 700; color: var(--text-primary);">Slider Settings</h4>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-toggle-label">
                            <input type="checkbox" ${settings.auto_play !== false ? 'checked' : ''} data-property="auto_play" data-widget-id="${widget.widget_id}" />
                            <span class="bazarino-toggle-switch"></span>
                            <span>Auto Play</span>
                        </label>
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Interval (seconds)</label>
                        <input type="number" class="bazarino-property-input" value="${settings.interval || 5}" min="1" max="60" data-property="interval" data-widget-id="${widget.widget_id}" />
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Height (px)</label>
                        <input type="number" class="bazarino-property-input" value="${settings.height || 200}" min="100" max="500" data-property="height" data-widget-id="${widget.widget_id}" />
                    </div>
                `;
                
            case 'categories':
                return `
                    <h4 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 700;">Categories Settings</h4>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Columns</label>
                        <select class="bazarino-property-input" data-property="columns" data-widget-id="${widget.widget_id}">
                            <option value="2" ${settings.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${settings.columns == 3 ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${settings.columns == 4 ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Show Count</label>
                        <input type="number" class="bazarino-property-input" value="${settings.show_count || 8}" min="1" max="20" data-property="show_count" data-widget-id="${widget.widget_id}" />
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-toggle-label">
                            <input type="checkbox" ${settings.hide_empty !== false ? 'checked' : ''} data-property="hide_empty" data-widget-id="${widget.widget_id}" />
                            <span class="bazarino-toggle-switch"></span>
                            <span>Hide Empty</span>
                        </label>
                    </div>
                `;
                
            case 'products_grid':
                return `
                    <h4 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 700;">Products Settings</h4>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Columns</label>
                        <select class="bazarino-property-input" data-property="columns" data-widget-id="${widget.widget_id}">
                            <option value="1" ${settings.columns == 1 ? 'selected' : ''}>1 Column</option>
                            <option value="2" ${settings.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${settings.columns == 3 ? 'selected' : ''}>3 Columns</option>
                        </select>
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Products Per Page</label>
                        <input type="number" class="bazarino-property-input" value="${settings.per_page || 10}" min="4" max="50" data-property="per_page" data-widget-id="${widget.widget_id}" />
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Product Type</label>
                        <select class="bazarino-property-input" data-property="product_type" data-widget-id="${widget.widget_id}">
                            <option value="all" ${settings.product_type == 'all' ? 'selected' : ''}>All Products</option>
                            <option value="featured" ${settings.product_type == 'featured' ? 'selected' : ''}>Featured</option>
                            <option value="on_sale" ${settings.product_type == 'on_sale' ? 'selected' : ''}>On Sale</option>
                            <option value="recent" ${settings.product_type == 'recent' ? 'selected' : ''}>Recent</option>
                        </select>
                    </div>
                `;
                
            case 'flash_sales':
                return `
                    <h4 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 700;">Flash Sales Settings</h4>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Layout</label>
                        <select class="bazarino-property-input" data-property="layout" data-widget-id="${widget.widget_id}">
                            <option value="horizontal" ${settings.layout == 'horizontal' ? 'selected' : ''}>Horizontal Scroll</option>
                            <option value="vertical" ${settings.layout == 'vertical' ? 'selected' : ''}>Vertical List</option>
                            <option value="grid" ${settings.layout == 'grid' ? 'selected' : ''}>Grid</option>
                        </select>
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-toggle-label">
                            <input type="checkbox" ${settings.show_timer !== false ? 'checked' : ''} data-property="show_timer" data-widget-id="${widget.widget_id}" />
                            <span class="bazarino-toggle-switch"></span>
                            <span>Show Timer</span>
                        </label>
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Items Count</label>
                        <input type="number" class="bazarino-property-input" value="${settings.items_count || 5}" min="3" max="20" data-property="items_count" data-widget-id="${widget.widget_id}" />
                    </div>
                `;
                
            case 'text_block':
                return `
                    <h4 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 700;">Text Block Settings</h4>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Content</label>
                        <textarea class="bazarino-property-input" rows="4" data-property="content" data-widget-id="${widget.widget_id}">${settings.content || ''}</textarea>
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Text Color</label>
                        <input type="text" class="bazarino-property-input bazarino-color-picker" value="${settings.text_color || '#1f2937'}" data-property="text_color" data-widget-id="${widget.widget_id}" />
                    </div>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Background Color</label>
                        <input type="text" class="bazarino-property-input bazarino-color-picker" value="${settings.bg_color || '#ffffff'}" data-property="bg_color" data-widget-id="${widget.widget_id}" />
                    </div>
                `;
                
            case 'spacer':
                return `
                    <h4 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 700;">Spacer Settings</h4>
                    
                    <div class="bazarino-property-group">
                        <label class="bazarino-property-label">Height (px)</label>
                        <input type="number" class="bazarino-property-input" value="${settings.height || 40}" min="10" max="200" data-property="height" data-widget-id="${widget.widget_id}" />
                    </div>
                `;
                
            default:
                return `
                    <div style="padding: 20px; text-align: center; color: #9ca3af;">
                        <p>No specific settings for this widget type.</p>
                    </div>
                `;
        }
    }
    
    function updateWidgetProperty(widgetId, property, value) {
        const widget = currentWidgets.find(w => w.widget_id == widgetId);
        if (!widget) return;
        
        if (property === 'name') {
            widget.name = value;
            $(`.bazarino-canvas-widget[data-widget-id="${widgetId}"] .bazarino-widget-title`).text(value);
        } else if (property === 'visible') {
            widget.is_visible = value;
            toggleWidgetVisibility(widget);
        } else {
            // Widget-specific setting
            if (!widget.settings) widget.settings = {};
            widget.settings[property] = value;
        }
        
        // Auto-save
        saveWidgetProperty(widgetId, property, value);
    }
    
    function saveWidgetProperty(widgetId, property, value) {
        const widget = currentWidgets.find(w => w.widget_id == widgetId);
        if (!widget) return;
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_save_widget',
                nonce: bazarinoAppBuilder.nonce,
                widget_id: widgetId,
                screen_id: currentScreen.screen_id,
                widget_type: widget.widget_type,
                title: widget.name,
                config: JSON.stringify(widget.settings || {}),
                position: widget.sort_order,
                status: widget.is_visible ? 'active' : 'inactive'
            },
            success: function(response) {
                // Silent save
            }
        });
    }
    
    function deleteWidget(widgetId) {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_delete_widget',
                nonce: bazarinoAppBuilder.nonce,
                widget_id: widgetId
            },
            success: function(response) {
                if (response.success) {
                    $(`.bazarino-canvas-widget[data-widget-id="${widgetId}"]`).fadeOut(function() {
                        $(this).remove();
                        currentWidgets = currentWidgets.filter(w => w.widget_id != widgetId);
                        if (currentWidgets.length === 0) {
                            $('#widgets-dropzone .bazarino-dropzone-placeholder').show();
                        }
                        updateStats();
                    });
                    showNotice('success', 'Widget deleted successfully');
                } else {
                    showNotice('error', 'Failed to delete widget');
                }
            }
        });
    }
    
    /* ===============================================
       DRAG & DROP
       =============================================== */
    function initDragAndDrop() {
        // Widget library items - draggable
        $(document).on('dragstart', '.bazarino-widget-library-item', function(e) {
            const widgetType = $(this).data('widget-type');
            e.originalEvent.dataTransfer.setData('widget-type', widgetType);
            $(this).css('opacity', '0.5');
        });
        
        $(document).on('dragend', '.bazarino-widget-library-item', function() {
            $(this).css('opacity', '1');
        });
        
        // Dropzone - droppable
        $('#widgets-dropzone').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });
        
        $('#widgets-dropzone').on('dragleave', function() {
            $(this).removeClass('drag-over');
        });
        
        $('#widgets-dropzone').on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const widgetType = e.originalEvent.dataTransfer.getData('widget-type');
            if (widgetType && currentScreen) {
                createWidget(widgetType);
            }
        });
    }
    
    function createWidget(widgetType) {
        if (!currentScreen) {
            showNotice('error', 'Please select a screen first');
            return;
        }
        
        const widgetName = widgetType.charAt(0).toUpperCase() + widgetType.slice(1).replace(/_/g, ' ');
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_create_widget',
                nonce: bazarinoAppBuilder.nonce,
                screen_id: currentScreen.screen_id,
                widget_type: widgetType,
                name: widgetName,
                sort_order: currentWidgets.length,
                is_visible: true
            },
            success: function(response) {
                if (response.success && response.data) {
                    currentWidgets.push(response.data);
                    addWidgetToCanvas(response.data);
                    $('#widgets-dropzone .bazarino-dropzone-placeholder').hide();
                    showNotice('success', 'Widget added successfully');
                    updateStats();
                } else {
                    showNotice('error', 'Failed to add widget');
                }
            }
        });
    }
    
    /* ===============================================
       SETTINGS PANEL
       =============================================== */
    function toggleSettingsPanel() {
        $('#screen-settings-panel').toggleClass('active');
    }
    
    function closeSettingsPanel() {
        $('#screen-settings-panel').removeClass('active');
    }
    
    /* ===============================================
       SCREEN ACTIONS
       =============================================== */
    function handleAddScreen() {
        // Clear modal form
        $('#modal-screen-name').val('');
        $('#modal-screen-route').val('');
        $('#modal-screen-type').val('custom');
        $('input[name="modal_screen_layout"][value="scroll"]').prop('checked', true);
        $('#modal-screen-active').prop('checked', true);
        
        // Update modal title
        $('#screen-modal-title').text('Create New Screen');
        $('#save-screen-modal').html('<span class="dashicons dashicons-saved"></span> Create Screen');
        
        // Remove existing screen ID if any
        $('#save-screen-modal').removeData('screen-id');
        
        // Auto-generate route from screen name
        $('#modal-screen-name').on('input', function() {
            const name = $(this).val();
            const route = '/' + name.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
            $('#modal-screen-route').val(route);
        });
        
        // Show modal
        $('#screen-modal').fadeIn();
    }
    
    // Save screen from modal
    $(document).on('click', '#save-screen-modal', function() {
        const screenName = $('#modal-screen-name').val().trim();
        const screenRoute = $('#modal-screen-route').val().trim();
        const screenType = $('#modal-screen-type').val();
        const layout = $('input[name="modal_screen_layout"]:checked').val();
        const isActive = $('#modal-screen-active').is(':checked');
        const screenId = $(this).data('screen-id');
        
        // Validation
        if (!screenName) {
            showNotice('error', 'Screen name is required');
            return;
        }
        
        if (!screenRoute) {
            showNotice('error', 'Route path is required');
            return;
        }
        
        // Disable button
        $(this).prop('disabled', true).html('<span class="bazarino-spinner"></span> Saving...');
        
        const ajaxData = {
            nonce: bazarinoAppBuilder.nonce,
            name: screenName,
            route: screenRoute,
            screen_type: screenType,
            layout: layout,
            is_active: isActive
        };
        
        // If editing existing screen
        if (screenId) {
            ajaxData.action = 'bazarino_update_screen';
            ajaxData.screen_id = screenId;
        } else {
            ajaxData.action = 'bazarino_create_screen';
        }
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success && response.data) {
                    showNotice('success', screenId ? 'Screen updated successfully' : 'Screen created successfully');
                    $('#screen-modal').fadeOut();
                    loadScreens();
                    
                    // Load the screen in builder
                    if (response.data.screen_id) {
                        loadScreen(response.data.screen_id);
                    }
                } else {
                    showNotice('error', response.data || 'Failed to create screen');
                }
            },
            error: function(xhr, status, error) {
                console.error('Create screen error:', error);
                showNotice('error', 'Failed to create screen. Please try again.');
            },
            complete: function() {
                // Re-enable button
                $('#save-screen-modal').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Create Screen');
            }
        });
    });
    
    function handleSaveScreen() {
        if (!currentScreen) {
            showNotice('error', 'No screen selected');
            return;
        }
        
        const screenData = {
            action: 'bazarino_update_screen',
            nonce: bazarinoAppBuilder.nonce,
            screen_id: currentScreen.screen_id,
            name: $('#screen-name').val(),
            route: $('#screen-route').val(),
            screen_type: $('#screen-type').val(),
            layout: $('input[name="screen_layout"]:checked').val(),
            is_active: $('#screen-status').prop('checked')
        };
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: screenData,
            success: function(response) {
                if (response.success) {
                    showNotice('success', 'Screen saved successfully');
                    loadScreens();
                } else {
                    showNotice('error', 'Failed to save screen');
                }
            }
        });
    }
    
    function handleCloseBuilder() {
        currentScreen = null;
        currentWidgets = [];
        $('#screen-builder').hide();
        $('#welcome-state').show();
        $('.bazarino-screen-item').removeClass('active');
    }
    
    function handleSaveAll() {
        showNotice('info', 'Saving all changes...');
        if (currentScreen) {
            handleSaveScreen();
        }
    }
    
    function handlePreviewScreen() {
        if (!currentScreen) {
            showNotice('error', 'No screen selected');
            return;
        }
        
        // Build preview HTML
        const previewHTML = buildPreviewHTML(currentScreen, currentWidgets);
        
        // Show preview modal
        $('#preview-content').html(previewHTML);
        $('#preview-modal').fadeIn();
    }
    
    function handlePreviewApp() {
        if (!currentScreen) {
            showNotice('error', 'No screen selected');
            return;
        }
        
        handlePreviewScreen();
    }
    
    function buildPreviewHTML(screen, widgets) {
        let widgetsHTML = '';
        
        if (!widgets || widgets.length === 0) {
            widgetsHTML = `
                <div style="padding: 60px 20px; text-align: center; color: #999;">
                    <span class="dashicons dashicons-admin-customizer" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 12px;"></span>
                    <p style="margin: 0; font-size: 14px;">No widgets added yet</p>
                </div>
            `;
        } else {
            widgets.forEach(function(widget) {
                widgetsHTML += buildWidgetPreview(widget);
            });
        }
        
        return `
            <div class="preview-phone-frame">
                <div class="preview-phone-notch"></div>
                <div class="preview-phone-content">
                    <div class="preview-statusbar">
                        <span class="preview-time">9:41</span>
                        <div class="preview-icons">
                            <span class="dashicons dashicons-smartphone"></span>
                            <span class="dashicons dashicons-wifi"></span>
                        </div>
                    </div>
                    <div class="preview-appbar">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <span class="preview-title">${escapeHtml(screen.name)}</span>
                        <span class="dashicons dashicons-menu"></span>
                    </div>
                    <div class="preview-body">
                        ${widgetsHTML}
                    </div>
                </div>
                <div class="preview-phone-bottom"></div>
            </div>
        `;
    }
    
    function buildWidgetPreview(widget) {
        const type = widget.widget_type || widget.type;
        
        switch(type) {
            case 'slider':
                return `<div class="preview-widget preview-slider">
                    <div class="preview-slide" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 180px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">Image Slider</div>
                </div>`;
            
            case 'banner':
                return `<div class="preview-widget preview-banner">
                    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); height: 120px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">Banner</div>
                </div>`;
            
            case 'categories':
                return `<div class="preview-widget preview-categories">
                    <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600;">Categories</h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                        ${[1,2,3,4].map(() => `
                            <div style="aspect-ratio: 1; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons dashicons-category" style="font-size: 24px; color: #667eea;"></span>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
            
            case 'products_grid':
            case 'featured_products':
            case 'recent_products':
            case 'sale_products':
                return `<div class="preview-widget preview-products">
                    <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600;">${widget.name || 'Products'}</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        ${[1,2].map(() => `
                            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                                <div style="aspect-ratio: 1; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                    <span class="dashicons dashicons-products" style="font-size: 32px; color: #667eea;"></span>
                                </div>
                                <div style="padding: 8px;">
                                    <div style="height: 12px; background: #e5e7eb; border-radius: 4px; margin-bottom: 6px;"></div>
                                    <div style="height: 10px; background: #f3f4f6; border-radius: 4px; width: 60%;"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
            
            case 'flash_sales':
                return `<div class="preview-widget preview-flash-sale">
                    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 16px; border-radius: 12px; color: white;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <h3 style="margin: 0; font-size: 18px; font-weight: 700;">⚡ Flash Sale</h3>
                                <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 12px;">Limited time offer!</p>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 8px; font-weight: 600;">
                                02:45:30
                            </div>
                        </div>
                    </div>
                </div>`;
            
            case 'text_block':
                return `<div class="preview-widget preview-text">
                    <p style="margin: 0; color: #4b5563; line-height: 1.6;">${widget.name || 'Text content will appear here'}</p>
                </div>`;
            
            case 'search_bar':
                return `<div class="preview-widget preview-search">
                    <div style="background: #f3f4f6; padding: 10px 16px; border-radius: 24px; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-search" style="color: #9ca3af; font-size: 18px;"></span>
                        <span style="color: #9ca3af; font-size: 14px;">Search products...</span>
                    </div>
                </div>`;
            
            case 'button':
                return `<div class="preview-widget preview-button">
                    <button style="background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 24px; font-weight: 600; width: 100%; cursor: pointer;">
                        ${widget.name || 'Button'}
                    </button>
                </div>`;
            
            case 'spacer':
                return `<div class="preview-widget preview-spacer" style="height: 24px;"></div>`;
            
            case 'countdown':
                return `<div class="preview-widget preview-countdown">
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        ${['02','45','30'].map((num, i) => `
                            <div style="background: #667eea; color: white; padding: 12px; border-radius: 8px; text-align: center; min-width: 60px;">
                                <div style="font-size: 24px; font-weight: 700;">${num}</div>
                                <div style="font-size: 10px; opacity: 0.8; margin-top: 4px;">${['HOURS','MINS','SECS'][i]}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
            
            case 'video':
                return `<div class="preview-widget preview-video">
                    <div style="aspect-ratio: 16/9; background: #1f2937; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span class="dashicons dashicons-video-alt3" style="font-size: 48px; color: white; opacity: 0.5;"></span>
                    </div>
                </div>`;
            
            case 'testimonials':
                return `<div class="preview-widget preview-testimonials">
                    <div style="background: white; border: 1px solid #e5e7eb; padding: 16px; border-radius: 12px;">
                        <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #667eea;"></div>
                            <div>
                                <div style="height: 12px; background: #e5e7eb; border-radius: 4px; width: 100px; margin-bottom: 6px;"></div>
                                <div style="color: #facc15;">★★★★★</div>
                            </div>
                        </div>
                        <div style="height: 10px; background: #f3f4f6; border-radius: 4px; margin-bottom: 6px;"></div>
                        <div style="height: 10px; background: #f3f4f6; border-radius: 4px; width: 80%;"></div>
                    </div>
                </div>`;
            
            default:
                return `<div class="preview-widget">
                    <div style="background: #f3f4f6; padding: 16px; border-radius: 8px; text-align: center; color: #6b7280;">
                        ${widget.name || type}
                    </div>
                </div>`;
        }
    }
    
    /* ===============================================
       SEARCH
       =============================================== */
    function handleScreensSearch() {
        const query = $(this).val().toLowerCase();
        $('.bazarino-screen-item').each(function() {
            const name = $(this).find('.screen-name').text().toLowerCase();
            const route = $(this).find('.screen-route').text().toLowerCase();
            const matches = name.includes(query) || route.includes(query);
            $(this).toggle(matches);
        });
    }
    
    function handleWidgetsSearch() {
        const query = $(this).val().toLowerCase();
        $('.bazarino-widget-library-item').each(function() {
            const name = $(this).find('.bazarino-widget-name').text().toLowerCase();
            const desc = $(this).find('.bazarino-widget-description').text().toLowerCase();
            const matches = name.includes(query) || desc.includes(query);
            $(this).toggle(matches);
        });
    }
    
    /* ===============================================
       STATS
       =============================================== */
    function updateStats(screens) {
        if (!screens) {
            // Update just widget count
            $('#total-widgets').text(currentWidgets.length);
            return;
        }
        
        const totalScreens = screens.length;
        const activeScreens = screens.filter(s => s.is_active).length;
        
        $('#total-screens').text(totalScreens);
        $('#active-screens').text(activeScreens);
        $('#total-widgets').text(currentWidgets.length);
    }
    
    function updateScreenRoute() {
        const name = $('#screen-name').val();
        if (name) {
            const route = '/' + name.toLowerCase().replace(/\s+/g, '-');
            if (!$('#screen-route').val()) {
                $('#screen-route').val(route);
                $('#screen-route-display').text(route);
            }
        }
    }
    
    /* ===============================================
       UTILITIES
       =============================================== */
    function showNotice(type, message, duration) {
        const $notice = $(`
            <div class="bazarino-notice bazarino-notice-${type}">
                ${message}
            </div>
        `);
        
        $('#bazarino-app-builder-notices').append($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, duration || 3000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /* ===============================================
       EXPORT/IMPORT
       =============================================== */
    function handleExportConfig() {
        showNotice('info', 'Preparing export...');
        
        // Get all screens and widgets
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_screens',
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (response.success) {
                    const screens = response.data || [];
                    
                    // Get widgets for each screen
                    const promises = screens.map(screen => {
                        return $.ajax({
                            url: bazarinoAppBuilder.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'bazarino_get_widgets',
                                nonce: bazarinoAppBuilder.nonce,
                                screen_id: screen.screen_id || screen.id
                            }
                        });
                    });
                    
                    Promise.all(promises).then(results => {
                        // Attach widgets to screens
                        screens.forEach((screen, index) => {
                            if (results[index].success) {
                                screen.widgets = results[index].data || [];
                            }
                        });
                        
                        // Create export object
                        const exportData = {
                            version: '1.0.0',
                            export_date: new Date().toISOString(),
                            screens: screens,
                            metadata: {
                                total_screens: screens.length,
                                total_widgets: screens.reduce((sum, s) => sum + (s.widgets ? s.widgets.length : 0), 0)
                            }
                        };
                        
                        // Download as JSON file
                        const jsonStr = JSON.stringify(exportData, null, 2);
                        const blob = new Blob([jsonStr], { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `bazarino-config-${Date.now()}.json`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        
                        showNotice('success', 'Configuration exported successfully!');
                    });
                } else {
                    showNotice('error', 'Failed to export configuration');
                }
            },
            error: function() {
                showNotice('error', 'Failed to export configuration');
            }
        });
    }
    
    function handleImportConfig() {
        $('#import-file').val('');
        $('#import-preview').hide();
        $('#confirm-import').prop('disabled', true);
        $('#import-replace-existing').prop('checked', false);
        $('#import-modal').fadeIn();
    }
    
    function handleImportFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(event) {
            try {
                const config = JSON.parse(event.target.result);
                
                // Validate config
                if (!config.screens || !Array.isArray(config.screens)) {
                    showNotice('error', 'Invalid configuration file');
                    return;
                }
                
                // Show preview
                const previewHTML = `
                    <div style="font-size: 13px;">
                        <p style="margin: 0 0 8px 0;"><strong>Version:</strong> ${config.version || 'Unknown'}</p>
                        <p style="margin: 0 0 8px 0;"><strong>Export Date:</strong> ${config.export_date ? new Date(config.export_date).toLocaleString() : 'Unknown'}</p>
                        <p style="margin: 0 0 8px 0;"><strong>Screens:</strong> ${config.screens.length}</p>
                        <p style="margin: 0 0 12px 0;"><strong>Total Widgets:</strong> ${config.metadata?.total_widgets || 0}</p>
                        <div style="max-height: 150px; overflow-y: auto;">
                            <strong>Screens:</strong>
                            <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                                ${config.screens.map(s => `<li>${escapeHtml(s.name)} (${s.widgets ? s.widgets.length : 0} widgets)</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                `;
                
                $('#import-preview-content').html(previewHTML);
                $('#import-preview').show();
                $('#confirm-import').prop('disabled', false).data('config', config);
                
            } catch (error) {
                showNotice('error', 'Failed to parse configuration file');
                console.error('Parse error:', error);
            }
        };
        reader.readAsText(file);
    }
    
    function handleConfirmImport() {
        const config = $('#confirm-import').data('config');
        const replaceExisting = $('#import-replace-existing').is(':checked');
        
        if (!config) {
            showNotice('error', 'No configuration selected');
            return;
        }
        
        showNotice('info', 'Importing configuration...');
        $('#confirm-import').prop('disabled', true).html('<span class="bazarino-spinner"></span> Importing...');
        
        // TODO: Implement actual import via AJAX
        // For now, just show success
        setTimeout(() => {
            $('#import-modal').fadeOut();
            showNotice('success', `Configuration imported successfully! ${config.screens.length} screens ${replaceExisting ? 'replaced' : 'added'}.`);
            loadScreens();
            
            // Re-enable button
            $('#confirm-import').prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Import');
        }, 1500);
    }
    
    /* ===============================================
       INITIALIZE ON LOAD
       =============================================== */
    init();
});
