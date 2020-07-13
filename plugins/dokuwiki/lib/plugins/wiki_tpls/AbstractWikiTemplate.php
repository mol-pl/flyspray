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
		Parse data.
		
		Note! This is used during handling pre-parse of data.
		It is cached and cannot be used to handle dynamic data (like current user data).
		
		@note If some parameter occures multiple times int the template then it's value will be an array
			e.g. for `{{My-template|param=1|param=2}}` the `tpl_params['param']` value will be equal to `array(1,2)`.
	*/
	abstract public function parse($tpl_params, $plugin_conf);

	/**
		Is reRender is implemented.
		
		Override in child class if needed.
		
		Tells renderer that `reRender` should called during each rendering of the page.
	*/
	public static function hasReRender()
	{
		return false;
	}
	/**
		Re-render during page rendering.
		
		Avoid heavy computations.
		
		@param $data is dokuwiki code or whatever returned from the parse function.
		@returns dokuwiki code.
	*/
	public function reRender($data) {
		return $data;
	}

}