(function($) {
	$(function() {

// load full content on excerpt click
		$('.body').on('click', 'article.excerpt .content', function() {
			var $article = $(this).closest('article.excerpt');
			$.get(
				rutter.endpointAjax,
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


	});
})(jQuery);
