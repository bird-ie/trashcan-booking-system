<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Pricing_Rules {

	public static function init() {
		self::maybe_create_table();
	}

	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'tbs_pricing_rules';
	}

	public static function maybe_create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			postcode VARCHAR(50) NOT NULL,
			price DECIMAL(10,2) NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NULL,
			PRIMARY KEY (id),
			KEY postcode (postcode),
			KEY is_active (is_active)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public static function normalize_postcode( $postcode ) {
		$postcode = strtoupper( trim( $postcode ) );
		$postcode = preg_replace( '/\s+/', '', $postcode );

		return sanitize_text_field( $postcode );
	}

	public static function add_rule( $postcode, $price ) {
		global $wpdb;

		self::maybe_create_table();

		$table_name = self::get_table_name();

		$postcode = self::normalize_postcode( $postcode );
		$price    = floatval( $price );

		if ( empty( $postcode ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'postcode'   => $postcode,
				'price'      => $price,
				'is_active'  => 1,
				'created_at' => current_time( 'mysql' ),
			),
			array(
				'%s',
				'%f',
				'%d',
				'%s',
			)
		);

		return $result ? $wpdb->insert_id : false;
	}

	public static function get_rules() {
		global $wpdb;

		self::maybe_create_table();

		$table_name = self::get_table_name();

		return $wpdb->get_results(
			"SELECT * FROM {$table_name} ORDER BY id DESC"
		);
	}

	public static function delete_rule( $rule_id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		return $wpdb->delete(
			$table_name,
			array(
				'id' => absint( $rule_id ),
			),
			array(
				'%d',
			)
		);
	}

	public static function get_price( $postcode ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$postcode   = self::normalize_postcode( $postcode );

		// Fetch all active rules to allow for range matching in PHP
		$rules = $wpdb->get_results(
			"SELECT postcode, price FROM {$table_name} WHERE is_active = 1"
		);

		if ( empty( $rules ) ) {
			return false;
		}

		foreach ( $rules as $rule ) {
			$rule_postcode = self::normalize_postcode( $rule->postcode );

			// 1. Exact Match
			if ( $rule_postcode === $postcode ) {
				return (float) $rule->price;
			}

			// 2. Range Match (e.g., 70000-79999)
			if ( strpos( $rule_postcode, '-' ) !== false ) {
				$parts = explode( '-', $rule_postcode );
				
				if ( count( $parts ) === 2 ) {
					$from = (int) trim( $parts[0] );
					$to   = (int) trim( $parts[1] );
					$input = (int) $postcode;

					if ( $input >= $from && $input <= $to ) {
						return (float) $rule->price;
					}
				}
			}
		}

		return false;
	}

	public static function postcode_exists( $postcode ) {
		return false !== self::get_price( $postcode );
	}
}