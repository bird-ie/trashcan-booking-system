jQuery(function ($) {
	let typingTimer = null;

	function updateMatchingRoutes() {
		const postcode = $('#tbs_postcode').val();
		const selectedRouteId = $('#tbs_route_id').val();

		$('#tbs_route_id').prop('disabled', true);

		$.ajax({
			url: tbsAdminAjax.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'tbs_get_matching_routes',
				nonce: tbsAdminAjax.nonce,
				postcode: postcode,
				selectedRouteId: selectedRouteId
			},
			success: function (response) {
				if (!response.success || !response.data.options) {
					return;
				}

				const $routeSelect = $('#tbs_route_id');

				$routeSelect.empty();

				response.data.options.forEach(function (option) {
					const $option = $('<option></option>')
						.attr('value', option.id)
						.text(option.label);

					if (option.selected) {
						$option.prop('selected', true);
					}

					$routeSelect.append($option);
				});
			},
			complete: function () {
				$('#tbs_route_id').prop('disabled', false);
			}
		});
	}

	$(document).on('input change', '#tbs_postcode', function () {
		clearTimeout(typingTimer);

		typingTimer = setTimeout(function () {
			updateMatchingRoutes();
		}, 400);
	});
});