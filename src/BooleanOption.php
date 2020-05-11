<?php

namespace RMS\WP2S\GitHub;

class BooleanOption extends Option {
    const TRUE_VALUE = 'yes';

    public function type() {
        return 'checkbox';
    }

    public function ui_value() {
        return self::TRUE_VALUE;
    }

    public function partial_name() {
        return 'checkbox-option';
    }

    public function attrs() {
        $attrs = [];

        // Mark checked if saved value
        if ( $this->value() === $this->ui_value() ) {
            $attrs[]= 'checked';
        }

        return $attrs;
    }

    public function update($value) {
        $sanitized_value = $this->sanitize($value);
        $new_value = '';
        if ( $value === $this->ui_value() ) {
            $new_value = $this->ui_value();
        }

        if ( $new_value !== $this->value ) {
            $this->value = $new_value;
            $this->value_changed = true;
        }
    }

    public function choice_label() {
        // TODO make translatable
        return 'Yes';
    }
}
