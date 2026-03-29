<?php

namespace DUT_Recruitment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	private static ?Plugin $instance = null;

	private Application_Service $application_service;

	private Rest_Applications_Controller $applications_controller;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'bootstrap' ] );
		add_action( 'admin_notices', [ $this, 'render_dependency_notice' ] );
	}

	public function bootstrap() {
		$this->application_service      = new Application_Service();
		$this->applications_controller  = new Rest_Applications_Controller( $this->application_service );

		add_action( 'rest_api_init', [ $this->applications_controller, 'register_routes' ] );
	}

	public function render_dependency_notice() {
		if ( has_dependencies() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s is a comma-separated list of plugin names. */
					__( 'DUT Recruitment is running in degraded mode because these required plugins are missing or inactive: %s.', 'dut-recruitment' ),
					implode( ', ', array_values( get_missing_dependencies() ) )
				)
			)
		);
	}
}
