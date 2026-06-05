<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<ul>
	<li><a href="<?php echo base_url(); ?>">Home</a></li>
	<?php if (!empty($_SESSION['role'])) { ?>
		<li><a href="<?php echo base_url('batch/mylist'); ?>">My Batches</a></li>
	<?php } else { ?>
		<li><a href="<?php echo base_url('contact-us'); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_contact_us')); ?></a></li>
	<?php } ?>
</ul>
