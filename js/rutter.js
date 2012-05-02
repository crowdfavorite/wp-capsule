Rutter = {};

Rutter.spinner = function (text) {
	if (typeof text == 'undefined') {
		text = rutterL10n.loading;
	}
	return '<div class="spinner"><span>' + text + '</span></div>';
};

(function($) {
	$(function() {

// load full content on excerpt click
		$('.body').on('click', 'article.excerpt .content', function() {
			var $article = $(this).closest('article.excerpt');
			$article.children().addClass('transparent').end()
				.append(Rutter.spinner());
			$.get(
				rutterL10n.endpointAjax,
				{
					rutter_action: 'post_content',
					post_id: $article.attr('id').replace('post-excerpt-', '')
				},
				function(response) {
					if (response.html) {
						$article.replaceWith(response.html);
					}
				},
				'json'
			);
		});

// load excerpt on full content doubleclick
		$('.body').on('dblclick', 'article.content .content', function() {
			var $article = $(this).closest('article.content');
			$article.children().addClass('transparent').end()
				.append(Rutter.spinner());
			$.get(
				rutterL10n.endpointAjax,
				{
					rutter_action: 'post_excerpt',
					post_id: $article.attr('id').replace('post-content-', '')
				},
				function(response) {
					if (response.html) {
						$article.replaceWith(response.html);
					}
				},
				'json'
			);
		});


	});
})(jQuery);
