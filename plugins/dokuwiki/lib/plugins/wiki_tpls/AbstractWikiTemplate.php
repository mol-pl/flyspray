<?php
/**
	Abstract for templates parsers implementation.

	@note Add all template classes in `templates` subfolder (they will be included automatically).
**/
abstract class AbstractWikiTemplate {
	protected $tplName;

	public function __construct($tplName)
	{
		$this->tplName = $tplName;
	}

	/**
		Based on the template name say if it's mine.
	*/
	abstract public static function isMine($tplName);

	/**
		Parse data
		
		@note If some parameter occures multiple times int the template then it's value will be an array
			e.g. for `{{My-template|param=1|param=2}}` the `tpl_params['param']` value will be equal to `array(1,2)`.
	*/
	abstract public function parse($tpl_params, $plugin_conf);
}