<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_subject_manager">
		<div class="edu_btn_wrapper sectionHolder padderBottom30 text-right">
			<a href="#promoCodePopup" class="edu_admin_btn openPopupLink addPromoPop"><i class="icofont-plus"></i><?php echo html_escape($this->common->languageTranslator('ltr_add_promo_code'));?></a>
		</div>

		<div class="createDivWrapper edu_add_question create_ppr_popup hide">
			<div class="edu_admin_informationdiv sectionHolder">
				<div class="ppr_popup_inner">
					<div class="row align-items-center text-center">
						<div class="col-lg-12 col-md-12 col-sm-12 col-12">
							<button class="promoMultiDelete btn_delete btn btn-primary" data-placement="top" title="Delete"><?php echo html_escape($this->common->languageTranslator('ltr_delete'));?></button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php if(!empty($promo_data) && $promo_data>=1){ ?>
		<div class="edu_main_wrapper edu_table_wrapper">
			<div class="edu_admin_informationdiv sectionHolder">
				<div class="tableFullWrapper">
					<table class="server_datatable datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%" data-url="ajaxcall/promo_code_table">
						<thead>
							<tr>
								<th><input type="checkbox" class="checkAllAttendance"></th>
								<th>#</th>
								<th><?php echo html_escape($this->common->languageTranslator('ltr_promo_code'));?></th>
								<th><?php echo html_escape($this->common->languageTranslator('ltr_discount_type'));?></th>
								<th><?php echo html_escape($this->common->languageTranslator('ltr_discount_value'));?></th>
								<th><?php echo html_escape($this->common->languageTranslator('ltr_valid_from'));?></th>
								<th><?php echo html_escape($this->common->languageTranslator('ltr_valid_to'));?></th>
								<th><?php echo html_escape($this->common->languageTranslator('ltr_usage'));?></th>
								<th><?php echo html_escape($this->common->languageTranslator('ltr_status'));?></th>
								<th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_actions'));?></th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php }else{
			echo '<section class="edu_admin_content">
					<div class="edu_admin_right sectionHolder edu_add_users">
						<div class="edu_admin_informationdiv edu_main_wrapper">
							<div class="eac_text eac_page_re">'.html_escape($this->common->languageTranslator('ltr_no_data_found')).'</div>
						</div>
					</div>
				</section>';
		} ?>
	</div>
</section>

<!-- Add / Edit Promo Code Popup -->
<div id="promoCodePopup" class="edu_popup_container mfp-hide">
	<div class="edu_popup_wrapper">
		<div class="edu_popup_inner">
			<h4 class="edu_sub_title" id="promoPopupTitle"><?php echo html_escape($this->common->languageTranslator('ltr_add_promo_code'));?></h4>
			<form method="post" class="pxn_amin form" action="javascript:void(0)" autocomplete="off">
				<input type="hidden" id="promoId" value="">
				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-12 col-12">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_promo_code'));?><sup>*</sup></label>
							<input type="text" id="promoCode" class="form-control" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_promo_code'));?>" />
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-12">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_discount_type'));?><sup>*</sup></label>
							<select id="promoDiscountType" class="form-control">
								<option value="PERCENT"><?php echo html_escape($this->common->languageTranslator('ltr_percent'));?></option>
								<option value="FLAT"><?php echo html_escape($this->common->languageTranslator('ltr_flat'));?></option>
							</select>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-12">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_discount_value'));?><sup>*</sup></label>
							<input type="number" step="0.01" min="0" id="promoDiscountValue" class="form-control" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_discount_value'));?>" />
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-12">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_max_use'));?></label>
							<input type="number" min="0" id="promoMaxUse" class="form-control" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_max_use'));?>" />
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-12">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_valid_from'));?></label>
							<input type="date" id="promoValidFrom" class="form-control" />
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-12">
						<div class="form-group">
							<label><?php echo html_escape($this->common->languageTranslator('ltr_valid_to'));?></label>
							<input type="date" id="promoValidTo" class="form-control" />
						</div>
					</div>
					<div class="col-lg-12 col-md-12 col-sm-12 col-12">
						<div class="edu_btn_wrapper">
							<input type="button" id="savePromoCode" value="<?php echo html_escape($this->common->languageTranslator('ltr_save'));?>" class="edu_admin_btn savePromoCode" />
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
