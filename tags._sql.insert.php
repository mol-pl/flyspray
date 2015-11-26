<?php
	include('tags.data.all.php');
?>

--
-- Test data
--
<?php
	foreach ($tags as $row) {
		echo "\nINSERT INTO flyspray_list_tag (show_in_list, tag_id, tag_group, tag_name) VALUES (1, {$row['tag_id']}, '{$row['tag_group']}', '{$row['tag_name']}');";
	}
?>
