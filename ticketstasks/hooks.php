<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

if(!defined('JTT_ROOT_PATH')) define('JTT_ROOT_PATH', dirname(__FILE__));
if(!defined('WHMCS_ROOT_PATH')) define('WHMCS_ROOT_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../'));

use Illuminate\Database\Capsule\Manager as Capsule;

require_once(JTT_ROOT_PATH . '/includes/functions.php');

function ticketstasks_hook_addpopup($vars)
{
	global $_ADMINLANG;

	if($vars['filename'] == 'clientssummary')
	{
		$instance = ticketstasks::getInstance();

		// if the system is disabled exit the function
		if(!$instance->getConfig('system_enabled')) return;

		return "<script type='text/javascript'>
			$(document).ready(function() {
				var new_ticket_link = $('a').filter(function() { return this.href.match(/supporttickets\.php\?action\=open.*/); });
				var new_ticket = new_ticket_link.closest('li');

				new_ticket.after('<li><a href=\"addonmodules.php?module=ticketstasks&pagename=tasks&view=create&userid={$vars['clientsdetails']['userid']}\"><img border=\"0\" align=\"absmiddle\" src=\"images/icons/todolist.png\" /> Create New Ticket Creation Task</a></li>');
			});
			</script>";
	}
	elseif($vars['filename'] == 'supporttickets' && $vars['pageicon'] != 'ticketsopen')
	{
		$instance = ticketstasks::getInstance();

		// if the system is disabled exit the function
		if(!$instance->getConfig('system_enabled')) return;

		$ticket_id = ticketstasks::request_var('id', 0);

		if($ticket_id)
		{
			// if the ticket view is disabled exit the function
			if(!$instance->getConfig('view_active')) return;

			$response = array();

			switch(ticketstasks::request_var('sub', '', array('addtask','deltask')))
			{
				case 'addtask':

					if(intval($_REQUEST['task_id']))
					{
						$response = ticketstasks::editTask(ticketstasks::request_var('id', 0), ticketstasks::request_var('task_id', 0), ticketstasks::request_var('task', array()));
					}
					else
					{
						$response = ticketstasks::addTask(ticketstasks::request_var('id', 0), ticketstasks::request_var('task', array()));
					}
				break;

				case 'deltask':

					$response = ticketstasks::deleteTask(ticketstasks::request_var('id', 0), ticketstasks::request_var('idsd', 0));
				break;
			}

			$ticket_details = Capsule::table('tbltickets')
				->where('id', $ticket_id)
				->get();

			$ticket_details = isset($ticket_details[0]) ? (array) $ticket_details[0] : null;

			$tasks = array();

			$task_rows = Capsule::table('mod_ticketstasks_tasks as s')
				->select('s.*', 's.id as task_id', 't.*')
				->join('tbltickets as t', 's.ticket_id', '=', 't.id')
				->where('s.ticket_id', '=', $ticket_id)
				->orderBy('s.task_time', 'asc')
				->get();

			foreach($task_rows as $task_details)
			{
				$task_details = (array) $task_details;

				$task_details['task_time_date'] = date("d/m/Y", $task_details['task_time']);
				$task_details['task_time_hours'] = date("H", $task_details['task_time']);
				$task_details['task_time_minutes'] = date("i", $task_details['task_time']);
				$task_details['task_time'] = fromMySQLDate(date("Y-m-d H:i", $task_details['task_time']), true);
				$task_details['task_abort'] = $task_details['task_abort'] ? unserialize($task_details['task_abort']) : array();

				if(isset($task_details['task_abort']['lastreplytime'])) $task_details['task_abort']['lastreplytime'] = fromMySQLDate($task_details['task_abort']['lastreplytime'], true);

				$tasks[$task_details['task_id']] = $task_details;
			}

			$predefined = array();

			$predefined_rows = Capsule::table('mod_ticketstasks_predefined')
				->orderBy('id', 'desc')
				->get();

			foreach($predefined_rows as $predefined_details)
			{
				$predefined_details = (array) $predefined_details;

				$predefined_details['task_abort'] = $predefined_details['task_abort'] ? unserialize($predefined_details['task_abort']) : array();
				$predefined[$predefined_details['id']] = $predefined_details;
			}

			$editor = '';

			if(file_exists(WHMCS_ROOT_PATH . '/assets/js/markdown.min.js'))
			{
				$editor .= '<script type="text/javascript" src="../assets/js/bootstrap-markdown.js"></script>';
				$editor .= '<link rel="stylesheet" type="text/css" href="../assets/css/bootstrap-markdown.min.css" />';
				$editor .= '<script type="text/javascript" src="../assets/js/markdown.min.js"></script>';
				$editor .= '<script type="text/javascript" src="../assets/js/to-markdown.js"></script>';
				$editor .= '<script type="text/javascript" src="../modules/addons/ticketstasks/js/editor.js"></script>';
			}

			$errors = '';

			if(sizeof($response) && !$response['success'])
			{
				$errors = '<div class="errorbox"><strong><span class="title">Error!</span></strong><br>' . $response['message'] . '</div>';
			}

			return "{$editor}<script type='text/javascript' src='../modules/addons/ticketstasks/js/sprintf.js'></script>
<style type=\"text/css\">
.ticketstasks-box .reply.note{
	box-shadow: 0 0 5px #EAF1F1 !important;
}
.ticketstasks-box .reply .rightcol {
	border-left: 1px solid #b4d0da !important;
}
.predefined-box {
	background-color: #fff;
	border: 1px solid #ccc;
	border-radius: 4px;
	margin: 10px auto;
	min-height: 24px;
	padding: 5px;
	width: 80%;
}
</style>
				
<script type='text/javascript'>

var ticket_statuses = " . (sizeof($instance->getTicketStatuses()) ? json_encode($instance->getTicketStatuses()) : '{}') . ";
var ticket_departments = " . (sizeof($instance->getTicketDepartments()) ? json_encode($instance->getTicketDepartments()) : '{}') . ";
var ticket_admins = " . (sizeof($instance->getAdmins()) ? json_encode($instance->getAdmins()) : '{}') . ";
var ticket_details = " . (sizeof($ticket_details) ? json_encode($ticket_details) : '{}') . ";
var tasks = " . (sizeof($tasks) ? json_encode($tasks) : '{}') . ";
var predefined = " . (sizeof($predefined) ? json_encode($predefined) : '{}') . ";
var addTaskTab = null;

$(document).ready(function() {

	" . ($errors ? "$('#content_padded').prepend('{$errors}');" : '') . " 
	$(\".tab\").unbind('click');

	addTaskTab = $('<li />').html($('<a/>').attr({ 'data-toggle': 'tab', role: 'tab', href: '#tabTicketTasks' }).text('" . $instance->lang('addtask') . "'));

	$('.nav-tabs.admin-tabs li').eq(2).after(addTaskTab);

	var html = '';

	html += '<form action=\"{$_SERVER['REQUEST_URI']}\" method=\"post\" id=\"addtaskform\">';

	html += '<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" class=\"form\"></tr>';
	html += '<td class=\"fieldlabel\" style=\"width: 25%\">" . $instance->lang('taskinterval') . ":</td><td class=\"fieldarea\">';
	html += '<select name=\"task[interval_type]\" class=\"form-control select-inline\">';
	html += '<option value=\"onetime\">One Time</option>';
	html += '<option value=\"repeated\">Repeated</option>';
	html += '</select>';
	html += '</td>';
	html += '</tr><tr>';
	html += '<td class=\"fieldlabel\">" . $instance->lang('settaskexecuted') . ":</td><td class=\"fieldarea\">';

	html += '<input type=\"text\" class=\"datepick form-control input-inline\" size=\"12\" value=\"" . date("d/m/Y") . "\" name=\"task[date]\" />';

	html += '<span style=\"margin-left: -10px;\">" . $instance->lang('at') . "</span> ';
	html += '<select name=\"task[hours]\" class=\"form-control select-inline\" style=\"min-width: auto;\">';

	for(var i = 0; i < 24; ++i)
	{
		html += '<option value=\"' + i + '\">' + (i < 10 ? '0'+i : i) + '</option>';
	}

	html += '</select>'

	html += ':<select name=\"task[minutes]\" class=\"form-control select-inline\" style=\"min-width: auto;\">';

	for(var i = 0; i < 6; ++i)
	{
		html += '<option value=\"' + i + '0\">' + i + '0</option>';
	}

	html += '</select>'

	html += '<span id=\"repeated_interval\" style=\"display: none;\">';
	html += ' <span>" . $instance->lang('executeevery') . "</span> ';
	
	html += '<select class=\"form-control select-inline\" name=\"task[interval]\" style=\"min-width: auto;\">';
	html += '<option value=\"day\">" . $instance->lang('day') . "</option>';
	html += '<option value=\"week\">" . $instance->lang('week') . "</option>';
	html += '<option value=\"month\">" . $instance->lang('month') . "</option>';
	html += '<option value=\"year\">" . $instance->lang('year') . "</option>';
	html += '</select>';
	html += '</span>';
	
	html += '</td>';
	html += '</tr><tr>';
	html += '<td class=\"fieldlabel\">" . $instance->lang('changestatus') . ":</td><td class=\"fieldarea\"><select name=\"task[status]\" class=\"form-control select-inline\">';

	$.each(ticket_statuses, function(i, data) {

		if(data.title != 'Pending Task')
		{
			var selected = (ticket_details.status == data.title ? ' selected=\"selected\"' : '');
			html += '<option value=\"' + data.id + '\" style=\"color: ' + data.color + '\"' + selected + '>' + data.title + '</option>';
		}
	});

	html += '</select></td>';


	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('changedept') . ":</td><td class=\"fieldarea\"><select name=\"task[dept]\" class=\"form-control select-inline\">';
	html += '<option value=\"0\">" . $instance->lang('dontchange') . "</option>';

	$.each(ticket_departments, function(i, data) {

		html += '<option value=\"' + data.id + '\">' + data.name + '</option>';
	});

	html += '</select></td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('changepriority') . ":</td><td class=\"fieldarea\"><select name=\"task[urgency]\" class=\"form-control select-inline\">';
	html += '<option value=\"\">" . $instance->lang('dontchange') . "</option>';
	html += '<option value=\"Low\">Low</option>';
	html += '<option value=\"Medium\">Medium</option>';
	html += '<option value=\"High\">High</option>';
	html += '</select></td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('changeflag') . ":</td><td class=\"fieldarea\"><select name=\"task[flag]\" class=\"form-control select-inline\">';
	html += '<option value=\"0\">" . $instance->lang('dontchange') . "</option>';

	$.each(ticket_admins, function(i, data) {

		html += '<option value=\"' + data.id + '\">' + data.firstname + ' ' + data.lastname + '</option>';
	});

	html += '</select>';

	html += ' <input type=\"checkbox\" name=\"task[flagemail]\" value=\"1\" /> " . $instance->lang('sendnotify') . "';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('bumpticket') . ":</td><td class=\"fieldarea\">';
	html += '<label class=\"radio-inline\"><input type=\"radio\" name=\"task[bump]\" value=\"0\" checked=\"checked\" /> " . $instance->lang('no') . "</label>';
	html += '<label class=\"radio-inline\"><input type=\"radio\" name=\"task[bump]\" value=\"1\" /> " . $instance->lang('yes') . "</label>';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('tasksactions')['abort'] . ":</td><td class=\"fieldarea\">';

	html += '<input type=\"checkbox\" name=\"task[abort][lastreply]\" value=\"1\" /> " . $instance->lang('tasksactions')['abort_lastreply'] . "';
	html += '<br />';
	html += '<input type=\"checkbox\" name=\"task[abort][invoice]\" value=\"1\" /> " . $instance->lang('tasksactions')['abort_invoice'] . " ';
	html += '<input type=\"text\" class=\"form-control input-300\" name=\"task[abort][invoiceids]\" value=\"\" style=\"width: 200px;\" /> " . $instance->lang('tasksactions')['abort_invoice_desc'] . "';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('addreply') . ":</td><td class=\"fieldarea\">';
	html += '<textarea id=\"taskreply\" name=\"task[reply]\" class=\"form-control\"></textarea>';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('addnote') . ":</td><td class=\"fieldarea\">';
	html += '<textarea id=\"tasknote\" name=\"task[note]\" class=\"form-control\"></textarea>';
	html += '</td>';

	html += '</tr><tr>';
		
	html += '<td class=\"fieldlabel\">" . $instance->lang('tools') . ":</td><td class=\"fieldarea\">';
	html += '<input type=\"button\" onclick=\"$(\'#insertpredeftask\').css(\'display\', $(\'#insertpredeftask\').is(\':visible\') ? \'none\' : \'\')\" class=\"btn btn-default\" value=\"Insert Pre-Defined Task\">';
		
	html += '<div style=\"display: none;\" id=\"insertpredeftask\">';
	html += '<div class=\"predefined-box\">';

	html += '</div>';
	html += '</div>';
		
	html += '</td>';

	html += '</tr></table>';

	html += '<img width=\"1\" height=\"10\" src=\"images/spacer.gif\" /><br />';

	html += '<div id=\"addtaskbtncontainer\" align=\"center\"><input type=\"submit\" class=\"btn btn-primary\" value=\"" . $instance->lang('addtask') . "\" /> <input type=\"reset\" onclick=\"$(\'select[name=task\\\[interval_type\\\]]\').change();\" class=\"btn btn-default\" value=\"" . $instance->lang('cancel') . "\"></div>';
	html += '<div id=\"edittaskbtncontainer\" align=\"center\" style=\"display: none;\"><input type=\"submit\" class=\"btn btn-primary\" value=\"" . $instance->lang('edittask') . "\" /> <input type=\"reset\" onclick=\"cancelEditTask();\" class=\"btn btn-default\" value=\"" . $instance->lang('cancel') . "\" /></div>';

	html += '<input type=\"hidden\" name=\"sub\" value=\"addtask\" />';
	html += '<input type=\"hidden\" name=\"task_id\" value=\"\" />';

	html += '</form>';

	var paddingTasks = '';

	if(tasks !== undefined)
	{
		$.each(tasks, function(id, data) {

			paddingTasks += '<div id=\"task' + data.task_id + '\" class=\"reply note tickettasks\" style=\"background: #EAF1F1; border: 1px dashed #b4d0da;\">';
			
			paddingTasks += '<div class=\"leftcol\">';
			paddingTasks += '<div class=\"submitter\">';
			paddingTasks += '<div class=\"name\">' + ticket_admins[data.admin_id].firstname + ' ' + ticket_admins[data.admin_id].lastname + '</div>';
			paddingTasks += '<div class=\"title\">' + (data.task_status == 'Completed' ? '<strong style=\"color: #1cb536;\">" . $instance->lang('completed') . "' : (data.task_status == 'Pending' ? '<strong style=\"color: #ff6600;\">" . $instance->lang('pending') . "' : '<strong style=\"color: #CC0000;\">" . $instance->lang('aborted') . "')) + '</strong></div>';
			paddingTasks += '</div>';
			
			paddingTasks += '<div class=\"tools\">';
			paddingTasks += '<div class=\"editbtnsr8\">';

			if(data.task_status == 'Pending') paddingTasks += '<input type=\"button\" class=\"btn btn-xs btn-small btn-default\" onclick=\"return doEditTask(\'' + id + '\');\" value=\"" . $instance->lang('edit') . "\"> ';
			paddingTasks += '<input type=\"button\" class=\"btn btn-xs btn-small btn-danger\" onclick=\"return doDeleteTask(\'' + id + '\');\" value=\"" . $instance->lang('delete') . "\">';
			
			
			paddingTasks += '</div>';
			paddingTasks += '</div>';

			paddingTasks += '</div>';
			paddingTasks += '<div class=\"rightcol\">';
			paddingTasks += '<div class=\"postedon\">' + sprintf('" . $instance->lang('taskaddedby') . "', ticket_admins[data.admin_id].firstname + ' ' + ticket_admins[data.admin_id].lastname, (data.task_status == 'Pending' ? '" . $instance->lang('taskadded_pending') . "' : (data.task_status == 'Aborted' ? '" . $instance->lang('taskadded_aborted') . "' : '" . $instance->lang('taskadded_completed') . "')), data.task_time) + (data.task_repeat ? ' (' + sprintf('" . $instance->lang('taskrepeatevery') . "', data.task_repeat.charAt(0).toUpperCase() + data.task_repeat.slice(1)) + ')' : '') + '</div>';
			paddingTasks += '<div class=\"msgwrap\">';
			paddingTasks += '<div class=\"message markdown-content\">';

			paddingTasks += '<ul style=\"list-style: disc !important;\">';
			paddingTasks += (parseInt(data.ticket_status_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['status'] . " <strong style=\"color: ' + ticket_statuses[data.ticket_status_id].color + '\">' + ticket_statuses[data.ticket_status_id].title + '</strong></li>' : '');
			paddingTasks += (parseInt(data.ticket_admin_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['flag'] . " <strong>' + ticket_admins[data.ticket_admin_id].firstname + ' ' + ticket_admins[data.ticket_admin_id].lastname + '</strong>' + (parseInt(data.ticket_admin_email) ? ' " . $instance->lang('andsendnotify') . "' : '') + '</li>' : '');
			paddingTasks += (parseInt(data.ticket_dept_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['dept'] . " <strong>' + ticket_departments[data.ticket_dept_id].name + '</strong></li>' : '');
			paddingTasks += (data.ticket_urgency ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['priority'] . " <strong>' + data.ticket_urgency + '</strong></li>' : '');
			paddingTasks += (data.ticket_bump == '1' ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['bump'] . "</li>' : '');
			paddingTasks += (data.ticket_reply ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['reply'] . "</li>' : '');
			paddingTasks += (data.ticket_note ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['note'] . "</li>' : '');

			if($(data.task_abort).length)
			{
				paddingTasks += '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['abort'] . "<ul>';

				paddingTasks += (data.task_abort.lastreply ? '<li style=\"list-style: circle !important;\">" . $instance->lang('tasksactions')['abort_lastreply'] . " - " . $instance->lang('lastreply') . ": ' + data.task_abort.lastreplytime + '</li>' : '');
				paddingTasks += (data.task_abort.invoice ? '<li style=\"list-style: circle !important;\">" . $instance->lang('tasksactions')['abort_invoice'] . " - ' + data.task_abort.invoiceids + '</li>' : '');

				paddingTasks += '</ul></li>';
			}

			paddingTasks += '</ul>';
			
			paddingTasks += '</div>';
			paddingTasks += '</div>';
			paddingTasks += '</div>';
			paddingTasks += '</div>';
		});

		if(paddingTasks) paddingTasks = '<br /><h2>" . $instance->lang('tickettasks') . "</h2><div id=\"ticketreplies\" class=\"ticketstasks-box\">' + paddingTasks + '</div>';
	}

	$('.tab-content.admin-tabs').append('<div id=\"tabTicketTasks\" class=\"tab-pane\">' + html + '</div>' + paddingTasks);

	var totalPredefined = 0;
		
	$.each(predefined, function(predefined_id, predefined_details) { totalPredefined++; });
		
	if(totalPredefined)
	{
		var ul = $('<ul />').css({ margin: 0 });
		
		$.each(predefined, function(predefined_id, predefined_details) {

			var li = $('<li />');
			
			$('<a />').attr({ href: '#' }).text(predefined_details.title).click(function() {

				$('form#addtaskform')[0].reset();
				$('select[name=task\\\[interval_type\\\]]').val(predefined_details.task_repeat ? 'repeated' : 'onetime').change();
				$('select[name=task\\\[interval\\\]]').val(predefined_details.task_repeat);
				$('select[name=task\\\[hours\\\]]').val(parseInt(predefined_details.task_hours));
				$('select[name=task\\\[minutes\\\]]').val(predefined_details.task_minutes);
				$('select[name=task\\\[status\\\]]').val(predefined_details.ticket_status_id);
				$('select[name=task\\\[flag\\\]]').val(predefined_details.ticket_admin_id);
				$('input[name=task\\\[flagemail\\\]]').prop('checked', predefined_details.ticket_admin_email == '1');
				$('select[name=task\\\[dept\\\]]').val(predefined_details.ticket_dept_id);
				$('select[name=task\\\[urgency\\\]]').val(predefined_details.ticket_urgency);
				$('input[name=task\\\[bump\\\]][value=' + predefined_details.ticket_bump + ']').attr('checked', 'checked');
				$('textarea[name=task\\\[reply\\\]]').val(predefined_details.ticket_reply);
				$('textarea[name=task\\\[note\\\]]').val(predefined_details.ticket_note);
				$('input[name=task\\\[abort\\\]\\\[lastreply\\\]]').prop('checked', (predefined_details.task_abort.lastreply == '1'));
				$('input[name=task\\\[abort\\\]\\\[invoice\\\]]').prop('checked', false);
				$('input[name=task\\\[abort\\\]\\\[invoiceids\\\]]').val('');
			
				return false;
			}).appendTo(li);

			ul.append(li);		
		});
			
		$('.predefined-box').append(ul);
	}
	else
	{
		$('.predefined-box').append('No Pre-Defined tasks was found - <a href=\"addonmodules.php?module=ticketstasks&pagename=predefined&view=manage\" target=\"_blank\">Click here to add new Pre-Defined task</a>');
	}

	" . (file_exists(WHMCS_ROOT_PATH . '/assets/js/markdown.min.js') ? '$("#taskreply, #tasknote").TicketMDE({locale: \'' . $_ADMINLANG['locale'] . '\',token: $(\'input[name=token]\').val(),});' : '') . "
			
	$('.datepick').datepicker({
		dateFormat: 'dd/mm/yy',
		showOn: 'button',
		buttonImage: 'images/showcalendar.gif',
		buttonImageOnly: true,
		showButtonPanel: true
	});
	
	$('select[name=task\\\[interval_type\\\]]').change(function() {

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

function doDeleteTask(id)
{
	if(confirm('" . $instance->lang('deletetasksure') . "'))
	{
		window.location='supporttickets.php?action=viewticket&id='+ticketid+'&sub=deltask&idsd='+id+'&token='+csrfToken;
	}
}

function cancelEditTask()
{
	$('form#addtaskform').trigger('reset');

	$('#addtaskbtncontainer').css('display', '');
	$('#edittaskbtncontainer').css('display', 'none');

	$('form#addtaskform input[name=task_id]').val('');

	$('select[name=task\\\[interval_type\\\]]').change();
	$('input[name=task\\\[bump\\\]][value=0]').attr('checked', 'checked');
	$('input[name=task\\\[bump\\\]][value=1]').removeAttr('checked');

	$('input[name=task\\\[flagemail\\\]]').removeAttr('checked');

	return false;
}

function doEditTask(id)
{
	if(addTaskTab)
	{
		$('#addtaskbtncontainer').css('display', 'none');
		$('#edittaskbtncontainer').css('display', '');

		$('form#addtaskform input[name=task_id]').val(id);

		$('select[name=task\\\[interval_type\\\]]').val(tasks[id].task_repeat ? 'repeated' : 'onetime').change();
		$('input[name=task\\\[date\\\]]').val(tasks[id].task_time_date);
		$('select[name=task\\\[interval\\\]]').val(tasks[id].task_repeat);
		$('select[name=task\\\[hours\\\]]').val(parseInt(tasks[id].task_time_hours));
		$('select[name=task\\\[minutes\\\]]').val(tasks[id].task_time_minutes);
		$('select[name=task\\\[status\\\]]').val(tasks[id].ticket_status_id);
		$('select[name=task\\\[flag\\\]]').val(tasks[id].ticket_admin_id);
		$('input[name=task\\\[flagemail\\\]]').prop('checked', tasks[id].ticket_admin_email == '1');
		$('select[name=task\\\[dept\\\]]').val(tasks[id].ticket_dept_id);
		$('select[name=task\\\[urgency\\\]]').val(tasks[id].ticket_urgency);
		$('input[name=task\\\[bump\\\]][value=' + tasks[id].ticket_bump + ']').attr('checked', 'checked');
		$('textarea[name=task\\\[reply\\\]]').val(tasks[id].ticket_reply);
		$('textarea[name=task\\\[note\\\]]').val(tasks[id].ticket_note);
		$('input[name=task\\\[abort\\\]\\\[lastreply\\\]]').prop('checked', (tasks[id].task_abort.lastreply !== undefined));
		$('input[name=task\\\[abort\\\]\\\[invoice\\\]]').prop('checked', (tasks[id].task_abort.invoice !== undefined));
		$('input[name=task\\\[abort\\\]\\\[invoiceids\\\]]').val(tasks[id].task_abort.invoiceids);

		$('a', addTaskTab).click();
	}

	return false;
}
</script>";
		}
		else
		{
			// if the list view is disabled exit the function
			if(!$instance->getConfig('list_active')) return;

			if($_REQUEST['ajax'])
			{
				switch($_REQUEST['oparation'])
				{
					case 'gettickets':

						if(is_array($_REQUEST['tickets']) && sizeof($_REQUEST['tickets']))
						{
							$tickets = $tasks = $statuses = array();

							foreach($_REQUEST['tickets'] as $ticket_id)
							{
								if(intval($ticket_id)) $tickets[] = intval($ticket_id);
							}

							$total_tasks = array();

							if(sizeof($tickets))
							{
								$task_rows = Capsule::table('mod_ticketstasks_tasks as s')
									->select('s.*', 's.id as task_id', 't.*')
									->join('tbltickets as t', 's.ticket_id', '=', 't.id')
									->whereIn('s.ticket_id', $tickets)
									->orderBy('s.task_time', 'asc')
									->get();

								foreach($task_rows as $task_details)
								{
									$task_details = (array) $task_details;

									$task_details['task_time_date'] = date("d/m/Y", $task_details['task_time']);
									$task_details['task_time_hours'] = date("H", $task_details['task_time']);
									$task_details['task_time_minutes'] = date("i", $task_details['task_time']);
									$task_details['task_time'] = fromMySQLDate(date("Y-m-d H:i", $task_details['task_time']), true);
									$task_details['task_abort'] = $task_details['task_abort'] ? unserialize($task_details['task_abort']) : array();

									if(isset($task_details['task_abort']['lastreplytime'])) $task_details['task_abort']['lastreplytime'] = fromMySQLDate($task_details['task_abort']['lastreplytime'], true);


									$tasks[$task_details['ticket_id']][$task_details['task_id']] = $task_details;
									if($task_details['task_status'] == 'Pending') $total_tasks[$task_details['ticket_id']]++;
								}

								$ticket_rows = Capsule::table('tbltickets')
									->select('id', 'status')
									->whereIn('id', $tickets)
									->get();

								foreach($ticket_rows as $ticket_details)
								{
									$statuses[$ticket_details->id] = $ticket_details->status;
								}
							}

							echo json_encode(array('success' => true, 'tasks' => $tasks, 'statuses' => $statuses, 'total_tasks' => $total_tasks));
							exit;
						}
					break;

					case 'addtask':

						$task = array();

						foreach($_REQUEST['fields'] as $field_data)
						{
							eval("\${$field_data['name']} = '{$field_data['value']}';");
						}

						if(intval($_REQUEST['task_id']))
						{
							$response = ticketstasks::editTask(ticketstasks::request_var('ticket_id', 0), ticketstasks::request_var('task_id', 0), $task);
						}
						else
						{
							$response = ticketstasks::addTask(ticketstasks::request_var('ticket_id', 0), $task);
						}

						echo json_encode(array('success' => $response['success'], 'task' => $response['task'], 'message' => $response['message']));
						exit;

					break;

					case 'deletetask':

						$deleteTask = ticketstasks::deleteTask(ticketstasks::request_var('ticket_id', 0), ticketstasks::request_var('task_id', 0));

						echo json_encode(array('success' => $deleteTask['success'], 'message' => $deleteTask['message']));
						exit;

					break;
				}

				echo json_encode(array('success' => false, 'message' => 'Unknown Error'));
				exit;
			}


			$filt = new WHMCS\Filter( "tickets" );

			$request_url = array();
			$request_url_ary = array('view', 'deptid', 'client', 'subject', 'email', 'tag');

			foreach($request_url_ary as $request_url_type)
			{
				$val = $filt->get($request_url_type);

				if($val) $request_url[] = "{$request_url_type}={$val}";
			}

			$predefined = array();

			$predefined_rows = Capsule::table('mod_ticketstasks_predefined')
				->orderBy('id', 'desc')
				->get();

			foreach($predefined_rows as $predefined_details)
			{
				$predefined_details = (array) $predefined_details;
				$predefined_details['task_abort'] = $predefined_details['task_abort'] ? unserialize($predefined_details['task_abort']) : array();
				$predefined[$predefined_details['id']] = $predefined_details;
			}

			$editor = '';

			if(file_exists(WHMCS_ROOT_PATH . '/assets/js/markdown.min.js'))
			{
				$editor .= '<script type="text/javascript" src="../assets/js/bootstrap-markdown.js"></script>';
				$editor .= '<link rel="stylesheet" type="text/css" href="../assets/css/bootstrap-markdown.min.css" />';
				$editor .= '<script type="text/javascript" src="../assets/js/markdown.min.js"></script>';
				$editor .= '<script type="text/javascript" src="../assets/js/to-markdown.js"></script>';
				$editor .= '<script type="text/javascript" src="../modules/addons/ticketstasks/js/editor.js"></script>';
			}

			return "{$editor}<script type='text/javascript' src='../modules/addons/ticketstasks/js/sprintf.js'></script>

<style type=\"text/css\">
.ticketstasks-box .reply.note{
	box-shadow: 0 0 5px #EAF1F1 !important;
}
.ticketstasks-box .reply .rightcol {
	border-left: 1px solid #b4d0da !important;
}
.predefined-box {
	background-color: #fff;
	border: 1px solid #ccc;
	border-radius: 4px;
	margin: 10px auto;
	min-height: 24px;
	padding: 5px;
	width: 80%;
}
</style>

<script type='text/javascript'>

var ticket_statuses = " . (sizeof($instance->getTicketStatuses()) ? json_encode($instance->getTicketStatuses()) : '{}') . ";
var ticket_departments = " . (sizeof($instance->getTicketDepartments()) ? json_encode($instance->getTicketDepartments()) : '{}') . ";
var ticket_admins = " . (sizeof($instance->getAdmins()) ? json_encode($instance->getAdmins()) : '{}') . ";
var statuses = {};
var tasks = {};
var predefined = " . (sizeof($predefined) ? json_encode($predefined) : '{}') . ";
var request_uri = '{$_SERVER['REQUEST_URI']}" . (sizeof($request_url) ? (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . implode('&', $request_url) : '') . "';

$(document).ready(function() {

	setTasksBar($('#content_padded form').eq(-1));
	setTasksBar($('#content_padded form').eq(-3));
});

function setTasksBar(form)
{
	var tickets = [];

	$('th', form).eq(1).after('<th width=\"20\"></th>');

	$('tr', form).each(function(i) {

		if($(this).children('td').length)
		{
			tickets[i] = $(this).children('td:first').children('input').val();

			var td = $('<td/>').attr({ id: 'tasks' + tickets[i] }).css('text-align', 'center');
			$(this).children('td').eq(1).after(td);
			td.append($('<img/>').attr({ src: '../assets/img/spinner.gif', alt: '' }));
		}
	});

	$.ajax({
		url: request_uri,
		data: {
			token: $('input[name=token]').val(),
			oparation: 'gettickets',
			tickets: tickets,
			ajax: 1
		},
		dataType: 'json',
		success: function(data) {

			if(data.success)
			{
				tasks = $.extend(data.tasks, tasks);
				statuses = $.extend(data.statuses, statuses);

				$('tr', form).each(function() {

					if($(this).children('td').length) 
					{ 
						var ticket_id = $(this).children('td:first').children('input').val();
						var td = $('td#tasks' + ticket_id);

						var link = $('<a/>').attr({ href: 'javascript: void(0);', onclick: 'addTask(' + ticket_id + ')' }).css({ position: 'relative' });
						var img = $('<img/>').attr({ src: './images/icons/todolist.png', alt: '' });
						var ticketTasks = (data.total_tasks[ticket_id] !== undefined) ? data.total_tasks[ticket_id] : 0;

						//if(tasks[ticket_id] !== undefined) $.each(tasks[ticket_id], function() { ticketTasks++; });

						td.html(link.append(img).append('<span id=\"taskscount' + ticket_id + '\" style=\"display: ' + (ticketTasks > 0 ? 'inline-block' : 'none') + '; position: absolute; top: -7px; right: -7px; background: #CC0000; color: #fff; border-radius: 10px; width: 14px; height: 14px; font-size: 10px; font-weight: bold;\"><span style=\"display: block; vertical-align: middle;\">' + ticketTasks + '</span></span>'));
					} 
				});
			}
		}
	});
}

function doDeleteTask(ticket_id, id)
{
	if(confirm('" . $instance->lang('deletetasksure') . "'))
	{
		$.ajax({
			url: request_uri,
			data: {
				token: $('input[name=token]').val(),
				oparation: 'deletetask',
				ticket_id: ticket_id,
				task_id: id,
				ajax: 1
			},
			dataType: 'json',
			success: function(data) {

				if(data.success)
				{
					$('.taskmsgbox.successbox').css('display', '');
					$('.taskmsgbox.successbox .taskmsgcontent').html('Task Deleted Successfully');

					$('#task' + id).fadeOut('slow', function() { 

						$(this).remove(); 

						if(tasks[ticket_id][id] !== undefined)
						{
							var newTasks = {};

							$.each(tasks[ticket_id], function(task_id, task_data) {
								if(task_id != id) newTasks[task_id] = task_data;
							});

							tasks[ticket_id] = newTasks;
						}

						if(!countObject(tasks[ticket_id]))
						{
							var newTasks = {};

							$.each(tasks, function(ticketid, tasksids) {
								if(ticketid != ticket_id) newTasks[ticketid] = tasksids;
							});

							tasks = newTasks;
						}

						var totalTasks = parseInt($('#taskscount' + ticket_id + ' span').text())-1;

						$('#taskscount' + ticket_id + ' span').text(totalTasks);
						$('#taskscount' + ticket_id).css('display', (totalTasks > 0 ? 'inline-block' : 'none'));

						if(totalTasks <= 0) $('#notasks').fadeIn('slow');
					});
				}
				else
				{
					$('.taskmsgbox.errorbox').css('display', '');
					$('.taskmsgbox.errorbox .taskmsgcontent').html(data.message);
				}
			}
		});
	}
	
	return false;
}

function countObject(obj)
{
	var count = 0;
	$.each(obj, function() { count++; });

	return count;
}

function cancelEditTask()
{
	$('form#addtaskform').trigger('reset');

	$('#addtaskbtncontainer').css('display', '');
	$('#edittaskbtncontainer').css('display', 'none');

	$('form#addtaskform input[name=task_id]').val('');

	$('select[name=task\\\[interval_type\\\]]').change();
	$('input[name=task\\\[bump\\\]][value=0]').attr('checked', 'checked');
	$('input[name=task\\\[bump\\\]][value=1]').removeAttr('checked');

	$('input[name=task\\\[flagemail\\\]]').removeAttr('checked');
	
	return false;
}

function doEditTask(ticket_id, task_id)
{
	$('#addtaskbtncontainer').css('display', 'none');
	$('#edittaskbtncontainer').css('display', '');

	$('input[name=task_id]').val(task_id);

	$('select[name=task\\\[interval_type\\\]]').val(tasks[ticket_id][task_id].task_repeat ? 'repeated' : 'onetime').change();
	$('input[name=task\\\[date\\\]]').val(tasks[ticket_id][task_id].task_time_date);
	$('select[name=task\\\[interval\\\]]').val(tasks[ticket_id][task_id].task_repeat);
	$('select[name=task\\\[hours\\\]]').val(parseInt(tasks[ticket_id][task_id].task_time_hours));
	$('select[name=task\\\[minutes\\\]]').val(tasks[ticket_id][task_id].task_time_minutes);
	$('select[name=task\\\[status\\\]]').val(tasks[ticket_id][task_id].ticket_status_id);
	$('select[name=task\\\[flag\\\]]').val(tasks[ticket_id][task_id].ticket_admin_id);
	$('input[name=task\\\[flagemail\\\]]').prop('checked', tasks[ticket_id][task_id].ticket_admin_email == '1');
	$('select[name=task\\\[dept\\\]]').val(tasks[ticket_id][task_id].ticket_dept_id);
	$('select[name=task\\\[urgency\\\]]').val(tasks[ticket_id][task_id].ticket_urgency);
	$('input[name=task\\\[bump\\\]][value=' + tasks[ticket_id][task_id].ticket_bump + ']').attr('checked', 'checked');
	$('textarea[name=task\\\[reply\\\]]').val(tasks[ticket_id][task_id].ticket_reply);
	$('textarea[name=task\\\[note\\\]]').val(tasks[ticket_id][task_id].ticket_note);
	$('input[name=task\\\[abort\\\]\\\[lastreply\\\]]').prop('checked', (tasks[ticket_id][task_id].task_abort.lastreply !== undefined));
	$('input[name=task\\\[abort\\\]\\\[invoice\\\]]').prop('checked', (tasks[ticket_id][task_id].task_abort.invoice !== undefined));
	$('input[name=task\\\[abort\\\]\\\[invoiceids\\\]]').val(tasks[ticket_id][task_id].task_abort.invoiceids);

	$('#taskstab1').click();
	
	return false;
}

function addTask(ticket_id)
{
	var task_data = tasks[ticket_id];
		
	var winWidth = $(window).outerWidth();
	var winHeight = $(window).outerHeight();
	var winTop = $(window).scrollTop();
	var boxWidth = 650;

	var shadow = $('<div/>').css({ display: 'none', width: '100%', height: '100%', background: '#000000', opacity: '0.5', zIndex: '10', position: 'fixed', left: 0, top: 0 });
	var box = $('<div/>').css({ display: 'none', borderRadius: '10px', width: boxWidth + 'px', background: '#fff', zIndex: '11', position: 'absolute', left: (winWidth / 2 - boxWidth / 2), top: 0 });

	var boxCloseBtn = $('<div/>').css({ width: '15px', height: '15px', marginRight: '10px', float: 'left', cursor: 'pointer', background: 'url(../modules/addons/ticketstasks/images/close.gif) 0 0 no-repeat' });
	var boxTitle = $('<div/>').css({ borderRadius: '10px 10px 0 0', background: '#1A4D80', color: '#fff', margin: '-7px -7px 10px -7px', padding: '5px 10px', fontWeight: 'bold' });
	var boxContent = $('<div/>').css({ padding: '10px' });

	boxContent.append(boxTitle.html('Ticket Tasks').append(boxCloseBtn));

	var html = '';

	html += '<ul style=\"width: auto;\" class=\"nav nav-tabs admin-tabs\">';
	html += '<li id=\"taskstab0\" class=\"tab\"><a href=\"javascript:;\">" . $instance->lang('tasks') . "</a></li>';
	html += '<li id=\"taskstab1\" class=\"tab\"><a href=\"javascript:;\">" . $instance->lang('addtask') . "</a></li>';
	html += '</ul>';

	html += '<div class=\"tab-content admin-tabs\">';


	html += '<div class=\"tab-pane\" id=\"taskstab1box\">';
	html += '<form action=\"\" method=\"post\" id=\"addtaskform\">';

	html += '<div class=\"taskmsgbox errorbox\" style=\"display: none;\"><strong><span class=\"title\">" . $instance->lang('error') . "!</span></strong><br><span class=\"taskmsgcontent\"></span></div>';

	html += '<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" class=\"form\"></tr>';

	
	html += '<td class=\"fieldlabel\" style=\"width: 25%\">" . $instance->lang('taskinterval') . ":</td><td class=\"fieldarea\">';
	html += '<select name=\"task[interval_type]\" class=\"form-control select-inline\">';
	html += '<option value=\"onetime\">One Time</option>';
	html += '<option value=\"repeated\">Repeated</option>';
	html += '</select>';
	html += '</td>';
	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('settaskexecuted') . ":</td><td class=\"fieldarea\"><input type=\"text\" class=\"datepick form-control input-inline\" size=\"12\" value=\"" . date("d/m/Y") . "\" name=\"task[date]\" /> <span>" . $instance->lang('at') . "</span> ';
	html += '<select name=\"task[hours]\" class=\"form-control select-inline\" style=\"min-width: auto;\">';

	for(var i = 0; i < 24; ++i)
	{
		html += '<option value=\"' + i + '\">' + (i < 10 ? '0'+i : i) + '</option>';
	}

	html += '</select>'

	html += ':<select name=\"task[minutes]\" class=\"form-control select-inline\" style=\"min-width: auto;\">';

	for(var i = 0; i < 6; ++i)
	{
		html += '<option value=\"' + i + '0\">' + i + '0</option>';
	}

	html += '</select>'
	
	html += '<span id=\"repeated_interval\" style=\"display: none;\">';
	html += ' <span>" . $instance->lang('executeevery') . "</span> ';
	
	html += '<select class=\"form-control select-inline\" name=\"task[interval]\" style=\"min-width: auto;\">';
	html += '<option value=\"day\">" . $instance->lang('day') . "</option>';
	html += '<option value=\"week\">" . $instance->lang('week') . "</option>';
	html += '<option value=\"month\">" . $instance->lang('month') . "</option>';
	html += '<option value=\"year\">" . $instance->lang('year') . "</option>';
	html += '</select>';
	html += '</span>';

	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('changestatus') . ":</td><td class=\"fieldarea\"><select name=\"task[status]\" class=\"form-control select-inline\">';

	$.each(ticket_statuses, function(i, data) {

		if(data.title != 'Pending Task')
		{
			var selected = (statuses[ticket_id] == data.title ? ' selected=\"selected\"' : '');
			html += '<option value=\"' + data.id + '\" style=\"color: ' + data.color + '\"' + selected + '>' + data.title + '</option>';
		}
	});

	html += '</select></td>';


	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('changedept') . ":</td><td class=\"fieldarea\"><select name=\"task[dept]\" class=\"form-control select-inline\">';
	html += '<option value=\"0\">" . $instance->lang('dontchange') . "</option>';

	$.each(ticket_departments, function(i, data) {

		html += '<option value=\"' + data.id + '\">' + data.name + '</option>';
	});

	html += '</select></td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('changepriority') . ":</td><td class=\"fieldarea\"><select name=\"task[urgency]\" class=\"form-control select-inline\">';
	html += '<option value=\"\">" . $instance->lang('dontchange') . "</option>';
	html += '<option value=\"Low\">Low</option>';
	html += '<option value=\"Medium\">Medium</option>';
	html += '<option value=\"High\">High</option>';
	html += '</select></td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('changeflag') . ":</td><td class=\"fieldarea\"><select name=\"task[flag]\" class=\"form-control select-inline\">';
	html += '<option value=\"0\">" . $instance->lang('dontchange') . "</option>';

	$.each(ticket_admins, function(i, data) {

		html += '<option value=\"' + data.id + '\">' + data.firstname + ' ' + data.lastname + '</option>';
	});

	html += '</select>';

	html += ' <input type=\"checkbox\" name=\"task[flagemail]\" value=\"1\" /> " . $instance->lang('sendnotify') . "';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('bumpticket') . ":</td><td class=\"fieldarea\">';
	html += '<label class=\"radio-inline\"><input type=\"radio\" name=\"task[bump]\" value=\"0\" checked=\"checked\" /> No</label>';
	html += '<label class=\"radio-inline\"><input type=\"radio\" name=\"task[bump]\" value=\"1\" /> Yes</label>';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('tasksactions')['abort'] . ":</td><td class=\"fieldarea\">';

	html += '<input type=\"checkbox\" name=\"task[abort][lastreply]\" value=\"1\" /> " . $instance->lang('tasksactions')['abort_lastreply'] . "';
	html += '<br />';
	html += '<input type=\"checkbox\" name=\"task[abort][invoice]\" value=\"1\" /> " . $instance->lang('tasksactions')['abort_invoice'] . " ';
	html += '<input type=\"text\" name=\"task[abort][invoiceids]\" value=\"\" class=\"form-control\" /><br />" . $instance->lang('tasksactions')['abort_invoice_desc'] . "';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('addreply') . ":</td><td class=\"fieldarea\">';
	html += '<textarea id=\"taskreply' + ticket_id + '\" name=\"task[reply]\" class=\"form-control\"></textarea>';
	html += '</td>';

	html += '</tr><tr>';

	html += '<td class=\"fieldlabel\">" . $instance->lang('addnote') . ":</td><td class=\"fieldarea\">';
	html += '<textarea id=\"tasknote' + ticket_id + '\" name=\"task[note]\" class=\"form-control\"></textarea>';
	html += '</td>';


	html += '</tr><tr>';
		
	html += '<td class=\"fieldlabel\">" . $instance->lang('tools') . ":</td><td class=\"fieldarea\">';
	html += '<input type=\"button\" onclick=\"$(\'#insertpredeftask' + ticket_id + '\').css(\'display\', $(\'#insertpredeftask' + ticket_id + '\').is(\':visible\') ? \'none\' : \'\')\" class=\"btn btn-default\" value=\"Insert Pre-Defined Task\">';
		
	html += '<div style=\"display: none;\" id=\"insertpredeftask' + ticket_id + '\">';
	html += '<div class=\"predefined-box' + ticket_id + '\">';

	html += '</div>';
	html += '</div>';
		
	html += '</td>';

	html += '</tr></table>';
		
	html += '<img width=\"1\" height=\"10\" src=\"images/spacer.gif\" /><br />';

	html += '<div align=\"center\"><img style=\"display: none;\" class=\"addtaskloader\" src=\"../assets/img/spinner.gif\" alt=\"\" />';
	html += '<div id=\"addtaskbtncontainer\" align=\"center\"><input type=\"submit\" class=\"btn btn-primary addtaskbtn\" onclick=\"return false;\" value=\"" . $instance->lang('addtask') . "\" /></div>';
	html += '<div id=\"edittaskbtncontainer\" align=\"center\" style=\"display: none;\"><input type=\"submit\" class=\"btn btn-primary addtaskbtn\" onclick=\"return false;\" value=\"" . $instance->lang('edittask') . "\" /> <input type=\"reset\" onclick=\"cancelEditTask();\" class=\"btn btn-default\" value=\"" . $instance->lang('cancel') . "\" /></div>';
	html += '</div>';

	html += '<input type=\"hidden\" name=\"sub\" value=\"addtask\" />';
	html += '<input type=\"hidden\" name=\"task_id\" value=\"\" />';

	html += '</form>';
	html += '</div>';


	html += '<div class=\"tab-pane\" id=\"taskstab0box\">';
	html += '<div class=\"taskmsgbox successbox\" style=\"display: none;\"><strong><span class=\"title\">" . $instance->lang('success') . "!</span></strong><br><span class=\"taskmsgcontent\"></span></div>';

	html += '<div id=\"taskstable\">';

	html += '<div style=\"text-align: center;' + (task_data !== undefined ? ' display: none;' : '') + '\" id=\"notasks\">" . $instance->lang('notasks') . "</div>';

	if(task_data !== undefined)
	{
		var paddingTasks = '';
		
		$.each(task_data, function(id, data) {

			paddingTasks += '<div id=\"task' + data.task_id + '\" class=\"reply note tickettasks\" style=\"background: #EAF1F1; border: 1px dashed #b4d0da;\">';
			
			paddingTasks += '<div class=\"leftcol\">';
			paddingTasks += '<div class=\"submitter\">';
			paddingTasks += '<div class=\"name\">' + ticket_admins[data.admin_id].firstname + ' ' + ticket_admins[data.admin_id].lastname + '</div>';
			paddingTasks += '<div class=\"title\">' + (data.task_status == 'Completed' ? '<strong style=\"color: #1cb536;\">" . $instance->lang('completed') . "' : (data.task_status == 'Pending' ? '<strong style=\"color: #ff6600;\">" . $instance->lang('pending') . "' : '<strong style=\"color: #CC0000;\">" . $instance->lang('aborted') . "')) + '</strong></div>';
			paddingTasks += '</div>';
			
			paddingTasks += '<div class=\"tools\">';
			paddingTasks += '<div class=\"editbtnsr8\">';

			if(data.task_status == 'Pending') paddingTasks += '<input type=\"button\" class=\"btn btn-xs btn-small btn-default\" onclick=\"return doEditTask(\'' + ticket_id + '\', \'' + id + '\');\" value=\"" . $instance->lang('edit') . "\"> ';
			paddingTasks += '<input type=\"button\" class=\"btn btn-xs btn-small btn-danger\" onclick=\"return doDeleteTask(\'' + ticket_id + '\', \'' + id + '\');\" value=\"" . $instance->lang('delete') . "\">';
			
			
			paddingTasks += '</div>';
			paddingTasks += '</div>';

			paddingTasks += '</div>';
			paddingTasks += '<div class=\"rightcol\">';
			paddingTasks += '<div class=\"postedon\">' + sprintf('" . $instance->lang('taskaddedby') . "', ticket_admins[data.admin_id].firstname + ' ' + ticket_admins[data.admin_id].lastname, (data.task_status == 'Pending' ? '" . $instance->lang('taskadded_pending') . "' : (data.task_status == 'Aborted' ? '" . $instance->lang('taskadded_aborted') . "' : '" . $instance->lang('taskadded_completed') . "')), data.task_time) + (data.task_repeat ? ' (' + sprintf('" . $instance->lang('taskrepeatevery') . "', data.task_repeat.charAt(0).toUpperCase() + data.task_repeat.slice(1)) + ')' : '') + '</div>';
			paddingTasks += '<div class=\"msgwrap\">';
			paddingTasks += '<div class=\"message markdown-content\">';

			paddingTasks += '<ul style=\"list-style: disc !important;\">';
			paddingTasks += (parseInt(data.ticket_status_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['status'] . " <strong style=\"color: ' + ticket_statuses[data.ticket_status_id].color + '\">' + ticket_statuses[data.ticket_status_id].title + '</strong></li>' : '');
			paddingTasks += (parseInt(data.ticket_admin_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['flag'] . " <strong>' + ticket_admins[data.ticket_admin_id].firstname + ' ' + ticket_admins[data.ticket_admin_id].lastname + '</strong>' + (parseInt(data.ticket_admin_email) ? ' " . $instance->lang('andsendnotify') . "' : '') + '</li>' : '');
			paddingTasks += (parseInt(data.ticket_dept_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['dept'] . " <strong>' + ticket_departments[data.ticket_dept_id].name + '</strong></li>' : '');
			paddingTasks += (data.ticket_urgency ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['priority'] . " <strong>' + data.ticket_urgency + '</strong></li>' : '');
			paddingTasks += (data.ticket_bump == '1' ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['bump'] . "</li>' : '');
			paddingTasks += (data.ticket_reply ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['reply'] . "</li>' : '');
			paddingTasks += (data.ticket_note ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['note'] . "</li>' : '');

			if($(data.task_abort).length)
			{
				paddingTasks += '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['abort'] . "<ul>';

				paddingTasks += (data.task_abort.lastreply ? '<li style=\"list-style: circle !important;\">" . $instance->lang('tasksactions')['abort_lastreply'] . " - " . $instance->lang('lastreply') . ": ' + data.task_abort.lastreplytime + '</li>' : '');
				paddingTasks += (data.task_abort.invoice ? '<li style=\"list-style: circle !important;\">" . $instance->lang('tasksactions')['abort_invoice'] . " - ' + data.task_abort.invoiceids + '</li>' : '');

				paddingTasks += '</ul></li>';
			}

			paddingTasks += '</ul>';
			
			paddingTasks += '</div>';
			paddingTasks += '</div>';
			paddingTasks += '</div>';
			paddingTasks += '</div>';
		});

		if(paddingTasks) paddingTasks = '<div id=\"ticketreplies\" class=\"ticketstasks-box\">' + paddingTasks + '</div>';

		html += paddingTasks;	
	}

	html += '</div></div></div>';

	boxContent.append(html);

	$('body').append(shadow).append(box.append(boxContent));

	var totalPredefined = 0;
		
	$.each(predefined, function(predefined_id, predefined_details) { totalPredefined++; });
		
	if(totalPredefined)
	{
		var ul = $('<ul />').css({ margin: 0 });
		
		$.each(predefined, function(predefined_id, predefined_details) {

			var li = $('<li />');
			
			$('<a />').attr({ href: '#' }).text(predefined_details.title).click(function() {

				$('form#addtaskform')[0].reset();
				$('select[name=task\\\[interval_type\\\]]').val(predefined_details.task_repeat ? 'repeated' : 'onetime').change();
				$('select[name=task\\\[interval\\\]]').val(predefined_details.task_repeat);
				$('select[name=task\\\[hours\\\]]').val(parseInt(predefined_details.task_hours));
				$('select[name=task\\\[minutes\\\]]').val(predefined_details.task_minutes);
				$('select[name=task\\\[status\\\]]').val(predefined_details.ticket_status_id);
				$('select[name=task\\\[flag\\\]]').val(predefined_details.ticket_admin_id);
				$('input[name=task\\\[flagemail\\\]]').prop('checked', predefined_details.ticket_admin_email == '1');
				$('select[name=task\\\[dept\\\]]').val(predefined_details.ticket_dept_id);
				$('select[name=task\\\[urgency\\\]]').val(predefined_details.ticket_urgency);
				$('input[name=task\\\[bump\\\]][value=' + predefined_details.ticket_bump + ']').attr('checked', 'checked');
				$('textarea[name=task\\\[reply\\\]]').val(predefined_details.ticket_reply);
				$('textarea[name=task\\\[note\\\]]').val(predefined_details.ticket_note);
				$('input[name=task\\\[abort\\\]\\\[lastreply\\\]]').prop('checked', (predefined_details.task_abort.lastreply == '1'));
				$('input[name=task\\\[abort\\\]\\\[invoice\\\]]').prop('checked', false);
				$('input[name=task\\\[abort\\\]\\\[invoiceids\\\]]').val('');
			
				return false;
			}).appendTo(li);

			ul.append(li);		
		});
			
		$('.predefined-box' + ticket_id).append(ul);
	}
	else
	{
		$('.predefined-box' + ticket_id).append('No Pre-Defined tasks was found - <a href=\"addonmodules.php?module=ticketstasks&pagename=predefined&view=manage\" target=\"_blank\">Click here to add new Pre-Defined task</a>');
	}
					
	" . (file_exists(WHMCS_ROOT_PATH . '/assets/js/markdown.min.js') ? '$("#taskreply" + ticket_id + ", #tasknote" + ticket_id).TicketMDE({locale: \'' . $_ADMINLANG['locale'] . '\',token: $(\'input[name=token]\').val(),});' : '') . "
					
	$('.addtaskbtn').click(function() {

		$('.taskmsgbox.successbox, .taskmsgbox.errorbox').css('display', 'none');
		$('.taskmsgbox.successbox .taskmsgcontent, .taskmsgbox.errorbox .taskmsgcontent').html('');

		var btn = $(this);

		$('#addtaskbtncontainer').css({ display: 'none'});
		$('#edittaskbtncontainer').css({ display: 'none'});
		//btn.css({ display: 'none'});
		$('.addtaskloader').show();

		var fields = $('#addtaskform').serializeArray();
		var task_id = $('input[name=task_id]').val();

		$.ajax({
			url: request_uri,
			data: {
				token: $('input[name=token]').val(),
				oparation: 'addtask',
				ticket_id: ticket_id,
				task_id: task_id,
				fields: fields,
				ajax: 1
			},
			dataType: 'json',
			success: function(data) {

				if(data.success)
				{
					$('.taskmsgbox.successbox').css('display', '');
					$('.taskmsgbox.successbox .taskmsgcontent').html(task_id ? '" . $instance->lang('taskupdated') . "' : '" . $instance->lang('taskadded') . "');

					$('#taskstab0').click();

					// add the new task to the tasks tab
					$('#notasks').fadeOut('slow');

					// remove the old task box
					if(task_id) $('#task' + task_id).remove();

					data = data.task;
					
					var html = '';

					html += '<div id=\"task' + data.task_id + '\" class=\"reply note tickettasks\" style=\"background: #EAF1F1; border: 1px dashed #b4d0da;\">';
			
					html += '<div class=\"leftcol\">';
					html += '<div class=\"submitter\">';
					html += '<div class=\"name\">' + ticket_admins[data.admin_id].firstname + ' ' + ticket_admins[data.admin_id].lastname + '</div>';
					html += '<div class=\"title\">' + (data.task_status == 'Completed' ? '<strong style=\"color: #1cb536;\">" . $instance->lang('completed') . "' : (data.task_status == 'Pending' ? '<strong style=\"color: #ff6600;\">" . $instance->lang('pending') . "' : '<strong style=\"color: #CC0000;\">" . $instance->lang('aborted') . "')) + '</strong></div>';
					html += '</div>';
			
					html += '<div class=\"tools\">';
					html += '<div class=\"editbtnsr8\">';

					if(data.task_status == 'Pending') html += '<input type=\"button\" class=\"btn btn-xs btn-small btn-default\" onclick=\"return doEditTask(\'' + ticket_id + '\', \'' + data.task_id + '\');\" value=\"" . $instance->lang('edit') . "\"> ';
					html += '<input type=\"button\" class=\"btn btn-xs btn-small btn-danger\" onclick=\"return doDeleteTask(\'' + ticket_id + '\', \'' + data.task_id + '\');\" value=\"" . $instance->lang('delete') . "\">';
			
			
					html += '</div>';
					html += '</div>';

					html += '</div>';
					html += '<div class=\"rightcol\">';
					html += '<div class=\"postedon\">' + sprintf('" . $instance->lang('taskaddedby') . "', ticket_admins[data.admin_id].firstname + ' ' + ticket_admins[data.admin_id].lastname, (data.task_status == 'Pending' ? '" . $instance->lang('taskadded_pending') . "' : (data.task_status == 'Aborted' ? '" . $instance->lang('taskadded_aborted') . "' : '" . $instance->lang('taskadded_completed') . "')), data.task_time) + (data.task_repeat ? ' (' + sprintf('" . $instance->lang('taskrepeatevery') . "', data.task_repeat.charAt(0).toUpperCase() + data.task_repeat.slice(1)) + ')' : '') + '</div>';
					html += '<div class=\"msgwrap\">';
					html += '<div class=\"message markdown-content\">';

					html += '<ul style=\"list-style: disc !important;\">';
					html += (parseInt(data.ticket_status_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['status'] . " <strong style=\"color: ' + ticket_statuses[data.ticket_status_id].color + '\">' + ticket_statuses[data.ticket_status_id].title + '</strong></li>' : '');
					html += (parseInt(data.ticket_admin_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['flag'] . " <strong>' + ticket_admins[data.ticket_admin_id].firstname + ' ' + ticket_admins[data.ticket_admin_id].lastname + '</strong>' + (parseInt(data.ticket_admin_email) ? ' " . $instance->lang('andsendnotify') . "' : '') + '</li>' : '');
					html += (parseInt(data.ticket_dept_id) ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['dept'] . " <strong>' + ticket_departments[data.ticket_dept_id].name + '</strong></li>' : '');
					html += (data.ticket_urgency ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['priority'] . " <strong>' + data.ticket_urgency + '</strong></li>' : '');
					html += (data.ticket_bump == '1' ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['bump'] . "</li>' : '');
					html += (data.ticket_reply ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['reply'] . "</li>' : '');
					html += (data.ticket_note ? '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['note'] . "</li>' : '');

					if($(data.task_abort).length)
					{
						html += '<li style=\"list-style: disc !important;\">" . $instance->lang('tasksactions')['abort'] . "<ul>';

						html += (data.task_abort.lastreply ? '<li style=\"list-style: circle !important;\">" . $instance->lang('tasksactions')['abort_lastreply'] . " - " . $instance->lang('lastreply') . ": ' + data.task_abort.lastreplytime + '</li>' : '');
						html += (data.task_abort.invoice ? '<li style=\"list-style: circle !important;\">" . $instance->lang('tasksactions')['abort_invoice'] . " - ' + data.task_abort.invoiceids + '</li>' : '');

						html += '</ul></li>';
					}

					html += '</ul>';
			
					html += '</div>';
					html += '</div>';
					html += '</div>';
					html += '</div>';
					
					$('.ticketstasks-box').append(html);

					var totalTasks = parseInt($('#taskscount' + ticket_id + ' span').text());

					$('#taskscount' + ticket_id + ' span').text(totalTasks+1);
					$('#taskscount' + ticket_id).css('display', 'inline-block');

					if(tasks[ticket_id] === undefined) tasks[ticket_id] = {};

					tasks[ticket_id][data.task_id] = data;

					cancelEditTask();
				}
				else
				{
					$('.taskmsgbox.errorbox').css('display', '');
					$('.taskmsgbox.errorbox .taskmsgcontent').html(data.message);
					$('#addtaskbtncontainer').css({ display: (task_id ? 'none' : '') });
					$('#edittaskbtncontainer').css({ display: (task_id ? '' : 'none') });
				}

				$('.addtaskloader').hide();
			}
		});
	});

	var selectedTab;

	$('.tab').click(function(){
		var self = $(this);
		var elid = self.attr('id');
		$('.tab').removeClass('active');
		self.addClass('active');

		if (elid != selectedTab) 
		{
			$('.tab-pane').removeClass('active');
			$('#'+elid+'box').addClass('active');
			selectedTab = elid;
		}

		$('#taskstab').val(elid.substr(3));
	});

	selectedTab = 'taskstab0';
	$('#taskstab0').addClass('active');
	$('#taskstab0box').addClass('active');

	$('.datepick').datepicker({
		dateFormat: 'dd/mm/yy',
		showOn: 'button',
		buttonImage: 'images/showcalendar.gif',
		buttonImageOnly: true,
		showButtonPanel: true
	});

	$('select[name=task\\\[interval_type\\\]]').change(function() {

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
	
	var boxTop = ((winHeight / 2 - box.outerHeight() / 2) + winTop);
	boxTop = boxTop < 10 ? 10 : boxTop;

	box.css({ top: boxTop }).fadeIn('slow');
	shadow.fadeIn('slow');

	shadow.click(function() { 
		shadow.fadeOut('slow', function() { $(this).remove(); });
		box.fadeOut('slow', function() { $(this).remove(); });
		$('.taskmsgbox.successbox, .taskmsgbox.errorbox').css('display', 'none');
		cancelEditTask();
	});

	boxCloseBtn.click(function() {
		shadow.fadeOut('slow', function() { $(this).remove(); });
		box.fadeOut('slow', function() { $(this).remove(); });
		$('.taskmsgbox.successbox, .taskmsgbox.errorbox').css('display', 'none');
		cancelEditTask();
	});
}

</script>";
		}

	}
}

function ticketstasks_widget($vars)
{
	global $vars;

	$instance = ticketstasks::getInstance();

	$tasks = array();

	$task_rows = Capsule::table('mod_ticketstasks_tasks as s')
		->select('s.*', 's.id as task_id', 't.*', 'c.firstname', 'c.lastname')
		->join('tbltickets as t', 's.ticket_id', '=', 't.id')
		->leftJoin('tblclients as c', 't.userid', '=', 'c.id')
		->where('s.task_status', '=', 'Pending')
		->orderBy('s.task_time', 'desc')
		->get();

	foreach($task_rows as $task_details)
	{
		$task_details = (array) $task_details;
		$task_details['task_abort'] = $task_details['task_abort'] ? unserialize($task_details['task_abort']) : array();
		$tasks[$task_details['task_id']] = $task_details;
	}

	$content = '<div style="overflow-y: scroll; max-height: 200px;">';
	$content .= '<table width="100%" bgcolor="#cccccc" cellspacing="1"><tbody>';
	$content .= '<tr bgcolor="#efefef" style="text-align:center;font-weight:bold;">';
	$content .= '<td>' . $instance->lang('ticket') . '</td>';
	$content .= '<td>' . $instance->lang('admin') . '</td>';
	$content .= '<td>' . $instance->lang('execution_date') . '</td>';
	$content .= '<td>' . $instance->lang('taskstoperform') . '</td>';
	$content .= '</tr>';

	if(sizeof($tasks))
	{
		foreach($tasks as $task_id => $task_details)
		{
			$content .= '<tr bgcolor="#ffffff">';

			$admin_details = $instance->getAdmin($task_details['admin_id']);
			$ticket_admin_details = $instance->getAdmin($task_details['ticket_admin_id']);
			$status_details = $instance->getTicketStatus($task_details['ticket_status_id']);
			$department_details = $instance->getTicketDepartment($task_details['ticket_dept_id']);

			$content .= '<td style="text-align:center;"><a href="supporttickets.php?action=view&id=' . $task_details['ticket_id'] . '" target="_blank">#' . $task_details['ticket_id'] . '</a></td>';
			$content .= '<td style="text-align:center;">' . $admin_details['firstname']  . ' ' . $admin_details['lastname'] . '</td>';
			$content .= '<td style="text-align:center;">' . date("d/m/Y H:i", $task_details['task_time']) . '</td>';
			$content .= '<td><ul>';

			$content .= ($task_details['ticket_status_id'] ? '<li style="list-style: circle !important;">' . $instance->lang('tasksactions')['status'] . ' <strong style="color: ' . $status_details['color'] . '">' . $status_details['title'] . '</strong></li>' : '');
			$content .= ($task_details['ticket_admin_id'] ? '<li style="list-style: circle !important;">' . $instance->lang('tasksactions')['flag'] . ' <strong>' . $ticket_admin_details['firstname'] . ' ' . $ticket_admin_details['lastname'] . '</strong>' . ($task_details['ticket_admin_email'] ? ' ' . $instance->lang('andsendnotify') : '') . '</li>' : '');
			$content .= ($task_details['ticket_dept_id'] ? '<li style="list-style: circle !important;">' . $instance->lang('tasksactions')['dept'] . ' <strong>' . $department_details['name'] . '</strong></li>' : '');
			$content .= ($task_details['ticket_urgency'] ? '<li style="list-style: circle !important;">' . $instance->lang('tasksactions')['priority'] . ' <strong>' . $task_details['ticket_urgency'] . '</strong></li>' : '');
			$content .= ($task_details['ticket_bump'] == '1' ? '<li style="list-style: circle !important;">' . $instance->lang('tasksactions')['bump'] . '</li>' : '');
			$content .= ($task_details['ticket_reply'] ? '<li style="list-style: circle !important;"><strong>' . $instance->lang('tasksactions')['reply'] . '</strong><br />' . $task_details['ticket_reply'] . '</li>' : '');
			$content .= ($task_details['ticket_note'] ? '<li style="list-style: circle !important;"><strong>' . $instance->lang('tasksactions')['note'] . '</strong><br />' . $task_details['ticket_note'] . '</li>' : '');

			if(sizeof($task_details['task_abort']))
			{
				$content .= '<li style="list-style: disc !important;">' . $instance->lang('tasksactions')['abort'];
				$content .= '<ul>';

				$content .= ($task_details['task_abort']['lastreply'] ? '<li style="list-style: circle !important;">' . $instance->lang('tasksactions')['abort_lastreply'] . ' - ' . $instance->lang('lastreply') . ': ' . $task_details['task_abort']['lastreplytime'] . '</li>' : '');
				$content .= ($task_details['task_abort']['invoice'] ? '<li style="list-style: circle !important;">' . $instance->lang('tasksactions')['abort_invoice'] . ' - ' . $task_details['task_abort']['invoiceids'] . '</li>' : '');

				$content .= '</ul>';
				$content .= '</li>';
			}

			$content .= '</ul></td>';

			$content .= '</tr>';
		}
	}
	else
	{
		$content .= '<td colspan="100">' . $instance->lang('no_records') . '</td>';
	}

	$content .= '</tr>';

	$content .= '</tbody></table>';
	$content .= '</div>';

	return array(
		'title'		=> $instance->lang('tickettasks'),
		'content'	=> $content,
	);
}

add_hook('AdminAreaHeadOutput', 0, 'ticketstasks_hook_addpopup');
add_hook('AdminHomeWidgets', 	0, 'ticketstasks_widget');

?>
