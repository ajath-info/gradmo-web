<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder edu_teacher_manager">
	    <div class="edu_btn_wrapper sectionHolder padderBottom30 text-right">
            <a href="javascript:void(0);" class="edu_admin_btn ml-2 addInstitutePop"><i class="icofont-plus"></i><?php echo html_escape($this->common->languageTranslator('ltr_Intitute_add'));?></a>
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
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_contact_no'));?></th>
                                <th>Institute code</th>
                                <th>School / college</th>
                                <th>Grade</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>City</th>
                                <th>Pincode</th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_address'));?></th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th><?php echo html_escape($this->common->languageTranslator('ltr_status'));?></th>
                                <th class="no-sort"><?php echo html_escape($this->common->languageTranslator('ltr_action'));?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
			</div>
		</div>
	</div>
</section>

<div id="input_feilds_institute" class="edu_popup_container institute_manage_popup mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner">
            <h4 class="edu_sub_title" id="PopupTitle"><?php echo html_escape($this->common->languageTranslator('ltr_Intitute_add'));?></h4>
            <form class="pxn_amin form" id="form_institute_manage" action="" method="post" autocomplete="off">
                <div class="row">   
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_name'));?><sup>*</sup></label>
                            <input type="text" class="form-control require alphaField" name="name" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_name'));?>">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_last_name'));?></label>
                            <input type="text" class="form-control alphaField" name="last_name" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_last_name'));?>">
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
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_contact_no'));?><sup>*</sup></label>
                            <input type="text" class="form-control require" name="contact_no" maxlength="15" placeholder="<?php echo html_escape($this->common->languageTranslator('ltr_contact_no'));?>" data-valid="mobile" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_contact_msg'));?>">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Institute code</label>
                            <input type="text" class="form-control" name="institute_code" placeholder="Institute code">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>School / college name</label>
                            <input type="text" class="form-control" name="school_college_name" placeholder="School or college name">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Grade</label>
                            <input type="text" class="form-control" name="grade" placeholder="Grade">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_image'));?></label>
                            <input type="file" class="form-control" name="teach_image" data-valid="image" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_image_msg'));?>">
                            <p class="fileNameShow"></p>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Banner<sup>*</sup></label>
                            <input type="file" class="form-control require" name="banner" data-valid="image" data-error="<?php echo html_escape($this->common->languageTranslator('ltr_valid_image_msg'));?>">
                            <p class="bannerNameShow"></p>
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
                            <select id="institute_sel_country" class="form-control require edu_selectbox_without_search" data-placeholder="Select country">
                                <option value="">Select country</option>
                            </select>
                            <input type="hidden" name="country" id="institute_inp_country" value="">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>State<sup>*</sup></label>
                            <select id="institute_sel_state" class="form-control require edu_selectbox_without_search" data-placeholder="Select state">
                                <option value="">Select state</option>
                            </select>
                            <input type="hidden" name="state" id="institute_inp_state" value="">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>City<sup>*</sup></label>
                            <select id="institute_sel_city" class="form-control require edu_selectbox_without_search" data-placeholder="Select city">
                                <option value="">Select city</option>
                            </select>
                            <input type="hidden" name="city" id="institute_inp_city" value="">
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
                            <input type="text" class="form-control" name="lat" placeholder="Latitude">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" class="form-control" name="long" placeholder="Longitude">
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="form-group eb_batchtype">
                            <label><?php echo html_escape($this->common->languageTranslator('ltr_module_access'));?><sup>*</sup></label><br>
                            <div class="form-control radio-btn-space adminAccessControlDiv">
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_batch_manager'));?>
                                    <input type="checkbox" class="batch" name="batch" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_teacher_manager'));?>
                                    <input type="checkbox" class="teacher_manager" name="teacher_manager" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_academics'));?>
                                    <input type="checkbox" class="academics" name="academics" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_homework'));?>
                                    <input type="checkbox" class="assignment" name="assignment" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_live_class'));?>
                                    <input type="checkbox" class="live_class" name="live_class" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_notice'));?>
                                    <input type="checkbox" class="notice" name="notice" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_extra_classes'));?>
                                    <input type="checkbox" class="extraclasses" name="extraclasses" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_library_manager'));?>
                                    <input type="checkbox" class="library_manager" name="library_manager" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_question_manager'));?>
                                    <input type="checkbox" class="question_manager" name="question_manager" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_video_lecture_manager'));?>
                                    <input type="checkbox" class="video_lecture" name="video_lecture" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_doubts_ask'));?>
                                    <input type="checkbox" class="doubtsask" name="doubtsask" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_exam'));?>
                                    <input type="checkbox" class="exam" name="exam" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_manage_student_leave'));?>
                                    <input type="checkbox" class="student_leave" name="student_leave" value="1"></label>
                                <label class="AdminAccess"><?php echo html_escape($this->common->languageTranslator('ltr_student_details'));?>
                                    <input type="checkbox" class="student_manage" name="student_manage" value="1"></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12 institute_popup_footer">
                        <div class="edu_btn_wrapper">
                            <button type="button" class="btn btn-primary addNewInstitute" data-btn-add="<?php echo html_escape($this->common->languageTranslator('ltr_Intitute_add'));?>" data-btn-update="<?php echo html_escape($this->common->languageTranslator('ltr_update_institute'));?>"><?php echo html_escape($this->common->languageTranslator('ltr_Intitute_add'));?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
