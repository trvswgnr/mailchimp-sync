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

/**
 * Generate random hash
 *
 * @param integer $len Length of hash.
 * @return $hash
 */
function generate_hash( $len = 10 ) {
	$hash = substr( md5( openssl_random_pseudo_bytes( 20 ) ), -$len );
	return $hash;
}

/**
 * Undocumented function
 *
 * @return $response
 */
function add_users_to_list() {
	global $mailchimp;
	$f          = FILTER_SANITIZE_STRING;
	$list_id    = filter_input( INPUT_POST, 'mailchimp_list', $f );
	$user_role  = filter_input( INPUT_POST, 'wp_role', $f );
	$tag_to_add = isset( $_POST['mc_tags'] ) ? filter_input( INPUT_POST, 'mc_tags', $f ) : false;
	$users      = get_users_by_role( $user_role );

	$operations = array();

	foreach ( $users as $user ) {
		$user_display_name  = ! empty( $user->display_name ) ? explode( ' ', $user->display_name ) : false;
		$user_firstname     = $user_display_name ? $user_display_name[0] : false;
		$user_lastname      = isset( $user_display_name[1] ) ? $user_display_name[1] : false;
		$subscriber_hash    = $mailchimp::subscriber_hash( $user->user_email );

		$add_update_member = array(
			'method' => 'PUT',
			'path'   => "/lists/$list_id/members/$subscriber_hash",
			'body'   => array(
				'email_address' => $user->user_email,
				'status_if_new' => 'subscribed',
				'status'        => 'subscribed',
			),
		);

		if ( $user_display_name ) {
			$add_update_member['body']['merge_fields'] = array();
		}
		if ( $user_firstname ) {
			$add_update_member['body']['merge_fields']['FNAME'] = $user_firstname;
		}
		if ( $user_lastname ) {
			$add_update_member['body']['merge_fields']['LNAME'] = $user_lastname;
		}

		$add_update_member['body'] = json_encode( $add_update_member['body'] );

		$operations[] = $add_update_member;

		if ( $tag_to_add ) {
			$add_tag_to_member         = array(
				'method' => 'POST',
				'path'   => "/lists/{$list_id}/segments/{$tag_to_add}/members",
				'body'   => array(
					'email_address' => $user->user_email,
				),
			);
			$add_tag_to_member['body'] = json_encode( $add_tag_to_member['body'] );
			$operations[]            = $add_tag_to_member;
		}
	}

	$response = array(
		'count'    => count( $operations ),
		'response' => $mailchimp->batch( $operations ),
	);

	return $response;
}

/**
 * Delete users from MailChimp list
 *
 * @return $response
 */
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
			'method' => 'PUT',
			'path'   => "/lists/$list_id/members/$subscriber_hash",
			'body'   => json_encode(
				array(
					'status' => 'unsubscribed',
				)
			),
		);

		$operations[] = $operation;
	}
	$response = array(
		'users'    => count( $users ),
		'response' => $mailchimp->batch( $operations ),
	);
	return $response;
}
