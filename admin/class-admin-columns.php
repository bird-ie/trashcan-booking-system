<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Columns {

	public static function init() {
		add_filter( 'manage_tbs_booking_posts_columns', array( __CLASS__, 'booking_columns' ) );
		add_action( 'manage_tbs_booking_posts_custom_column', array( __CLASS__, 'booking_column_content' ), 10, 2 );

		add_filter( 'manage_tbs_route_posts_columns', array( __CLASS__, 'route_columns' ) );
		add_action( 'manage_tbs_route_posts_custom_column', array( __CLASS__, 'route_column_content' ), 10, 2 );

		add_action( 'restrict_manage_posts', array( __CLASS__, 'booking_filters' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_booking_filters' ) );
	}

	public static function booking_columns( $columns ) {
		return array(
			'cb'                   => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'                => 'Booking',
			'tbs_customer'         => 'Customer',
			'tbs_service_date'     => 'Service Date',
			'tbs_postcode'         => 'Postcode',
			'tbs_appointment_type' => 'Type',
			'tbs_route'            => 'Route',
			'tbs_worker'           => 'Worker',
			'tbs_status'           => 'Status',
			'tbs_order_id'         => 'Order',
			'date'                 => 'Created',
		);
	}

	public static function booking_column_content( $column, $post_id ) {
		if ( 'tbs_customer' === $column ) {
			$name  = get_post_meta( $post_id, '_tbs_customer_name', true );
			$email = get_post_meta( $post_id, '_tbs_customer_email', true );

			echo esc_html( $name ? $name : '—' );

			if ( $email ) {
				echo '<br><small>' . esc_html( $email ) . '</small>';
			}
		}

		if ( 'tbs_service_date' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_tbs_service_date', true ) ?: '—' );
		}

		if ( 'tbs_postcode' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_tbs_postcode', true ) ?: '—' );
		}

		if ( 'tbs_appointment_type' === $column ) {
			$type = get_post_meta( $post_id, '_tbs_appointment_type', true );
			echo esc_html( TBS_Admin_Booking_Meta_Boxes::format_appointment_type( $type ) );
		}

		if ( 'tbs_route' === $column ) {
			$route_id = get_post_meta( $post_id, '_tbs_route_id', true );

			if ( $route_id ) {
				echo '<a href="' . esc_url( get_edit_post_link( $route_id ) ) . '">';
				echo esc_html( get_the_title( $route_id ) );
				echo '</a>';
			} else {
				echo '—';
			}
		}

		if ( 'tbs_worker' === $column ) {
			$worker_id = get_post_meta( $post_id, '_tbs_worker_id', true );

			if ( $worker_id ) {
				$user = get_user_by( 'id', $worker_id );
				echo $user ? esc_html( $user->display_name ) : '—';
			} else {
				echo '—';
			}
		}

		if ( 'tbs_status' === $column ) {
			$status = get_post_meta( $post_id, '_tbs_booking_status', true );
			echo esc_html( TBS_Admin_Booking_Meta_Boxes::format_status( $status ) );
		}

		if ( 'tbs_order_id' === $column ) {
			$order_id = get_post_meta( $post_id, '_tbs_order_id', true );

			if ( $order_id ) {
				$order_link = admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' );
				echo '<a href="' . esc_url( $order_link ) . '">#' . esc_html( $order_id ) . '</a>';
			} else {
				echo '—';
			}
		}
	}

	public static function route_columns( $columns ) {
		return array(
			'cb'                => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'             => 'Route',
			'tbs_route_date'    => 'Route Date',
			'tbs_postcode_area' => 'Postcode Area',
			'tbs_capacity'      => 'Capacity',
			'tbs_worker'        => 'Worker',
			'tbs_status'        => 'Status',
			'date'              => 'Created',
		);
	}

	public static function route_column_content( $column, $post_id ) {
		if ( 'tbs_route_date' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_tbs_route_date', true ) ?: '—' );
		}

		if ( 'tbs_postcode_area' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_tbs_postcode_area', true ) ?: 'All postcodes' );
		}

		if ( 'tbs_capacity' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_tbs_capacity', true ) ?: '—' );
		}

		if ( 'tbs_worker' === $column ) {
			$worker_id = get_post_meta( $post_id, '_tbs_assigned_worker', true );

			if ( $worker_id ) {
				$user = get_user_by( 'id', $worker_id );
				echo $user ? esc_html( $user->display_name ) : '—';
			} else {
				echo '—';
			}
		}

		if ( 'tbs_status' === $column ) {
			$status = get_post_meta( $post_id, '_tbs_route_status', true );
			echo esc_html( self::format_status( $status ) );
		}
	}

	public static function booking_filters() {
		global $typenow;

		if ( 'tbs_booking' !== $typenow ) {
			return;
		}

		$selected_status = isset( $_GET['tbs_booking_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['tbs_booking_status_filter'] ) ) : '';
		$selected_type   = isset( $_GET['tbs_appointment_type_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['tbs_appointment_type_filter'] ) ) : '';
		$selected_route  = isset( $_GET['tbs_route_filter'] ) ? absint( $_GET['tbs_route_filter'] ) : 0;

		echo '<select name="tbs_booking_status_filter">';
		echo '<option value="">All booking statuses</option>';

		foreach ( TBS_Admin_Booking_Meta_Boxes::get_booking_statuses() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $selected_status, $key, false ) . '>';
			echo esc_html( $label );
			echo '</option>';
		}

		echo '</select>';

		echo '<select name="tbs_appointment_type_filter">';
		echo '<option value="">All appointment types</option>';
		echo '<option value="tour" ' . selected( $selected_type, 'tour', false ) . '>Tour</option>';
		echo '<option value="urgent_single_trip" ' . selected( $selected_type, 'urgent_single_trip', false ) . '>Urgent single trip</option>';
		echo '<option value="preferred_date" ' . selected( $selected_type, 'preferred_date', false ) . '>Free preferred date</option>';
		echo '</select>';

		$routes = get_posts(
			array(
				'post_type'      => 'tbs_route',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		echo '<select name="tbs_route_filter">';
		echo '<option value="">All routes</option>';

		foreach ( $routes as $route ) {
			echo '<option value="' . esc_attr( $route->ID ) . '" ' . selected( $selected_route, $route->ID, false ) . '>';
			echo esc_html( $route->post_title );
			echo '</option>';
		}

		echo '</select>';
	}

	public static function apply_booking_filters( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );

		if ( 'tbs_booking' !== $post_type ) {
			return;
		}

		$meta_query = array();

		if ( ! empty( $_GET['tbs_booking_status_filter'] ) ) {
			$meta_query[] = array(
				'key'   => '_tbs_booking_status',
				'value' => sanitize_text_field( wp_unslash( $_GET['tbs_booking_status_filter'] ) ),
			);
		}

		if ( ! empty( $_GET['tbs_appointment_type_filter'] ) ) {
			$meta_query[] = array(
				'key'   => '_tbs_appointment_type',
				'value' => sanitize_text_field( wp_unslash( $_GET['tbs_appointment_type_filter'] ) ),
			);
		}

		if ( ! empty( $_GET['tbs_route_filter'] ) ) {
			$meta_query[] = array(
				'key'   => '_tbs_route_id',
				'value' => absint( $_GET['tbs_route_filter'] ),
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	private static function format_status( $status ) {
		if ( empty( $status ) ) {
			return '—';
		}

		return ucwords( str_replace( '_', ' ', $status ) );
	}
}