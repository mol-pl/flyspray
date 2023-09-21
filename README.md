Flyspray
========

[Flyspray](http://www.flyspray.org/) is a lightweight, browser based bug tracking software. It was originally developed by Tony Collins in 2003.

It is licensed under the terms of GNU GPL version 2.1. See [LICENSE text](/LICENSE) for more details.

About this fork
---------------

This is a highly customized [Flyspray](http://www.flyspray.org/) fork used internally by MOL. This means you might need to take some extra security measures to use it publicly over the Internet.

There are a lot of customization from the original and we are not in sync with the current Flyspray (specifically we are not using the new theme). There were attempts to make our modifications configurable and generic, but there wasn't always time to perfect all changes. Specifically some changes are PostgreSQL only.

Highlights of differences from original:

* PostgreSQL fixes (also for PG SQL 8.4 and above).
* PHP7 and PHP8 compatibility.
* Gantt export of selected task.
* Mass close for tasks from given version (with given type).
* Form copying to allow cross-domain copy of tasks between Flyspray installations.
* Read-only edit form to allow copying closed tasks.
* Attachments preview (lightbox) — AFAIK this was later added to Flyspray.
* Support for `blockquote` tag in dokuwiki.
* Editor tool: helper for inserting URLs (and e-mails).
* Support for templates in Dokuwiki with template syntax resembling MediaWiki (templates defined in plugin configuration though, not like in MW). 
* Test sections support for configuration.
* Flyspray prefix code customization.
* Database errors logging.
* Anonymous users notifications.
* Special support tags in dokuwiki — text in this tags is hidden from anonymous users (removed from notifications, tasks details and comments).
* Anonymous users lock — a configuration file based lock for your Flyspray installation (blocks anonymous access). With `anon_lock = "1"`, users are not able to view any tasks or download any files. The only thing they can do is to use login form (authentication), lost password form, and registration form.
* Filter by projects when viewing/searching all tasks (in all projects).
* Remove old attachments (to be run via cron).

Major features
--------------

Applying this features to your FS should be a bit easier as they have their branches. At least for initial changes.

### Done status for comments ###
**Initial changes**: [feature/done-comments](https://github.com/mol-pl/flyspray/commits/feature/done-comments) branch.

This feature allows marking some comments as done/irrelevant.
* Comments status is changed rapidly in background (AJAX) call.
* Done comments are marked with green border (can be changed in `theme.css`).
* When the task is initially rendered done-comments will be collapsed.
* Collapsed comments can be expanded both individually and all at once.

Note. Required database change are in:
* `.workdir/commentStatus/db_changes.sql`

### Task tags ###
**Initial changes**: [feature/tags](https://github.com/mol-pl/flyspray/commits/feature/tags) branch.

This feature is NOT about some dokuwiki tags. This feature allows you to tag tasks (in other words - add some keywords to tasks). You can:
* define global and per-project tags.  
* group tags (e.g. for group "edition" one might have tags "home", "professional", "lite") 
* search for tagged tasks (e.g. find tasks concerning home edition of some product).
* search for tasks that are not tagged (e.g. find tasks that have no "edition" selected).

So tags are kind of like categories, but you can only add one category to a task. You can add multiple tags to a task.

Note. Required database change are in `.workdir/tags/` in files:
* `tags._sql.create.sql`
* `tags._sql.create.assignment.sql`

Both are PostgreSQL specific and require some changes to work in MySQL (e.g. `serial` should be `auto_increment`, `character varying` should be `varchar`).
Also, you need to change owner of tables to something else.

### PHP7+ compatibility ###
**Initial changes**: [feature/php7](https://github.com/mol-pl/flyspray/commits/feature/php7) branch.

Although initial goal was PHP 7.4, some changes were future proof and latests version should work with PHP 8.0 and probably 8.1 too.

This change also included Swift mailer update. It was not feasiable to make old version compatible with PHP7. So Swift v5.4 was added as `swift-mailer-5`. We use a custom PHP Mailer more, so not sure if Swift fully works with FS.

Some changes were done outside of the feature branch after later testing. Especially for later PHP versions compatibility.
