<?php
/**
 * Plugin Name: Mailchimp Sync
 * Plugin URI: https://github.com/trvswgnr/mailchimp-sync.git
 * Description: Sync contacts between WordPress and Mailchimp.
 * Version: 1.1.1
 * Author: Travis Aaron Wagner
 * Author URI: https://travisaw.com
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

register_activation_hook( __DIR__ . '/mailchimp-sync.php', 'mcs_plugin_activate' );
function mcs_plugin_activate() {
	if ( ! file_exists( MCSYNC_DIR . 'data' ) ) {
		mkdir( MCSYNC_DIR . 'data', 0775, true );
	}
}

$mailchimp = new Mailchimp_API();

if ( isset( $_POST['mailchimp_add_members'] ) ) {
	$response = add_users_to_list();
}

if ( isset( $_POST['mailchimp_delete_members'] ) ) {
	$response = delete_users_from_list();
}

add_action( 'admin_menu', 'mcs_admin_menu' );

/**
 * Add admin menu
 */
function mcs_admin_menu() {
	add_menu_page( 'Mailchimp Sync', 'Mailchimp Sync', 'manage_options', 'mailchimp-sync', 'mcs_admin_page', 'dashicons-update', 40 );
}

/**
 * Display admin page
 */
function mcs_admin_page() {
	global $mailchimp;
	global $response;
	$fss = FILTER_SANITIZE_STRING;

	$auth            = $mailchimp->get();
	$roles           = get_role_names();
	$mailchimp_lists = isset( $auth->account_id ) ? $mailchimp->get_lists()->lists : '';
	$selected_list   = ! empty( MCSYNC_LIST ) ? MCSYNC_LIST : $mailchimp->list_id;
	if ( isset( $_POST['save_api_key'] ) ) {
		$api_key = filter_input( INPUT_POST, 'api_key', $fss );
		file_put_contents( MCSYNC_DIR . 'data/key.data', $api_key );
		echo '<script>window.location.reload();</script>';
	}
	if ( isset( $_POST['save_list_id'] ) ) {
		$list_id = filter_input( INPUT_POST, 'mailchimp_list', $fss );
		file_put_contents( MCSYNC_DIR . 'data/list.data', $list_id );
		echo '<script>window.location.reload();</script>';
	}
	if ( isset( $_POST['get_role_count'] ) ) {
		$selected_role = filter_input( INPUT_POST, 'wp_role', $fss );
		$role_count    = count( get_users_by_role( $selected_role ) );
	}
	?>
	<style>
	.button.button-danger {
		background-color: #C62828;
		color: #fff;
		border-color: #C62828;
	}

	.button.button-danger:hover,
	.button.button-danger:active,
	.button.button-danger:focus  {
		background-color: #B71C1C;
		color: #fff;
		border-color: #B71C1C;
	}
	</style>
	<div class="wrap">
		<h1>Mailchimp Sync</h1>
		<p>Add, update, and delete WordPress users in Mailchimp.</p>
		<form action="" method="post">
			<table class="form-table">
				<tr>
					<th><label for="api_key">API Key</label></th>
					<td>
						<input type="password" name="api_key" id="api_key" size="45" value="<?php echo esc_attr( API_KEY ); ?>">
						<input type="submit" name="save_api_key" class="button" value="Save">
					</td>
				</tr>
			</table>
		</form>
		<div class="valid-key-message">
		<?php
		if ( isset( $auth->account_id ) ) {
			echo '<span style="color: #008045;">Your Mailchimp API Key is valid.</span>';
		} else {
			echo '<span style="color: #d61f1f;">Please enter a valid Mailchimp API Key.</span>';
			return false;
		}
		?>
		</div>
		<br>
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
						<?php echo ! empty( $selected_list ) ? "<script>jQuery('#mailchimp_list').val('$selected_list');</script>" : ''; ?>
						</select>
						<input type="submit" name="save_list_id" class="button" value="Save">
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
						<input type="submit" name="get_role_count" class="button-link" value="Count Users">
						<?php if ( isset( $role_count ) ) : ?>
						<p id="role_count"><?php echo "$role_count users with $selected_role role"; ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><label for="mc_tags">Add Tag</label></th>
					<td>
						<select name="mc_tags" id="mc_tags">
						<option value="" disabled selected>Select a tag</option>
						<?php
						$tags = $mailchimp->get_tags();
						foreach ( $tags as $tag ) :
							?>
							<option value="<?php echo esc_attr( $tag['id'] ); ?>"><?php echo esc_html( $tag['name'] ); ?></option>
							<?php
						endforeach;
						?>
						</select>
					</td>
				</tr>
			</table>
			<p><input type="submit" name="mailchimp_add_members" class="button button-primary" value="Add & Subscribe Users"></p>
			<p><input type="submit" name="mailchimp_delete_members" class="button button-danger" value="Unsubscribe Users"></p>
			<?php if ( isset( $response ) ) : ?>
				<h2>Response</h2>
				<pre><code><?php print_r( $response ); ?></code></pre>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
