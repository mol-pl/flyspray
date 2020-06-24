<?php
  /*********************************************************\
  | Show export for Gantt - extra functions					|
  | ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~                 |
  \*********************************************************/

if (!defined('IN_FS')) {
	die('Do not access this file directly.');
}

/**
	Checks if the string is valid CSV of numbers
	
	@param $ids CSV list of ids (or any int)
	
	@return parsed CSV that should be safe for e.g. DB processing, if the list was empty then returns empty string
*/
function ParseIdsCSVForSafety ($ids)
{
	if (empty($ids))
	{
		return '';
	}

	$ids = explode(",", $ids);
	foreach($ids as &$id)
	{
		$id = intval($id);
	}
	$ids = implode(",", $ids);

	return $ids;
}


/**
	Get standard and raw interval from task
	
	Gets strtotime style interval (+X hours/days/weeks) from task details
	
	@param $task Task details array
	
	@return
		array(
			'std' => translated, strtotime-readable string interval (e.g. "+1 days"),
			'raw' => untranslated, human-readable string interval,
		);
		
	@note
		The std_time_string may be empty if the parser was
		unable to translate the raw_time_string
*/
function GetIntervalsFromTask ($task)
{
	$std_time_string = '';
	if (preg_match("#(?:czas wykonania|time estimation):[ \t]*(.+)#", $task['detailed_desc'], $raw_time_string))
	{
		$raw_time_string = trim($raw_time_string[1]);
		$std_time_string = InternalTaskInterval2StdInterval ($raw_time_string, empty($task['assigned_count']) ? 1 : $task['assigned_count']);
	}
	else
	{
		$raw_time_string = '';
	}
	
	return array(
		'std' => $std_time_string,
		'raw' => $raw_time_string,
	);
}

/**
	Divides interval.
	
	@note Minimum time assumed is 1 hour (+1 hour / 2 = +1 hour).
	@note Interval is rounded to days when longer then two days (+7 days / 2 = 4 days).
	@note One day is treated as 8 hour long (1 day / 2 = 4 h)
*/
function TimeIntervalDivider($time_string, $interval_divider)
{
	$now = time();
	$then = strtotime($time_string, $now);
	
	// original interval difference
	$diff = abs($now - $then);
	$aDay = 60*60*24;
	$days = ($diff < $aDay) ? 0 : ceil($diff / $aDay);
	$hours = ceil(($diff - $days * $aDay) / (60*60));

	//echo "\n now: ".date("Y-m-d H:i:s", $now);
	//echo "\n then: ".date("Y-m-d H:i:s", $then);
	//echo "\n diff [h]: ".($diff / (60*60);
	
	// One day is treated as 8 hour long 
	$hours += $days * 8;
	
	// Divide hours
	$hours_divided = $hours / $interval_divider;
	
	// Transform back to days when needed
	// (Interval is rounded to days when longer then two days or is a day)
	if ($hours_divided >= 16 or $hours_divided == 8)
	{
		$days = ceil($hours_divided / 8);
	}
	else
	{
		$days = 0;
		$hours = ceil($hours_divided);
	}
	
	/**
	// real hours to workday hours
	if (empty($days) && preg_match ('#[0-9] hours?#', $time_string))
	{
		//echo "\n pre: +$hours hours";
		$hours = ceil($hours * 8/24);
		//echo "\n post: +$hours hours";
	}
	/**/
	
	return empty($days) ? "+$hours hours" : "+$days days";
}

