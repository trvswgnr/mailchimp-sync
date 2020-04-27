<?php
/**
 * Utility/Miscellaneous functions
 *
 * @package mailchimp-symc
 */

function get_users_by_role( $role = 'administrator' ) {
	$users = get_users( array( 'role' => $role ) );
	return $users;
}

function get_role_names() {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	return $wp_roles->get_names();
}

