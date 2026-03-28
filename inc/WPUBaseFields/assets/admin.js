document.addEventListener("DOMContentLoaded", function() {
    'use strict';
    var locked = false,
        $fields = document.querySelectorAll('.wpubasefield-input input, .wpubasefield-input select, .wpubasefield-input textarea');
    if (!$fields.length) {
        return;
    }

    /* ----------------------------------------------------------
      Toggle
    ---------------------------------------------------------- */

    jQuery('.wpubasefield-input[data-toggle-display]').each(function(i, $wrapper) {
        var _data = JSON.parse(jQuery(this).attr('data-toggle-display'));
        if (!_data) {
            return;
        }

        function check_values() {
            var _visible = true,
                $input;
            for (var _key in _data) {
                $input = document.getElementById('wpubasefields_' + _key);
                if (_data[_key] == 'checked') {
                    if (!$input.checked) {
                        _visible = false;
                    }
                } else if (_data[_key] == 'notchecked') {
                    if ($input.checked) {
                        _visible = false;
                    }
                } else {
                    if (_data[_key] != $input.value) {
                        _visible = false;
                    }
                }
            }
            $wrapper.setAttribute('data-visible', _visible ? '1' : '0');
        }

        for (var _key in _data) {
            document.getElementById('wpubasefields_' + _key).addEventListener('change', check_values, 1);
        }

        check_values();
    });

    /* ----------------------------------------------------------
      Validity check on Gutemberg
    ---------------------------------------------------------- */

    jQuery('.wpubasefield-list').on('change', wpubasefields_check_form_validity);
    wpubasefields_check_form_validity();

    function wpubasefields_check_form_validity() {
        var formValid = true;
        Array.prototype.forEach.call($fields, function(el, i) {
            if (el.classList.contains('wpubasefield-input-control')) {
                return;
            }
            var $parent = jQuery(el).closest('.wpubasefield-input');
            var el_valid = !!el.checkValidity();
            $parent.attr('data-valid', el_valid ? '1' : '0');
            if (!el_valid) {
                if (el.validationMessage) {
                    $parent.find('.wpubasefield-msg-invalid').text(el.validationMessage);
                }
                formValid = false;
            }
        });
        if (wp.data) {
            if (!formValid) {
                if (!locked) {
                    locked = true;
                    wp.data.dispatch('core/editor').lockPostSaving('wpubasefields');
                }
            } else if (locked) {
                locked = false;
                wp.data.dispatch('core/editor').unlockPostSaving('wpubasefields');
            }
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

    /* ----------------------------------------------------------
      WP Link modal
    ---------------------------------------------------------- */

    var _wpubasefields_active_link_wrapper = false;

    /* Remove
    -------------------------- */

    jQuery('.wpubasefields-link-wrap__remove').on('click', function(e) {
        e.preventDefault();
        var $link = jQuery(this),
            $wrapper = $link.closest('.wpubasefield-input'),
            $main = $wrapper.find('.wpubasefields-link-wrap__main'),
            $input = $wrapper.find('input[type="hidden"]');
        $main.find('.wpubasefields-link-wrap__title').text('');
        $main.find('.wpubasefields-link-wrap__url').text('');
        $main.attr('data-haspreview', '0');
        $input.val(JSON.stringify({url: '', title: '', target: ''}));
    });

    /* Select
    -------------------------- */

    jQuery('.wpubasefields_select_link').on('click', function(e) {
        e.preventDefault();
        var $button = jQuery(this),
            $wrapper = $button.closest('.wpubasefield-input'),
            $input = $wrapper.find('input[type="hidden"]'),
            _current = {};

        try {
            _current = JSON.parse($input.val());
        } catch (e) {
            _current = {};
        }

        _wpubasefields_active_link_wrapper = $wrapper;

        /* Pre-fill the wpLink dialog */
        if (!document.getElementById('wpubasefields-link-textarea')) {
            jQuery('body').append('<textarea id="wpubasefields-link-textarea" style="display:none;"></textarea>');
        }

        wpLink.open('wpubasefields-link-textarea');

        /* Fill dialog with current values */
        jQuery('#wp-link-url').val(_current.url || '');
        jQuery('#wp-link-text').val(_current.title || '');
        jQuery('#wp-link-target').prop('checked', _current.target === '_blank');
    });

    /* Capture values on submit click (before wpLink.close resets them) */
    jQuery(document).on('click', '#wp-link-submit', function(e) {
        if (!_wpubasefields_active_link_wrapper) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();

        var $wrapper = _wpubasefields_active_link_wrapper,
            $main = $wrapper.find('.wpubasefields-link-wrap__main'),
            $input = $wrapper.find('input[type="hidden"]'),
            _url = jQuery('#wp-link-url').val(),
            _title = jQuery('#wp-link-text').val(),
            _target = jQuery('#wp-link-target').prop('checked') ? '_blank' : '';

        if (_url) {
            var _data = {url: _url, title: _title, target: _target};
            $input.val(JSON.stringify(_data));
            $main.find('.wpubasefields-link-wrap__title').text(_title || _url);
            $main.find('.wpubasefields-link-wrap__url').text(_url);
            $main.attr('data-haspreview', '1');
        }

        _wpubasefields_active_link_wrapper = false;
        wpLink.close();
    });

    /* Handle cancel / close */
    jQuery(document).on('click', '#wp-link-close, #wp-link-cancel', function() {
        _wpubasefields_active_link_wrapper = false;
    });

    jQuery('.wpubasefields_select_file').on('click', function(e) {
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
                var img_src = attachment.url,
                    _format = $wrapper.attr('data-image-preview');
                if (attachment.sizes[_format].url) {
                    img_src = attachment.sizes[_format].url;
                }
                $imageTarget.html('<img src="' + img_src + '" alt="" />');

            }
        });
        custom_uploader.open();
    });

});
