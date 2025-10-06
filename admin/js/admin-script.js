/**
 * Bazarino Admin Panel JavaScript
 */

jQuery(document).ready(function($) {
    
    /**
     * Initialize Color Pickers
     */
    $('.bazarino-color-picker').wpColorPicker();
    
    /**
     * Initialize Sortable Widget List
     */
    $('#widget-order-list').sortable({
        placeholder: 'ui-sortable-placeholder',
        helper: 'clone',
        cursor: 'move',
        tolerance: 'pointer',
        update: function() {
            updateWidgetOrder();
        }
    });
    
    /**
     * Update widget order hidden input
     */
    function updateWidgetOrder() {
        var order = [];
        $('#widget-order-list li').each(function() {
            order.push($(this).data('widget'));
        });
        $('#widget-order-input').val(JSON.stringify(order));
    }
    
    // Initialize widget order on page load
    updateWidgetOrder();
    
    /**
     * Confirm before form submission
     */
    $('form').on('submit', function(e) {
        var $form = $(this);
        
        // Skip confirmation for reset form
        if ($form.find('input[name="action"]').val() === 'bazarino_reset_config') {
            return true;
        }
        
        // Update widget order before submit
        updateWidgetOrder();
    });
    
    /**
     * Real-time preview (optional enhancement)
     */
    $('.bazarino-color-picker').on('change', function() {
        // You can add real-time preview here if needed
        console.log('Color changed:', $(this).val());
    });
    
    /**
     * Validation
     */
    $('input[type="number"]').on('change', function() {
        var $input = $(this);
        var min = parseFloat($input.attr('min'));
        var max = parseFloat($input.attr('max'));
        var value = parseFloat($input.val());
        
        if (min && value < min) {
            $input.val(min);
        }
        if (max && value > max) {
            $input.val(max);
        }
    });
    
});

