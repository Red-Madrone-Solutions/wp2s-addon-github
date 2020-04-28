<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class OptionSet implements \IteratorAggregate {
    private $list = [];

    public function __construct($data = [], $load_from_db = false) {
        $this->setup();
        if ( $load_from_db ) {
            $this->load_option_values_from_db();
        }
        $this->populate($data);
    }

    protected function setup() {
        $this->list[]= new Option('account', 'Account');
        $this->list[]= new Option('repository', 'Repository');
        $this->list[]= new Option('branch', 'Branch');
        $this->list[]= new EncryptedOption('personal_access_token', 'Personal Access Token');
        $this->list[]= new Option('subdirectory', 'Subdirectory');
        $this->list[]= new Option('commit_message', 'Commit Message');
    }

    protected function load_option_values_from_db() {
        foreach ( $this->list as $option ) {
            $option->load_from_db();
        }
    }

    protected function populate($data) {
        foreach ( $data as $name => $value ) {
            if ( $option = $this->findByName($name) ) {
                $option->update($value);
            }
        }
    }

    public function findByName($name) {
        foreach ( $this->list as $option ) {
            if ( $option->name() === $name ) {
                return $option;
            }
        }

        return null;
    }

    public function changedOptions() {
        return array_filter($this->list, function($option) {
            return $option->value_changed();
        });
    }

    public function getIterator() {
        return new \ArrayIterator($this->list);
    }

    public function count() : int {
        return count($this->list);
    }
}

class Option {
    private $name;
    private $label;
    private $hint;
    private $value;
    private $value_changed = false;

    public function __construct($name, $label, $hint = '') {
        $this->name = $name;
        $this->label = $label;
        $this->hint = $hint;
        $this->value = $this->default_value();
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

    public function type() {
        return 'text';
    }

    public function default_value() {
        return '';
    }

    public function value() {
        return $this->value;
    }

    public function load_from_db() {
        $this->value = Database::instance()->get_option_value($this);
    }

    public function update($value) {
        if ( $value !== '' ) {
            $sanitized_value = $this->sanitize($value);
            if ( $sanitized_value !== $this->value ) {
                $this->value = $sanitized_value;
                $this->value_changed = true;
            }
        }
    }

    public function value_changed() {
        return $this->value_changed;
    }

    public function sanitize($value) {
        return sanitize_text_field($value);
    }
}

class EncryptedOption extends Option {
    public function type() {
        return 'password';
    }
}
