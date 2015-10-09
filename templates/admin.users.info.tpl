<table border="1">
<tr>
<th>user_id</th>
<th>user_name</th>
<th>user_pass</th>
<th>email_address</th>
<th>notify_type</th>
<th>notify_own</th>
<th>tasks_perpage</th>
<th>register_date</th>
<th>time_zone</th>
</tr>
<?php foreach ($usersinfo as $user): ?>
<tr>
<td>{$user['user_id']}</td>
<td>{$user['user_name']}</td>
<td>{$user['user_pass']}</td>
<td>{$user['email_address']}</td>
<td>{$user['notify_type']}</td>
<td>{$user['notify_own']}</td>
<td>{$user['tasks_perpage']}</td>
<td>{$user['register_date']}</td>
<td>{$user['time_zone']}</td>
</tr>
<?php endforeach; ?>
</table>