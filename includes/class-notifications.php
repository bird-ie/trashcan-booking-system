<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Notifications {

	public static function init() {
		// Send email when booking is created (Pending Payment)
		add_action( 'tbs_booking_created', array( __CLASS__, 'send_booking_confirmation' ), 10, 1 );

		// Send email when job is completed (Service Report ready)
		add_action( 'tbs_booking_completed', array( __CLASS__, 'send_job_completion_email' ), 10, 1 );
	}

	public static function send_booking_confirmation( $booking_id ) {
		$email = get_post_meta( $booking_id, '_tbs_customer_email', true );
		$name  = get_post_meta( $booking_id, '_tbs_customer_name', true );

		if ( ! $email ) {
			return;
		}

		$subject = 'Booking Confirmation - ' . get_bloginfo( 'name' );
		$message = "Hi " . esc_html( $name ) . ",\n\n";
		$message .= "Your booking has been created successfully.\n";
		$message .= "Booking ID: #" . $booking_id . "\n";
		$message .= "Date: " . get_post_meta( $booking_id, '_tbs_service_date', true ) . "\n";
		$message .= "Address: " . get_post_meta( $booking_id, '_tbs_street', true ) . ' ' . get_post_meta( $booking_id, '_tbs_house_number', true ) . ', ' . get_post_meta( $booking_id, '_tbs_postcode', true ) . ' ' . get_post_meta( $booking_id, '_tbs_city', true ) . "\n\n";
		$message .= "Thank you for choosing " . get_bloginfo( 'name' ) . "!";

		wp_mail( $email, $subject, $message );
	}

	public static function send_job_completion_email( $booking_id ) {
		$email = get_post_meta( $booking_id, '_tbs_customer_email', true );
		$name  = get_post_meta( $booking_id, '_tbs_customer_name', true );

		if ( ! $email ) {
			return;
		}

		$dashboard_page_id = get_option( 'tbs_dashboard_page_id' );
		$dashboard_url = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/my-dashboard/' );

		$subject = 'Service Completed - ' . get_bloginfo( 'name' );
		$message = "Hi " . esc_html( $name ) . ",\n\n";
		$message .= "Your trashcan cleaning has been completed successfully!\n\n";
		$message .= "You can view your service report, photos, and checklist in your dashboard:\n";
		$message .= $dashboard_url . "\n\n";
		$message .= "Thank you for choosing " . get_bloginfo( 'name' ) . "!";

		wp_mail( $email, $subject, $message );
	}
}