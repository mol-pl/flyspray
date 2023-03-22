<?php
require_once BASEDIR . '/includes/class.AttachmentFilter.php';

/**
 * Remove old attachments.
 */
class AttachmentRemoval
{
	public $base_path = './attachments/';

	/** CSV log with processed files.  */
	public $log_path = './cache/.logextra/del.log.csv';
	public $log_fp = null;

	/** All files list (control). */
	private $files = array();

	/** Filter */
	public AttachmentFilter $filter;

	/** Pre-init. */
	public function __construct(array $filter_config = array()) {
		$this->filter = new AttachmentFilter($filter_config);
	}

	/**
	 * Main.
	 */
	public function run_schedule(array $options)
	{
		$this->init_once($options);
		
		$total = array(
			'batches' => 0,
			'removed' => 0,
			'checked' => 0,
			'1st_closed_dt' => 0,
			'last_closed_dt' => 0,
		);
		
		// run batches
		do {
			$result = $this->run_batch($total['last_closed_dt']);
		
			$total['batches']++;
			$total['removed'] += $result['removed'];
			$total['checked'] += $result['checked'];
			if ($total['1st_closed_dt'] === 0) {
				$total['1st_closed_dt'] = $result['1st_closed_dt'];
			}
			$total['last_closed_dt'] = $result['last_closed_dt'];

			$total['1st (all)'] = date('Y-m-d', $total['1st_closed_dt']);
			$total['1st (batch)'] = date('Y-m-d', $result['1st_closed_dt']);
			$total['last'] = date('Y-m-d', $result['last_closed_dt']);

			echo "\n[INFO] after batch: " . json_encode(array_filter($total, function($key) {
				return !preg_match('#_dt$#', $key);
			}, ARRAY_FILTER_USE_KEY));
			
			// removed enough
			if ($total['removed'] >= $this->batch_size) {
				echo "\n[DEBUG] removed enough: {$total['removed']}.";
				break;
			}
			// batch was less then size
			if ($result['checked'] < $this->batch_size) {
				echo "\n[DEBUG] got too little rows (batch larger then available records): {$result['checked']}.";
				break;
			}
			// loops count limit
			if ($total['batches'] >= $this->max_batches) {
				echo "\n[DEBUG] batches count limit reached: {$total['batches']}.";
				break;
			}
		} while(true);
		
		return $total;
	}

	/**
	 * Prepare batches.
	 * 
	 * @param attach_del options (can be from flyspray.conf.php)
	 */
	private function init_once(array $options)
	{
		global $db;
		// override FS default (which is ADODB_FETCH_BOTH)
		$db->dblink->SetFetchMode(ADODB_FETCH_ASSOC);

		// clear cache for file_exists etc
		clearstatcache();
		// read files from attachments dir
		$this->read_files($this->base_path);

		// default options
		$min_days_closed = 10;	// min days for SQL
		$batch_size = 1000;
		$max_batches = 30;
		$is_closed = 1;
		if (isset($options['batch_size']) && intval($options['batch_size']) >= 10) {
			$batch_size = intval($options['batch_size']);
		}
		if (isset($options['max_batches']) && intval($options['max_batches']) > 0) {
			$max_batches = intval($options['max_batches']);
		}

		// init filter
		$this->filter->init();
		$min_days_closed = $this->filter->min_days_closed();

		$this->min_days_closed = $min_days_closed;
		$this->batch_size = $batch_size;
		$this->max_batches = $max_batches;
		$this->is_closed = $is_closed;
	}

	private int $min_days_closed;
	/** Max number of rows per batch. */
	private int $batch_size;
	/** Max number of batches in one schedule. */
	private int $max_batches;
	private int $is_closed;

	/**
	 * Run single batch.
	 */
	private function run_batch(int $min_dt_closed)
	{
		global $db;

		$min_days_closed = $this->min_days_closed;
		$batch_size = $this->batch_size;
		$is_closed = $this->is_closed;

		$max_dt_closed = strtotime("-$min_days_closed days");
		$this->batch_info($min_dt_closed, $max_dt_closed);

		// main query
		$query = $db->Query("SELECT  a.*
			, date_closed
			, floor((extract(epoch from now()) - date_closed) / 3600 / 24) as days_closed
			, task_type, product_category
				FROM  {attachments} a
			INNER JOIN  {tasks}	 t ON a.task_id = t.task_id
				WHERE is_removed = 0 AND t.is_closed = ? AND date_closed <= ? AND date_closed > ?
			ORDER BY date_closed, a.task_id, a.date_added
			LIMIT $batch_size
			"
			, array($is_closed, $max_dt_closed, $min_dt_closed)
		);


		$checked = 0;
		$removed = 0;
		$first_closed_dt = 0;	// max because ORDER BY date_closed ASC
		$last_closed_dt = 0;	// max because ORDER BY date_closed ASC
		// $rows = $db->FetchAllArray($query);
		while ($row = $db->FetchRow($query)) {
			$checked++;

			if (!is_array($row) || empty($row)) {
				var_export($row);
				die("\n[ERROR] Invalid row");
			}

			// type mapping (note that all numbers are strings in Adodb rows)
			$row['date_closed'] = intval($row['date_closed']);
			$row['days_closed'] = $row['date_closed'] == 0 ? 0 : intval($row['days_closed']);

			$last_closed_dt = $row['date_closed'];
			if ($first_closed_dt === 0) {
				$first_closed_dt = $row['date_closed'];
			}

			// filtering and removal group
			$has_group = $this->filter->setup_group($row);
			$should_remove = false;
			if ($has_group) {
				$should_remove = $this->filter->should_remove($row);
			}

			// remove (remove file from disk and register removal in metadata)
			if ($should_remove) {
				$removed += $this->remove_att($row);
			}
		}
		if ($removed > 0) {
			echo $this->data_totsv($this->att_metainfo(true));
		}
		$this->batch_info($min_dt_closed, $max_dt_closed, true);
		echo "\n[INFO] Removed $removed/$checked.";
		return array(
			'checked' => $checked,
			'removed' => $removed,
			'1st_closed_dt' => $first_closed_dt,
			'last_closed_dt' => $last_closed_dt,
		);
	}

