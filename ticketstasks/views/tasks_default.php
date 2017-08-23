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

class jtt_tasks_default
{
	public function _default()
	{	
		$output = array('success' => true, 'message' => '', 'data' => array());

		$pagenum = ticketstasks::request_var('pagenum', 1);
		$limit = 25;

		$output['data']['tasks'] = $output['data']['tickets'] = array();

		$tickets = Capsule::table('mod_ticketstasks_tickets as t')
			->select('t.*', 'c.firstname', 'c.lastname')
			->join('tblclients as c', 't.userid', '=', 'c.id')
			->orderBy('t.schedule', 'desc');

		$total_tasks = $tickets->count();

		$ticket_rows = $tickets->skip((($pagenum * $limit) - $limit))->take($limit)->get();

		foreach($ticket_rows as $ticket_details)
		{
			$ticket_details = (array) $ticket_details;
			$ticket_details['admin_details'] = ticketstasks::getInstance()->getAdmin($ticket_details['adminid']);
			$ticket_details['department_details'] = ticketstasks::getInstance()->getTicketDepartment($ticket_details['deptid']);
				
			$output['data']['tickets'][$ticket_details['id']] = $ticket_details;
		}

		$tasks = Capsule::table('mod_ticketstasks_tasks as s')
			->select('s.*', 's.id as task_id', 't.*', 'c.firstname', 'c.lastname')
			->join('tbltickets as t', 's.ticket_id', '=', 't.id')
			->leftJoin('tblclients as c', 't.userid', '=', 'c.id')
			->orderBy('s.task_time', 'desc');

		$total_tasks = $tasks->count();

		$task_rows = $tasks->skip((($pagenum * $limit) - $limit))->take($limit)->get();

		foreach($task_rows as $task_details)
		{
			$task_details = (array) $task_details;
			$task_details['task_abort'] = $task_details['task_abort'] ? unserialize($task_details['task_abort']) : array();
			$task_details['admin_details'] = ticketstasks::getInstance()->getAdmin($task_details['admin_id']);
			$task_details['status_details'] = ticketstasks::getInstance()->getTicketStatus($task_details['ticket_status_id']);
			$task_details['ticket_admin_details'] = ticketstasks::getInstance()->getAdmin($task_details['ticket_admin_id']);
			$task_details['department_details'] = ticketstasks::getInstance()->getTicketDepartment($task_details['ticket_dept_id']);
			
			$output['data']['tasks'][$task_details['task_id']] = $task_details;
		}

		$total_pages = ceil($total_tasks / $limit);
			
		return $output;
	}

	public function cancel()
	{	
		$output = array('success' => false, 'message' => '', 'data' => array());

		$type = ticketstasks::request_var('type', 'tickets', array('tickets','tasks'));

		$selected = array(
			'tickets' 	=> ticketstasks::request_var('selectedtickets', array()),
			'tasks' 	=> ticketstasks::request_var('selectedtasks', array()),
		);
		
		if(sizeof($selected[$type]))
		{
			Capsule::table('mod_ticketstasks_' . $type)
				->whereIn('id', $selected[$type])
				->delete();

			$output['success'] = true;
			$output['message'] = ticketstasks::getInstance()->lang('canceled');
		}
		
		return $output;
	}
}

?>