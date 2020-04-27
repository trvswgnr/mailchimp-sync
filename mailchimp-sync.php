<?php
/**
 * Plugin Name: UnionCentrics - Mailchimp Sync
 * Plugin URI: https://github.com/icentrics/mailchimp-sync.git
 * Description: Sync contacts between WordPress and Mailchimp.
 * Version: 1.0.0
 * Author: UnionCentrics.com
 * Author URI: https://unioncentrics.com
 * Text Domain: mailchimp-sync
 *
 * @author Travis Aaron Wagner
 * @package mailchimp-sync
 */

// prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// import dependencies.
require_once 'config.php';
require_once 'inc/class-mailchimp-api.php';
require_once 'inc/utility.php';

// register_activation_hook( __DIR__ . '/mailchimp-sync.php', 'mcs_plugin_activate' );
// function mcs_plugin_activate() {
// 	file_put_contents( MCSYNC_DIR . 'data/key.php', '' );
// }


$mailchimp = new Mailchimp_API();

if ( isset( $_POST['mailchimp_add_members'] ) ) {
	$response = add_users_to_list();
}

if ( isset( $_POST['mailchimp_delete_members'] ) ) {
	$response = delete_users_from_list();
}

add_action( 'admin_menu', 'mcs_admin_menu' );

function mcs_admin_menu() {
	add_menu_page( 'Mailchimp Sync', 'Mailchimp Sync', 'manage_options', 'mailchimp-sync', 'mcs_admin_page', 'dashicons-update', 40 );
}

function mcs_admin_page() {
	global $mailchimp;
	global $response;
	$mailchimp_lists = $mailchimp->get_lists()->lists;
	$roles           = get_role_names();
	if ( isset( $_POST['save_api_key'] ) ) {
		$api_key = filter_input( INPUT_POST, 'api_key', FILTER_SANITIZE_STRING );
		file_put_contents( MCSYNC_DIR . 'data/key.php', "<?php\n\$api_key = '$api_key';\n" );
		echo '<script>window.location.reload();</script>';
	}
	if ( isset( $_POST['get_role_count'] ) ) {
		$selected_role = $_POST['wp_role'];
		$role_count    = count( get_users_by_role( $selected_role ) );
	}
	?>
	<div class="wrap">
		<h1>Mailchimp Sync</h1>
		<p>Add, update, and delete WordPress users in Mailchimp.</p>
		<form action="" method="post">
			<table class="form-table">
				<tr>
					<th><label for="api_key">API Key</label></th>
					<td>
						<input type="password" name="api_key" id="api_key" size="45" value="<?php echo API_KEY; ?>">
						<input type="submit" name="save_api_key" class="button" value="Save">
					</td>
				</tr>
			</table>
		</form>
		<?php
		$auth = $mailchimp->get();
		if ( isset( $auth->account_id ) ) {
			echo '<span style="color: green;">Your Mailchimp API Key is valid.</span>';
		} else {
			echo '<span style="color:red;">Please enter a valid Mailchimp API Key.</span>';
			return false;
		}
		?>
		<br><br>
		<hr>
		<form action="" method="post">
			<table class="form-table">
				<tr>
					<th><label for="mailchimp_list">Audience/List: </label></th>
					<td>
						<select name="mailchimp_list" id="mailchimp_list">
						<?php foreach ( $mailchimp_lists as $list ) : ?>
						<option value="<?php echo esc_attr( $list->id ); ?>"><?php echo esc_html( $list->name ); ?></option>
						<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="wp_role">Role: </label></th>
					<td>
						<select name="wp_role" id="wp_role">
						<?php foreach ( $roles as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
						</select>
						<?php echo isset( $selected_role ) ? "<script>jQuery('#wp_role').val('$selected_role');</script>" : ''; ?>
						<input type="submit" name="get_role_count" class="button-link" value="Get Members">
						<?php if ( isset( $role_count ) ) : ?>
						<p id="role_count"><?php echo "$role_count users with $selected_role role"; ?></p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			<p><input type="submit" name="mailchimp_add_members" class="button button-primary" value="Add Users to Mailchimp List"></p>
			<p><input type="submit" name="mailchimp_delete_members" class="button button-primary" value="Remove Users from Mailchimp List"></p>
			<?php if ( isset( $response ) ) : ?>
			<h2>Response</h2>
			<pre><code><?php print_r( $response ); ?></code></pre>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
