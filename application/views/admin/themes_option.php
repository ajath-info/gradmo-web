<section class="edu_admin_content">
   <?php 
      $role = $this->session->userdata('role'); 
       $admin_themes = json_decode($theme_color[0]['admin_themes'],true);
       $teacher_themes = json_decode($theme_color[0]['teacher_themes'],true);
       $student_themes = json_decode($theme_color[0]['student_themes'],true);
      //   print_r($teacher_themes);
      //     die
      ?>
   <div class="edu_admin_right sectionHolder edu_question_manager">
      <div class="edu_admin_right sectionHolder edu_home_setting_wrapper">
         <div class="edu_admin_informationdiv edu_main_wrapper edu_main_wrapperRES">
            <div class="container-fluid">
               <div class="row">
                  <div class="col-lg-12">
                     <div class="edu_color_option_main_heading">
                        <h2><?php echo html_escape($this->common->languageTranslator('ltr_theme_coloroption'));?></h2>
                     </div>
                  </div>
                  <form class="themesColor">
                     <!--Admin field color option-->
                     <div class="col-lg-3 col-md-6 col-sm-12 col-12 edu_bottom_20">
                        <div class="edu_color_option_bg">
                           <div class="edu_theme_color_heading">
                              <h2><?php echo html_escape($this->common->languageTranslator('ltr_admin_dashboard'));?></h2>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_primary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  id="primaryColor" value="<?=$admin_themes['admin_primary'];?>" name="admin_primary"/>
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_secondary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$admin_themes['admin_secondary'];?>" name="admin_secondary"id="togglePaletteOnly1" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_delete_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$admin_themes['admin_accent'];?>" name="admin_accent" id="accentColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_alternate_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$admin_themes['admin_text'];?>" name="admin_text" id="textColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_active_button'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$admin_themes['admin_alternate'];?>" name="admin_alternate" id="alternateTextColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                <h5><?php echo html_escape($this->common->languageTranslator('ltr_edit_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$admin_themes['admin_header'];?>" name="admin_header"id="headerBgColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                        </div>
                     </div>
                     <!--Teacher field color option-->
                     <div class="col-lg-3 col-md-6 col-sm-12 col-12 edu_bottom_20">
                        <div class="edu_color_option_bg">
                           <div class="edu_theme_color_heading">
                              <h2><?php echo html_escape($this->common->languageTranslator('ltr_teacher_dashboard'));?></h2>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_primary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$teacher_themes['teacher_primary'];?>" name="teacher_primary"id="TeacherprimaryColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_secondary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$teacher_themes['teacher_secondary'];?>" name="teacher_secondary"id="TeachertogglePaletteOnly1" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_delete_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$teacher_themes['teacher_accent'];?>" name="teacher_accent"id="TeacheraccentColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_alternate_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$teacher_themes['teacher_text'];?>" name="teacher_text"id="TeachertextColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_active_button'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$teacher_themes['teacher_alternate'];?>" name="teacher_alternate"id="TeacheralternateTextColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_edit_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$teacher_themes['teacher_header'];?>" name="teacher_header"id="TeacherheaderBgColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                        </div>
                     </div>
                     <!---->
                     <!--student field color option-->
                     <div class="col-lg-3 col-md-6 col-sm-12 col-12 edu_bottom_20">
                        <div class="edu_color_option_bg">
                           <div class="edu_theme_color_heading">
                              <h2><?php echo html_escape($this->common->languageTranslator('ltr_student_dashboard'));?></h2>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_primary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$student_themes['student_primary'];?>" name="student_primary"id="StudentprimaryColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_secondary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$student_themes['student_secondary'];?>" name="student_secondary"id="StudenttogglePaletteOnly1" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_delete_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$student_themes['student_accent'];?>" name="student_accent"id="StudentaccentColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_alternate_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion "  value="<?=$student_themes['student_text'];?>" name="student_text"id="StudenttextColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_active_button'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion " value="<?=$student_themes['student_alternate'];?>" name="student_alternate"id="StudentalternateTextColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_edit_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <input type='text' class="themesotion " value="<?=$student_themes['student_header'];?>" name="student_header"id="StudentheaderBgColor" />
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-3 col-md-6 col-sm-12 col-12 edu_bottom_20">
                        <div class="edu_color_option_bg">
                           <div class="edu_theme_color_heading">
                              <h2><?php echo html_escape($this->common->languageTranslator('ltr_frontend_Web'));?></h2>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_primary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <span class="sp-original-input-container" style="margin: 0px; display: flex;">
                                    <div class="sp-colorize-container sp-add-on" style="width: 40px; border-radius: 0px; border: 0px none rgb(0, 0, 0);">
                                       <div class="sp-colorize" style="background-color: rgb(77, 74, 129); color: rgb(255, 255, 255);"></div>
                                    </div>
                                    <input type="text" class="themesotion spectrum with-add-on" value="rgb(103, 78, 167)" name="frontend_primaryColor" id="frontendprimaryColor">
                                 </span>
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_secondary_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <span class="sp-original-input-container" style="margin: 0px; display: flex;">
                                    <div class="sp-colorize-container sp-add-on" style="width: 40px; border-radius: 0px; border: 0px none rgb(0, 0, 0);">
                                       <div class="sp-colorize" style="background-color: rgb(247, 247, 251); color: rgb(0, 0, 0);"></div>
                                    </div>
                                    <input type="text" class="themesotion spectrum with-add-on" value="rgb(255, 255, 255)" name="frontend_togglePaletteOnly1" id="frontendtogglePaletteOnly1">
                                 </span>
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_delete_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <span class="sp-original-input-container" style="margin: 0px; display: flex;">
                                    <div class="sp-colorize-container sp-add-on" style="width: 40px; border-radius: 0px; border: 0px none rgb(0, 0, 0);">
                                       <div class="sp-colorize" style="background-color: rgb(246, 45, 81); color: rgb(255, 255, 255);"></div>
                                    </div>
                                    <input type="text" class="themesotion spectrum with-add-on" value="rgb(238, 73, 73)" name="frontend_accentColor" id="frontendaccentColor">
                                 </span>
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_alternate_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <span class="sp-original-input-container" style="margin: 0px; display: flex;">
                                    <div class="sp-colorize-container sp-add-on" style="width: 40px; border-radius: 0px; border: 0px none rgb(0, 0, 0);">
                                       <div class="sp-colorize" style="background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);"></div>
                                    </div>
                                    <input type="text" class="themesotion spectrum with-add-on" value="rgb(255, 255, 255)" name="frontend_textColor" id="frontendtextColor">
                                 </span>
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_active_button'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <span class="sp-original-input-container" style="margin: 0px; display: flex;">
                                    <div class="sp-colorize-container sp-add-on" style="width: 40px; border-radius: 0px; border: 0px none rgb(0, 0, 0);">
                                       <div class="sp-colorize" style="background-color: rgb(58, 192, 218); color: rgb(0, 0, 0);"></div>
                                    </div>
                                    <input type="text" class="themesotion spectrum with-add-on" value="rgb(13, 210, 210)" name="frontend_alternateTextColor" id="frontendalternateTextColor">
                                 </span>
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                           <div class="edu_color_picker1 edu_color_picker_class">
                              <div class="edu_color_piceker_text">
                                 <h5><?php echo html_escape($this->common->languageTranslator('ltr_edit_icon_color'));?></h5>
                              </div>
                              <div class="edu_color_picker_color">
                                 <span class="sp-original-input-container" style="margin: 0px; display: flex;">
                                    <div class="sp-colorize-container sp-add-on" style="width: 40px; border-radius: 0px; border: 0px none rgb(0, 0, 0);">
                                       <div class="sp-colorize" style="background-color: rgb(95, 197, 255); color: rgb(0, 0, 0);"></div>
                                    </div>
                                    <input type="text" class="themesotion spectrum with-add-on" value="#5fc5ff" name="frontend_BgColorid" id="frontendBgColor">
                                 </span>
                                 <p class="edu_check_color"></p>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-12 col-md-12 col-sm-12 col-12 edu_bottom_20">
                        <div class="edu_color_option_bg">
                           <div class="edu_theme_color_heading">
                              <h2><?php echo html_escape($this->common->languageTranslator('ltr_login_signup_theme_option'));?></h2>
                           </div>
                           <div class="col-lg-12 col-md-6 col-sm-12 col-12 edu_bottom_20 padding_none">
                              <div class="edu_color_option_bg padding_none row">
                                  <div class="col-lg-4 col-md-6 col-sm-12">
                                      <div class="edu_color_picker1 edu_color_picker_class">
                                        <div class="edu_color_piceker_text">
                                           <h5><?php echo html_escape($this->common->languageTranslator('ltr_primary_color'));?></h5>
                                        </div>
                                        <div class="edu_color_picker_color">
                                           <input type='text' class="themesotion "  id="login_primaryColor" value="<?=$admin_themes['login_primary'];?>" name="login_primary"/>
                                           <p class="edu_check_color"></p>
                                        </div>
                                     </div>
                                  </div>
                                  <div class="col-lg-4 col-md-6 col-sm-12">
                                      <div class="edu_color_picker1 edu_color_picker_class">
                                        <div class="edu_color_piceker_text">
                                           <h5><?php echo html_escape($this->common->languageTranslator('ltr_secondary_color'));?></h5>
                                        </div>
                                        <div class="edu_color_picker_color">
                                           <input type='text' class="themesotion "  value="<?=$login_themes['login_secondary'];?>" name="login_secondary"id="login_togglePaletteOnly1" />
                                           <p class="edu_check_color"></p>
                                        </div>
                                     </div>
                                  </div>
                                  <div class="col-lg-4 col-md-6 col-sm-12">
                                      <div class="edu_color_picker1 edu_color_picker_class">
                                        <div class="edu_color_piceker_text">
                                           <h5><?php echo html_escape($this->common->languageTranslator('ltr_alternate_color'));?></h5>
                                        </div>
                                        <div class="edu_color_picker_color">
                                           <input type='text' class="themesotion "  value="<?=$login_themes['login_accentColor'];?>" name="login_accentColor"id="login_accentColor" />
                                           <p class="edu_check_color"></p>
                                        </div>
                                     </div>
                                  </div>
                                 
                                 
                                 
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-12">
                        <div class="edu_theme_option_btn">
                           <div class="d-none"><input type='text' hidden class="themesotion ed_themesotion01 fdff" value="<?=$theme_color[0]['id'];?>" name="themesid"id="StudentheaderBgColor" /></div>
                           <a href="javascript:;" class="edu_admin_btn themesColorAdd">Submit</a>
                        </div>
                     </div>
                     <!---->
                  </form>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>