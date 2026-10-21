<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Admin_Design_Settings {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function add_menu_page() {
		add_submenu_page(
			'edit.php?post_type=tbs_booking',
			__( 'Design Settings', 'trashcan-booking-system' ),
			__( 'Design', 'trashcan-booking-system' ),
			'manage_options',
			'tbs-design',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting( 'tbs_design_settings', 'tbs_primary_color', array( 'default' => '#111827' ) );
		register_setting( 'tbs_design_settings', 'tbs_secondary_color', array( 'default' => '#10b981' ) );
		register_setting( 'tbs_design_settings', 'tbs_accent_color', array( 'default' => '#3b82f6' ) );
		register_setting( 'tbs_design_settings', 'tbs_border_radius', array( 'default' => '8' ) );
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to access this page.' );
		}
		?>
		<div class="wrap">
			<h1>Design Settings</h1>
			<p>Customize the look and feel of the booking wizard, dashboard, and worker portal.</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'tbs_design_settings' ); ?>
				<?php do_settings_sections( 'tbs_design_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><label for="tbs_primary_color">Primary Color (Buttons, Headers)</label></th>
						<td>
							<input type="color" name="tbs_primary_color" id="tbs_primary_color" value="<?php echo esc_attr( get_option( 'tbs_primary_color', '#111827' ) ); ?>">
							<p class="description">Used for main buttons and headers.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tbs_secondary_color">Secondary Color (Success, Checkmarks)</label></th>
						<td>
							<input type="color" name="tbs_secondary_color" id="tbs_secondary_color" value="<?php echo esc_attr( get_option( 'tbs_secondary_color', '#10b981' ) ); ?>">
							<p class="description">Used for success messages and completed states.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tbs_accent_color">Accent Color (Links, Highlights)</label></th>
						<td>
							<input type="color" name="tbs_accent_color" id="tbs_accent_color" value="<?php echo esc_attr( get_option( 'tbs_accent_color', '#3b82f6' ) ); ?>">
							<p class="description">Used for links and icons.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tbs_border_radius">Border Radius (px)</label></th>
						<td>
							<input type="number" name="tbs_border_radius" id="tbs_border_radius" value="<?php echo esc_attr( get_option( 'tbs_border_radius', '8' ) ); ?>" class="small-text">
							<p class="description">How rounded should cards and inputs be? (e.g., 0 for sharp, 8 for rounded).</p>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Save Design Settings' ); ?>
			</form>
		</div>
		<?php
	}
}