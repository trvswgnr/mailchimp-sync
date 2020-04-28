<?php
/**
 * Mailchimp API
 *
 * @package mailchimp-sync
 */

/** Mailchimp API */
class Mailchimp_API {
	/**
	 * List ID
	 *
	 * @var string
	 */
	public $list_id;

	/**
	 * Construct
	 */
	public function __construct() {
		// checks if the list is set in the <select> element and submitted, if not then check if the constant has been set for a list, otherwise use the first list in mailchimp.
		$this->list_id = isset( $_POST['mailchimp_list'] ) ? filter_input( INPUT_POST, 'mailchimp_list', FILTER_SANITIZE_STRING ) : MCSYNC_LIST;
		$lists         = $this->get_lists();
		$this->lists   = isset( $lists->lists ) ? $lists->lists : false;
		$first_list    = ! empty( $this->lists[0]->id ) ? $this->lists[0]->id : false;
		if ( empty( MCSYNC_LIST ) && ! empty( $first_list ) ) {
			$this->list_id = $first_list;
		}
	}

	/**
	 * Set the list ID for this object
	 *
	 * @param string $list_id List ID.
	 */
	public function set_list_id( $list_id ) {
		$this->list_id = $list_id;
	}

	/**
	 * GET
	 *
	 * @param string $endpoint API endpoint.
	 * @return $response
	 */
	public function get( string $endpoint = '' ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => API_URL . $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_USERPWD        => 'arbitrary:' . API_KEY,
			)
		);

		$response = json_decode( curl_exec( $curl ) );

		curl_close( $curl );
		return $response;
	}

	/**
	 * POST
	 *
	 * @param string $endpoint API endpoint.
	 * @param object $data Data to post.
	 * @return $response
	 */
	public function post( string $endpoint, $data ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => API_URL . $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => json_encode( $data ),
				CURLOPT_USERPWD        => 'arbitrary:' . API_KEY,
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: application/json',
				),
			)
		);

		$response = json_decode( curl_exec( $curl ) );

		if ( ! $response ) {
			$msg = 'An error occurred on POST request.' . PHP_EOL;
			echo $msg;
			return false;
		}

		curl_close( $curl );
		return $response;
	}

	/**
	 * PUT
	 *
	 * @param string $endpoint API endpoint.
	 * @param object $data Data to update.
	 * @return $response
	 */
	public function put( string $endpoint, $data ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => API_URL . $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => 'PUT',
				CURLOPT_POSTFIELDS     => json_encode( $data ),
				CURLOPT_USERPWD        => 'arbitrary:' . API_KEY,
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: application/json',
				),
			)
		);

		$response = json_decode( curl_exec( $curl ) );
		if ( ! $response ) {
			$msg = 'An error occurred on PUT request.' . PHP_EOL;
			echo $msg;
			return false;
		}

		curl_close( $curl );
		return $response;
	}

	/**
	 * Update
	 *
	 * @param string $endpoint API endpoint.
	 * @param object $data Data to update.
	 * @see $this->put
	 * @return $response
	 */
	public function update( string $endpoint, $data ) {
		$response = $this->put( $endpoint, $data );
		return $response;
	}

	/**
	 * DELETE
	 *
	 * @param string $endpoint API endpoint.
	 * @return $response
	 */
	public function delete( string $endpoint ) {
		$curl = curl_init();

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => API_URL . $endpoint,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST  => 'DELETE',
				CURLOPT_USERPWD        => 'arbitrary:' . API_KEY,
			)
		);

		$response = json_decode( curl_exec( $curl ) );

		curl_close( $curl );
		return $response;
	}


	/** Get lists */
	public function get_lists() {
		$response = $this->get( 'lists' );
		return $response;
	}

	/** Get tags/segments */
	public function get_tags() {
		$response = $this->get( "lists/{$this->list_id}/segments" );
		if ( ! $response || isset( $response->detail ) ) {
			echo 'An error occurred while getting tags/segments.';
			$response = false;
		}

		$tags = array();

		if ( ! empty( $response->segments ) ) {
			foreach ( $response->segments as $tag ) {
				$tags[] = array(
					'id'   => $tag->id,
					'name' => $tag->name,
				);
			}
		}
		return $tags;
	}

	/**
	 * Add tag to member
	 *
	 * @param string $email Email address.
	 * @param string $tag_id Tag ID.
	 * @return $response
	 */
	public function add_tag_to_member( $email, $tag_id ) {
		$data     = array( 'email_address' => $email );
		$response = $this->post( "lists/{$this->list_id}/segments/{$tag_id}/members", $data );
		return $reponse;
	}

	/**
	 * Add or update member
	 *
	 * @param object $data Data to update.
	 * @return $response
	 */
	public function add_or_update_member( $data ) {
		$subscriber_hash = self::subscriber_hash( $data->email_address );
		$endpoint        = "lists/{$this->list_id}/members/$subscriber_hash";
		$response        = $this->put( $endpoint, $data );
		return $response;
	}

	/**
	 * Get member count
	 *
	 * @return $count
	 */
	public function get_member_count() {
		$count = $this->get( "lists/{$this->list_id}" )->stats->member_count;
		return $count;
	}

	/**
	 * Get all members
	 *
	 * @return $members
	 */
	public function get_all_members() {
		$member_count = $this->get_member_count();
		$members      = array();
		$i            = 1;
		for ( $offset = 0; $offset < $member_count; $offset += 50 ) {
			$members = array_merge(
				$members,
				$this->get( "lists/{$this->list_id}/members?offset=$offset&count=50" )->members
			);
			echo 'Page ' . $i . PHP_EOL;
			$i++;
		}
		return $members;
	}

	/**
	 * Get member from list by email
	 *
	 * @param string $email Subscriber email address.
	 * @param string $list_id Mailchimp audience/list ID.
	 * @return $response
	 */
	public function get_member( string $email, $list_id = '' ) {
		$list_id         = ! $list_id ? $this->list_id : $list_id;
		$subscriber_hash = self::subscriber_hash( $email );
		$response        = $this->get( "lists/{$this->list_id}/members/$subscriber_hash" );
		return $response;
	}

	/**
	 * Batch operations
	 *
	 * @param array $operations Array of operations to run.
	 * @return $response
	 */
	public function batch( array $operations ) {
		$data             = new stdClass();
		$data->operations = $operations;
		$response         = $this->post( 'batches', $data );
		return $response;
	}

	/**
	 * Make subscriber hash from email
	 *
	 * @param string $email Member email address.
	 * @return $hash
	 */
	public static function subscriber_hash( string $email ) {
		$hash = md5( strtolower( $email ) );
		return $hash;
	}
}
