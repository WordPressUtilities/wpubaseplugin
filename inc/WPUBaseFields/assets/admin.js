document.addEventListener("DOMContentLoaded", function() {
    'use strict';
    var locked = false,
        $fields = document.querySelectorAll('.wpubasefield-input input, .wpubasefield-input select');
    if (!$fields.length) {
        return;
    }

    /* ----------------------------------------------------------
      Validity check on Gutemberg
    ---------------------------------------------------------- */

    if (wp.data) {
        jQuery('.wpubasefield-list').on('change', wpubasefields_check_form_validity);
        wpubasefields_check_form_validity();
    }

    function wpubasefields_check_form_validity() {
        var formValid = true;
        Array.prototype.forEach.call($fields, function(el, i) {
            if (!el.checkValidity()) {
                formValid = false;
            }
        });
        if (!formValid) {
            if (!locked) {
                locked = true;
                wp.data.dispatch('core/editor').lockPostSaving('wpubasefields');
            }
        }
        else if (locked) {
            locked = false;
            wp.data.dispatch('core/editor').unlockPostSaving('wpubasefields');
        }
    }

    /* ----------------------------------------------------------
      File upload
    ---------------------------------------------------------- */

    /* Remove
    -------------------------- */

    jQuery('.wpubasefields-file-wrap__remove').on('click', function(e) {
        e.preventDefault();
        var $link = jQuery(this),
            $wrapper = $link.closest('.wpubasefield-input'),
            $preview = $wrapper.find('.wpubasefields-file-wrap__main'),
            $imageTarget = $wrapper.find('.wpubasefields-file-image'),
            $input = $wrapper.find('input[type="hidden"]');
        $preview.find('.value').text('');
        $preview.attr('data-haspreview', '0');
        $input.val(0);
        $imageTarget.html('');
    });

    /* Select
    -------------------------- */

    jQuery('.wpubasefields_select_file').click(function(e) {
        e.preventDefault();

        var $button = jQuery(this),
            $wrapper = $button.closest('.wpubasefield-input'),
            $preview = $wrapper.find('.wpubasefields-file-wrap__main'),
            $imageTarget = $wrapper.find('.wpubasefields-file-image'),
            $input = $wrapper.find('input[type="hidden"]'),
            _isImage = ($wrapper.attr('data-type') == 'image');

        var wp_media_args = {
            title: $button.attr('title'),
            button: {
                text: $button.attr('title')
            },
            multiple: false
        };

        if (_isImage) {
            wp_media_args.library = {
                type: ['image']
            };
        }

        var custom_uploader = wp.media.frames.file_frame = wp.media(wp_media_args);
        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $preview.attr('data-haspreview', '1');
            $preview.find('.value').text(attachment.filename);
            $input.val(attachment.id);
            if (_isImage) {
                var img_src = attachment.url;
                if (attachment.sizes.thumbnail.url) {
                    img_src = attachment.sizes.thumbnail.url;
                }
                $imageTarget.html('<img src="' + img_src + '" alt="" />');

            }
        });
        custom_uploader.open();
    });

});