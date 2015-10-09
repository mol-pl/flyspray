<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="pl_PL" xml:lang="pl_PL">
<head>
	<title>{$proj->prefs['project_title']} &ndash; {L('roadmap')}</title>
	<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
	<style type="text/css">
		<!-- 
		body,div,table,thead,tbody,tfoot,tr,th,td,p { font-family:Verdana, Arial; font-size:12px; }
	
		table {
			border-collapse: collapse;
		}
		caption {
			margin-top: 0.5em;
		}
		th,td {
			padding: 2px 4px;
		}
		th {
			background: #ddd;
		}
		td {
			text-align:left; vertical-align:top;
			border: 1px solid #000000;
		}
		td>p,div>p {
			margin-top: 0px;
		}
		.comment {
			border:1px dotted grey;
			padding: 2px 4px;
			margin: 6px 1px;
		}
		dt {
			font-weight: bold; margin-top: 0.4em;
		}
		dd {
			margin-left: 1.5em;
		}
		 -->
	</style>
	<script type="text/javascript" src="{$baseurl}javascript/sortable.js"></script>
</head>

<body>

<?php
/*
	// sf - tylko Flashowe
	$url_status_base='index.php?do='.Get::val('do').'&project='.Get::val('project').'&smp_htm='.Get::val('smp_htm').'&sf='.Get::val('sf').'&ver_id='.Get::val('ver_id').'&status';
	$url_version_base='index.php?do='.Get::val('do').'&project='.Get::val('project').'&smp_htm='.Get::val('smp_htm').'&sf='.Get::val('sf').'&status='.Get::val('status').'&ver_id';
*/
	$url_status_base='index.php?do='.Get::val('do').'&project='.Get::val('project').'&smp_htm='.Get::val('smp_htm').'&ver_id='.Get::val('ver_id').'&status';
	$url_version_base='index.php?do='.Get::val('do').'&project='.Get::val('project').'&smp_htm='.Get::val('smp_htm').'&status='.Get::val('status').'&ver_id';
?>
<div style="font-size:90%"><a href="{$url_status_base}=any">{L('allstatuses')}</a>
&bull; <a href="{$url_status_base}=open">{L('allopentasks')}</a>
&bull; <a href="{$url_status_base}=closed">{L('closed')}</a>
&bull; <a href="{CreateURL('roadmap', $proj->id, null)}">{L('backtoroadmap')}</a></div>

<h1>{$proj->prefs['project_title']}</h2>
<?php foreach($data as $milestone): ?>

<?php if(Get::val('ver_id')): ?>
	<h2>{$milestone['name']}</h2>
<?php else: ?>
	<h2><a href="{$url_version_base}={$milestone['id']}">{$milestone['name']}</a></h2>
<?php endif; ?>

<?php if(count($milestone['open_tasks'])): ?>
	<table border="1" class="sortable">
		<tr>
			<th>{L('id')}</th>
			<th>{L('severity')}</th>
			<th>{L('status')}</th>
			<th>{L('category')}</th>
			<th>{L('assigned')}</th>
			<th>{L('summary')}</th>
			<th>{L('description')}</th>
			<th>{L('comments')}</th>
		</tr>
		<?php foreach($milestone['open_tasks'] as $task):
			if(!$user->can_view_task($task) || (Get::val('sf') && $task['task_type']==3)) continue; ?>
			<tr>
				<td>{FS_PREFIX_CODE}#{$task['task_id']}</td>
				<td>{$fs->severities[$task['task_severity']]}</td>
				<td>
					<?php if($task['is_closed']): ?>
						{L('closed')}
					<?php else: ?>
						{$task['status_name']}
					<?php endif; ?>
				</td>
				<td>{$task['category_name']}</td>
				<td>{$task['assigned_to_name']}</td>
				<td>{$task['item_summary']}</td>
				<td>{!smp_render($task['detailed_desc'])}</td>
				<td>
					<?php if(!empty($task['comments'])): ?>
						<?php foreach($task['comments'] as $c): ?>
							<div class="comment">{!smp_render($c['comment_text'])}</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>

<?php endforeach; ?>
</body>
</html>