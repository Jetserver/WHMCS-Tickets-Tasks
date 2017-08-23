<?php if (!defined("JETTICKETSTASKS")) die("This file cannot be accessed directly"); ?>
<?php include_once(JTT_ROOT_PATH . "/template/overall_header.php"); ?>

<p>Set up a cron job to run every 10 minutes.</p>

<div class="alert alert-warning text-center">
	<div class="input-group">
		<span class="input-group-addon"><?php echo ticketstasks::getInstance()->lang('createphpcron'); ?></span>
		<input type="text" class="form-control" value="php -q <?php echo JTT_ROOT_PATH; ?>/cron.php <?php echo ticketstasks::getInstance()->getConfig('token'); ?>" />
	</div>
	<strong><?php echo ticketstasks::getInstance()->lang('or'); ?></strong>
	<br />
	<div class="input-group">
		<span class="input-group-addon"><?php echo ticketstasks::getInstance()->lang('creategetcron'); ?></span>
		<input type="text" class="form-control" value="GET <?php echo $CONFIG['SystemURL']; ?>/modules/addons/ticketstasks/cron.php?token=<?php echo ticketstasks::getInstance()->getConfig('token'); ?>" id="cronGet" />
	</div>
</div>

<form action="<?php echo $modulelink; ?>&pagename=settings" method="post">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
	<td class="fieldlabel" style="width: 25%;"><?php echo ticketstasks::getInstance()->lang('system_enabled'); ?></td>
	<td class="fieldarea">
		<input type="radio"<?php if(ticketstasks::getInstance()->getConfig('system_enabled')) { ?> checked="checked"<?php } ?> name="config[system_enabled]" value="1" /> <?php echo ticketstasks::getInstance()->lang('yes'); ?>
		<input type="radio"<?php if(!ticketstasks::getInstance()->getConfig('system_enabled')) { ?> checked="checked"<?php } ?>  name="config[system_enabled]" value="0" /> <?php echo ticketstasks::getInstance()->lang('no'); ?>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo ticketstasks::getInstance()->lang('activeonlist'); ?></td>
	<td class="fieldarea">
		<input type="radio"<?php if(ticketstasks::getInstance()->getConfig('list_active')) { ?> checked="checked"<?php } ?> name="config[list_active]" value="1" /> <?php echo ticketstasks::getInstance()->lang('yes'); ?>
		<input type="radio"<?php if(!ticketstasks::getInstance()->getConfig('list_active')) { ?> checked="checked"<?php } ?>  name="config[list_active]" value="0" /> <?php echo ticketstasks::getInstance()->lang('no'); ?>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo ticketstasks::getInstance()->lang('activeonview'); ?></td>
	<td class="fieldarea">
		<input type="radio"<?php if(ticketstasks::getInstance()->getConfig('view_active')) { ?> checked="checked"<?php } ?> name="config[view_active]" value="1" /> <?php echo ticketstasks::getInstance()->lang('yes'); ?>
		<input type="radio"<?php if(!ticketstasks::getInstance()->getConfig('view_active')) { ?> checked="checked"<?php } ?>  name="config[view_active]" value="0" /> <?php echo ticketstasks::getInstance()->lang('no'); ?>
	</td>
</tr>
</tbody>
</table>


<div class="btn-container">
	<input type="hidden" name="action" value="save" />
	<input type="submit" name="submit" class="btn btn-primary" value="<?php echo ticketstasks::getInstance()->lang('save_changes'); ?>" />
</div>

</form>

<?php include_once(JTT_ROOT_PATH . "/template/overall_footer.php"); ?>