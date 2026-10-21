<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Route_Meta_Boxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_tbs_route', array( __CLASS__, 'save_route_meta' ) );
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'tbs_route_details',
			'Route Details',
			array( __CLASS__, 'render_route_details_box' ),
			'tbs_route',
			'normal',
			'high'
		);
	}

	public static function render_route_details_box( $post ) {
		wp_nonce_field( 'tbs_save_route_meta', 'tbs_route_meta_nonce' );

		$route_date      = get_post_meta( $post->ID, '_tbs_route_date', true );
		$route_status    = get_post_meta( $post->ID, '_tbs_route_status', true );
		$assigned_worker = get_post_meta( $post->ID, '_tbs_assigned_worker', true );
		$capacity        = get_post_meta( $post->ID, '_tbs_capacity', true );
		$postcode_area   = get_post_meta( $post->ID, '_tbs_postcode_area', true );

		if ( empty( $route_status ) ) {
			$route_status = 'planned';
		}

		$workers = get_users(
			array(
				'role'   => 'tbs_worker',
				'fields' => array( 'ID', 'display_name', 'user_email' ),
			)
		);

		echo '<table class="form-table">';

		echo '<tr>';
		echo '<th><label for="tbs_route_date">Route Date</label></th>';
		echo '<td><input type="date" name="tbs_route_date" id="tbs_route_date" value="' . esc_attr( $route_date ) . '" class="regular-text"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_postcode_area">Postcode / PLZ Area</label></th>';
		echo '<td>';
		echo '<input type="text" name="tbs_postcode_area" id="tbs_postcode_area" value="' . esc_attr( $postcode_area ) . '" class="regular-text" placeholder="e.g. 10115 or 10100-10200">';
		echo '<p class="description">Optional. Leave empty if this route should be available for all postcodes.</p>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_capacity">Capacity</label></th>';
		echo '<td><input type="number" name="tbs_capacity" id="tbs_capacity" value="' . esc_attr( $capacity ) . '" class="regular-text" min="0"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_assigned_worker">Assigned Worker</label></th>';
		echo '<td>';
		echo '<select name="tbs_assigned_worker" id="tbs_assigned_worker">';
		echo '<option value="">No worker assigned</option>';

		foreach ( $workers as $worker ) {
			echo '<option value="' . esc_attr( $worker->ID ) . '" ' . selected( $assigned_worker, $worker->ID, false ) . '>';
			echo esc_html( $worker->display_name . ' (' . $worker->user_email . ')' );
			echo '</option>';
		}

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="tbs_route_status">Route Status</label></th>';
		echo '<td>';
		echo '<select name="tbs_route_status" id="tbs_route_status">';

		$statuses = array(
			'planned'     => 'Planned',
			'active'      => 'Active',
			'in_progress' => 'In Progress',
			'completed'   => 'Completed',
			'cancelled'   => 'Cancelled',
		);

		foreach ( $statuses as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $route_status, $key, false ) . '>';
			echo esc_html( $label );
			echo '</option>';
		}

		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';
	}

	public static function save_route_meta( $post_id ) {
		if ( ! isset( $_POST['tbs_route_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tbs_route_meta_nonce'] ) ), 'tbs_save_route_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_tbs_route_date'      => 'tbs_route_date',
			'_tbs_route_status'    => 'tbs_route_status',
			'_tbs_assigned_worker' => 'tbs_assigned_worker',
			'_tbs_capacity'        => 'tbs_capacity',
			'_tbs_postcode_area'   => 'tbs_postcode_area',
		);

		foreach ( $fields as $meta_key => $post_key ) {
			if ( isset( $_POST[ $post_key ] ) ) {
				update_post_meta(
					$post_id,
					$meta_key,
					sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) )
				);
			}
		}
	}
}