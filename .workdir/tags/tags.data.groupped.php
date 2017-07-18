<?php
	include('tags.data.all.php');

	function getGroupCode($group_name) {
		return preg_replace('#[^a-zA-Z]#', '-', $group_name);
	}

	$current_group = '';
	$tags_copy = array_merge($tags, array());
	$tags = array();
	foreach ($tags_copy as $tag) {
		if ($current_group != $tag['tag_group']) {
			$current_group = $tag['tag_group'];
			$tags[$current_group] = array(
				'code' => getGroupCode($tag['tag_group']),
				'name' => $tag['tag_group'],
				'tags' => array(),
			);
		}
		$tags[$current_group]['tags'][] = $tag;
	}
	
	//var_export($tags);

?>