	/** Batch options info */
	private function batch_info(int $min_dt_closed, int $max_dt_closed, $debug = false)
	{
		echo "\n" . ($debug ? '[DEBUG]' : '[INFO]');
		echo " date min: " . date('Y-m-d', $min_dt_closed) . " (epoch: $min_dt_closed)";
		echo "; date max: " . date('Y-m-d', $max_dt_closed) . " (epoch: $max_dt_closed)";
		echo "; batch: {$this->batch_size}.";
	}

	/**
	 * Remove attachment file.
	 * 
	 * Remove a physical file but keep metadata.
	 * Update metadata with removal date and set removal status.
	 */
	function remove_att($row)
	{
		global $db;

		$file_name = $row['file_name'];
		$full_path = $this->base_path . $file_name;
		$exists = file_exists($full_path);

		// check exists in array as a control
		$exists_in_dir = in_array($file_name, $this->files);
		// report inconsistency problems
		if ($exists_in_dir != $exists) {
			var_export($row);
			die("[ERROR] Note! Existance inconsitency for $file_name; (dir)$exists_in_dir != $exists(exist).");
		}

		if (!$exists) {
			// update removed state
			$db->Query("UPDATE  {attachments}
					SET is_removed = 1
					WHERE  attachment_id = ?
				",
				array($row['attachment_id'])
			);
			$this->log_row($row, $exists);
			return 0;
		}

		// remove file from disk
		unlink($full_path);

		// Update the database with the time sent
		$db->Query("UPDATE  {attachments}
				SET  date_removed = ?
				,  is_removed = 1
				WHERE  attachment_id = ?
			",
			array(time(), $row['attachment_id'])
		);
		// removal log
		$this->log_row($row, $exists);
		// removal info
		echo $this->data_totsv($this->att_info($row, $exists, true, true));
		return 1;
	}

	/** Read files list from attachments dir */
	private function read_files($path)
	{
		$exclusions = array('.', '..',
			'.htaccess',
			'index.html',
		);
		$this->files = array_diff(scandir($path), $exclusions);
	}

	/** Init log dir */
	private function init_log()
	{
		$dir = dirname($this->log_path);
		if (!is_dir($dir)) {
			mkdir($dir);
		}
	}

	/** Log row info */
	function log_row($row, $exists = null) {
		$add_meta = false;
		if (!file_exists($this->log_path)) {
			$add_meta = true;
		}
		if (is_null($this->log_fp)) {
			$this->init_log();
			$this->log_fp = fopen($this->log_path, 'a+');
			if ($this->log_fp === false) {
				die ("[ERROR] Unable to open log {$this->log_path}");
			}
		}

		$fp = $this->log_fp;

		if ($add_meta) {
			fputcsv($fp, $this->att_metainfo());
		}
		fputcsv($fp, $this->att_info($row, $exists));
	}

	/**
	 * Attachment information.
	 */
	function att_info($row, $exists = null, $short = false, $padded=false) {
		$exists_display = '-';
		$date_removed = '';
		if (!is_null($exists)) {
			$exists_display = $exists ? 'y' : 'n';
			if ($exists) {
				$date_removed = date('Y-m-d');
			}
		}
		$date_closed = date('Y-m-d', $row['date_closed']); //, $extended = false, $default = '');

		$data = array (
			$padded ? sprintf('%7d', $row['task_id']) : $row['task_id'],
			$exists_display,
			$date_removed,
			$date_closed,
			$padded ? sprintf('%3d', $row['days_closed']) : $row['days_closed'],
			$padded ? sprintf('%2d', $row['task_type']) : $row['task_type'],
			$padded ? sprintf('%3d', $row['product_category']) : $row['product_category'],
			round($row['file_size']/1024/1204, 1),
			$padded ? sprintf('%-20s', $row['_gname']) : $row['_gname'],
		);
		if (!$short) {
			$data[] = $row['file_size'];
			$data[] = $row['file_type'];
			$data[] = $row['file_name'];
		}
		$data[] = $row['orig_name'];
		return $data;
	}
	function att_metainfo($short = false) {
		$data = array (
			"t_id",
			"exists?",
			"date_removed",
			"date_closed",
			'days_closed',
			"t_type",
			"t_cat",
			"sizeMB",
			"del_group",
		);
		if (!$short) {
			$data[] = 'file_size';
			$data[] = 'file_type';
			$data[] = 'file_name';
		}
		$data[] = 'orig_name';
		return $data;
	}
	/** Format data line csv/tsv. */
	function data_totsv($data) {
		return "\n" . implode("\t", $data);
	}
	/** Format data line csv/tsv. */
	function data_putcsv($fp, $data) {
		return "\n" . implode("\t", $data);
	}

}