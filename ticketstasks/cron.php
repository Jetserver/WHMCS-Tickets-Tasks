<?php

define('JTT_ROOT_PATH', dirname(__FILE__));
define('WHMCS_ROOT_PATH', JTT_ROOT_PATH . '/../../../');
define('PROXY_FILE', true);

require_once(WHMCS_ROOT_PATH . "init.php");
require_once(WHMCS_ROOT_PATH . "includes/functions.php");
require_once(WHMCS_ROOT_PATH . "includes/ticketfunctions.php");
require_once(JTT_ROOT_PATH . '/includes/functions.php');

use Illuminate\Database\Capsule\Manager as Capsule;

$instance = ticketstasks::getInstance();

if(!$instance->getConfig('system_enabled'))
{
	die('System is disabled. you can turn it on from the WHMCS Tickets Tasks module GUI');
}

$get_keys = isset($_GET) ? array_keys($_GET) : array('');
$token = isset($argv[1]) ? $argv[1] : ($_GET['token'] ? $_GET['token'] : $get_keys[0]);

if($instance->getConfig('token') != $token)
{
	die('Invalid Token');
}

$statuses = $instance->getTicketStatuses(); 
$departments = $instance->getTicketDepartments();
$admins = $instance->getAdmins();

$task_rows = Capsule::table('mod_ticketstasks_tickets')
	->where('status', 'Pending')
	->where('schedule', '<=', (time()+30))
	->get();

foreach($task_rows as $task_details)
{
	$task_details = (array) $task_details;

	localAPI('openticket', array(
		'clientid' 		=> $task_details['userid'],
		'email'			=> $task_details['email'],
		'deptid'		=> $task_details['deptid'],
		'subject'		=> $task_details['subject'],
		'message'		=> $task_details['message'],
		'priority'		=> $task_details['priority'],
		'contactid'		=> $task_details['contactid'],
		'noemail'		=> $task_details['sendmail'] ? true : false,
	), $admins[$task_details['adminid']]['username']);

	// set task as triggered
	Capsule::table('mod_ticketstasks_tickets')
		->where('id', '=', $task_details['id'])
		->update(array(
			'status' 	=> 'Completed',
		));
}

$task_rows = Capsule::table('mod_ticketstasks_tasks')
	->where('task_status', 'Pending')
	->where('task_time', '<=', (time()+30))
	->get();

