<?php if (!defined("JETTICKETSTASKS")) die("This file cannot be accessed directly"); ?>
<?php include_once(JTT_ROOT_PATH . "/template/overall_header.php"); ?>
<?php $instance = ticketstasks::getInstance(); ?>

<?php if(file_exists(WHMCS_ROOT_PATH . '/assets/js/markdown.min.js')) { ?>
<script type="text/javascript" src="../assets/js/bootstrap-markdown.js"></script>
<link rel="stylesheet" type="text/css" href="../assets/css/bootstrap-markdown.min.css" />
<script type="text/javascript" src="../assets/js/markdown.min.js"></script>
<script type="text/javascript" src="../assets/js/to-markdown.js"></script>
<script type='text/javascript' src='../modules/addons/ticketstasks/js/editor.js'></script>

<script type="text/javascript">
$(document).ready(function(){
	jQuery("#taskreply, #tasknote").TicketMDE({
		locale: '<?php echo $_ADMINLANG['locale']; ?>',
		token: jQuery('input[name=token]').val(),
	});
});
</script>
<?php } ?>

<script type="text/javascript">
$(document).ready(function(){
	$('select[name=task\\[interval_type\\]]').change(function() {
	
		var value = $(this).val(); 
		switch(value)
		{
			case 'onetime':
				$('#repeated_interval').hide();
			break;
	
			case 'repeated':
				$('#repeated_interval').show();
			break;
		}
		
	}).change();	
});
</script>


<form action="<?php echo $modulelink; ?>&pagename=predefined&view=manage<?php echo ($id ? "&id={$id}" : ''); ?>&action=save" method="post">

<table width="100%" border="0" cellspacing="2" cellpadding="3" class="form">
</tr>
	<td class="fieldlabel" style="width: 25%"><?php echo $instance->lang('title'); ?>:</td>
	<td class="fieldarea"><input type="text" name="title" class="form-control input-400 input-inline" value="<?php echo $action_response['data']['fields']['title']; ?>" /></td>
