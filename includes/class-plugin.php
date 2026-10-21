<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TBS_PLUGIN_DIR . 'includes/class-roles.php';
require_once TBS_PLUGIN_DIR . 'includes/class-post-types.php';
require_once TBS_PLUGIN_DIR . 'includes/class-meta-fields.php';
require_once TBS_PLUGIN_DIR . 'includes/class-addresses.php';
require_once TBS_PLUGIN_DIR . 'includes/class-credits.php';
require_once TBS_PLUGIN_DIR . 'includes/class-pricing-rules.php';
require_once TBS_PLUGIN_DIR . 'includes/class-route-capacity.php';
require_once TBS_PLUGIN_DIR . 'includes/class-service-reports.php';
require_once TBS_PLUGIN_DIR . 'includes/class-woocommerce-foundation.php';
require_once TBS_PLUGIN_DIR . 'includes/class-booking-checkout.php';
require_once TBS_PLUGIN_DIR . 'includes/class-wizard-order.php';
require_once TBS_PLUGIN_DIR . 'includes/class-order-sync.php';
require_once TBS_PLUGIN_DIR . 'includes/class-notifications.php';

require_once TBS_PLUGIN_DIR . 'admin/class-admin-menu.php';
require_once TBS_PLUGIN_DIR . 'admin/class-admin-pricing-page.php';
require_once TBS_PLUGIN_DIR . 'admin/class-admin-settings-page.php';
require_once TBS_PLUGIN_DIR . 'admin/class-admin-design-settings.php'; // NEW
require_once TBS_PLUGIN_DIR . 'admin/class-admin-booking-meta-boxes.php';
require_once TBS_PLUGIN_DIR . 'admin/class-admin-route-meta-boxes.php';
require_once TBS_PLUGIN_DIR . 'admin/class-admin-columns.php';
require_once TBS_PLUGIN_DIR . 'admin/class-admin-ajax.php';
require_once TBS_PLUGIN_DIR . 'admin/class-admin-export.php';

require_once TBS_PLUGIN_DIR . 'frontend/class-postcode-checker.php';
require_once TBS_PLUGIN_DIR . 'frontend/class-booking-wizard.php';
require_once TBS_PLUGIN_DIR . 'frontend/class-customer-dashboard.php';
require_once TBS_PLUGIN_DIR . 'frontend/class-worker-portal.php';

class TBS_Plugin {

	public function run() {
		add_action( 'init', array( 'TBS_Roles', 'register_roles' ) );
		add_action( 'init', array( 'TBS_Post_Types', 'register' ) );
		add_action( 'init', array( 'TBS_Meta_Fields', 'init' ) );
		add_action( 'init', array( 'TBS_Addresses', 'init' ) );
		add_action( 'init', array( 'TBS_Credits', 'init' ) );
		add_action( 'init', array( 'TBS_Pricing_Rules', 'init' ) );
		add_action( 'init', array( 'TBS_Service_Reports', 'init' ) );
		add_action( 'init', array( 'TBS_WooCommerce_Foundation', 'init' ) );
		add_action( 'init', array( 'TBS_Booking_Checkout', 'init' ) );
		add_action( 'init', array( 'TBS_Wizard_Order', 'init' ) );
		add_action( 'init', array( 'TBS_Order_Sync', 'init' ) );
		add_action( 'init', array( 'TBS_Notifications', 'init' ) );

		TBS_Admin_Menu::init();
		TBS_Admin_Booking_Meta_Boxes::init();
		TBS_Admin_Route_Meta_Boxes::init();
		TBS_Admin_Columns::init();
		TBS_Admin_Ajax::init();
		TBS_Admin_Export::init();
		TBS_Admin_Design_Settings::init(); // NEW

		TBS_Postcode_Checker::init();
		TBS_Booking_Wizard::init();
		TBS_Customer_Dashboard::init();
		TBS_Worker_Portal::init();

		// Inject CSS variables into the frontend
		add_action( 'wp_head', array( $this, 'inject_design_css' ) );

		add_action( 'admin_notices', array( $this, 'welcome_notice' ) );
	}

	public function inject_design_css() {
		$primary   = get_option( 'tbs_primary_color', '#111827' );
		$secondary = get_option( 'tbs_secondary_color', '#10b981' );
		$accent    = get_option( 'tbs_accent_color', '#3b82f6' );
		$radius    = get_option( 'tbs_border_radius', '8' );

		?>
		<style>
			:root {
				--tbs-primary: <?php echo esc_attr( $primary ); ?>;
				--tbs-secondary: <?php echo esc_attr( $secondary ); ?>;
				--tbs-accent: <?php echo esc_attr( $accent ); ?>;
				--tbs-radius: <?php echo esc_attr( $radius ); ?>px;
			}
		</style>
		<?php
	}

	public function welcome_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_option( 'tbs_welcome_notice_dismissed' ) ) {
			return;
		}

		if ( isset( $_GET['tbs_dismiss_notice'] ) && $_GET['tbs_dismiss_notice'] == 1 ) {
			update_option( 'tbs_welcome_notice_dismissed', 1 );
			return;
		}

		echo '<div class="notice notice-success is-dismissible" style="border-left-color: #10b981;">';
		echo '<p><strong>TonniClean Booking System is ready!</strong></p>';
		echo '<p>I have automatically created your essential pages (<strong>Book Now</strong>, <strong>My Dashboard</strong>, <strong>Worker Portal</strong>) and your WooCommerce Booking Product. You are ready to start accepting bookings.</p>';
		echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=tbs_booking&page=tbs-settings' ) ) . '" class="button button-primary">Go to Settings</a> ';
		echo '<a href="' . esc_url( add_query_arg( 'tbs_dismiss_notice', '1' ) ) . '" class="button">Dismiss this notice</a></p>';
		echo '</div>';
	}
}