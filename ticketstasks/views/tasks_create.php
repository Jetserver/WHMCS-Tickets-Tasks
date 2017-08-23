<?php
/*
 *
 * JetBackupManager @ whmcs module package
 * Created By Idan Ben-Ezra
 *
 * Copyrights @ Jetserver Web Hosting
 * http://jetserver.net
 *
 **/

if (!defined("JETTICKETSTASKS"))
	die("This file cannot be accessed directly");

use Illuminate\Database\Capsule\Manager as Capsule;

class jtt_tasks_create extends jtt_tasks_default
{
	public function _default()
	{
		global $CONFIG;
				
		$output = array('success' => true, 'message' => '', 'data' => array());
		
		$user_id = ticketstasks::request_var('userid', 0);

		if(!$user_id)
		{
			$output['success'] = false;
			$output['message'] = "No user id was provided";
			return $output;
		}

		$client_details = Capsule::table('tblclients')
			->where('id', $user_id)
			->get();

		if(!isset($client_details[0]))
		{
			$output['success'] = false;
			$output['message'] = "The provided user id not exists";
			return $output;
		}

		$output['data']['client_details'] = (array) $client_details;

		$output['data']['client_details']['contacts'] = array();

		$contact_rows = Capsule::table('tblcontacts')
			->where('userid', $user_id)
			->get();

		foreach($contact_rows as $contact_details)
		{
			$output['data']['client_details']['contacts'][$contact_details->id] = (array) $contact_details;
		}

		$instance = ticketstasks::getInstance();
		
		$output['data']['departments'] = $instance->getTicketDepartments();
		
		$default_department = array_keys($output['data']['departments']);
		$default_department = $default_department[0];
		
		$output['data']['fields'] = array(
			'scheduledate' 		=> ticketstasks::request_var('scheduledate', date("d/m/Y")),
			'schedule_hours' 	=> ticketstasks::request_var('schedule_hours', 0, array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23)),
			'schedule_minutes' 	=> ticketstasks::request_var('schedule_minutes', '00', array('00','10','20','30','40','50')),
			'sendmail'		=> ticketstasks::request_var('sendmail', 1, array(0,1)),
			'email' 		=> ticketstasks::request_var('email', $output['data']['client_details']['email']),
			'contactid' 		=> ticketstasks::request_var('contactid', 0, array_keys($output['data']['client_details']['contacts'])),
			'ccemail' 		=> ticketstasks::request_var('ccemail', ''),
			'subject' 		=> ticketstasks::request_var('subject', ''),
			'deptid' 		=> ticketstasks::request_var('deptid', $default_department, array_keys($output['data']['departments'])),
			'priority' 		=> ticketstasks::request_var('priority', 'Medium', array('High', 'Medium', 'Low')),
			'message' 		=> ticketstasks::request_var('message', ''),
		);
		
		return $output;
	}

	public function save()
	{
		$output = $this->_default();
		if(!$output['success']) return $output;
		$output['success'] = false;

		$default_department = array_keys($output['data']['departments']);
		$default_department = $default_department[0];
		
		$output['data']['fields'] = array(
			'scheduledate' 		=> ticketstasks::request_var('scheduledate', date("d/m/Y")),
			'schedule_hours' 	=> ticketstasks::request_var('schedule_hours', 0, array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23)),
			'schedule_minutes' 	=> ticketstasks::request_var('schedule_minutes', '00', array('00','10','20','30','40','50')),
			'sendmail'		=> ticketstasks::request_var('sendmail', 1, array(0,1)),
			'email' 		=> ticketstasks::request_var('email', $output['data']['client_details']['email']),
			'contactid' 		=> ticketstasks::request_var('contactid', 0, array_keys($output['data']['client_details']['contacts'])),
			'ccemail' 		=> ticketstasks::request_var('ccemail', ''),
			'subject' 		=> ticketstasks::request_var('subject', ''),
			'deptid' 		=> ticketstasks::request_var('deptid', $default_department, array_keys($output['data']['departments'])),
			'priority' 		=> ticketstasks::request_var('priority', 'Medium', array('High', 'Medium', 'Low')),
			'message' 		=> ticketstasks::request_var('message', ''),
		);

		$schedule_time = 0;
		
		if($output['data']['fields']['scheduledate'] && isset($output['data']['fields']['schedule_hours']) && isset($output['data']['fields']['schedule_minutes']))
		{
			list($day, $month, $year) = explode('/', $output['data']['fields']['scheduledate']);
			$schedule_time = mktime($output['data']['fields']['schedule_hours'], $output['data']['fields']['schedule_minutes'], 0, $month, $day, $year);
		}
		
		$ccemail_validate = true;
		
		if($output['data']['fields']['ccemail'])
		{
			$ccemails = explode(",", $output['data']['fields']['ccemail']);
			
			foreach($ccemails as $ccemail)
			{
				if(!filter_var($ccemail, FILTER_VALIDATE_EMAIL))
				{
					$ccemail_validate = false;
					break;
				}
			}
		}
		
		if($schedule_time && $schedule_time > time() && $output['data']['fields']['email'] && filter_var($output['data']['fields']['email'], FILTER_VALIDATE_EMAIL) && $ccemail_validate && $output['data']['fields']['subject'] && $output['data']['fields']['message'])
		{
			Capsule::table('mod_ticketstasks_tickets')->insert([
				'userid'		=> $output['data']['client_details']['id'],
				'adminid'		=> intval($_SESSION['adminid']),
				'time'			=> time(),
				'schedule'		=> $schedule_time,
				'sendmail'		=> $output['data']['fields']['sendmail'],
				'email'			=> $output['data']['fields']['email'],
				'contactid'		=> $output['data']['fields']['contactid'],
				'ccemail'		=> $output['data']['fields']['ccemail'],
				'subject'		=> $output['data']['fields']['subject'],
				'deptid'		=> $output['data']['fields']['deptid'],
				'priority'		=> $output['data']['fields']['priority'],
				'message'		=> $output['data']['fields']['message'],
				'status'		=> 'Pending',
			]);

			$output['message'] = "Task created successfully";
			$output['success'] = true;
		}
		else 
		{
			if(!$schedule_time) $output['errormessages'][] = "No valid schedule time was provided";
			if($schedule_time && $schedule_time <= time()) $output['errormessages'][] = "The task schedule time must be higher then the current time";
			if(!$output['data']['fields']['email']) $output['errormessages'][] = "No Email was provided";
			if($output['data']['fields']['email'] && !filter_var($output['data']['fields']['email'], FILTER_VALIDATE_EMAIL)) $output['errormessages'][] = "The provided Email is invalid";
			if(!$ccemail_validate) $output['errormessages'][] = "The provided CC Recipients is invalid";
			if(!$output['data']['fields']['subject']) $output['errormessages'][] = "No Subject was provided";
			if(!$output['data']['fields']['message']) $output['errormessages'][] = "No Message was provided";
		}
		
		return $output;
	}
}

?>