</tr>
</tr>
	<td class="fieldlabel"><?php echo $instance->lang('taskinterval'); ?>:</td>
	<td class="fieldarea">
		<select name="task[interval_type]" class="form-control select-inline">
			<option <?php if($action_response['data']['fields']['task']['interval_type'] == 'onetime') { ?>selected="selected" <?php } ?>value="onetime">One Time</option>
			<option <?php if($action_response['data']['fields']['task']['interval_type'] == 'repeated') { ?>selected="selected" <?php } ?>value="repeated">Repeated</option>
		</select>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('settaskexecuted'); ?>:</td>
	<td class="fieldarea">
		<select name="task[hours]" class="form-control select-inline" style="min-width: auto;">
			<?php for($i = 0; $i < 24; ++$i) { ?>
			<option <?php if($action_response['data']['fields']['task']['hours'] == $i) { ?>selected="selected" <?php } ?>value="<?php echo $i; ?>"><?php echo ($i < 10 ? '0'.$i : $i); ?></option>
			<?php } ?>
		</select>:<select name="task[minutes]" class="form-control select-inline" style="min-width: auto;">
			<?php for($i = 0; $i < 6; ++$i) { ?>
			<option <?php if($action_response['data']['fields']['task']['minutes'] == $i.'0') { ?>selected="selected" <?php } ?>value="<?php echo $i; ?>0"><?php echo $i; ?>0</option>
			<?php } ?>
		</select>
		<span id="repeated_interval" style="display: none;">
		<span><?php echo $instance->lang('executeevery'); ?></span>
			<select class="form-control select-inline" name="task[interval]" style="min-width: auto;">
				<option <?php if($action_response['data']['fields']['task']['interval'] == 'day') { ?>selected="selected" <?php } ?>value="day"><?php echo $instance->lang('day'); ?></option>
				<option <?php if($action_response['data']['fields']['task']['interval'] == 'week') { ?>selected="selected" <?php } ?>value="week"><?php echo $instance->lang('week'); ?></option>
				<option <?php if($action_response['data']['fields']['task']['interval'] == 'month') { ?>selected="selected" <?php } ?>value="month"><?php echo $instance->lang('month'); ?></option>
				<option <?php if($action_response['data']['fields']['task']['interval'] == 'year') { ?>selected="selected" <?php } ?>value="year"><?php echo $instance->lang('year'); ?></option>
			</select>
		</span>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('changestatus'); ?>:</td>
	<td class="fieldarea">
		<select name="task[status]" class="form-control select-inline">
			<?php foreach($action_response['data']['statuses'] as $status_id => $status_details) { ?>
			<?php if($status_details['title'] == 'Pending Task') continue; ?>
			<option <?php if($action_response['data']['fields']['task']['status'] == $status_id) { ?>selected="selected" <?php } ?>value="<?php echo $status_id; ?>" style="color: <?php echo $status_details['color']; ?>"><?php echo $status_details['title']; ?></option>
			<?php } ?>
		</select>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('changedept'); ?>:</td>
	<td class="fieldarea">
		<select name="task[dept]" class="form-control select-inline">
			<option value="0"><?php echo $instance->lang('dontchange'); ?></option>
			<?php foreach($action_response['data']['departments'] as $department_id => $department_details) { ?>
			<option <?php if($action_response['data']['fields']['task']['dept'] == $department_id) { ?>selected="selected" <?php } ?>value="<?php echo $department_id; ?>"><?php echo $department_details['name']; ?></option>
			<?php } ?>
		</select>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('changepriority'); ?>:</td>
	<td class="fieldarea">
		<select name="task[urgency]" class="form-control select-inline">
			<option value=""><?php echo $instance->lang('dontchange'); ?></option>
			<option <?php if($action_response['data']['fields']['task']['urgency'] == 'Low') { ?>selected="selected" <?php } ?>value="Low">Low</option>
			<option <?php if($action_response['data']['fields']['task']['urgency'] == 'Medium') { ?>selected="selected" <?php } ?>value="Medium">Medium</option>
			<option <?php if($action_response['data']['fields']['task']['urgency'] == 'High') { ?>selected="selected" <?php } ?>value="High">High</option>
		</select>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('changeflag'); ?>:</td>
	<td class="fieldarea">
		<select name="task[flag]" class="form-control select-inline">
			<option value="0"><?php echo $instance->lang('dontchange'); ?></option>
			<?php foreach($action_response['data']['admins'] as $admin_id => $admin_details) { ?>
			<option <?php if($action_response['data']['fields']['task']['flag'] == $admin_id) { ?>selected="selected" <?php } ?>value="<?php echo $admin_id; ?>"><?php echo $admin_details['firstname'] . ' ' . $admin_details['lastname']; ?></option>
			<?php } ?>
		</select>
		<input type="checkbox" <?php if($action_response['data']['fields']['task']['flagemail']) { ?>checked="checked" <?php } ?>name="task[flagemail]" value="1" /> <?php echo $instance->lang('sendnotify'); ?>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('bumpticket'); ?>:</td>
	<td class="fieldarea">
		<label class="radio-inline"><input type="radio" <?php if(!$action_response['data']['fields']['task']['bump']) { ?>checked="checked" <?php } ?>name="task[bump]" value="0" /> <?php echo $instance->lang('no'); ?></label>
		<label class="radio-inline"><input type="radio" <?php if($action_response['data']['fields']['task']['bump']) { ?>checked="checked" <?php } ?>name="task[bump]" value="1" /> <?php echo $instance->lang('yes'); ?></label>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('tasksactions')['abort']; ?>:</td>
	<td class="fieldarea"><input type="checkbox" <?php if($action_response['data']['fields']['task']['abort']['lastreply']) { ?>checked="checked" <?php } ?>name="task[abort][lastreply]" value="1" /> <?php echo $instance->lang('tasksactions')['abort_lastreply']; ?></td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('addreply'); ?>:</td>
	<td class="fieldarea"><textarea id="taskreply" name="task[reply]" class="form-control"><?php echo $action_response['data']['fields']['task']['reply']; ?></textarea></td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('addnote'); ?>:</td>
	<td class="fieldarea"><textarea id="tasknote" name="task[note]" class="form-control"><?php echo $action_response['data']['fields']['task']['note']; ?></textarea></td>
</tr>
</table>

<div class="btn-container">
	<input type="submit" class="btn btn-primary" value="<?php echo ticketstasks::getInstance()->lang('createpredefinedtask'); ?>" />
</div>

</form>
<?php include_once(JTT_ROOT_PATH . "/template/overall_footer.php"); ?>