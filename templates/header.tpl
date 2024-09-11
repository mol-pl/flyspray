<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{L('locale')}" xml:lang="{L('locale')}">
  <head>
    <title>{$this->_title}</title>

    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />

    <?php if (!empty($conf['cliph']['css_url'])): ?>
	<link media="screen" href="<?=$conf['cliph']['css_url']?>" rel="stylesheet" type="text/css" />
	<?php endif; ?>
	
    <link rel="icon" type="image/png" href="{$this->get_image('favicon')}" />
    <link rel="index" id="indexlink" type="text/html" href="{$baseurl}" />
    <?php foreach ($fs->projects as $project): ?>
    <link rel="section" type="text/html" href="{$baseurl}?project={$project[0]}" />
    <?php endforeach; ?>
    <link media="screen" href="{$this->themeUrl()}theme.css?2016" rel="stylesheet" type="text/css" />
    <link media="screen" href="{$this->themeUrl()}a_mobile.css?1616" rel="stylesheet" type="text/css" />
    <link media="print"  href="{$this->themeUrl()}theme_print.css" rel="stylesheet" type="text/css" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 1.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 2.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss2&amp;project={$proj->id}" />
	<link rel="alternate" type="application/atom+xml" title="Flyspray Atom 0.3 Feed"
	      href="{$baseurl}feed.php?feed_type=atom&amp;project={$proj->id}" />

	<!-- app icons -->
	<link rel="apple-touch-icon" href="{$baseurl}themes/logo_app/logo128.png">

    <script type="text/javascript" src="{$baseurl}javascript/prototype/prototype.js"></script>
	
	<!-- jQuery + UI base -->
	<script src="{$baseurl}javascript/jquery-ui/js/jquery.js"></script>
	<script src="{$baseurl}javascript/jquery-ui/js/jquery-ui.js"></script>
	<link rel="stylesheet" href="{$baseurl}javascript/jquery-ui/css/ui-base/jquery-ui.css" media="screen" />
	<!-- jUI extras -->
	<script src="{$baseurl}javascript/jquery-ui/extra/combobox.js"></script>
	<link rel="stylesheet" href="{$baseurl}javascript/jquery-ui/extra/forms.css" media="screen" />
	<script src="{$baseurl}javascript/jquery-ui/extra/init.js"></script>
	<!-- jQuery extras -->
	<script>
		// avoid conflict with Prototype JavaScript framework
		var $jQuery = jQuery.noConflict();
	</script>
	<script src="{$baseurl}javascript/jquery-ext.js?1311"></script>
    <link href="{$baseurl}javascript/jquery-ext.css?1311" rel="stylesheet" media="screen" />
	
    <script type="text/javascript" src="{$baseurl}javascript/script.aculo.us/scriptaculous.js"></script>
	
    <?php if ('index' == $do || 'details' == $do): ?>
        <script type="text/javascript" src="{$baseurl}javascript/{$do}.js"></script>
    <?php endif; ?>
    <?php if ( $do == 'pm' || $do == 'admin'): ?>
        <script type="text/javascript" src="{$baseurl}javascript/tablecontrol.js?2016"></script>
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

    <script type="text/javascript" src="{$baseurl}javascript/clipboard.js"></script>
	
    <script type="text/javascript" src="{$baseurl}javascript/jsdiff.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jsdiff-history.js"></script>
	
	<!-- Table collapser helpers -->
	<?php if (isset($conf['general']['enable_intro_collapser']) && $conf['general']['enable_intro_collapser']=='1'): ?>
	<script type="text/javascript" src="{$baseurl}javascript/table-collapser.js"></script>
	<script type="text/javascript" src="{$baseurl}javascript/table-collapser-init.js"></script>
	<?php endif; ?>

	<!-- Form copy -->
	<script type="text/javascript" src="{$baseurl}javascript/framemsg.js"></script>
	<script type="text/javascript" src="{$baseurl}javascript/frmcopy.js?1611"></script>
	<script type="text/javascript">
	//<![CDATA[
	oPostman.reMsgSourceBaseUrls = {!$conf['formcopy']['reMsgSourceBaseUrls']};
	//]]>
	</script>

	<script type="text/javascript" src="{$baseurl}javascript/frmextra.js"></script>

    <link media="screen" href="{$baseurl}javascript/frmcopy_dest.css" rel="stylesheet" type="text/css" />
	<!-- LIGHTBOX -->
	<script type="text/javascript" src="{$baseurl}javascript/lightbox/js/lightbox.js"></script>
	<link rel="stylesheet" href="{$baseurl}javascript/lightbox/css/lightbox.css?1641" type="text/css" media="screen" />

    <!--[if IE]>
    <link media="screen" href="{$this->themeUrl()}ie.css" rel="stylesheet" type="text/css" />
    <![endif]-->
    <?php foreach(TextFormatter::get_javascript() as $file): ?>
        <script type="text/javascript" src="{$baseurl}plugins/{$file}"></script>
    <?php endforeach; ?>
	
	<script type="text/javascript" src="{$baseurl}javascript/UserTimezoneHelper.js"></script>

	<script type="text/javascript" src="{$baseurl}javascript/commentsStatus.js"></script>
  </head>
  <body
	data-userTimezone="<?=($user->isAnon() ? '' : $user->infos['time_zone'])?>" 
	data-userRole="<?=($user->perms('is_admin') ? 'admin' : '')?>"
	class="
		<?php if ($proj->id == 0): ?>
			global-project
		<?php endif; ?>
		<?php if ( !empty($do) ): ?>
			page-do-{$do}
		<?php endif; ?>
		<?php if ( strlen(Req::val('area', '')) > 0 ): ?>
			page-area-{Req::val('area')}
		<?php endif; ?>
	"
    onload="
	    perms = new Perms('permissions');
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
    "
  >

  <div id="container">
    <!-- Remove this to remove the logo -->
    <?php if (!empty($conf['cliph']['include_path'])): ?>
      <?php if (!empty($conf['cliph']['tab_key'])): ?>
	    <?php $cliph_mn_key=$conf['cliph']['tab_key']; ?>
      <?php else: ?>
	    <?php $cliph_mn=$conf['cliph']['tab_number']; ?>
      <?php endif; ?>
	  <?php @include($conf['cliph']['include_path']); ?>
    <?php endif; ?>

    <?php $this->display('links.tpl'); ?>

    <?php if (isset($_SESSION['SUCCESS']) && isset($_SESSION['ERROR'])): ?>
    <div id="mixedbar" class="mixed bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['SUCCESS']}<br />{$_SESSION['ERROR']}</div></div>
    <?php elseif (isset($_SESSION['ERROR'])): ?>
    <div id="errorbar" class="error bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['ERROR']}</div></div>
    <?php elseif (isset($_SESSION['SUCCESS'])): ?>
    <div id="successbar" class="success bar" onclick="this.style.display='none'"><div class="errpadding">{$_SESSION['SUCCESS']}</div></div>
    <?php endif; ?>

    <div id="content">
    <?php if (!$anon_lock_active): ?>
    
      <div id="showtask">
        <form action="{$baseurl}index.php" method="get">
          <div>
            <button type="submit">{L('showtask')} #</button>
            <input id="taskid" name="show_task" class="text" type="text" size="10" accesskey="t" />
          </div>
        </form>
      </div>

      <div class="clear"></div>
      <?php $show_message = array('details', 'index', 'newtask', 'reports', 'depends');
            $actions = explode('.', Req::val('action'));
            if ($proj->prefs['intro_message'] && (in_array($do, $show_message) || in_array(reset($actions), $show_message))): ?>
      <div style="height:0px" title="{L('hideshowprojectintro')}"><img
		alt="{L('hideshowprojectintro')}"
		class="DoNotPrint"
		id="intromessage_hider"
		style="float:right; cursor:pointer;"
		src="{$this->themeUrl()}/edit_remove.png"
		onclick="savedHideShow.toogle('intromessage')" /></div>
	  <div id="intromessage">{!TextFormatter::render($proj->prefs['intro_message'], false, 'msg', $proj->id,
                               ($proj->prefs['last_updated'] < $proj->prefs['cache_update']) ? $proj->prefs['pm_instructions'] : '')}</div>
	  <script>savedHideShow.quickSetUp('intromessage')</script>
      <?php if (isset($conf['general']['enable_intro_collapser']) && $conf['general']['enable_intro_collapser']=='1'): ?>
        <script>initCollapsers('#intromessage')</script>
      <?php endif; ?>
    <?php endif; ?>
    
    <?php endif; ?>
