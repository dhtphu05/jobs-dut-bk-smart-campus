<?php

namespace DUT_Recruitment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {
	public static function activate() {
		if ( ! has_dependencies() ) {
			deactivate_plugins( plugin_basename( DUT_RECRUITMENT_FILE ) );

			wp_die(
				esc_html(
					sprintf(
						/* translators: %s is a comma-separated list of plugin names. */
						__( 'DUT Recruitment requires these active plugins: %s.', 'dut-recruitment' ),
						implode( ', ', array_values( get_missing_dependencies() ) )
					)
				),
				esc_html__( 'Activation failed', 'dut-recruitment' ),
				[ 'back_link' => true ]
			);
		}

		update_option( 'dut_recruitment_version', DUT_RECRUITMENT_VERSION );
		flush_rewrite_rules();
	}
}
