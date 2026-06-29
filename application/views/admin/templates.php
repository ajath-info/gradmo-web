<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_templates_manage">
	    <div class="edu_btn_wrapper sectionHolder padderBottom30 text-right res-left">
	        <a href="#templateModal" class="edu_admin_btn add_template"><i class="icofont-plus"></i><?php echo html_escape($this->common->languageTranslator('ltr_add_template'));?></a>
	    </div>
	    <div class="createDivWrapper edu_add_question create_ppr_popup hide">
    		<div class="edu_admin_informationdiv sectionHolder">
    		    <div class="ppr_popup_inner">
        			<div class="row align-items-center text-center">
        			    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
					<button class="multiDelete btn_delete btn btn-primary" data-placement="top" title="Delete" data-table="templates" data-column="id"><?php echo html_escape($this->common->languageTranslator('ltr_delete'));?></button>
        		</div>
					</div>
        		</div>
    		</div>
		</div>
	    <div class="edu_main_wrapper edu_table_wrapper">
		    <div class="edu_admin_informationdiv sectionHolder">
                <div class="tableFullWrapper">
                    <table class="server_datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%" data-url="ajaxcall/templates_table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="checkAllAttendance"></th>
                                <th>#</th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_template_for'));?></th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_purpose'));?></th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_title'));?></th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_image'));?></th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_description'));?></th>
                                <th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_status'));?></th>
                                <th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_action'));?></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
		</div>
	</div>
</section>

<div id="templateModal" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" id="templateModalLabel"><?php echo html_escape($this->common->languageTranslator('ltr_add_template'));?></h4>
            <form method="post" autocomplete="off" id="templateForm" enctype="multipart/form-data">
				<div class="row">
				    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_template_for'));?><sup>*</sup></label>
    						<select class="form-control require" name="template_for" id="template_for">
    							<option value="email">Email</option>
    							<option value="sms">SMS</option>
    						</select>
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_purpose'));?><sup>*</sup></label>
    						<input type="text" class="form-control require" id="template_purpose" name="purpose" placeholder="welcome_email">
    						<p class="edu_info"><?php echo html_escape($this->common->languageTranslator('ltr_template_purpose_note'));?></p>
    					</div>
    				</div>
    				<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_title'));?><sup>*</sup></label>
    						<input type="text" class="form-control require" name="title" id="template_title" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_title'));?>">
    					</div>
    				</div>
    				<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_image'));?></label>
    						<input type="file" class="form-control" name="image" id="template_image" accept="image/*">
    						<p class="fileNameShow edu_top_10"></p>
    						<div id="template_image_preview" class="edu_top_10"></div>
    					</div>
    				</div>
    				<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_description'));?></label>
                            <textarea name="description" id="template_description" class="form-control" rows="4" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_description'));?>"></textarea>
    					</div>
    				</div>
					<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_html_code'));?><sup>*</sup></label>
                            <textarea name="html_code" id="template_html_code" class="form-control require" rows="8"></textarea>
    					</div>
    				</div>
					<div class="col-lg-12 col-md-12 col-sm-12 col-12">
    					<div class="edu_btn_wrapper">
    						<input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_save'));?>" class="btn btn-primary addEditTemplate" data-id="" />
    					</div>
    				</div>
				</div>
			</form>
        </div>
    </div>
</div>
