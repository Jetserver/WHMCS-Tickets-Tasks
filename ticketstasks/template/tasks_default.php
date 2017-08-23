<?php if (!defined("JETTICKETSTASKS")) die("This file cannot be accessed directly"); ?>
<?php include_once(JTT_ROOT_PATH . "/template/overall_header.php"); ?>
<?php $tasksactions = ticketstasks::getInstance()->lang('tasksactions', array()); ?>

<form action="<?php echo $modulelink; ?>&pagename=tasks" method="post">

	<h2>Tickets creation tasks</h2>
	<div class="tablebg">
		<table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
		<tbody>
		<tr>
			<th width="20"><input type="checkbox" onclick="$('input[name=selectedtickets\\[\\]]').prop('checked', $(this).is(':checked'))" /></th>
			<th><?php echo ticketstasks::getInstance()->lang('admin'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('client'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('execution_date'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('subject'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('department'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('priority'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('status'); ?></th>
		</tr>
		<?php if(sizeof($action_response['data']['tickets'])) { ?>
		<?php foreach($action_response['data']['tickets'] as $task_id => $task_details) { ?>
		<tr>
			<td><input type="checkbox" class="checkall" value="<?php echo $task_id; ?>" name="selectedtickets[]" /></td>
			<td><?php echo $task_details['admin_details']['firstname']  . ' ' . $task_details['admin_details']['lastname']; ?></td>
			<td><?php echo $task_details['userid'] ? '<a href="clientssummary.php?userid=' . $task_details['userid'] . '" target="_blank">' . $task_details['firstname'] . ' ' . $task_details['lastname'] . '</a>' : '-'; ?></td>
			<td><?php echo date("d/m/Y H:i", $task_details['schedule']); ?></td>
			<td><?php echo $task_details['subject']; ?></td>
			<td><?php echo $task_details['department_details']['name']; ?></td>
			<td><?php echo $task_details['priority']; ?></td>
			<td><?php echo ($task_details['status'] == 'Completed' ? '<strong style="color: #1cb536;">' . ticketstasks::getInstance()->lang('completed') : ($task_details['status'] == 'Pending' ? '<strong style="color: #ff6600;">' . ticketstasks::getInstance()->lang('pending') : '<strong style="color: #CC0000;">' . ticketstasks::getInstance()->lang('aborted'))); ?></strong></td>
		</tr>
		<?php } ?>
		<?php } else { ?>
		<tr>
			<td colspan="100"><?php echo ticketstasks::getInstance()->lang('no_records'); ?></td>
		</tr>
		<?php } ?>
		</tbody>
		</table>
	</div>
	<?php echo ticketstasks::getInstance()->lang('with_selected'); ?>:
	<input type="submit" onclick="if(confirm('<?php echo ticketstasks::getInstance()->lang('delete_confirm'); ?>')) { $('input[name=action]').val('cancel'); $('input[name=type]').val('tickets'); return true; } else { return false; }" class="btn btn-danger" value="<?php echo ticketstasks::getInstance()->lang('delete'); ?>" />
	
	<h2>Tickets tasks</h2>
	<div class="tablebg">
		<table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
		<tbody>
		<tr>
			<th width="20"><input type="checkbox" onclick="$('input[name=selectedtasks\\[\\]]').prop('checked', $(this).is(':checked'))" /></th>
			<th><?php echo ticketstasks::getInstance()->lang('ticket'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('admin'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('client'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('execution_date'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('taskstoperform'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('status'); ?></th>
		</tr>
		<?php if(sizeof($action_response['data']['tasks'])) { ?>
		<?php foreach($action_response['data']['tasks'] as $task_id => $task_details) { ?>
		<tr>
			<td><input type="checkbox" class="checkall" value="<?php echo $task_id; ?>" name="selectedtasks[]" /></td>
			<td><a href="supporttickets.php?action=view&id=<?php echo $task_details['ticket_id']; ?>" target="_blank">#<?php echo $task_details['ticket_id'] . ' - ' . $task_details['title']; ?></a></td>
			<td><?php echo $task_details['admin_details']['firstname']  . ' ' . $task_details['admin_details']['lastname']; ?></td>
			<td><?php echo $task_details['userid'] ? '<a href="clientssummary.php?userid=' . $task_details['userid'] . '" target="_blank">' . $task_details['firstname'] . ' ' . $task_details['lastname'] . '</a>' : '-'; ?></td>
			<td><?php echo date("d/m/Y H:i", $task_details['task_time']); ?></td>
			<td>
				<ul>
					<?php echo ($task_details['ticket_status_id'] ? '<li style="list-style: disc !important;">' . $tasksactions['status'] . ' <strong style="color: ' . $task_details['status_details']['color'] . '">' . $task_details['status_details']['title'] . '</strong></li>' : ''); ?>
					<?php echo ($task_details['ticket_admin_id'] ? '<li style="list-style: disc !important;">' . $tasksactions['flag'] . ' <strong>' . $task_details['ticket_admin_details']['firstname']  . ' ' . $task_details['ticket_admin_details']['lastname'] . '</strong>' . ($task_details['ticket_admin_email'] ? ' ' . ticketstasks::getInstance()->lang('andsendnotify') : '') . '</li>' : ''); ?>
					<?php echo ($task_details['ticket_dept_id'] ? '<li style="list-style: disc !important;">' . $tasksactions['dept'] . ' <strong>' . $task_details['department_details']['name'] . '</strong></li>' : ''); ?>
					<?php echo ($task_details['ticket_urgency'] ? '<li style="list-style: disc !important;">' . $tasksactions['priority'] . ' <strong>' . $task_details['ticket_urgency'] . '</strong></li>' : ''); ?>
					<?php echo ($task_details['ticket_bump'] == '1' ? '<li style="list-style: disc !important;">' . $tasksactions['bump'] . '</li>' : ''); ?>
					<?php echo ($task_details['ticket_reply'] ? '<li style="list-style: disc !important;"><strong>' . $tasksactions['reply'] . '</strong><br />' . $task_details['ticket_reply'] . '</li>' : ''); ?>
					<?php echo ($task_details['ticket_note'] ? '<li style="list-style: disc !important;"><strong>' . $tasksactions['note'] . '</strong><br />' . $task_details['ticket_note'] . '</li>' : ''); ?>

					<?php if(sizeof($task_details['task_abort'])) { ?>
					<li style="list-style: disc !important;"><?php echo $tasksactions['abort']; ?>
						<ul>

						<?php echo ($task_details['task_abort']['lastreply'] ? '<li style="list-style: circle !important;">' . $tasksactions['abort_lastreply'] . ' - ' . ticketstasks::getInstance()->lang('lastreply') . ': ' . $task_details['task_abort']['lastreplytime'] . '</li>' : ''); ?>
						<?php echo ($task_details['task_abort']['invoice'] ? '<li style="list-style: circle !important;">' . $tasksactions['abort_invoice'] . ' - ' . $task_details['task_abort']['invoiceids'] . '</li>' : ''); ?>

						</ul>
					</li>
					<?php } ?>
				</ul>
			</td>
			<td><?php echo ($task_details['task_status'] == 'Completed' ? '<strong style="color: #1cb536;">' . ticketstasks::getInstance()->lang('completed') : ($task_details['task_status'] == 'Pending' ? '<strong style="color: #ff6600;">' . ticketstasks::getInstance()->lang('pending') : '<strong style="color: #CC0000;">' . ticketstasks::getInstance()->lang('aborted'))); ?></strong></td>
		</tr>
		<?php } ?>
		<?php } else { ?>
		<tr>
			<td colspan="100"><?php echo ticketstasks::getInstance()->lang('no_records'); ?></td>
		</tr>
		<?php } ?>
		</tbody>
		</table>
	</div>
	<?php echo ticketstasks::getInstance()->lang('with_selected'); ?>:
	<input type="submit" onclick="if(confirm('<?php echo ticketstasks::getInstance()->lang('delete_confirm'); ?>')) { $('input[name=action]').val('cancel'); $('input[name=type]').val('tasks'); return true; } else { return false; }" class="btn btn-danger" value="<?php echo ticketstasks::getInstance()->lang('delete'); ?>" />
	<input type="hidden" name="action" value="" />
	<input type="hidden" name="type" value="" />
</form>

<ul class="pager">
	<li class="previous<?php if($pagenum <= 1) { ?> disabled<?php } ?>"><a href="<?php if($pagenum > 1) { ?><?php echo $modulelink; ?>&pagename=tasks&pagenum=<?php echo ($pagenum - 1); ?><?php } else { ?>#<?php } ?>">« <?php echo ticketstasks::getInstance()->lang('prev_page'); ?></a></li>
	<li class="next<?php if($total_pages <= $pagenum) { ?> disabled<?php } ?>"><a href="<?php if($total_pages > $pagenum) { ?><?php echo $modulelink; ?>&pagename=tasks&pagenum=<?php echo ($pagenum + 1); ?><?php } else { ?>#<?php } ?>"><?php echo ticketstasks::getInstance()->lang('next_page'); ?> »</a></li>
</ul>

<?php include_once(JTT_ROOT_PATH . "/template/overall_footer.php"); ?>