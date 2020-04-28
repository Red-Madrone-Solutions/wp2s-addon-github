<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class OptionSet implements \IteratorAggregate {
    private $list = [];

    public function __construct($load_from_db = false, $data = []) {
        $this->setup();
        if ( $load_from_db ) {
            $this->load_option_values_from_db();
        }
        $this->populate($data);
    }

    protected function setup() {
        $this->list[]= new Option('account', 'Account', 'The account at GitHub that owns the repository you want to deploy to');
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