/**
	Translate internaly styled task interval to more standard interval
	
	Bascially translates (G/D/T/M/+/-) to strtotime style (+X hours/days/weeks).
	
	@note
		G/D/T/M +/- are treated in a very fuzzy way
		X G/D/T/M are treated as exact values
	
	@param $time_string A string to be translated
	@param $If false then the string was not matched
	
	@return
		translated interval string (if OK)
		empty if the parser was unable to translate the string
*/
function InternalTaskInterval2StdInterval ($time_string, $interval_divider = 1)
{
	// If false then the string was not matched
	$time_string_matched = true;
	switch ($time_string)
	{
		case "G-":    $time_string = "+1 hours"; break;
		case "G+":    $time_string = "+3 hours"; break;
		case "D-":    $time_string = "+1 days"; break;
		case "D+":    $time_string = "+3 days"; break;
		case "D+/T-": $time_string = "+5 days"; break;
		case "T-":    $time_string = "+7 days"; break;
		case "T+":    $time_string = "+21 days"; break;
		case "M-":    $time_string = "+30 days"; break;
		case "M+":    $time_string = "+90 days"; break;
		default:
			if (preg_match('#([0-9]+)\s?([GDTM])#', $time_string, $arrMatches))
			{
				$time_string = $arrMatches[1];
				switch ($arrMatches[2])
				{
					case "G": $time_string .= " hours"; break;
					case "D": $time_string .= " days"; break;
					case "T": $time_string .= " weeks"; break;
					case "M": $time_string .= " months"; break;
					default:
						$time_string_matched = false;
					break;
				}
			}
			else
			{
				$time_string_matched = false;
			}
		break;
	}
	
	if ($time_string_matched)
	{
		if ($interval_divider > 1)
		{
			$time_string = TimeIntervalDivider($time_string, $interval_divider);
		}
		return $time_string;
	}
	else
	{
		return '';
	}
}

/**
	Basic freedays parsing (based on freedays GET param)
	
	@see SkipFreedays
	
	@param $taskAssignee Name of asignee
	
	@return $freedays array
*/
function GetFreedays ($taskAssignee='')
{
	$freedays = Get::val('freedays');
	if (is_string($freedays))
	{
		$freedays = explode(",", $freedays);
	}
	else if (is_array($freedays))
	{
		$freedays_get = array_merge(array(), $freedays);	// clone
		$freedays = array();
		// for all
		if (!empty($freedays_get[0]))
		{
			$freedays = array_merge($freedays, explode(",", $freedays_get[0]));
		}
		// for this asignee
		if (!empty($freedays_get[$taskAssignee]))
		{
			$freedays = array_merge($freedays, explode(",", $freedays_get[$taskAssignee]));
		}
	}
	
	return $freedays;
}

/**
	Basic freedays parsing for all asignees (based on freedays GET param)
	
	@see SkipFreedays
	
	@return $freedays asociative array
*/
function GetAllFreedays ()
{
	$freedays = Get::val('freedays');
	if (is_string($freedays))
	{
		$freedays[0] = $freedays;
	}
	if (empty($freedays))
	{
		return array();
	}
	
	$freedays_get = array_merge(array(), $freedays);	// clone
	$freedays = array();
	foreach($freedays_get as $key=>$val)
	{
		if (empty($val))
		{
			continue;
		}
		$freedays[$key] = explode(",", $val);
	}
	
	return $freedays;
}

/**
	Parse ranges in freedays array (e.g. previously returned from GetFreedays)
	
	@see GetFreedays
	
	@param $freedays freedays array (of date strings or date ranges); date ranges are date strings separated by "::"
	@param $parseToTimestamp
		If true ranges will be populated as day-timestamps into the returned array
		otherwise
			days will be changed to single day range like
			and ranges will be returned as subarrays containing strings like: array('start'=>'2012-01-03','end'=>'2012-01-09'),
		
	@note Date strings SHOUD be strtotime-readable for $parseToTimestamp to work.
	
	@return $freedays array
*/
function ParseFreedaysRanges ($freedays, $parseToTimestamp = true)
{
	if (!empty($freedays))
	{
		$extra_freedays = array();
		foreach ($freedays as $key=>$freeday)
		{
			if (strpos($freeday, "::")!==false)
			{
				$freeday = explode("::", $freeday);
				$freedays_start = $freeday[0];
				$freedays_end = $freeday[1];
			}
			else
			{
				$freedays_start = $freedays_end = $freeday;
			}

			// parsing to day-timestamps
			if ($parseToTimestamp)
			{
				// if non-range then only parse
				if ($freedays_start == $freedays_end)
				{
					$freedays[$key] = strtotime($freeday);
				}
				// range - add days in range one by one
				else
				{
					unset($freedays[$key]);
					$freedays_start = strtotime($freedays_start);
					$freedays_end = strtotime($freedays_end);
					for($day=$freedays_start; $day<=$freedays_end; $day+=3600*24)
					{
						$extra_freedays[] = $day;
					}
				}
			}
			// string ranges
			else
			{
				$freedays[$key] = array(
					'start'=>$freedays_start,
					'end'=>$freedays_end
				);
			}
		}
		$freedays = array_merge($extra_freedays, $freedays);
	}

	return $freedays;
}

