define('cf/js/capsule', ['require', 'exports', 'module',
		'jquery',
		'ace/ace',
		'ace/mode/text', 'ace/lib/dom', 'ace/tokenizer',
		'cf/js/syntax/cf_php_highlight_rules', 'cf/js/syntax/cfmarkdown', 'cf/js/syntax/cfsh',
		'cf/js/static_highlight', 'ace/theme/textmate'
	],
function(require, exports, module, $) {
"use strict";
	var ace = require('ace/ace');
	var aceconfig = require('ace/config');

	aceconfig.set('packaged', true);
	aceconfig.set('basePath', requirejsL10n.ace + '/build/src-min');
	window.editors = {},
	window.Capsule = {},
	Capsule.delaySave = {};

	Capsule.spinner = function (text) {
		if (typeof text == 'undefined') {
			text = capsuleL10n.loading;
		}
		return '<div class="spinner"><span>' + text + '</span></div>';
	};

	Capsule.authCheck = function(response) {
		if (typeof response.result != 'undefined' && response.result == 'unauthorized') {
			alert(response.msg);
			location.href = response.login_url + '?redirect_to=' + encodeURIComponent(location.href);
			return false;
		}
		return true;
	};

	Capsule.get = function(url, args, callback, type) {
		$.get(url, args, function(response) {
			if (Capsule.authCheck(response)) {
				callback.call(this, response);
			}
		}, type);
	};

	Capsule.post = function(url, args, callback, type) {
		$.post(url, args, function(response) {
			if (Capsule.authCheck(response)) {
				callback.call(this, response);
			}
		}, type);
	};

	Capsule.loadExcerpt = function($article, postId) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Capsule.spinner());
		Capsule.get(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'post_excerpt',
				post_id: postId
			},
			function(response) {
				if (response.html) {
					$article.replaceWith(response.html);
					$article = $('#post-content-' + postId);
					Capsule.postExpandable($article);
					$article.scrollintoview({ offset: 10 }).find('.post-content').linkify();
					Capsule.highlightCodeSyntax($article.find('.post-content'));
				}
			},
			'json'
		);
	};

	Capsule.centerEditor = function(postId) {
		$('#post-edit-' + postId).scrollintoview({
			duration: 200,
			offset: 15
		})
	};

	Capsule.loadEditor = function($article, postId) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Capsule.spinner());
		Capsule.get(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'post_editor',
				post_id: postId
			},
			function(response) {
				if (response.html) {
					$article.replaceWith(response.html);
					Capsule.sizeEditor();
					Capsule.initEditor(postId, response.content);
					Capsule.centerEditor(postId);
				}
			},
			'json'
		);
	};

	Capsule.createPost = function($article) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Capsule.spinner());
		Capsule.post(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'create_post'
			},
			function(response) {
				if (response.html) {
					$article.replaceWith(response.html);
					Capsule.sizeEditor();
					Capsule.initEditor(response.post_id, '');
					Capsule.centerEditor(response.post_id);
				}
			},
			'json'
		);
	};

	Capsule.watchForEditorChanges = function(postId, $article, suppress_time_display) {
		if (typeof $article == 'undefined') {
			$article = $('#post-edit-' + postId);
		}
		if (typeof suppress_time_display == 'undefined') {
			suppress_time_display = false;
		}

		var timestamp = (new Date()).getTime() / 1000,
			updated = date('g:i a', timestamp),
			save_cb = function() {
				Capsule.delaySave[postId] = null;
				Capsule.updatePost(postId, window.editors[postId].getSession().getValue());
			},
			change_cb = function() {
				$article.clearQueue().addClass('dirty');
				if (Capsule.delaySave[postId]) {
					clearTimeout(Capsule.delaySave[postId]);
				}
				Capsule.delaySave[postId] = setTimeout(save_cb, 10000);
				window.editors[postId].getSession().removeEventListener('change', change_cb);
				return true;
			};
		if (!suppress_time_display) {
			$article.find('span.post-last-saved').html(updated);
		}
		window.editors[postId].getSession().on('change', change_cb);

		// Debounce clearing the dirty flag slightly
		$article.delay(50).queue(function() {
			$(this).removeClass('dirty').dequeue();
		});
	};

	Capsule.updatePost = function(postId, content, $article, loadExcerpt) {
		if (typeof loadExcerpt == 'undefined') {
			loadExcerpt = false;
		}
		if (typeof $article == 'undefined') {
			$article = $('#post-edit-' + postId);
		}
		if (loadExcerpt) {
			$article.addClass('unstyled')
				.children().addClass('transparent').end()
				.append(Capsule.spinner());
		}
		else {
			$article.addClass('saving');
		}
		// strip code blocks before extracting projects and tags
		var prose = content.replace(/^```([\s\S]+)^```/mg, '')
				.replace(/^\/\*([\s\S]+)^\*\//mg, '')
				.replace(/<pre>([^]+?)<\/pre>/mg, '')
				.replace(/<code>([^]+?)<\/code>/mg, ''),
			projects = twttr.txt.extractMentions(prose),
			tags = twttr.txt.extractHashtags(prose),
			code = Capsule.extractCodeLanguages(content);
		Capsule.post(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'update_post',
				post_id: postId,
				content: content,
				projects: JSON.stringify(projects),
				post_tag: JSON.stringify(tags),
				code: JSON.stringify(code)
			},
			function(response) {
				if (response.result == 'success') {
					if (loadExcerpt) {
						Capsule.loadExcerpt($article, postId);
					}
					else {
						$article.removeClass('saving');
						Capsule.updatePostTaxonomies($article, response.projects_html, response.tags_html, response.code_html);
						Capsule.watchForEditorChanges(postId, $article);
					}
				}
			},
			'json'
		);
	};

	Capsule.updatePostTaxonomies = function($article, project_html, tags_html, code_html) {
		$article.find('.post-meta').html(project_html + tags_html + code_html);
	};

	Capsule.deletePost = function(postId, $article) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Capsule.spinner());
		Capsule.post(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'delete_post',
				post_id: postId
			},
			function(response) {
				if (response.result == 'success') {
					$article.replaceWith(response.html);
				}
				else {
					alert(response.msg);
					$article.removeClass('unstyled').children().removeClass('transparent').end()
						.find('.spinner').remove();
				}
			},
			'json'
		);
	};

	Capsule.undeletePost = function(postId, $article) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Capsule.spinner());
		Capsule.post(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'undelete_post',
				post_id: postId
			},
			function(response) {
				if (response.result == 'success') {
					$article.replaceWith(response.html);
					$article = $('#post-content-' + postId);
					Capsule.postExpandable($article);
				}
				else {
					alert(response.msg);
					$article.removeClass('unstyled').children().removeClass('transparent').end()
						.find('.spinner').remove();
				}
			},
			'json'
		);
	};

	Capsule.stickPost = function(postId, $article) {
		$article.addClass('sticky-loading');
		Capsule.post(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'stick_post',
				post_id: postId
			},
			function(response) {
				if (response.result == 'success') {
					$article.addClass('sticky').removeClass('sticky-loading');
				}
				else {
					alert(response.msg);
				}
			},
			'json'
		);
	};

	Capsule.unstickPost = function(postId, $article) {
		$article.addClass('sticky-loading');
		Capsule.post(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'unstick_post',
				post_id: postId
			},
			function(response) {
				if (response.result == 'success') {
					// in some situations the sticky class is added twice by WP + custom code,
					// this removes both instances
					$article.removeClass('sticky sticky sticky-loading');
				}
				else {
					alert(response.msg);
				}
			},
			'json'
		);
	};

	Capsule.initEditor = function(postId, content) {
		window.Capsule.CFMarkdownMode = require("cf/js/syntax/cfmarkdown").Mode;
		window.editors[postId] = ace.edit('ace-editor-' + postId);
		window.editors[postId].getSession().setUseWrapMode(true);
		window.editors[postId].getSession().setMode('cf/js/syntax/cfmarkdown');
		window.editors[postId].setShowPrintMargin(false);
		window.editors[postId].setTheme('ace/theme/twilight');
		window.editors[postId].getSession().setValue(content);
		window.editors[postId].container.style.lineHeight = '20px';
		window.editors[postId].renderer.setPadding(12);
		window.editors[postId].commands.addCommand({
			name: 'save',
			bindKey: {
				mac: 'Command-S',
				win: 'Ctrl-S'
			},
			exec: function(editor) {
				Capsule.updatePost(postId, editor.getSession().getValue());
			}
		});
		window.editors[postId].commands.addCommand({
			name: 'recenter',
			bindKey: {
				mac: 'Command-Shift-0',
				win: 'Ctrl-Shift-0'
			},
			exec: function(editor) {
				Capsule.centerEditor(postId);
			}
		});
		window.editors[postId].commands.addCommand({
			name: 'close',
			bindKey: {
				mac: 'Esc',
				win: 'Esc'
			},
			exec: function(editor) {
				var $article = $('#post-edit-' + postId);
				Capsule.updatePost(postId, window.editors[postId].getSession().getValue(), $article, true);
			}
		});
		window.editors[postId].commands.addCommand({
			name: 'cfindent',
			bindKey: {
				mac: 'Command-]',
				win: 'Ctrl-]'
			},
			exec: function(editor) {
				editor.blockIndent();
			},
			multiSelectAction: "forEachLine"
		});
		window.editors[postId].commands.addCommand({
			name: 'cfoutdent',
			bindKey: {
				mac: 'Command-[',
				win: 'Ctrl-['
			},
			exec: function(editor) {
				editor.blockOutdent();
			},
			multiSelectAction: "forEachLine"
		});

		Capsule.watchForEditorChanges(postId, undefined, true);
		window.editors[postId].focus();
	};

	Capsule.sizeEditor = function() {
		$('.ace-editor:not(.resized)').each(function() {
			$(this).height(
				($(window).height() - $(this).closest('article').find('header').height() - 60) + 'px'
			);
		});
	};

	Capsule.saveAllEditors = function() {
		$('.ace-editor').each(function() {
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			if ($article.hasClass('dirty')) {
				Capsule.updatePost(postId, window.editors[postId].getSession().getValue());
			}
		});
	};

	Capsule.extractCodeLanguages = function(content) {
		var block = new RegExp("^```[a-zA-Z]+\\s*$", "gm"),
			tag = new RegExp("[a-zA-Z]+", ""),
			tags = [],
			matches = content.match(block);
		if (matches != null && matches.length) {
			$.each(matches, function(i, val) {
				tags.push(val.match(tag)[0].replace(/^js$/i, "javascript"));
			});
		}
		return tags;
	};

	Capsule.postExpandable = function($article) {
		if ($article.find('.post-content:first')[0].scrollHeight > $article[0].scrollHeight) {
			$article.addClass('toggleable');
		}
	};

	Capsule.highlightCodeSyntax = function($elem) {
		if (typeof $elem == 'undefined') {
			$elem = $('article:not(.edit) .post-content');
		}
		$elem.each(function() {
			var block = $(this);
			block.find("pre>code").each(function(i) {
				var el = $(this),
					data, lang, requirelang,
					newlines = [""];
				// markdown uses <br> for leading blank
				// lines; replace with real newlines for Ace
				el.find("br").each(function(i) {
					newlines.push("");
				});
				data = newlines.join("\n") + el.text();

				// remove markdown's trailing newline
				if (data.substr(-1) === "\n") {
					data = data.substr(0, data.length - 1);
				}

				lang = el.attr('class');
				if (lang) {
					lang = lang.match(/language-([-_a-z0-9]+)/i);
					if (lang) {
						lang = lang[1].toLowerCase();
					}
					if ("js" === lang) {
						lang = "javascript";
					}
				}
				else {
					lang = "code";
				}
				if (lang === "code" || lang === "bash") {
					requirelang = "cfsh";
				}
				else {
					requirelang = lang;
				}
				try {

					var dohighlight = function(highlighter, theme, mode) {
						var highlighted;
						if (mode) {
							mode = mode.Mode;
							mode = new mode();
							if ('php' === lang) {
								var Tokenizer = require("ace/tokenizer").Tokenizer;
								var PhpLangHighlightRules = require("cf/js/syntax/cf_php_highlight_rules").PhpLangHighlightRules;
								mode.$tokenizer = new Tokenizer(new PhpLangHighlightRules().getRules());
							}
							highlighted = highlighter.render(data, mode, theme, 1, lang);
							el.closest("pre").replaceWith(highlighted.html);
						}
					};
					var namespacedLang = ('cfsh' === requirelang) ? 'cf/js/syntax/cfsh' : 'ace/mode/' + requirelang;
					var requirements = [
						'cf/js/static_highlight', 'ace/theme/textmate',
						namespacedLang, 'ace/lib/dom', 'ace/tokenizer',
						'cf/js/syntax/cf_php_highlight_rules'
					];
					require(requirements,
							dohighlight);
				}
				catch (er) {console.log(er); throw(er);}
			});
		});
	};

	$(function() {
		Capsule.highlightCodeSyntax();

		$('.js-search').suggest(capsuleSearchURL + '?capsule_action=search', {
			delay : 500,
			// token plus first character
			minchars: 2,
			multiple: true,
			multipleSep: ' ',
			resultsClass: 'search_results',
			selectClass: 'search_selected',
			matchClass: 'search_match'
		});

		$('.js-search').closest('form').on('submit', function(e) {
			e.preventDefault();
			var $input = $('.js-search', $(this));
			var $form = $(this);

			$input.val($input.val().trim());
			if ($form.data('permastruct') == 1) {
				window.location.href = this.action+'search/'+encodeURIComponent(this.s.value).replace(/%20/g, '+').replace(/%2f/gi, '/');
				return false;
			}
			else {
				$form.unbind('submit').submit();
			}
		});

		$(document).on('click', 'article.excerpt.toggleable .post-content', function(e) {
			// load full content on excerpt click
			$(this).closest('article.excerpt.toggleable').removeClass('excerpt').addClass('open');
		}).on('click', 'article:not(.excerpt, a.post-edit-link) .post-toggle', function(e) {
			// load excerpt on content click
			$(this).closest('article').removeClass('open').addClass('excerpt');
		}).on('click', 'article .post-edit-link', function(e) {
			// load editor
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.loadEditor($article, postId);
			e.preventDefault();
		}).on('dblclick', 'body:not(.capsule-server) article:not(.edit) .post-content', function(e) {
			// load editor
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.loadEditor($article, postId);
		}).on('dblclick', 'body:not(.capsule-server) article:not(.edit) pre, body:not(.capsule-server) article:not(.edit) code', function(e) {
			// Don't load editor on double-click inside code blocks (pre or code)
			e.stopPropagation();
		}).on('click', 'article .post-close-link', function(e) {
			e.preventDefault();
			// save content and load excerpt
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.updatePost(postId, window.editors[postId].getSession().getValue(), $article, true);
		}).on('click', 'article .post-save-link', function(e) {
			e.preventDefault();
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.updatePost(postId, window.editors[postId].getSession().getValue());
			window.editors[postId].focus();
		}).on('click', 'article .post-delete-link', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.deletePost(postId, $article);
		}).on('click', 'article .post-undelete-link', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.undeletePost(postId, $article);
		}).on('click', 'article:not(.sticky) .post-sticky-link', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.stickPost(postId, $article);
		}).on('click', 'article.sticky .post-unsticky-link', function(e) {
			e.stopPropagation();
			e.preventDefault();
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.unstickPost(postId, $article);
		}).on('mousewheel', 'article.edit .post-content', function(e) {
			e.preventDefault();
		}).on('click', '.post-new-link', function(e) {
			e.preventDefault();
			if ($('#sidr-projects').is(':visible')) {
				$.sidr('close', 'sidr-projects');
			}
			if ($('#sidr-tags').is(':visible')) {
				$.sidr('close', 'sidr-tags');
			}
			if ($('#sidr-servers').is(':visible')) {
				$.sidr('close', 'sidr-servers');
			}
			var $article = $('<article></article>').height('400px');
			$('.body').prepend($article);
			Capsule.createPost($article);
		}).on('click', '.filter-toggle', function(e) {
			e.preventDefault();
			var $body = $('body'),
				$header = $('#header'),
				$search = $header.find('input[name="s"]'),
				$filter = $header.find('.filter');
			if (!$body.hasClass('filters-on')) {
				$filter.slideDown();
				$('body').addClass('filters-on');
				$search.attr('disabled', 'disabled');
			}
			else {
				$filter.slideUp();
				$('body').removeClass('filters-on');
				$search.removeAttr('disabled');
			}
		}).on('heartbeat-connection-lost', function() { // WP 3.6 heartbeat API support
			$('body:not(".capsule-server")').addClass('connection-lost');
		}).on('heartbeat-connection-restored', function() { // WP 3.6 heartbeat API support
			$('body').removeClass('connection-lost');
		}).on('keyup', null, 'shift+h', function() { // home nav
			location.href = $('.main-nav .home').attr('href');
		}).on('keyup', null, 'shift+n', function() { // new post
			$('.main-nav .post-new-link').click();
		}).on('keyup', null, 'shift+f', function() { // new post
			$('.js-search').focus().select();
		});
		$(window).on('resize', function() {
			Capsule.sizeEditor();
		}).on('blur', function() {
			Capsule.saveAllEditors();
		});
		$('article').each(function() {
			Capsule.postExpandable($(this));
		});
		$('.main-nav').find('.projects').sidr({
			name: 'sidr-projects',
			source: '#projects',
		}).end().find('.tags').sidr({
			name: 'sidr-tags',
			source: '#tags'
		}).end().find('.servers').sidr({
			name: 'sidr-servers',
			renaming: false,
			source: '#servers'
		});
		$(':not(.edit) .post-content').linkify();
	});
});
