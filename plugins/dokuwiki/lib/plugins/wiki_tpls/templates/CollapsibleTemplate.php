<?php
/**
	Basic usage:
	{{tpl:Collapsible-top}}
	{{tpl:Collapsible-start|header=Archive}}
	 Some content to collapse.
	{{tpl:Collapsible-end}}
	{{tpl:Collapsible-start|header=Current}}
	 Some other content to collapse.
	{{tpl:Collapsible-end}}
	{{tpl:Collapsible-bottom}}
	
**/
class CollapsibleTemplate extends AbstractWikiTemplate {
	/**
		Based on the template name say if it's mine.
	*/
	public static function isMine($tplName) {
		switch ($tplName)
		{
			case 'Collapsible-start':
			case 'Collapsible-end':
			case 'Collapsible-top':
			case 'Collapsible-bottom':
			return true;
		}
		return false;
	}

	/**
		Parse data
	*/
	public function parse($tpl_params, $plugin_conf) {
		if (!empty($tpl_params['header'])) {
			$data['html'] = $this->prepareHeader($tpl_params['header'], $tpl_params);
		} else {
			switch ($this->tplName) {
				case 'Collapsible-top'; $data['html'] = $this->prepareTop($tpl_params); break;
				case 'Collapsible-bottom'; $data['html'] = $this->prepareBottom($tpl_params); break;
				default:
				case 'Collapsible-end'; $data['html'] = $this->prepareFooter($tpl_params); break;
			}
		}
		
		return $data;
	}

	/**
		Top HTML.
	*/
	private function prepareTop($tpl_params = array()) {
		$data = "<div class='collapsible-container'>";
		return $data;
	}
	/**
		Bottom HTML.
	*/
	private function prepareBottom($tpl_params = array()) {
		$data = "</div>";
		return $data;
	}

	/**
		Header HTML.
	*/
	private function prepareHeader($header, $tpl_params = array()) {
		$data = "";
		if (!empty($tpl_params['top'])) {
			$data .= $this->prepareTop();
		}
		$data .= "<h3>{$header}</h3><div class='collapsible-section-content'>";
		return $data;
	}
	
	/**
		Footer HTML.
	*/
	private function prepareFooter($tpl_params = array()) {
		$data = "";
		$data .= "</div>";
		if (!empty($tpl_params['bottom'])) {
			$data .= $this->prepareBottom();
		}
		return $data;
	}
}