Capsule
=======

The developer's code journal

![](docs/hero.jpg)

## Overview

Many developers keep a scratch document open next to their project code or IDE when they are coding. This document ends up containing miscellaneous artifacts: failed code attempts, data formats, math calculations, etc. Most of the time, this document gets thrown away.

Capsule is a replacement for that scratch document. It archives and organizes your development artifacts for future reference.

We have intentionally designed Capsule so that you you can stay on the front-end of the app for everything except administrative tasks (adding Capsule Servers, mapping projects, etc.).

## Projects & Tags

![Projects and Tags](docs/tags.jpg)

Capsule stores metadata about your posts to make them easy to filter and find later. You can specify projects and tags for each post, just by entering them into the content of your post. Capsule uses the following syntax to parse projects and tags:

*   Projects: `@example`, `@example-project`, `@example.com`
*   Tags: `#example`, `#example-tag`, `#example.com`

Simply include these in the content of your post and Capsule will find them and store them as standard WordPress taxonomy terms for your post.

When creating projects or tags please be aware that these should explicitly not include a space character in their name. The reasoning behind this is that the parser cannot properly identify a project or tag containing a space character when trying to reference them in your capsule (document).

Consider the following example:

1.  You have a project named “Project X”
2.  You are writing a new capsule (document)
3.  When trying to reference your project by using the @ symbol, you would write “@Project X”
4.  The parser reads this input as follows:
    1.  Assign this capsule to the project named “Project”
    2.  What is left will be added to the content of your capsule, i.e: “ X”

## Search

![Search](docs/search.jpg)

We're saving this information to make it useful in the future, so we've got to be able to find it again. Capsule supports both keyword search and filtering by projects, tags, code languages and date range, whew! When using keyword search you can auto-complete projects, tags, and code languages by using their syntax prefix.

![Filters](docs/filter.jpg)

When filtering, multiple projects/tags/etc. can be selected and are all populated with auto-complete.

### Editing

![Editing](docs/editing.jpg)

Bring up the editor for a post by clicking the Edit icon or double-clicking on the post content.

Capsule supports Markdown Extra syntax with one minor nuance. Since we are using hashtag notation to create tags for our posts, to create a title using Markdown syntax Capsule requires a space between the "#" and the title text. Example:

*   Title: # I am a Title!
*   Tag: #i-am-a-tag

When you are editing a post, Capsule auto-saves for you every 10 seconds. There is an "edited" indicator in the upper left corner of the editor next to the Last Saved time. Of course you can also save explicitly at any time using the keyboard shortcut. Capsule also saves when you close the editor.

If you want to keep a post easily accessible, you can star it and it will remain at the top of your posts list (until it is un-starred). You can star as many posts as you like.

### Code Syntax Highlighting

![Syntax Highlighting](docs/highlighting.jpg)

Capsule supports GitHub-style fenced code blocks, and syntax highlighting for code blocks.
````
```php
// Say hello!
echo 'Hello World';
```
````


Additionally, when you use fenced code blocks Capsule saves the code language as metadata for your post.

### Keyboard Shortcuts

| Action | Keybind for Mac | Keybind for Windows |
| -------- | ------------------ | --------------------- |
| Save | `Command-S` | `Control-S` |
| Re-center active editor | `Command-Shift-0` | `Control-Shift-0` |
| Close active editor | `Esc` | `Esc` |
| Indent current line | `Command-\]` | `Control-\]` |
| Outdent current line | `Command-\[` | `Control-\[` |
| Navigate Home | `Shift-H` | `Shift-H` |
| Create New Post | `Shift-N` | `Shift-N` |
| Set Focus to Search | `Shift-F` | `Shift-F` |

### Icon and Fluid Apps

Capsule works great with apps like Fluid that give you an application for a website. Need an icon for your app? Find it in the `wp-content/themes/capule/ui/assets/icon/` directory.



* * *

## Working With a Team

While Capsule is a tool for an individual developer, it is also a tool for team collaboration. You can connect to one or more Capsule Servers and replicate selected posts to those servers.

1.  Add a Capsule Server (you must have an account on the Capsule Server)
2.  Connect to the Server's Projects

Once you map a local project to a project on a Capsule Server, any posts for that project will be automatically replicated to the Capsule Server (if you want, you can send the same local project to multiple Capsule Servers). While you maintain your single development journal, you can connect to multiple Capsule Servers to coordinate with multiple development teams.

The Capsule Server allows you to view posts by project, tag, developer, date range, and keyword search.

## Capsule Server

Anyone can set up a Capsule Server. It is free, Open Source and built on WordPress; just like Capsule.

Add users to your Capsule Server and they will be able to connect their Capsule journals to your Server.

## Using dnsmasq

Many local development environments take advantage of dnsmasq to have pretty links for their local projects. However, please be aware that there is a common issue affecting cURL usage on environments with dnsmasq running as a service.

As WP Capsule uses cURL to sync capsules, you might find that your local instance is not able to properly send information over to your defined WP Capsule Server.

To check if your local domain properly resolves, use the terminal command `dig`, followed by your local URL (eg: `dig mywebsite.localhost`). In the response section of the output you should see an `A` record pointing to `127.0.0.1`.