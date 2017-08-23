<?php if (!defined("JETTICKETSTASKS")) die("This file cannot be accessed directly"); ?>
<?php include_once(JTT_ROOT_PATH . "/template/overall_header.php"); ?>

<div class="<?php echo JTT_TRIGGER_TYPE; ?>box">
	<strong><span class="title"><?php echo JTT_TRIGGER_TITLE; ?></span></strong><br />
	<?php echo JTT_TRIGGER_MESSAGE; ?>
</div>

<?php include_once(JTT_ROOT_PATH . "/template/overall_footer.php"); ?>