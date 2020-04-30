<div class="wrap capsule-admin">
	<div class="capsule-welcome">
		<h1><?php esc_html_e( 'Capsule', 'capsule' ); ?></h1>
		<p><?php esc_html_e( 'The developer\'s code journal', 'capsule' ); ?></p>
	</div>
	<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/hero.jpg" style="width: 100%;" alt="" />

	<div class="capsule-doc-col-left">
		<h3><?php esc_html_e( 'Overview', 'capsule' ); ?></h3>
		<p><?php esc_html_e( 'Many developers keep a scratch document open next to their project code or IDE when they are coding. This document ends up containing miscellaneous artifacts: failed code attempts, data formats, math calculations, etc. Most of the time, this document gets thrown away.', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'Capsule is a replacement for that scratch document. It archives and organizes your development artifacts for future reference.', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'We have intentionally designed Capsule so that you you can stay on the front-end of the app for everything except administrative tasks (adding Capsule Servers, mapping projects, etc.).', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'Can\'t wait to get started?', 'capsule' ); ?> <a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Post away!', 'capsule' ); ?></a></p>

		<h3><?php esc_html_e( 'Projects &amp; Tags', 'capsule' ); ?></h3>
		<p><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/tags.jpg" alt="<?php esc_html_e( 'Projects and Tags', 'capsule' ); ?>" class="capsule-screenshot" /></p>
		<p><?php esc_html_e( 'Capsule stores metadata about your posts to make them easy to filter and find later. You can specify projects and tags for each post, just by entering them into the content of your post. Capsule uses the following syntax to parse projects and tags:', 'capsule' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Projects: @example, @example-project, @example.com', 'capsule' ); ?></li>
			<li><?php esc_html_e( 'Tags: #example, #example-tag, #example.com', 'capsule' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'Simply include these in the content of your post and Capsule will find them and store them as standard WordPress taxonomy terms for your post.', 'capsule' ); ?></p>
		<p>
			<?php esc_html_e( 'When creating projects or tags please be aware that these should explicitly not include a space character in their name. The reasoning behind this is that the parser cannot properly identify a project or tag containing a space character when trying to reference them in your capsule (document).', 'capsule' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Consider the following example:', 'capsule' ); ?>
		</p>
		<ol>
			<li><?php esc_html_e( 'You have a project named “Project X”', 'capsule' ); ?></li>
			<li><?php esc_html_e( 'You are writing a new capsule (document)', 'capsule' ); ?></li>
			<li><?php esc_html_e( 'When trying to reference your project by using the @ symbol, you would write “@Project X”', 'capsule' ); ?></li>
			<li><?php esc_html_e( 'The parser reads this input as follows:', 'capsule' ); ?>
				<ol>
					<li><?php esc_html_e( 'Assign this capsule to the project named “Project”', 'capsule' ); ?></li>
					<li><?php esc_html_e( 'What is left will be added to the content of your capsule, i.e: “ X”', 'capsule' ); ?></li>
				</ol>
			</li>
		</ol>
		<h3><?php esc_html_e( 'Search', 'capsule' ); ?></h3>
		<p><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/search.jpg" alt="<?php esc_html_e( 'Search', 'capsule' ); ?>" class="capsule-screenshot" /></p>
		<p><?php esc_html_e( 'We\'re saving this information to make it useful in the future, so we\'ve got to be able to find it again. Capsule supports both keyword search and filtering by projects, tags, code languages and date range, whew! When using keyword search you can auto-complete projects, tags, and code languages by using their syntax prefix.', 'capsule' ); ?></p>
		<p><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/filter.jpg" alt="<?php esc_html_e( 'Filters', 'capsule' ); ?>" class="capsule-screenshot" /></p>
		<p><?php esc_html_e( 'When filtering, multiple projects/tags/etc. can be selected and are all populated with auto-complete.', 'capsule' ); ?></p>
	</div>
	<div class="capsule-doc-col-right">
		<h3><?php esc_html_e( 'Editing', 'capsule' ); ?></h3>
		<!-- 1050 x 450 -->
		<p><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/editing.jpg" alt="<?php esc_html_e( 'Editing', 'capsule' ); ?>" class="capsule-screenshot" /></p>
		<p><?php esc_html_e( 'Bring up the editor for a post by clicking the Edit icon or double-clicking on the post content.', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'Capsule supports <a href="http://michelf.ca/projects/php-markdown/extra/">Markdown Extra</a> syntax with one minor nuance. Since we are using hashtag notation to create tags for our posts, to create a title using Markdown syntax Capsule requires a space between the &quot;#&quot; and the title text. Example:', 'capsule' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Title: # I am a Title!', 'capsule' ); ?></li>
			<li><?php esc_html_e( 'Tag: #i-am-a-tag', 'capsule' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'When you are editing a post, Capsule auto-saves for you every 10 seconds. There is an &quot;edited&quot; indicator in the upper left corner of the editor next to the Last Saved time. Of course you can also save explicitly at any time using the keyboard shortcut. Capsule also saves when you close the editor.', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'If you want to keep a post easily accessible, you can star it and it will remain at the top of your posts list (until it is un-starred). You can star as many posts as you like.', 'capsule' ); ?></p>

		<h3><?php esc_html_e( 'Code Syntax Highlighting', 'capsule' ); ?></h3>
		<p><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/highlighting.jpg" alt="<?php esc_html_e( 'Syntax Highlighting', 'capsule' ); ?>" class="capsule-screenshot" /></p>
		<p><?php esc_html_e( 'Capsule supports GitHub-style fenced code blocks, and syntax highlighting for code blocks.', 'capsule' ); ?></p>
<pre>```php
// Say hello!
echo 'Hello World';
```</pre>
		<p><?php esc_html_e( 'Additionally, when you use fenced code blocks Capsule saves the code language as metadata for your post.', 'capsule' ); ?></p>

		<h3><?php esc_html_e( 'Keyboard Shortcuts', 'capsule' ); ?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th><?php esc_html_e( 'Mac', 'capsule' ); ?></th>
					<th><?php esc_html_e( 'Windows', 'capsule' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Save', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Command-S', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Control-S', 'capsule ' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Re-center active editor', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Command-Shift-0', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Control-Shift-0', 'capsule' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Close active editor', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Esc', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Esc', 'capsule' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Indent current line', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Command-]', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Control-]', 'capsule' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Outdent current line', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Command-[', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Control-[', 'capsule' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Navigate Home', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Shift-H', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Shift-H', 'capsule' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Create New Post', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Shift-N', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Shift-N', 'capsule' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Set Focus to Search', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Shift-F', 'capsule' ); ?></td>
					<td><?php esc_html_e( 'Shift-F', 'capsule' ); ?></td>
				</tr>
			</tbody>
		</table>
		<h3><?php esc_html_e( 'Icon and Fluid Apps', 'capsule' ); ?></h3>
		<p><?php esc_html_e( 'Capsule works great with apps like Fluid that give you an application for a website. Need an icon for your app? Find it in the <code>wp-content/themes/capule/ui/assets/icon/</code> dir.', 'capsule' ); ?></p>
	</div>
	<br style="clear: both;">
	<hr>
	<div class="capsule-doc-col-left">
		<h3><?php esc_html_e( 'Working With a Team', 'capsule' ); ?></h3>
		<p><?php esc_html_e( 'While Capsule is a tool for an individual developer, it is also a tool for team collaboration. You can connect to one or more Capsule Servers and replicate selected posts to those servers.', 'capsule' ); ?></p>
		<ol>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=capsule-servers' ) ); ?>"><?php esc_html_e( 'Add a Capsule Server', 'capsule' ); ?></a> <?php esc_html_e( 'you must have an account on the Capsule Server)', 'capsule' ); ?>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=capsule-projects' ) ); ?>"><?php esc_html_e( 'Connect to the Server\'s Projects', 'capsule' ); ?></a>
			</li>
		</ol>
		<p><?php esc_html_e( 'Once you map a local project to a project on a Capsule Server, any posts for that project will be automatically replicated to the Capsule Server (if you want, you can send the same local project to multiple Capsule Servers). While you maintain your single development journal, you can connect to multiple Capsule Servers to coordinate with multiple development teams.', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'The Capsule Server allows you to view posts by project, tag, developer, date range, and keyword search.', 'capsule' ); ?></p>
	</div>
	<div class="capsule-doc-col-right">
		<h3><?php esc_html_e( 'Capsule Server', 'capsule' ); ?></h3>
		<p><?php esc_html_e( 'Anyone can set up a <a href="http://crowdfavorite.com/capsule/">Capsule Server</a>. It is free, Open Source and built on WordPress; just like Capsule.', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'Add users to your Capsule Server and they will be able to connect their Capsule journals to your Server.', 'capsule' ); ?></p>
	</div>
	<br style="clear: both;">
	<hr>
	<div class="capsule-doc-col-left">
		<h3><?php esc_html_e( 'Credits', 'capsule' ); ?></h3>
		<p><?php esc_html_e( 'Capsule was conceived and executed by the brilliant and devastatingly good-looking men and women at <a href="http://crowdfavorite.com">Crowd Favorite</a>.', 'capsule' ); ?></p>
		<p><?php esc_html_e( 'Capsule is released under the GPL v2 license.', 'capsule' ); ?></p>
	</div>
	<div class="capsule-doc-col-right">
		<h3>&nbsp;</h3>
		<p><?php esc_html_e( 'In the finest tradition of Open Source, Capsule was built on the shoulders of the following giants:', 'capsule' ); ?></p>
		<?php capsule_credits(); ?>
	</div>
</div>
