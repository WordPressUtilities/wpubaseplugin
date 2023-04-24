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
            $input = $wrapper.find('input[type="hidden"]');
        $preview.find('.value').text('');
        $preview.attr('data-haspreview', '0');
        $input.val(0);
    });

    /* Select
    -------------------------- */

    jQuery('.wpubasefields_select_file').click(function(e) {
        e.preventDefault();

        var $button = jQuery(this),
            $wrapper = $button.closest('.wpubasefield-input'),
            $preview = $wrapper.find('.wpubasefields-file-wrap__main'),
            $input = $wrapper.find('input[type="hidden"]');
        var custom_uploader = wp.media.frames.file_frame = wp.media({
            title: $button.attr('title'),
            button: {
                text: $button.attr('title')
            },
            multiple: false
        });
        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $preview.attr('data-haspreview', '1');
            $preview.find('.value').text(attachment.filename);
            $input.val(attachment.id);
        });
        custom_uploader.open();
    });

});
