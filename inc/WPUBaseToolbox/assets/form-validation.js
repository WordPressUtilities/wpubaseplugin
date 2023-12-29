document.addEventListener("DOMContentLoaded", function() {
    'use strict';
    /* Boxes */
    Array.prototype.forEach.call(document.querySelectorAll('.wpubasetoolbox-form [data-box-name]'), function($box) {
        wpubasetoolbox_box_validation($box);
    });

    /* Wizard */
    Array.prototype.forEach.call(document.querySelectorAll('.wpubasetoolbox-form[data-wizard="1"]'), wpubasetoolbox_form_setup_wizard);
});

/* ----------------------------------------------------------
  Fieldset switch
---------------------------------------------------------- */

function wpubasetoolbox_form_setup_wizard($form) {
    'use strict';
    var $fieldsets = $form.querySelectorAll('fieldset');
    var _currentFieldset = 0;

    /* Display first fieldset */
    wpubasetoolbox_fieldset_display($fieldsets, _currentFieldset);

    /* On button click : change visible fieldset */
    Array.prototype.forEach.call($form.querySelectorAll(' .form-navigation [data-dir]'), function($btn) {
        $btn.addEventListener('click', btn_click_event, 1);
    });

    function btn_click_event(e) {
        var _dir = e.target.getAttribute('data-dir');
        e.preventDefault();


        if (_dir == 'next') {
            /* Check if a field is invalid in this fieldset*/
            if (wpubasetoolbox_fieldset_fieldset_has_invalid_fields($fieldsets[_currentFieldset])) {
                return;
            }

            /* Allow next fieldset */
            _currentFieldset++;
        }
        else {
            /* Always allow previous fieldset */
            _currentFieldset--;
        }

        /* Ensure everything is ok */
        _currentFieldset = Math.max(0, _currentFieldset);
        _currentFieldset = Math.min($fieldsets.length - 1, _currentFieldset);

        /* Display fieldset */
        wpubasetoolbox_fieldset_display($fieldsets, _currentFieldset);
    }
}

function wpubasetoolbox_fieldset_fieldset_has_invalid_fields($fieldset) {
    'use strict';
    var $invalidFields = $fieldset.querySelectorAll(':invalid');
    Array.prototype.forEach.call($invalidFields, function(el) {
        el.dispatchEvent(new Event('change'));
    });
    return $invalidFields.length > 0;
}

function wpubasetoolbox_fieldset_display($fieldsets, _nb) {
    'use strict';
    Array.prototype.forEach.call($fieldsets, function(el) {
        el.style.display = 'none';
    });
    $fieldsets[_nb].style.display = '';
}

/* ----------------------------------------------------------
  Box validation
---------------------------------------------------------- */

function wpubasetoolbox_box_validation($box) {
    'use strict';
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

    Array.prototype.forEach.call($fields, function($tmp_field) {
        $tmp_field.addEventListener("invalid", function() {
            check_field_error($tmp_field);
        });
        $tmp_field.addEventListener("change", function() {
            check_field_error($tmp_field);
        });
    });

}
