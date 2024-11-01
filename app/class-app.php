<?php

/**
 * Main class of the plugin
 *
 * @package CF7_STREAK_CRM_INTEGRATION
 */
namespace CF7_STREAK_CRM_INTEGRATION;

use  Monolog\Handler\StreamHandler;
use  Monolog\Logger;
/**
 * Class App
 */
class App {

	private $plugin_name;
	private $version;
	/**
	 * Register plugin name,version and hooks
	 *
	 * @param string $plugin_name
	 * @param string $version
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->register_hooks();
	}

	/**
	 * Register app hooks
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'wpcf7_editor_panels', array( $this, 'add_panel' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action(
			'wpcf7_save_contact_form',
			array( $this, 'save_fields' ),
			10,
			3
		);
		add_action( 'plugins_loaded', array( $this, 'load_action' ), 10 );
		add_filter( 'wpcf7_before_send_mail', array( $this, 'send_contact' ) );
		add_action( 'admin_menu', array( $this, 'add_sub_menu_page' ), 999 );
	}

	/**
	 * Add submenu page to elementor
	 *
	 * @return void
	 */
	public function add_sub_menu_page() {
		add_submenu_page(
			'wpcf7',
			'Streak CRM',
			'Streak CRM',
			'manage_options',
			'cf7-streak-crm-integration',
			array( $this, 'display_option_page' )
		);
	}

	/**
	 * Sub menu page callback
	 *
	 * @return void
	 */
	public function display_option_page() {
		include_once 'option-page.php';
	}

	/**
	 * Send contact to CRM
	 *
	 * @param array   $form
	 * @param boolean $result
	 * @return array
	 */
	public function send_contact( $form ) {
		$form_id = $form->id();
		$service = new Action();
		if ( ! $service->validate_api_key() ) {
			return;
		}
		$count = intval( get_post_meta( $form_id, 'cfsci_pipelines_number', true ) );
		if ( $count === 0 ) {
			return;
		}
		for ( $i = 0;  $i < $count;  $i++ ) {
			$enabled = get_post_meta( $form_id, 'cfsci_enabled_' . $i, true );
			if ( $enabled !== 'on' ) {
				continue;
			}

			$team_key = get_post_meta( $form_id, 'cfsci_team_' . $i, true );
			// Creating Contact

			if ( intval( get_post_meta( $form_id, 'cfsci_type_' . $i, true ) ) === 1 ) {
				$person      = array_filter( get_post_meta( $form_id, 'cfsci_pipeline_person_' . $i, true ) );
				$person_data = $this->load_form_value( $person, $_POST );
				$data        = $person_data;
				$contact     = $service->request(
					'teams/' . $team_key . '/contacts/?getIfExisting=true',
					wp_json_encode( $data ),
					'POST',
					'v2'
				);

				if ( is_array( $contact ) && isset( $contact['status'] ) && $contact['status'] == 'error' ) {
					self::log( $form_id, $contact['message'], array( 'create_contact' ) );
					return;
				}

				$box_name = $this->get_box_name( $contact );
			} else {
				// Creating Organization
				$org      = array_filter( get_post_meta( $form_id, 'cfsci_pipeline_org_' . $i, true ) );
				$org_data = $this->load_form_value( $org, $_POST );
				$data     = array(
					'domains' => $org_data['domains'],
				);
				$contact  = $service->request(
					'teams/' . $team_key . '/organizations/?getIfExisting=true',
					wp_json_encode( $data ),
					'POST',
					'v2'
				);

				if ( is_array( $contact ) && isset( $contact['status'] ) && $contact['status'] == 'error' ) {
					self::log( $form_id, $contact['message'], array( 'create_organization' ) );
					return;
				}

				// Update box with Organization
				$org_key            = $contact->key;
				$update_box_contact = $service->request(
					'organizations/' . $org_key,
					wp_json_encode( $org_data ),
					'POST',
					'v2'
				);

				if ( is_array( $update_box_contact ) && isset( $update_box_contact['status'] ) && $update_box_contact['status'] == 'error' ) {
					self::log( $form_id, $update_box_contact['message'], array( 'update_organization' ) );
					return;
				}

				$box_name = $this->get_box_name( $update_box_contact, true );
			}

			// Create a box with the contact
			$pipeline_key = get_post_meta( $form_id, 'cfsci_pipeline_key_' . $i, true );
			$box_data     = array(
				'name' => $box_name,
			);
			$create_box   = $service->request(
				'pipelines/' . $pipeline_key . '/boxes/',
				wp_json_encode( $box_data ),
				'POST',
				'v2'
			);

			if ( is_array( $create_box ) && isset( $create_box['status'] ) && $create_box['status'] == 'error' ) {
				self::log( $form_id, $create_box['message'], array( 'create_box' ) );
				return;
			}

			// Update box with contact/Organization
			$box_key         = $create_box->boxKey;
			$ad_contact      = new \stdClass();
			$ad_contact->key = $contact->key;

			if ( intval( get_post_meta( $form_id, 'cfsci_type_' . $i, true ) ) === 1 ) {
				$update_box_data = array(
					'contacts' => array( $ad_contact ),
				);
			} else {
				$update_box_data = array(
					'organizations' => array( $ad_contact ),
				);
			}

			$update_box_contact = $service->request(
				'boxes/' . $box_key,
				wp_json_encode( $update_box_data ),
				'POST',
				'v1'
			);

			if ( is_array( $update_box_contact ) && isset( $update_box_contact['status'] ) && $update_box_contact['status'] == 'error' ) {
				self::log( $form_id, $update_box_contact['message'], array( 'update_box' ) );
				return;
			}
		}
	}

