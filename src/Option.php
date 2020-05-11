<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class Option {
    private $name;
    private $label;
    private $description;
    protected $value;
    protected $value_changed = false;

    public function __construct($name, $label, $description = '') {
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
        $this->value = $this->default_value();
    }

    public function formatAttrs($attrs = null) {
        if ( is_null($attrs) ) {
            $attrs = $this->attrs();
        }

        return implode(
            ' ',
            array_map(function($k, $v) {
                if ( is_int($k) ) {
                    return $v;
                } else {
                    return sprintf('%s="%s"', $k, esc_attr($v));
                }
            }, array_keys($attrs), array_values($attrs))
        );
    }

    public function name() {
        return $this->name;
    }

    public function id() {
        return $this->name;
    }

    public function label() {
        return $this->label;
    }

    public function description() {
        return $this->description;
    }

    public function type() {
        return 'text';
    }

    public function default_value() {
        return '';
    }

    public function attrs() {
        return [];
    }

    public function ui_value() {
        return $this->value();
    }

    public function value() {
        return $this->value;
    }

    public function load_from_db() {
        $this->value = Database::instance()->get_option_value($this);
    }

    public function update($value) {
        $sanitized_value = $this->sanitize($value);
        if ( $sanitized_value !== $this->value ) {
            $this->value = $sanitized_value;
            $this->value_changed = true;
        }
    }

    public function value_changed() {
        return $this->value_changed;
    }

    public function sanitize($value) {
        return sanitize_text_field($value);
    }
}
