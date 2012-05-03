(function($) {
	window.editors = {};

	window.Rutter = {};

	Rutter.spinner = function (text) {
		if (typeof text == 'undefined') {
			text = rutterL10n.loading;
		}
		return '<div class="spinner"><span>' + text + '</span></div>';
	};
	
	Rutter.loadContent = function($article, postId) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Rutter.spinner());
		$.get(
			rutterL10n.endpointAjax,
			{
				rutter_action: 'post_content',
				post_id: postId
			},
			function(response) {
				if (response.html) {
					$article.replaceWith(response.html);
				}
			},
			'json'
		);
	};
	
	Rutter.loadExcerpt = function($article, postId) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Rutter.spinner());
		$.get(
			rutterL10n.endpointAjax,
			{
				rutter_action: 'post_excerpt',
				post_id: postId
			},
			function(response) {
				if (response.html) {
					$article.replaceWith(response.html);
				}
			},
			'json'
		);
	};

	Rutter.loadEditor = function($article, postId) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Rutter.spinner());
		$.get(
			rutterL10n.endpointAjax,
			{
				rutter_action: 'post_editor',
				post_id: postId
			},
			function(response) {
				if (response.html) {
					$article.replaceWith(response.html);
					window.editors[postId] = ace.edit('ace-editor-' + postId);
					window.Rutter.CFMarkdownMode = require("cf/js/syntax/cfmarkdown").Mode;
					window.editors[postId].getSession().setValue(response.content);
					window.editors[postId].getSession().setUseWrapMode(true);
					window.editors[postId].getSession().setMode('cf/js/syntax/cfmarkdown');
				}
			},
			'json'
		);
	}

	$(function() {
	
		$('.body').on('click', 'article.excerpt', function() {
			// load full content on excerpt click
			var $article = $(this).closest('article.excerpt'),
				postId = $article.data('post-id');
			Rutter.loadContent($article, postId);
		}).on('click', 'article.excerpt header a:not(.post-edit-link)', function(e) {
			// exception for links in header
			e.stopPropagation();
		}).on('dblclick', 'article.content .content', function() {
			// load excerpt on full content doubleclick
			var $article = $(this).closest('article.content'),
				postId = $article.data('post-id');
			Rutter.loadExcerpt($article, postId);
		}).on('click', 'article .post-edit-link', function(e) {
			// load editor
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Rutter.loadEditor($article, postId);
			e.preventDefault();
		}).on('click', 'article .post-close-link', function(e) {
			// save content and load excerpt
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');

// TODO - save content

			Rutter.loadExcerpt($article, postId);
			e.preventDefault();
		});

	});
})(jQuery);
