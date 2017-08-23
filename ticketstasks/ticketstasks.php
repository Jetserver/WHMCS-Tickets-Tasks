<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

define('JETTICKETSTASKS', true);
define('JTT_ROOT_PATH', dirname(__FILE__));
define('WHMCS_ROOT_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../'));

use Illuminate\Database\Capsule\Manager as Capsule;

function ticketstasks_config() 
{
	return array(
		"name" 		=> "Tickets Tasks",
		"description" 	=> "This addon module will help you maximize your productivity when using WHMCS internal tickets system.<br />You can specify future tasks to be executed for a ticket such as - Change status, Change priority, Flag ticket, Bump ticket, Add note, Send predefined reply.<br />Furthermore, you can smartly set the module to abort the task if a specific invoice has been paid, or ticket last reply was changed.<br /><br />The module is fully integrated into WHMCS's tickets system - there is no need for any template changes!",
		"version" 	=> "1.5.7",
		"author" 	=> "Idan Ben-Ezra",
		"language" 	=> "english",
	);
}

function ticketstasks_activate() 
{
    ticketstasks_deactivate();

    if(!Capsule::hasTable('mod_ticketstasks_config'))
    {
	    Capsule::schema()->create('mod_ticketstasks_config', function($table) {
		    $table->string('name', 255);
		    $table->text('value');
		    $table->unique('name');
	    });

	    Capsule::table('mod_ticketstasks_config')->insert(array(
		    array('name' => 'token', 'value' => md5(rand() . time() . $_SERVER['REMOTE_ADDR'])),
		    array('name' => 'system_enabled', 'value' => '1'),
		    array('name' => 'list_active', 'value' => '1'),
		    array('name' => 'view_active', 'value' => '1'),
	    ));
    }

	if(!Capsule::hasTable('mod_ticketstasks_tasks'))
	{
        Capsule::schema()->create('mod_ticketstasks_tasks', function($table) {
            $table->increments('id');
            $table->integer('task_time')->default(0);
            $table->integer('ticket_id')->default(0);
            $table->integer('admin_id')->default(0);
            $table->integer('time')->default(0);
            $table->string('task_repeat', 255);
            $table->integer('ticket_status_id')->default(0);
            $table->integer('ticket_admin_id')->default(0);
            $table->tinyInteger('ticket_admin_email')->default(0);
            $table->integer('ticket_dept_id')->default(0);
            $table->string('ticket_urgency', 255);
            $table->tinyInteger('ticket_bump')->default(0);
            $table->text('ticket_reply');
            $table->text('ticket_note');
            $table->text('task_abort');
            $table->string('task_status', 255);
        });
	}

	if(!Capsule::hasTable('mod_ticketstasks_tickets'))
	{
        Capsule::schema()->create('mod_ticketstasks_tickets', function($table) {
            $table->increments('id');
            $table->integer('userid')->default(0);
            $table->integer('time')->default(0);
            $table->integer('schedule')->default(0);
            $table->tinyInteger('sendmail')->default(0);
            $table->text('email');
            $table->integer('contactid')->default(0);
            $table->text('ccemail');
            $table->text('subject');
            $table->integer('deptid')->default(0);
            $table->string('priority', 255);
            $table->text('message');
            $table->integer('adminid')->default(0);
            $table->string('status', 255);
        });
	}

	if(!Capsule::hasTable('mod_ticketstasks_predefined'))
	{
		Capsule::schema()->create('mod_ticketstasks_predefined', function($table) {
			$table->increments('id');
			$table->string('title', 255);
			$table->string('task_hours', 255);
			$table->string('task_minutes', 255);
			$table->string('task_repeat', 255);
			$table->integer('ticket_status_id')->default(0);
			$table->integer('ticket_admin_id')->default(0);
			$table->tinyInteger('ticket_admin_email')->default(0);
			$table->integer('ticket_dept_id')->default(0);
			$table->string('ticket_urgency', 255);
			$table->tinyInteger('ticket_bump')->default(0);
			$table->text('ticket_reply');
			$table->text('ticket_note');
			$table->text('task_abort');
		});

		Capsule::table('tblticketstatuses')->insert(array(
			array('title' => 'Pending Task', 'color' => '#B300FF', 'sortorder' => '0', 'showactive' => '1', 'showawaiting' => '0', 'autoclose' => '0'),
		));
	}

	return array(
		'status'	=> 'success',
		'description'	=> 'Tickets Tasks activated successfully'
	);
}

function ticketstasks_deactivate() 
{
	Capsule::schema()->dropIfExists('mod_ticketstasks_config');
	Capsule::schema()->dropIfExists('mod_ticketstasks_tasks');
	Capsule::schema()->dropIfExists('mod_ticketstasks_tickets');
	Capsule::schema()->dropIfExists('mod_ticketstasks_predefined');

	Capsule::table('tbltickets')
		->where('status', 'Pending Task')
		->update(array(
			'status' 	=> 'Open',
		));

	Capsule::table('tblticketstatuses')
		->where('title', 'Pending Task')
		->delete();

	return array(
		'status'	=> 'success',
		'description'	=> 'Tickets Tasks deactivated successfully'
	);
}

