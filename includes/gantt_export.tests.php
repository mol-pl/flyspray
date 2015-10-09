<?php
	define ('IN_FS', true);
	require_once('gantt_export.inc.php');

	echo "\n";
	echo "\n '+1 hour' / 1 = ". TimeIntervalDivider('+1 hour', 1);
	echo "\n '+1 hour' / 2 = ". TimeIntervalDivider('+1 hour', 2);
	echo "\n '+1 hour' / 3 = ". TimeIntervalDivider('+1 hour', 3);

	echo "\n";
	echo "\n '+3 hours' / 1 = ". TimeIntervalDivider('+3 hours', 1);
	echo "\n '+3 hours' / 2 = ". TimeIntervalDivider('+3 hours', 2);
	echo "\n '+3 hours' / 3 = ". TimeIntervalDivider('+3 hours', 3);
	echo "\n '+3 hours' / 4 = ". TimeIntervalDivider('+3 hours', 4);

	echo "\n";
	echo "\n '+1 day' / 1 = ". TimeIntervalDivider('+1 day', 1);
	echo "\n '+1 day' / 2 = ". TimeIntervalDivider('+1 day', 2);
	echo "\n '+1 day' / 3 = ". TimeIntervalDivider('+1 day', 3);

	echo "\n";
	echo "\n '+12 hours' / 1 = ". TimeIntervalDivider('+12 hours', 1);
	echo "\n '+12 hours' / 2 = ". TimeIntervalDivider('+12 hours', 2);
	echo "\n '+12 hours' / 3 = ". TimeIntervalDivider('+12 hours', 3);

	echo "\n";
	echo "\n +1 weeks' / 1 = ". TimeIntervalDivider('+1 weeks', 1);
	echo "\n +1 weeks' / 2 = ". TimeIntervalDivider('+1 weeks', 2);
	echo "\n +1 weeks' / 3 = ". TimeIntervalDivider('+1 weeks', 3);

	echo "\n";
	echo "\n '+2 weeks' / 1 = ". TimeIntervalDivider('+2 weeks', 1);
	echo "\n '+2 weeks' / 2 = ". TimeIntervalDivider('+2 weeks', 2);
	echo "\n '+2 weeks' / 3 = ". TimeIntervalDivider('+2 weeks', 3);
?>
