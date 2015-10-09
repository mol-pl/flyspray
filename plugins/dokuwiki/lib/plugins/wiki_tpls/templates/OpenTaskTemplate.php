<?php
/**
	Basic usage (main categories):
	{{tpl:Open-task}}
	
	Additional category (add as many as you want):
	{{tpl:Open-task|extra_cat=1}}	- by ID
	{{tpl:Open-task|extra_cat=Biblioteczka}}	- by name

	@note requires special `interwiki.conf` definition: `server    {PATH}?{QUERY}`
**/
class OpenTaskTemplate {
	private $tplName;

	public function __construct($tplName)
	{
		$this->tplName = $tplName;
	}

	/**
		Based on the template name say if it's mine.
	*/
	public static function isMine($tplName) {
		if ($tplName === 'Open-task') {
			return true;
		}
		return false;
	}

	/**
		Gets new task base.
		
		Append category ID directly to it.
	*/
	private function getNewTaskBaseUrl($project_id) {
		//return strtolower(FS_PREFIX_CODE) . '>?do=newtask&project='.$project_id.'&product_category=';
		/**/
		global $baseurl;
		$basePath = preg_replace('#.+//.*?/#', '/', $baseurl);
		return 'server>'. $basePath . '?do=newtask&project='.$project_id.'&product_category=';
		/**/
	}

	/**
		Parse data
	*/
	public function parse($tpl_params, $plugin_conf) {
		global $proj;
		
		$tpl_name = $this->tplName;
		
		// setup
		$project_id = $proj->id;
		$categories = $proj->listCategories($project_id, true, true, false);
		$base_link = $this->getNewTaskBaseUrl($project_id);
		
		$extra_cat_names = array();
		$extra_cat_ids = array();
		$extra_cats = is_array($tpl_params['extra_cat']) ? $tpl_params['extra_cat'] : array($tpl_params['extra_cat']);
		foreach($extra_cats as $extra_cat)
		{
			if (is_numeric($extra_cat)) {
				$extra_cat_ids[intval($extra_cat)] = 1;
			}
			else {
				$extra_cat_names[$extra_cat] = 1;
			}
		}
		
		// create doku-code
		$data = '';
		for ($i = 0; $i < count($categories); $i++) {
			$cat = $categories[$i];
			
			if ($cat['depth'] == 0
				|| isset($extra_cat_names[$cat['category_name']])
				|| isset($extra_cat_names[intval($cat['category_id'])])) {
				if (!empty($data)) {
					$data .= " • ";
				}
				$data .= "[[{$base_link}{$cat['category_id']}|{$cat['category_name']}]]";
			}
		}
		
		return $data;
	}
}