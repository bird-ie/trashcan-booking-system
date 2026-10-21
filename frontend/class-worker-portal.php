<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Worker_Portal {

	public static function init() {
		add_shortcode( 'tbs_worker_portal', array( __CLASS__, 'render_portal' ) );
		add_action( 'wp_ajax_tbs_update_job_status', array( __CLASS__, 'ajax_update_job_status' ) );
		add_action( 'wp_ajax_tbs_save_job_report', array( __CLASS__, 'ajax_save_job_report' ) );
	}

	public static function render_portal() {
		if ( ! is_user_logged_in() || ! current_user_can( 'tbs_view_assigned_routes' ) ) {
			return '<p>You do not have permission to view this page. Please log in as a Worker.</p>';
		}

		$current_user = wp_get_current_user();
		$worker_id = $current_user->ID;

		$routes_query = new WP_Query( array(
			'post_type'      => 'tbs_route',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_tbs_assigned_worker',
					'value' => $worker_id,
				),
			),
			'meta_key'       => '_tbs_route_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		) );

		ob_start();
		?>
		<div class="tbs-worker-dashboard">
			<h1 style="margin-bottom: 20px;">Worker Portal</h1>
			<p>Welcome, <?php echo esc_html( $current_user->display_name ); ?>. Here are your assigned routes and jobs.</p>

			<?php if ( ! $routes_query->have_posts() ) : ?>
				<div class="tbs-dashboard-section">
					<p>You have no routes assigned at the moment.</p>
				</div>
			<?php else : ?>
				<?php while ( $routes_query->have_posts() ) : $routes_query->the_post(); 
					$route_id = get_the_ID();
					$route_date = get_post_meta( $route_id, '_tbs_route_date', true );
					$route_status = get_post_meta( $route_id, '_tbs_route_status', true );
					
					$bookings_query = new WP_Query( array(
						'post_type'      => 'tbs_booking',
						'posts_per_page' => -1,
						'meta_query'     => array(
							array(
								'key'   => '_tbs_route_id',
								'value' => $route_id,
							),
						),
						'meta_key'       => '_tbs_house_number',
						'orderby'        => 'meta_value',
						'order'          => 'ASC',
					) );
					?>
					<div class="tbs-route-card">
						<div class="tbs-route-header">
							<h2><?php echo esc_html( get_the_title( $route_id ) ); ?> (<?php echo esc_html( $route_date ); ?>)</h2>
							<span class="tbs-route-status"><?php echo esc_html( ucwords( str_replace( '_', ' ', $route_status ) ) ); ?></span>
						</div>

						<?php if ( ! $bookings_query->have_posts() ) : ?>
							<p>No bookings on this route yet.</p>
						<?php else : ?>
							<?php while ( $bookings_query->have_posts() ) : $bookings_query->the_post(); 
								$booking_id = get_the_ID();
								$booking_status = get_post_meta( $booking_id, '_tbs_booking_status', true );
								$address = get_post_meta( $booking_id, '_tbs_street', true ) . ' ' . get_post_meta( $booking_id, '_tbs_house_number', true ) . ', ' . get_post_meta( $booking_id, '_tbs_postcode', true ) . ' ' . get_post_meta( $booking_id, '_tbs_city', true );
								$bin = get_post_meta( $booking_id, '_tbs_bin_type', true ) . ' / ' . get_post_meta( $booking_id, '_tbs_bin_size', true );
								$report = get_post_meta( $booking_id, '_tbs_service_report', true );
							?>
								<div class="tbs-job-card">
									<div class="tbs-job-header">
										<div>
											<strong><?php echo esc_html( get_the_title( $booking_id ) ); ?></strong><br>
											<small><?php echo esc_html( $address ); ?></small><br>
											<small>Bin: <?php echo esc_html( $bin ); ?></small>
										</div>
										<div>
											<?php if ( 'completed' === $booking_status ) : ?>
												<span class="tbs-job-status-completed">Completed</span>
											<?php elseif ( 'in_progress' === $booking_status ) : ?>
												<span class="tbs-job-status-progress">In Progress</span>
											<?php else : ?>
												<button type="button" class="tbs-start-job tbs-start-job-btn" data-booking-id="<?php echo esc_attr( $booking_id ); ?>">Start Job</button>
											<?php endif; ?>
										</div>
									</div>

									<?php if ( 'in_progress' === $booking_status ) : ?>
										<div class="tbs-job-form">
											<form class="tbs-job-report-form" data-booking-id="<?php echo esc_attr( $booking_id ); ?>">
												<div class="tbs-file-grid">
													<label><input type="file" name="before_photo" accept="image/*" required> <br><small>Before Photo</small></label>
													<label><input type="file" name="after_photo" accept="image/*" required> <br><small>After Photo</small></label>
												</div>
												
												<div class="tbs-checkbox-grid">
													<label><input type="checkbox" name="checklist[]" value="Inspected" <?php echo ( is_array($report) && in_array('Inspected', $report['checklist'] ?? []) ) ? 'checked' : ''; ?>> Inspected</label>
													<label><input type="checkbox" name="checklist[]" value="Cleaned" <?php echo ( is_array($report) && in_array('Cleaned', $report['checklist'] ?? []) ) ? 'checked' : ''; ?>> Cleaned</label>
													<label><input type="checkbox" name="checklist[]" value="Sanitized" <?php echo ( is_array($report) && in_array('Sanitized', $report['checklist'] ?? []) ) ? 'checked' : ''; ?>> Sanitized</label>
													<label><input type="checkbox" name="checklist[]" value="Deodorized" <?php echo ( is_array($report) && in_array('Deodorized', $report['checklist'] ?? []) ) ? 'checked' : ''; ?>> Deodorized</label>
													<label><input type="checkbox" name="checklist[]" value="Damage Check" <?php echo ( is_array($report) && in_array('Damage Check', $report['checklist'] ?? []) ) ? 'checked' : ''; ?>> Damage Check</label>
												</div>

												<label style="display: block; margin-top: 12px;">
													Worker Notes:
													<textarea name="notes" rows="3"><?php echo esc_textarea( $report['worker_notes'] ?? '' ); ?></textarea>
												</label>

												<button type="submit" class="tbs-complete-job-btn">Complete Job & Save Report</button>
												<div class="tbs-report-message" style="margin-top: 8px; font-weight: bold;"></div>
											</form>
										</div>
									<?php endif; ?>
								</div>
							<?php endwhile; wp_reset_postdata(); ?>
						<?php endif; ?>
					</div>
				<?php endwhile; wp_reset_postdata(); ?>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Start Job
			$('.tbs-start-job').on('click', function(e) {
				e.preventDefault(); // Prevent any default form submission behavior
				
				var btn = $(this);
				var bookingId = btn.data('booking-id');
				btn.text('Starting...').prop('disabled', true);

				$.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
					action: 'tbs_update_job_status',
					nonce: '<?php echo wp_create_nonce( "tbs_worker_nonce" ); ?>',
					booking_id: bookingId,
					new_status: 'in_progress'
				}, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert('Error: ' + response.data.message);
						btn.text('Start Job').prop('disabled', false);
					}
				});
			});

			// Submit Job Report
			$('.tbs-job-report-form').on('submit', function(e) {
				e.preventDefault();
				var form = $(this);
				var btn = form.find('button[type="submit"]');
				var bookingId = form.data('booking-id');
				var formData = new FormData(this);
				
				formData.append('action', 'tbs_save_job_report');
				formData.append('nonce', '<?php echo wp_create_nonce( "tbs_worker_nonce" ); ?>');
				formData.append('booking_id', bookingId);

				btn.text('Saving...').prop('disabled', true);
				form.find('.tbs-report-message').text('').css('color', 'inherit');

				$.ajax({
					url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						if (response.success) {
							form.find('.tbs-report-message').css('color', 'green').text('Report saved! Refreshing...');
							setTimeout(function() { location.reload(); }, 1500);
						} else {
							form.find('.tbs-report-message').css('color', 'red').text('Error: ' + response.data.message);
							btn.text('Complete Job & Save Report').prop('disabled', false);
						}
					},
					error: function() {
						form.find('.tbs-report-message').css('color', 'red').text('Server error.');
						btn.text('Complete Job & Save Report').prop('disabled', false);
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	public static function ajax_update_job_status() {
		check_ajax_referer( 'tbs_worker_nonce', 'nonce' );

		if ( ! current_user_can( 'tbs_update_job_status' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$booking_id = absint( $_POST['booking_id'] );
		$new_status = sanitize_text_field( $_POST['new_status'] );

		if ( ! $booking_id || ! in_array( $new_status, array( 'in_progress', 'completed' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request.' ) );
		}

		update_post_meta( $booking_id, '_tbs_booking_status', $new_status );

		$order_id = get_post_meta( $booking_id, '_tbs_order_id', true );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				if ( 'completed' === $new_status ) {
					$order->update_status( 'completed', 'Worker completed the job.' );
				} elseif ( 'in_progress' === $new_status ) {
					$order->update_status( 'processing', 'Worker started the job.' );
				}
			}
		}

		wp_send_json_success();
	}

	public static function ajax_save_job_report() {
		check_ajax_referer( 'tbs_worker_nonce', 'nonce' );

		if ( ! current_user_can( 'tbs_complete_reports' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$booking_id = absint( $_POST['booking_id'] );
		$checklist  = isset( $_POST['checklist'] ) ? array_map( 'sanitize_text_field', $_POST['checklist'] ) : array();
		$notes      = isset( $_POST['notes'] ) ? sanitize_textarea_field( $_POST['notes'] ) : '';

		update_post_meta( $booking_id, '_tbs_booking_status', 'completed' );

		$order_id = get_post_meta( $booking_id, '_tbs_order_id', true );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->update_status( 'completed', 'Job report submitted by worker.' );
			}
		}

		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$before_img_id = 0;
		$after_img_id = 0;

		if ( ! empty( $_FILES['before_photo']['name'] ) ) {
			$before_upload = media_handle_upload( 'before_photo', $booking_id );
			if ( is_wp_error( $before_upload ) ) {
				wp_send_json_error( array( 'message' => 'Before Photo Error: ' . $before_upload->get_error_message() ) );
			}
			$before_img_id = $before_upload;
		}

		if ( ! empty( $_FILES['after_photo']['name'] ) ) {
			$after_upload = media_handle_upload( 'after_photo', $booking_id );
			if ( is_wp_error( $after_upload ) ) {
				wp_send_json_error( array( 'message' => 'After Photo Error: ' . $after_upload->get_error_message() ) );
			}
			$after_img_id = $after_upload;
		}

		$report_data = array(
			'checklist'       => $checklist,
			'worker_notes'    => $notes,
			'before_image_id' => $before_img_id,
			'after_image_id'  => $after_img_id,
			'completed_at'    => current_time( 'mysql' ),
		);

		update_post_meta( $booking_id, '_tbs_service_report', $report_data );

		do_action( 'tbs_booking_completed', $booking_id );

		wp_send_json_success();
	}
}