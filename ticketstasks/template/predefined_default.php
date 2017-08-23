<?php if (!defined("JETTICKETSTASKS")) die("This file cannot be accessed directly"); ?>
<?php include_once(JTT_ROOT_PATH . "/template/overall_header.php"); ?>
<?php $tasksactions = ticketstasks::getInstance()->lang('tasksactions'); ?>

<form action="<?php echo $modulelink; ?>&pagename=predefined" method="post">

	<div role="group" class="btn-group">
		<a class="btn btn-default" href="<?php echo $modulelink; ?>&pagename=predefined&view=manage"><i class="fa fa-plus"></i> <?php echo ticketstasks::getInstance()->lang('createnewpredefinedtask'); ?></a>
	</div>
    
	<div class="tablebg">
		<table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
		<tbody>
		<tr>
			<th width="20"><input type="checkbox" onclick="$('input[name=selectedtasks\\[\\]]').prop('checked', $(this).is(':checked'))" /></th>
			<th><?php echo ticketstasks::getInstance()->lang('title'); ?></th>
			<th><?php echo ticketstasks::getInstance()->lang('taskstoperform'); ?></th>
			<th width="20"></th>
		</tr>
		<?php if(sizeof($action_response['data']['tasks'])) { ?>
		<?php foreach($action_response['data']['tasks'] as $task_id => $task_details) { ?>
		<tr>
			<td><input type="checkbox" class="checkall" value="<?php echo $task_id; ?>" name="selectedtasks[]" /></td>
			<td><?php echo $task_details['title']; ?></td>
			<td>
				<ul>
					<?php echo ($task_details['ticket_status_id'] ? '<li style="list-style: disc !important;">' . $tasksactions['status'] . ' <strong style="color: ' . $task_details['status_details']['color'] . '">' . $task_details['status_details']['title'] . '</strong></li>' : ''); ?>
					<?php echo ($task_details['ticket_admin_id'] ? '<li style="list-style: disc !important;">' . $tasksactions['flag'] . ' <strong>' . $task_details['ticket_admin_details']['firstname']  . ' ' . $task_details['ticket_admin_details']['lastname'] . '</strong>' . ($task_details['ticket_admin_email'] ? ' ' . ticketstasks::getInstance()->lang('andsendnotify') : '') . '</li>' : ''); ?>
					<?php echo ($task_details['ticket_dept_id'] ? '<li style="list-style: disc !important;">' . $tasksactions['dept'] . ' <strong>' . $task_details['department_details']['name'] . '</strong></li>' : ''); ?>
					<?php echo ($task_details['ticket_urgency'] ? '<li style="list-style: disc !important;">' . $tasksactions['priority'] . ' <strong>' . $task_details['ticket_urgency'] . '</strong></li>' : ''); ?>
					<?php echo ($task_details['ticket_bump'] == '1' ? '<li style="list-style: disc !important;">' . $tasksactions['bump'] . '</li>' : ''); ?>
					<?php echo ($task_details['ticket_reply'] ? '<li style="list-style: disc !important;"><strong>' . $tasksactions['reply'] . '</strong><br />' . $task_details['ticket_reply'] . '</li>' : ''); ?>
					<?php echo ($task_details['ticket_note'] ? '<li style="list-style: disc !important;"><strong>' . $tasksactions['note'] . '</strong><br />' . $task_details['ticket_note'] . '</li>' : ''); ?>
					<?php if($task_details['task_abort']['lastreply'] == '1') { ?>
					<li style="list-style: disc !important;"><?php echo $tasksactions['abort']; ?>
						<ul>
							<li style="list-style: circle !important;"><?php echo $tasksactions['abort_lastreply']; ?></li>
						</ul>
					</li>
					<?php } ?>
				</ul>
			</td>
			<td>
				<a href="<?php echo $modulelink; ?>&pagename=predefined&view=manage&id=<?php echo $task_id; ?>">
					<img border="0" width="16" height="16" alt="Edit" src="images/edit.gif" />
				</a>
			</td>
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
	<input type="submit" onclick="if(confirm('<?php echo ticketstasks::getInstance()->lang('delete_confirm'); ?>')) { $('input[name=action]').val('delete'); return true; } else { return false; }" class="btn btn-danger" value="<?php echo ticketstasks::getInstance()->lang('delete'); ?>" />
	
	<input type="hidden" name="action" value="" />
</form>

<?php include_once(JTT_ROOT_PATH . "/template/overall_footer.php"); ?>