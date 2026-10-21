<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Service_Reports {

	public static function init() {
		// Future report functionality.
	}

	public static function create_report( $booking_id ) {

		$booking_id = absint( $booking_id );

		if ( ! $booking_id ) {
			return false;
		}

		$report = array(
			'booking_id'       => $booking_id,
			'before_images'    => array(),
			'after_images'     => array(),
			'worker_notes'     => '',
			'completion_notes' => '',
			'created_at'       => current_time( 'mysql' ),
		);

		update_post_meta(
			$booking_id,
			'_tbs_service_report',
			$report
		);

		return true;
	}

	public static function get_report( $booking_id ) {

		return get_post_meta(
			$booking_id,
			'_tbs_service_report',
			true
		);
	}
}