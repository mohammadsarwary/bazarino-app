/**
 * Bazarino Visual App Builder - JavaScript
 * Version: 2.0.0
 * Professional drag & drop interface
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
        initTabs();
    }
    
    /* ===============================================
       EVENT HANDLERS
       =============================================== */
    function initEventHandlers() {
        // Header actions
        $('#save-all').on('click', handleSaveAll);
        $('#preview-app').on('click', handlePreviewApp);
        
        // Screen management
        $('#add-new-screen, #create-first-screen').on('click', handleAddScreen);
        $('#close-builder').on('click', handleCloseBuilder);
        $('#save-screen').on('click', handleSaveScreen);
        $('#preview-screen').on('click', handlePreviewScreen);
        
        // Settings panel
        $('#screen-settings-toggle').on('click', toggleSettingsPanel);
        $('.bazarino-close-settings').on('click', closeSettingsPanel);
        
        // Screen name input
        $('#screen-name').on('input', function() {
            $('#canvas-screen-name').text($(this).val() || 'Screen Name');
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
            const $item = $(`
                <div class="bazarino-screen-item" data-screen-id="${screen.screen_id}">
                    <div>
                        <div class="screen-name">${escapeHtml(screen.name)}</div>
                        <div class="screen-route">${escapeHtml(screen.route || '/screen')}</div>
                    </div>
                    <span class="screen-status ${screen.is_active ? 'active' : 'inactive'}">
                        ${screen.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
            `);
            
            $item.on('click', function() {
                loadScreen(screen.screen_id);
            });
            
            $list.append($item);
        });
    }
    
    function loadScreen(screenId) {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_screen',
                nonce: bazarinoAppBuilder.nonce,
                screen_id: screenId
            },
            success: function(response) {
                if (response.success && response.data) {
                    currentScreen = response.data;
                    renderScreenBuilder(response.data);
                    loadScreenWidgets(screenId);
                } else {
                    showNotice('error', 'Failed to load screen');
                }
            },
            error: function() {
                showNotice('error', 'Failed to load screen');
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
            {
                widget_type: 'slider',
                name: 'Image Slider',
                description: 'Carousel with auto-play images',
                icon: 'dashicons-images-alt2'
            },
            {
                widget_type: 'categories',
                name: 'Categories Grid',
                description: 'Grid of product categories',
                icon: 'dashicons-category'
            },
            {
                widget_type: 'flash_sales',
                name: 'Flash Sales',
                description: 'Limited time offers banner',
                icon: 'dashicons-tagcloud'
            },
            {
                widget_type: 'products_grid',
                name: 'Products Grid',
                description: 'Grid of featured products',
                icon: 'dashicons-products'
            },
            {
                widget_type: 'banners',
                name: 'Banner',
                description: 'Promotional banner image',
                icon: 'dashicons-format-image'
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
        const $widget = $(`
            <div class="bazarino-canvas-widget" data-widget-id="${widget.widget_id}" data-widget-type="${widget.widget_type}">
                <div class="bazarino-widget-header">
                    <div class="bazarino-widget-title">${widget.name}</div>
                    <div class="bazarino-widget-actions">
                        <button type="button" class="bazarino-widget-action widget-settings" title="Settings">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </button>
                        <button type="button" class="bazarino-widget-action widget-delete" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div style="padding: 12px; background: #f9fafb; border-radius: 6px; font-size: 12px; color: #6b7280; text-align: center;">
                    ${widget.widget_type} widget preview
                </div>
            </div>
        `);
        
        // Widget click - select
        $widget.on('click', function(e) {
            if (!$(e.target).closest('.bazarino-widget-actions').length) {
                selectWidget(widget);
            }
        });
        
        // Settings button
        $widget.find('.widget-settings').on('click', function(e) {
            e.stopPropagation();
            showWidgetProperties(widget);
        });
        
        // Delete button
        $widget.find('.widget-delete').on('click', function(e) {
            e.stopPropagation();
            deleteWidget(widget.widget_id);
        });
        
        $('#widgets-dropzone').append($widget);
    }
    
    function selectWidget(widget) {
        selectedWidget = widget;
        
        $('.bazarino-canvas-widget').removeClass('active');
        $(`.bazarino-canvas-widget[data-widget-id="${widget.widget_id}"]`).addClass('active');
        
        showWidgetProperties(widget);
    }
    
    function showWidgetProperties(widget) {
        // Switch to properties tab
        $('.bazarino-tab-btn[data-tab="properties"]').click();
        
        // Render properties
        const $properties = $('#widget-properties');
        $properties.html(`
            <div class="bazarino-property-group">
                <label class="bazarino-property-label">Widget Name</label>
                <input type="text" class="bazarino-property-input" value="${widget.name}" data-property="name" />
            </div>
            <div class="bazarino-property-group">
                <label class="bazarino-property-label">Widget Type</label>
                <input type="text" class="bazarino-property-input" value="${widget.widget_type}" disabled />
            </div>
            <div class="bazarino-property-group">
                <label class="bazarino-toggle-label">
                    <input type="checkbox" ${widget.is_visible ? 'checked' : ''} data-property="visible" />
                    <span class="bazarino-toggle-switch"></span>
                    <span>Visible</span>
                </label>
            </div>
        `);
        
        // Add property change handlers
        $properties.find('[data-property]').on('change input', function() {
            const property = $(this).data('property');
            const value = $(this).is(':checkbox') ? $(this).prop('checked') : $(this).val();
            updateWidgetProperty(widget.widget_id, property, value);
        });
    }
    
    function updateWidgetProperty(widgetId, property, value) {
        const widget = currentWidgets.find(w => w.widget_id == widgetId);
        if (widget) {
            if (property === 'name') widget.name = value;
            if (property === 'visible') widget.is_visible = value;
        }
    }
    
    function deleteWidget(widgetId) {
        if (!confirm('Are you sure you want to delete this widget?')) {
            return;
        }
        
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
        
        // Canvas widgets - sortable (reorder)
        // Note: This would require jQuery UI sortable or a similar library
        // For now, widgets can be reordered manually via settings
    }
    
    function createWidget(widgetType) {
        if (!currentScreen) {
            showNotice('error', 'Please select a screen first');
            return;
        }
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_create_widget',
                nonce: bazarinoAppBuilder.nonce,
                screen_id: currentScreen.screen_id,
                widget_type: widgetType,
                name: widgetType.charAt(0).toUpperCase() + widgetType.slice(1).replace(/_/g, ' '),
                sort_order: currentWidgets.length
            },
            success: function(response) {
                if (response.success && response.data) {
                    currentWidgets.push(response.data);
                    addWidgetToCanvas(response.data);
                    $('#widgets-dropzone .bazarino-dropzone-placeholder').hide();
                    showNotice('success', 'Widget added successfully');
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
        const screenName = prompt('Enter screen name:', 'New Screen');
        if (!screenName) return;
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_create_screen',
                nonce: bazarinoAppBuilder.nonce,
                name: screenName,
                route: '/' + screenName.toLowerCase().replace(/\s+/g, '-'),
                screen_type: 'custom',
                layout: 'scroll',
                is_active: true
            },
            success: function(response) {
                if (response.success && response.data) {
                    showNotice('success', 'Screen created successfully');
                    loadScreens();
                    loadScreen(response.data.screen_id);
                } else {
                    showNotice('error', 'Failed to create screen');
                }
            }
        });
    }
    
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
        // Save current screen if any
        if (currentScreen) {
            handleSaveScreen();
        }
    }
    
    function handlePreviewScreen() {
        showNotice('info', 'Preview functionality coming soon!');
    }
    
    function handlePreviewApp() {
        showNotice('info', 'App preview functionality coming soon!');
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
        const totalScreens = screens.length;
        const activeScreens = screens.filter(s => s.is_active).length;
        const totalWidgets = currentWidgets.length;
        
        $('#total-screens').text(totalScreens);
        $('#active-screens').text(activeScreens);
        $('#total-widgets').text(totalWidgets);
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
    function showNotice(type, message) {
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
        }, 3000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /* ===============================================
       INITIALIZE ON LOAD
       =============================================== */
    init();
});
