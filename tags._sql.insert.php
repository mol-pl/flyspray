<?php
	include('tags.data.all.php');
?>

--
-- Test data
--
<?php
	$max_id = 1;
	foreach ($tags as $row) {
		if ($max_id < $row['tag_id']) {
			$max_id = $row['tag_id'];
		}
		echo "\nINSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, {$row['tag_id']}, '{$row['tag_group']}', '{$row['tag_name']}');";
	}
?>


-- must change sequance after manually setting tag_id
SELECT setval('flyspray_list_tag_tag_id_seq', <?=$max_id?>);