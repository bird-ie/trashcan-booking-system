<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Ajax {

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_tbs_get_matching_routes', array( __CLASS__, 'get_matching_routes' ) );
	}

	public static function enqueue_admin_scripts( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'tbs_booking' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'tbs-admin-js',
			TBS_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			TBS_VERSION,
			true
		);

		wp_localize_script(
			'tbs-admin-js',
			'tbsAdminAjax',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tbs_admin_ajax_nonce' ),
			)
		);
	}

	public static function get_matching_routes() {
		if (
			! isset( $_POST['nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tbs_admin_ajax_nonce' )
		) {
			wp_send_json_error(
				array(
					'message' => 'Invalid security check.',
				)
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => 'Permission denied.',
				)
			);
		}

		$postcode          = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
		$selected_route_id = isset( $_POST['selectedRouteId'] ) ? absint( $_POST['selectedRouteId'] ) : 0;

		$routes = TBS_Admin_Booking_Meta_Boxes::get_matching_routes( $postcode );

		$options = array();

		$options[] = array(
			'id'       => '',
			'label'    => 'No route assigned',
			'selected' => empty( $selected_route_id ),
		);

		foreach ( $routes as $route ) {
			$options[] = array(
				'id'       => $route->ID,
				'label'    => TBS_Admin_Booking_Meta_Boxes::get_route_label( $route->ID ),
				'selected' => absint( $selected_route_id ) === absint( $route->ID ),
			);
		}

		wp_send_json_success(
			array(
				'options' => $options,
			)
		);
	}
}