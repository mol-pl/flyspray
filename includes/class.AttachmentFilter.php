<?php
class AttachmentConf
{
	public static function size(string $size, int $days, string $comment = null)
	{
		if (!is_string($comment)) {
			$title = "[$size]";
		} else {
			$title = "[$size] $comment";
		}
		return array(
			'short_name' => $title,
			'min_file_size' => $size,
			'min_closed_days' => $days,
		);
	}
}

/**
 * Filtering attachment list (setup group).
 */
class AttachmentFilter
{
	/**
	 * Filtering criteria for attachments.
	 * 
	 * @example Filtering by task type and category with different removal delay based on file name.
	 	array(
			'short_name' => 'data, non-doc',
			'task_type' => 'Service task',
			'product_category' => 'Data acquisition',
			'name_not_re' =>  "\\.(pdf|doc)",
			'min_closed_days' => 30,
		),
	 	array(
			'short_name' => 'data, doc',
			'task_type' => 'Service task',
			'product_category' => 'Data acquisition',
			'name_re' =>  "\\.(pdf|doc)",
			'min_closed_days' => 365,
	 	)
	 *
	 */
	private $config = array();
	private $ids_cat = array();
	private $ids_type = array();

	public function __construct(array $config = array()) {
		$this->config = $config;
	}

	/** Init internals. */
	public function init()
	{
		// init maps / cache.
		$this->ids_cat =  $this->map_cats();
		$this->ids_type = $this->map_types();
		
		// transform names to id-arrays
		// throws/dies when name is invalid
		$this->name_to_ids('product_category', $this->ids_cat);
		$this->name_to_ids('task_type', $this->ids_type);
		// 1M to bytes
		$this->size_to_byte('min_file_size');
	}

	/**
	 * Setup group.
	 */
	public function setup_group(array &$row)
	{
		$g = -1;
		foreach ($this->config as $gid => $group) {
			$matched = $this->matches_group($row, $group);
			if ($matched) {
				$g = $gid;
				break;	// first match is good
			}
		}
		$row['_gid'] = $g;
		$row['_gname'] = ($g < 0) ? '_none_' : $this->config[$g]['short_name'];
		return ($g >= 0);
	}

	/**
	 * Filter by days_closed.
	 * 
	 * @return true when row should be removed now.
	 */
	public function should_remove(array &$row)
	{
		if (!isset($row['_gid'])) {
			$this->setup_group($row);
		}
		$g = $row['_gid'];
		if (!isset($this->config[$g])) {
			return false;
		}
		$days_closed = $row['days_closed'];
		$min_closed_days = $this->config[$g]['min_closed_days'];

		return ($days_closed > $min_closed_days);
	}

	/** Prepares plain regexp for PHP. */
	private function prep_re(string $str)
	{
		$re = preg_replace('/#/', '\#', $str);
		return "#$re#i";
	}

	/** Group matching. */
	private function matches_group(array $row, array $group)
	{
		// lists match
		if (isset($group['product_category_ids'])) {
			if (!in_array($row['product_category'], $group['product_category_ids'])) {
				return false;	// no match
			}
		}
		if (isset($group['task_type_ids'])) {
			if (!in_array($row['task_type'], $group['task_type_ids'])) {
				return false;	// no match
			}
		}

		// orig_name partial match check
		if (isset($group['name_not_re'])) {
			$re = $this->prep_re($group['name_not_re']);
			if (preg_match($re, $row['orig_name'])) {
				return false;	// no match
			}
		}
		if (isset($group['name_re'])) {
			$re = $this->prep_re($group['name_re']);
			if (!preg_match($re, $row['orig_name'])) {
				return false;	// no match
			}
		}

		// size matching
		if (isset($group['min_file_size'])) {
			if (intval($row['file_size']) > $group['min_file_size']) {
				return true;
			}
			return false;
		}

		return true;
	}

	/** Transform name config. */
	private function name_to_ids(string $key, array &$ids)
	{
		foreach ($this->config as &$conf) {
			if (!isset($conf[$key])) {
				continue;
			}
			$name = $conf[$key];
			if (!isset($ids[$name])) {
				die ("[ERROR] unknown name ($name) for $key.");
			}
			$ids_key = $key.'_ids';
			$conf[$ids_key] = $ids[$name];
		}
	}

	/** Transform size config. */
	private function size_to_byte(string $key)
	{
		foreach ($this->config as &$conf) {
			if (!isset($conf[$key])) {
				continue;
			}
			$bytes = $this->letter_to_byte($conf[$key]);
			$conf[$key] = $bytes;
		}
	}
	/** Transform 1MB etc to bytes. */
	public function letter_to_byte(string $size)
	{
		$size = $size;
		$re = '#([0-9]+)([KMG])#';
		$letter_to_byte = array('K'=>1024, 'M'=>1024*1024, 'G'=>1024*1024*1024);
		if (!preg_match($re, $size, $m)) {
			$bytes = intval($size);
		} else {
			$mul = $letter_to_byte[$m[2]];
			$bytes = intval($m[1]) * $mul;
		}
		return $bytes;
	}

	/** Min of all filters. */
	public function min_days_closed()
	{
		return $this->min_conf('min_closed_days');
	}
	/** Min int from config. */
	private function min_conf(string $key)
	{
		$min = PHP_INT_MAX;
		foreach ($this->config as &$conf) {
			if (!isset($conf[$key])) {
				continue;
			}
			$val = intval($conf[$key]);
			if ($val < $min) {
				$min = $val;
			}
		}
		return $min;
	}

	/** Get categories map. */
	public function map_cats()
	{
		global $db;

		$query = $db->Query("SELECT  category_id as id, category_name as name
				FROM  {list_category}
				ORDER BY  2,1
			"
		);
		return $this->map($query);
	}

	/** Get categories map. */
	public function map_types()
	{
		global $db;

		$query = $db->Query("SELECT  tasktype_id as id, tasktype_name as name
				FROM  {list_tasktype}
				ORDER BY  2,1
			"
		);
		return $this->map($query);
	}

	/** Create name:id map with possible duplicates. */
	private function map($query)
	{
		global $db;

		$map = array();
		while ($row = $db->FetchRow($query)) {
			$id = $row['id'];
			$name = $row['name'];
			if (!isset($map[$name])) {
				$map[$name] = array();
			}
			$map[$name][] = $id;
		}

		return $map;
	}

}