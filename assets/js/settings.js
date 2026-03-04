jQuery(document).ready(function($) {

    var mediaUploader = null;

    /**
     * Upload logo button click
     */
    $(document).on('click', '.tasa-upload-logo-button', function(e) {
        e.preventDefault();

        if (typeof wp === 'undefined' || !wp.media) {
            alert('Media uploader is not available. Please refresh the page and try again.');
            return;
        }

        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Extend the wp.media object
        mediaUploader = wp.media({
            title: 'Choose Company Logo',
            button: {
                text: 'Choose Logo'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#company_logo').val(attachment.url);
            $('.tasa-logo-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;" />');
            $('.tasa-remove-logo-button').show();
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    /**
     * Remove logo button click
     */
    $(document).on('click', '.tasa-remove-logo-button', function(e) {
        e.preventDefault();
        $('#company_logo').val('');
        $('.tasa-logo-preview').html('');
        $(this).hide();
    });
});
