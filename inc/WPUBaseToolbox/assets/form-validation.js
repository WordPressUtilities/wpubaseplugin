document.addEventListener("DOMContentLoaded", function() {
    'use strict';
    Array.prototype.forEach.call(document.querySelectorAll('.wpubasetoolbox-form [data-box-name]'), function($box, i) {
        wpubasetoolbox_box_validation($box);
    });
});

function wpubasetoolbox_box_validation($box) {
    var _id = $box.getAttribute('data-box-name'),
        $fields = $box.querySelectorAll('[name="' + _id + '"]'),
        $message = $box.querySelector('.wpubasetoolbox-form-validation-message'),
        _ischecking = false;

    if (!$fields.length || !$message) {
        return;
    }

    function check_field_error($tmp_field) {
        if (_ischecking) {
            return false;
        }
        _ischecking = true;
        var _valid = $tmp_field.checkValidity();
        _ischecking = false;
        if (_valid) {
            $box.setAttribute('data-box-error', '0');
            $message.innerHTML = '';
            return;
        }
        setTimeout(function() {
            window.scrollTo({
                top: $box.getBoundingClientRect().top + window.pageYOffset - 100,
                behavior: 'smooth'
            });
        }, 10);

        $box.setAttribute('data-box-error', '1');
        $message.innerHTML = $tmp_field.validationMessage;
    }

    Array.prototype.forEach.call($fields, function($tmp_field, i) {
        $tmp_field.addEventListener("invalid", function() {
            check_field_error($tmp_field);
        });
        $tmp_field.addEventListener("change", function() {
            check_field_error($tmp_field);
        });
    });

}
