## Version 1.4.0
 - added EDD licensing/updating

## Version 1.3.0
- fixed warning when editing posts in the backend
- replace icon
- rewrite URLs to SSL variants(https)
- PSR-12 code style enforce
- fixed a bug where projects and tags were being registered from codeblocks or docblocks
- fixed a bug where `` $` `` or `$'` broke the codeblock interface
- Added "open in new" icon to webfont files. Updated how icons are added. Improved accessibility of main menu and articles utility menu items.
- update issue templates to add labels
- update php-markdown lib to ignore parsing of heading tags without a space eg: #test
- added phpcs ruleset, license file, security policy, code of conduct, contributing guidelines, changelog file, templates for pull requests and issues, support information
- Replaced wp_redirect with wp_safe_redirect
- Replaced wp_remote_* with wp_safe_remote_*

## Version 1.2

- remove the git submodules structure
- update libraries, code cleanup
- fix various PHP notices

## Version 1.1.1

- include jQuery Hotkeys in the optimized.js file

## Version 1.1

- add keyboard shortcuts for Home, New Post, focus to Search field
- background queue for sending posts to Capsule Server (saves are now non-blocking UI actions, also supports offline usage)
- add favicon and icon for use with Fluid app
- add default styling for tables
- show which servers a post has been pushed to, with link to post on server
- allow mapping of multiple local projects to a single server project
- fix issues with syntax highlighting markdown emphasis in editor versus display
- fix double-encoding of ampersands on display
- fix fenced code blocks not being entity-encoded in some cases
- don't allow the same Capsule Server to be added twice
- fix auth check (prevent direct access to posts)
- remove persistent horizontal scrollbar from code blocks (now only appears when needed)
- add hooks in Capsule's controllers for extensibility (capsule_controller_action_get, capsule_controller_action_post)
- add filter to allow overriding of Capsule's access restrictions (capsule_gatekeeper_enabled)
- add before and after actions to post menu (capsule_post_menu_before, capsule_post_menu_after)
- add before and after actions to main nav (capsule_main_nav_before, capsule_main_nav_after)
- update WP permalinks when pretty premalinks are detected and our custom taxonomies are not present
- explicitly remove post formats support
- fix various PHP notices

## Version 1.0

- initial release
