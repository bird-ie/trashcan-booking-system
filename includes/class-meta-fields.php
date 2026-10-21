<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Meta_Fields {

	public static function init() {
		// Placeholder for future meta key registry.
	}

	public static function booking_keys() {

		return array(
			'customer_id' => '_tbs_customer_id',
			'order_id'    => '_tbs_order_id',
			'route_id'    => '_tbs_route_id',
		);

	}
}