/**
	For debugging
*/
function TimestampsToDate($stampsarr)
{
	foreach($stampsarr as &$stamp)
	{
		if (is_int($stamp))
		{
			$stamp = date('Y-m-d H.i.s', $stamp);
		}
	}
	
	return $stampsarr;
}

/**
	Skip freedays (based on freedays GET param)
	
	Some assumption taken:
	* if the first day is a freeday then $task_start_dt is changed
	* freedays are strtotime-readable CSV containg free days
	* freedays CSV can contain ranges Y-m-d::Y-m-d (from::to dates)
	* freedays can be
	** only for all (&freedays=CSV)
	** for specific asignee (e.g. &freedays[Nux]=CSV)
	** for all and more for specific asignee (e.g. &freedays[]=CSV&freedays[Nux]=CSV)
	
	@param $task_start_dt start time of the task (Unix timestamp)
	@param $task_end_dt so far calculated $task_end_dt
	@param $taskAssignee Name of asignee
	
	@return end time of the task (Unix timestamp)
*/
function SkipFreedays (&$task_start_dt, &$task_end_dt, $taskAssignee='')
{
	// basic freedays parse
	$freedays = GetFreedays($taskAssignee);
	
	// add weekends (potential)
	AddWeekendsAsFree ($freedays, $task_start_dt, $task_end_dt);

	// if empty then return unchanged
	if (empty($freedays))
	{
		return $task_end_dt;
	}
	
	// parsing to timestamps and support for ranges Y-m-d::Y-m-d (from::to)
	$freedays = ParseFreedaysRanges($freedays);
	
	// uniqueness and sorting
	$freedays = array_unique($freedays);
	asort( $freedays );
	
	/**
	echo "\n\n<!--DEBUG pre";
	var_export(
		array(
			'freedays'=>TimestampsToDate($freedays),
			'start'=>date("c", $task_start_dt),
			'  end'=>date("c", $task_end_dt),
		)
	);
	echo "-->";
	/**/
	
	// skipping
	foreach ($freedays as $freeday)
	{
		// is starting in the freeday
		if ($task_start_dt == $freeday)
		{
			$task_start_dt = strtotime("+1 day", $task_start_dt);
			$task_end_dt = strtotime("+1 day", $task_end_dt);
		}
		// is between
		else if ($task_start_dt < $freeday && $freeday <= $task_end_dt)
		{
			$task_end_dt = strtotime("+1 day", $task_end_dt);
		}
	}

	/**
	echo "\n<!--DEBUG post";
	var_export(
		array(
			'start'=>date("c", $task_start_dt),
			'  end'=>date("c", $task_end_dt),
		)
	);
	echo "-->";
	/**/
	return $task_end_dt;
}

/**
	Is given \a time on weekend?
*/
function IsInWeekend ($time)
{
	$day_of_week = date("w", $time);
	// sunday || saturday
	if ($day_of_week==0 || $day_of_week==6)
	{
		return true;
	}
	return false;
}

/**
	Add weekends to freedays
	
	@param $task_start_dt start time of the task (Unix timestamp)
	@param $task_end_dt so far calculated $task_end_dt
	
	@return end time of the task (Unix timestamp)
*/
function AddWeekendsAsFree (&$freedays, $task_start_dt, $task_end_dt)
{
	if (empty($freedays))
	{
		$freedays = array();
	}

	// max days the task will take
	$maxdays = count($freedays) + ceil( ($task_end_dt - $task_start_dt) / (3600 * 24) );
	// including weekends
	$maxdays += ceil($maxdays * 2/7) + 2;
	// add weekends (potential)
	for ($i=0; $i<=$maxdays; $i++)
	{
		$t = ($i == 0) ? $task_start_dt : strtotime("+$i days", $task_start_dt);
		
		if ( IsInWeekend($t) )
		{
			array_push($freedays, date('Y-m-d', $t));
		}
	}
}

