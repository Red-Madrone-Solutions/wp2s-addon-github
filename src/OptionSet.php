<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class OptionSet implements \IteratorAggregate {
    private $list = [];

    public function __construct() {
        $this->setup();
    }

    protected function setup() {
        $this->list[]= new Option('account', 'Account');
        $this->list[]= new Option('repository', 'Repository');
        $this->list[]= new Option('branch', 'Branch');
        $this->list[]= new EncryptedOption('personal_access_token', 'Personal Access Token');
        $this->list[]= new Option('subdirectory', 'Subdirectory');
        $this->list[]= new Option('commit_message', 'Commit Message');
    }

    public function getIterator() {
        return new \ArrayIterator($this->list);
    }
}

class Option {
    private $name;
    private $label;
    private $hint;

    public function __construct($name, $label, $hint = '') {
        $this->name = $name;
        $this->label = $label;
        $this->hint = $hint;
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
}

class EncryptedOption extends Option {
    public function type() {
        return 'password';
    }
}
