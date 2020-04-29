<?php

namespace RMS\WP2S\GitHub;

if ( !defined('ABSPATH') ) exit;

class AdminNotice {
    const NOTICE_KEY = 'rms_admin_notice_data';

    private $type;
    private $message;
    private $dismissible;

    private function _valid_types() {
        return [ 'success', 'warning', 'error' ];
    }

    public function __construct($message, $type = 'success', $dismissible = true) {
        if ( !in_array($type, $this->_valid_types()) ) {
            throw new \Exception(
                "Invalid notice type: '$type' - type must be one of " . implode(', ', $this->_valid_types())
            );
        }

        $this->message = $message;
        $this->type = $type;
        $this->dismissible = $dismissible;
    }

    public function save() {
        $admin_data = self::serialize($this);
        add_option(self::NOTICE_KEY, $admin_data);
    }

    private static function serialize($notice) {
        return [
            'message' => $notice->message,
            'type' => $notice->type,
            'dismissible' => $notice->dismissible,
        ];
    }

    private static function deserialize($data) {
        return new self(
            $data['message'],
            $data['type'],
            $data['dismissible']
        );
    }

    public static function setup() {
        if ( $notice_data = get_option(self::NOTICE_KEY) ) {
            $notice = self::deserialize($notice_data);
            add_action('admin_notices', [ $notice, 'display' ]);
            delete_option(self::NOTICE_KEY);
        }
    }

    public function display() {
?>
<div class="notice notice-<?php echo $this->type; ?> <?php echo $this->dismissible ? 'is-dismissible' : ''; ?>">
    <p><?php echo esc_html($this->message); ?></p>
</div>
<?php
    }
}