/**
	Skip weekends
	
	@param $task_start_dt start time of the task (Unix timestamp)
	@param $task_end_dt so far calculated $task_end_dt
	@param $basic_task_duration_hours original task_duration_hours (because weekends are skippend only for tasks shorter then 1 week originaly)
	
	@return end time of the task (Unix timestamp)
	
	@note Not used anymore. See AddWeekendsAsFree instead.
*/
function SkipWeekend ($task_start_dt, $task_end_dt, $basic_task_duration_hours)
{
	// skip weekends for tasks shorter then 1 week
	if ($basic_task_duration_hours<24*7)
	{
		$extra_time = "";
		$end_day_of_week = date("w", $task_end_dt);
		$week_num_start = date("W", $task_start_dt);
		$week_num_end = date("W", $task_end_dt);
		// sunday
		if ($end_day_of_week==0)
		{
			$extra_time = "+1 day";
		}
		// saturday
		else if ($end_day_of_week==6)
		{
			$extra_time = "+2 days";
		}
		// starting e.g. on friday and ending somewhere in next week
		else if ($week_num_start<$week_num_end)
		{
			$extra_time = "+2 days";
		}
		// add extra time if needed
		if (!empty($extra_time))
		{
			$task_end_dt = strtotime($extra_time, $task_end_dt);
		}
	}

	return $task_end_dt;
}

/**
	Calculate task end date
	
	Some assumption taken:
	* work day is 8 hours long (task taking 10 hours will take 1 day +2 hours)
	* tasks longer then one day always finishes at the end of work day
	* weekends are skippend only for tasks shorter then 1 week
	
	@param $task_start_dt Start time of the task (Unix timestamp)
	@param $std_interval Strtotime-readable interval string
	@param $taskAssignee Name of asignee (for freedays)
	
	@return end time of the task (Unix timestamp)
*/
function WorkTaskEndDT (&$task_start_dt, $std_interval, $taskAssignee='')
{
	$task_end_dt = strtotime($std_interval, $task_start_dt);
	$task_duration_hours = ($task_end_dt - $task_start_dt)/3600;
	// what time would it be? (8 o'clock is max)
	$task_end_hourmin = date("H.i", $task_end_dt);
	// move to next day if over end of work day
	if (floatval($task_end_hourmin)>8.0)
	{
		$task_end_dt = strtotime("+1 day -8 hours", $task_end_dt);
	}
	// move to end of work day not end of real day
	else if ($task_end_hourmin=='00.00')
	{
		$task_end_dt = strtotime("-1 day +8 hours", $task_end_dt);
	}
	// tasks longer then one day always finishes at the end of work day
	else if (floatval($task_end_hourmin)<8.0 && $task_duration_hours>24)
	{
		$task_end_date = date("Y-m-d", $task_end_dt);
		$task_end_dt = strtotime("$task_end_date +8 hours");
	}
	// skip weekneds and other freedays
	//$task_end_dt = SkipFreedays ($task_start_dt, $task_end_dt, $taskAssignee);
	//$task_end_dt = SkipWeekend ($task_start_dt, $task_end_dt, $task_duration_hours);
	$task_end_dt = SkipFreedays ($task_start_dt, $task_end_dt, $taskAssignee);
	
	return $task_end_dt;
}

/**
	Calculate next task start date
	
	Some assumption taken:
	* work day is 8 hours long
	* if a task takes one day to complete it should be seen as from 2011-01-01 to 2011-01-01
	* if a task finishes at the end of day it should then start from 2011-01-02
	
	@param $task_start_dt start time of the task (Unix timestamp)
	
	@return start time of the next task (Unix timestamp)
*/
function WorkTaskNextStartDT ($task_end_dt)
{
	$task_start_dt = $task_end_dt;
	
	// move next start to next day if over or on end of work day
	// why? because if a task takes one day to complete it should be seen as from 2011-01-01 to 2011-01-01
	// and next task should then start from 2011-01-02
	$task_start_hourmin = date("H.i", $task_end_dt);
	if (floatval($task_start_hourmin)>=8.0)
	{
		$task_start_dt = strtotime("+1 day -8 hours", $task_start_dt);
	}
	
	return $task_start_dt;
}

?>
