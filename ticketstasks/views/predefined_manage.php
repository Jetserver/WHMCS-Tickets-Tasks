<?php
/*
 *
 * JetTicketsTasks @ whmcs module package
 * Created By Idan Ben-Ezra
 *
 * Copyrights @ Jetserver Web Hosting
 * http://jetserver.net
 *
 **/

if (!defined("JETTICKETSTASKS"))
	die("This file cannot be accessed directly");

use Illuminate\Database\Capsule\Manager as Capsule;

class jtt_predefined_manage extends jtt_predefined_default
{
	public function _default()
	{	
		$output = array('success' => true, 'message' => '', 'data' => array());

		$instance = ticketstasks::getInstance();
		
		$output['data']['statuses'] = $instance->getTicketStatuses();		
		$output['data']['departments'] = $instance->getTicketDepartments();
		$output['data']['admins'] = $instance->getAdmins();

		$predefined_id = ticketstasks::request_var('id', 0);
		
		if($predefined_id)
		{
			$predefined_details = Capsule::table('mod_ticketstasks_predefined')
				->where('id', $predefined_id)
				->get();

			if(!isset($predefined_details[0]))
			{
				$output['message'] = "The provided predefined task id not exists";
				$output['success'] = false;
				return $output;
			}

			$predefined_details = (array) $predefined_details[0];

			$output['data']['fields'] = array(
				'title'		=> $predefined_details['title'],
				'task'		=> array(
					'interval_type'		=> $predefined_details['task_repeat'] ? 'repeated' : 'onetime',
					'hours'			=> $predefined_details['task_hours'],
					'minutes'		=> $predefined_details['task_minutes'],
					'interval'		=> $predefined_details['task_repeat'],
					'status'		=> $predefined_details['ticket_status_id'],
					'dept'			=> $predefined_details['ticket_dept_id'],
					'urgency'		=> $predefined_details['ticket_urgency'],
					'flag'			=> $predefined_details['ticket_admin_id'],
					'flagemail'		=> $predefined_details['ticket_admin_email'],
					'bump'			=> $predefined_details['ticket_bump'],
					'abort'			=> unserialize($predefined_details['task_abort']),
					'reply'			=> $predefined_details['ticket_reply'],
					'note'			=> $predefined_details['ticket_note'],
				),
			);
		}
		else
		{
			$output['data']['fields'] = array(
				'title'		=> ticketstasks::request_var('title', ''),
				'task'		=> array(
					'interval_type'		=> ticketstasks::request_var(array('task', 'interval_type'), 'onetime', array('onetime','repeated')),
					'hours'			=> ticketstasks::request_var(array('task', 'hours'), 0, array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23)),
					'minutes'		=> ticketstasks::request_var(array('task', 'minutes'), '00', array('00','10','20','30','40','50')),
					'interval'		=> ticketstasks::request_var(array('task', 'interval'), 'day', array('day','week','month','year')),
					'status'		=> ticketstasks::request_var(array('task', 'status'), 0, array_keys($output['data']['statuses'])),
					'dept'			=> ticketstasks::request_var(array('task', 'dept'), 0, array_keys($output['data']['departments'])),
					'urgency'		=> ticketstasks::request_var(array('task', 'urgency'), '', array('Low','Medium','High')),
					'flag'			=> ticketstasks::request_var(array('task', 'flag'), 0, array_keys($output['data']['admins'])),
					'flagemail'		=> ticketstasks::request_var(array('task', 'flagemail'), 0, array(0,1)),
					'bump'			=> ticketstasks::request_var(array('task', 'bump'), 0, array(0,1)),
					'abort'			=> array(
						'lastreply'		=> ticketstasks::request_var(array('task', 'abort', 'lastreply'), 0, array(0,1)),
					),
					'reply'			=> ticketstasks::request_var(array('task', 'reply'), ''),
					'note'			=> ticketstasks::request_var(array('task', 'note'), ''),
				),
			);
		}
		
