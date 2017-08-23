<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

use Illuminate\Database\Capsule\Manager as Capsule;

class ticketstasks
{
	var $ticketStatuses;
	var $ticketDepartments;
	var $admins;
	var $lang;
	private $config = array();
	
	function __construct()
	{
		$config_rows = Capsule::table('mod_ticketstasks_config')->get();

		foreach($config_rows as $config_details)
		{
			if(preg_match("/^a:\d+:{.*?}$/", $config_details->value))
			{
				$config_details->value = @unserialize($config_details->value);
			}

			$this->config[$config_details->name] = $config_details->value;
		}

		$status_rows = Capsule::table('tblticketstatuses')->get();

		foreach($status_rows as $status_details)
		{
			$this->ticketStatuses[$status_details->id] = (array) $status_details;
		}

		$department_rows = Capsule::table('tblticketdepartments')->get();

		foreach($department_rows as $department_details)
		{
			$this->ticketDepartments[$department_details->id] = (array) $department_details;
		}

		$admin_rows = Capsule::table('tbladmins')->select('id', 'username', 'firstname', 'lastname', 'email')->get();

		foreach($admin_rows as $admin_details)
		{
			$this->admins[$admin_details->id] = (array) $admin_details;
		}

		$this->_loadLanguage();
	}

	public static function getInstance()
	{
		static $i;
		if(!$i) $i = new ticketstasks();
		return $i;
	}

	public function getAdmins()
	{
		return $this->admins;
	}
	
	public function getAdmin($admin_id, $default = null)
	{
		return isset($this->admins[$admin_id]) ? $this->admins[$admin_id] : $default;
	}
	
	public function getTicketDepartments()
	{
		return $this->ticketDepartments;
	}
	
	public function getTicketDepartment($department_id, $default = null)
	{
		return isset($this->ticketDepartments[$department_id]) ? $this->ticketDepartments[$department_id] : $default;
	}
	
	public function getTicketStatuses()
	{
		return $this->ticketStatuses;
	}
	
	public function getTicketStatus($tickt_id, $default = null)
	{
		return isset($this->ticketStatuses[$tickt_id]) ? $this->ticketStatuses[$tickt_id] : $default;
	}
	
	public function getConfig($key, $default = null)
	{
		return isset($this->config[$key]) ? $this->config[$key] : $default;
	}

	function setConfig($key, $value)
	{
		if(isset($this->config[$key]))
		{
			Capsule::table('mod_ticketstasks_config')
				->where('name', '=', $key)
				->update(array(
					'value' 	=> $value,
				));
		}
		else
		{
			Capsule::table('mod_ticketstasks_config')
				->insert(array(
					'name' 		=> $key,
					'value' 	=> $value,
				));
		}

		$this->config[$key] = $value;

		if(preg_match("/^a:\d+:{.*?}$/", $value)) 
		{
			$value = @unserialize($value);
		}

		$this->config[$key] = $value;
	}

	static public function request_var($name, $default = null, $options = array())
	{
		if(is_array($name))
		{
			$var = $_REQUEST;
			foreach($name as $key) $var = isset($var[$key]) ? $var[$key] : null;
		}
		else
		{
			$var = isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
		}

		$value = null;

		if((!isset($var)) || sizeof($options) && !in_array($var, $options))
		{
			$value = $default;
		}
		elseif((!sizeof($options)) || sizeof($options) && in_array($var, $options))
		{
			$value = $var;
		}

		if(isset($default))
		{
			switch(gettype($default))
			{
				case 'integer': return intval($value); break;
				case 'double': return floatval($value); break;
				default: return $value; break;
			}
		}

		return $value;
	}
	
	static public function trigger_message($success, $message)
	{
		define('JTT_TRIGGER', true);
		define('JTT_TRIGGER_TYPE', ($success ? 'success' : 'error'));
		define('JTT_TRIGGER_TITLE', ($success ? 'Success!' : 'Error!'));
		define('JTT_TRIGGER_MESSAGE', $message);
	}
	
	function lang($key, $default = null)
	{
		return isset($this->lang[$key]) ? $this->lang[$key] : (isset($default) ? $default : $key);
	}
	
	private function _loadLanguage()
	{
		$admin_details = Capsule::table('tbladmins')
			->select('language')
			->where('id', $_SESSION['adminid'])
			->get();

		$admin_details = isset($admin_details[0]) ? (array) $admin_details[0] : null;

		$default = 'english';
		$language = strtolower($admin_details['language']);

		$admin_lang_file = JTT_ROOT_PATH . "/lang/{$language}.php";
		$default_lang_file = JTT_ROOT_PATH . "/lang/{$default}.php";
				
		if(file_exists($admin_lang_file))
		{
			require($admin_lang_file);
		}
		elseif(file_exists($default_lang_file))
		{
			require($default_lang_file);
		}

		$this->lang = isset($_ADDONLANG) ? $_ADDONLANG : array();
	}

