<?php

/**
	Standarized error log.
	
	Adds some information in header for diagnostics.
*/
function fs_error_log($content, $extra_header = "", $level = "ERROR") {
	global $user;
	
	$header = ''
		."\nTime: ".date('c')
		."\nIP: {$_SERVER['REMOTE_ADDR']}"
	;
	
	if (is_object($user)) {
		$header .= "\nUser: [{$user->id}] {$user->infos['user_name']}";
	}
	
	if (!empty($extra_header)) {
		$header .= "\n" . ltrim($extra_header);
	}
	
	error_log (
		''
			."\n-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-"
			.$header
			."\n---------------------------------------------"
			.$content
		,3
		,$level=="ERROR" ? FS_CACHE_DIR.'/err.log' : FS_CACHE_DIR.'/warn.log'
	);
}

/*
	@param $rows tablica z wierszami
	@param $link specyfikacja gdzie wstawiæ link
	e.g.:
	$link = array(
		'column_name' => 'ID',
		'link_string' => '/bibz_admins/index.php?do=details&task_id=%%cell_value%%',
	)
*/
function tabelka_print($rows, $link = array())
{
	echo '<table class="nicetable sortable">';
	//
	// nag³ówek
	echo '<tr>';
	foreach ($rows[0] as $n=>$k)
	{
		if (is_int($n))	// numeryczne ignore (tylko kolumny z nazw¹) wa¿ne przy tablicach mieszanych
		{
			continue;
		}
		echo '<th>'.$n.'</th>';
	}
	echo "</tr>\n";
	
	//
	// wnêtrze
	foreach ($rows as $row)
	{
		echo '<tr>';
		foreach ($row as $n=>$k)
		{
			if (is_int($n))	// numeryczne ignore (tylko kolumny z nazw¹) wa¿ne przy tablicach mieszanych
			{
				continue;
			}
			
			if (!empty($link) && $link['column_name'] == $n)
			{
				$url = str_replace('%%cell_value%%', $k, $link['link_string']);
				echo "<td><a href='$url'>$k</a></td>";
			}
			else
			{
				echo '<td>'.$k.'</td>';
			}
		}
		echo '</tr>';
	}
	echo '</table>';
	// koniec
	//
}

function smp_header($title)
{
	echo '<html><head><title>'.$title.'</title></head><body>';
}

?>