<?php

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class Tpl
{
    var $_uses  = array();
    var $_vars  = array();
    var $_theme = '';
    var $_tpls  = array();
    var $_title = "";

    function uses()
    {
        $args = func_get_args();
        $this->_uses = array_merge($this->_uses, $args);
    }

    function assign($arg0 = null, $arg1 = null)
    {
        if (is_string($arg0)) {
            $this->_vars[$arg0] = $arg1;
        }elseif (is_array($arg0)) {
            $this->_vars += $arg0;
        }elseif (is_object($arg0)) {
            $this->_vars += get_object_vars($arg0);
        }
    }

    function setTheme($theme)
    {
        // Check available themes
        $themes = Flyspray::listThemes();
        if (in_array($theme, $themes)) {
            $this->_theme = $theme.'/';
        } else {
            $this->_theme = $themes[0].'/';
        }
    }

    function setTitle($title)
    {
        $this->_title = $title;
    }

    function themeUrl()
    {
        return sprintf('%s/themes/%s', $GLOBALS['baseurl'], $this->_theme);
    }

    function compile(&$item)
    {
        if (strncmp($item, '<?', 2)) {
            $item = preg_replace( '/{!([^\s&][^{}]*)}(\n?)/', '<?php echo \1; ?>\2\2', $item);
            // For lang strings in Javascript
            $item = preg_replace( '/{#([^\s&][^{}]*)}(\n?)/',
                    '<?php echo Filters::noXSS(utf8_str_replace("\'", "\\\'", \1)); ?>\2\2', $item);
            $item = preg_replace( '/{([^\s&][^{}]*)}(\n?)/',
                    '<?php echo Filters::noXSS(\1); ?>\2\2', $item);
        }
    }
    // {{{ Display page
    function pushTpl($_tpl)
    {
        $this->_tpls[] = $_tpl;
    }

    function catch_start()
    {
        ob_start();
    }

    function catch_end()
    {
        $this->_tpls[] = array(ob_get_contents());
        ob_end_clean();
    }

    function display($_tpl, $_arg0 = null, $_arg1 = null)
    {
        // if only plain text
        if (is_array($_tpl) && count($tpl)) {
            echo $_tpl[0];
            return;
        }

        // theming part
        if (is_readable(BASEDIR . '/themes/' . $this->_theme.$_tpl)) {
            $_tpl_data = file_get_contents(BASEDIR . '/themes/' . $this->_theme.$_tpl);
        } else {
            $_tpl_data = file_get_contents(BASEDIR . '/templates/'.$_tpl);
        }

        // compilation part
        $_tpl_data = preg_split('!(<\?php.*\?>)!sU', $_tpl_data, -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        array_walk($_tpl_data, array(&$this, 'compile'));
        $_tpl_data = join('', $_tpl_data);

        $from = array('&lbrace;','&rbrace;');
        $to = array('{','}');
        $_tpl_data = str_replace($from, $to, $_tpl_data);

        // variables part
        if (!is_null($_arg0)) {
            $this->assign($_arg0, $_arg1);
        }

        foreach ($this->_uses as $_var) {
            global $$_var;
        }

        extract($this->_vars, EXTR_REFS|EXTR_SKIP);

        // XXX: if you find a clever way to remove the evil here,
        // send us a patch, thanks.. we don't want this..really ;)

        eval( '?>'. $_tpl_data );
    } // }}}

    function render()
    {
        while (count($this->_tpls)) {
            $this->display(array_shift($this->_tpls));
        }

    }

    function fetch($tpl, $arg0 = null, $arg1 = null)
    {
        ob_start();
        $this->display($tpl, $arg0, $arg1);
        return ob_get_clean();
    }
}

class FSTpl extends Tpl
{
    var $_uses = array('fs', 'conf', 'baseurl', 'language', 'proj', 'user');

    function get_image($name, $base = true)
	{
        global $proj, $baseurl;
        $pathinfo = pathinfo($name);
        $link = sprintf('themes/%s/', $proj->prefs['theme_style']);
        if ($pathinfo['dirname'] != '.') {
            $link .= $pathinfo['dirname'] . '/';
            $name = $pathinfo['basename'];
        }

        $extensions = array('.png', '.gif', '.jpg', '.ico');

        foreach ($extensions as $ext) {
            if (is_file(BASEDIR . '/' . $link . $name . $ext)) {
                return ($base) ? ($baseurl . $link . $name . $ext) : ($link . $name . $ext);
            }
        }
        return '';
    }

}

// {{{ costful templating functions, TODO: optimize them

function tpl_tasklink($task, $text = null, $strict = false, $attrs = array(), $title = array('status','summary','percent_complete'))
{
    global $user;

    $params = array();

    if (!is_array($task) || !isset($task['status_name'])) {
        $td_id = (is_array($task) && isset($task['task_id'])) ? $task['task_id'] : $task;
        $task = Flyspray::GetTaskDetails($td_id, true);
    }

    if ($strict === true && (!is_object($user) || !$user->can_view_task($task))) {
        return '';
    }

    if (is_object($user) && $user->can_view_task($task)) {
        $summary = utf8_substr($task['item_summary'], 0, 150);
    } else {
        $summary = L('taskmadeprivate');
    }

    if (is_null($text)) {
        $text = sprintf(FS_PREFIX_CODE.'#%d - %s', $task['task_id'], Filters::noXSS($summary));
    } elseif(is_string($text)) {
        $text = htmlspecialchars(utf8_substr($text, 0, 64), ENT_QUOTES, 'utf-8');
    } else {
        //we can't handle non-string stuff here.
        return '';
    }

    if (!$task['task_id']) {
        return $text;
    }

    $title_text = array();

    foreach($title as $info)
    {
        switch($info)
        {
            case 'status':
                if ($task['is_closed']) {
                    $title_text[] = $task['resolution_name'];
                    $attrs['class'] = 'closedtasklink';
                } else {
                    $title_text[] = $task['status_name'];
                }
                break;

            case 'summary':
                $title_text[] = $summary;
                break;

            case 'assignedto':
                if (isset($task['assigned_to_name']) ) {
                    if (is_array($task['assigned_to_name'])) {
                        $title_text[] = implode(', ', $task['assigned_to_name']);
                    } else {
                        $title_text[] = $task['assigned_to_name'];
                    }
                }
                break;

            case 'percent_complete':
                    $title_text[] = $task['percent_complete'].'%';
                break;

            case 'category':
                if ($task['product_category']) {
                    if (!isset($task['category_name'])) {
                        $task = Flyspray::GetTaskDetails($task['task_id'], true);
                    }
                    $title_text[] = $task['category_name'];
                }
                break;

            // ... more options if necessary
        }
    }

    $title_text = implode(' | ', $title_text);

    // to store search options
    $params = $_GET;
    unset($params['do'], $params['action'], $params['task_id'], $params['switch']);

    $url = htmlspecialchars(CreateURL('details', $task['task_id'],  null, $params), ENT_QUOTES, 'utf-8');
    $title_text = htmlspecialchars($title_text, ENT_QUOTES, 'utf-8');
    $link  = sprintf('<a href="%s" title="%s" %s>%s</a>',$url, $title_text, join_attrs($attrs), $text);

    if ($task['is_closed']) {
        $link = '<del>&#160;' . $link . '&#160;</del>';
    }
    return $link;
}

function tpl_userlink($uid)
{
    global $db, $user;

    static $cache = array();

    if (is_array($uid)) {
        list($uid, $uname, $rname) = $uid;
    } elseif (empty($cache[$uid])) {
        $sql = $db->Query('SELECT user_name, real_name FROM {users} WHERE user_id = ?',
                           array(intval($uid)));
        if ($sql && $db->countRows($sql)) {
            list($uname, $rname) = $db->fetchRow($sql);
        }
    }

    if (isset($uname)) {
        $url = CreateURL(($user->perms('is_admin')) ? 'edituser' : 'user', $uid);
        $cache[$uid] = vsprintf('<a href="%s">%s (%s)</a>', array_map(array('Filters', 'noXSS'), array($url, $rname, $uname)));
    } elseif (empty($cache[$uid])) {
        $cache[$uid] = eL('anonymous');
    }

    return $cache[$uid];
}

/**
 * Translate tag id to it's data (tag_id, tag_name, tag_group).
 *
 * @global Database $db
 * @global Project $proj
 * @staticvar array $cache
 * @param integer $tag_id
 * @return array (tag_id, tag_name, tag_group) or an empty array if tag ID was not found.
 */
function tpl_tagdata($tag_id)
{
	global $db, $proj;

	static $cache = array();	// note - this cache works only within a single request

	$tag_id = intval($tag_id);

	// setup tags cache
	if (empty($cache)) {
		$tags = $proj->listTags();
		foreach ($tags as $tag) {
			$cache[intval($tag['tag_id'])] = $tag;
		}
	}

	// still unknown? weird...
	if (empty($cache[$tag_id])) {
		return array();
	}
	return $cache[$tag_id];
}

/**
 * Translate tag id to its name.
 *
 * @global Database $db
 * @global Project $proj
 * @staticvar array $cache
 * @param integer $tag_id
 * @return string
 */
function tpl_tagname($tag_id)
{
	$tag_id = intval($tag_id);
	
	$tag = tpl_tagdata($tag_id);

	// unknown? weird...
	if (empty($tag)) {
		return "NN [$tag_id]";
	}
	return $tag['tag_name'];
}

function tpl_fast_tasklink($arr)
{
    return tpl_tasklink($arr[1], $arr[0]);
}

// Nux-start: parse summary to add a link
function tpl_summarylink($item_summary)
{
    global $conf;
	
	$item_summary = htmlspecialchars($item_summary);
	
	if (!empty($conf['mol']) && !empty($conf['mol']['klienci_base_url']))
	{
		$url_rep = $conf['mol']['klienci_base_url']."szukaj.php?nr=$1";
		$item_summary = preg_replace ("#\[([a-z]*[0-9]{3,}.*?)\]#", "[<a href='$url_rep'>$1</a>]", $item_summary);
		$item_summary = preg_replace ("#lic\.\s+([a-z]*[0-9]+)\s*$#", "lic. <a href='$url_rep'>$1</a>", $item_summary);
	}

    return $item_summary;
}
// Nux-end
// Nux-start: parse summary to add a client details frame
function tpl_summary_client_details_frame($item_summary)
{
    global $conf;
	
	$frame_url = "";
	
	$item_summary = htmlspecialchars($item_summary);
	
	if (!empty($conf['mol']) && !empty($conf['mol']['klienci_frame_base_url']))
	{
		$licenses = array();
		if (preg_match_all("#\[([a-z]*[0-9]{3,}.*?)\]#", $item_summary, $matches)) {
			$licenses = array_merge($licenses, $matches[1]);
		}
		if (preg_match_all("#lic\.\s+([a-z]*[0-9]+)\s*$#", $item_summary, $matches)) {
			$licenses = array_merge($licenses, $matches[1]);
		}
		// fliter out special license
		$licenses = array_filter($licenses, function($l) {
			return intval($l) != 1;
		});
		
		if (!empty($licenses)) {
			$frame_url = $conf['mol']['klienci_frame_base_url']."short_license_info.php?nrs=" . implode(",", $licenses);
		}
	}
	
    return $frame_url;
}
// Nux-end

// }}}
// {{{ some useful plugins

function join_attrs($attr = null) {
    if (is_array($attr) && count($attr)) {
        $arr = array();
        foreach ($attr as $key=>$val) {
            $arr[] = vsprintf('%s = "%s"', array_map(array('Filters', 'noXSS'), array($key, $val)));
        }
        return ' '.join(' ', $arr);
    }
    return '';
}
// {{{ Datepicker
function tpl_datepicker($name, $label = '', $value = 0) {
    global $user;

    $date = '';

    if ($value) {
        if (!is_numeric($value)) {
            $value = strtotime($value);
        }

        if (!$user->isAnon()) {
            $st = date('Z')/3600; // server GMT timezone
            $value += ($user->infos['time_zone'] - $st) * 60 * 60;
        }

        $date = date('Y-m-d', intval($value));

     /* It must "look" as a date..
      * XXX : do not blindly copy this code to validate other dates
      * this is mostly a tongue-in-cheek validation
      * 1. it will fail on 32 bit systems on dates < 1970
      * 2. it will produce different results bewteen 32 and 64 bit systems for years < 1970
      * 3. it will not work when year > 2038 on 32 bit systems (see http://en.wikipedia.org/wiki/Year_2038_problem)
      *
      * Fortunately tasks are never opened to be dated on 1970 and maybe our sons or the future flyspray
      * coders may be willing to fix the 2038 issue ( in the strange case 32 bit systems are still used by that year) :-)
      */

    } elseif (Req::has($name) && strlen(Req::val($name))) {

        //strtotime sadly returns -1 on faliure in php < 5.1 instead of false
        $ts = strtotime(Req::val($name));

        foreach (array('m','d','Y') as $period) {
            //checkdate only accepts arguments of type integer
            $$period = intval(date($period, $ts));
        }
        // $ts has to be > 0 to get around php behavior change
        // false is casted to 0 by the ZE
        $date = ($ts > 0 && checkdate($m, $d, $Y)) ? Req::val($name) : '';
    }


    $page = new FSTpl;
    $page->assign('name', $name);
    $page->assign('date', $date);
    $page->assign('label', $label);
    $page->assign('dateformat', '%Y-%m-%d');
    $page->display('common.datepicker.tpl');
}
// }}}
// {{{ user selector
function tpl_userselect($name, $value = null, $id = '', $attrs = array()) {
    global $db, $user;

    if (!$id) {
        $id = $name;
    }

    if ($value && ctype_digit($value)) {
        $sql = $db->Query('SELECT user_name FROM {users} WHERE user_id = ?', array($value));
        $value = $db->FetchOne($sql);
    }

    if (!$value) {
        $value = '';
    }


    $page = new FSTpl;
    $page->assign('name', $name);
    $page->assign('id', $id);
    $page->assign('value', $value);
    $page->assign('attrs', $attrs);
    $page->display('common.userselect.tpl');
}
// }}}

// {{{ Options for a <select>
/**
 * Prepares option tags for select.
 *
 * @example Options array and output:
 * array(
 *  '' => 'Nothing'	// -> <option value="">Nothing
 *  1 => array (0 => 5, 'os_id' => 5, 1 => 'Linux', 'os_name' => 'Linux') // -> <option value="5">Linux
 *  2 => array (0 => 6, 'os_id' => 6, 1 => 'Windows', 'os_name' => 'Windows') // -> <option value="6">Windows
 *  2 => array (0 => 8, 'os_id' => 8, 1 => 'iOS', 'os_name' => 'iOS') // -> <option value="8">iOS
 * );
 *
 * @param array $options Note! Each element of the array can either be
 *	'value' => 'label'
 *	or
 *	'whatever' => array(0 => 'value', 1 => 'label')
 * @param mixed $selected array or string from $_GET or something like that. Mulitselect arrays supported.
 * @param boolean $labelIsValue Force value to be the same as label.
 * @param array $attr Additional attributes.
 * @param string $remove Value to be ignored.
 * @return string HTML with options.
 */
function tpl_options($options, $selected = null, $labelIsValue = false, $attr = null, $remove = null)
{
    $html = '';

    // force $selected to be an array.
    // this allows multi-selects to have multiple selected options.

    // operate by value ..
    $selected = is_array($selected) ? $selected : (array) $selected;
    $options = is_array($options) ? $options : (array) $options;

    foreach ($options as $value=>$label)
    {
        if (is_array($label)) {
            $value = $label[0];
            $label = $label[1];
        }
        $label = htmlspecialchars($label, ENT_QUOTES, 'utf-8');
        $value = $labelIsValue ? $label
                               : htmlspecialchars($value, ENT_QUOTES, 'utf-8');

        if ($value === $remove) {
            continue;
        }

        $html .= '<option value="'.$value.'"';
        if (in_array($value, $selected)) {
            $html .= ' selected="selected"';
        }
        $html .= ($attr ? join_attrs($attr): '') . '>' . $label . '</option>';
    }
    if (!$html) {
        $html .= '<option value="0">---</option>';
    }

    return $html;
} // }}}
// {{{ Double <select>
function tpl_double_select($name, $options, $selected = null, $labelIsValue = false, $updown = true)
{
    static $_id = 0;
    static $tpl = null;

    if (!$tpl) {
        // poor man's cache
        $tpl = new FSTpl();
    }

    settype($selected, 'array');
    settype($options, 'array');

    $tpl->assign('id', '_task_id_'.($_id++));
    $tpl->assign('name', $name);
    $tpl->assign('selected', $selected);
    $tpl->assign('updown', $updown);

    $html = $tpl->fetch('common.dualselect.tpl');

    $selectedones = array();

    $opt1 = '';
    foreach ($options as $value => $label) {
        if (is_array($label) && count($label) >= 2) {
            $value = $label[0];
            $label = $label[1];
        }
        if ($labelIsValue) {
            $value = $label;
        }
        if (in_array($value, $selected)) {
            $selectedones[$value] = $label;
            continue;
        }
        $label = htmlspecialchars($label, ENT_QUOTES, 'utf-8');
        $value = htmlspecialchars($value, ENT_QUOTES, 'utf-8');

        $opt1 .= sprintf('<option title="%2$s" value="%1$s">%2$s</option>', $value, $label);
    }

    $opt2 = '';
    foreach ($selected as $value) {
        if (!isset($selectedones[$value])) {
            continue;
        }
        $label = htmlspecialchars($selectedones[$value], ENT_QUOTES, 'utf-8');
        $value = htmlspecialchars($value, ENT_QUOTES, 'utf-8');

        $opt2 .= sprintf('<option title="%2$s" value="%1$s">%2$s</option>', $value, $label);
    }

    return sprintf($html, $opt1, $opt2);
} // }}}
// {{{ Radio buttons [Nux - based on tpl_options]
function tpl_radiobtn($name, $options, $selected = null, $labelIsValue = false, $attr = null, $remove = null)
{
    $html = '';

    // force $selected to be an array.
    // this allows multi-selects to have multiple selected options.

    // operate by value ..
    $selected = is_array($selected) ? $selected : (array) $selected;
    $options = is_array($options) ? $options : (array) $options;

	$i = 0;
    foreach ($options as $value=>$label)
    {
		$i++;
		$el_id = "{$name}_{$i}";
		
        if (is_array($label)) {
            $value = $label[0];
            $label = $label[1];
        }
        $label = htmlspecialchars($label, ENT_QUOTES, 'utf-8');
        $value = $labelIsValue ? $label
                               : htmlspecialchars($value, ENT_QUOTES, 'utf-8');

        if ($value === $remove) {
            continue;
        }
		$selected_ = var_export($selected, true);
        $html .= "<span class='radiobtn'><input type='radio' value='$value' id='$el_id' name='$name' ";
        if (in_array($value, $selected)) {
            $html .= ' checked="checked"';
        }
        $html .= ($attr ? join_attrs($attr): '') . " /><label for='$el_id'>$label</label></span>";
    }

    return $html;
} // }}}
// {{{ Checkboxes
function tpl_checkbox($name, $checked = false, $id = null, $value = 1, $attr = null)
{
    $name  = htmlspecialchars($name,  ENT_QUOTES, 'utf-8');
    $value = htmlspecialchars($value, ENT_QUOTES, 'utf-8');
    $html  = sprintf('<input type="checkbox" name="%s" value="%s" ', $name, $value);
    if (is_string($id)) {
        $html .= sprintf('id="%s" ', Filters::noXSS($id));
    }
    if ($checked == true) {
        $html .= 'checked="checked" ';
    }
    // do not call join_attrs if $attr is null or nothing..
    return ($attr ? $html. join_attrs($attr) : $html) . '/>';
} // }}}
// {{{ Image display
function tpl_img($src, $alt = '')
{
    global $baseurl;
    if (is_file(BASEDIR .'/'.$src)) {
        return sprintf('<img src="%s%s" alt="%s" />', $baseurl, Filters::noXSS($src), Filters::noXSS($alt));
    }
    return Filters::noXSS($alt);
} // }}}
// {{{ Text formatting
//format has been already checked in constants.inc.php
if(isset($conf['general']['syntax_plugin'])) {

    $path_to_plugin = BASEDIR . '/plugins/' . $conf['general']['syntax_plugin'] . '/' . $conf['general']['syntax_plugin'] . '_formattext.inc.php';

    if (is_readable($path_to_plugin)) {
        include($path_to_plugin);
    }
}

class TextFormatter
{
    function get_javascript()
    {
        global $conf;

        $path_to_plugin = sprintf('%s/plugins/%s', BASEDIR, $conf['general']['syntax_plugin']);
         $return = array();

        if (!is_readable($path_to_plugin)) {
            return $return;
        }

        $d = dir($path_to_plugin);
        while (false !== ($entry = $d->read())) {
           if (substr($entry, -3) == '.js') {
                $return[] = $conf['general']['syntax_plugin'] . '/' . $entry;
            }
        }

        return $return;
    }

	/**
		Remove tags from text
		
		@return true if any tag was found
	*/
    function remove_tags(&$text)
    {
        global $conf;
		
		$isFound = false;
		
		if (!empty($conf['general']['dokuwiki_remove_tags']))
		{
			$arrTags = explode(",", $conf['general']['dokuwiki_remove_tags']);
			foreach($arrTags as $strTagName)
			{
				$isFound = Flyspray::removeTag($text, $strTagName);
			}
		}
		
		return $isFound;
	}

	/**
		Check if tags to be restored exist
		
		@return true if any tag was found
	*/
    function is_restore_tags_needed(&$text, &$instructions)
    {
        global $conf;
		
		$isNeeded = false;
		
		if (!empty($conf['general']['dokuwiki_restore_tags']))
		{
			$arrTags = explode(",", $conf['general']['dokuwiki_restore_tags']);
			foreach($arrTags as $strTagName)
			{
				if (is_string($text) && strpos($text, "<{$strTagName}")!==false)
				{
					if (!(is_string($instructions) && strpos($instructions, "<{$strTagName}")!==false))
					{
						$isNeeded = true;
					}
				}
			}
		}
		
		return $isNeeded;
	}

    function render($text, $onlyfs = false, $type = null, $id = null, $instructions = null)
    {
        global $conf;

		//
		// Nux start - remove/restore some tags from text
		/**/
		// remove tags from text and reset formatting cache if they were removed
		if (TextFormatter::remove_tags($text))
		{
			$instructions = null; // invalidate formatting cache
		}
		// reset formatting cache if tags need to be restored
		if (TextFormatter::is_restore_tags_needed($text, $instructions))
		{
			$instructions = null; // invalidate formatting cache
		}
		/**/
		// Nux end
		//

        if (@in_array('render', get_class_methods($conf['general']['syntax_plugin'] . '_TextFormatter')) && !$onlyfs) {
            return call_user_func(array($conf['general']['syntax_plugin'] . '_TextFormatter', 'render'),
                                  $text, $onlyfs, $type, $id, $instructions);
        } else {
            $text = ' ' . nl2br(htmlspecialchars($text, ENT_QUOTES, 'utf-8')) . ' ';

            // Change URLs into hyperlinks
            if (!$onlyfs) {
                $text = preg_replace('|[[:space:]]+[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]|', '<a href="\0">\0</a>', $text);
                $text = preg_replace('/[a-zA-Z0-9._-]+@[a-zA-Z0-9-.]+\.[a-zA-Z]{2,5}/', '<a href="mailto:\0">\0</a>', $text);
            }

            // Change FS_PREFIX_CODE#123 into hyperlinks to tasks
            return preg_replace_callback("/\b(?:".FS_PREFIX_CODE."#|bug )(\d+)\b/", 'tpl_fast_tasklink', trim($text));
        }
    }

    function textarea($name, $rows, $cols, $attrs = null, $content = null)
    {
        global $conf;

        if (@in_array('textarea', get_class_methods($conf['general']['syntax_plugin'] . '_TextFormatter'))) {
            return call_user_func(array($conf['general']['syntax_plugin'] . '_TextFormatter', 'textarea'),
                                  $name, $rows, $cols, $attrs, $content);
        }

        $name = htmlspecialchars($name, ENT_QUOTES, 'utf-8');
        $return = sprintf('<textarea name="%s" cols="%d" rows="%d"', $name, $cols, $rows);
        if (is_array($attrs) && count($attrs)) {
            $return .= join_attrs($attrs);
        }
        $return .= '>';
        if (is_string($content) && strlen($content)) {
            $return .= htmlspecialchars($content, ENT_QUOTES, 'utf-8');
        }
        $return .= '</textarea>';
        return $return;
    }
}
// }}}
// Format Date {{{
function formatDate($timestamp, $extended = false, $default = '')
{
    global $db, $conf, $user, $fs;

    setlocale(LC_ALL, str_replace('-', '_', L('locale')) . '.utf8');

    if (!$timestamp) {
        return $default;
    }

    $dateformat = '';
    $format_id  = $extended ? 'dateformat_extended' : 'dateformat';
    $st = date('Z')/3600; // server GMT timezone

    if (!$user->isAnon()) {
        $dateformat = $user->infos[$format_id];
        $timestamp += ($user->infos['time_zone'] - $st) * 60 * 60;
        $st = $user->infos['time_zone'];
    }

    if (!$dateformat) {
        $dateformat = $fs->prefs[$format_id];
    }

    if (!$dateformat) {
        $dateformat = $extended ? '%A, %d %B %Y, %H:%M %GMT' : '%Y-%m-%d';
    }

    $zone = L('GMT') . (($st == 0) ? ' ' : (($st > 0) ? '+' . $st : $st));
    $dateformat = str_replace('%GMT', $zone, $dateformat);
    //it returned utf-8 encoded by the system
    return strftime(Filters::noXSS($dateformat), (int) $timestamp);
} /// }}}
// {{{ Draw permissi ons table
function tpl_draw_perms($perms)
{
    global $proj;

    $perm_fields = array('is_admin', 'manage_project', 'view_tasks',
            'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks', 'edit_assignments',
            'view_comments', 'add_comments', 'edit_comments', 'delete_comments',
            'create_attachments', 'delete_attachments',
            'view_history', 'close_own_tasks', 'close_other_tasks',
            'assign_to_self', 'assign_others_to_self', 'view_reports',
            'add_votes', 'edit_own_comments');

    $yesno = array(
            '<td class="bad">' . eL('no') . '</td>',
            '<td class="good">' . eL('yes') . '</td>');

    // FIXME: html belongs in a template, not in the template class
    $html = '<table border="1" onmouseover="perms.hide()" onmouseout="perms.hide()">';
    $html .= '<thead><tr><th colspan="2">';
    $html .= htmlspecialchars(L('permissionsforproject').$proj->prefs['project_title'], ENT_QUOTES, 'utf-8');
    $html .= '</th></tr></thead><tbody>';

    foreach ($perms[$proj->id] as $key => $val) {
        if (!is_numeric($key) && in_array($key, $perm_fields)) {
            $display_key = htmlspecialchars(str_replace( '_', ' ', $key), ENT_QUOTES, 'utf-8');
            $html .= '<tr><th>' . $display_key . '</th>';
            $html .= $yesno[ ($val || $perms[0]['is_admin']) ].'</tr>';
        }
    }
    return $html . '</tbody></table>';
} // }}}

/**
 * Highlights searchqueries in HTML code
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Harry Fuecks <hfuecks@gmail.com>
 */
function html_hilight($html,$query){
  //split at common delimiters
  $queries = preg_split ('/[\s\'"\\\\`()\]\[?:!\.{};,#+*<>]+/',$query,-1,PREG_SPLIT_NO_EMPTY);
  foreach ($queries as $q){
     $q = preg_quote($q,'/');
     $html = preg_replace_callback("/((<[^>]*)|$q)/i",'html_hilight_callback',$html);
  }
  return $html;
}

/**
 * Callback used by html_hilight()
 *
 * @author Harry Fuecks <hfuecks@gmail.com>
 */
function html_hilight_callback($m) {
  $hlight = unslash($m[0]);
  if ( !isset($m[2])) {
    $hlight = '<span class="search_hit">'.$hlight.'</span>';
  }
  return $hlight;
}

function tpl_disableif ($if)
{
    if ($if) {
        return 'disabled="disabled"';
    }
}

// {{{ Url handling
// Create an URL bas ed upon address-rewriting preferences {{{
function CreateURL($type, $arg1 = null, $arg2 = null, $arg3 = array())
{
    global $baseurl, $conf;

    $url = $baseurl;

    // If we do want address rewriting
    if ($conf['general']['address_rewriting'] == '1') {
        switch ($type) {
            case 'depends':   $return = $url . 'task/' .  $arg1 . '/' . $type; break;
            case 'copytobugz':
            case 'details':   $return = $url . 'task/' . $arg1; break;
			case 'copytask':
            case 'edittask':  $return = $url . 'task/' .  $arg1 . '/edit'; break;
            case 'pm':        $return = $url . 'pm/proj' . $arg2 . '/' . $arg1; break;

            case 'admin':
            case 'edituser':
            case 'user':      $return = $url . $type . '/' . $arg1; break;

            case 'project':   $return = $url . 'proj' . $arg1; break;

            case 'toplevel':
            case 'roadmap':
            case 'index':
            case 'newtask':   $return = $url . $type .  '/proj' . $arg1; break;

            case 'editgroup': $return = $url . $arg2 . '/' . $type . '/' . $arg1; break;

            case 'logout':
            case 'lostpw':
            case 'myprofile':
            case 'register':
            case 'reports':  $return = $url . $type; break;
        }
    } else {
        if ($type == 'edittask') {
            $url .= 'index.php?do=details';
        } else {
            $url .= 'index.php?do=' . $type;
        }

        switch ($type) {
            case 'admin':     $return = $url . '&area=' . $arg1; break;
			case 'copytask':
            case 'edittask':  $return = $url . '&task_id=' . $arg1 . '&edit=yep'; break;
            case 'pm':        $return = $url . '&area=' . $arg1 . '&project=' . $arg2; break;
            case 'user':      $return = $baseurl . 'index.php?do=user&area=users&id=' . $arg1; break;
            case 'edituser':  $return = $baseurl . 'index.php?do=admin&area=users&user_id=' . $arg1; break;
            case 'logout':    $return = $baseurl . 'index.php?do=authenticate&logout=1'; break;

            case 'copytobugz':
            case 'details':
            case 'depends':   $return = $url . '&task_id=' . $arg1; break;

            case 'project':   $return = $baseurl . 'index.php?project=' . $arg1; break;

            case 'roadmap':
            case 'toplevel':
            case 'index':
            case 'newtask':   $return = $url . '&project=' . $arg1; break;

            case 'editgroup': $return = $baseurl . 'index.php?do=' . $arg2 . '&area=editgroup&id=' . $arg1; break;

            case 'lostpw':
            case 'myprofile':
            case 'register':
            case 'reports':   $return = $url; break;
        }
    }

    $url = new Url($return);
    if (count($arg3)) {
        $url->addvars($arg3);
    }
    return $url->get();
} // }} }
// Page  numbering {{{
// Thanks to Nathan Fritz for this.  http://www.netflint.net/
function pagenums($pagenum, $perpage, $totalcount)
{
    global $proj;
    $pagenum = intval($pagenum);
    $perpage = intval($perpage);
    $totalcount = intval($totalcount);

    // Just in case $perpage is something weird, like 0, fix it here:
    if ($perpage < 1) {
        $perpage = $totalcount > 0 ? $totalcount : 1;
    }
    $pages  = ceil($totalcount / $perpage);
    $output = sprintf(eL('page'), $pagenum, $pages);

    if (!($totalcount / $perpage <= 1)) {
        $output .= '<span class="DoNotPrint"> &nbsp;&nbsp;--&nbsp;&nbsp; ';

        $start  = max(1, $pagenum - 4 + min(2, $pages - $pagenum));
        $finish = min($start + 4, $pages);

        if ($start > 1) {
            $url = Filters::noXSS(CreateURL('index', $proj->id, null, array_merge($_GET, array('pagenum' => 1))));
            $output .= sprintf('<a href="%s">&lt;&lt;%s </a>', $url, eL('first'));
        }
        if ($pagenum > 1) {
            $url = Filters::noXSS(CreateURL('index', $proj->id, null, array_merge($_GET, array('pagenum' => $pagenum - 1))));
            $output .= sprintf('<a id="previous" accesskey="p" href="%s">&lt; %s</a> - ', $url, eL('previous'));
        }

        for ($pagelink = $start; $pagelink <= $finish;  $pagelink++) {
            if ($pagelink != $start) {
                $output .= ' - ';
            }

            if ($pagelink == $pagenum) {
                $output .= sprintf('<strong>%d</strong>', $pagelink);
            } else {
                $url = Filters::noXSS(CreateURL('index', $proj->id, null, array_merge($_GET, array('pagenum' => $pagelink))));
                $output .= sprintf('<a href="%s">%d</a>', $url, $pagelink);
            }
        }

        if ($pagenum < $pages) {
            $url =  Filters::noXSS(CreateURL('index', $proj->id, null, array_merge($_GET, array('pagenum' => $pagenum + 1))));
            $output .= sprintf(' - <a id="next" accesskey="n" href="%s">%s &gt;</a>', $url, eL('next'));
        }
        if ($finish < $pages) {
            $url = Filters::noXSS(CreateURL('index', $proj->id, null, array_merge($_GET, array('pagenum' => $pages))));
            $output .= sprintf('<a href="%s"> %s &gt;&gt;</a>', $url, eL('last'));
        }
        $output .= '</span>';
    }

    return $output;
} // }}}
class Url {
	var $url = '';
	var $parsed;

	function url($url = '') {
		$this->url = $url;
		$this->parsed = parse_url($this->url);
	}

	function seturl($url) {
		$this->url = $url;
		$this->parsed = parse_url($this->url);
	}

	function getinfo($type = null) {
		if (is_null($type)) {
			return $this->parsed;
		} elseif (isset($this->parsed[$type])) {
			return $this->parsed[$type];
		} else {
			return '';
		}
	}

	function setinfo($type, $value) {
		$this->parsed[$type] = $value;
	}

	function addfrom($method = 'get', $vars = array()) {
		$append = '';
		foreach($vars as $key) {
			$append .= Url::query_from_array( (($method == 'get') ? Get::val($key) : Post::val($key)) ) . '&';
        }
        $append = substr($append, 0, -1);

        if ($this->getinfo('query')) {
        	$this->parsed['query'] .= '&' . $append;
        } else {
        	$this->parsed['query'] = $append;
        }
	}

    function query_from_array($vars) {
        $append = '';
		foreach ($vars as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $valuei) {
                    $append .= rawurlencode($key) . '[]=' . rawurlencode($valuei) . '&';
                }
            } else {
                $append .= rawurlencode($key) . '=' . rawurlencode($value) . '&';
            }
        }
        return substr($append, 0, -1);
    }

	function addvars($vars = array()) {
        $append = Url::query_from_array($vars);

        if ($this->getinfo('query')) {
        	$this->parsed['query'] .= '&' . $append;
        } else {
        	$this->parsed['query'] = $append;
        }
	}

	function get($fullpath = true) {
		$return = '';
		if ($fullpath) {
			$return .= $this->getinfo('scheme') . '://' . $this->getinfo('host');

            if ($this->getinfo('port')) {
                $return .= ':' . $this->getinfo('port');
            }
        }

		$return .= $this->getinfo('path');

		if ($this->getinfo('query')) {
            $return .= '?' . $this->getinfo('query');
		}

		if ($this->getinfo('fragment')) {
		 	$return .= '#' . $this->getinfo('fragment');
		}

		return $return;
	}
}
// }}}
// }}}
?>
