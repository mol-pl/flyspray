<?php
/**
 * BB links plugin
 *
 * conf -> connectTo
 *
 * @license	GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author	Maciej Jaros enux.pl
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_bblink extends DokuWiki_Syntax_Plugin
{
	/**
	 * @return configuration for this plugin
	 */
	function getMyConf()
	{
		$bugServices = array();
		include_once(dirname(__FILE__).'/.private.conf.php');
		
		return $bugServices;
	}

	/**
	 * return some info
	 */
	function getInfo()
	{
		return array(
			'author' => 'Maciej Jaros',
			'email'  => 'egil@wp.pl',
			'date'   => '2011-04-05',
			'name'   => 'BB links plugin',
			'desc'   => 'Enables external flyspray or other bugzilla links',
			'url'	=> 'http://enux.pl/',
		);
	}
 
	/**
	 * What kind of syntax are we?
	 */
	function getType()
	{
		return 'substition';
	}
 
	/**
	 * Where to sort in?
	 */
	function getSort()
	{
		return 302;
	}
 
	/**
	 * Connect pattern to lexer
	 */
	function connectTo($mode)
	{
		$plugin_confs = $this->getMyConf();
		foreach($plugin_confs as $plugin_conf)
		{
			// register all except self
			if (FS_PREFIX_CODE != $plugin_conf['prefix'])
			{
				$this->Lexer->addSpecialPattern($plugin_conf['prefix'].'#\d+',$mode,'plugin_bblink');
			}
		}
	}
 
	/**
	 * Handle the match
	 */
	function handle($match, $state, $pos, &$handler)
	{
		$posSep = strpos($match, '#');
		if ($posSep===false)
		{
			$posSep = strpos($match, ' ');
		}
		$prefix = substr($match, 0, $posSep);
		$confid = 0;
		$plugin_confs = $this->getMyConf();
		foreach($plugin_confs as $key => $plugin_conf)
		{
			if ($plugin_conf['prefix'] == $prefix)
			{
				$confid = $key;
				break;
			}
		}
		//echo "[$prefix][$confid]";
		$bugid = substr($match, $posSep+1);
		//var_export($match);
		return array($match, $bugid, $confid);
	}			
 
	/**
	 * Create output
	 */
	function render($mode, &$renderer, $data)
	{
		$plugin_conf = $this->getMyConf();
		if($mode == 'xhtml')
		{
			// get data set in handle()
			$id = $data[1];
			$confid = $data[2];
			$text = $data[0];
			// prepare other data
			global $dokuConf;
			$url = str_replace('{ID}', $id, $plugin_conf[$confid]['url']);
		   
			//prepare for formating
			$link['target'] = $dokuConf['target']['wiki'];
			$link['style']  = '';
			$link['pre']	= '';
			$link['suf']	= '';
			$link['more']   = '';
			$link['class']  = 'interwikilink';
			$link['url']	= $url;
			$link['name']   = $text;
			$link['title']  = $text;
	
			//output formatted
			$renderer->doc .= $renderer->_formatLink($link);
		}
		return true;
	}
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>