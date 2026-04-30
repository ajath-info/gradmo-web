<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_teacher_manager">
	    <div class="edu_btn_wrapper sectionHolder padderBottom30 text-right">
            <a href="#input_feilds_institute" class="edu_admin_btn openPopupLink ml-2 addInstitutePop"><i class="icofont-plus"></i><?php echo html_escape($this->common->languageTranslator('ltr_Intitute_add'));?></a>
	    </div>

	    <div class="createDivWrapper edu_add_question create_ppr_popup hide">
    		<div class="edu_admin_informationdiv sectionHolder">
    		    <div class="ppr_popup_inner">
        			<div class="row align-items-center text-center">
        			    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
					<button class="multiDelete btn_delete btn btn-primary" data-toggle="tooltip" data-placement="top" title="Delete" data-table="users" data-column="id"><?php echo html_escape($this->common->languageTranslator('ltr_delete'));?></button>
        		</div>
					</div>
        		</div>
    		</div>
		</div>
		<?php if(!empty($institute_data) && $institute_data>=1){ ?>
	    <div class="edu_main_wrapper edu_table_wrapper">		
			<div class="edu_admin_informationdiv sectionHolder dropdown_height">
                <div class="tableFullWrapper">
                    <table class="server_datatable datatable table table-striped table-hover dt-responsive" cellspacing="0" width="100%" data-url="ajaxcall/institute_table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="checkAllAttendance"></th>
                                <th>#</th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_name'));?></th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_email'));?></th>
                                <th>Country</th>
                                <th>State</th>
                                <th>City</th>
                                <th>Pincode</th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_address'));?></th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_status'));?></th>
                                <th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_action'));?></th>
                                <th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_added_by'));?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
			</div>
		</div>
		<?php }else{ 
		     echo '<section class="edu_admin_content">
                    <div class="edu_admin_right sectionHolder edu_add_users">
                        <div class="edu_admin_informationdiv edu_main_wrapper">
                            <div class="eac_text eac_page_re">No institute data found.</div>
                        </div>
                    </div>
                </section>';
		} ?>
	</div>
</section>

<div id="input_feilds_institute" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" id="PopupTitle"><?php echo html_escape($this->common->languageTranslator('ltr_Intitute_add'));?></h4>
            <form class="pxn_amin form" action="" method="post" autocomplete="off">
                <div class="row">   
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_name'));?><sup>*</sup></label>
                            <input type="text" class="form-control require alphaField" name="name" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_name'));?>">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_gender'));?><sup>*</sup></label>
                            <select name="teach_gender" class="form-control require edu_selectbox_without_search">
                                <option value=""><?php echo html_escape($this->common->languageTranslator('ltr_select_gender'));?></option>
                                <option value="male"><?php echo html_escape($this->common->languageTranslator('ltr_male'));?></option>
                                <option value="female"><?php echo html_escape($this->common->languageTranslator('ltr_female'));?></option>
                                <option value="other"><?php echo html_escape($this->common->languageTranslator('ltr_other'));?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_email'));?><sup>*</sup></label>
                            <input type="text" class="form-control require" name="email" data-valid="email" data-error="Please enter a valid email." placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_email'));?>">
                        </div>
                     </div> 
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_image'));?><sup>*</sup></label>
                            <input type="file" class="form-control require" name="teach_image" data-valid="image" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_image_msg'));?>">
                            <p class="fileNameShow"></p>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_education'));?></label>
                            <input type="text" class="form-control" name="teach_education" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_education'));?>">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_password'));?><sup class="hide">*</sup></label>
                            <input type="password" class="form-control" name="password" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_password'));?>">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Country<sup>*</sup></label>
                            <input type="text" class="form-control require" name="country" placeholder="Country">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>State<sup>*</sup></label>
                            <input type="text" class="form-control require" name="state" placeholder="State">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>City<sup>*</sup></label>
                            <input type="text" class="form-control require" name="city" placeholder="City">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Pincode<sup>*</sup></label>
                            <input type="text" class="form-control require" name="pincode" placeholder="Pincode">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_address'));?><sup>*</sup></label>
                            <textarea class="form-control require" name="address" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_address'));?>"></textarea>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" class="form-control" name="lat" readonly>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" class="form-control" name="long" readonly>
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="edu_btn_wrapper">
                            <input type="button" value="<?php echo html_escape($this->common->languageTranslator('ltr_Intitute_add'));?>" class="btn btn-primary addNewInstitute" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
