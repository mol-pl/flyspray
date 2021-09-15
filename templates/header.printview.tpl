<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{L('locale')}" xml:lang="{L('locale')}">
  <head>
    <title>{$this->_title}</title>

    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Style-Type" content="text/css" />

    <link rel="icon" type="image/png" href="{$this->get_image('favicon')}" />
    <link rel="index" id="indexlink" type="text/html" href="{$baseurl}" />
    <?php foreach ($fs->projects as $project): ?>
    <link rel="section" type="text/html" href="{$baseurl}?project={$project[0]}" />
    <?php endforeach; ?>
    <link href="{$this->themeUrl()}theme_print.css" rel="stylesheet" type="text/css" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 1.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 2.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss2&amp;project={$proj->id}" />
	<link rel="alternate" type="application/atom+xml" title="Flyspray Atom 0.3 Feed"
	      href="{$baseurl}feed.php?feed_type=atom&amp;project={$proj->id}" />

    <script type="text/javascript" src="{$baseurl}javascript/prototype/prototype.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/script.aculo.us/scriptaculous.js"></script>
    <?php if ('index' == $do || 'details' == $do): ?>
        <script type="text/javascript" src="{$baseurl}javascript/{$do}.js"></script>
    <?php endif; ?>
    <?php if ( $do == 'pm' || $do == 'admin'): ?>
        <script type="text/javascript" src="{$baseurl}javascript/tablecontrol.js"></script>
    <?php endif; ?>
	<script type="text/javascript">
	//<![CDATA[
	var jsglobal_theme_url = '{$this->themeUrl()}';
	var jsglobal_base_url = '{$baseurl}';
	//]]>
	</script>
    <script type="text/javascript" src="{$baseurl}javascript/tabs.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/sel_t.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/sftJSmsg.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/functions.js?2224"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar-setup_stripped.js"> </script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/lang/calendar-{substr(L('locale'), 0, 2)}.js"></script>

	<!-- Form copy -->
	<script type="text/javascript" src="{$baseurl}/javascript/framemsg.js"></script>
	<script type="text/javascript" src="{$baseurl}/javascript/frmcopy.js?2224"></script>
	<script type="text/javascript">
	//<![CDATA[
	oPostman.reMsgSourceBaseUrls = {!$conf['formcopy']['reMsgSourceBaseUrls']};
	//]]>
	</script>

	<script type="text/javascript" src="{$baseurl}/javascript/frmextra.js"></script>

	<!-- LIGHTBOX -->
	<script type="text/javascript" src="{$baseurl}javascript/lightbox/js/lightbox.js"></script>

    <?php foreach(TextFormatter::get_javascript() as $file): ?>
        <script type="text/javascript" src="{$baseurl}plugins/{$file}"></script>
    <?php endforeach; ?>
  </head>

  <body onload="perms = new Perms('permissions');
		if (document.getElementById('mixedbar'))
		{
			window.setTimeout('Effect.Fade(\'mixedbar\', &lbrace;duration:.3&rbrace;)', 10000);
		}
		else if (document.getElementById('successbar'))
		{
			window.setTimeout('Effect.Fade(\'successbar\', &lbrace;duration:.3&rbrace;)', 8000);
		}
		else if (document.getElementById('errorbar'))
		{
			window.setTimeout('Effect.Fade(\'errorbar\', &lbrace;duration:.3&rbrace;)', 8000);
		}
		">

  <div id="container">
    <div id="content">
      <div class="clear"></div>
