<?php
namespace wpubasemodal_0_2_0;

/*
Class Name: WPU Base Modal
Description: A class to display a modal in WordPress
Version: 0.2.0
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
    public function get_modal_html($message = '', $args = array()) {
        $args = wp_parse_args($args, array(
            'close_label' => __('Close', __NAMESPACE__),
            'title' => ''
        ));

        $this->count++;
        $p = $this->prefix;
        $id = $p . '-modal-' . $this->count;

        $html = '<div id="' . esc_attr($id) . '" class="' . esc_attr($p) . '-modal" role="dialog" aria-modal="true">';
        $html .= '<div class="' . esc_attr($p) . '-modal-content">';
        $html .= '<span class="' . esc_attr($p) . '-modal-close" role="button" tabindex="0" aria-label="' . esc_attr($args['close_label']) . '">&times;</span>';
        if ($args['title']) {
            $html .= '<h2 class="' . esc_attr($p) . '-modal-title">' . esc_html($args['title']) . '</h2>';
        }
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

        $css_content = file_get_contents(__DIR__ . '/assets/modal.css');
        $css_content = str_replace('wpubasemodalplaceholder', $p, $css_content);

        $js_content = file_get_contents(__DIR__ . '/assets/modal.js');
        $js_content = str_replace('wpubasemodalplaceholder', $p, $js_content);

        return '<style>' . $this->compress_code($css_content) . '</style>' . '<script>' . $this->compress_code($js_content) . '</script>';
    }

    private function compress_code($code) {
        $code = preg_replace('/\s+/', ' ', $code);
        $code = str_replace('; ', ';', $code);
        return $code;
    }
}