	/**
	 * Format date to streak format
	 *
	 * @param string $val
	 * @return string
	 */
	public function format_date( $val ) {

		if ( ( $timestamp = strtotime( $val ) ) !== false ) {
			return strtotime( $val ) * 1000;
		} else {
			return $val;
		}

	}

	/**
	 * Get pipline box name
	 *
	 * @param object $contact
	 * @return string
	 */
	public function get_box_name( $contact, $org = false ) {
		// Organization
		if ( $org === true ) {

			if ( $contact->name != '' ) {
				return $contact->name;
			} elseif ( $contact->industry != '' ) {
				return $contact->industry;
			} else {
				return $contact->domains[0];
			}
		}
		// Contact

		if ( $contact->title != '' ) {
			return $contact->title;
		} elseif ( $contact->givenName != '' ) {
			return $contact->givenName;
		} else {
			return $contact->emailAddresses[0];
		}

	}

	/**
	 * Map form values to contact information
	 *
	 * @param array $array
	 * @param array $post
	 * @return array
	 */
	public function load_form_value( $array, $post ) {
		if ( ! is_array( $array ) ) {
			return;
		}
		$new = array();
		foreach ( $array as $key => $val ) {

			if ( $key === 'emailAddresses' || $key === 'addresses' || $key === 'phoneNumbers' || $key === 'domains' ) {

				if ( $key === 'domains' ) {
					$valid_url = wp_parse_url( $post[ $val ] );
					if ( isset( $valid_url['host'] ) ) {
						$value = $valid_url['host'];
					}
					$new[ $key ] = array( sanitize_text_field( $value ) );
				} else {
					$new[ $key ] = array( sanitize_text_field( $post[ $val ] ) );
				}
			} else {
				$new[ $key ] = sanitize_text_field( $post[ $val ] );
			}
		}
		return $new;
	}

	/**
	 * Load Action Class
	 *
	 * @return void
	 */
	public function load_action() {
		include_once 'class-action.php';
	}

