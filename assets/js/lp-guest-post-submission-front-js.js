(function ($) {
    // Add class in pagination links
    $('.page-numbers').addClass('page-link');
    
    // Handle form submit request
    $('#lpgp-post-form').on('submit', function(e){
        e.preventDefault();
        
        var error = false;
        if($('#lpgp-post-title').val()==""){
            $('#invalid-post-title').show();
            error = true;
        }else{
            $('#invalid-post-title').hide();
        }

        $('.alert-wrapper .alert-heading').html('');
        $('.alert-wrapper p').html('');
        $('.alert-wrapper').addClass('d-none');
        $('.alert-wrapper .alert').removeClass('alert-danger');
        $('.alert-wrapper .alert').removeClass('alert-success');

        if(!error){
            $('#lpgp-btn-submit').html(lpgp_obj.please_wait_caption);
            var formdata = new FormData(this);
            formdata.append("action", "lpgp_submit_post");
            jQuery.ajax({
                url: lpgp_obj.admin_ajax_url,
                type: "POST",
                dataType: 'json',
                enctype: 'multipart/form-data',
                cache: false,
                contentType: false,
                processData: false,
                data: formdata,
                success: function(response) {
                    $('#lpgp-btn-submit').html(lpgp_obj.submit_btn_caption);
                    $('.alert-wrapper .alert-heading').html(response.message_heading);
                    $('.alert-wrapper p').html(response.message);
                    $('.alert-wrapper').removeClass('d-none');
                    if(response.status == 'failed'){
                        $('.alert-wrapper .alert').addClass('alert-danger');
                    }else if(response.status == 'success'){
                        $('.alert-wrapper .alert').addClass('alert-success');
                    }
                    setTimeout(() => {
                        $('.alert-wrapper').addClass('d-none');
                    }, 5000);
                    $('#lpgp-post-form')[0].reset();
                    $('#featured-image-preview').html('');
                    $('#featured-image-preview').addClass('d-none');
                }
            });
        }
    });

    // Handle media uploader
    $('#lpgp-select-image').click(function(e){
        $('#featured-image-preview').addClass('d-none');
        var lpgp_img_uploader = wp.media({
            title: 'Choose Image', 
            button: {
                text: 'Choose Image'
            },
            multiple: false,
            library: { 
                type: [ 'image/jpeg', 'image/png', 'image/jpg', 'image/webp' ]
           },
        });

        lpgp_img_uploader.on("select", function() {
            var attachment = lpgp_img_uploader.state().get('selection').first().toJSON();
            $('#lpgp-featured-image-id').val(attachment.id);
            $('#featured-image-preview').html('<img src="'+attachment.sizes.thumbnail.url+'" alt="Featured Image" class="img-thumbnail" />');
            $('#featured-image-preview').removeClass('d-none');
        });
        lpgp_img_uploader.open();
    });
}(jQuery));