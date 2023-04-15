document.addEventListener("DOMContentLoaded", function() {
    'use strict';
    var locked = false,
        $fields = document.querySelectorAll('.wpubasefield-input input, .wpubasefield-input select');

    if (!wp.data || !$fields.length) {
        return;
    }

    jQuery('.wpubasefield-list').on('change', wpubasefields_check_form_validity);
    wpubasefields_check_form_validity();

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
});
