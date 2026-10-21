jQuery(function ($) {
	$(document).on('click', '#tbs_check_postcode_btn', function () {
		const postcode = $('#tbs_frontend_postcode').val();
		const $result = $('#tbs_postcode_result');

		$('#tbs_booking_form_wrap').hide();
		$result.removeClass('tbs-success tbs-error').html('Checking...');

		$.ajax({
			url: tbsFrontendAjax.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'tbs_check_postcode',
				nonce: tbsFrontendAjax.nonce,
				postcode: postcode
			},
			success: function (response) {
				if (response.success) {
					let routesHtml = '';

					if (response.data.routes && response.data.routes.length) {
						routesHtml += '<div class="tbs-route-options">';
						routesHtml += '<strong>Available service dates/routes:</strong>';

						response.data.routes.forEach(function (route) {
							routesHtml += '<div class="tbs-route-option">';
							routesHtml += '<div><strong>' + route.title + '</strong></div>';

							if (route.date) {
								routesHtml += '<div>Date: ' + route.date + '</div>';
							}

							routesHtml += '<div>Area: ' + route.area + '</div>';

							if (route.capacity) {
								routesHtml += '<div>Capacity: ' + route.capacity + '</div>';
							}

							routesHtml += '<button type="button" class="tbs-select-route-btn" ' +
								'data-route-id="' + route.id + '" ' +
								'data-route-title="' + route.title + '" ' +
								'data-route-date="' + route.date + '" ' +
								'data-price="' + response.data.price + '" ' +
								'data-postcode="' + response.data.postcode + '">' +
								'Select this date' +
								'</button>';

							routesHtml += '</div>';
						});

						routesHtml += '</div>';
					}

					$result
						.addClass('tbs-success')
						.html(
							'<strong>' + response.data.message + '</strong><br>' +
							'Price: €' + response.data.price +
							routesHtml
						);
				} else {
					let errorHtml = response.data.message;

					if (response.data.price) {
						errorHtml += '<br>Price: €' + response.data.price;
					}

					$result
						.addClass('tbs-error')
						.html(errorHtml);
				}
			},
			error: function () {
				$result
					.addClass('tbs-error')
					.html('Something went wrong. Please try again.');
			}
		});
	});

	$(document).on('click', '.tbs-select-route-btn', function () {
		const routeId = $(this).data('route-id');
		const routeTitle = $(this).data('route-title');
		const routeDate = $(this).data('route-date');
		const price = $(this).data('price');
		const postcode = $(this).data('postcode');

		$('#tbs_selected_route_id').val(routeId);
		$('#tbs_selected_route_date').val(routeDate);
		$('#tbs_selected_price').val(price);
		$('#tbs_booking_postcode').val(postcode);

		$('#tbs_selected_summary').html(
			'<strong>Selected route:</strong> ' + routeTitle + '<br>' +
			'<strong>Date:</strong> ' + routeDate + '<br>' +
			'<strong>Price:</strong> €' + price
		);

		$('#tbs_booking_form_wrap').slideDown();

		$('html, body').animate({
			scrollTop: $('#tbs_booking_form_wrap').offset().top - 80
		}, 400);
	});

	$(document).on('click', '#tbs_continue_to_checkout_btn', function () {
		const $message = $('#tbs_booking_form_message');

		const data = {
			action: 'tbs_add_booking_to_cart',
			nonce: tbsFrontendAjax.nonce,
			route_id: $('#tbs_selected_route_id').val(),
			route_date: $('#tbs_selected_route_date').val(),
			price: $('#tbs_selected_price').val(),
			street: $('#tbs_booking_street').val(),
			house_number: $('#tbs_booking_house_number').val(),
			postcode: $('#tbs_booking_postcode').val(),
			city: $('#tbs_booking_city').val(),
			notes: $('#tbs_booking_notes').val()
		};

		$message
			.removeClass('tbs-success tbs-error')
			.addClass('tbs-success')
			.html('Preparing checkout...');

		$.ajax({
			url: tbsFrontendAjax.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: data,
			success: function (response) {
				if (response.success && response.data.checkout_url) {
					window.location.href = response.data.checkout_url;
				} else {
					$message
						.removeClass('tbs-success')
						.addClass('tbs-error')
						.html(response.data.message || 'Could not continue to checkout.');
				}
			},
			error: function () {
				$message
					.removeClass('tbs-success')
					.addClass('tbs-error')
					.html('Something went wrong. Please try again.');
			}
		});
	});
});