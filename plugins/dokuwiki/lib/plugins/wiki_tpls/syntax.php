<?php
/**
 * Wiki-like templates for flyspray dokuwiki
 *
 * Currently all templates should be inline and have parameters so they wouldn't colide with Media handling.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Maciej Jaros <http://www.enux.pl>
 * @start      2010-02-23
 * @version    2.0.0
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'wiki_tpls/AbstractWikiTemplate.php');
foreach (glob(DOKU_PLUGIN."wiki_tpls/templates/*.php") as $filename)
{
    include $filename;
}
//echo "<pre>";
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_wiki_tpls extends DokuWiki_Syntax_Plugin
{
	/**
	 * return some info
	 */
	function getInfo()
	{
		return array(
			'author' => 'Maciej Jaros',
			'email'  => 'egil@wp.pl',
			'date'   => '2010-02-22',
			'name'   => 'Wiki-like templates for flyspray dokuwiki',
			'desc'   => 'Adds syntax for mediawiki-like templates with "Tpl:" or "T:" prefix (note - you need to implement templates here)',
			'url'	=> 'http://enux.pl/',
		);
	}
	
	/**
	 * @return configuration for this plugin
	 */
	function getMyConf()
	{
		$myConf = array(
			'template_classes' => array(),
			'devs_status' => '&status[]=1&status[]=2&status[]=3&status[]=5&status[]=9',
			'test_status' => '&status[]=4',
			'pm_status' => '&status[]=13&status[]=1&status[]=9&status[]=6',
			'devs_order' => '&order=assignedto&sort=asc',
			'single_dev_order' => '&order=assignedto&sort=asc',
			'iso_link_format' => '[[iso>inc/forms/KARTA_PROJEKTU.jsp?id_typ=6&id_form=3&count={count}|Karta projektu]]',
		);
		
		// RejsZ extra
 		if (FS_PREFIX_CODE == 'RZ')
		{
			$myConf['devs_status'] = '&status[]=2&status[]=3&status[]=5&status[]=14';
			$myConf['test_status'] = '&status[]=4&status[]=15';
			$myConf['devs_order'] = $myConf['single_dev_order'] = '&order=priority&sort=desc&order2=severity&sort2=desc';
		}
		
		foreach (glob(DOKU_PLUGIN."wiki_tpls/templates/*.php") as $filename)
		{
			$myConf['template_classes'][] = str_ireplace('.php', '', basename($filename));
		}
		
		return $myConf;
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
		return 319;	// before Doku_Parser_Mode_media, see http://www.dokuwiki.org/devel:parser:getsort_list
	}
 
	/**
	 * Inline
	 */
    function getPType()
    {
        return 'normal';
    }
    function getEmbeddingType()
    {
        return 'inline';
    }

	/**
	 * Connect pattern to lexer
	 */
	 
	function connectTo($mode)
	{
		$this->syntax_plugin_wiki_tpls_tplprefix = "[Tt](?:[Pp][Ll])?";
		$this->Lexer->addSpecialPattern("\{\{".$this->syntax_plugin_wiki_tpls_tplprefix.":.+?\}\}",$mode,'plugin_wiki_tpls');
	}
	
	/**
	 * Handle the match
	 * Note! this is cached.
	 */
	function handle($dokuwikicode, $state, $pos, &$handler)
	{
		$plugin_conf = $this->getMyConf();
		
		//
		// pre-parse parameterss
		//
		$parsed = syntax_plugin_wiki_tpls::pre_parse_template ($dokuwikicode);
		$tpl_name = $parsed[0];
		$tpl_params = $parsed[1];
		unset($parsed);
		
		//
		// Match template parser
		//
		$parserClass = '';
		foreach ($plugin_conf['template_classes'] as $className) {
			if (call_user_func(array($className, 'isMine'), $tpl_name)) {
				$parserClass = $className;
				break;
			}
		}
		
		//
		// Parse
		//
		if (!empty($parserClass)) {
			$parser = new $parserClass($tpl_name);
			$data = array(
				'parsed' => $parser->parse($tpl_params, $plugin_conf),
				'parserClass' => $parserClass,
				'tpl_name' => $tpl_name,
			);
		}
		else {
			$data = array(
				'parsed' => '__NIEZNANY_SZABLON__:'.$tpl_name,
			);
		}
		return $data;
	}			
 
	/**
	 * Create output
	 * This is calculated upon every run (every page refresh).
	 */
	function render($mode, &$renderer, $data)
	{
		//echo "\n\npre-rendered:";
		//var_export($data);
		$dokuCode = is_array($data) ? $data['parsed'] : $data;
		if (!empty($data['parserClass'])) {
			$className = $data['parserClass'];
			$method = array($className, 'hasReRender');
			if (is_callable($method) && call_user_func($method)) {
				$parser = new $className($data['tpl_name']);
				$dokuCode = $parser->reRender($dokuCode);
			}
		}
		// render content
        $html = TextFormatter::render($dokuCode);
		$html = preg_replace('#^\s*<p>\s*#im', '', $html);
		$html = preg_replace('#\s*</?p>\s*$#im', '', $html);
		// output
		$renderer->doc .= $html;
		return true;
	}
	 

	/**
	 * Extra functions
	 *
	 * pre-parse template code
	 *
	 * @return: array($tpl_name, $tpl_params);
	 */
	function pre_parse_template($dokuwikicode)
	{
		preg_match('/^\{\{'.$this->syntax_plugin_wiki_tpls_tplprefix.':([^|{}]+)(.*)\}\}$/u', $dokuwikicode, $matches);
		$tpl_name = $matches[1];
		$tpl_params = $matches[2];
		unset($matches);
		if ($tpl_params!='')
		{
			if (preg_match_all('/\|\s*(\S+?)\s*=\s*([^=]+?)\s*(?=([\|\}]|$))/', $tpl_params, $tpl_params_))
			{
				$tpl_param_names = $tpl_params_[1];
				$tpl_param_values = $tpl_params_[2];
				unset($tpl_params_);
				$tpl_params = array();
				foreach ($tpl_param_names as $i=>&$val)
				{
					$p_name = $tpl_param_names[$i];
					
					// param is an array of vaules
					if (isset($tpl_params[$p_name]) && is_array($tpl_params[$p_name]))
					{
						$tpl_params[$p_name][] = $tpl_param_values[$i];
					}
					// we have to create an array
					else if (!empty($tpl_params[$p_name]))
					{
						$tpl_params[$p_name] = array($tpl_params[$p_name]);
						$tpl_params[$p_name][] = $tpl_param_values[$i];
					}
					// param not set yet
					else
					{
						$tpl_params[$p_name] = $tpl_param_values[$i];
					}
				}
			}
			else
			{
				$tpl_params = '';
			}
		}
		
		return array($tpl_name, $tpl_params);
	}
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>