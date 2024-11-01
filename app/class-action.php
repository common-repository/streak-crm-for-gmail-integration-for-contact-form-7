<?php
/**
 * Action class of the plugin
 *
 * @package CF7_STREAK_CRM_INTEGRATION
 */

namespace CF7_STREAK_CRM_INTEGRATION;

/**
 * Add Action after submit to cf7 form
 */
class Action {

	private $api_key;

	/**
	 * Get API Key on class instance
	 */
	public function __construct() {
		$this->api_key = sanitize_text_field(get_option( 'cfsci_api_key' ));
	}


	/**
	 * Request client
	 *
	 * @param string $base
	 * @param array  $body
	 * @param string $method
	 * @return array
	 */
	public function request( $base, $body = array(), $method = 'POST', $version = 'v1' ) {
		if ( $this->api_key == '' ) {
			return;
		}
		$args     = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type'  => 'application/json; charset=utf-8',
				'Authorization' => 'Basic ' . base64_encode( $this->api_key ),
			),
			'body'    => $body,
		);
		$url      = 'https://www.streak.com/api/' . $version . '/' . $base;
		$response = wp_remote_request( $url, $args );
		$body     = json_decode( wp_remote_retrieve_body( $response ) );
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return array(
				'status'  => 'error',
				'message' => $body->error,
			);
		}
		return json_decode( wp_remote_retrieve_body( $response ) );
	}


	/**
	 * Get Pipelines
	 *
	 * @return array
	 */
	public function get_pipelines() {
		$response = $this->request( 'pipelines/', array(), 'GET' );
		return $response;
	}


	/**
	 * Validate API Key
	 *
	 * @return array
	 */
	public function validate_api_key() {
		$response = $this->request( 'users/me/teams', array(), 'GET', 'v2' );
		return $response;
	}


	/**
	 * Get person data
	 *
	 * @return array
	 */
	public function get_person_data() {
		return array(
			'givenName'      => __( 'First Name', 'cf7-streak-crm-integration' ),
			'familyName'     => __( 'Last Name', 'cf7-streak-crm-integration' ),
			'emailAddresses' => __( 'Email Address', 'cf7-streak-crm-integration' ),
			'title'          => __( 'Title', 'cf7-streak-crm-integration' ),
			'other'          => __( 'Notes', 'cf7-streak-crm-integration' ),
			'addresses'      => __( 'Address', 'cf7-streak-crm-integration' ),
			'phoneNumbers'   => __( 'Phone Number', 'cf7-streak-crm-integration' ),
			'twitterHandle'  => __( 'Twitter Handle', 'cf7-streak-crm-integration' ),
			'facebookHandle' => __( 'Facebook Handle', 'cf7-streak-crm-integration' ),
			'linkedinHandle' => __( 'Linkedin Handle', 'cf7-streak-crm-integration' ),
			'photoUrl'       => __( 'Photo URL', 'cf7-streak-crm-integration' ),
		);
	}

	/**
	 * Get organization data
	 *
	 * @return array
	 */
	public function get_organization_data() {
		return array(
			'name'           => __( 'Organization Name', 'cf7-streak-crm-integration' ),
			'domains'        => __( 'Organization Domain', 'cf7-streak-crm-integration' ),
			'industry'       => __( 'Industry', 'cf7-streak-crm-integration' ),
			'phoneNumbers'   => __( 'Phone Number', 'cf7-streak-crm-integration' ),
			'addresses'      => __( 'Address', 'cf7-streak-crm-integration' ),
			'employeeCount'  => __( 'Employee Count', 'cf7-streak-crm-integration' ),
			'logoURL'        => __( 'Logo URL', 'cf7-streak-crm-integration' ),
			'other'          => __( 'Notes', 'cf7-streak-crm-integration' ),
			'twitterHandle'  => __( 'Twitter Handle', 'cf7-streak-crm-integration' ),
			'facebookHandle' => __( 'Facebook Handle', 'cf7-streak-crm-integration' ),
			'linkedinHandle' => __( 'Linkedin Handle', 'cf7-streak-crm-integration' ),
		);
	}

}
