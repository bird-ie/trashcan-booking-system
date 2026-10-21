<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TBS_Booking_Wizard {

	public static function init() {
		add_shortcode( 'tbs_booking_wizard', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets() {
		wp_enqueue_style(
			'tbs-booking-wizard-css',
			TBS_PLUGIN_URL . 'assets/booking-wizard.css',
			array(),
			TBS_VERSION
		);

		wp_enqueue_script(
			'tbs-booking-wizard-js',
			TBS_PLUGIN_URL . 'assets/booking-wizard.js',
			array( 'jquery' ),
			TBS_VERSION,
			true
		);

		wp_localize_script(
			'tbs-booking-wizard-js',
			'tbsBookingWizard',
			array(
				'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'tbs_frontend_nonce' ),
				'urgentSurcharge'     => floatval( get_option( 'tbs_urgent_surcharge', 15 ) ),
				'expressPrice'        => floatval( get_option( 'tbs_express_service_price', 10 ) ),
				'photoPrice'          => floatval( get_option( 'tbs_photo_documentation_price', 5 ) ),
				'hygienePrice'        => floatval( get_option( 'tbs_hygienic_documentation_price', 8 ) ),
			)
		);
	}

	public static function render_shortcode() {
		$urgent_surcharge = floatval( get_option( 'tbs_urgent_surcharge', 15 ) );
		$express_price    = floatval( get_option( 'tbs_express_service_price', 10 ) );
		$photo_price      = floatval( get_option( 'tbs_photo_documentation_price', 5 ) );
		$hygiene_price    = floatval( get_option( 'tbs_hygienic_documentation_price', 8 ) );

		ob_start();
		?>
		<div class="tbs-wizard-shell" data-tbs-booking-wizard>
			<aside class="tbs-wizard-stepper">
				<button type="button" class="tbs-step-pill active" data-step-jump="0">
					<span>1</span>
					<strong>Address</strong>
					<small>Pickup location</small>
				</button>

				<button type="button" class="tbs-step-pill" data-step-jump="1">
					<span>2</span>
					<strong>Trashcan</strong>
					<small>Type & size</small>
				</button>

				<button type="button" class="tbs-step-pill" data-step-jump="2">
					<span>3</span>
					<strong>Date</strong>
					<small>Tour or request</small>
				</button>

				<button type="button" class="tbs-step-pill" data-step-jump="3">
					<span>4</span>
					<strong>Extras</strong>
					<small>Optional services</small>
				</button>

				<button type="button" class="tbs-step-pill" data-step-jump="4">
					<span>5</span>
					<strong>Summary</strong>
					<small>Review booking</small>
				</button>

				<button type="button" class="tbs-step-pill" data-step-jump="5">
					<span>6</span>
					<strong>Payment</strong>
					<small>Customer details</small>
				</button>
			</aside>

			<div class="tbs-wizard-main">
				<form class="tbs-wizard-card" id="tbs_booking_wizard_form">
					<section class="tbs-wizard-step active" data-step="0">
						<div class="tbs-step-title">
							<span>1. Pickup address</span>
							<h2>Where should we clean your trashcan?</h2>
						</div>

						<?php 
						$current_user_id = get_current_user_id();
						$saved_addresses = $current_user_id ? TBS_Addresses::get_user_addresses( $current_user_id ) : array();
						
						if ( ! empty( $saved_addresses ) ) : 
						?>
						<div style="margin-bottom: 20px; padding: 15px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px;">
							<label style="font-weight: 700; display: block; margin-bottom: 8px;">Use a saved address:</label>
							<select id="tbs_saved_address_select" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #d1d5db;">
								<option value="">-- Enter a new address --</option>
								<?php foreach ( $saved_addresses as $addr ) : ?>
									<option value="<?php echo esc_attr( json_encode( $addr ) ); ?>">
										<?php echo esc_html( $addr['label'] . ': ' . $addr['street'] . ' ' . $addr['house_number'] . ', ' . $addr['postcode'] . ' ' . $addr['city'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php endif; ?>

						<div class="tbs-form-grid">
							<label class="tbs-field">Street
								<input type="text" name="street" placeholder="Street">
							</label>

							<label class="tbs-field">Nr.
								<input type="text" name="house_number" placeholder="Nr.">
							</label>

							<label class="tbs-field">Postcode / PLZ
								<input type="text" name="postcode" placeholder="79713">
							</label>

							<label class="tbs-field">City
								<input type="text" name="city" placeholder="Bad Säckingen">
							</label>

							<label class="tbs-field wide">Pickup note
								<input type="text" name="pickup_note" placeholder="After emptying, near the garden gate">
							</label>
						</div>

						<div class="tbs-zone-message" data-zone-message>
							Enter your postcode to check price and available dates.
						</div>
					</section>

					<section class="tbs-wizard-step" data-step="1">
						<div class="tbs-step-title">
							<span>2. Trashcan selection</span>
							<h2>Which trashcan should be cleaned?</h2>
						</div>

						<div class="tbs-choice-grid" data-bin-types>
							<label class="tbs-choice-card">
								<input type="radio" name="bin_type" value="General Waste" checked>
								<span>General Waste</span>
								<small>Residual waste bin</small>
							</label>

							<label class="tbs-choice-card">
								<input type="radio" name="bin_type" value="Organic">
								<span>Organic</span>
								<small>Bio / compost bin</small>
							</label>

							<label class="tbs-choice-card">
								<input type="radio" name="bin_type" value="Paper">
								<span>Paper</span>
								<small>Paper bin</small>
							</label>

							<label class="tbs-choice-card">
								<input type="radio" name="bin_type" value="Plastic">
								<span>Plastic</span>
								<small>Recycling bin</small>
							</label>
						</div>

						<div class="tbs-form-grid compact">
							<label class="tbs-field">Trashcan size
								<select name="bin_size">
									<option value="120L">120 Liter</option>
									<option value="240L" selected>240 Liter</option>
									<option value="770-1100L">770-1.100 Liter Container</option>
								</select>
							</label>

							<label class="tbs-field">Quantity
								<input type="number" name="quantity" min="1" max="20" value="1">
							</label>
						</div>
					</section>

					<section class="tbs-wizard-step" data-step="2">
						<div class="tbs-step-title">
							<span>3. Appointment</span>
							<h2>Choose how you want to schedule the cleaning</h2>
						</div>

						<p class="tbs-muted">
							Tours are usually cheaper because multiple customers in the same area are handled together.
						</p>

						<input type="hidden" name="appointment_type" value="tour">
						<input type="hidden" name="route_id">
						<input type="hidden" name="route_title">
						<input type="hidden" name="route_date">
						<input type="hidden" name="preferred_date">
						<input type="hidden" name="price">
						<input type="hidden" name="urgent_surcharge" value="0">

						<div class="tbs-appointment-tabs" role="tablist">
							<button type="button" class="tbs-appointment-tab active" data-appointment-type="tour">
								Tour
							</button>

							<button type="button" class="tbs-appointment-tab" data-appointment-type="urgent_single_trip">
								Urgent single trip
							</button>

							<button type="button" class="tbs-appointment-tab" data-appointment-type="preferred_date">
								Free preferred date
							</button>
						</div>

						<div class="tbs-appointment-panel active" data-appointment-panel="tour">
							<div class="tbs-route-loader" data-route-loader>
								<button type="button" class="tbs-button secondary" data-load-routes>
									Load available tours
								</button>
							</div>

							<div class="tbs-route-list" data-route-list></div>

							<p class="tbs-policy-note">
								Tours are sorted by date. Select the most suitable available tour for your postcode.
							</p>
						</div>

						<div class="tbs-appointment-panel" data-appointment-panel="urgent_single_trip">
							<div class="tbs-request-card">
								<h3>Urgent single trip</h3>
								<p>
									Request a faster individual trip outside the normal tour schedule. Extra surcharge: <strong>€<?php echo esc_html( number_format( $urgent_surcharge, 2 ) ); ?></strong>.
								</p>

								<label class="tbs-field">Preferred urgent date
									<input type="date" name="urgent_date">
								</label>

								<div class="tbs-zone-message tbs-zone-success">
									This will be created as an urgent booking request.
								</div>
							</div>
						</div>

						<div class="tbs-appointment-panel" data-appointment-panel="preferred_date">
							<div class="tbs-request-card">
								<h3>Free preferred date</h3>
								<p>
									Choose a preferred date. This is a request and needs to be confirmed by the admin.
								</p>

								<label class="tbs-field">Preferred date
									<input type="date" name="free_preferred_date">
								</label>

								<div class="tbs-zone-message">
									The selected date will be sent as a preferred date request.
								</div>
							</div>
						</div>
					</section>

					<section class="tbs-wizard-step" data-step="3">
						<div class="tbs-step-title">
							<span>4. Optional extras</span>
							<h2>Do you need anything extra?</h2>
						</div>

						<div class="tbs-extras-list">
							<label class="tbs-extra-card">
								<input type="checkbox" name="extras[]" value="Express Service" data-extra-price="<?php echo esc_attr( $express_price ); ?>">
								<span>
									<strong>Express Service</strong>
									<small>Priority handling where available</small>
								</span>
								<b>+€<?php echo esc_html( number_format( $express_price, 2 ) ); ?></b>
							</label>

							<label class="tbs-extra-card">
								<input type="checkbox" name="extras[]" value="Photo Documentation" data-extra-price="<?php echo esc_attr( $photo_price ); ?>">
								<span>
									<strong>Photo Documentation</strong>
									<small>Before/after images for this job</small>
								</span>
								<b>+€<?php echo esc_html( number_format( $photo_price, 2 ) ); ?></b>
							</label>

							<label class="tbs-extra-card">
								<input type="checkbox" name="extras[]" value="Hygienic Documentation" data-extra-price="<?php echo esc_attr( $hygiene_price ); ?>">
								<span>
									<strong>Hygienic Documentation</strong>
									<small>Checklist documentation for business customers</small>
								</span>
								<b>+€<?php echo esc_html( number_format( $hygiene_price, 2 ) ); ?></b>
							</label>
						</div>
					</section>

					<section class="tbs-wizard-step" data-step="4">
						<div class="tbs-step-title">
							<span>5. Summary</span>
							<h2>Review your booking</h2>
						</div>

						<div class="tbs-summary-card" data-booking-summary></div>

						<label class="tbs-checkline">
							<input type="checkbox" name="terms">
							I accept the booking terms and price calculation.
						</label>
					</section>

					<section class="tbs-wizard-step" data-step="5">
						<div class="tbs-step-title">
							<span>6. Payment</span>
							<h2>Customer and payment details</h2>
						</div>

						<div class="tbs-payment-section">
							<h3>Customer information</h3>

							<div class="tbs-form-grid">
								<label class="tbs-field">First name
									<input type="text" name="first_name" placeholder="First name">
								</label>

								<label class="tbs-field">Last name
									<input type="text" name="last_name" placeholder="Last name">
								</label>

								<label class="tbs-field">Email
									<input type="email" name="email" placeholder="email@example.com">
								</label>

								<label class="tbs-field">Phone
									<input type="tel" name="phone" placeholder="+49 ...">
								</label>
							</div>
						</div>

						<div class="tbs-payment-section">
							<h3>Billing address</h3>

							<label class="tbs-checkline">
								<input type="checkbox" name="billing_same" checked>
								Billing address is the same as pickup address.
							</label>

							<div class="tbs-billing-fields" data-billing-fields style="display:none;">
								<div class="tbs-form-grid">
									<label class="tbs-field">Billing street
										<input type="text" name="billing_street" placeholder="Billing street">
									</label>

									<label class="tbs-field">Billing Nr.
										<input type="text" name="billing_house_number" placeholder="Nr.">
									</label>

									<label class="tbs-field">Billing postcode
										<input type="text" name="billing_postcode" placeholder="Postcode">
									</label>

									<label class="tbs-field">Billing city
										<input type="text" name="billing_city" placeholder="City">
									</label>
								</div>
							</div>
						</div>

						<div class="tbs-payment-section">
							<?php 
								$current_user_id = get_current_user_id();
								$user_credits = $current_user_id ? TBS_Credits::get_available_credits( $current_user_id ) : 0;
								
								if ( $current_user_id && $user_credits > 0 ) : 
								?>
								<div class="tbs-payment-section" style="background: #ecfdf3; border: 1px solid #b7ebc6; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
									<label class="tbs-checkline" style="font-weight: 700; color: #166534;">
										<input type="checkbox" name="use_credit" value="1" id="tbs_use_credit">
										Use 1 Booking Credit (You have <?php echo esc_html( $user_credits ); ?> remaining)
									</label>
									<p style="margin: 5px 0 0 25px; font-size: 13px; color: #166534;">If checked, you will not be charged today.</p>
								</div>
							<?php endif; ?>
							<h3>Payment method</h3>

							<div class="tbs-payment-methods">
								<label class="tbs-payment-card">
									<input type="radio" name="payment_method" value="Card" checked>
									<span>
										<strong>Credit / Debit Card</strong>
										<small>Pay securely by card</small>
									</span>
								</label>

								<label class="tbs-payment-card">
									<input type="radio" name="payment_method" value="PayPal">
									<span>
										<strong>PayPal</strong>
										<small>Pay with your PayPal account</small>
									</span>
								</label>

								<label class="tbs-payment-card">
									<input type="radio" name="payment_method" value="SEPA">
									<span>
										<strong>SEPA Direct Debit</strong>
										<small>For supported customers</small>
									</span>
								</label>
							</div>
						</div>

						<div class="tbs-payment-total" data-payment-total>
							Total: €0.00
						</div>

						<div class="tbs-final-message" data-final-message>
							Payment processing will be connected in the next backend step.
						</div>
					</section>

					<div class="tbs-wizard-actions">
						<button type="button" class="tbs-button secondary" data-prev-step>Back</button>
						<button type="button" class="tbs-button primary" data-next-step>Next</button>
						<button type="button" class="tbs-button primary hidden" data-submit-wizard>Book Now</button>
					</div>
				</form>

				<aside class="tbs-live-summary">
					<span>Your booking</span>
					<div data-live-summary>
						Enter your details to see the live summary.
					</div>
				</aside>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}