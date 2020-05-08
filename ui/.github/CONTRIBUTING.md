# Contributing to WP Capsule

Looking to contribute something to WP Capsule UI? **Here's how you can help.**

Please take a moment to review this document in order to make the contribution
process easy and effective for everyone involved.

Following these guidelines helps to communicate that you respect the time of
the developers managing and developing this open source project. In return,
they should reciprocate that respect in addressing your issue or assessing
patches and features.


## Using the issue tracker

The [issue tracker](https://github.com/crowdfavorite/wp-capsule-ui/issues) is
the preferred channel for [bug reports](#bug-reports), [features requests](#feature-requests)
and [submitting pull requests](#pull-requests), but please respect the following
restrictions:

* Please **do not** derail or troll issues. Keep the discussion on topic and
  respect the opinions of others.

* Please **do not** post comments consisting solely of "+1" or ":thumbsup:".
  Use [GitHub's "reactions" feature](https://blog.github.com/2016-03-10-add-reactions-to-pull-requests-issues-and-comments/)
  instead. We reserve the right to delete comments which violate this rule.

* Please **do not** open issues regarding [WP Capsule](https://github.com/crowdfavorite/wp-capsule/) or [WP Capsule Server](https://github.com/crowdfavorite/wp-capsule-server/).


## Issues and labels

Our bug tracker utilizes several labels to help organize and identify issues. Here's what they represent and how we use them:

- `good first issue` - GitHub will help potential first-time contributors discover issues that have this label.
- `bug` - Issues that have been confirmed with a reduced test case and identify a bug in WP Capsule UI.
- `css` - Issues stemming from our compiled CSS or source Sass files.
- `docs` - Issues for improving or updating our documentation.
- `feature` - Issues asking for a new feature to be added, or an existing one to be extended or modified.
- `help wanted` - Issues we need or would love help from the community to resolve.
- `js` - Issues stemming from our compiled or source JavaScript files.
- `meta` - Issues with the project itself or our GitHub repository.
- `duplicate` - This issue or pull request already exists.
- `question` - General support/questions issue bucket.

For a complete look at our labels, see the [project labels page](https://github.com/crowdfavorite/wp-capsule-ui/labels).


## Bug reports

A bug is a _demonstrable problem_ that is caused by the code in the repository.
Good bug reports are extremely helpful, so thanks!

Guidelines for bug reports:

0. **Validate your code** to ensure your
   problem isn't caused by a simple error in your own code.

1. **Use the GitHub issue search** &mdash; check if the issue has already been
   reported.

2. **Check if the issue has been fixed** &mdash; try to reproduce it using the
   latest `master` or `develop` branch in the repository.

3. **Isolate the problem** &mdash; ideally create or record a live example.


A good bug report shouldn't leave others needing to chase you up for more
information. Please try to be as detailed as possible in your report. What is
your environment(installed plugins, WordPress version, WP Capsule Server version)? What steps will reproduce the issue? What browser(s) and serverstack
experience the problem? What
would you expect to be the outcome? All these details will help people to fix
any potential bugs.

Example:

> Short and descriptive example bug report title
>
> A summary of the issue and the browser/serverstack/WordPress environment in which it occurs. If
> suitable, include the steps required to reproduce the bug.
>
> 1. This is the first step
> 2. This is the second step
> 3. Further steps, etc.
>
>
> Any other information you want to share that is relevant to the issue being
> reported. This might include the lines of code that you have identified as
> causing the bug, and potential solutions (and your opinions on their
> merits).

## Feature requests

Feature requests are welcome. But take a moment to find out whether your idea
fits with the scope and aims of the project. It's up to *you* to make a strong
case to convince the project's developers of the merits of this feature. Please
provide as much detail and context as possible.


## Pull requests

Good pull requests—patches, improvements, new features—are a fantastic
help. They should remain focused in scope and avoid containing unrelated
commits.

**Please ask first** before embarking on any significant pull request (e.g.
implementing features, refactoring code), otherwise you risk spending
a lot of time working on something that the project's developers
might not want to merge into the project.

Please adhere to the [coding guidelines](#code-guidelines) used throughout the
project (indentation, accurate comments, etc.) and any other requirements
(such as test coverage).

**Do not edit compiled assets or vendor dependencies directly!**
Those files are either automatically generated in the case of js or css assets
or ignored from the repo codebase in the case of dependencies(`vendor` AND `ui` folder).
You should edit the source files for assets or extend vendor dependencies.

Adhering to the following process is the best way to get your work
included in the project:

1. [Fork](https://help.github.com/articles/fork-a-repo/) the project, clone your fork,
   and configure the remotes:

   ```bash
   # Clone your fork of the repo into the current directory
   git clone https://github.com/<your-username>/wp-capsule-ui.git
   # Navigate to the newly cloned directory
   cd wp-capsule
   # Assign the original repo to a remote called "upstream"
   git remote add upstream https://github.com/crowdfavorite/wp-capsule-ui.git
   ```

2. If you cloned a while ago, get the latest changes from upstream:

   ```bash
   git checkout develop
   git pull upstream develop
   ```

3. Create a new topic branch (off the main project `develop` branch) to
   contain your feature, change, or fix:

   ```bash
   git checkout -b <topic-branch-name>
   ```

4. Commit your changes in logical chunks. Please adhere to these [git commit
   message guidelines](https://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)
   or your code is unlikely be merged into the main project. Use Git's
   [interactive rebase](https://help.github.com/articles/about-git-rebase/)
   feature to tidy up your commits before making them public.

5. Locally merge (or rebase) the upstream `develop` branch into your topic branch:

   ```bash
   git pull [--rebase] upstream develop
   ```

6. Push your topic branch up to your fork:

   ```bash
   git push origin <topic-branch-name>
   ```

7. [Open a Pull Request](https://help.github.com/articles/about-pull-requests/)
    with a clear title and description against the `develop` branch.

**IMPORTANT**: By submitting a patch, you agree to allow the project owners to
license your work under the terms of the [GPLv2 License](../LICENSE).


## Code guidelines

### PHP
WP Capsule repos follow an extended [PSR12 ruleset](../phpcs.xml) that includes WordPress Security sniffs with the added change of preffering tabs instead of spaces for indentation.
**You code should adhere to this ruleset.**

### CSS
- Classes should be named following the [BEM conventions](http://getbem.com/naming/).
- Your compiled CSS should nest no more than 4 levels deep, preferably no more than 3 levels.
- Follow [ITCSS](https://www.creativebloq.com/web-design/manage-large-css-projects-itcss-101517528) principles for organizing styles.

### JS
We adhere to the [AirBnB JavaScript Style Guide](https://github.com/airbnb/javascript) for project JavaScript code. [All rules apply](https://github.com/airbnb/javascript/blob/master/README.md#table-of-contents) with the following exceptions:
- Ignore rules related to IE8 support. We don't generally do it, and sometimes it can be useful to use a normal property overridden into a different one (such as a CSS styles object to pass to jQuery).
- Put all functions at the end of scope, possibly after a return. Doing this comes from the Angular guide, and makes it easier to follow a narrative flow of the code. We see the behaviors, then can quickly dig into the implementation if we wish to. (Credit to [John Papa's Angular Style guide](https://github.com/johnpapa/angular-styleguide) for this idea)

### Checking coding style
Run `phpcs --standard=phpcs.xml` before committing to ensure your changes follow our coding standards.

## License
By contributing your code, you agree to license your contribution under the [GPLv2 License](../LICENSE).