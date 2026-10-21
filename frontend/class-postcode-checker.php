<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Postcode_Checker {

	public static function init() {
		add_shortcode( 'tbs_postcode_checker', array( __CLASS__, 'render_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		add_action( 'wp_ajax_tbs_check_postcode', array( __CLASS__, 'check_postcode' ) );
		add_action( 'wp_ajax_nopriv_tbs_check_postcode', array( __CLASS__, 'check_postcode' ) );
	}

	public static function enqueue_assets() {
		wp_enqueue_style(
			'tbs-frontend-css',
			TBS_PLUGIN_URL . 'assets/frontend.css',
			array(),
			TBS_VERSION
		);

		wp_enqueue_script(
			'tbs-frontend-js',
			TBS_PLUGIN_URL . 'assets/frontend.js',
			array( 'jquery' ),
			TBS_VERSION,
			true
		);

		wp_localize_script(
			'tbs-frontend-js',
			'tbsFrontendAjax',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tbs_frontend_nonce' ),
			)
		);
	}

	public static function render_shortcode() {
		ob_start();
		?>
		<div class="tbs-postcode-checker">
			<div class="tbs-checker-box">
				<h3>Check availability in your area</h3>

				<div class="tbs-field-group">
					<label for="tbs_frontend_postcode">Postcode / PLZ</label>
					<input type="text" id="tbs_frontend_postcode" placeholder="Enter your postcode">
				</div>

				<button type="button" id="tbs_check_postcode_btn">
					Check Availability
				</button>

				<div id="tbs_postcode_result"></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function check_postcode() {
		if (
			! isset( $_POST['nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tbs_frontend_nonce' )
		) {
			wp_send_json_error(
				array(
					'message' => 'Security check failed.',
				)
			);
		}

		$postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';

		if ( empty( $postcode ) ) {
			wp_send_json_error(
				array(
					'message' => 'Please enter your postcode.',
				)
			);
		}

		$price = TBS_Pricing_Rules::get_price( $postcode );

		if ( false === $price ) {
			wp_send_json_error(
				array(
					'message' => 'Sorry, this postcode is not available yet.',
				)
			);
		}

		$routes = TBS_Admin_Booking_Meta_Boxes::get_matching_routes( $postcode );

		if ( empty( $routes ) ) {
			wp_send_json_error(
				array(
					'message' => 'Your postcode is available, but no route dates are currently available.',
					'price'   => number_format( (float) $price, 2 ),
				)
			);
		}

		$route_options = array();

		foreach ( $routes as $route ) {
			if ( class_exists( 'TBS_Route_Capacity' ) && TBS_Route_Capacity::is_full( $route->ID ) ) {
				continue;
			}

			$route_date = get_post_meta( $route->ID, '_tbs_route_date', true );
			$route_area = get_post_meta( $route->ID, '_tbs_postcode_area', true );
			$capacity   = get_post_meta( $route->ID, '_tbs_capacity', true );
			$remaining  = class_exists( 'TBS_Route_Capacity' ) ? TBS_Route_Capacity::get_remaining_capacity( $route->ID ) : null;

			$route_options[] = array(
				'id'        => $route->ID,
				'title'     => get_the_title( $route->ID ),
				'date'      => $route_date,
				'area'      => ! empty( $route_area ) ? $route_area : 'All postcodes',
				'capacity'  => ! empty( $capacity ) ? $capacity : '',
				'remaining' => null === $remaining ? '' : $remaining,
				'label'     => self::format_route_label( $route->ID ),
			);
		}

		if ( empty( $route_options ) ) {
			wp_send_json_error(
				array(
					'message' => 'Your postcode is available, but all matching route dates are currently full.',
					'price'   => number_format( (float) $price, 2 ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message'  => 'Great news! We are available in your area.',
				'price'    => number_format( (float) $price, 2 ),
				'postcode' => sanitize_text_field( $postcode ),
				'routes'   => $route_options,
			)
		);
	}

	private static function format_route_label( $route_id ) {
		$title = get_the_title( $route_id );
		$date  = get_post_meta( $route_id, '_tbs_route_date', true );
		$area  = get_post_meta( $route_id, '_tbs_postcode_area', true );

		$label = $title;

		if ( ! empty( $date ) ) {
			$label .= ' - ' . $date;
		}

		if ( ! empty( $area ) ) {
			$label .= ' - PLZ: ' . $area;
		} else {
			$label .= ' - All postcodes';
		}

		return $label;
	}
}