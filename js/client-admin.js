(function($) {

	$(function() {
		$('form#js-capsule-add-server').on('submit', function(e) {
			var $form = $(this);
			$form.find('input[name="capsule_client_action"]').val('add_server_ajax');
			e.preventDefault();
			$.post(
				ajaxurl,
				$form.serialize(),
				function(data) {
					if (data.result == 'success') {
						$(data.html).hide().prependTo("#js-capsule-update-servers tbody").fadeIn();
					}
					else {
						// @TODO handle if response is an error
						alert('error');
					}
				},
				'json'
			);
		});

		$('#wpbody-content').on('click', 'form#js-capsule-update-servers .js-update-server', function(e) {
			var server_id = $(this).data('server_id');
			var $form = $('form#js-capsule-update-servers');
			e.preventDefault();
			$.post(
				ajaxurl,
				{
					capsule_client_action : 'update_server_ajax',
					server_name : $('#js-server-name-'+server_id).val(),
					server_id : server_id,
					api_key : $('#js-server-api_key-'+server_id).val(),
					server_url : $('#js-server-url-'+server_id).val(),
					_update_server_nonce : $('#_update_server_nonce').val(),
					_wp_http_referer : $form.find('input[name="_wp_http_referer"]').val()
				},
				function(data) {
					if (data.result == 'success') {
						$('#js-server-item-'+server_id).fadeOut().fadeIn();
					}
					else {
						// @TODO nothing, it hasn't been updated
					}
				},
				'json'
			);
		});

		$('#wpbody-content').on('click', '.js-delete-server', function(e) {
			var url = $(this).attr('href');
			var $form = $('#js-capsule-update-servers');
			var server_id = $(this).data('server_id');
			e.preventDefault();
			$.get(
				url,
				{ doing_ajax : true },
				function(data) {
					if (data.result == 'success') {
						$form.find('#js-server-item-'+server_id).fadeOut();
					}
					else {
						//nothing it hasn't been deleted
					}
				},
				'json'
			);
		});

	});

})(jQuery);