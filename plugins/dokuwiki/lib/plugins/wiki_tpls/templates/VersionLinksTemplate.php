<?php
/**
	Basic usage:
	{{tpl:Rejsz-linki-wersja|proj_id=5|ver_id=75}}
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75}}
	
	Two versions in links (you can have as many as you want):
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75|ver_id=90}}
	
	Gantt
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75|gantt=auto}} -- basic generator
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75|gantt=auto|gantt_start=2013-09-16}} - start given date
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75|gantt=auto:2013-09-16}} - shorthand syntaxt for start date
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75|gantt=wiki:Harmonogram_-_MOL_13.10}} - wiki link syntax
	
	ISO
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75|karta_count=123}} - extra ISO project link count = iso project version
	
	Dev links
	{{tpl:Bugz-linki-wersja|proj_id=5|ver_id=75|ver_id=90|dev=Maciej|dev=Paweł}} - add dev links after TODO devs
**/
class VersionLinksTemplate {
	private $tplName;

	public function __construct($tplName)
	{
		$this->tplName = $tplName;
	}

	/**
		Based on the template name say if it's mine.
	*/
	public static function isMine($tplName) {
		switch ($tplName)
		{
			case 'Rejsz-linki-wersja':
			case 'Bugz-linki-wersja':
			return true;
		}
		return false;
	}

	/**
		Parse data
	*/
	public function parse($tpl_params, $plugin_conf) {
		$tpl_name = $this->tplName;
		
		$isRejszTemplate = strpos($tpl_name, 'Rejsz') === 0;
		
		// podstawa linka i pierwszy wymagany parametr
		if ($isRejszTemplate)
		{
			$base_link = 'rz>index.php?string=&do=index&project='.$tpl_params['proj_id'];
		}
		else
		{
			$base_link = 'fs>index.php?string=&do=index&project='.$tpl_params['proj_id'];
		}
		
		// drugi parametr może być wielokrotny...
		if (is_array($tpl_params['ver_id']))
		{
			foreach($tpl_params['ver_id'] as $ver)
			{
				$base_link .= '&due[]='.$ver;
			}
		}
		else
		{
			$base_link .= '&due[]='.$tpl_params['ver_id'];
		}

		// devs...
		$todo_dev_base = $base_link . $plugin_conf['devs_status'];
		$devs = '';
		if (!empty($tpl_params['dev']))
		{
			if (!is_array($tpl_params['dev']))
			{
				$tpl_params['dev'] = array($tpl_params['dev']);
			}
			$devs_tmp = array();
			foreach($tpl_params['dev'] as $dev)
			{
				$devs_tmp[] = '[['.$todo_dev_base.$plugin_conf['single_dev_order'].'&dev='.$dev.'|'.$dev.']]';
			}
			$devs = ': '.implode(', ', $devs_tmp);
		}
		
		// gantt
		$gantt_link = '';
		$gantt_mode = 'auto';
		$gantt_title = 'Harmonogram (auto)';
		if (!empty($tpl_params['gantt']))
		{
			$gantt_params = explode(":", $tpl_params['gantt']);
			if (count($gantt_params) > 1)
			{
				$gantt_mode = $gantt_params[0];
				unset($gantt_params[0]);
				$gantt_param = implode(":", $gantt_params);
				switch ($gantt_mode)
				{
					case 'wiki':
						$gantt_title = 'Harmonogram (wiki)';
						$gantt_link = 'molwiki>'.$gantt_param;
					break;
					case 'auto':
						$tpl_params['gantt_start'] = $gantt_param;
					break;
				}
			}
			if ($gantt_mode == 'auto')
			{
				$gantt_link = preg_replace('#([&?])do=index(?=[&?]|$)#', '$1do=gantt_export', $base_link);
				if (!empty($tpl_params['gantt_start']))
				{
					$gantt_link .= '&gantt_base_date='.$tpl_params['gantt_start'];
				}
			}
		}
		
		// iso
		$iso_links = '';
		if (!empty($tpl_params['karta_count']))
		{
			
			$iso_links = ' • '.str_replace('{count}', $tpl_params['karta_count'], $plugin_conf['iso_link_format']);
		}
		
		$data = ''
			.'[['.$base_link.'|Otwarte]]'
			.' • [['.$todo_dev_base.$plugin_conf['devs_order'].'|TODO programiści]]'.$devs
			.' • [['.$base_link.'&status[]=4|TODO testerzy]]'
			.(!$isRejszTemplate ? '' : ' • [['.$base_link.'&status[]=13&status[]=1&status[]=9|TODO PM]]')
			.' • [['.$base_link.'&status[]=|Wszystkie]]'
			.(empty($gantt_link) ? '' : ' • [['.$gantt_link.'|'.$gantt_title.']]')
			.$iso_links
		;
		
		return $data;
	}
}