		return $output;
	}

	public function save()
	{	
		$output = $this->_default();		
		if(!$output['success']) return $output;
		$output['success'] = false;

		$predefined_id = ticketstasks::request_var('id', 0);
		
		$output['data']['fields'] = array(
			'title'		=> ticketstasks::request_var('title', $output['data']['fields']['title']),
			'task'		=> array(
				'interval_type'		=> ticketstasks::request_var(array('task', 'interval_type'), $output['data']['fields']['task']['interval_type'], array('onetime','repeated')),
				'hours'			=> ticketstasks::request_var(array('task', 'hours'), $output['data']['fields']['task']['hours'], array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23)),
				'minutes'		=> ticketstasks::request_var(array('task', 'minutes'), $output['data']['fields']['task']['minutes'], array('00','10','20','30','40','50')),
				'interval'		=> ticketstasks::request_var(array('task', 'interval'), $output['data']['fields']['task']['interval'], array('day','week','month','year')),
				'status'		=> ticketstasks::request_var(array('task', 'status'), $output['data']['fields']['task']['status'], array_keys($output['data']['statuses'])),
				'dept'			=> ticketstasks::request_var(array('task', 'dept'), $output['data']['fields']['task']['dept'], array_keys($output['data']['departments'])),
				'urgency'		=> ticketstasks::request_var(array('task', 'urgency'), $output['data']['fields']['task']['urgency'], array('Low','Medium','High')),
				'flag'			=> ticketstasks::request_var(array('task', 'flag'), $output['data']['fields']['task']['flag'], array_keys($output['data']['admins'])),
				'flagemail'		=> isset($_REQUEST['task']['flagemail']) ? ticketstasks::request_var(array('task', 'flagemail'), $output['data']['fields']['task']['flagemail'], array(0,1)) : 0,
				'bump'			=> ticketstasks::request_var(array('task', 'bump'), $output['data']['fields']['task']['bump'], array(0,1)),
				'abort'			=> array(
					'lastreply'		=> isset($_REQUEST['task']['abort']['lastreply']) ? ticketstasks::request_var(array('task', 'abort', 'lastreply'), $output['data']['fields']['task']['abort']['lastreply'], array(0,1)) : 0,
				),
				'reply'			=> ticketstasks::request_var(array('task', 'reply'), $output['data']['fields']['task']['reply']),
				'note'			=> ticketstasks::request_var(array('task', 'note'), $output['data']['fields']['task']['note']),
			),
		);
				
		if($output['data']['fields']['title'])
		{
			$task = array(
				'title'			=> $output['data']['fields']['title'],
				'task_hours'		=> $output['data']['fields']['task']['hours'],
				'task_minutes'		=> $output['data']['fields']['task']['minutes'],
				'task_repeat'		=> $output['data']['fields']['task']['interval_type'] == 'repeated' ? $output['data']['fields']['task']['interval'] : '',
				'ticket_status_id'	=> intval($output['data']['fields']['task']['status']),
				'ticket_admin_id'	=> intval($output['data']['fields']['task']['flag']),
				'ticket_admin_email'	=> $output['data']['fields']['task']['flagemail'] ? 1 : 0,
				'ticket_dept_id'	=> intval($output['data']['fields']['task']['dept']),
				'ticket_urgency'	=> $output['data']['fields']['task']['urgency'],
				'ticket_bump'		=> $output['data']['fields']['task']['bump'] ? 1 : 0,
				'ticket_reply'		=> $output['data']['fields']['task']['reply'],
				'ticket_note'		=> $output['data']['fields']['task']['note'],
				'task_abort'		=> serialize($output['data']['fields']['task']['abort']),
			);
						
			if($predefined_id)
			{
				Capsule::table('mod_ticketstasks_predefined')
					->where('id', '=', $predefined_id)
					->update($task);

				$output['message'] = "Pre-Defined Task updated successfully";
			}
			else
			{
				Capsule::table('mod_ticketstasks_predefined')
					->insert($task);

				$output['message'] = "Pre-Defined Task create successfully";
			}
			
			$output['success'] = true;
		}
		else
		{
			$output['errormessages'][] = "No Pre-Defined task title was provided";
		}
		
		return $output;
	}	
}

?>