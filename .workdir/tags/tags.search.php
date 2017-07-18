<?php
	include('tags.data.groupped.php');

	var_export($_GET);

	$args = $_GET;

			$tag_ids = array();
			$negative_tag_groups = array();
			foreach ($args['tags'] as $tag_value) {
				if (empty($tag_value)) {
					continue;
				}
				// id search
				$tag_id = intval($tag_value, 10);
				if (!empty($tag_id)) {
					$tag_ids[] = $tag_id;
				}
				// unassugned search
				if (strpos($tag_value, '-') === 0) {
					$negative_tag_groups[] = substr($tag_value, 1);
				}
			}
	echo"<br>tag_ids "; var_export($tag_ids);
	echo"<br>negative_tag_groups "; var_export($negative_tag_groups);
?>

<style type="text/css">
div.search_select { 
	float: left;
	margin-right: 1em;
}
label
{display:block; font-weight: bold}
h2
{clear:both; padding-top: 1em}
</style>

<form action="" method="get">
	
	<h2>W jednym polu</h2>
	
	<div class="search_select">
		<label class="default multisel" for="tagscomb">Znaczniki</label>
		<select name="tagscomb[]" id="tags" multiple="multiple" size="8">
			<option value="" selected="selected">dowolne</option>
			<?php foreach ($tags as $tag_group): ?>
				<optgroup label="<?=$tag_group['name']?>">
					<option value="-<?=$tag_group['name']?>">nieprzypisane</option>
					<?php foreach ($tag_group['tags'] as $tag): ?>
						<option value="<?=$tag['tag_id']?>"><?=$tag['tag_name']?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
	</div>

	<h2>Osobne pola na grupy</h2>
	
	<?php foreach ($tags as $tag_group): ?>
		<div class="search_select">
			<label class="default multisel" for="tags-<?=$tag_group['code']?>"><?=$tag_group['name']?></label>
			<select name="tags[]" id="tags-<?=$tag_group['code']?>" multiple="multiple" size="5">
				<option value="">{L('allcategories')}</option>
				<option value="-<?=$tag_group['name']?>">nieprzypisane</option>
				<?php foreach ($tag_group['tags'] as $tag): ?>
					<option value="<?=$tag['tag_id']?>"><?=$tag['tag_name']?></option>
				<?php endforeach; ?>
			</select>
		</div>
	<?php endforeach; ?>

	<br clear="all">
	<input type="submit" value="Szukaj" name="submit" />
</form>