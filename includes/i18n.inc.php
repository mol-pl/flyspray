<?php

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once BASEDIR . '/lang/en.php';

/**
 * get the language string $key
 * return string
 */

function L($key)
{
    global $language;
    if (empty($key)) {
        return '';
    }
    if (isset($language[$key])) {
        return $language[$key];
    }
    return "[[$key]]";
}

/**
 * html escaped variant of the previous
 * return $string
 */

function eL($key)
{
    return htmlspecialchars(L($key), ENT_QUOTES, 'utf-8');
}

function load_translations()
{
    global $proj, $language;
    // Load translations
    // if no valid lang_code, return english
    // valid == a-z and "_" case insensitive
    if (!preg_match('/^[a-z_]+$/iD', $proj->prefs['lang_code'])) {
        $proj->prefs['lang_code'] = 'en';
    }

    $translation_file = BASEDIR . "/lang/{$proj->prefs['lang_code']}.php";
    if ($proj->prefs['lang_code'] != 'en' && is_readable($translation_file)) {
        include_once($translation_file);
		// Nux-start: allow site-specific translations to be merged in
		// @note It's assumed they used the following syntax: $translation = array_merge($translation, array(...overridden and new stuff...));
		$translation_file_site_specific = preg_replace("#\\.php$#", '.site-specific.php', $translation_file);
		if (is_readable($translation_file_site_specific))
		{
			include($translation_file_site_specific);
		}
		// Nux-end: allow site-specific translations to be merged in
        $language = is_array($translation) ? array_merge($language, $translation) : $language;
    }

    // correctly translate title since language not set when initialising the project
    if (!$proj->id) {
        $proj->prefs['project_title'] = L('allprojects');
        $proj->prefs['feed_description']  = L('feedforall');
    }
}

?>
