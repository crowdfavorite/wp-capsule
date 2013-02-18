(function($) {

	$(function() {
		$('form#capsule-add-server').on('submit', function(e) {
			var $form = $(this);
			$form.find('input[name="capsule_client_action"]').val('add_server_ajax');
			e.preventDefault();
			$.post(
				ajaxurl,
				$form.serialize(),
				function(data) {
					if (data.result == 'success') {
						$(data.html).hide().prependTo("div#capsule-servers").fadeIn();
					}
					else {
						// @TODO handle if response is an error
						alert('error');
					}
				},
				'json'
			);
		});

		$('#wpbody-content').on('submit', 'form.update-server', function(e) {
			var $form = $(this);
			$form.find('input[name="capsule_client_action"]').val('update_server_ajax');
			e.preventDefault();
			$.post(
				ajaxurl,
				$form.serialize(),
				function(data) {
					if (data.result == 'success') {
						$form.closest('div').fadeOut().fadeIn();
					}
					else {
						// @TODO handle if response is an error
						alert('error');
					}
				},
				'json'
			);
		});

		$('#wpbody-content').on('submit', 'form.delete-server', function(e) {
			var $form = $(this);
			$form.find('input[name="capsule_client_action"]').val('delete_server_ajax');
			e.preventDefault();
			$.post(
				ajaxurl,
				$form.serialize(),
				function(data) {
					if (data.result == 'success') {
						$form.closest('div.server-item').fadeOut();
					}
					else {
						// @TODO handle if response is an error
						alert('error');
					}
				},
				'json'
			);
		});

	});

})(jQuery);