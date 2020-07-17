<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit; // phpcs:ignore

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
        // phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning
        $this->list[]= new Option('account', 'Account', 'The account at GitHub that owns the repository you want to deploy to');
        $this->list[]= new Option('repository', 'Repository');
        $this->list[]= new Option('source_branch', 'Source Branch', 'Branch in GitHub that a PR for the target branch should be based on');
        $this->list[]= new Option('target_branch', 'Target Branch', 'Branch in GitHub that a PR for the deploy should be targeted at.');
        $this->list[]= new EncryptedOption('personal_access_token', 'Personal Access Token', 'Not displayed for security. Enter an invalid token value to disable.');
        $this->list[]= new Option('commit_message', 'Commit Message', 'Message to use for commit to GitHub');
        $this->list[]= new Option('pr_title', 'PR Title', 'Text to use for title of Pull Request');
        $this->list[]= new Option('pr_body', 'PR Body', 'Text to use for body of Pull Request');
        $this->list[]= new BooleanOption('merge_pr', 'Merge PR', 'Should the PR be automatically merged or not?');
        $this->list[]= new Option('pr_merge_title', 'PR Merge Title', 'Title to use for commit to merge PR');
        $this->list[]= new Option('pr_merge_message', 'PR Merge Message', 'Message to use with commit to merge PR');
        // $this->list[]= new DisabledOption('subdirectory', 'Subdirectory');
        // $this->list[]= new DisabledOption('commit_message', 'Commit Message');
        // phpcs:enable Generic.Formatting.MultipleStatementAlignment.NotSameWarning
    }

    protected function load_option_values_from_db() {
        foreach ( $this->list as $option ) {
            $option->load_from_db();
        }
    }

    protected function populate($data) {
        foreach ( $data as $name => $value ) {
            // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
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
        return array_filter(
            $this->list,
            function($option) {
                return $option->value_changed();
            }
        );
    }

    public function getIterator() {
        return new \ArrayIterator($this->list);
    }

    public function count() : int {
        return count($this->list);
    }
}

