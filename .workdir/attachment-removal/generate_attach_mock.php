<?php
/**
 * Simple file generator (mocking attachments).
 * 
 * TODO: Might want to change that in future to generate files from DB.
 * 
 * TODO: Could also add some example files per mime-type to e.g. generate:
 * <li>a proper png/jpg
 * <li>some mini PDF
 */

$list = include 'generate_attach_mock.list.php';
echo count($list);

$base_path = '../attachments/';

foreach ($list as $file_name) {
	$file_path = $base_path . $file_name;
	file_put_contents($file_path, '');
}
