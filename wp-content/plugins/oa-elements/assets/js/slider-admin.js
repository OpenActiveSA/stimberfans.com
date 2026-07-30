jQuery(document).ready(function($) {
    // Handle background type change
    $('input[name="oa_slider_background_type"]').on('change', function() {
        var selectedType = $(this).val();
        var videoField = $('.oa-video-field');
        
        if (selectedType === 'video') {
            videoField.show();
        } else {
            videoField.hide();
        }
    });
    
    // Initialize on page load
    var selectedType = $('input[name="oa_slider_background_type"]:checked').val();
    var videoField = $('.oa-video-field');
    
    if (selectedType === 'video') {
        videoField.show();
    } else {
        videoField.hide();
    }

    // Video poster image media uploader
    var posterFrame = null;
    var $posterWrap = $('.oa-video-poster-field');
    var $posterInput = $('#oa_slider_video_poster_id');
    var $posterPreview = $posterWrap.find('.oa-video-poster-preview');
    var $posterRemove = $posterWrap.find('.oa-video-poster-remove');

    $posterWrap.on('click', '.oa-video-poster-upload', function(e) {
        e.preventDefault();

        if (posterFrame) {
            posterFrame.open();
            return;
        }

        posterFrame = wp.media({
            title: 'Select Poster Image',
            button: { text: 'Use this image' },
            library: { type: 'image' },
            multiple: false
        });

        posterFrame.on('select', function() {
            var attachment = posterFrame.state().get('selection').first().toJSON();
            var src = attachment.sizes && attachment.sizes.large ? attachment.sizes.large.url : attachment.url;

            $posterInput.val(attachment.id);
            $posterPreview.find('img').attr('src', src);
            $posterPreview.show();
            $posterRemove.show();
        });

        posterFrame.open();
    });

    $posterWrap.on('click', '.oa-video-poster-remove', function(e) {
        e.preventDefault();
        $posterInput.val('');
        $posterPreview.hide().find('img').attr('src', '');
        $(this).hide();
    });
}); 