foreach($task_rows as $task_details)
{
	$task_details = (array) $task_details;

	$set_session = false;

	if(!isset($_SESSION["adminid"]) || $_SESSION["adminid"] <= 0)
	{
		$set_session = true;
		$_SESSION["adminid"] = $task_details['admin_id'];
	}

	$ticket_details = Capsule::table('tbltickets')
		->where('id', $task_details['ticket_id'])
		->get();

	if(!isset($ticket_details[0])) continue;

	$ticket_details = (array) $ticket_details[0];

	$task_details['task_abort'] = $task_details['task_abort'] ? unserialize($task_details['task_abort']) : array();

	// abort task
	if(sizeof($task_details['task_abort']))
	{
		if($task_details['task_abort']['lastreply'])
		{
			if($task_details['task_abort']['lastreplytime'] != $ticket_details['lastreply'])
			{
				// abort this task
				Capsule::table('mod_ticketstasks_tasks')
					->where('id', '=', $task_details['id'])
					->update(array(
						'task_status' 	=> 'Aborted',
					));

				continue;
			}
		}

		if($task_details['task_abort']['invoice'])
		{
			$invoice_details = Capsule::table('tblinvoices')
				->where('status', 'Unpaid')
				->whereIn('id', explode(',', $task_details['task_abort']['invoiceids']))
				->get();

			if(!isset($invoice_details[0]))
			{
				$invoice_details = (array) $invoice_details[0];

				// abort this task
				Capsule::table('mod_ticketstasks_tasks')
					->where('id', '=', $task_details['id'])
					->update(array(
						'task_status' 	=> 'Aborted',
					));

				continue;
			}
		}
	}

	// add new ticket reply
	if(trim($task_details['ticket_reply']))
	{
		localAPI('addticketreply', array(
			'ticketid' 		=> $task_details['ticket_id'],
			'clientid'		=> $ticket_details['userid'],
			'contactid'		=> $ticket_details['contactid'],
			'adminusername'		=> $admins[$task_details['admin_id']]['username'],
			'status'		=> (intval($task_details['ticket_status_id']) ? $statuses[$task_details['ticket_status_id']]['title'] : null),
			'name' 			=> $ticket_details['userid'] ? null : $ticket_details['name'], 
			'email' 		=> $ticket_details['userid'] ? null : $ticket_details['email'],
			'message'		=> $task_details['ticket_reply'],
		), $admins[$task_details['admin_id']]['username']);

		// change ticket last reply
		if(intval($task_details['ticket_bump'])) addTicketLog($task_details['ticket_id'], 'Ticket Bump');

		// change ticket status
		if(intval($task_details['ticket_status_id'])) addTicketLog($task_details['ticket_id'], sprintf('Status changed to %1$s', $statuses[$task_details['ticket_status_id']]['title']));
	}
	else
	{
		// change ticket last reply
		if(intval($task_details['ticket_bump']))
		{
			Capsule::table('tbltickets')
				->where('id', '=', $task_details['ticket_id'])
				->update(array(
					'lastreply' 	=> date("Y-m-d H:i:s"),
				));

			addTicketLog($task_details['ticket_id'], 'Ticket Bump');
		}

		// change ticket status
		if(intval($task_details['ticket_status_id']))
		{
			localAPI('updateticket', array(
				'ticketid' 		=> $task_details['ticket_id'],
				'status'		=> $statuses[$task_details['ticket_status_id']]['title'],
			), $admins[$task_details['admin_id']]['username']);

			addTicketLog($task_details['ticket_id'], sprintf('Status changed to %1$s', $statuses[$task_details['ticket_status_id']]['title']));
		}
	}

	// flag ticket to seleted admin
	if(intval($task_details['ticket_admin_id']))
	{
		localAPI('updateticket', array(
			'ticketid' 	=> $task_details['ticket_id'],
			'flag'		=> $task_details['ticket_admin_id'],
		), $admins[$task_details['admin_id']]['username']);

		addTicketLog($task_details['ticket_id'], sprintf('Ticket Flagged to %1$s %2$s', $admins[$task_details['ticket_admin_id']]['firstname'], $admins[$task_details['ticket_admin_id']]['lastname']));

		if($task_details['ticket_admin_email'])
		{
			$name = $ticket_details['name'];

			if($ticket_details['userid'])
			{
				$client_details = Capsule::table('tblclients')
					->where('id', $ticket_details['userid'])
					->get();

				$client_details = isset($client_details[0]) ? (array) $client_details[0] : null;

				$name = $client_details['firstname'] . ' ' . $client_details['lastname'];
			}

			$message = strip_tags($ticket_details['message']);
			$message = preg_replace( "/\[div=\"(.*?)\"\]/", "<div class=\"\">", $message );
			$replacetags = array( "b" => "strong", "i" => "em", "u" => "ul", "div" => "div" );

			foreach ($replacetags as $k => $v) 
			{
				$message = str_replace( "[" . $k . "]", "<" . $k . ">", $message );
				$message = str_replace( "[/" . $k . "]", "</" . $k . ">", $message );
			}

			$message = nl2br($message);
			$message = autoHyperLink($message);

			// send email
			$tplvars = array(
				'ticket_id' 		=> $ticket_details['id'], 
				'ticket_tid' 		=> $ticket_details['tid'], 
				'client_id' 		=> $ticket_details['userid'], 
				'client_name' 		=> $name, 
				'ticket_department' 	=> $departments[$ticket_details['did']], 
				'ticket_subject' 	=> $ticket_details['title'], 
				'ticket_priority' 	=> $ticket_details['urgency'], 
				'ticket_message' 	=> $message,
			);

			sendAdminMessage('Support Ticket Flagged', $tplvars, 'support', $ticket_details['did'], $task_details['ticket_admin_id'], false);
		}
	}

	// change ticket department
	if(intval($task_details['ticket_dept_id']))
	{
		localAPI('updateticket', array(
			'ticketid' 	=> $task_details['ticket_id'],
			'deptid'	=> $task_details['ticket_dept_id'],
		), $admins[$task_details['admin_id']]['username']);

		addTicketLog($task_details['ticket_id'], sprintf('Department changed to %1$s', $departments[$task_details['ticket_dept_id']]['name']));
	}

	// change ticket priority
	if(trim($task_details['ticket_urgency']))
	{
		localAPI('updateticket', array(
			'ticketid' 	=> $task_details['ticket_id'],
			'priority'	=> $task_details['ticket_urgency'],
		), $admins[$task_details['admin_id']]['username']);

		addTicketLog($task_details['ticket_id'], sprintf('Priority Changed to %1$s', trim($task_details['ticket_urgency'])));
	}

	// add new ticket note
	if(trim($task_details['ticket_note']))
	{
		localAPI('addticketnote', array(
			'ticketid' 	=> $task_details['ticket_id'],
			'message'	=> $task_details['ticket_note'],
		), $admins[$task_details['admin_id']]['username']);
	}

	if($task_details['task_repeat'])
	{
		Capsule::table('mod_ticketstasks_tasks')
			->where('id', '=', $task_details['id'])
			->update(array(
				'task_time' 	=> strtotime("+1 {$task_details['task_repeat']}", $task_details['task_time']),
			));
	}
	else
	{
		// set task as triggered
		Capsule::table('mod_ticketstasks_tasks')
			->where('id', '=', $task_details['id'])
			->update(array(
				'task_status' 	=> 'Completed',
			));
	}
	
	// if we forced the admin id inside the session, unset the session
	if($set_session) unset($_SESSION["adminid"]);
}

?>