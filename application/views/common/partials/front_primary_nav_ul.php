<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<ul>
	<li><a href="<?php echo base_url(); ?>">Home</a></li>
	<?php if (!empty($_SESSION['role'])) { ?>
		<li><a href="<?php echo base_url('batch/mylist'); ?>">My Batches</a></li>
		<li><a href="<?php echo base_url('batch/video-lectures'); ?>">Recorded Lectures</a></li>
		<li><a href="<?php echo base_url('homework-list'); ?>">Homework</a></li>
		<li><a href="<?php echo base_url('attendance'); ?>">Attendance</a></li>
		<li><a href="<?php echo base_url('batch/exams'); ?>">Assignments</a></li>
	<?php } else { ?>
		<li><a href="<?php echo base_url('contact-us'); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_contact_us')); ?></a></li>
	<?php } ?>
</ul>
