<?php
/**
 * Config
 *
 * @package mailchimp-sync
 */

require_once 'data/key.php';

define( 'API_KEY', $api_key );
define( 'API_URL', 'https://us4.api.mailchimp.com/3.0/' );
define( 'MCSYNC_DIR', dirname( __FILE__ ) . '/' );
