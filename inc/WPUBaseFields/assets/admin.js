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

    jQuery('.wpubasefields_select_file').click(function(e) {
        e.preventDefault();

        var $button = jQuery(this),
            $wrapper = $button.closest('.wpubasefield-input'),
            $input = $wrapper.find('input[type="text"]');
        var custom_uploader = wp.media.frames.file_frame = wp.media({
            title: $button.attr('title'),
            button: {
                text: $button.attr('title')
            },
            multiple: false
        });
        custom_uploader.on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $input.val(attachment.id);
        });
        custom_uploader.open();
    });

});
