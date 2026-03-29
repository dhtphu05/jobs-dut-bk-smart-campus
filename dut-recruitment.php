<?php
/**
 * Plugin Name: DUT Recruitment
 * Plugin URI: https://dutjobs.local
 * Description: Recruitment orchestration layer for DUT Jobs. Adds dependency checks and REST API endpoints for the new recruitment flow.
 * Version: 0.1.0
 * Author: DUT Jobs
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: dut-recruitment
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DUT_RECRUITMENT_VERSION', '0.1.0' );
define( 'DUT_RECRUITMENT_FILE', __FILE__ );
define( 'DUT_RECRUITMENT_PATH', plugin_dir_path( __FILE__ ) );
define( 'DUT_RECRUITMENT_URL', plugin_dir_url( __FILE__ ) );

require_once DUT_RECRUITMENT_PATH . 'includes/helpers.php';
require_once DUT_RECRUITMENT_PATH . 'includes/class-activator.php';
require_once DUT_RECRUITMENT_PATH . 'includes/class-permissions.php';
require_once DUT_RECRUITMENT_PATH . 'includes/class-application-service.php';
require_once DUT_RECRUITMENT_PATH . 'includes/class-rest-applications-controller.php';
require_once DUT_RECRUITMENT_PATH . 'includes/class-plugin.php';

register_activation_hook( DUT_RECRUITMENT_FILE, [ 'DUT_Recruitment\\Activator', 'activate' ] );

DUT_Recruitment\Plugin::instance();