function ticketstasks_upgrade($vars) 
{
	if(!isset($vars['version'])) $vars['version'] = $vars['backupmanager']['version'];
	if(!$vars['version']) return;

	if(version_compare($vars['version'], '1.5.3') < 0)
	{
		Capsule::table('mod_ticketstasks_tasks', function($table) {
			$table->string('task_repeat', 255);
		});
	}

	if(!Capsule::hasTable('mod_ticketstasks_tickets'))
	{
		Capsule::schema()->create('mod_ticketstasks_tickets', function($table) {
			$table->increments('id');
			$table->integer('userid')->default(0);
			$table->integer('time')->default(0);
			$table->integer('schedule')->default(0);
			$table->tinyInteger('sendmail')->default(0);
			$table->text('email');
			$table->integer('contactid')->default(0);
			$table->text('ccemail');
			$table->text('subject');
			$table->integer('deptid')->default(0);
			$table->string('priority', 255);
			$table->text('message');
			$table->integer('adminid')->default(0);
			$table->string('status', 255);
		});
	}

	if(!Capsule::hasTable('mod_ticketstasks_predefined'))
	{
		Capsule::schema()->create('mod_ticketstasks_predefined', function($table) {
			$table->increments('id');
			$table->string('title', 255);
			$table->string('task_hours', 255);
			$table->string('task_minutes', 255);
			$table->string('task_repeat', 255);
			$table->integer('ticket_status_id')->default(0);
			$table->integer('ticket_admin_id')->default(0);
			$table->tinyInteger('ticket_admin_email')->default(0);
			$table->integer('ticket_dept_id')->default(0);
			$table->string('ticket_urgency', 255);
			$table->tinyInteger('ticket_bump')->default(0);
			$table->text('ticket_reply');
			$table->text('ticket_note');
			$table->text('task_abort');
		});
	}
}

function ticketstasks_output($vars) 
{
	global $CONFIG, $LANG, $_LANG, $_ADMINLANG;

	$LANG = $vars['_lang'];

	require_once(JTT_ROOT_PATH . '/includes/functions.php');

	$global_success = ticketstasks::request_var('success', '');
	$global_error = ticketstasks::request_var('error', '');
	
	$modulelink = $vars['modulelink'];
	
	$pages = array('tasks','predefined','settings');
	
	$id = ticketstasks::request_var('id', 0);
	$pagename = ticketstasks::request_var('pagename', 'tasks', $pages);
	$view = ticketstasks::request_var('view', '');
	$action = ticketstasks::request_var('action', '');
	$page = ticketstasks::request_var('page', 0);
	
	$view_class = "{$pagename}_default";
	$default_view = JTT_ROOT_PATH . "/views/{$view_class}.php";
	
	if(file_exists($default_view))
	{
		require_once($default_view);
	
		if($view && $view != 'default')
		{
			// load the requested view
			$view_class = "{$pagename}_{$view}";
			$view_file = JTT_ROOT_PATH . "/views/{$view_class}.php";
	
			if(file_exists($view_file))
			{
				require_once($view_file);
			}
			else
			{
				ticketstasks::trigger_message(false, 'The requested view not exists');
			}
		}
	
		if(!defined('JTT_TRIGGER'))
		{
			$view_class = "jtt_{$view_class}";
			$module = new $view_class;
	
			$default_response = $module->_default();
	
			if(isset($default_response['success']) && $default_response['success'])
			{
				if($action)
				{
					if(method_exists($module, $action))
					{
						$action_response = $module->$action();
	
						if(isset($action_response['errormessages']) && sizeof($action_response['errormessages']))
						{
							$template_file = JTT_ROOT_PATH . "/template/{$pagename}_" . ($view ? $view : 'default') . ".php";
	
							if(file_exists($template_file))
							{
								require_once($template_file);
							}
							else
							{
								ticketstasks::trigger_message(false, "The file {$template_file} is missing!");
							}
						}
						else
						{
							header('Location: ' . $modulelink . '&pagename=' . $pagename . ($page ? '&page=' . $page : '') . '&' . ($action_response['success'] ? 'success=' : 'error=') . $action_response['message']);
							exit;
						}
					}
					else
					{
						ticketstasks::trigger_message(false, "Invalid action provided");
					}
				}
				else
				{
					$action_response['data'] = $default_response['data'];
	
					$template_file = JTT_ROOT_PATH . "/template/{$pagename}_" . ($view ? $view : 'default') . ".php";
	
					if(file_exists($template_file))
					{
						require_once($template_file);
					}
					else
					{
						ticketstasks::trigger_message(false, "The file {$template_file} is missing!");
					}
				}
			}
			else
			{
				ticketstasks::trigger_message(false, nl2br($default_response['message']));
			}
		}
	}
	else
	{
		ticketstasks::trigger_message(false, 'The file ' . $default_view . ' is missing!');
	}
	
	if(defined('JTT_TRIGGER'))
	{
		$template_file = JTT_ROOT_PATH . "/template/message.php";
	
		if(file_exists($template_file))
		{
			require_once($template_file);
		}
		else
		{
?>
	<div class="errorbox">
		<strong><span class="title">Error!</span></strong><br />
		The file <?php echo $template_file; ?> is missing!
	</div>
	<?php
		}
	}
}

?>
