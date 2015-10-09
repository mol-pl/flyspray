<?php foreach($data as $milestone): ?>

<div class="box roadmap">
<h3>{L('roadmapfor')} {$milestone['name']}</h3>

<p><img src="{$this->get_image('percent-' . round($milestone['percent_complete']/10)*10)}"
				title="{(round($milestone['percent_complete']/10)*10)}% {L('complete')}"
				alt="" width="200" height="20" align="left" /> &nbsp; ({$milestone['percent_complete']}%)
</p>

<p>
<b>{L('viewtasks')}</b>: 
<a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;tasks=&amp;project={$proj->id}&amp;due={$milestone['id']}&amp;status[]=">{mb_strtolower(L('tasksall'))} ({count($milestone['all_tasks'])})</a>
<?php if(count($milestone['open_tasks'])): ?>
	&bull;
	<a href="{$_SERVER['SCRIPT_NAME']}?do=index&amp;tasks=&amp;project={$proj->id}&amp;due={$milestone['id']}">{mb_strtolower(L('opentasks'))} ({count($milestone['open_tasks'])})</a>
<?php endif; ?>
&bull;
<!--
<a href="{CreateURL('roadmap', $proj->id, null, array('smp_htm' => 'true', 'sf' => 'true', 'ver_id'=>$milestone['id']))}">{mb_strtolower(L('smp_summary'))} HTML</a>
-->
<a href="{CreateURL('roadmap', $proj->id, null, array('smp_htm' => 'true', 'ver_id'=>$milestone['id']))}">{mb_strtolower(L('smp_summary'))} HTML</a>
</p>

<?php if(count($milestone['open_tasks'])): ?>
<h4 style="margin-bottom: 5px; font-size: 110%">{L('overview')} ({mb_strtolower(L('open'))}):
    <?php if (count($milestone['open_tasks'])): ?>
    <small class="DoNotPrint">
      <a href="javascript:<?php foreach($milestone['open_tasks'] as $task): ?>showstuff('dd{$task['task_id']}');hidestuff('expand{$task['task_id']}');showstuff('hide{$task['task_id']}', 'inline');<?php endforeach; ?>">{L('expandall')}</a> |
      <a href="javascript:<?php foreach($milestone['open_tasks'] as $task): ?>hidestuff('dd{$task['task_id']}');hidestuff('hide{$task['task_id']}');showstuff('expand{$task['task_id']}', 'inline');<?php endforeach; ?>">{L('collapseall')}</a>
    </small>
    <?php endif; ?>
</h4>
<dl class="roadmap">
    <?php foreach($milestone['open_tasks'] as $task):
          if(!$user->can_view_task($task)) continue; ?>
      <dt class="severity{$task['task_severity']}">
        {!tpl_tasklink($task['task_id'])}
        <small class="DoNotPrint">
          <a id="expand{$task['task_id']}" href="javascript:showstuff('dd{$task['task_id']}');hidestuff('expand{$task['task_id']}');showstuff('hide{$task['task_id']}', 'inline')">{L('expand')}</a>
          <a class="hide" id="hide{$task['task_id']}" href="javascript:hidestuff('dd{$task['task_id']}');hidestuff('hide{$task['task_id']}');showstuff('expand{$task['task_id']}', 'inline')">{L('collapse')}</a>
        </small>
      </dt>
      <dd id="dd{$task['task_id']}" >
        {!TextFormatter::render(substr($task['detailed_desc'], 0, 500) . ((strlen($task['detailed_desc']) > 500) ? '...' : ''),
                         false, 'rota', $task['task_id'], $task['content'])}
        <br style="position:absolute;" />
      </dd>
    <?php endforeach; ?>
</dl>

<?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (!count($data)): ?>
<div class="box roadmap">
<p><em>{L('noroadmap')}</em></p>
</div>
<?php else: ?>
<!--
<p><b>{L('smp_summaries')}:</b> &nbsp; <a href="{CreateURL('roadmap', $proj->id, null, array('txt' => 'true'))}"><img src="{$this->get_image('mime/text')}" alt="" /> {L('textversion')}</a> &bull; <a href="{CreateURL('roadmap', $proj->id, null, array('smp_htm' => 'true', 'sf' => 'true'))}"><img src="{$this->get_image('x-office-calendar')}" alt="" /> HTML</a></p>
-->
<p><b>{L('smp_summaries')}:</b> &nbsp; <a href="{CreateURL('roadmap', $proj->id, null, array('txt' => 'true'))}"><img src="{$this->get_image('mime/text')}" alt="" /> {L('textversion')}</a> &bull; <a href="{CreateURL('roadmap', $proj->id, null, array('smp_htm' => 'true'))}"><img src="{$this->get_image('x-office-calendar')}" alt="" /> HTML</a></p>
<?php endif; ?>
