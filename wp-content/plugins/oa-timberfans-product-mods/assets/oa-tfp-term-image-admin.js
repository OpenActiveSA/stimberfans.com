jQuery(document).ready(function($) {
    var frame;
    
    function setImage(id, url) {
        $('#oa-tfp_term_image').val(id);
        $('#oa-tfp_term_image_preview').html(url ? '<img src="'+url+'" style="max-width:100px;" />' : '');
        $('.oa-tfp-remove-term-image').show();
    }
    
    function clearImage() {
        $('#oa-tfp_term_image').val('');
        $('#oa-tfp_term_image_preview').empty();
        $('.oa-tfp-remove-term-image').hide();
    }
    
    $(document).on('click', '.oa-tfp-upload-term-image', function(e) {
        e.preventDefault();
        if (frame) {
            frame.open();
            return;
        }
        frame = wp.media({
            title: 'Select or Upload Image',
            button: { text: 'Use this image' },
            multiple: false
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            setImage(attachment.id, attachment.url);
        });
        frame.open();
    });
    
    $(document).on('click', '.oa-tfp-remove-term-image', function(e) {
        e.preventDefault();
        clearImage();
    });
    
    // Clear image when success message appears (indicates successful submission)
    $(document).on('DOMNodeInserted', function(e) {
        if (e.target.nodeType === 1 && e.target.classList.contains('notice-success')) {
            setTimeout(function() {
                clearImage();
            }, 200);
        }
    });
    
    // Also clear image when the page loads (for fresh add new term pages)
    if (window.location.href.indexOf('edit-tags.php') !== -1 && 
        window.location.href.indexOf('action=add') !== -1) {
        clearImage();
    }
    
    // Clear image when form is reset or when adding new term
    $(document).on('click', 'input[type="submit"]', function() {
        if (window.location.href.indexOf('edit-tags.php') !== -1 && 
            window.location.href.indexOf('action=add') !== -1) {
            setTimeout(function() {
                clearImage();
            }, 500);
        }
    });
}); 