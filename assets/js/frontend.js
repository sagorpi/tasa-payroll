jQuery(document).ready(function($) {
    
    /**
     * Handle download button clicks
     */
    $('.tasa-btn-download').on('click', function(e) {
        // Show loading state
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<span class="dashicons dashicons-update"></span> Generating PDF...');
        $btn.prop('disabled', true);
        
        // Reset button after a delay (PDF will open in new tab)
        setTimeout(function() {
            $btn.html(originalText);
            $btn.prop('disabled', false);
        }, 2000);
    });
    
});

