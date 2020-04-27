<?php
/**
 * Utility/Miscellaneous functions
 *
 * @package mailchimp-symc
 */

/**
 * Get WP role names
 *
 * @return $wp_roles->get_names
 */
function get_role_names() {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	return $wp_roles->get_names();
}

/**
 * Get WP users by role
 *
 * @param string $role WordPress role.
 * @return $users
 */
function get_users_by_role( $role = 'administrator' ) {
	$users = get_users( array( 'role' => $role ) );
	return $users;
}

function generate_hash( $len = 10 ) {
	$hash = substr( md5( openssl_random_pseudo_bytes( 20 ) ), -$len );
	return $hash;
}


function add_users_to_list() {
	global $mailchimp;
	$f          = FILTER_SANITIZE_STRING;
	$list_id    = filter_input( INPUT_POST, 'mailchimp_list', $f );
	$user_role  = filter_input( INPUT_POST, 'wp_role', $f );
	$users      = get_users_by_role( $user_role );
	$operations = array();
	foreach ( $users as $user ) {
		$user_display_name = ! empty( $user->display_name ) ? explode( ' ', $user->display_name ) : false;
		$user_firstname    = $user_display_name ? $user_display_name[0] : false;
		$user_lastname     = isset( $user_display_name[1] ) ? $user_display_name[1] : false;
		$subscriber_hash   = $mailchimp::subscriber_hash( $user->user_email );
		$operation         = array(
			'method' => 'PUT',
			'path'   => "/lists/$list_id/members/$subscriber_hash",
			'body'   => array(
				'email_address' => $user->user_email,
				'status_if_new' => 'subscribed',
			),
		);

		if ( $user_display_name ) {
			$operation['body']['merge_fields'] = array();
		}
		if ( $user_firstname ) {
			$operation['body']['merge_fields']['FNAME'] = $user_firstname;
		}

		if ( $user_lastname ) {
			$operation['body']['merge_fields']['LNAME'] = $user_lastname;
		}

		$operation['body'] = json_encode( $operation['body'] );

		$operations[] = $operation;
	}
	$response = array(
		'count' => count( $operations ),
		'response' => $mailchimp->batch( $operations ),
	);
	return $response;
}


function delete_users_from_list() {
	global $mailchimp;
	$f          = FILTER_SANITIZE_STRING;
	$list_id    = filter_input( INPUT_POST, 'mailchimp_list', $f );
	$user_role  = filter_input( INPUT_POST, 'wp_role', $f );
	$users      = get_users_by_role( $user_role );
	$operations = array();
	foreach ( $users as $user ) {
		$subscriber_hash = $mailchimp::subscriber_hash( $user->user_email );

		$operation = array(
			'method' => 'DELETE',
			'path'   => "/lists/$list_id/members/$subscriber_hash",
		);

		$operations[] = $operation;
	}
	$response = array(
		'count' => count( $operations ),
		'response' => $mailchimp->batch( $operations ),
	);
	return $response;
}
