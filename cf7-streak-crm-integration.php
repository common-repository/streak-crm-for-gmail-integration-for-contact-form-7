<?php

/**
 * Plugin Name: Streak CRM For Gmail For Contact Form 7 - WordPress Plugin Paid
 * Description: Integrate your contact form 7 with Streak CRM for Gmail, Send your contacts directly to Streak CRM pipelines which can be Sales, Support, Orders and many more.
 * Plugin URI:  https://wisersteps.com/docs/contact-form-7-streak-crm-for-gmail-integration/setup-streak-crm/
 * Version:     1.1.1
 * Author:      WiserSteps
 * Author URI:  https://wisersteps.com/
 * Developer: Omar Kasem
 * Developer URI: https://www.wisersteps.com
 * Text Domain: cf7-streak-crm-integration
 * Domain Path: /languages
 *
 * @package CF7_STREAK_CRM_INTEGRATION
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
// Require Contact form 7
add_action( 'admin_init', 'cfsci_require_cf7' );
function cfsci_require_cf7()
{
    
    if ( !in_array( 'contact-form-7/wp-contact-form-7.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        add_action( 'admin_notices', function () {
            echo  '<div class="error"><p>Sorry, This Addon Requires Contact form 7 to be installed and activated.</p></div>' ;
        } );
        deactivate_plugins( plugin_basename( __FILE__ ) );
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }

}

// Define Name & Version.
define( 'CF7_STREAK_CRM_INTEGRATION', 'cf7-streak-crm-integration' );
define( 'CF7_STREAK_CRM_INTEGRATION_VERSION', '1.1.1' );
define( 'CF7_STREAK_CRM_INTEGRATION_PATH_DIR', __DIR__ );
define( 'CF7_STREAK_CRM_INTEGRATION_LOG_FILE', __DIR__ . '/logs/cf7_streak_crm.log' );
// Require Main Files.
require plugin_dir_path( __FILE__ ) . 'app/class-app.php';
new CF7_STREAK_CRM_INTEGRATION\App( CF7_STREAK_CRM_INTEGRATION, CF7_STREAK_CRM_INTEGRATION_VERSION );