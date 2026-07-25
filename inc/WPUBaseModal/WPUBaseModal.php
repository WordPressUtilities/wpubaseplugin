<?php
namespace wpubasemodal_0_1_3;

/*
Class Name: WPU Base Modal
Description: A class to display a modal in WordPress
Version: 0.1.3
Class URI: https://github.com/WordPressUtilities/wpubaseplugin
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

defined('ABSPATH') || die;

class WPUBaseModal {

    private $prefix;
    private $count = 0;
    private $assets_printed = false;

    public function __construct($prefix = 'wpubase') {
        $this->prefix = sanitize_html_class($prefix);
    }

    /* Return the HTML for a modal. Assets are printed only on the first call. */
    public function get_modal_html($message = '') {
        $this->count++;
        $p = $this->prefix;
        $id = $p . '-modal-' . $this->count;

        $html = '<div id="' . esc_attr($id) . '" class="' . esc_attr($p) . '-modal" role="dialog" aria-modal="true">';
        $html .= '<div class="' . esc_attr($p) . '-modal-content">';
        $html .= '<span class="' . esc_attr($p) . '-modal-close" role="button" tabindex="0" aria-label="' . esc_attr__('Close', __NAMESPACE__) . '">&times;</span>';
        $html .= '<p>' . wp_kses_post($message) . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= $this->get_assets_html();

        return $html;
    }

    /* CSS + JS, printed once per instance. JS is delegated to handle N modals. */
    private function get_assets_html() {
        if ($this->assets_printed) {
            return '';
        }
        $this->assets_printed = true;
        $p = $this->prefix;

        $css = '<style>
            .' . $p . '-modal { display: flex; align-items: center; justify-content: center; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
            .' . $p . '-modal-content { background-color: #fefefe; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; }
            .' . $p . '-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
            .' . $p . '-modal-close:hover, .' . $p . '-modal-close:focus { color: black; text-decoration: none; }
        </style>';

        $js = '<script>
            (function() {
                var modalClass = "' . $p . '-modal";
                var closeClass = "' . $p . '-modal-close";
                function close(modal) { if (modal) { modal.style.display = "none"; } }
                document.addEventListener("click", function(e) {
                    if (e.target.classList.contains(closeClass)) { close(e.target.closest("." + modalClass)); }
                    else if (e.target.classList.contains(modalClass)) { close(e.target); }
                });
                document.addEventListener("keydown", function(e) {
                    if (e.key === "Escape") {
                        document.querySelectorAll("." + modalClass).forEach(close);
                    } else if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains(closeClass)) {
                        e.preventDefault();
                        close(e.target.closest("." + modalClass));
                    }
                });
            })();
        </script>';

        return $css . $js;
    }
}
