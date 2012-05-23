(function($) {

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
	
	Capsule.loadContent = function($article, postId) {
		$article.addClass('unstyled').children().addClass('transparent').end()
			.append(Capsule.spinner());
		Capsule.get(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'post_content',
				post_id: postId
			},
			function(response) {
				if (response.html) {

					var block = $(response.html);
					block.find("pre>code").each(function(i) {
						var el = $(this), 
							data, lang,
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
						try {
							var highlighter = require("ace/ext/static_highlight");
							var theme = require("ace/theme/textmate");
							var mode = require("ace/mode/" + lang);
							var dom = require("ace/lib/dom");
							if (!mode) {
								mode = require("ace/mode/text");
							}
							if (mode) {
								mode = mode.Mode;
								var highlighted = highlighter.render(data, new mode(), theme, 1, lang);
								el.closest("pre").replaceWith(highlighted.html);
							}
						}
						catch (er) {console.log(er); throw(er);}
					});
					$article.replaceWith(block);
					$('#post-content-' + postId).scrollintoview({ offset: 10 });
				}
			},
			'json'
		);
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
					$('#post-content-' + postId).scrollintoview({ offset: 10 });
				}
			},
			'json'
		);
	};

	Capsule.centerEditor = function(postId) {
		$.scrollTo('#post-edit-' + postId, {offset: -10});
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
					Capsule.centerEditor(postId);
					Capsule.sizeEditor();
					Capsule.initEditor(postId, response.content);
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
					Capsule.centerEditor(response.post_id);
					Capsule.sizeEditor();
					Capsule.initEditor(response.post_id, '');
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
		ymd = date('g:i a', timestamp),
		save_cb = function() {
			Capsule.delaySave[postId] = null;
			Capsule.updatePost(postId, window.editors[postId].getSession().getValue());
		}
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
			$article.find('span.post-last-saved').html("Last saved: " + ymd);
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
			$article.addClass('unstyled').children().addClass('transparent').end()
				.append(Capsule.spinner());
		}
		else {
			$article.addClass('saving');
		}
		var projects = twttr.txt.extractMentions(content),
			tags = twttr.txt.extractHashtags(content),
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
						Capsule.watchForEditorChanges(postId, $article);
					}
				}
			},
			'json'
		);
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
	
	Capsule.initEditor = function(postId, content) {
		window.Capsule.CFMarkdownMode = require("cf/js/syntax/cfmarkdown").Mode;
		window.editors[postId] = ace.edit('ace-editor-' + postId);
		window.editors[postId].getSession().setUseWrapMode(true);
		window.editors[postId].getSession().setMode('cf/js/syntax/cfmarkdown');
		window.editors[postId].setShowPrintMargin(false);
		window.editors[postId].getSession().setValue(content);
		window.editors[postId].commands.addCommand({
			name: 'save',
			bindKey: {
				win: 'Ctrl-S',
				mac: 'Command-S'
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

		Capsule.watchForEditorChanges(postId, undefined, true);
		window.editors[postId].focus();
	};
	
	Capsule.sizeEditor = function() {
		$('.ace-editor:not(.resized)').each(function() {
			$(this).height(
				($(window).height() - $(this).closest('article').find('header').height() - 40) + 'px'
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
	}
	
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
	
	$(function() {
	
		$('.body').on('click', 'article.excerpt:not(a.post-edit-link)', function(e) {
			// load full content on excerpt click
			var $article = $(this).closest('article.excerpt'),
				postId = $article.data('post-id');
			Capsule.loadContent($article, postId);
		}).on('click', 'article.excerpt header a:not(.post-edit-link)', function(e) {
			// exception for links in header
			e.stopPropagation();
		}).on('dblclick', 'article.content header', function() {
			// load excerpt on full content doubleclick
			var $article = $(this).closest('article.content'),
				postId = $article.data('post-id');
			Capsule.loadExcerpt($article, postId);
		}).on('click', 'article .post-edit-link', function(e) {
			// load editor
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.loadEditor($article, postId);
			e.preventDefault();
			// don't allow bubbling to load content
			if ($article.hasClass('excerpt')) {
				e.stopPropagation();
			}
		}).on('click', 'article .post-close-link', function(e) {
			// save content and load excerpt
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.updatePost(postId, window.editors[postId].getSession().getValue(), $article, true);
			e.preventDefault();
		}).on('click', 'article .post-save-link', function(e) {
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.updatePost(postId, window.editors[postId].getSession().getValue());
			window.editors[postId].focus();
			e.preventDefault();
		}).on('click', 'article .post-delete-link', function(e) {
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.deletePost(postId, $article);
			e.stopPropagation();
			e.preventDefault();
		}).on('click', 'article .post-undelete-link', function(e) {
			var $article = $(this).closest('article'),
				postId = $article.data('post-id');
			Capsule.undeletePost(postId, $article);
			e.stopPropagation();
			e.preventDefault();
		}).on('mousewheel', 'article.edit', function(e) {
			e.preventDefault();
		});
		$('#header').on('click', '.post-new-link', function(e) {
			var $article = $('<article></article>').height('400px'),
				timestamp = (new Date()).getTime() / 1000,
				ymd = date('Ymd', timestamp),
				$dateTitle = $('.body h2.date-' + ymd);
			if ($dateTitle.size()) {
				$dateTitle.after($article);
			}
			else {
				$('.body').prepend($article)
					.prepend('<h2 class="date-title date-' + ymd + '">' + date('F j, Y', timestamp) + '</h2>');
			}
			Capsule.createPost($article);
			e.preventDefault();
		});
		$(window).on('resize', function() {
			Capsule.sizeEditor();
		});
		$(window).on('blur', function() {
			Capsule.saveAllEditors();
		})

	});

})(jQuery);