	static public function deleteTask($ticket_id, $task_id)
	{
		$output = array('success' => false, 'message' => '');

		Capsule::table('mod_ticketstasks_tasks')
			->where('id', $task_id)
			->delete();

		addTicketLog($ticket_id, 'Ticket Tasks Deleted');

		$output['success'] = true;

		return $output;
	}

	static public function addTask($ticket_id, $fields)
	{
		$output = array('success' => false, 'message' => '', 'task' => array());

		if(!is_array($fields) || !sizeof($fields))
		{
			$output['message'] = 'No data was provided';
			return $output;
		}

		$fields['task_time'] = 0;

		if($fields['date'] && isset($fields['hours']) && isset($fields['minutes']))
		{
			list($day, $month, $year) = explode('/', $fields['date']);
			$fields['task_time'] = mktime($fields['hours'], $fields['minutes'], 0, $month, $day, $year);
		}

		if(intval($fields['task_time']) > time() && $fields['status'] && ($fields['urgency'] && in_array($fields['urgency'], array('Low','Medium','High')) || !$fields['urgency']) && (!$fields['abort']['invoice'] || $fields['abort']['invoice'] && $fields['abort']['invoiceids'] && preg_match("/^([0-9\,]+)$/", trim($fields['abort']['invoiceids']))))
		{
			if(!isset($fields['abort']) || !sizeof($fields['abort']))
			{
				$fields['abort'] = array();
			}

			if($fields['abort']['lastreply'])
			{
				$ticket_details = Capsule::table('tbltickets')
					->select('lastreply')
					->where('id', $ticket_id)
					->get();

				$ticket_details = isset($ticket_details[0]) ? (array) $ticket_details[0] : null;
				$fields['abort']['lastreplytime'] = $ticket_details['lastreply'];
			}

			if($fields['abort']['invoice'])
			{
				$invoice_ids = explode(',', trim($fields['abort']['invoiceids']));

				$fields['abort']['invoiceids'] = array();

				foreach($invoice_ids as $invoice_id)
				{
					if(intval(trim($invoice_id)) > 0) $fields['abort']['invoiceids'][] = intval(trim($invoice_id));
				}

				$fields['abort']['invoiceids'] = implode(',', $fields['abort']['invoiceids']);
			}
			else
			{
				unset($fields['abort']['invoiceids']);
			}

			$task_id = Capsule::table('mod_ticketstasks_tasks')->insertGetId([
				'task_time'		    => $fields['task_time'],
				'admin_id'		    => intval($_SESSION['adminid']),
				'time'			    => time(),
				'task_repeat'		=> $fields['interval_type'] == 'repeated' ? $fields['interval'] : '',
				'ticket_id'		    => $ticket_id,
				'ticket_status_id'	=> intval($fields['status']),
				'ticket_admin_id'	=> intval($fields['flag']),
				'ticket_admin_email'	=> $fields['flagemail'] ? 1 : 0,
				'ticket_dept_id'	=> intval($fields['dept']),
				'ticket_urgency'	=> $fields['urgency'],
				'ticket_bump'		=> intval($fields['bump']),
				'ticket_reply'		=> $fields['reply'],
				'ticket_note'		=> $fields['note'],
				'task_abort'		=> serialize($fields['abort']),
				'task_status'		=> 'Pending',
			]);

			if($task_id)
			{
				addTicketLog($ticket_id, 'Ticket Tasks Added');

				Capsule::table('tbltickets')
					->where('id', '=', $ticket_id)
					->update(array(
						'status' 	=> 'Pending Task',
					));

				$task_details = Capsule::table('mod_ticketstasks_tasks as s')
					->select('s.*', 's.id as task_id', 't.*')
					->join('tbltickets as t', 's.ticket_id', '=', 't.id')
					->where('s.ticket_id', '=', $ticket_id)
					->where('s.id', '=', $task_id)
					->get();

				$task_details = isset($task_details[0]) ? (array) $task_details[0] : null;

				$task_details['task_time_date'] = date("d/m/Y", $task_details['task_time']);
				$task_details['task_time_hours'] = date("H", $task_details['task_time']);
				$task_details['task_time_minutes'] = date("i", $task_details['task_time']);
				$task_details['task_time'] = date("d/m/Y H:i", $task_details['task_time']);
				$task_details['task_abort'] = $task_details['task_abort'] ? unserialize($task_details['task_abort']) : array();

				$output['task'] = $task_details;

				$output['success'] = true;
			}
			else
			{
				$output['message'] = (!$output['success'] ? 'Unable to add task' : '');
			}
		}
		else
		{
			$instance = ticketstasks::getInstance();
			$errors = array();

			if(intval($fields['task_time']) <= time()) $errors[] = $instance->lang('invalidtaskdate');
			if((!$fields['status'] && !$fields['dept'] && !$fields['urgency'] && !$fields['flag'] && !$fields['bump'] && !$fields['reply'] && !$fields['note'])) $errors[] = $instance->lang('notasksselected');
			if($fields['abort']['invoice'] && !trim($fields['abort']['invoiceids'])) $errors[] = $instance->lang('noinvoiceids');
			if($fields['abort']['invoice'] && trim($fields['abort']['invoiceids']) && !preg_match("/^([0-9\,]+)$/", trim($fields['abort']['invoiceids']))) $errors[] = $instance->lang('invalidinvoiceids');

			$output['message'] = implode("<br />", $errors);
		}

		return $output;
	}

