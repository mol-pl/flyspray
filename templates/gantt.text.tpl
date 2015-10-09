<?php 
	$freedays = GetAllFreedays();
	foreach($freedays as $owner=>$days)
	{
		asort( $freedays[$owner] );
		$freedays[$owner] = ParseFreedaysRanges ($freedays[$owner], false);
	}
	$editlink = ($_SERVER["HTTPS"]=='on' ? 'https' : 'http') ."://{$_SERVER["HTTP_HOST"]}{$_SERVER['REQUEST_URI']}";
	$editlink = preg_replace(array(
		// submit
		"#&submit=[^=&]+#",
		// puste parametry
		"#&[^=&]+=(?=&|$)#",
		// puste due
		"#[?&]due=0#",
	), "", $editlink);
?>

<jsgantt autolink="1"  baselink="{$baseurl}index.php?do=details&amp;task_id=">
<!--
Źródło:
{$editlink}
-->
<?php foreach($tasks as $task): ?>
    <?php if(!$user->can_view_task($task)) continue; ?>
	<task>
		<pID>{$task['GUID']}</pID>
		<pName>{FS_PREFIX_CODE}#{$task['task_id']} {$task['item_summary']} [{$fs->severities[$task['task_severity']]}]</pName>
		<pStart>{$task['start_date']}</pStart>
		<pEnd>{$task['end_date']}</pEnd>
		<pColor>E0EAF8</pColor>
		<pRes>{$task['assigned_to_name']}</pRes>
		<pOpen>{$task['is_open']}</pOpen>
		<pDepend>{$task['depend']}</pDepend>
		<pComp>{$task['percent_complete']}</pComp>
<?php if(!empty($gantt_addlinks)):?>
		<pLink>{CreateUrl('details', $task['task_id'])}</pLink>
<?php endif; ?>
	</task>
<?php endforeach; ?>

<?php $id=1000000; ?>
<?php if(!empty($freedays)):?>
<!-- Dni wolne -->
<?php endif; ?>
<?php foreach($freedays as $owner=>$days): ?>
<?php if ($owner=='0') $owner = 'Wszyscy'; ?>
<?php $id++; ?>
<?php $parent=$id; ?>
	<task>
		<pID>{$id}</pID>
		<pGroup>1</pGroup>
		<pOpen>0</pOpen>
		<pName>Dni wolne</pName>
		<pRes>{$owner}</pRes>
		<pLink>http://prl.mol.com.pl/wiki/index.php?title=Grafik_nieobecno%C5%9Bci</pLink>
	</task>
<?php foreach($days as $freeday): ?>
<?php if (empty($freeday['start'])) { continue; } ?>
<?php $id++; ?>
	<task>
		<pID>{$id}</pID>
		<pParent>{$parent}</pParent>
		<pName>Dni wolne</pName>
		<pStart>{$freeday['start']}</pStart>
		<pEnd>{$freeday['end']}</pEnd>
		<pColor>00cc00</pColor>
		<pRes>{$owner}</pRes>
		<pLink>http://prl.mol.com.pl/wiki/index.php?title=Grafik_nieobecno%C5%9Bci</pLink>
	</task>
<?php endforeach; ?>
<?php endforeach; ?>
</jsgantt>