<?php
class dokuwiki_TextFormatter
{    
    function render($text, $onyfs = false, $type = null, $id = null, $instructions = null)
    {
        global $dokuConf, $baseurl, $db;
        
		/*
        // Unfortunately dokuwiki also uses $conf
        $fs_conf = $conf;
        $conf = array();
		*/

        // Dokuwiki generates some notices
		if (!defined('E_DEPRECATED'))
		{
			error_reporting(E_ALL ^ E_NOTICE);
		}
		else
		{
			error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED | E_USER_DEPRECATED));
		}
        if (!$instructions) {
            include_once(BASEDIR . '/plugins/dokuwiki/inc/parser/parser.php');
        }
        require_once(BASEDIR . '/plugins/dokuwiki/inc/common.php');
        require_once(BASEDIR . '/plugins/dokuwiki/inc/parser/xhtml.php');
        
		//
		// Nux: FIX blockquotes
		//
		// step 1 - encode before parsing
		$text = preg_replace ('#<(/?blockquote)>#', '[$1]', $text);
		//
		// Nux: FIX blockquotes :END
		//

		//
		// Nux: FIX (remove) HTML comments
		//
		$text = preg_replace ('#<!--([\s\S]+?)-->#', '', $text);
		//
		// Nux: FIX (remove) HTML comments :END
		//

        // Create a renderer
        $Renderer = & new Doku_Renderer_XHTML();

        if (!is_string($instructions) || strlen($instructions) < 1) {
            $modes = p_get_parsermodes();
            
            $Parser = & new Doku_Parser();
            
            // Add the Handler
            $Parser->Handler = & new Doku_Handler();
            
            // Add modes to parser
            foreach($modes as $mode){
                $Parser->addMode($mode['mode'], $mode['obj']);
            }
            $instructions = $Parser->parse($text);

            
            // Cache the parsed text
            if (!is_null($type) && !is_null($id)) {
                $fields = array('content'=> serialize($instructions), 'type'=> $type , 'topic'=> $id,
                                'last_updated'=> time());

                $keys = array('type','topic');
                //autoquote is always true on db class
                $db->Replace('{cache}', $fields, $keys);
            }
        } else {
            $instructions = unserialize($instructions);
        }

        $Renderer->smileys = getSmileys();
        $Renderer->entities = getEntities();
        $Renderer->acronyms = getAcronyms();
        $Renderer->interwiki = getInterwiki();

		/*
        $conf = $fs_conf;
		*/
        $dokuConf['cachedir'] = FS_CACHE_DIR; // for dokuwiki
        $dokuConf['fperm'] = 0600;
        $dokuConf['dperm'] = 0700;
        
        // Loop through the instructions
        foreach ($instructions as $instruction) {
            // Execute the callback against the Renderer
            call_user_func_array(array(&$Renderer, $instruction[0]), $instruction[1]);
        }

        $return = $Renderer->doc;
		
		/**
		debug_log ('[postrender]'
			."\n\$return:\n$return"
		);
		/**/

		//
		// Nux: FIX http(s)
		//
		$return = preg_replace ('#(href=[\'\"])https?://(192\.168\.0\.23|prl(\.mol\.com\.pl)?)/#', '$1//prl.mol.com.pl/', $return);
		//
		// Nux: FIX http(s) :END
		//

		//
		// Nux: FIX blockquotes
		//
		// step 2 - decode after parsing
		$return = preg_replace ('#\[(/?blockquote)\]#', '<$1>', $return);
		/*
		// 3rd fix (simple mode)
		$return = preg_replace ('#<p>[ \n\r\t]*(.+?)&lt;blockquote&gt;[ \n\r\t]*</p>#', '<p>$1</p><blockquote>', $return);
		// 1st fix - '#<p>[ \n\r\t]*&lt;/blockquote&gt;[ \n\r\t]*</p>#' -> </blockquote>
		$pv_blocks_open = preg_match_all ('#<blockquote>#', $return, $pv_blocks);
		$pv_blocks_close = $pv_blocks_open ? preg_match_all ('#</blockquote>#', $return, $pv_blocks) : 0;
		// something wrong...
		if ($pv_blocks_open > $pv_blocks_close)
		{
			$pv_limit = $pv_blocks_open - $pv_blocks_close; // still some might be right to be escaped - probably...
			$return = preg_replace ('#<p>[ \n\r\t]*&lt;/blockquote&gt;[ \n\r\t]*</p>#', '</blockquote>', $return, $pv_limit);
		}
		// 2nd fix - </blockquote></div></li></ol> -> </div></li></ol></blockquote>
		//$return = preg_replace ('#(<li[^>]*><div[^>]*>(.|\n)*?)</blockquote></div>#', '</blockquote>', $return, $pv_limit);
		$return = preg_replace ('#</blockquote>(</div>[ \n\r\t]*</li>[ \n\r\t]*</ol>)#', '$1</blockquote>', $return);
		*/

		//
		// Nux: FIX blockquotes :END
		//
		
		
        // Display the output
        if (Get::val('histring')) {
            $words = explode(' ', Get::val('histring'));
            foreach($words as $word) {
                $return = html_hilight($return, $word);
            }
        }
        
        return $return;
    }
    function textarea( $name, $rows, $cols, $attrs = null, $content = null) {
    	
    	$name = htmlspecialchars($name, ENT_QUOTES, 'utf-8');
        $rows = intval($rows);
        $cols = intval($cols);
        $return = '<div id="dokuwiki_toolbar">'
        		. dokuwiki_TextFormatter::getDokuWikiToolbar( $attrs['id'] )
        		. '</div>';
        
        $return .= "<textarea name=\"{$name}\" cols=\"$cols\" rows=\"$rows\" ";
        if (is_array($attrs)) {
            $return .= join_attrs($attrs);
        }
        $return .= '>';
        if (!is_null($content)) {
            $return .= htmlspecialchars($content, ENT_QUOTES, 'utf-8');
        }
        $return .= '</textarea>';
        return $return;
    }
    /**
	 * Displays a toolbar for formatting text in the DokuWiki Syntax
	 * Uses Javascript
	 *
	 * @param string $textareaId
	 */
	function getDokuWikiToolbar( $textareaId ) {
		global $dokuConf, $baseurl, $user;
	
		//
		// Setup
		//
		$strToolbarExtra = '';
		$strBugExtraStepsText = ""
			."\\n"
			."\\n**Powtarzalność**:"
			."\\nProblem wystąpił tylko raz / Problem zdarza się czasami / Problem występuje zawsze"
		;
		switch (FS_PREFIX_CODE)
		{
			case 'RZ':
				$strBugExtraStepsText .= ""
					."\\n"
					."\\n**Przeglądarka (i wersja!)**:"
					."\\nFirefox / Chrome / IE / Opera / mobilna"
					."\\n"
					."\\n**Użyty serwer i konto**:"
					."\\n  * serwer: test / dev"
					."\\n  * mol.net user: ..."
					."\\n  * mol.net hasło: ..."
				;
				$strBugExtraInfoText = ""
					."\\n====Informacje dodatkowe===="
					."\\n  * czas wykonania: (G/D/T/M/+/-)"
				;
				
				if (in_array($user->infos['real_name'], array('Nux', 'Paweł', 'Przemek')))
				{
					$strToolbarExtra = '
						<div class="serwis_templates">
							<img onload="nuxbar.insertTemplates(this.parentNode, \''.$textareaId.'\')" src="'.$baseurl.'themes/Bluey/ajax_load.gif" align="bottom" width="22" height="22" alt="Wczytywanie?" title="Wczytywanie szablonów odpowiedzi" border="0" />
						</div>
					';
				}
			break;

			case 'BB':
				$strBugExtraInfoText = ""
					."\\n====Informacje dodatkowe===="
					."\\n  * Praca biblioteki wstrzymana: Tak/Nie"
				;
				if (!$user->isAnon()) // especially not for Bibz users (not for customers)
				{
				$strToolbarExtra = '
					<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'\n<notka_serwisowa>**'.$user->infos['real_name'].'** ('.date('Y-m-d, H:i').')\n\n\', \'\n</notka_serwisowa>\', \''.$textareaId.'\'); return false;">
						<img src="'.$baseurl.'plugins/dokuwiki/img/note_internal.png" align="bottom" alt="Wstaw notkę" title="Wstaw notkę serwisową (niewidoczną dla klienta)" border="0" /></a>

					<img src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

					<a tabindex="-1" href="javascript:void(0);" onclick="nuxbar.mapInternalToExternal(\''.$textareaId.'\'); return false;">
						<img src="'.$baseurl.'plugins/dokuwiki/img/Crystal_Clear_app_network_local.png" align="bottom" alt="Serv2URL" title="Mapuj zaznaczone plik (ścieżki) z Serv na zewnętrzny URL" border="0" /></a>

					<div class="serwis_templates">
						<img onload="nuxbar.insertTemplates(this.parentNode, \''.$textareaId.'\')" src="'.$baseurl.'themes/Bluey/ajax_load.gif" align="bottom" width="22" height="22" alt="Wczytywanie?" title="Wczytywanie szablonów odpowiedzi" border="0" />
					</div>
				';
				}
			break;

			default:
				$strBugExtraInfoText = ""
					."\\n====Informacje dodatkowe===="
					."\\n  * ilość zgłoszeń: 1 (BB#)"
					."\\n  * czas wykonania: (G/D/T/M/+/-)"
				;
			break;
		}
		
		$strBugText = ""
			."\\n====Kroki do powtórzenia===="
			."\\n  -"
			."\\n  -"
			."\\n  -"
			.$strBugExtraStepsText
			."\\n"
			."\\n====Otrzymany wynik===="
			."\\n"
			."\\n"
			."\\n====Spodziewany wynik===="
			."\\n"
			."\\n"
			.$strBugExtraInfoText
		;
		
		//
		// Return toolbar HTML
		//
		return '
			<script type="text/javascript" src="'.$baseurl.'plugins/dokuwiki/lib/bar_enhance.js?1343"></script>
			<script type="text/javascript" src="'.$baseurl.'plugins/dokuwiki/lib/bar_enhance.config.js?1343"></script>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'**\', \'**\', \''.$textareaId.'\'); return false;">
		  		<img src="'.$baseurl.'plugins/dokuwiki/img/format-text-bold.png" align="bottom" alt="Pogrubienie" title="Pogrubienie" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'//\', \'//\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/format-text-italic.png" align="bottom" alt="Kursywa" title="Kursywa" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'__\', \'__\', \''.$textareaId.'\'); return false;">
			<img src="'.$baseurl.'plugins/dokuwiki/img/format-text-underline.png" align="bottom" alt="Podkreślenie" title="Podkreślenie" border="0" /></a>
			
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'&lt;del&gt;\', \'&lt;/del&gt;\', \''.$textareaId.'\'); return false;">
			<img src="'.$baseurl.'plugins/dokuwiki/img/format-text-strikethrough.png" align="bottom" alt="Przekreślenie" title="Przekreślenie" border="0" /></a>
			
			<img src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" align="bottom" alt="|" style="margin: 0 3px 0 3px;" />
			
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'======\', \'======\', \''.$textareaId.'\'); return false;">
			<img title="Nagłówek - poziom 1" src="'.$baseurl.'plugins/dokuwiki/img/h1.gif" align="bottom" width="23" height="22" alt="Heading1" border="0" /></a>

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'=====\', \'=====\', \''.$textareaId.'\'); return false;">
			<img title="Nagłówek - poziom 2" src="'.$baseurl.'plugins/dokuwiki/img/h2.gif" align="bottom" width="23" height="22" alt="Heading2" border="0" /></a>

			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'====\', \'====\', \''.$textareaId.'\'); return false;">
			<img title="Nagłówek - poziom 3" src="'.$baseurl.'plugins/dokuwiki/img/h3.gif" align="bottom" width="23" height="22" alt="Heading3" border="0" /></a>
			
			<img title="Divider" src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />
			
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'&#123;&#123;FB:index.php?getfile=...|550px|center|tytuł\', \'&#125;&#125;\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/image-x-generic.png" align="bottom" alt="Wstaw obrazek" title="Wstaw obrazek" border="0" /></a>
			
			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\'\n  * \', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/ul.gif" align="bottom" width="23" height="22" alt="Wstaw listę" title="Wstaw listę" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\'\n  - \', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/ol.gif" align="bottom" width="23" height="22" alt="Wstaw listę numeryczną" title="Wstaw listę numeryczną" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\'----\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/hr.gif" align="bottom" width="23" height="22" alt="Linia rozdzielająca" title="Linia rozdzielająca" border="0" /></a>
				
			<img src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />
			
			<a tabindex="-1" href="javascript:void(0);" onclick="nuxbar.insertURL(\''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/text-html.png" align="bottom" alt="Wstaw link" title="Wstaw link" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="nuxbar.insertEmail(\''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/email.png" align="bottom" alt="Wstaw e-mail" title="Wstaw e-mail" border="0" /></a>
			'.
			/*
			'
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'[[http://link.zewnetrzny.pl/cos_tam.htm|Link zewnatrzny\', \']]\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/text-html.png" align="bottom" alt="Wstaw link" title="Wstaw link" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'[[\', \']]\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/email.png" align="bottom" alt="Wstaw e-mail" title="Wstaw e-mail" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'[[ftp://\', \']]\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/network.png" align="bottom" alt="Wstaw link do FTP" title="Wstaw link do FTP" border="0" /></a>
			'.
			*/
			'
				
			<img src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />
			
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'Cytat:\n<blockquote>\', \'\n</blockquote>\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/quote.png" align="bottom" alt="Wstaw cytat" title="Wstaw cytat" border="0" /></a>

			<img src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />
			
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'<code java>\', \'</code>\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/source_java.png" align="bottom" alt="Wstaw kod Javy" title="Wstaw kod Javy" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'<code cpp>\', \'</code>\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/source.png" align="bottom" alt="Wstaw kod C++" title="Wstaw kod C++" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'<code sql>\', \'</code>\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/source_sql.png" align="bottom" alt="Wstaw kod SQL" title="Wstaw kod SQL" border="0" /></a>
			<a tabindex="-1" href="javascript:void(0);" onclick="surroundText(\'<code php>\', \'</code>\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/source_php.png" align="bottom" alt="Wstaw kod PHP" title="Wstaw kod PHP" border="0" /></a>

			<img src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\''.$strBugText.'\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/bug_form.png" align="bottom" width="22" height="22" alt="Formatka błędu" title="Formatka błędu (kroki do powtórzenia itp)" border="0" /></a>
			
			<a tabindex="-1" href="javascript:void(0);" onclick="replaceText(\''.$strBugExtraInfoText.'\', \''.$textareaId.'\'); return false;">
				<img src="'.$baseurl.'plugins/dokuwiki/img/bug_extrainfo.png" align="bottom" width="22" height="22" alt="Formatka błędu" title="Formatka &quot;Informacje dodatkowe&quot;" border="0" /></a>
			
			<img src="'.$baseurl.'plugins/dokuwiki/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<span style="position:relative;top:-30px;left:-32px;float:right;"><a style="position:absolute;font-size:20px;margin-bottom:16px" title="Pomoc dotycząca formatowania tekstu" href="https://www.dokuwiki.org/pl:syntax" target="_blank">[?]</a></span>

		'.$strToolbarExtra;
	}
}
?>