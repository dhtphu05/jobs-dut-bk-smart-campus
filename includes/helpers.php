<?php

namespace DUT_Recruitment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function get_dependency_map() {
	return [
		'wp-job-manager/wp-job-manager.php'                           => 'WP Job Manager',
		'wp-job-manager-applications/wp-job-manager-applications.php' => 'WP Job Manager - Applications',
		'wp-job-manager-resumes/wp-job-manager-resumes.php'           => 'WP Job Manager - Resume Manager',
	];
}

function is_plugin_active_by_basename( $plugin_basename ) {
	$active_plugins = (array) get_option( 'active_plugins', [] );

	if ( in_array( $plugin_basename, $active_plugins, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) );
		return in_array( $plugin_basename, $network_plugins, true );
	}

	return false;
}

function get_missing_dependencies() {
	$missing = [];

	foreach ( get_dependency_map() as $basename => $label ) {
		if ( ! is_plugin_active_by_basename( $basename ) ) {
			$missing[ $basename ] = $label;
		}
	}

	return $missing;
}

function has_dependencies() {
	return [] === get_missing_dependencies();
}

function get_dependency_error() {
	return new \WP_Error(
		'dut_missing_dependencies',
		sprintf(
			/* translators: %s is a comma-separated list of plugin names. */
			__( 'DUT Recruitment requires these active plugins: %s.', 'dut-recruitment' ),
			implode( ', ', array_values( get_missing_dependencies() ) )
		),
		[
			'status'  => 503,
			'missing' => array_values( get_missing_dependencies() ),
		]
	);
}

function get_application_status_label( $status ) {
	if ( function_exists( 'get_job_application_statuses' ) ) {
		$statuses = get_job_application_statuses();
		if ( isset( $statuses[ $status ] ) ) {
			return wp_strip_all_tags( $statuses[ $status ] );
		}
	}

	return ucwords( str_replace( [ '-', '_' ], ' ', (string) $status ) );
}

function get_allowed_resume_statuses() {
	if ( function_exists( 'get_resume_post_statuses' ) ) {
		$statuses = array_keys( get_resume_post_statuses() );
		$statuses = array_intersect( $statuses, [ 'publish', 'pending', 'expired', 'hidden' ] );
		if ( ! empty( $statuses ) ) {
			return array_values( $statuses );
		}
	}

	return [ 'publish', 'pending', 'expired', 'hidden' ];
}

function get_resume_public_link( $resume_id ) {
	$resume_id = absint( $resume_id );
	if ( ! $resume_id ) {
		return '';
	}

	if ( function_exists( 'get_resume_share_link' ) ) {
		$link = get_resume_share_link( $resume_id );
		if ( $link ) {
			return (string) $link;
		}
	}

	$link = get_permalink( $resume_id );

	return $link ? (string) $link : '';
}

function get_post_plain_title( $post ) {
	if ( ! $post instanceof \WP_Post ) {
		return '';
	}

	return html_entity_decode( wp_strip_all_tags( $post->post_title ), ENT_QUOTES, get_bloginfo( 'charset' ) );
}

function get_application_created_at( $post ) {
	if ( ! $post instanceof \WP_Post ) {
		return '';
	}

	return mysql_to_rfc3339( $post->post_date_gmt ?: $post->post_date );
}
