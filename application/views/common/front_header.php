<!DOCTYPE html>
<html <?php if($this->common->language_name=='arabic'){echo 'lang="ar" dir="rtl"';}else if($this->common->language_name=='french'){echo 'lang="fr" dir="ltr"';}else if($this->common->language_name=='english'){echo 'lang="en" dir="ltr"';} ?> >
<!-- Begin Head -->
  <head>
    <!----- Required MetaTags ----->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="keywords" content="<?php echo html_escape($this->common->siteKeywords); ?>">
    <meta name="description" content="<?php echo html_escape($this->common->siteDescription); ?>">
    <meta name="author" content="<?php echo html_escape($this->common->siteAuthorName); ?>">
    <!----- Style css ----->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/animate.css">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/fontawesome.min.css">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/icofont.css">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/font.css">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/magnific-popup.css">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/swiper.min.css">
	<link rel="stylesheet" type="text/css" href="<?php echo base_url().'assets/css/toastr.min.css';?>"/>
	<?php
		$_fa_ver = trim((string) $this->config->item('frontend_asset_version'));
		$_fa_q = ($_fa_ver !== '') ? ('?v=' . rawurlencode($_fa_ver)) : '';
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/frontend-rtl.css<?php echo $_fa_q; ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo base_url();?>assets/css/frontend-style.css<?php echo $_fa_q; ?>">
    
	<!----- Favicon ----->
	<link rel="shortcut icon" type="image/ico" href="<?php echo html_escape($this->common->siteFavicon); ?>" />
	<?php 
    $colorsData =$this->common->theme_colors;
    $admin_themes = json_decode($colorsData[0]['admin_themes'],true);
    $teacher_themes = json_decode($colorsData[0]['teacher_themes'],true);
    $student_themes = json_decode($colorsData[0]['student_themes'],true);
    $frontend_themes = json_decode($colorsData[0]['frontend_themes'],true);
    // print_r($frontend_themes);die;
    ?>
    <style>
    :root {
    --Primary-Color:<?php echo isset($frontend_themes['frontend_primary'])?$frontend_themes['frontend_primary']:'#4d4a81';?>;
    --Secondary-Color:<?php echo isset($frontend_themes['frontend_secondary'])?$frontend_themes['frontend_secondary']:'#f7f7fb';?> ;
    --delete-Color:<?php echo isset($frontend_themes['frontend_accent'])?$frontend_themes['frontend_accent']:'#f62d51';?> ;
    --Alternate-Text-Color:<?php echo isset($frontend_themes['frontend_text'])?$frontend_themes['frontend_text']:'#ffffff';?> ;
    --Active-icon-Color:<?php echo isset($frontend_themes['frontend_alternate'])?$frontend_themes['frontend_alternate']:'#3ac0da';?>;
     --edit-icon-Color:<?php echo isset($frontend_themes['frontend_header'])?$frontend_themes['frontend_header']:'#5fc5ff';?>;
     }
    </style>
	<!----- Title ----->
    <title><?php echo html_escape($this->common->siteTitle).((isset($title) && !empty($title)) ? ' | '.$title:'');?></title>
    
	<script>
		var base_url = "<?php echo base_url();?>";
		var site_logo = "<?php echo base_url();?>assets/images/favicon.png";
		var rzp_key ="<?php echo $this->common->rzp_key ?>";
        var ltr_status_msg = "<?php echo html_escape($this->common->languageTranslator('ltr_status_msg')); ?>";
		var ltr_matching_msg = "<?php echo html_escape($this->common->languageTranslator('ltr_matching_msg')); ?>";
		var ltr_select_chapter ="<?php echo html_escape($this->common->languageTranslator('ltr_select_chapter')); ?>";
		var ltr_select_subject ="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject')); ?>";
		var ltr_subject ="<?php echo html_escape($this->common->languageTranslator('ltr_subject')); ?>";
		var ltr_teacher ="<?php echo html_escape($this->common->languageTranslator('ltr_teacher')); ?>";
		var ltr_select_teacher ="<?php echo html_escape($this->common->languageTranslator('ltr_select_teacher')); ?>";
		var ltr_start_date ="<?php echo html_escape($this->common->languageTranslator('ltr_start_date')); ?>";
		var ltr_end_date ="<?php echo html_escape($this->common->languageTranslator('ltr_end_date')); ?>";
		var ltr_start_time ="<?php echo html_escape($this->common->languageTranslator('ltr_start_time')); ?>";
		var ltr_end_time ="<?php echo html_escape($this->common->languageTranslator('ltr_end_time')); ?>";
        var ltr_cant_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_cant_msg')); ?>";
        var ltr_are_you_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_are_you_msg')); ?>";
        var ltr_add_course ="<?php echo html_escape($this->common->languageTranslator('ltr_add_course')); ?>";
        var ltr_edit_course ="<?php echo html_escape($this->common->languageTranslator('ltr_edit_course')); ?>";
        var ltr_add_doubts_date_class ="<?php echo html_escape($this->common->languageTranslator('ltr_add_doubts_date_class')); ?>";
        var ltr_add_new_exam ="<?php echo html_escape($this->common->languageTranslator('ltr_add_new_exam')); ?>";
        var ltr_end_date_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_end_date_msg')); ?>";
        var ltr_subject_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_subject_msg')); ?>";
        var ltr_characters_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_characters_msg')); ?>";
        var ltr_password_student_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_password_student_msg')); ?>";
        var ltr_enrollment_id ="<?php echo html_escape($this->common->languageTranslator('ltr_enrollment_id')); ?>";
        var ltr_password ="<?php echo html_escape($this->common->languageTranslator('ltr_password')); ?>";
        var ltr_add_another_student ="<?php echo html_escape($this->common->languageTranslator('ltr_add_another_student')); ?>";
        var ltr_select_batch_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_batch_msg')); ?>";
		var ltr_select_batch ="<?php echo html_escape($this->common->languageTranslator('ltr_select_batch')); ?>";
        var ltr_changed_batch_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_changed_batch_msg')); ?>";
        var ltr_changed_password_for_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_changed_password_for_msg')); ?>";
        var ltr_confirm_password_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_confirm_password_msg')); ?>";
        var ltr_password_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_password_msg')); ?>";
        var ltr_subject_name_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_subject_name_msg')); ?>";
        var ltr_letters_characters_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_letters_characters_msg')); ?>";
        var ltr_subject_updated_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_subject_updated_msg')); ?>";
        var ltr_subject_add_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_subject_add_msg')); ?>";
        var ltr_subject_exists_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_subject_exists_msg')); ?>";
        var ltr_are_you_so_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_are_you_so_msg')); ?>";
        var ltr_subject_delete_alert_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_subject_delete_alert_msg')); ?>";
        var ltr_atleast_chapter_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_atleast_chapter_msg')); ?>";
        var ltr_add_chapter_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_add_chapter_msg')); ?>";
        var ltr_exists_chapter_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_exists_chapter_msg')); ?>";
        var ltr_chapter_name_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_chapter_name_msg')); ?>";
        var ltr_chapter_updated_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_chapter_updated_msg')); ?>";
        var ltr_chapter_delete_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_chapter_delete_msg')); ?>";
        var ltr_loading_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_loading_msg')); ?>";
        var ltr_select_subject_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject_msg')); ?>";
        var ltr_select_subject_both_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_subject_both_msg')); ?>";
        var ltr_word_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_word_msg')); ?>";
        var ltr_answer_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_answer_msg')); ?>";
        var ltr_start_date_greater_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_start_date_greater_msg')); ?>";
        var ltr_add_teacher ="<?php echo html_escape($this->common->languageTranslator('ltr_add_teacher')); ?>";
        var ltr_edit_teacher ="<?php echo html_escape($this->common->languageTranslator('ltr_edit_teacher')); ?>";
        var ltr_update_teacher ="<?php echo html_escape($this->common->languageTranslator('ltr_update_teacher')); ?>";
        var ltr_add_extra_class ="<?php echo html_escape($this->common->languageTranslator('ltr_add_extra_class')); ?>";
        var ltr_add_class ="<?php echo html_escape($this->common->languageTranslator('ltr_add_class')); ?>";
        var ltr_edit_extra_class ="<?php echo html_escape($this->common->languageTranslator('ltr_edit_extra_class')); ?>";
        var ltr_update_class ="<?php echo html_escape($this->common->languageTranslator('ltr_update_class')); ?>";
        var ltr_past_time_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_past_time_msg')); ?>";
        var ltr_end_greater_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_end_greater_msg')); ?>";
        var ltr_today_greater_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_today_greater_msg')); ?>";
        var ltr_class_already_added_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_class_already_added_msg')); ?>";
        var ltr_valid_time_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_valid_time_msg')); ?>";
        var ltr_select_date_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_date_msg')); ?>";
        var ltr_atleast_question_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_atleast_question_msg')); ?>";
        var ltr_select_year_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_year_msg')); ?>";
        var ltr_select_paper_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_paper_msg')); ?>";
        var ltr_add_facility ="<?php echo html_escape($this->common->languageTranslator('ltr_add_facility')); ?>";
        var ltr_edit_facility ="<?php echo html_escape($this->common->languageTranslator('ltr_edit_facility')); ?>";
        var ltr_add_assignment ="<?php echo html_escape($this->common->languageTranslator('ltr_add_assignment')); ?>";
        var ltr_edit_assignment ="<?php echo html_escape($this->common->languageTranslator('ltr_edit_assignment')); ?>";
        var ltr_update_assignment ="<?php echo html_escape($this->common->languageTranslator('ltr_update_assignment')); ?>";
        var ltr_select_from_date_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_from_date_msg')); ?>";
        var ltr_select_to_date_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_select_to_date_msg')); ?>";
        var ltr_batch_inactive_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_batch_inactive_msg')); ?>";
        var ltr_mark_complete ="<?php echo html_escape($this->common->languageTranslator('ltr_mark_complete')); ?>";
        var ltr_complete ="<?php echo html_escape($this->common->languageTranslator('ltr_complete')); ?>";
        var ltr_all_fields_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_all_fields_msg')); ?>";
        var ltr_hide_password ="<?php echo html_escape($this->common->languageTranslator('ltr_hide_password')); ?>";
        var ltr_change_password ="<?php echo html_escape($this->common->languageTranslator('ltr_change_password')); ?>";
        var ltr_new_password_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_new_password_msg')); ?>";
        var ltr_all_test_record_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_all_test_record_msg')); ?>";
        var ltr_once_deleted_alert_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_once_deleted_alert_msg')); ?>";
        var ltr_are_deleted_alert_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_are_deleted_alert_msg')); ?>";
        var ltr_updated_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_updated_msg')); ?>";
        var ltr_alert_updated_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_alert_updated_msg')); ?>";
        var ltr_category_changed_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_category_changed_msg')); ?>";
        var ltr_invalid_birth_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_invalid_birth_msg')); ?>";
        var ltr_to_greater_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_to_greater_msg')); ?>";
        var ltr_message ="<?php echo html_escape($this->common->languageTranslator('ltr_message')); ?>";
        var ltr_add_live_class ="<?php echo html_escape($this->common->languageTranslator('ltr_add_live_class')); ?>";
        var ltr_edit_live_class ="<?php echo html_escape($this->common->languageTranslator('ltr_edit_live_class')); ?>";
        var ltr_atleast_student_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_atleast_student_msg')); ?>";
        var ltr_atleast_date_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_atleast_date_msg')); ?>";
        var ltr_maximum40_characters_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_maximum40_characters_msg')); ?>";
        var ltr_maximum50_characters_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_maximum50_characters_msg')); ?>";
        var ltr_double_class_date_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_double_class_date_msg')); ?>";
		var ltr_ok ="<?php echo html_escape($this->common->languageTranslator('ltr_ok')); ?>";
		var ltr_cancel ="<?php echo html_escape($this->common->languageTranslator('ltr_cancel')); ?>";
		var ltr_select_student ="<?php echo html_escape($this->common->languageTranslator('ltr_select_student')); ?>";
		var ltr_description ="<?php echo html_escape($this->common->languageTranslator('ltr_description')); ?>";
		var ltr_can_remove_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_can_remove_msg')); ?>";
		var ltr_some_required ="<?php echo html_escape($this->common->languageTranslator('ltr_some_required')); ?>";
        var ltr_only_letters_msg ="<?php echo html_escape($this->common->languageTranslator('ltr_only_letters_msg')); ?>";
        var ltr_search ="<?php echo html_escape($this->common->languageTranslator('ltr_search')); ?>";
        var ltr_show ="<?php echo html_escape($this->common->languageTranslator('ltr_show')); ?>";
        var ltr_heading  ="<?php echo html_escape($this->common->languageTranslator('ltr_heading')); ?>";
        var ltr_sub_heading  ="<?php echo html_escape($this->common->languageTranslator('ltr_sub_heading')); ?>";
        var ltr_batch_speci_heading  ="<?php echo html_escape($this->common->languageTranslator('ltr_batch_speci_heading')); ?>";
        var ltr_fecherd  ="<?php echo html_escape($this->common->languageTranslator('ltr_fecherd')); ?>";
        var ltr_email  ="<?php echo html_escape($this->common->languageTranslator('ltr_email')); ?>";
        var ltr_wrong_credentials_msg  ="<?php echo html_escape($this->common->languageTranslator('ltr_wrong_credentials_msg')); ?>";
        var ltr_benefit  ="<?php echo html_escape($this->common->languageTranslator('ltr_benefit')); ?>";
        var ltr_batch_spe_msg  ="<?php echo html_escape($this->common->languageTranslator('ltr_batch_spe_msg')); ?>";
        var ltr_you_delete  ="<?php echo html_escape($this->common->languageTranslator('ltr_you_delete')); ?>";
        var ltr_i_learn  ="<?php echo html_escape($this->common->languageTranslator('ltr_i_learn')); ?>";
        var ltr_chapters  ="<?php echo html_escape($this->common->languageTranslator('ltr_chapters')); ?>";
        var ltr_offer_price_msg  ="<?php echo html_escape($this->common->languageTranslator('ltr_offer_price_msg')); ?>";
        var ltr_batch_price_msg  ="<?php echo html_escape($this->common->languageTranslator('ltr_batch_price_msg')); ?>";
        var ltr_payment_msg  ="<?php echo html_escape($this->common->languageTranslator('ltr_payment_msg')); ?>";
        var ltr_something_msg  ="<?php echo html_escape($this->common->languageTranslator('ltr_something_msg')); ?>";
        var ltr_enter_your_name="<?php echo html_escape($this->common->languageTranslator('ltr_enter_your_name')); ?>";
        var ltr_enter_your_email="<?php echo html_escape($this->common->languageTranslator('ltr_enter_your_email')); ?>";
        var ltr_valid_enter_your_email="<?php echo html_escape($this->common->languageTranslator('ltr_valid_enter_your_email')); ?>";
        var ltr_enter_your_phone="<?php echo html_escape($this->common->languageTranslator('ltr_enter_your_phone')); ?>";
        var ltr_valid_enter_your_phone="<?php echo html_escape($this->common->languageTranslator('ltr_valid_enter_your_phone')); ?>";
        var ltr_enter_your_message="<?php echo html_escape($this->common->languageTranslator('ltr_enter_your_message')); ?>";
        var ltr_send="<?php echo html_escape($this->common->languageTranslator('ltr_send')); ?>";
        var ltr_no_result="<?php echo html_escape($this->common->languageTranslator('ltr_no_result')); ?>";

	</script>
  </head>
  <body class="<?php if($this->common->language_name=='arabic'){ echo 'rtl' ;}?>">
	<!----- Preloader Box ----->
	<div class="edu_preloader">
		<div class="edu_status">
			<img src="<?php echo html_escape($this->common->siteLoader); ?>" alt="loader">
		</div>
	</div>
	<!----- Preloader Box ----->
	<?php
		$front_logo_src = trim((string) $this->common->siteminiLogo);
		if ($front_logo_src === '') {
			$front_logo_src = base_url('assets/images/logo.png');
		}
	?>
	<!----- Main Wraapper ----->
	<section class="main_wrapper">
		<!----- Header Start ----->
		<header class="edu-header-gradmo">
			<div class="edu_header_top edu_header_top1">
				<div class="container-fluid">
					<div class="row align-items-center">
						<div class="col-xl-2 col-lg-2 col-md-4 col-sm-6 col-6">
							<div class="edu_logo">
								<a href="<?php echo base_url();?>"><img class="front_logo" src="<?php echo html_escape($front_logo_src); ?>" alt="logo" /></a>
							</div>
						</div>
						<div class="col-xl-8 col-lg-8 d-none d-lg-block">
							<div class="edu_main_menu main_menu_parent">
								<div class="edu_nav_items main_menu_wrapper text-left">
									<?php $this->load->view('common/partials/front_primary_nav_ul'); ?>
								</div>
							</div>
						</div>
						<div class="col-xl-2 col-lg-2 col-md-8 col-sm-6 col-6">
							<div class="edu_header_info edu_header_info--with-mobile-toggle">
								<div class="menu_btn_wrap d-lg-none">
									<a href="javascript:void(0);" class="menu_btn" id="frontMobileNavToggle" aria-label="Open menu" aria-expanded="false" aria-controls="eduFrontMobileNavDrawer"><span></span><span></span><span></span></a>
								</div>
								<ul>
									<li>
									  <?php if(!empty($_SESSION['role'])){ ?>
										<?php
											$front_user_name = trim((string) $this->session->userdata('name'));
											if ($front_user_name === '') {
												$front_user_name = 'User';
											}
											$front_api_ut = strtolower(trim((string) $this->session->userdata('api_user_type')));
											$front_role_raw = $this->session->userdata('role');
											$front_role_s = strtolower(trim((string) $front_role_raw));
											$is_front_student = ($front_api_ut === 'student' || $front_role_s === 'student' || $front_role_raw === 2 || $front_role_raw === '2');
											$is_front_teacher = ($front_api_ut === 'teacher' || $front_role_s === 'teacher' || $front_role_raw === 3 || $front_role_raw === '3');
											$front_my_institutes_url = base_url('institute/mylist');
											$front_my_batches_url = base_url('batch/list');
											if ($is_front_student || $is_front_teacher) {
												$front_my_batches_url = base_url('batch/mylist');
											    $front_my_institutes_url = base_url('institute/mylist');
                                            }
											$front_notifications_url = base_url('notifications');
											$front_profile_img = trim((string) $this->session->userdata('profile_img'));
											$front_avatar_url = ($front_profile_img !== '') ? profile_image_url($front_profile_img, $front_role_raw, $front_api_ut) : '';
											$front_api_token = trim((string) $this->session->userdata('access_token'));
										?>
										<?php if ($front_api_token !== '' && ($is_front_student || $is_front_teacher)) { ?>
										<style>
											.front-bell-wrap{position:relative;display:inline-block;margin-right:16px;vertical-align:middle;}
											.front-bell-trigger{position:relative;color:#fff;font-size:19px;line-height:1;background:none;border:0;cursor:pointer;padding:6px;}
											.front-bell-badge{position:absolute;top:-3px;right:-3px;min-width:17px;height:17px;padding:0 4px;border-radius:9px;background:#e53935;color:#fff;font-size:10px;font-weight:700;line-height:17px;text-align:center;display:none;box-shadow:0 0 0 2px rgba(255,255,255,.6);}
											.front-bell-panel{position:absolute;right:0;top:135%;width:360px;max-width:90vw;background:#fff;border-radius:12px;box-shadow:0 12px 34px rgba(0,0,0,.20);z-index:1050;display:none;overflow:hidden;}
											.front-bell-panel.open{display:block;}
											.front-bell-head{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #eee;gap:10px;}
											.front-bell-head .ttl{font-weight:700;color:#1a1a1a;font-size:15px;}
											.front-bell-head .acts{display:flex;gap:12px;}
											.front-bell-head .acts a{font-size:12px;cursor:pointer;font-weight:600;}
											.front-bell-head .acts a.read{color:#3787FF;}
											.front-bell-head .acts a.clear{color:#e53935;}
											.front-bell-list{max-height:400px;overflow-y:auto;padding:6px;}
											.front-bell-item{position:relative;display:flex;gap:11px;align-items:flex-start;padding:11px 34px 11px 12px;margin:4px 2px;border-radius:10px;cursor:pointer;transition:background .15s,box-shadow .15s;}
											.front-bell-item .ic{flex:0 0 auto;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e9efff;color:#3787FF;font-size:14px;margin-top:1px;}
											.front-bell-item .bd{flex:1 1 auto;min-width:0;}
											.front-bell-item .t{font-weight:700;font-size:13px;margin:0 0 3px;color:#1a1a1a;padding-right:6px;}
											.front-bell-item .m{font-size:12px;color:#555;margin:0 0 4px;line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
											.front-bell-item .d{font-size:11px;color:#9aa0a6;}
											.front-bell-item .cl{position:absolute;top:8px;right:8px;width:22px;height:22px;line-height:1;border:0;background:none;color:#c2c7cc;font-size:16px;cursor:pointer;border-radius:6px;opacity:0;transition:opacity .12s,background .12s,color .12s;}
											.front-bell-item:hover .cl{opacity:1;}
											.front-bell-item .cl:hover{background:#fdeaea;color:#e53935;}
											/* Unread: light blue, blue accent bar + dot. Read: plain, muted. */
											.front-bell-item.is-unread{background:#eef4ff;}
											.front-bell-item.is-unread::before{content:"";position:absolute;left:3px;top:12px;bottom:12px;width:3px;border-radius:3px;background:#3787FF;}
											.front-bell-item.is-unread .t{color:#0b3d91;}
											.front-bell-item.is-unread .t::after{content:"";display:inline-block;width:7px;height:7px;border-radius:50%;background:#3787FF;margin-left:6px;vertical-align:middle;}
											.front-bell-item.is-read{background:#fff;}
											.front-bell-item.is-read .ic{background:#f2f3f5;color:#9aa0a6;}
											.front-bell-item.is-read .t{color:#5a5f66;font-weight:600;}
											.front-bell-item:hover{background:#e3ecff;}
											.front-bell-item.is-read:hover{background:#f5f7fa;}
											.front-bell-empty{padding:26px 16px;text-align:center;color:#9aa0a6;font-size:13px;}
											.front-bell-foot{padding:11px 16px;text-align:center;border-top:1px solid #eee;}
											.front-bell-foot a{font-size:13px;color:#3787FF;cursor:pointer;font-weight:600;}
										</style>
										<div class="front-bell-wrap" id="frontBellWrap">
											<button type="button" class="front-bell-trigger" id="frontBellBtn" aria-label="Notifications">
												<i class="fas fa-bell"></i>
												<span class="front-bell-badge" id="frontBellBadge">0</span>
											</button>
											<div class="front-bell-panel" id="frontBellPanel">
												<div class="front-bell-head">
													<span class="ttl">Notifications</span>
													<span class="acts">
														<a class="read" id="frontBellReadAll">Read All</a>
														<a class="clear" id="frontBellClearAll">Clear All</a>
													</span>
												</div>
												<div class="front-bell-list" id="frontBellList"></div>
												<div class="front-bell-foot"><a href="<?php echo html_escape($front_notifications_url); ?>">See all notifications</a></div>
											</div>
										</div>
										<script>
										(function () {
											var listUrl = <?php echo json_encode(site_url('api/main/notifications-list')); ?>;
											var readUrl = <?php echo json_encode(site_url('api/main/notifications-read')); ?>;
											var deleteUrl = <?php echo json_encode(site_url('api/main/notifications-delete')); ?>;
											var siteBase = <?php echo json_encode(base_url()); ?>;
											var token = <?php echo json_encode($front_api_token); ?>;
											// Notification urls are stored relative (e.g. "batch/live-room?..."). Resolve them against
											// the site root so a click never becomes /batch/batch/... based on the current page path.
											function absUrl(u){ u=String(u||''); if(u===''){ return ''; } if(/^https?:\/\//i.test(u)){ return u; } return siteBase.replace(/\/+$/,'/')+u.replace(/^\/+/,''); }
											var btn = document.getElementById('frontBellBtn');
											var panel = document.getElementById('frontBellPanel');
											var listEl = document.getElementById('frontBellList');
											var badge = document.getElementById('frontBellBadge');
											if (!btn) { return; }
											function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];});}
											function api(url, body){return fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify(body||{})}).then(function(r){return r.json();});}
											function prettyTitle(s){ s=String(s||'Notification').replace(/[_-]+/g,' ').trim(); return s.replace(/\w\S*/g,function(t){return t.charAt(0).toUpperCase()+t.substr(1).toLowerCase();}); }
											function isRead(it){ var r=it.read; return r===1||r==='1'||r===true; }
											function setBadge(n){ n=Math.max(0,n|0); if(n>0){badge.textContent=n>99?'99+':n;badge.style.display='block';}else{badge.style.display='none';} }
											var PAGE_SIZE=20, page=1, loading=false, hasMore=true;
											function showEmpty(){ listEl.innerHTML='<div class="front-bell-empty">No notifications</div>'; }
											function buildRows(rows){
												var h='';
												for(var i=0;i<rows.length;i++){
													var it=rows[i]; var read=isRead(it);
													var did=(it.detailId!=null)?it.detailId:'';
													h+='<div class="front-bell-item '+(read?'is-read':'is-unread')+'" data-detail="'+esc(did)+'" data-url="'+esc(absUrl(it.url))+'" data-read="'+(read?'1':'0')+'">'+
														'<span class="ic"><i class="fas fa-bell"></i></span>'+
														'<span class="bd">'+
															'<p class="t">'+esc(prettyTitle(it.notificationType||it.title))+'</p>'+
															'<p class="m">'+esc(it.msg||'')+'</p>'+
															'<p class="d">'+esc(it.time||'')+'</p>'+
														'</span>'+
														'<button type="button" class="cl" title="Clear">&times;</button>'+
													'</div>';
												}
												return h;
											}
											// reset=true: first page (replace). Otherwise fetch the next page and append at the bottom.
											function loadPage(reset){
												if(loading){ return; }
												if(reset){ page=1; hasMore=true; }
												if(!hasMore){ return; }
												loading=true;
												api(listUrl,{page:page,limit:PAGE_SIZE}).then(function(res){
													loading=false;
													var ok=res&&(res.status===true||res.status==='true');
													var rows=(ok&&Array.isArray(res.notifications))?res.notifications:[];
													if(reset){
														if(!rows.length){ showEmpty(); } else { listEl.innerHTML=buildRows(rows); }
													} else if(rows.length){
														listEl.insertAdjacentHTML('beforeend', buildRows(rows));
													}
													hasMore = rows.length === PAGE_SIZE; // a full page => there may be more
													if(hasMore){ page++; }
													if(reset){
														setBadge((ok && res.unreadCount!=null) ? res.unreadCount : rows.filter(function(x){return !isRead(x);}).length);
													}
												}).catch(function(){ loading=false; if(reset){ showEmpty(); } });
											}
											function load(){ loadPage(true); }
											// Infinite scroll: near the bottom of the list, pull the next page.
											listEl.addEventListener('scroll',function(){
												if(loading || !hasMore){ return; }
												if(listEl.scrollTop + listEl.clientHeight >= listEl.scrollHeight - 48){ loadPage(false); }
											});
											// Mark ONLY this row read in-place (do NOT remove it). Keys on the detail row id so
											// duplicates that share a master id are not all flipped.
											function markItemRead(item){
												if(!item || item.getAttribute('data-read')==='1'){ return false; }
												var did=item.getAttribute('data-detail');
												item.setAttribute('data-read','1');
												item.classList.remove('is-unread'); item.classList.add('is-read');
												if(did!==''){ api(readUrl,{detail_id:did}); }
												setBadge((parseInt(badge.textContent,10)||0)-1);
												return true;
											}
											// Header "clear" sets clear=1 for THIS row (via its detail id). That removes it from the
											// header list (notifications-list = clear 0) but it stays on the Notification Page
											// (all_notifications-list shows every row, including cleared ones).
											function clearItem(item){
												if(!item){ return; }
												var did=item.getAttribute('data-detail');
												var wasUnread=item.getAttribute('data-read')!=='1';
												if(did!==''){ api(deleteUrl,{detail_id:did}); }
												item.parentNode.removeChild(item);
												if(wasUnread){ setBadge((parseInt(badge.textContent,10)||0)-1); }
												if(!listEl.querySelector('.front-bell-item')){ showEmpty(); }
											}
											btn.addEventListener('click',function(e){ e.stopPropagation(); panel.classList.toggle('open'); });
											document.addEventListener('click',function(e){ if(!document.getElementById('frontBellWrap').contains(e.target)){ panel.classList.remove('open'); } });
											listEl.addEventListener('click',function(e){
												var item=e.target.closest?e.target.closest('.front-bell-item'):null;
												if(!item){ return; }
												// The ✕ clears this row from the popup only (stays on the Notification Page).
												if(e.target.closest('.cl')){ e.preventDefault(); e.stopPropagation(); clearItem(item); return; }
												// Clicking anywhere else on the row marks ONLY this one read, then opens its link.
												var url=item.getAttribute('data-url');
												markItemRead(item);
												if(url){ setTimeout(function(){ window.location.href=url; },120); }
											});
											document.getElementById('frontBellReadAll').addEventListener('click',function(e){
												e.stopPropagation();
												api(readUrl,{}).then(function(){
													var items=listEl.querySelectorAll('.front-bell-item');
													for(var i=0;i<items.length;i++){ items[i].setAttribute('data-read','1'); items[i].classList.remove('is-unread'); items[i].classList.add('is-read'); }
													setBadge(0);
												});
											});
											// Header "Clear All" sets clear=1 for all the caller's active rows: they leave the header
											// but remain on the Notification Page (all_notifications-list still returns them).
											document.getElementById('frontBellClearAll').addEventListener('click',function(e){
												e.stopPropagation();
												api(deleteUrl,{}).then(function(){ showEmpty(); setBadge(0); });
											});
											load();
										})();
										</script>
										<?php } ?>
										<div class="dropdown d-inline-block front-profile-wrap">
											<a href="javascript:void(0);" class="dropdown-toggle front-profile-trigger" id="frontUserMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
												<?php if ($front_avatar_url !== '') { ?>
													<span class="front-profile-trigger-avatar"><img src="<?php echo html_escape($front_avatar_url); ?>" alt=""></span>
												<?php } else { ?>
													<span class="front-profile-trigger-avatar front-profile-trigger-avatar--ph"><i class="fas fa-user"></i></span>
												<?php } ?>
												<span class="front-profile-trigger-name">Hello, <?php echo html_escape($front_user_name); ?>!</span>
											</a>
											<div class="dropdown-menu dropdown-menu-right front-profile-dropdown" aria-labelledby="frontUserMenu">
												<div class="front-profile-dropdown-inner">
													<nav class="front-profile-links" aria-label="Account menu">
														<a class="front-profile-link" href="<?php echo base_url('update-profile'); ?>"><i class="fas fa-user-circle"></i> View Profile</a>
                                                        <a class="front-profile-link" href="<?php echo html_escape($front_my_institutes_url); ?>"><i class="fas fa-chalkboard-teacher"></i>My Institutes</a>
														<a class="front-profile-link" href="<?php echo html_escape($front_my_batches_url); ?>"><i class="fas fa-layer-group"></i>My Batches</a>
														<a class="front-profile-link" href="<?php echo base_url('payment-history'); ?>"><i class="fas fa-receipt"></i> Payment History</a>
														<a class="front-profile-link" href="<?php echo base_url('update-password'); ?>"><i class="fas fa-key"></i> Update Password</a>
														<a class="front-profile-link" href="<?php echo html_escape($front_notifications_url); ?>"><i class="fas fa-bell"></i> Notifications</a>
														<div class="front-profile-divider"></div>
														<a class="front-profile-link front-profile-link--danger" href="<?php echo base_url('delete-account'); ?>"><i class="fas fa-user-times"></i> Delete Account</a>
														<a class="front-profile-link cnfmlogOutBtn" href="javascript:void(0);"><i class="icofont-logout"></i> <?php echo html_escape($this->common->languageTranslator('ltr_logout')); ?></a>
													</nav>
												</div>
											</div>
										</div>
									  <?php }else{ ?>
										<a class="edu-header-login-link" href="<?php echo base_url('login');?>"><i class="fas fa-sign-in-alt"></i><?php echo html_escape($this->common->languageTranslator('ltr_login')); ?></a>
									<?php } ?>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="d-lg-none edu-front-mobile-nav-drawer" id="eduFrontMobileNavDrawer" aria-hidden="true">
				<div class="edu_main_menu main_menu_parent">
					<div class="edu_nav_items main_menu_wrapper text-left">
						<?php $this->load->view('common/partials/front_primary_nav_ul'); ?>
					</div>
				</div>
			</div>
		</header>