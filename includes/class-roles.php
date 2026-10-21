<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Roles {

	public static function register_roles() {
		$worker_caps = array(
			'read'                       => true,
			'upload_files'               => true,
			'tbs_view_assigned_routes'   => true,
			'tbs_view_assigned_bookings' => true,
			'tbs_update_job_status'      => true,
			'tbs_upload_job_photos'      => true,
			'tbs_complete_reports'       => true,
		);

		$worker_role = get_role( 'tbs_worker' );

		if ( ! $worker_role ) {
			add_role(
				'tbs_worker',
				__( 'TC Worker', 'trashcan-booking-system' ),
				$worker_caps
			);
		} else {
			foreach ( $worker_caps as $cap => $grant ) {
				if ( $grant ) {
					$worker_role->add_cap( $cap );
				}
			}
		}

		self::update_existing_worker_label();

		$admin = get_role( 'administrator' );

		if ( $admin ) {
			foreach ( array_keys( $worker_caps ) as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	private static function update_existing_worker_label() {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		if ( isset( $wp_roles->roles['tbs_worker'] ) ) {
			$wp_roles->roles['tbs_worker']['name'] = 'TC Worker';
			$wp_roles->role_names['tbs_worker']    = 'TC Worker';
		}
	}
}