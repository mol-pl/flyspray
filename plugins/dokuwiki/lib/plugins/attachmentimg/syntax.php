<?php
/**
 * Show-Attachements-As-Imgs Plugin
 *
 * @license	GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author	 Maciej Jaros <egil@wp.pl>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_attachmentimg extends DokuWiki_Syntax_Plugin
{
	/**
	 * return some info
	 */
	function getInfo()
	{
		return array(
			'author' => 'Maciej Jaros',
			'email'  => 'egil@wp.pl',
			'date'   => '2009-06-29',
			'name'   => 'Show-Attachements-As-Imgs Plugin',
			'desc'   => 'Adds abbility to show attachements with {{FB:*}}',
			'url'	=> 'http://flyspray.org/',
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
		return 319;	// before Doku_Parser_Mode_media, see http://www.dokuwiki.org/devel:parser:getsort_list
	}
 
	/**
	 * Connect pattern to lexer
	 */
	 
	function connectTo($mode)
	{
		$this->Lexer->addSpecialPattern("\{\{FB:.+?\}\}",$mode,'plugin_attachmentimg');
	}
 
	/**
	 * Handle the match
	 */
	function handle($match, $state, $pos, &$handler)
	{
		// Strip the opening and closing markup
		$match = preg_replace('/^\{\{FB:(.*)\}\}$/u', '$1', $match);
		
		$img_attrs = array();
		
		// no options?
		if (strpos($match, '|')===false)
		{
			$img_attrs ['src'] = $match;
		}
		else
		{
			$parts = explode('|', $match);
			$img_attrs ['src'] = $parts[0];
			unset ($parts[0]);
			foreach ($parts as $m)
			{
				$m = trim($m);
				if (preg_match('/^[0-9]+(px|em|pt)$/', $m))
				{
					$img_attrs ['width'] = $m;
				}
				if (preg_match('/^([0-9]*)((?:px|em|pt)?)x([0-9]+)(px|em|pt)$/', $m, $submatches))
				{
					if (!empty($submatches[1]))
					{
						$img_attrs ['width'] = $submatches[1] . (!empty($submatches[2]) ? $submatches[2] : $submatches[4]);
					}
					$img_attrs ['height'] = $submatches[3] . $submatches[4];
				}
				// not working nicely... (height should be added for it to work)
				else if (preg_match('/^(right|left|center)$/', $m))
				{
					$img_attrs ['align'] = $m;
					$img_attrs ['class'] = 'media'.$m;
				}
				else if (!empty($m))
				{
					$img_attrs ['title'] = $m;
					$img_attrs ['alt'] = $m;
				}
			}
		}

		//
		// alt attr
		if (empty($img_attrs ['title']))
		{
			$img_attrs ['alt'] = htmlspecialchars($img_attrs ['src']);
		}

		//
		// link if height or width given
		//
		$url_attrs = array();
		if (isset($img_attrs['height']) || isset($img_attrs['width']))
		{
			$url_attrs['rel'] = 'lightbox[bug]';
			$url_attrs['href'] = $img_attrs['src'];
			if (isset($img_attrs['title']))
			{
				$url_attrs['title'] =  $img_attrs['title'];
			}
			if (isset($img_attrs['height']))
			{
				$url_attrs['style'] =  'height:'.$img_attrs['height'];
			}
		}

		$tmp = '';
		foreach ($img_attrs as $k=>$d)
		{
			$tmp .= " $k=\"$d\" ";
		}
		$img_attrs = $tmp;

		$tmp = '';
		foreach ($url_attrs as $k=>$d)
		{
			$tmp .= " $k=\"$d\" ";
		}
		$url_attrs = $tmp;

		return array($img_attrs, $url_attrs);
	}			
 
	/**
	 * Create output
	 */
	function render($mode, &$renderer, $data)
	{
		if($mode == 'xhtml')
		{
			//$renderer->doc .= '<pre>debug: '.htmlspecialchars(var_export($data)).'</pre>';
			if (empty($data[1]))
			{
				$renderer->doc .= '<img '.$data[0].' />';
			}
			else
			{
				$renderer->doc .= '<a class="media" '.$data[1].'><img '.$data[0].' /></a>';
			}
		}
		return true;
	}
	 
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>