	/**
	 * Register option page settings
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'cfsci_option_page', 'cfsci_api_key' );
	}

	/**
	 * Save fields in CF7 Form
	 *
	 * @param array  $contact_form
	 * @param array  $args
	 * @param string $context
	 * @return array
	 */
	public function save_fields( $contact_form, $args, $context ) {
		if ( $args['id'] == null ) {
			return;
		}
		if ( ! isset( $_POST['cfsci_pipelines_number'] ) || intval( $_POST['cfsci_pipelines_number'] ) === 0 ) {
			return;
		}
		// Pipeline counts
		$count = intval( $_POST['cfsci_pipelines_number'] );
		update_post_meta( $args['id'], 'cfsci_pipelines_number', $count );
		// Logging

		if ( isset( $_POST['cfsci_logging'] ) ) {
			update_post_meta( $args['id'], 'cfsci_logging', sanitize_text_field( $_POST['cfsci_logging'] ) );
		} else {
			update_post_meta( $args['id'], 'cfsci_logging', 0 );
		}

		for ( $i = 0;  $i < $count;  $i++ ) {
			// Update Type
			if ( isset( $_POST[ 'cfsci_type_' . $i ] ) ) {
				update_post_meta( $args['id'], 'cfsci_type_' . $i, intval( $_POST[ 'cfsci_type_' . $i ] ) );
			}
			// Update pipeline key
			if ( isset( $_POST[ 'cfsci_pipeline_key_' . $i ] ) ) {
				update_post_meta( $args['id'], 'cfsci_pipeline_key_' . $i, sanitize_text_field( $_POST[ 'cfsci_pipeline_key_' . $i ] ) );
			}
			// Update team
			if ( isset( $_POST[ 'cfsci_team_' . $i ] ) ) {
				update_post_meta( $args['id'], 'cfsci_team_' . $i, sanitize_text_field( $_POST[ 'cfsci_team_' . $i ] ) );
			}
			// Update enable

			if ( isset( $_POST[ 'cfsci_enabled_' . $i ] ) ) {
				update_post_meta( $args['id'], 'cfsci_enabled_' . $i, sanitize_text_field( $_POST[ 'cfsci_enabled_' . $i ] ) );
			} else {
				update_post_meta( $args['id'], 'cfsci_enabled_' . $i, 0 );
			}

			// Update contact
			if ( isset( $_POST[ 'cfsci_pipeline_person_' . $i ] ) ) {
				update_post_meta( $args['id'], 'cfsci_pipeline_person_' . $i, $this->sanitize_array( $_POST[ 'cfsci_pipeline_person_' . $i ] ) );
			}
			// Update Organization
			if ( isset( $_POST[ 'cfsci_pipeline_org_' . $i ] ) ) {
				update_post_meta( $args['id'], 'cfsci_pipeline_org_' . $i, $this->sanitize_array( $_POST[ 'cfsci_pipeline_org_' . $i ] ) );
			}
		}
		return $contact_form;
	}

	/**
	 * Sanitize array
	 *
	 * @param array $array
	 * @return array
	 */
	public function sanitize_array( $array ) {
		if ( empty( $array ) ) {
			return;
		}
		$new = array();
		foreach ( $array as $key => $val ) {

			if ( is_array( $val ) ) {
				foreach ( $val as $k => $v ) {
					$val[ $k ] = sanitize_text_field( wp_unslash( $v ) );
				}
				$new[ $key ] = $val;
			} else {
				$new[ $key ] = sanitize_text_field( wp_unslash( $val ) );
			}
		}
		return $new;
	}

	/**
	 * Add panel to CF7
	 *
	 * @param array $panels
	 * @return array
	 */
	public function add_panel( $panels ) {
		if ( $this->validate_api_key() ) {
			$panels['streak-crm-tab'] = array(
				'title'    => __( 'Streak CRM', CF7_STREAK_CRM_INTEGRATION ),
				'callback' => array( $this, 'panel_callback' ),
			);
		}
		return $panels;
	}

	public function panel_callback( $post ) {
		include_once 'panel-page.php';
	}

	/**
	 * Enqueue Scripts and localize it
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'wpcf7' ) ) {
			return;
		}
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'assets/js/cf7-streak-crm-integration.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' ),
			$this->version,
			true
		);
	}

	/**
	 * Validate API Key in option page
	 *
	 * @return array
	 */
	public function validate_api_key() {
		$action     = new Action();
		$validation = $action->validate_api_key();
		if ( is_array( $validation ) && isset( $validation['status'] ) && $validation['status'] === 'error' ) {
			return false;
		}
		return $validation;
	}

	/**
	 * Logging errors
	 *
	 * @param integer $form_id
	 * @param string  $message
	 * @param array   $data
	 * @param string  $type
	 * @return void
	 */
	public static function log( $form_id, $message, $data = array() ) {
		$logging = esc_attr( get_post_meta( $form_id, 'cfsci_logging', true ) );
		if ( $logging === 'on' ) {
			try {
				$directory = wp_mkdir_p( CF7_STREAK_CRM_INTEGRATION_PATH_DIR . '/logs/' );
				if ( ! empty( $data ) ) {
					$message .= ' [' . $data[0] . ']';
				}
				if ( $directory === true ) {
					error_log( $message . PHP_EOL, 3, CF7_STREAK_CRM_INTEGRATION_LOG_FILE );
				}
			} catch ( \Exception $exception ) {

			}
		}
	}

	/**
	 * Load plugin text domain
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'cf7-streak-crm-integration', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/' );
	}

}
