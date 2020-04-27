<?php
/**
 * Mailchimp Sync
 *
 * @package mailchimp-sync
 */

// import dependencies.
require_once 'config.php';
require_once 'inc/class-mailchimp-api.php';
require_once 'inc/utility.php';

$list_id = '948450845a';

$mailchimp = new Mailchimp_API( $list_id );
$data      = '{
	"email_address": "AGoodBoi@hotmail.com",
	"status_if_new": "subscribed",
	"merge_fields": {
		"FNAME": "Goodest",
		"LNAME": "Boi"
	}
}';
$data      = json_decode( $data );
// $subscriber_hash = $mailchimp::subscriber_hash( $data->email_address );
// var_dump($subscriber_hash);
// var_dump( $mailchimp->add_or_update_member( $data ) );
// var_dump( $mailchimp->get_member_count() );
// var_dump( $mailchimp->get_all_members() );

