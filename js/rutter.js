Rutter = {};

Rutter.spinner = function (text) {
	if (typeof text == 'undefined') {
		text = rutterL10n.loading;
	}
	return '<div class="spinner"><span>' + text + '</span></div>';
};

window.editors = {};

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

// edit post
		$('.body').on('click', 'article .post-edit-link', function(e) {
			var $article = $(this).closest('article'),
				postId = null;
			if ($article.attr('id').indexOf('post-content-') != -1) {
				postId = $article.attr('id').replace('post-content-', '');
			}
			else if ($article.attr('id').indexOf('post-excerpt-') != -1) {
				postId = $article.attr('id').replace('post-excerpt-', '');
			}
			else {
				// no ID found - whoops!
				return;
			}
			$article.children().addClass('transparent').end()
				.append(Rutter.spinner());
			$.get(
				rutterL10n.endpointAjax,
				{
					rutter_action: 'edit_post',
					post_id: postId
				},
				function(response) {
					if (response.html) {
						$article.replaceWith(response.html);
// this isn't loading as desired - need to figure out the right way to size the editor
// 						window.editors[postId] = ace.edit('post-edit-' + postId);

// proof of concept
						window.editors[postId] = ace.edit('ace-editor');

						window.editors[postId].getSession().setValue(response.content);
						window.editors[postId].getSession().setUseWrapMode(true);
					}
				},
				'json'
			);
			e.preventDefault();
		});


	});
})(jQuery);
