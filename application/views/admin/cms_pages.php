<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_cms_pages_manage">
	    <div class="edu_btn_wrapper sectionHolder padderBottom30 text-right res-left">
	        <a href="#cmsPageModal" class="edu_admin_btn openPopupLink add_cms_page"><i class="icofont-plus"></i><?php echo html_escape($this->common->languageTranslator('ltr_add_cms_page'));?></a>
	    </div>
	    <div class="createDivWrapper edu_add_question create_ppr_popup hide">
    		<div class="edu_admin_informationdiv sectionHolder">
    		    <div class="ppr_popup_inner">
        			<div class="row align-items-center text-center">
        			    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
					<button class="multiDelete btn_delete btn btn-primary" data-placement="top" title="Delete" data-table="pages" data-column="id"><?php echo html_escape($this->common->languageTranslator('ltr_delete'));?></button>
        		</div>
					</div>
        		</div>
    		</div>
		</div>
	    <div class="edu_main_wrapper edu_table_wrapper">
		    <div class="edu_admin_informationdiv sectionHolder">
                <div class="tableFullWrapper">
                    <table class="server_datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%" data-url="ajaxcall/pages_table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="checkAllAttendance"></th>
                                <th>#</th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_page_key'));?></th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_page_url'));?></th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_title'));?></th>
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

<div id="cmsPageModal" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" id="cmsPageModalLabel"><?php echo html_escape($this->common->languageTranslator('ltr_add_cms_page'));?></h4>
            <form method="post" autocomplete="off" id="cmsPageForm">
				<div class="row">
				    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_page_key'));?><sup>*</sup></label>
    						<input type="text" class="form-control require" id="page_key" name="page_key" placeholder="about_us">
    					</div>
    				</div>
    				<div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_page_url'));?><sup>*</sup></label>
    						<input type="text" class="form-control require" id="page_url" name="page_url" placeholder="about-us">
    						<p class="edu_info"><?php echo html_escape($this->common->languageTranslator('ltr_page_url_note'));?></p>
    					</div>
    				</div>
    				<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_title'));?><sup>*</sup></label>
    						<input type="text" class="form-control require" name="subject" id="page_subject" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_title'));?>">
    					</div>
    				</div>
					<div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
    					<div class="form-group">
    						<label><?php echo html_escape($this->common->languageTranslator('ltr_description'));?><sup>*</sup></label>
                            <textarea name="content" id="page_content" class="form-control page_content require" rows="6" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_description'));?>"></textarea>
    					</div>
    				</div>
					<div class="col-lg-12 col-md-12 col-sm-12 col-12">
    					<div class="edu_btn_wrapper">
    						<input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_save'));?>" class="btn btn-primary addEditCmsPage" data-id="" />
    					</div>
    				</div>
				</div>
			</form>
        </div>
    </div>
</div>
