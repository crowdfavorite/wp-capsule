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
					Rutter.initEditor(postId, response.content);
				}
			},
			'json'
		);
	};
	
	Rutter.createPost = function($article) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Rutter.spinner());
		$.post(
			rutterL10n.endpointAjax,
			{
				rutter_action: 'create_post'
			},
			function(response) {
				if (response.html) {
					$article.replaceWith(response.html);
					Rutter.initEditor(response.post_id, '');
				}
			},
			'json'
		);
	};
	
	Rutter.updatePost = function(postId, content, $article, loadExcerpt) {
		if (typeof loadExcerpt == 'undefined') {
			loadExcerpt = false;
		}
		if (loadExcerpt) {
			$article.addClass('unstyled').children().addClass('transparent').end()
				.append(Rutter.spinner());
		}
		$.post(
			rutterL10n.endpointAjax,
			{
				rutter_action: 'update_post',
				post_id: postId,
				content: content
			},
			function(response) {
				if (response.result == 'success') {
					if (loadExcerpt) {
						Rutter.loadExcerpt($article, postId);
					}
				}
			},
			'json'
		);
	};
	
	Rutter.initEditor = function(postId, content) {
		window.Rutter.CFMarkdownMode = require("cf/js/syntax/cfmarkdown").Mode;
		window.editors[postId] = ace.edit('ace-editor-' + postId);
		window.editors[postId].getSession().setValue(content);
		window.editors[postId].getSession().setUseWrapMode(true);
		window.editors[postId].getSession().setMode('cf/js/syntax/cfmarkdown');
		window.editors[postId].focus();
	};

	$(function() {
	
		$('.body').on('click', 'article.excerpt:not(a.post-edit-link)', function(e) {
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
			// don't allow bubbling to load content
			if ($article.hasClass('excerpt')) {
				e.stopPropagation();
			}
		}).on('click', 'article .post-close-link', function(e) {
			// save content and load excerpt
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Rutter.updatePost(postId, window.editors[postId].getSession().getValue(), $article, true);
			e.preventDefault();
		});
		$('#header').on('click', '.post-new-link', function(e) {
			var $article = $('<article></article>').height('400px'),
				ymd = date('Ymd', (new Date()).getTime() / 1000),
				$dateTitle = $('.body h2.date-' + ymd);
			if ($dateTitle.size()) {
				$dateTitle.after($article);
			}
			else {
				$('.body').prepend($article);
			}
			Rutter.createPost($article);
			e.preventDefault();
		});

	});
})(jQuery);
