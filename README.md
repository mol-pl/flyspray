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
* Gantt export of selected task.
* Mass close for tasks from given version (with given type).
* Form copying to allow cross-domain copy of tasks between Flyspray installations.
* Read-only edit form to allow copying closed tasks.
* Attachments preview (lightbox) -- AFAIK was later added to Flyspray.
* Support for `blockquote` tag in dokuwiki.
* URL/e-mail inserting helper.
* Dokuwiki templates support with syntax resembling MediaWiki (defined in plugin configuration though). 
* Test sections support for configuration.
* Flyspray prefix code customization.
* Database errors logging.
* Anonymous users notifications.
* Special support tags dokuwiki -- text in this tags is hidden anonymous users (remove from both notifications, tasks details and comments).
* Anonymous users lock -- file configuration based lock for your Flyspray installation. With `anon_lock = "1"` users are not able to view any tasks, nor download any files. Only thing they can do is -- using login (authentication), lost password and registration forms.
* Filter by projects when viewing/searching all tasks (in all projects).

Major features
--------------

Applying this features to your FS should be a bit easier as they have their branches. At least for initial changes.

### Task tags ###
**Initial changes**: [feature/tags](https://github.com/mol-pl/flyspray/commits/feature/tags) branch.

This feature is NOT about some dokuwiki tags. This feature allows you to tag tasks (in other words - add some keywords to tasks). You can:
* define global and per-project tags.  
* group tags (e.g. for group "edition" one might have tags "home", "professional", "lite") 
* search for tagged tasks (e.g. find tasks concerning home edition of some product).
* search for tasks that are not tagged (e.g. find tasks that have no "edition" selected).

So tags are kind of like categories, but you can only add one category to a task. You can add multiple tags to a task.

Note. Required database change are in:
* tags._sql.create.sql
* tags._sql.create.assignment.sql

Both are PostgreSQL specific and require some changes to work in MySQL (e.g. `serial` should be `auto_increment`, `character varying` should be `varchar`).
Also, you need to change owner of tables to something else.