<?php

namespace DUT_Recruitment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rest_Applications_Controller {
	private Application_Service $service;

	public function __construct( Application_Service $service ) {
		$this->service = $service;
	}

	public function register_routes() {
		register_rest_route(
			'dut/v1',
			'/health',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'health_check' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			'dut/v1',
			'/applications',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_application' ],
				'permission_callback' => [ Permissions::class, 'can_view_own_applications' ],
				'args'                => [
					'jobId'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'resumeId' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'message'  => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'wp_kses_post',
					],
				],
			]
		);

		register_rest_route(
			'dut/v1',
			'/my-applications',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_my_applications' ],
				'permission_callback' => [ Permissions::class, 'can_view_own_applications' ],
			]
		);

		register_rest_route(
			'dut/v1',
			'/jobs/(?P<job_id>\d+)/applications',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_job_applications' ],
				'permission_callback' => [ Permissions::class, 'can_view_own_applications' ],
				'args'                => [
					'job_id' => [
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			'dut/v1',
			'/applications/(?P<application_id>\d+)/status',
			[
				'methods'             => 'PATCH',
				'callback'            => [ $this, 'update_application_status' ],
				'permission_callback' => [ Permissions::class, 'can_view_own_applications' ],
				'args'                => [
					'application_id' => [
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
					'status'         => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	public function health_check( \WP_REST_Request $request ) {
		unset( $request );

		return new \WP_REST_Response(
			[
				'ok'           => has_dependencies(),
				'plugin'       => 'dut-recruitment',
				'version'      => DUT_RECRUITMENT_VERSION,
				'dependencies' => [
					'ok'      => has_dependencies(),
					'missing' => array_values( get_missing_dependencies() ),
				],
				'endpoints'    => [
					'applications'    => '/wp-json/dut/v1/applications',
					'myApplications'  => '/wp-json/dut/v1/my-applications',
					'jobApplications' => '/wp-json/dut/v1/jobs/{jobId}/applications',
					'updateStatus'    => '/wp-json/dut/v1/applications/{id}/status',
				],
			],
			has_dependencies() ? 200 : 503
		);
	}

	public function create_application( \WP_REST_Request $request ) {
		$dependency_check = Permissions::ensure_dependencies();
		if ( is_wp_error( $dependency_check ) ) {
			return $dependency_check;
		}

		$result = $this->service->create_application(
			$request->get_param( 'jobId' ),
			get_current_user_id(),
			[
				'resumeId' => $request->get_param( 'resumeId' ),
				'message'  => $request->get_param( 'message' ),
			]
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( $result, 201 );
	}

	public function get_my_applications( \WP_REST_Request $request ) {
		unset( $request );

		$dependency_check = Permissions::ensure_dependencies();
		if ( is_wp_error( $dependency_check ) ) {
			return $dependency_check;
		}

		$items = $this->service->get_user_applications( get_current_user_id() );

		return new \WP_REST_Response(
			[
				'items' => $items,
				'total' => count( $items ),
			],
			200
		);
	}

	public function get_job_applications( \WP_REST_Request $request ) {
		$dependency_check = Permissions::ensure_dependencies();
		if ( is_wp_error( $dependency_check ) ) {
			return $dependency_check;
		}

		$result = $this->service->get_job_applications(
			$request->get_param( 'job_id' ),
			get_current_user_id()
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response(
			[
				'items' => $result,
				'total' => count( $result ),
			],
			200
		);
	}

	public function update_application_status( \WP_REST_Request $request ) {
		$dependency_check = Permissions::ensure_dependencies();
		if ( is_wp_error( $dependency_check ) ) {
			return $dependency_check;
		}

		$result = $this->service->update_application_status(
			$request->get_param( 'application_id' ),
			$request->get_param( 'status' ),
			get_current_user_id()
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( $result, 200 );
	}
}
