<?php

namespace DUT_Recruitment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Permissions {
	public static function can_access_plugin() {
		return has_dependencies();
	}

	public static function ensure_dependencies() {
		if ( has_dependencies() ) {
			return true;
		}

		return get_dependency_error();
	}

	public static function ensure_logged_in() {
		if ( is_user_logged_in() ) {
			return true;
		}

		return new \WP_Error(
			'dut_auth_required',
			__( 'You must be logged in to access recruitment data.', 'dut-recruitment' ),
			[ 'status' => 401 ]
		);
	}

	public static function can_view_own_applications() {
		$dependency_check = self::ensure_dependencies();
		if ( is_wp_error( $dependency_check ) ) {
			return $dependency_check;
		}

		return self::ensure_logged_in();
	}

	public static function user_can_manage_job( $job, $user_id = 0 ) {
		$job     = $job instanceof \WP_Post ? $job : get_post( $job );
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $job || 'job_listing' !== $job->post_type || ! $user_id ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_post', $job->ID ) ) {
			return true;
		}

		if ( absint( $job->post_author ) === $user_id ) {
			return true;
		}

		if ( function_exists( 'job_manager_user_can_edit_job' ) ) {
			$current_user = get_current_user_id();
			if ( $current_user === $user_id ) {
				return (bool) job_manager_user_can_edit_job( $job->ID );
			}
		}

		return false;
	}

	public static function user_can_manage_application( $application, $user_id = 0 ) {
		$application = $application instanceof \WP_Post ? $application : get_post( $application );

		if ( ! $application || 'job_application' !== $application->post_type ) {
			return false;
		}

		return self::user_can_manage_job( (int) $application->post_parent, $user_id );
	}

	public static function user_owns_resume( $resume, $user_id = 0 ) {
		$resume  = $resume instanceof \WP_Post ? $resume : get_post( $resume );
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $resume || 'resume' !== $resume->post_type || ! $user_id ) {
			return false;
		}

		if ( absint( $resume->post_author ) !== $user_id ) {
			return false;
		}

		return in_array( $resume->post_status, get_allowed_resume_statuses(), true );
	}
}
