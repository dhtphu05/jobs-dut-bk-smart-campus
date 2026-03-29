<?php

namespace DUT_Recruitment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Application_Service {
	public function create_application( $job_id, $user_id, array $payload ) {
		$job_id    = absint( $job_id );
		$user_id   = absint( $user_id );
		$resume_id = isset( $payload['resumeId'] ) ? absint( $payload['resumeId'] ) : 0;
		$message   = isset( $payload['message'] ) ? wp_kses_post( (string) $payload['message'] ) : '';

		$job = get_post( $job_id );
		if ( ! $job || 'job_listing' !== $job->post_type ) {
			return new \WP_Error(
				'dut_job_not_found',
				__( 'The selected job could not be found.', 'dut-recruitment' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! $resume_id ) {
			return new \WP_Error(
				'dut_resume_required',
				__( 'A resume is required to apply for this job.', 'dut-recruitment' ),
				[ 'status' => 422 ]
			);
		}

		$resume = get_post( $resume_id );
		if ( ! $resume || 'resume' !== $resume->post_type || ! Permissions::user_owns_resume( $resume, $user_id ) ) {
			return new \WP_Error(
				'dut_resume_not_found',
				__( 'The selected resume is invalid or does not belong to the current user.', 'dut-recruitment' ),
				[ 'status' => 404 ]
			);
		}

		if ( function_exists( 'user_has_applied_for_job' ) && user_has_applied_for_job( $user_id, $job_id ) ) {
			return new \WP_Error(
				'dut_application_exists',
				__( 'You have already applied for this job.', 'dut-recruitment' ),
				[ 'status' => 409 ]
			);
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new \WP_Error(
				'dut_candidate_not_found',
				__( 'The current user account could not be resolved.', 'dut-recruitment' ),
				[ 'status' => 404 ]
			);
		}

		$candidate_name = trim( (string) $resume->post_title );
		if ( '' === $candidate_name ) {
			$candidate_name = trim( (string) $user->display_name );
		}

		$candidate_email = (string) $user->user_email;
		if ( '' === $candidate_email ) {
			return new \WP_Error(
				'dut_candidate_email_missing',
				__( 'The current user does not have a valid email address.', 'dut-recruitment' ),
				[ 'status' => 422 ]
			);
		}

		$application_id = create_job_application(
			$job_id,
			$candidate_name,
			$candidate_email,
			$message,
			[
				'_resume_id' => $resume_id,
			],
			true,
			'resume-manager'
		);

		if ( ! $application_id ) {
			return new \WP_Error(
				'dut_application_create_failed',
				__( 'The application could not be created.', 'dut-recruitment' ),
				[ 'status' => 500 ]
			);
		}

		return $this->get_application_detail( $application_id, $user_id );
	}

	public function get_user_applications( $user_id ) {
		$query = new \WP_Query(
			[
				'post_type'      => 'job_application',
				'post_status'    => array_merge( array_keys( get_job_application_statuses() ), [ 'publish' ] ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => [
					[
						'key'   => '_candidate_user_id',
						'value' => absint( $user_id ),
						'type'  => 'NUMERIC',
					],
				],
			]
		);

		return $this->map_applications( $query->posts );
	}

	public function get_job_applications( $job_id, $current_user_id ) {
		$job = get_post( $job_id );
		if ( ! $job || 'job_listing' !== $job->post_type ) {
			return new \WP_Error(
				'dut_job_not_found',
				__( 'The selected job could not be found.', 'dut-recruitment' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! Permissions::user_can_manage_job( $job, $current_user_id ) ) {
			return new \WP_Error(
				'dut_job_forbidden',
				__( 'You are not allowed to view applications for this job.', 'dut-recruitment' ),
				[ 'status' => 403 ]
			);
		}

		$query = new \WP_Query(
			[
				'post_type'      => 'job_application',
				'post_status'    => array_merge( array_keys( get_job_application_statuses() ), [ 'publish' ] ),
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_parent'    => absint( $job_id ),
			]
		);

		return $this->map_applications( $query->posts );
	}

	public function update_application_status( $application_id, $status, $current_user_id ) {
		$application = get_post( $application_id );
		if ( ! $application || 'job_application' !== $application->post_type ) {
			return new \WP_Error(
				'dut_application_not_found',
				__( 'The selected application could not be found.', 'dut-recruitment' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! Permissions::user_can_manage_application( $application, $current_user_id ) ) {
			return new \WP_Error(
				'dut_application_forbidden',
				__( 'You are not allowed to update this application.', 'dut-recruitment' ),
				[ 'status' => 403 ]
			);
		}

		$status   = sanitize_key( (string) $status );
		$statuses = get_job_application_statuses();
		if ( ! isset( $statuses[ $status ] ) ) {
			return new \WP_Error(
				'dut_invalid_status',
				__( 'The application status is invalid.', 'dut-recruitment' ),
				[ 'status' => 422 ]
			);
		}

		$updated = wp_update_post(
			[
				'ID'          => $application->ID,
				'post_status' => $status,
			],
			true
		);

		if ( is_wp_error( $updated ) ) {
			return new \WP_Error(
				'dut_application_update_failed',
				$updated->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		return $this->get_application_detail( $application->ID, $current_user_id );
	}

	public function get_application_detail( $application_id, $current_user_id = 0 ) {
		$application = get_post( $application_id );
		if ( ! $application || 'job_application' !== $application->post_type ) {
			return new \WP_Error(
				'dut_application_not_found',
				__( 'The selected application could not be found.', 'dut-recruitment' ),
				[ 'status' => 404 ]
			);
		}

		$current_user_id = absint( $current_user_id );
		if ( $current_user_id ) {
			$candidate_user_id = (int) get_post_meta( $application->ID, '_candidate_user_id', true );
			$can_view          = $candidate_user_id === $current_user_id || Permissions::user_can_manage_application( $application, $current_user_id );

			if ( ! $can_view ) {
				return new \WP_Error(
					'dut_application_forbidden',
					__( 'You are not allowed to view this application.', 'dut-recruitment' ),
					[ 'status' => 403 ]
				);
			}
		}

		return $this->map_application( $application );
	}

	private function map_applications( array $applications ) {
		$items = [];

		foreach ( $applications as $application ) {
			$item = $this->map_application( $application );
			if ( is_array( $item ) ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	private function map_application( $application ) {
		$application = $application instanceof \WP_Post ? $application : get_post( $application );
		if ( ! $application instanceof \WP_Post ) {
			return null;
		}

		$job              = $application->post_parent ? get_post( $application->post_parent ) : null;
		$candidate_user   = (int) get_post_meta( $application->ID, '_candidate_user_id', true );
		$candidate_email  = (string) get_post_meta( $application->ID, '_candidate_email', true );
		$resume_id        = (int) get_post_meta( $application->ID, '_resume_id', true );
		$resume           = $resume_id ? get_post( $resume_id ) : null;
		$candidate_name   = get_post_plain_title( $application );
		$status           = (string) $application->post_status;

		return [
			'id'          => (int) $application->ID,
			'status'      => $status,
			'statusLabel' => get_application_status_label( $status ),
			'createdAt'   => get_application_created_at( $application ),
			'job'         => [
				'id'    => $job ? (int) $job->ID : 0,
				'title' => get_post_plain_title( $job ),
			],
			'candidate'   => [
				'userId' => $candidate_user,
				'name'   => $candidate_name,
				'email'  => $candidate_email,
			],
			'resume'      => [
				'id'    => $resume ? (int) $resume->ID : 0,
				'title' => get_post_plain_title( $resume ),
				'link'  => $resume ? get_resume_public_link( $resume->ID ) : '',
			],
			'message'     => (string) $application->post_content,
		];
	}
}
