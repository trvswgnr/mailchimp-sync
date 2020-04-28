<?php
/**
 * Config
 *
 * @package mailchimp-sync
 */

// get data files.
define( 'MCSYNC_DIR', dirname( __FILE__ ) . '/' );
define( 'API_KEY', file_get_contents(MCSYNC_DIR . 'data/key.data') );
define( 'API_DC', substr( API_KEY, strpos( API_KEY, '-' ) + 1 ) );
define( 'API_URL', 'https://' . API_DC . '.api.mailchimp.com/3.0/' );
define( 'MCSYNC_LIST', file_get_contents(MCSYNC_DIR . 'data/list.data') );
