jQuery(function ($) {
	let currentStep = 0;
	const totalSteps = 6;
	let postcodeTypingTimer = null;
	let bookingCreated = false;

	function getUrgentSurcharge() {
		return parseFloat(tbsBookingWizard.urgentSurcharge) || 0;
	}

	function getFormData() {
		const extras = [];
		let extrasTotal = 0;

		$('input[name="extras[]"]:checked').each(function () {
			extras.push($(this).val());
			extrasTotal += parseFloat($(this).data('extra-price')) || 0;
		});

		const price = parseFloat($('input[name="price"]').val()) || 0;
		const quantity = parseInt($('input[name="quantity"]').val(), 10) || 1;
		const billingSame = $('input[name="billing_same"]').is(':checked');
		const appointmentType = $('input[name="appointment_type"]').val();

		let appointmentLabel = 'Tour';
		let appointmentDate = $('input[name="route_date"]').val();
		let urgentSurcharge = 0;

		if (appointmentType === 'urgent_single_trip') {
			appointmentLabel = 'Urgent single trip';
			appointmentDate = $('input[name="urgent_date"]').val();
			urgentSurcharge = getUrgentSurcharge();
		}

		if (appointmentType === 'preferred_date') {
			appointmentLabel = 'Free preferred date';
			appointmentDate = $('input[name="free_preferred_date"]').val();
		}

		return {
			street: $('input[name="street"]').val(),
			house_number: $('input[name="house_number"]').val(),
			postcode: $('input[name="postcode"]').val(),
			city: $('input[name="city"]').val(),
			pickup_note: $('input[name="pickup_note"]').val(),

			bin_type: $('input[name="bin_type"]:checked').val(),
			bin_size: $('select[name="bin_size"]').val(),
			quantity: quantity,

			appointment_type: appointmentType,
			appointment_label: appointmentLabel,
			preferred_date: appointmentDate,

			route_id: $('input[name="route_id"]').val(),
			route_title: $('input[name="route_title"]').val(),
			route_date: $('input[name="route_date"]').val(),
			price: price,

			urgent_surcharge: urgentSurcharge,
			extras: extras,
			extras_total: extrasTotal,
			total: (price * quantity) + urgentSurcharge + extrasTotal,

			terms: $('input[name="terms"]').is(':checked'),

			first_name: $('input[name="first_name"]').val(),
			last_name: $('input[name="last_name"]').val(),
			email: $('input[name="email"]').val(),
			phone: $('input[name="phone"]').val(),

			billing_same: billingSame,
			billing_street: billingSame ? $('input[name="street"]').val() : $('input[name="billing_street"]').val(),
			billing_house_number: billingSame ? $('input[name="house_number"]').val() : $('input[name="billing_house_number"]').val(),
			billing_postcode: billingSame ? $('input[name="postcode"]').val() : $('input[name="billing_postcode"]').val(),
			billing_city: billingSame ? $('input[name="city"]').val() : $('input[name="billing_city"]').val(),

			payment_method: $('input[name="payment_method"]:checked').val(),
			use_credit: $('#tbs_use_credit').is(':checked')
		};
	}

	function updateSteps() {
		$('.tbs-wizard-step').removeClass('active');
		$('.tbs-wizard-step[data-step="' + currentStep + '"]').addClass('active');

		$('.tbs-step-pill').removeClass('active completed');

		$('.tbs-step-pill').each(function () {
			const step = parseInt($(this).data('step-jump'), 10);

			if (step === currentStep) {
				$(this).addClass('active');
			}

			if (step < currentStep) {
				$(this).addClass('completed');
			}
		});

		$('[data-prev-step]').toggle(currentStep > 0);
		$('[data-next-step]').toggle(currentStep < totalSteps - 1);
		$('[data-submit-wizard]').toggleClass('hidden', currentStep !== totalSteps - 1);

		if (currentStep === 4) {
			updateSummary();
		}

		if (currentStep === 5) {
			updatePaymentTotal();

			if (!bookingCreated) {
				$('[data-final-message]')
					.removeClass('tbs-zone-success tbs-zone-error')
					.html('Payment processing will be connected in the next backend step.');

				$('[data-submit-wizard]').prop('disabled', false).text('Book Now');
			}
		}

		updateLiveSummary();
	}

	function validateStep(step) {
		const data = getFormData();

		if (step === 0) {
			if (!data.street || !data.house_number || !data.postcode || !data.city) {
				alert('Please complete the address fields.');
				return false;
			}
		}

		if (step === 2) {
			if (data.appointment_type === 'tour' && !data.route_id) {
				alert('Please select an available tour/date.');
				return false;
			}

			if (data.appointment_type === 'urgent_single_trip' && !data.preferred_date) {
				alert('Please select your preferred urgent date.');
				return false;
			}

			if (data.appointment_type === 'preferred_date' && !data.preferred_date) {
				alert('Please select your preferred date.');
				return false;
			}
		}

		if (step === 4 && !data.terms) {
			alert('Please accept the booking terms.');
			return false;
		}

		if (step === 5) {
			if (!data.first_name || !data.last_name || !data.email || !data.phone) {
				alert('Please complete the customer information fields.');
				return false;
			}

			if (!data.billing_same) {
				if (!data.billing_street || !data.billing_house_number || !data.billing_postcode || !data.billing_city) {
					alert('Please complete the billing address fields.');
					return false;
				}
			}
		}

		return true;
	}

	function updateLiveSummary() {
		const data = getFormData();

		let html = '';

		if (data.postcode) {
			html += '<p><strong>Postcode:</strong> ' + data.postcode + '</p>';
		}

		if (data.bin_type) {
			html += '<p><strong>Trashcan:</strong> ' + data.bin_type + ' / ' + data.bin_size + ' × ' + data.quantity + '</p>';
		}

		if (data.appointment_type) {
			html += '<p><strong>Appointment:</strong> ' + data.appointment_label + '</p>';
		}

		if (data.preferred_date) {
			html += '<p><strong>Date:</strong> ' + data.preferred_date + '</p>';
		}

		if (data.urgent_surcharge > 0) {
			html += '<p><strong>Urgent surcharge:</strong> €' + data.urgent_surcharge.toFixed(2) + '</p>';
		}

		if (data.extras.length) {
			html += '<p><strong>Extras:</strong> ' + data.extras.join(', ') + '</p>';
		}

		if (data.total > 0) {
			html += '<p><strong>Total:</strong> €' + data.total.toFixed(2) + '</p>';
		}

		if (!html) {
			html = 'Enter your details to see the live summary.';
		}

		$('[data-live-summary]').html(html);
	}

	function updateSummary() {
		const data = getFormData();

		const extrasHtml = data.extras.length ? data.extras.join(', ') : 'No extras selected';
		const routeHtml = data.appointment_type === 'tour' ? data.route_title : 'Admin confirmation required';

		let surchargeRow = '';

		if (data.urgent_surcharge > 0) {
			surchargeRow = '<div class="tbs-summary-row"><strong>Urgent surcharge</strong><span>€' + data.urgent_surcharge.toFixed(2) + '</span></div>';
		}

		const html =
			'<div class="tbs-summary-row"><strong>Address</strong><span>' +
			data.street + ' ' + data.house_number + ', ' + data.postcode + ' ' + data.city +
			'</span></div>' +

			'<div class="tbs-summary-row"><strong>Trashcan</strong><span>' +
			data.bin_type + ' / ' + data.bin_size + ' × ' + data.quantity +
			'</span></div>' +

			'<div class="tbs-summary-row"><strong>Appointment Type</strong><span>' +
			data.appointment_label + '</span></div>' +

			'<div class="tbs-summary-row"><strong>Route</strong><span>' +
			routeHtml + '</span></div>' +

			'<div class="tbs-summary-row"><strong>Date</strong><span>' +
			data.preferred_date + '</span></div>' +

			surchargeRow +

			'<div class="tbs-summary-row"><strong>Extras</strong><span>' +
			extrasHtml + '</span></div>' +

			'<div class="tbs-summary-row total"><strong>Total</strong><span>€' +
			data.total.toFixed(2) + '</span></div>';

		$('[data-booking-summary]').html(html);
	}

	function updatePaymentTotal() {
		const data = getFormData();
		let displayTotal = data.total;

		if (data.use_credit) {
			displayTotal = 0;
		}

		$('[data-payment-total]').html(
			'Total: €' + displayTotal.toFixed(2)
		);
	}

	function checkPostcodeInstantly() {
		const postcode = $('input[name="postcode"]').val();
		const $zoneMessage = $('[data-zone-message]');

		$('input[name="price"]').val('');
		$('input[name="route_id"]').val('');
		$('input[name="route_title"]').val('');
		$('input[name="route_date"]').val('');
		$('input[name="preferred_date"]').val('');
		$('[data-route-list]').html('');

		if (!postcode) {
			$zoneMessage
				.removeClass('tbs-zone-success tbs-zone-error')
				.html('Enter your postcode to check price and available dates.');
			updateLiveSummary();
			return;
		}

		$zoneMessage
			.removeClass('tbs-zone-success tbs-zone-error')
			.html('Checking postcode...');

		$.ajax({
			url: tbsBookingWizard.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'tbs_check_postcode',
				nonce: tbsBookingWizard.nonce,
				postcode: postcode
			},
			success: function (response) {
				if (response.success) {
					$('input[name="price"]').val(response.data.price);

					$zoneMessage
						.removeClass('tbs-zone-error')
						.addClass('tbs-zone-success')
						.html(
							'<strong>Great news! Your area is available.</strong><br>' +
							'Starting price: €' + response.data.price + '<br>' +
							'Available route dates: ' + response.data.routes.length
						);
				} else {
					let message = 'This postcode is not available yet.';

					if (response.data && response.data.message) {
						message = response.data.message;
					}

					$zoneMessage
						.removeClass('tbs-zone-success')
						.addClass('tbs-zone-error')
						.html(message);
				}

				updateLiveSummary();
			},
			error: function (xhr) {
				let message = 'Could not check postcode. Please try again.';

				if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				}

				$zoneMessage
					.removeClass('tbs-zone-success')
					.addClass('tbs-zone-error')
					.html(message);

				updateLiveSummary();
			}
		});
	}

	function loadRoutes() {
		const postcode = $('input[name="postcode"]').val();
		const $routeList = $('[data-route-list]');

		if (!postcode) {
			alert('Please enter postcode in step 1 first.');
			return;
		}

		$routeList.html('<div class="tbs-loading">Loading available tours...</div>');

		$.ajax({
			url: tbsBookingWizard.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'tbs_check_postcode',
				nonce: tbsBookingWizard.nonce,
				postcode: postcode
			},
			success: function (response) {
				if (!response.success) {
					const message = response.data && response.data.message ? response.data.message : 'No routes found.';
					$routeList.html('<div class="tbs-error-box">' + message + '</div>');
					return;
				}

				$('input[name="price"]').val(response.data.price);

				let html = '';

				response.data.routes.forEach(function (route) {
					let capacityText = '';

					if (route.remaining !== '') {
						capacityText = '<small>Free stops: ' + route.remaining + '</small>';
					}

					html += '<button type="button" class="tbs-route-choice" ' +
						'data-route-id="' + route.id + '" ' +
						'data-route-title="' + route.title + '" ' +
						'data-route-date="' + route.date + '" ' +
						'data-price="' + response.data.price + '">' +
						'<strong>' + route.title + '</strong>' +
						(route.date ? '<span>Date: ' + route.date + '</span>' : '') +
						'<small>Area: ' + route.area + '</small>' +
						capacityText +
						'</button>';
				});

				$routeList.html(html);
			},
			error: function () {
				$routeList.html('<div class="tbs-error-box">Could not load routes. Please try again.</div>');
			}
		});
	}

	function createWizardOrder() {
		const data = getFormData();

		$('[data-final-message]')
			.removeClass('tbs-zone-error tbs-zone-success')
			.html('Creating your booking...');

		$('[data-submit-wizard]').prop('disabled', true);

		$.ajax({
			url: tbsBookingWizard.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'tbs_create_wizard_order',
				nonce: tbsBookingWizard.nonce,

				street: data.street,
				house_number: data.house_number,
				postcode: data.postcode,
				city: data.city,
				pickup_note: data.pickup_note,

				bin_type: data.bin_type,
				bin_size: data.bin_size,
				quantity: data.quantity,

				appointment_type: data.appointment_type,
				preferred_date: data.preferred_date,

				route_id: data.route_id,
				route_title: data.route_title,
				route_date: data.route_date,

				base_price: data.price,
				urgent_surcharge: data.urgent_surcharge,
				extras: data.extras,
				extras_total: data.extras_total,
				total: data.total,

				first_name: data.first_name,
				last_name: data.last_name,
				email: data.email,
				phone: data.phone,

				billing_same: data.billing_same ? 'yes' : 'no',
				billing_street: data.billing_street,
				billing_house_number: data.billing_house_number,
				billing_postcode: data.billing_postcode,
				billing_city: data.billing_city,

				payment_method: data.payment_method,
				use_credit: data.use_credit
			},
			success: function (response) {
				if (response.success) {
					bookingCreated = true;

					$('[data-final-message]')
						.addClass('tbs-zone-success')
						.html(
							'<div class="tbs-custom-success">' +
								'<h3>Booking created! Redirecting...</h3>' +
								'<p>Please wait while we take you to the next step.</p>' +
							'</div>'
						);

					// Redirect to the WooCommerce payment page or Thank You page
					if (response.data.redirect_url) {
						window.location.href = response.data.redirect_url;
					}
				} else {
					const message = response.data && response.data.message ? response.data.message : 'Could not create booking.';

					$('[data-final-message]')
						.addClass('tbs-zone-error')
						.html(message);

					$('[data-submit-wizard]').prop('disabled', false);
				}
			},
			error: function () {
				$('[data-final-message]')
					.addClass('tbs-zone-error')
					.html('Something went wrong while creating the booking.');

				$('[data-submit-wizard]').prop('disabled', false);
			}
		});
	}

	$(document).on('click', '[data-next-step]', function () {
		if (!validateStep(currentStep)) {
			return;
		}

		if (currentStep < totalSteps - 1) {
			currentStep++;
			updateSteps();
		}
	});

	$(document).on('click', '[data-prev-step]', function () {
		if (currentStep > 0) {
			currentStep--;
			updateSteps();
		}
	});

	$(document).on('click', '[data-step-jump]', function () {
		const target = parseInt($(this).data('step-jump'), 10);

		if (target < currentStep || validateStep(currentStep)) {
			currentStep = target;
			updateSteps();
		}
	});

	$(document).on('click', '.tbs-appointment-tab', function () {
		const type = $(this).data('appointment-type');

		$('.tbs-appointment-tab').removeClass('active');
		$(this).addClass('active');

		$('.tbs-appointment-panel').removeClass('active');
		$('.tbs-appointment-panel[data-appointment-panel="' + type + '"]').addClass('active');

		$('input[name="appointment_type"]').val(type);

		$('input[name="route_id"]').val('');
		$('input[name="route_title"]').val('');
		$('input[name="route_date"]').val('');
		$('input[name="preferred_date"]').val('');

		$('.tbs-route-choice').removeClass('active');

		updateLiveSummary();
		updatePaymentTotal();
	});

	$(document).on('click', '[data-load-routes]', function () {
		loadRoutes();
	});

	$(document).on('input change', 'input[name="postcode"]', function () {
		clearTimeout(postcodeTypingTimer);

		postcodeTypingTimer = setTimeout(function () {
			checkPostcodeInstantly();
		}, 500);
	});

	$(document).on('click', '.tbs-route-choice', function () {
		$('.tbs-route-choice').removeClass('active');
		$(this).addClass('active');

		$('input[name="route_id"]').val($(this).data('route-id'));
		$('input[name="route_title"]').val($(this).data('route-title'));
		$('input[name="route_date"]').val($(this).data('route-date'));
		$('input[name="preferred_date"]').val($(this).data('route-date'));
		$('input[name="price"]').val($(this).data('price'));

		updateLiveSummary();
	});

	$(document).on('change', 'input[name="urgent_date"]', function () {
		$('input[name="preferred_date"]').val($(this).val());
		updateLiveSummary();
	});

	$(document).on('change', 'input[name="free_preferred_date"]', function () {
		$('input[name="preferred_date"]').val($(this).val());
		updateLiveSummary();
	});

	$(document).on('change', 'input[name="billing_same"]', function () {
		if ($(this).is(':checked')) {
			$('[data-billing-fields]').slideUp();
		} else {
			$('[data-billing-fields]').slideDown();
		}

		updateLiveSummary();
	});

	$(document).on('input change', '#tbs_booking_wizard_form input, #tbs_booking_wizard_form select', function () {
		updateLiveSummary();
		updatePaymentTotal();

		if (currentStep === 4) {
			updateSummary();
		}
	});

	$(document).on('change', '#tbs_use_credit', function () {
		updatePaymentTotal();
	});

	$(document).on('click', '[data-submit-wizard]', function () {
		if (!validateStep(5)) {
			return;
		}

		createWizardOrder();
	});
	
	// Auto-fill address from saved addresses
	$(document).on('change', '#tbs_saved_address_select', function () {
		const selectedData = $(this).val();

		if (!selectedData) {
			$('input[name="street"]').val('');
			$('input[name="house_number"]').val('');
			$('input[name="postcode"]').val('');
			$('input[name="city"]').val('');
			return;
		}

		try {
			const data = JSON.parse(selectedData);
			$('input[name="street"]').val(data.street);
			$('input[name="house_number"]').val(data.house_number);
			$('input[name="postcode"]').val(data.postcode).trigger('change'); // Trigger change to check postcode
			$('input[name="city"]').val(data.city);
			
			// Update live summary
			updateLiveSummary();
		} catch (e) {
			console.error('Invalid address data', e);
		}
	});

	updateSteps();
});