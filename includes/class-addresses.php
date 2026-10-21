<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Addresses {

	public static function init() {
		// Address UI will be added later.
	}

	public static function get_user_addresses( $user_id ) {

		$addresses = get_user_meta( $user_id, '_tbs_saved_addresses', true );

		if ( ! is_array( $addresses ) ) {
			$addresses = array();
		}

		return $addresses;
	}

	public static function add_user_address( $user_id, $address ) {

		$addresses = self::get_user_addresses( $user_id );

		$address_id = uniqid( 'addr_' );

		$addresses[ $address_id ] = array(
			'id'           => $address_id,
			'label'        => isset( $address['label'] ) ? sanitize_text_field( $address['label'] ) : '',
			'street'       => isset( $address['street'] ) ? sanitize_text_field( $address['street'] ) : '',
			'house_number' => isset( $address['house_number'] ) ? sanitize_text_field( $address['house_number'] ) : '',
			'city'         => isset( $address['city'] ) ? sanitize_text_field( $address['city'] ) : '',
			'postcode'     => isset( $address['postcode'] ) ? sanitize_text_field( strtoupper( trim( $address['postcode'] ) ) ) : '',
			'country'      => isset( $address['country'] ) ? sanitize_text_field( $address['country'] ) : '',
			'created_at'   => current_time( 'mysql' ),
		);

		update_user_meta( $user_id, '_tbs_saved_addresses', $addresses );

		return $address_id;
	}

	public static function delete_user_address( $user_id, $address_id ) {

		$addresses = self::get_user_addresses( $user_id );

		if ( isset( $addresses[ $address_id ] ) ) {
			unset( $addresses[ $address_id ] );
			update_user_meta( $user_id, '_tbs_saved_addresses', $addresses );
			return true;
		}

		return false;
	}
}