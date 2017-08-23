<?php if (!defined("JETTICKETSTASKS")) die("This file cannot be accessed directly"); ?>
<?php include_once(JTT_ROOT_PATH . "/template/overall_header.php"); ?>

<?php if(file_exists(WHMCS_ROOT_PATH . '/assets/js/markdown.min.js')) { ?>
<script type="text/javascript" src="../assets/js/bootstrap-markdown.js"></script>
<link rel="stylesheet" type="text/css" href="../assets/css/bootstrap-markdown.min.css" />
<script type="text/javascript" src="../assets/js/markdown.min.js"></script>
<script type="text/javascript" src="../assets/js/to-markdown.js"></script>
<script type='text/javascript' src='../modules/addons/ticketstasks/js/editor.js'></script>

<script type="text/javascript">
$(document).ready(function(){
	jQuery("#replymessage").TicketMDE({
		locale: '<?php echo $_ADMINLANG['locale']; ?>',
		token: jQuery('input[name=token]').val(),
	});
});
</script>
<?php } ?>
<form action="<?php echo $modulelink; ?>&pagename=tasks&view=create&userid=<?php echo $action_response['data']['client_details']['id']; ?>&action=save" method="post">
<table cellspacing="2" cellpadding="3" border="0" width="100%" class="form">
<tbody>
<tr>
	<td class="fieldlabel"><?php echo ticketstasks::getInstance()->lang('scheduledate'); ?>:</td>
	<td colspan="3" class="fieldarea">
		<input type="text" class="datepick form-control input-inline" size="12" value="<?php echo $action_response['data']['fields']['scheduledate']; ?>" name="scheduledate" />
		<span style="margin-left: -10px;"><?php echo ticketstasks::getInstance()->lang('at'); ?></span>
		<select name="schedule_hours" class="form-control select-inline" style="min-width: auto;">
			<?php for($i = 0; $i < 24; ++$i) { ?>
			<option <?php if($action_response['data']['fields']['schedule_hours'] == ($i < 10 ? '0'.$i : $i)) { ?>selected="selected" <?php } ?>value="<?php echo $i; ?>"><?php echo ($i < 10 ? '0'.$i : $i); ?></option>
			<?php } ?>
		</select>:<select name="schedule_minutes" class="form-control select-inline" style="min-width: auto;">
			<?php for($i = 0; $i < 6; ++$i) { ?>
			<option <?php if($action_response['data']['fields']['schedule_minutes'] == ($i.'0')) { ?>selected="selected" <?php } ?>value="<?php echo $i; ?>0"><?php echo $i; ?>0</option>
			<?php } ?>
		</select>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $_ADMINLANG['fields']['client']; ?></td>
	<td colspan="3" class="fieldarea"><?php echo $action_response['data']['client_details']['firstname'] . ' ' . $action_response['data']['client_details']['lastname']; ?></td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $_ADMINLANG['fields']['email']; ?></td>
	<td colspan="3" class="fieldarea">
		<input type="text" value="<?php echo $action_response['data']['fields']['email']; ?>" class="form-control input-400 input-inline" id="email" name="email" /> 
		<label class="checkbox-inline"><input type="checkbox"<?php if($action_response['data']['fields']['sendmail']) { ?> checked=""<?php } ?> name="sendemail" /> <?php echo $_ADMINLANG['global']['sendemail']; ?></label>
	</td>
</tr>
<?php if(sizeof($action_response['data']['client_details']['contacts'])) { ?>
<tr>
	<td class="fieldlabel"><?php echo $_ADMINLANG['clientsummary']['contacts']; ?></td>
	<td colspan="3" id="contacthtml" class="fieldarea">
		<select class="form-control select-inline" name="contactid">
			<option value="0"><?php echo $_ADMINLANG['global']['none']; ?></option>
			<?php foreach($action_response['data']['client_details']['contacts'] as $contact_details) { ?>
			<option <?php if($action_response['data']['fields']['contactid'] == $contact_details['id']) { ?>selected="selected" <?php } ?>value="<?php echo $contact_details['id']; ?>"><?php echo $contact_details['firstname'] . ' ' . $contact_details['lastname'] . ' - ' . $contact_details['email']; ?></option>
			<?php } ?>
		</select>
	</td>
</tr>
<?php } ?>
<tr>
	<td class="fieldlabel"><?php echo $_ADMINLANG['support']['ccrecipients']; ?></td>
	<td colspan="3" class="fieldarea"><input type="text" class="form-control input-500 input-inline" value="<?php echo $action_response['data']['fields']['ccemail']; ?>" name="ccemail" /> (<?php echo $_ADMINLANG['transactions']['commaseparated']; ?>)</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $_ADMINLANG['fields']['subject']; ?></td>
	<td colspan="3" class="fieldarea"><input type="text" value="<?php echo $action_response['data']['fields']['subject']; ?>" class="form-control" name="subject" /></td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $_ADMINLANG['support']['department']; ?></td>
	<td class="fieldarea">
		<select class="form-control select-inline" name="deptid">
			<?php foreach($action_response['data']['departments'] as $department_details) { ?>
			<option <?php if($action_response['data']['fields']['deptid'] == $department_details['id']) { ?>selected="selected" <?php } ?>value="<?php echo $department_details['id']; ?>"><?php echo $department_details['name']; ?></option>
			<?php } ?>
		</select>
	</td>
	<td class="fieldlabel"><?php echo $_ADMINLANG['support']['priority']; ?></td>
	<td class="fieldarea">
		<select class="form-control select-inline" name="priority">
			<option <?php if($action_response['data']['fields']['priority'] == 'High') { ?>selected="selected" <?php } ?>value="High"><?php echo $_ADMINLANG['status']['high']; ?></option>
			<option <?php if($action_response['data']['fields']['priority'] == 'Medium') { ?>selected="selected" <?php } ?>value="Medium"><?php echo $_ADMINLANG['status']['medium']; ?></option>
			<option <?php if($action_response['data']['fields']['priority'] == 'Low') { ?>selected="selected" <?php } ?>value="Low"><?php echo $_ADMINLANG['status']['low']; ?></option>
		</select>
	</td>
</tr>
</tbody>
</table>
<textarea name="message" id="replymessage" rows="20" class="form-control top-margin-10 bottom-margin-10"><?php echo $action_response['data']['fields']['message']; ?></textarea>

<div class="btn-container">
	<input type="submit" class="btn btn-primary" value="<?php echo ticketstasks::getInstance()->lang('createtickettask'); ?>" />
</div>

</form>

<?php include_once(JTT_ROOT_PATH . "/template/overall_footer.php"); ?>