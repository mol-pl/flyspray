Flyspray
========

[Flyspray](http://www.flyspray.org/) is a lightweight, browser based bug tracking software. It was originally developed by Tony Collins in 2003.

It is licensed under the terms of GNU GPL version 2.1. See [LICENSE text](/LICENSE) for more details.

About this fork
---------------

This is a highly customized [Flyspray](http://www.flyspray.org/) fork used internally by MOL. This means you might need to take some extra security measures to use it publicly over the Internet.

There are a lot of customization from the original and we are not in sync with the current Flyspray (specifically we are not using the new theme). There were attempts to make our modifications configurable and generic, but there wasn't always time to perfect all changes. Specifically some changes are PostgreSQL only.

Highlights of differences from original:

* PostgreSQL fixes.
* Gantt export of selected task.
* Mass close for tasks from given version (with given type).
* Form copying to allow cross-domian copy of tasks between Flyspray installations.
* Read-only edit form to allow copying closed tasks.
* Attachments preview (lightbox) -- AFAIK was later added to Flyspray.
* Support for `blockquote` tag.
* URL/e-mail inserting helper.
* Dokuwiki templates support with syntax resembling MediaWiki (defined in plugin configuration though). 
* Test sections support for configuration.
* Flyspray prefix code customization.
* Database errors logging.
* Anonymous users notifications.
* Removing support tags from both notifications and task view for anonymous users.
