<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Pricing_Page {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to access this page.' );
		}

		self::handle_actions();

		$rules = TBS_Pricing_Rules::get_rules();

		echo '<div class="wrap">';
		echo '<h1>Pricing Rules</h1>';

		echo '<p>Add postcode or PLZ region pricing here. For now, use single postcodes or region labels like <strong>10115</strong>, <strong>10100-10200</strong>, or <strong>Berlin-North</strong>. Advanced Google Maps radius logic can be added later.</p>';

		echo '<h2>Add New Rule</h2>';

		echo '<form method="post" action="">';
		wp_nonce_field( 'tbs_add_pricing_rule', 'tbs_pricing_nonce' );

		echo '<input type="hidden" name="tbs_action" value="add_pricing_rule">';

		echo '<table class="form-table">';
		echo '<tr>';
		echo '<th><label for="postcode">Postcode / Region</label></th>';
		echo '<td><input type="text" name="postcode" id="postcode" class="regular-text" required placeholder="e.g. 10115 or 10100-10200"></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="price">Price</label></th>';
		echo '<td><input type="number" step="0.01" min="0" name="price" id="price" class="regular-text" required></td>';
		echo '</tr>';
		echo '</table>';

		submit_button( 'Add Pricing Rule' );

		echo '</form>';

		echo '<hr>';

		echo '<h2>Existing Pricing Rules</h2>';

		if ( empty( $rules ) ) {
			echo '<p>No pricing rules added yet.</p>';
		} else {
			echo '<table class="widefat striped">';
			echo '<thead>';
			echo '<tr>';
			echo '<th>ID</th>';
			echo '<th>Postcode / Region</th>';
			echo '<th>Price</th>';
			echo '<th>Status</th>';
			echo '<th>Action</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $rules as $rule ) {
				$delete_url = wp_nonce_url(
					admin_url( 'edit.php?post_type=tbs_booking&page=tbs-pricing-rules&tbs_action=delete_pricing_rule&rule_id=' . absint( $rule->id ) ),
					'tbs_delete_pricing_rule_' . absint( $rule->id )
				);

				echo '<tr>';
				echo '<td>' . esc_html( $rule->id ) . '</td>';
				echo '<td>' . esc_html( $rule->postcode ) . '</td>';
				echo '<td>' . esc_html( number_format( (float) $rule->price, 2 ) ) . '</td>';
				echo '<td>' . ( ! empty( $rule->is_active ) ? 'Active' : 'Inactive' ) . '</td>';
				echo '<td><a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Delete this pricing rule?\')">Delete</a></td>';
				echo '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
		}

		echo '</div>';
	}

	private static function handle_actions() {
		if ( isset( $_POST['tbs_action'] ) && 'add_pricing_rule' === $_POST['tbs_action'] ) {
			if (
				! isset( $_POST['tbs_pricing_nonce'] )
				|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tbs_pricing_nonce'] ) ), 'tbs_add_pricing_rule' )
			) {
				return;
			}

			$postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : '';
			$price    = isset( $_POST['price'] ) ? floatval( wp_unslash( $_POST['price'] ) ) : 0;

			$inserted = TBS_Pricing_Rules::add_rule( $postcode, $price );

			if ( $inserted ) {
				echo '<div class="notice notice-success is-dismissible"><p>Pricing rule added successfully.</p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>Pricing rule could not be added.</p></div>';
			}
		}

		if ( isset( $_GET['tbs_action'], $_GET['rule_id'], $_GET['_wpnonce'] ) && 'delete_pricing_rule' === $_GET['tbs_action'] ) {
			$rule_id = absint( $_GET['rule_id'] );

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'tbs_delete_pricing_rule_' . $rule_id ) ) {
				return;
			}

			TBS_Pricing_Rules::delete_rule( $rule_id );

			echo '<div class="notice notice-success is-dismissible"><p>Pricing rule deleted successfully.</p></div>';
		}
	}
}