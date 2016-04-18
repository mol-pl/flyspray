<?php
	include('tags.data.all.php');

	var_export($_GET);
	
	function getGroupCode($group_name) {
		return preg_replace('#[^a-zA-Z]#', '-', $group_name);
	}
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
			<?php $current_group = ''; ?>
			<?php foreach ($tags as $tag): ?>
				<?php if ($current_group != $tag['tag_group']): ?>
					<?php if (!empty($current_group)): ?>
						</optgroup>
					<?php endif; ?>
					<optgroup label="<?=$tag['tag_group']?>">
					<option value="-<?=$tag['tag_group']?>">nieprzypisane</option>
				<?php endif; ?>
				<option value="<?=$tag['tag_id']?>"><?=$tag['tag_name']?></option>
				<?php $current_group = $tag['tag_group']; ?>
			<?php endforeach; ?>
			</optgroup>
		</select>
	</div>

	<h2>Osobne pola na grupy</h2>
	
	<?php $current_group = ''; ?>
	<?php foreach ($tags as $tag): ?>
		<?php if ($current_group != $tag['tag_group']): ?>
			<?php if (!empty($current_group)): ?>
					</select>
				</div>
			<?php endif; ?>
			<div class="search_select">
				<label class="default multisel" for="tags-<?=getGroupCode($tag['tag_group'])?>"><?=$tag['tag_group']?></label>
				<select name="tags[]" id="tags-<?=getGroupCode($tag['tag_group'])?>" multiple="multiple" size="5">
					<option value="-<?=$tag['tag_group']?>">nieprzypisane</option>
		<?php endif; ?>
		<option value="<?=$tag['tag_id']?>"><?=$tag['tag_name']?></option>
		<?php $current_group = $tag['tag_group']; ?>
	<?php endforeach; ?>
		</select>
	</div>

	<br clear="all">
	<input type="submit" value="Szukaj" name="submit" />
</form>