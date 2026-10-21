<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Customer_Dashboard {

	public static function init() {
		add_shortcode( 'tbs_customer_dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_action( 'wp_ajax_tbs_add_address', array( __CLASS__, 'ajax_add_address' ) );
		add_action( 'wp_ajax_tbs_delete_address', array( __CLASS__, 'ajax_delete_address' ) );
		add_action( 'wp_ajax_tbs_get_customer_report', array( __CLASS__, 'ajax_get_customer_report' ) );
	}

	public static function render_dashboard() {
		if ( ! is_user_logged_in() ) {
			return '<p>Please <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">log in</a> to view your dashboard.</p>';
		}

		$user_id = get_current_user_id();
		$credits = TBS_Credits::get_available_credits( $user_id );
		$addresses = TBS_Addresses::get_user_addresses( $user_id );

		$bookings_query = new WP_Query( array(
			'post_type'      => 'tbs_booking',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_tbs_customer_email',
					'value' => wp_get_current_user()->user_email,
				),
			),
			'meta_key'       => '_tbs_service_date',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		) );

		ob_start();
		?>
		<div class="tbs-dashboard-wrap">
			
			<!-- Credits Section -->
			<div class="tbs-dashboard-credits-card">
				<h2>Available Booking Credits</h2>
				<div class="tbs-credit-count"><?php echo esc_html( $credits ); ?></div>
				<p>Use these credits on your next booking.</p>
			</div>

			<!-- Addresses Section -->
			<div class="tbs-dashboard-section">
				<h3>My Addresses</h3>
				
				<div id="tbs-address-list" style="display: grid; gap: 12px; margin-bottom: 20px;">
					<?php if ( empty( $addresses ) ) : ?>
						<p>You have no saved addresses yet.</p>
					<?php else : ?>
						<?php foreach ( $addresses as $id => $addr ) : ?>
							<div class="tbs-address-card">
								<div>
									<strong><?php echo esc_html( $addr['label'] ? $addr['label'] : 'Address' ); ?></strong><br>
									<small><?php echo esc_html( $addr['street'] . ' ' . $addr['house_number'] . ', ' . $addr['postcode'] . ' ' . $addr['city'] ); ?></small>
								</div>
								<button type="button" class="tbs-delete-address" data-id="<?php echo esc_attr( $id ); ?>">Delete</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<h4>Add New Address</h4>
				<form id="tbs-add-address-form" class="tbs-add-address-form">
					<input type="text" name="label" placeholder="Label (e.g. Home)">
					<input type="text" name="city" placeholder="City" required>
					<input type="text" name="street" placeholder="Street" required>
					<input type="text" name="house_number" placeholder="Nr." required>
					<input type="text" name="postcode" placeholder="Postcode / PLZ" required>
					<button type="submit">Save Address</button>
				</form>
				<div id="tbs-address-message" style="margin-top: 10px; font-weight: bold;"></div>
			</div>

			<!-- Bookings Section -->
			<div class="tbs-dashboard-section">
				<h3>My Bookings</h3>
				<?php if ( ! $bookings_query->have_posts() ) : ?>
					<p>You have no bookings yet.</p>
				<?php else : ?>
					<table class="tbs-bookings-table">
						<thead>
							<tr>
								<th>Date</th>
								<th>Route</th>
								<th>Status</th>
								<th>Total</th>
								<th>Report</th>
							</tr>
						</thead>
						<tbody>
							<?php while ( $bookings_query->have_posts() ) : $bookings_query->the_post(); 
								$booking_id = get_the_ID();
								$date = get_post_meta( $booking_id, '_tbs_service_date', true );
								$route_id = get_post_meta( $booking_id, '_tbs_route_id', true );
								$status = get_post_meta( $booking_id, '_tbs_booking_status', true );
								$total = get_post_meta( $booking_id, '_tbs_total', true );
								$report = get_post_meta( $booking_id, '_tbs_service_report', true );
								?>
								<tr>
									<td><?php echo esc_html( $date ); ?></td>
									<td><?php echo esc_html( get_the_title( $route_id ) ); ?></td>
									<td><span class="tbs-status-badge"><?php echo esc_html( ucwords( str_replace( '_', ' ', $status ) ) ); ?></span></td>
									<td>€<?php echo esc_html( number_format( (float) $total, 2 ) ); ?></td>
									<td>
										<?php if ( ! empty( $report ) ) : ?>
											<button type="button" class="tbs-view-report tbs-report-btn" data-booking-id="<?php echo esc_attr( $booking_id ); ?>">View Report</button>
										<?php else : ?>
											<span style="color: #9ca3af; font-size: 12px;">—</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endwhile; wp_reset_postdata(); ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

		</div>

		<!-- Report Modal -->
		<div id="tbs-report-modal" class="tbs-modal-overlay">
			<div class="tbs-modal-content">
				<button type="button" id="tbs-close-modal" class="tbs-modal-close">X</button>
				<h2 style="margin-top: 0;">Service Report</h2>
				<div id="tbs-report-content"></div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#tbs-add-address-form').on('submit', function(e) {
				e.preventDefault();
				var btn = $(this).find('button');
				btn.text('Saving...').prop('disabled', true);

				var data = $(this).serialize();
				data += '&action=tbs_add_address&nonce=<?php echo wp_create_nonce( "tbs_dashboard_nonce" ); ?>';

				$.post('<?php echo admin_url( "admin-ajax.php" ); ?>', data, function(response) {
					if (response.success) {
						$('#tbs-address-message').css('color', 'green').text('Address saved! Refreshing...');
						location.reload();
					} else {
						$('#tbs-address-message').css('color', 'red').text(response.data.message);
						btn.text('Save Address').prop('disabled', false);
					}
				});
			});

			$('.tbs-delete-address').on('click', function() {
				if (!confirm('Are you sure you want to delete this address?')) return;
				
				var btn = $(this);
				btn.text('...').prop('disabled', true);

				$.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
					action: 'tbs_delete_address',
					nonce: '<?php echo wp_create_nonce( "tbs_dashboard_nonce" ); ?>',
					address_id: btn.data('id')
				}, function(response) {
					if (response.success) {
						btn.closest('.tbs-address-card').fadeOut();
					} else {
						alert('Could not delete address.');
						btn.text('Delete').prop('disabled', false);
					}
				});
			});

			$('.tbs-view-report').on('click', function() {
				var bookingId = $(this).data('booking-id');
				$('#tbs-report-content').html('<p>Loading...</p>');
				$('#tbs-report-modal').css('display', 'flex');

				$.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
					action: 'tbs_get_customer_report',
					nonce: '<?php echo wp_create_nonce( "tbs_dashboard_nonce" ); ?>',
					booking_id: bookingId
				}, function(response) {
					if (response.success) {
						$('#tbs-report-content').html(response.data.html);
					} else {
						$('#tbs-report-content').html('<p>Report not found.</p>');
					}
				});
			});

			$('#tbs-close-modal').on('click', function() {
				$('#tbs-report-modal').css('display', 'none');
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	public static function ajax_add_address() {
		check_ajax_referer( 'tbs_dashboard_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
		}

		$address = array(
			'label'        => sanitize_text_field( $_POST['label'] ),
			'street'       => sanitize_text_field( $_POST['street'] ),
			'house_number' => sanitize_text_field( $_POST['house_number'] ),
			'postcode'     => sanitize_text_field( $_POST['postcode'] ),
			'city'         => sanitize_text_field( $_POST['city'] ),
		);

		TBS_Addresses::add_user_address( get_current_user_id(), $address );
		wp_send_json_success();
	}

	public static function ajax_delete_address() {
		check_ajax_referer( 'tbs_dashboard_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
		}

		$address_id = sanitize_text_field( $_POST['address_id'] );
		TBS_Addresses::delete_user_address( get_current_user_id(), $address_id );
		wp_send_json_success();
	}

	public static function ajax_get_customer_report() {
		check_ajax_referer( 'tbs_dashboard_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error();
		}

		$booking_id = absint( $_POST['booking_id'] );
		$report = get_post_meta( $booking_id, '_tbs_service_report', true );

		if ( empty( $report ) ) {
			wp_send_json_error();
		}

		$html = '<h4>Checklist:</h4>';
		if ( ! empty( $report['checklist'] ) && is_array( $report['checklist'] ) ) {
			$html .= '<ul style="list-style: disc; padding-left: 20px; margin-bottom: 20px;">';
			foreach ( $report['checklist'] as $item ) {
				$html .= '<li>' . esc_html( $item ) . '</li>';
			}
			$html .= '</ul>';
		} else {
			$html .= '<p>No checklist items completed.</p>';
		}

		$html .= '<h4>Worker Notes:</h4><p style="background: #f9fafb; padding: 10px; border-radius: 4px;">' . esc_html( ! empty( $report['worker_notes'] ) ? $report['worker_notes'] : 'No notes provided.' ) . '</p>';

		if ( ! empty( $report['before_image_id'] ) ) {
			$img_url = wp_get_attachment_url( $report['before_image_id'] );
			if ( $img_url ) {
				$html .= '<h4>Before:</h4><img src="' . esc_url( $img_url ) . '" style="width: 100%; border-radius: 8px; margin-bottom: 10px;">';
			}
		}
		if ( ! empty( $report['after_image_id'] ) ) {
			$img_url = wp_get_attachment_url( $report['after_image_id'] );
			if ( $img_url ) {
				$html .= '<h4>After:</h4><img src="' . esc_url( $img_url ) . '" style="width: 100%; border-radius: 8px;">';
			}
		}

		wp_send_json_success( array( 'html' => $html ) );
	}
}