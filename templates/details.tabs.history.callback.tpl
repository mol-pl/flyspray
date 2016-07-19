<?php if ($details && count($histories)): ?>
<table class="history">
  <tr>
    <th>{L('previousvalue')}</th>
    <th>{L('newvalue')}</th>
  </tr>
  <tr>
    <td>{!$details_previous}</td>
    <td>{!$details_new}</td>
  </tr>
  <tr>
    <th colspan="2">diff</th>
  </tr>
  <tr>
    <td colspan="2"><div style="white-space: pre-wrap;" class="history-diff">
		<input type="button" name="show" value="{L('show')}" onclick="showDiffOnHistory()">
	</div></td>
  </tr>
</table>
<style>
	.history-diff del {
		background:#FFE6E6;
	}
	.history-diff ins {
		background:#E6FFE6;
	}
</style>
<?php else: ?>
<table class="history">
  <tr>
    <th>{L('eventdate')}</th>
    <th>{L('user')}</th>
    <th>{L('event')}</th>
  </tr>

  <?php foreach($histories as $history): ?>
  <tr>
    <td>{formatDate($history['event_date'], false)}</td>
    <td>{!tpl_userlink($history['user_id'])}</td>
    <td>{!event_description($history)}</td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>