	static public function editTask($ticket_id, $task_id, $fields)
	{
		$output = array('success' => false, 'message' => '', 'task' => array());

		if(!is_array($fields) || !sizeof($fields))
		{
			$output['message'] = 'No data was provided';
			return $output;
		}

		$fields['task_time'] = 0;

		if($fields['date'] && isset($fields['hours']) && isset($fields['minutes']))
		{
			list($day, $month, $year) = explode('/', $fields['date']);
			$fields['task_time'] = mktime($fields['hours'], $fields['minutes'], 0, $month, $day, $year);
		}

		if(intval($fields['task_time']) > time() && $fields['status'] && ($fields['urgency'] && in_array($fields['urgency'], array('Low','Medium','High')) || !$fields['urgency']) && (!$fields['abort']['invoice'] || $fields['abort']['invoice'] && $fields['abort']['invoiceids'] && preg_match("/^([0-9\,]+)$/", trim($fields['abort']['invoiceids']))))
		{
			if(!isset($fields['abort']) || !sizeof($fields['abort']))
			{
				$fields['abort'] = array();
			}

			if($fields['abort']['lastreply'])
			{
				$ticket_details = Capsule::table('tbltickets')
					->select('lastreply')
					->where('id', $ticket_id)
					->get();

				$ticket_details = isset($ticket_details[0]) ? (array) $ticket_details[0] : null;
				$fields['abort']['lastreplytime'] = $ticket_details['lastreply'];
			}

			if($fields['abort']['invoice'])
			{
				$invoice_ids = explode(',', trim($fields['abort']['invoiceids']));

				$fields['abort']['invoiceids'] = array();

				foreach($invoice_ids as $invoice_id)
				{
					if(intval(trim($invoice_id)) > 0) $fields['abort']['invoiceids'][] = intval(trim($invoice_id));
				}

				$fields['abort']['invoiceids'] = implode(',', $fields['abort']['invoiceids']);
			}
			else
			{
				unset($fields['abort']['invoiceids']);
			}

			Capsule::table('mod_ticketstasks_tasks')
				->where('id', $task_id)
				->where('task_status', 'Pending')
				->update([
				'task_time'		=> $fields['task_time'],
				'task_repeat'		=> $fields['interval_type'] == 'repeated' ? $fields['interval'] : '',
				'ticket_status_id'	=> intval($fields['status']),
				'ticket_admin_id'	=> intval($fields['flag']),
				'ticket_admin_email'	=> $fields['flagemail'] ? 1 : 0,
				'ticket_dept_id'	=> intval($fields['dept']),
				'ticket_urgency'	=> $fields['urgency'],
				'ticket_bump'		=> intval($fields['bump']),
				'ticket_reply'		=> $fields['reply'],
				'ticket_note'		=> $fields['note'],
				'task_abort'		=> serialize($fields['abort']),
			]);

			addTicketLog($ticket_id, 'Ticket Tasks Updated');

			Capsule::table('tbltickets')
				->where('id', '=', $ticket_id)
				->update(array(
					'status' 	=> 'Pending Task',
				));

			$task_details = Capsule::table('mod_ticketstasks_tasks as s')
				->select('s.*', 's.id as task_id', 't.*')
				->join('tbltickets as t', 's.ticket_id', '=', 't.id')
				->where('s.ticket_id', '=', $ticket_id)
				->where('s.id', '=', $task_id)
				->get();

			$task_details = isset($task_details[0]) ? (array) $task_details[0] : null;

			$task_details['task_time_date'] = date("d/m/Y", $task_details['task_time']);
			$task_details['task_time_hours'] = date("H", $task_details['task_time']);
			$task_details['task_time_minutes'] = date("i", $task_details['task_time']);
			$task_details['task_time'] = date("d/m/Y H:i", $task_details['task_time']);
			$task_details['task_abort'] = $task_details['task_abort'] ? unserialize($task_details['task_abort']) : array();

			$output['task'] = $task_details;

			$output['success'] = true;
		}
		else
		{
			$instance = ticketstasks::getInstance();
			$errors = array();

			if(intval($fields['task_time']) <= time()) $errors[] = $instance->lang('invalidtaskdate');
			if((!$fields['status'] && !$fields['dept'] && !$fields['urgency'] && !$fields['flag'] && !$fields['bump'] && !$fields['reply'] && !$fields['note'])) $errors[] = $instance->lang('notasksselected');
			if($fields['abort']['invoice'] && !trim($fields['abort']['invoiceids'])) $errors[] = $instance->lang('noinvoiceids');
			if($fields['abort']['invoice'] && trim($fields['abort']['invoiceids']) && !preg_match("/^([0-9\,]+)$/", trim($fields['abort']['invoiceids']))) $errors[] = $instance->lang('invalidinvoiceids');

			$output['message'] = implode("<br />", $errors);
		}

		return $output;
	}
}

?>
