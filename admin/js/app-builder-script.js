jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    var currentScreen = null;
    var currentWidgets = [];
    var widgetIdCounter = 0;
    
    // Initialize the app builder
    function initAppBuilder() {
        loadScreens();
        loadWidgetsLibrary();
        initEventHandlers();
    }
    
    // Load screens from server
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
                    renderScreensList(response.data);
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Failed to load screens');
            }
        });
    }
    
    // Render screens list
    function renderScreensList(screens) {
        var $screensList = $('#screens-list');
        $screensList.empty();
        
        if (screens.length === 0) {
            $screensList.html('<p style="padding: 15px; text-align: center; color: #666;">No screens found. Create your first screen!</p>');
            return;
        }
        
        screens.forEach(function(screen) {
            var $screenItem = $('<div class="bazarino-screen-item" data-screen-id="' + screen.id + '">');
            $screenItem.append('<span class="screen-name">' + screen.name + '</span>');
            $screenItem.append('<div class="screen-actions">' +
                '<button type="button" class="button button-small edit-screen">Edit</button> ' +
                '<button type="button" class="button button-small delete-screen">Delete</button>' +
                '</div>');
            $screensList.append($screenItem);
        });
    }
    
    // Load widgets library
    function loadWidgetsLibrary() {
        var $widgetsLibrary = $('#widgets-library');
        $widgetsLibrary.empty();
        
        Object.keys(bazarinoAppBuilder.widget_types).forEach(function(widgetType) {
            var $widgetItem = $('<div class="bazarino-widget-item" data-widget-type="' + widgetType + '" draggable="true">');
            $widgetItem.append('<span class="widget-icon">' + getWidgetIcon(widgetType) + '</span>');
            $widgetItem.append('<span class="widget-name">' + bazarinoAppBuilder.widget_types[widgetType] + '</span>');
            $widgetsLibrary.append($widgetItem);
        });
    }
    
    // Get widget icon
    function getWidgetIcon(widgetType) {
        var icons = {
            'slider': '🎠',
            'banner': '🖼️',
            'categories': '📂',
            'products': '🛍️',
            'flash_sale': '⚡',
            'text_block': '📝',
            'image_block': '🖼️',
            'featured_products': '⭐',
            'recent_products': '🆕',
            'sale_products': '💰'
        };
        return icons[widgetType] || '📦';
    }
    
    // Initialize event handlers
    function initEventHandlers() {
        // Add new screen
        $(document).on('click', '#add-new-screen', function(e) {
            e.preventDefault();
            showScreenBuilder();
        });
        
        // Screen item clicks
        $(document).on('click', '.bazarino-screen-item', function(e) {
            if (!$(e.target).hasClass('button')) {
                var screenId = $(this).data('screen-id');
                loadScreen(screenId);
            }
        });
        
        // Edit screen
        $(document).on('click', '.edit-screen', function(e) {
            e.stopPropagation();
            var screenId = $(this).closest('.bazarino-screen-item').data('screen-id');
            loadScreen(screenId);
        });
        
        // Delete screen
        $(document).on('click', '.delete-screen', function(e) {
            e.stopPropagation();
            var screenId = $(this).closest('.bazarino-screen-item').data('screen-id');
            if (confirm(bazarinoAppBuilder.strings.confirm_delete_screen)) {
                deleteScreen(screenId);
            }
        });
        
        // Save screen
        $('#save-screen').on('click', function() {
            saveScreen();
        });
        
        // Preview screen
        $('#preview-screen').on('click', function() {
            previewScreen();
        });
        
        // Widget library drag and drop
        initWidgetDragAndDrop();
        
        // Modal close buttons
        $(document).on('click', '.bazarino-modal-close', function() {
            closeModal($(this).closest('.bazarino-modal'));
        });
        
        // Modal background click
        $(document).on('click', '.bazarino-modal', function(e) {
            if (e.target === this) {
                closeModal($(this));
            }
        });
        
        // Save widget
        $('#save-widget').on('click', function() {
            saveWidget();
        });
    }
    
    // Initialize widget drag and drop
    function initWidgetDragAndDrop() {
        // Make widget library items draggable
        $(document).on('dragstart', '.bazarino-widget-item', function(e) {
            var widgetType = $(this).data('widget-type');
            e.originalEvent.dataTransfer.setData('widgetType', widgetType);
            e.originalEvent.dataTransfer.setData('isNewWidget', 'true');
            $(this).addClass('dragging');
        });
        
        $(document).on('dragend', '.bazarino-widget-item', function() {
            $(this).removeClass('dragging');
        });
        
        // Make screen widgets draggable
        $(document).on('dragstart', '.bazarino-screen-widget', function(e) {
            var widgetId = $(this).data('widget-id');
            e.originalEvent.dataTransfer.setData('widgetId', widgetId);
            e.originalEvent.dataTransfer.setData('isNewWidget', 'false');
            $(this).addClass('dragging');
        });
        
        $(document).on('dragend', '.bazarino-screen-widget', function() {
            $(this).removeClass('dragging');
        });
        
        // Dropzone events
        var $dropzone = $('#widgets-dropzone');
        
        $dropzone.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });
        
        $dropzone.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
        });
        
        $dropzone.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            var widgetType = e.originalEvent.dataTransfer.getData('widgetType');
            var widgetId = e.originalEvent.dataTransfer.getData('widgetId');
            var isNewWidget = e.originalEvent.dataTransfer.getData('isNewWidget') === 'true';
            
            if (isNewWidget && widgetType) {
                addNewWidget(widgetType);
            } else if (!isNewWidget && widgetId) {
                // Handle reordering existing widgets
                var $draggedWidget = $('.bazarino-screen-widget[data-widget-id="' + widgetId + '"]');
                var dropY = e.originalEvent.clientY;
                var $widgets = $('.bazarino-screen-widget');
                var insertBefore = null;
                
                $widgets.each(function() {
                    var $widget = $(this);
                    var widgetY = $widget.offset().top + $widget.height() / 2;
                    
                    if (dropY < widgetY) {
                        insertBefore = $widget;
                        return false;
                    }
                });
                
                if (insertBefore) {
                    insertBefore.before($draggedWidget);
                } else {
                    $('#screen-widgets').append($draggedWidget);
                }
                
                updateWidgetOrder();
            }
        });
    }
    
    // Show screen builder
    function showScreenBuilder(screenData) {
        $('#welcome-screen').hide();
        $('#screen-builder').show();
        
        if (screenData) {
            currentScreen = screenData;
            $('#screen-title').text('Edit Screen: ' + screenData.name);
            $('#screen-name').val(screenData.name);
            $('#screen-route').val(screenData.route);
            $('#screen-type').val(screenData.screen_type);
            $('#screen-layout').val(screenData.layout);
            $('#screen-status').val(screenData.status);
            
            loadScreenWidgets(screenData.id);
        } else {
            currentScreen = null;
            $('#screen-title').text('Create New Screen');
            $('#screen-name').val('');
            $('#screen-route').val('');
            $('#screen-type').val('custom');
            $('#screen-layout').val('scroll');
            $('#screen-status').val('active');
            
            $('#screen-widgets').empty();
            $('.bazarino-dropzone-placeholder').removeClass('hidden');
        }
        
        // Highlight active screen in sidebar
        $('.bazarino-screen-item').removeClass('active');
        if (screenData) {
            $('.bazarino-screen-item[data-screen-id="' + screenData.id + '"]').addClass('active');
        }
    }
    
    // Load screen
    function loadScreen(screenId) {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_screens',
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (response.success) {
                    var screen = response.data.find(function(s) { return s.id == screenId; });
                    if (screen) {
                        showScreenBuilder(screen);
                    }
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Failed to load screen');
            }
        });
    }
    
    // Load screen widgets
    function loadScreenWidgets(screenId) {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_get_widgets',
                screen_id: screenId,
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentWidgets = response.data;
                    renderScreenWidgets();
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Failed to load widgets');
            }
        });
    }
    
    // Render screen widgets
    function renderScreenWidgets() {
        var $screenWidgets = $('#screen-widgets');
        $screenWidgets.empty();
        
        if (currentWidgets.length === 0) {
            $('.bazarino-dropzone-placeholder').removeClass('hidden');
        } else {
            $('.bazarino-dropzone-placeholder').addClass('hidden');
            
            currentWidgets.forEach(function(widget) {
                var $widget = createScreenWidgetElement(widget);
                $screenWidgets.append($widget);
            });
        }
    }
    
    // Create screen widget element
    function createScreenWidgetElement(widget) {
        var $widget = $('<div class="bazarino-screen-widget" data-widget-id="' + widget.id + '" data-widget-type="' + widget.widget_type + '" draggable="true">');
        
        var $header = $('<div class="bazarino-screen-widget-header">');
        $header.append('<div class="bazarino-screen-widget-title">' +
            '<span class="widget-icon">' + getWidgetIcon(widget.widget_type) + '</span>' +
            '<span class="widget-title">' + (widget.title || bazarinoAppBuilder.widget_types[widget.widget_type]) + '</span>' +
            '<span class="widget-type">' + bazarinoAppBuilder.widget_types[widget.widget_type] + '</span>' +
            '</div>');
        $header.append('<div class="bazarino-screen-widget-actions">' +
            '<button type="button" class="button button-small edit-widget">Edit</button> ' +
            '<button type="button" class="button button-small delete-widget">Delete</button>' +
            '</div>');
        
        var $content = $('<div class="bazarino-screen-widget-content">');
        $content.append('<div class="bazarino-screen-widget-preview">' + getWidgetPreview(widget) + '</div>');
        
        $widget.append($header);
        $widget.append($content);
        
        return $widget;
    }
    
    // Get widget preview
    function getWidgetPreview(widget) {
        var previews = {
            'slider': '🎠 Slider Widget',
            'banner': '🖼️ Banner Widget',
            'categories': '📂 Categories Widget',
            'products': '🛍️ Products Widget',
            'flash_sale': '⚡ Flash Sale Widget',
            'text_block': '📝 Text Block',
            'image_block': '🖼️ Image Block',
            'featured_products': '⭐ Featured Products',
            'recent_products': '🆕 Recent Products',
            'sale_products': '💰 Sale Products'
        };
        
        var preview = previews[widget.widget_type] || '📦 Widget';
        
        if (widget.title) {
            preview = widget.title;
        }
        
        return preview;
    }
    
    // Add new widget
    function addNewWidget(widgetType) {
        var widget = {
            id: 'new-' + (++widgetIdCounter),
            widget_type: widgetType,
            title: bazarinoAppBuilder.widget_types[widgetType],
            config: getDefaultWidgetConfig(widgetType),
            position: currentWidgets.length,
            status: 'active'
        };
        
        currentWidgets.push(widget);
        
        var $widget = createScreenWidgetElement(widget);
        $('#screen-widgets').append($widget);
        
        $('.bazarino-dropzone-placeholder').addClass('hidden');
        
        // Open widget modal for configuration
        openWidgetModal(widget);
    }
    
    // Get default widget config
    function getDefaultWidgetConfig(widgetType) {
        var configs = {
            'slider': {
                auto_play: true,
                interval: 5,
                height: 200,
                show_dots: true,
                show_arrows: false
            },
            'banner': {
                image_url: '',
                title: '',
                link: '',
                background_color: '#0073aa',
                text_color: '#ffffff'
            },
            'categories': {
                display_type: 'grid',
                items_per_row: 4,
                show_count: true,
                limit: 8
            },
            'products': {
                display_type: 'grid',
                items_per_row: 2,
                show_price: true,
                show_add_to_cart: true,
                limit: 10
            },
            'flash_sale': {
                title: 'Flash Sale',
                end_time: '',
                discount_percentage: 50,
                products: []
            },
            'text_block': {
                content: '',
                font_size: 16,
                text_color: '#333333',
                background_color: '#ffffff',
                padding: 20
            },
            'image_block': {
                image_url: '',
                link: '',
                width: '100%',
                height: 'auto'
            },
            'featured_products': {
                title: 'Featured Products',
                display_type: 'grid',
                items_per_row: 2,
                limit: 6
            },
            'recent_products': {
                title: 'Recent Products',
                display_type: 'grid',
                items_per_row: 2,
                limit: 6
            },
            'sale_products': {
                title: 'Sale Products',
                display_type: 'grid',
                items_per_row: 2,
                limit: 6
            }
        };
        
        return configs[widgetType] || {};
    }
    
    // Edit widget
    $(document).on('click', '.edit-widget', function(e) {
        e.stopPropagation();
        var widgetId = $(this).closest('.bazarino-screen-widget').data('widget-id');
        var widget = currentWidgets.find(function(w) { return w.id == widgetId; });
        if (widget) {
            openWidgetModal(widget);
        }
    });
    
    // Delete widget
    $(document).on('click', '.delete-widget', function(e) {
        e.stopPropagation();
        var widgetId = $(this).closest('.bazarino-screen-widget').data('widget-id');
        
        if (confirm(bazarinoAppBuilder.strings.confirm_delete_widget)) {
            var widgetIndex = currentWidgets.findIndex(function(w) { return w.id == widgetId; });
            if (widgetIndex !== -1) {
                var widget = currentWidgets[widgetIndex];
                
                // If it's a saved widget, delete from server
                if (typeof widget.id === 'number') {
                    deleteWidget(widget.id);
                } else {
                    // Just remove from local array
                    currentWidgets.splice(widgetIndex, 1);
                    renderScreenWidgets();
                }
            }
        }
    });
    
    // Open widget modal
    function openWidgetModal(widget) {
        var $modal = $('#widget-modal');
        var $title = $('#widget-modal-title');
        var $formContainer = $('#widget-form-container');
        
        $title.text('Configure: ' + bazarinoAppBuilder.widget_types[widget.widget_type]);
        
        var formHtml = generateWidgetForm(widget);
        $formContainer.html(formHtml);
        
        // Store current widget being edited
        $modal.data('widget', widget);
        
        $modal.show();
    }
    
    // Generate widget form
    function generateWidgetForm(widget) {
        var formHtml = '<div class="bazarino-widget-form">';
        
        // Common fields
        formHtml += '<div class="bazarino-form-group">' +
            '<label for="widget-title">Title</label>' +
            '<input type="text" id="widget-title" name="title" value="' + (widget.title || '') + '" />' +
            '</div>';
        
        // Widget-specific fields
        if (widget.widget_type === 'slider') {
            formHtml += generateSliderForm(widget.config);
        } else if (widget.widget_type === 'banner') {
            formHtml += generateBannerForm(widget.config);
        } else if (widget.widget_type === 'categories') {
            formHtml += generateCategoriesForm(widget.config);
        } else if (widget.widget_type === 'products') {
            formHtml += generateProductsForm(widget.config);
        } else if (widget.widget_type === 'flash_sale') {
            formHtml += generateFlashSaleForm(widget.config);
        } else if (widget.widget_type === 'text_block') {
            formHtml += generateTextBlockForm(widget.config);
        } else if (widget.widget_type === 'image_block') {
            formHtml += generateImageBlockForm(widget.config);
        }
        
        formHtml += '</div>';
        return formHtml;
    }
    
    // Generate slider form
    function generateSliderForm(config) {
        return '<div class="bazarino-form-group">' +
            '<label><input type="checkbox" name="auto_play" ' + (config.auto_play ? 'checked' : '') + ' /> Auto Play</label>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="interval">Interval (seconds)</label>' +
            '<input type="number" id="interval" name="interval" value="' + (config.interval || 5) + '" min="1" max="30" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="height">Height (pixels)</label>' +
            '<input type="number" id="height" name="height" value="' + (config.height || 200) + '" min="100" max="500" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label><input type="checkbox" name="show_dots" ' + (config.show_dots ? 'checked' : '') + ' /> Show Dots</label>' +
            '</div>';
    }
    
    // Generate banner form
    function generateBannerForm(config) {
        return '<div class="bazarino-form-group">' +
            '<label for="image_url">Image URL</label>' +
            '<input type="text" id="image_url" name="image_url" value="' + (config.image_url || '') + '" />' +
            '<button type="button" class="button upload-image-button">Upload Image</button>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="banner_title">Title</label>' +
            '<input type="text" id="banner_title" name="banner_title" value="' + (config.title || '') + '" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="link">Link</label>' +
            '<input type="text" id="link" name="link" value="' + (config.link || '') + '" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="background_color">Background Color</label>' +
            '<input type="text" id="background_color" name="background_color" value="' + (config.background_color || '#0073aa') + '" class="bazarino-color-picker" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="text_color">Text Color</label>' +
            '<input type="text" id="text_color" name="text_color" value="' + (config.text_color || '#ffffff') + '" class="bazarino-color-picker" />' +
            '</div>';
    }
    
    // Generate categories form
    function generateCategoriesForm(config) {
        return '<div class="bazarino-form-group">' +
            '<label for="display_type">Display Type</label>' +
            '<select id="display_type" name="display_type">' +
            '<option value="grid" ' + (config.display_type === 'grid' ? 'selected' : '') + '>Grid</option>' +
            '<option value="horizontal" ' + (config.display_type === 'horizontal' ? 'selected' : '') + '>Horizontal</option>' +
            '</select>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="items_per_row">Items Per Row</label>' +
            '<input type="number" id="items_per_row" name="items_per_row" value="' + (config.items_per_row || 4) + '" min="2" max="6" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label><input type="checkbox" name="show_count" ' + (config.show_count ? 'checked' : '') + ' /> Show Product Count</label>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="limit">Limit</label>' +
            '<input type="number" id="limit" name="limit" value="' + (config.limit || 8) + '" min="1" max="20" />' +
            '</div>';
    }
    
    // Generate products form
    function generateProductsForm(config) {
        return '<div class="bazarino-form-group">' +
            '<label for="display_type">Display Type</label>' +
            '<select id="display_type" name="display_type">' +
            '<option value="grid" ' + (config.display_type === 'grid' ? 'selected' : '') + '>Grid</option>' +
            '<option value="list" ' + (config.display_type === 'list' ? 'selected' : '') + '>List</option>' +
            '</select>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="items_per_row">Items Per Row</label>' +
            '<input type="number" id="items_per_row" name="items_per_row" value="' + (config.items_per_row || 2) + '" min="1" max="4" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label><input type="checkbox" name="show_price" ' + (config.show_price ? 'checked' : '') + ' /> Show Price</label>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label><input type="checkbox" name="show_add_to_cart" ' + (config.show_add_to_cart ? 'checked' : '') + ' /> Show Add to Cart</label>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="limit">Limit</label>' +
            '<input type="number" id="limit" name="limit" value="' + (config.limit || 10) + '" min="1" max="20" />' +
            '</div>';
    }
    
    // Generate flash sale form
    function generateFlashSaleForm(config) {
        return '<div class="bazarino-form-group">' +
            '<label for="flash_title">Title</label>' +
            '<input type="text" id="flash_title" name="flash_title" value="' + (config.title || 'Flash Sale') + '" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="end_time">End Time</label>' +
            '<input type="datetime-local" id="end_time" name="end_time" value="' + (config.end_time || '') + '" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="discount_percentage">Discount Percentage</label>' +
            '<input type="number" id="discount_percentage" name="discount_percentage" value="' + (config.discount_percentage || 50) + '" min="1" max="99" />' +
            '</div>';
    }
    
    // Generate text block form
    function generateTextBlockForm(config) {
        return '<div class="bazarino-form-group">' +
            '<label for="content">Content</label>' +
            '<textarea id="content" name="content" rows="5">' + (config.content || '') + '</textarea>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="font_size">Font Size (px)</label>' +
            '<input type="number" id="font_size" name="font_size" value="' + (config.font_size || 16) + '" min="10" max="48" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="text_color">Text Color</label>' +
            '<input type="text" id="text_color" name="text_color" value="' + (config.text_color || '#333333') + '" class="bazarino-color-picker" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="background_color">Background Color</label>' +
            '<input type="text" id="background_color" name="background_color" value="' + (config.background_color || '#ffffff') + '" class="bazarino-color-picker" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="padding">Padding (px)</label>' +
            '<input type="number" id="padding" name="padding" value="' + (config.padding || 20) + '" min="0" max="50" />' +
            '</div>';
    }
    
    // Generate image block form
    function generateImageBlockForm(config) {
        return '<div class="bazarino-form-group">' +
            '<label for="image_url">Image URL</label>' +
            '<input type="text" id="image_url" name="image_url" value="' + (config.image_url || '') + '" />' +
            '<button type="button" class="button upload-image-button">Upload Image</button>' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="link">Link</label>' +
            '<input type="text" id="link" name="link" value="' + (config.link || '') + '" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="width">Width</label>' +
            '<input type="text" id="width" name="width" value="' + (config.width || '100%') + '" />' +
            '</div>' +
            '<div class="bazarino-form-group">' +
            '<label for="height">Height</label>' +
            '<input type="text" id="height" name="height" value="' + (config.height || 'auto') + '" />' +
            '</div>';
    }
    
    // Save widget
    function saveWidget() {
        var $modal = $('#widget-modal');
        var widget = $modal.data('widget');
        var formData = collectWidgetFormData();
        
        // Update widget data
        widget.title = formData.title;
        widget.config = formData;
        
        // Update widget in array
        var widgetIndex = currentWidgets.findIndex(function(w) { return w.id == widget.id; });
        if (widgetIndex !== -1) {
            currentWidgets[widgetIndex] = widget;
        }
        
        // Re-render widgets
        renderScreenWidgets();
        
        // Close modal
        closeModal($modal);
        
        showNotice('success', 'Widget saved successfully!');
    }
    
    // Collect widget form data
    function collectWidgetFormData() {
        var formData = {
            title: $('#widget-title').val()
        };
        
        // Collect form fields
        $('.bazarino-widget-form input, .bazarino-widget-form select, .bazarino-widget-form textarea').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            
            if (name) {
                if ($field.attr('type') === 'checkbox') {
                    formData[name] = $field.is(':checked');
                } else {
                    formData[name] = $field.val();
                }
            }
        });
        
        return formData;
    }
    
    // Close modal
    function closeModal($modal) {
        $modal.hide();
        $modal.removeData('widget');
    }
    
    // Update widget order
    function updateWidgetOrder() {
        var widgetIds = [];
        $('.bazarino-screen-widget').each(function(index) {
            var widgetId = $(this).data('widget-id');
            widgetIds.push(widgetId);
            
            // Update position in array
            var widgetIndex = currentWidgets.findIndex(function(w) { return w.id == widgetId; });
            if (widgetIndex !== -1) {
                currentWidgets[widgetIndex].position = index;
            }
        });
        
        // Save order to server
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_reorder_widgets',
                widgets: widgetIds,
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (!response.success) {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Failed to update widget order');
            }
        });
    }
    
    // Save screen
    function saveScreen() {
        var screenName = $('#screen-name').val();
        var screenRoute = $('#screen-route').val();
        
        if (!screenName) {
            showNotice('error', bazarinoAppBuilder.strings.screen_name_required);
            return;
        }
        
        if (!screenRoute) {
            showNotice('error', bazarinoAppBuilder.strings.screen_route_required);
            return;
        }
        
        var screenData = {
            name: screenName,
            route: screenRoute,
            screen_type: $('#screen-type').val(),
            layout: $('#screen-layout').val(),
            status: $('#screen-status').val()
        };
        
        if (currentScreen) {
            screenData.screen_id = currentScreen.id;
        }
        
        var postData = $.extend({ action: 'bazarino_save_screen', nonce: bazarinoAppBuilder.nonce }, screenData);
        
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: postData,
            success: function(response) {
                if (response.success) {
                    currentScreen = response.data.data;
                    // Save widgets
                    saveScreenWidgets();
                    // Reload screens list
                    loadScreens();
                    showNotice('success', bazarinoAppBuilder.strings.save_success);
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', bazarinoAppBuilder.strings.save_error);
            }
        });
    }
    
    // Save screen widgets
    function saveScreenWidgets() {
        currentWidgets.forEach(function(widget) {
            var widgetData = {
                screen_id: currentScreen.id,
                widget_type: widget.widget_type,
                title: widget.title,
                config: widget.config,
                position: widget.position,
                status: widget.status
            };
            
            if (typeof widget.id === 'number') {
                widgetData.widget_id = widget.id;
            }
            
            var widgetPostData = $.extend({ action: 'bazarino_save_widget', nonce: bazarinoAppBuilder.nonce }, widgetData);
            
            $.ajax({
                url: bazarinoAppBuilder.ajax_url,
                type: 'POST',
                data: widgetPostData,
                success: function(response) {
                    if (response.success) {
                        // Update widget ID if it was a new widget
                        if (typeof widget.id === 'string') {
                            widget.id = response.data.data.id;
                        }
                    }
                },
                error: function() {
                    showNotice('error', 'Failed to save widget: ' + widget.title);
                }
            });
        });
    }
    
    // Delete screen
    function deleteScreen(screenId) {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_delete_screen',
                screen_id: screenId,
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (response.success) {
                    loadScreens();
                    showScreenBuilder();
                    showNotice('success', 'Screen deleted successfully!');
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Failed to delete screen');
            }
        });
    }
    
    // Delete widget
    function deleteWidget(widgetId) {
        $.ajax({
            url: bazarinoAppBuilder.ajax_url,
            type: 'POST',
            data: {
                action: 'bazarino_delete_widget',
                widget_id: widgetId,
                nonce: bazarinoAppBuilder.nonce
            },
            success: function(response) {
                if (response.success) {
                    var widgetIndex = currentWidgets.findIndex(function(w) { return w.id == widgetId; });
                    if (widgetIndex !== -1) {
                        currentWidgets.splice(widgetIndex, 1);
                        renderScreenWidgets();
                    }
                    showNotice('success', 'Widget deleted successfully!');
                } else {
                    showNotice('error', response.data);
                }
            },
            error: function() {
                showNotice('error', 'Failed to delete widget');
            }
        });
    }
    
    // Preview screen
    function previewScreen() {
        var $modal = $('#preview-modal');
        var $previewContent = $('#preview-content');
        
        // Generate preview HTML
        var previewHtml = generateScreenPreview();
        $previewContent.html(previewHtml);
        
        $modal.show();
    }
    
    // Generate screen preview
    function generateScreenPreview() {
        var html = '<div style="padding: 20px;">';
        
        if (currentScreen) {
            html += '<h3 style="margin: 0 0 20px 0; text-align: center;">' + currentScreen.name + '</h3>';
            
            currentWidgets.forEach(function(widget) {
                html += generateWidgetPreviewHtml(widget);
            });
        } else {
            html += '<p style="text-align: center; color: #666;">No screen selected</p>';
        }
        
        html += '</div>';
        return html;
    }
    
    // Generate widget preview HTML
    function generateWidgetPreviewHtml(widget) {
        var html = '<div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">';
        
        if (widget.title) {
            html += '<h4 style="margin: 0 0 10px 0;">' + widget.title + '</h4>';
        }
        
        html += '<div style="background: #e0e0e0; height: 100px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666;">';
        html += getWidgetPreview(widget);
        html += '</div>';
        
        html += '</div>';
        return html;
    }
    
    // Show notice
    function showNotice(type, message) {
        var $notices = $('#bazarino-app-builder-notices');
        var $notice = $('<div class="bazarino-app-builder-notice ' + type + '">' + message + '</div>');
        
        $notices.append($notice);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $notice.remove();
            });
        }, 3000);
    }
    
    // Initialize color pickers
    function initColorPickers() {
        $(document).on('click', '.bazarino-color-picker', function() {
            var $input = $(this);
            
            if ($input.data('wp-color-picker')) {
                return;
            }
            
            $input.wpColorPicker({
                change: function() {
                    // Color changed
                },
                clear: function() {
                    // Color cleared
                }
            });
        });
    }
    
    // Initialize media uploaders
    function initMediaUploaders() {
        $(document).on('click', '.upload-image-button', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $input = $button.siblings('input[type="text"]');
            
            var frame = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url);
            });
            
            frame.open();
        });
    }
    
    // Initialize everything
    initAppBuilder();
    initColorPickers();
    initMediaUploaders();
});