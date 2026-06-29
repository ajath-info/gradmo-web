<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=9">
<link rel="stylesheet" href="<?php echo base_url('assets/css/jquery-ui.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/select2.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/js/timepicker/bootstrap-clockpicker.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/backend.css'); ?>?v=<?php echo time(); ?>">
<style>
/* Teacher batch form — same markup/classes as admin/add-batch; only layout tweaks */
.tcb-page .inst-detail-container { max-width: 1180px; }
.tcb-page .edu_admin_content {
	display: block;
	width: 100%;
	padding: 0;
	padding-top: 0;
}
.tcb-page .edu_admin_right.sectionHolder.edu_add_users {
	padding: 0;
}
.tcb-page .edu_admin_informationdiv.edu_main_wrapper {
	background: transparent;
	box-shadow: none;
	padding: 0;
}
.tcb-page .edu_btn_wrapper .edu_admin_btn {
	text-transform: uppercase;
}
.tcb-cancel-row {
	margin-top: 16px;
	padding: 0 4px;
}
/* Category / subcategory / institute — native selects (same look as Batch mode) */
.tcb-page .select2-container + select.tcb-native-select,
.tcb-page select.tcb-native-select + .select2-container {
	display: none !important;
}
.tcb-page #category_dropdown,
.tcb-page #subcategory_dropdown,
.tcb-page #institute_id,
.tcb-page select.tcb-native-select {
	display: block !important;
	width: 100% !important;
	min-height: 45px !important;
	height: 45px !important;
	padding: 0 12px;
	border: 1px solid #e7e7e9;
	border-radius: 4px;
	background: #fff;
	color: #333;
	font-size: 14px;
	line-height: 43px;
	-webkit-appearance: menulist;
	appearance: menulist;
	opacity: 1 !important;
	position: static !important;
	clip: auto !important;
	clip-path: none !important;
}
/* Select2 for subject/chapter rows inside accordions */
.tcb-page .form-group .select2-container {
	width: 100% !important;
	display: block;
	min-height: 45px;
}
.tcb-page .select2-container--default .select2-selection--single {
	height: 45px;
	border: 1px solid #e7e7e9;
	border-radius: 4px;
}
.tcb-page .select2-container--default .select2-selection--single .select2-selection__rendered {
	line-height: 43px;
	padding-left: 12px;
	color: #333;
}
.tcb-page .select2-container--default .select2-selection--single .select2-selection__arrow {
	height: 43px;
}
.tcb-page .form-control,
.tcb-page #b_startDate,
.tcb-page #b_endDate {
	min-height: 45px;
	border-radius: 4px;
	border: 1px solid #e7e7e9;
}
.tcb-page .edu_accordion_container_heading,
.tcb-page .edu_accordion_container {
	margin-bottom: 12px;
}
.tcb-page .eb_subhead_icon {
	display: flex;
	gap: 10px;
	align-items: center;
}
.tcb-page .eb_subhead_icon i {
	cursor: pointer;
	font-size: 16px;
}
.tcb-page .AssignBatchHeading,
.tcb-page .AssignSubBatch {
	margin-top: 8px;
}
</style>
<script>
	var ltr_valid_price_msg = <?php echo json_encode(html_escape($this->common->languageTranslator('ltr_valid_price_msg'))); ?>;
	var ltr_benefit = <?php echo json_encode(html_escape($this->common->languageTranslator('ltr_benefit'))); ?>;
	var ltr_batch_spe_msg = <?php echo json_encode(html_escape($this->common->languageTranslator('ltr_batch_spe_msg'))); ?>;
</script>

<div class="inst-detail-page tcb-page">
	<div class="inst-detail-mobile-bar">
		<a href="<?php echo base_url('batch/mylist'); ?>" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title"><?php echo !empty($batch_id) ? html_escape($this->common->languageTranslator('ltr_edit_batch')) : html_escape($this->common->languageTranslator('ltr_add_batch')); ?></div>
	</div>
	<div class="inst-detail-container">
		<section class="edu_admin_content">
			<div class="edu_admin_right sectionHolder edu_add_users">
				<div class="edu_admin_informationdiv edu_main_wrapper tcb-form-wrap" id="teacherBatchFormRoot">
					<?php $this->load->view('common/batch_add_form'); ?>
				</div>
			</div>
		</section>
		<div class="tcb-cancel-row">
			<a href="<?php echo base_url('batch/mylist'); ?>" class="btn btn-outline-secondary"><?php echo html_escape($this->common->languageTranslator('ltr_cancel')); ?></a>
		</div>
	</div>
</div>
