<!-- Logout start-->
<div id="logoutPopup" class="edu_popup_container mfp-hide">
    <div class="edu_popup_wrapper">
        <div class="edu_popup_inner text-center">
            <h4 class="edu_title edu_logt_title padderBottom20"><?php echo html_escape($this->common->languageTranslator('ltr_are_you_logout'));?></h4>
            <button type="button" class="edu_btn edu_admin_btn edu_admin_btn_black edu_btn_black logoutBtnCncl mb-2"><?php echo html_escape($this->common->languageTranslator('ltr_cancel'));?></button>
            <button type="button" class="edu_btn edu_admin_btn logOutBtn ml-2 mb-2"><?php echo html_escape($this->common->languageTranslator('ltr_yes'));?></button>
        </div>
    </div>
</div>
<!-- Logout end-->
<?php
$fd0 = (isset($frontend_details) && is_array($frontend_details) && !empty($frontend_details[0]) && is_array($frontend_details[0])) ? $frontend_details[0] : array();
$ft_about = '';
if (!empty($fd0['abt_frst_desc'])) {
	$ft_about = strip_tags((string) $fd0['abt_frst_desc']);
	if (strlen($ft_about) > 220) {
		$ft_about = substr($ft_about, 0, 220) . '…';
	}
	$ft_about = html_escape($ft_about);
} else {
	$ft_about = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.';
}
$ft_address = !empty($fd0['address']) ? html_escape((string) $fd0['address']) : '04 A, Agroha Nagar, Dewas, Madhya Pradesh 455001';
$ft_mobile = !empty($fd0['mobile']) ? html_escape((string) $fd0['mobile']) : '+91 9999999999';
$ft_email_raw = !empty($fd0['email']) ? trim((string) $fd0['email']) : 'helpdesk@gradmo.academy';
$ft_email = html_escape($ft_email_raw);
$ft_tel_href = 'tel:' . preg_replace('/[^\d+]/', '', preg_replace('/\s+/', '', !empty($fd0['mobile']) ? (string) $fd0['mobile'] : '+919999999999'));
if (strlen($ft_tel_href) < 5) {
	$ft_tel_href = 'tel:+919999999999';
}
$ft_brand = html_escape(trim((string) $this->common->siteTitle) !== '' ? $this->common->siteTitle : 'Gradmo');
?>
<footer class="edu_footer_wrapper edu-footer-gradmo">
		<div class="container-fluid edu-footer-gradmo__fluid">
			<div class="edu-footer-gradmo__inner">
				<div class="edu-footer-gradmo__grid">
					<div class="edu-footer-gradmo__logo-col">
						<div class="edu-footer-gradmo__logo-icon" aria-hidden="true">
							<i class="fas fa-book-open"></i>
						</div>
						<h2 class="edu-footer-gradmo__brand"><?php echo $ft_brand; ?></h2>
					</div>
					<div class="edu-footer-gradmo__about">
						<h4 class="edu-footer-gradmo__heading"><?php echo $ft_brand; ?></h4>
						<p class="edu-footer-gradmo__text"><?php echo $ft_about; ?></p>
					</div>
					<div class="edu-footer-gradmo__links">
						<h4 class="edu-footer-gradmo__heading edu-footer-gradmo__heading--caps"><?php echo html_escape('Useful links'); ?></h4>
						<div class="edu-footer-gradmo__links-cols">
							<div class="edu-footer-gradmo__links-col">
								<p><a href="<?php echo base_url('about-us'); ?>">› <?php echo html_escape($this->common->languageTranslator('ltr_about_us')); ?></a></p>
								<p><a href="<?php echo base_url('contact-us'); ?>">› <?php echo html_escape($this->common->languageTranslator('ltr_contact_us')); ?></a></p>
								<p><a href="<?php echo base_url('courses-offered'); ?>">› <?php echo html_escape($this->common->languageTranslator('ltr_courses_offered')); ?></a></p>
							</div>
							<div class="edu-footer-gradmo__links-col">
								<p><a href="<?php echo base_url('institute/listing'); ?>">› <?php echo html_escape('Institute listing'); ?></a></p>
								<p><a href="<?php echo base_url('privacy-policy'); ?>">› <?php echo html_escape($this->common->languageTranslator('ltr_privacy_policy')); ?></a></p>
								<p><a href="<?php echo base_url('contact-us'); ?>">› <?php echo html_escape('Get in touch'); ?></a></p>
							</div>
						</div>
					</div>
					<div class="edu-footer-gradmo__contact">
						<h4 class="edu-footer-gradmo__heading edu-footer-gradmo__heading--caps"><?php echo html_escape($this->common->languageTranslator('ltr_contact_us')); ?></h4>
						<p class="edu-footer-gradmo__contact-line"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span><?php echo $ft_address; ?></span></p>
						<p class="edu-footer-gradmo__contact-line"><i class="fas fa-phone-alt" aria-hidden="true"></i><a href="<?php echo html_escape($ft_tel_href); ?>"><?php echo $ft_mobile; ?></a></p>
						<p class="edu-footer-gradmo__contact-line"><i class="fas fa-envelope" aria-hidden="true"></i><a href="mailto:<?php echo html_escape($ft_email_raw, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $ft_email; ?></a></p>
					</div>
				</div>
			</div>
			<div class="edu-footer-gradmo__bottom-wrap">
				<div class="edu-footer-gradmo__bottom">
					<p><?php echo html_escape($this->common->copyrightText); ?></p>
				</div>
			</div>
		</div>
	</footer>	
	<!---------- GO To Top ------------>
	<a href="javascript:void(0);" id="scroll"><span class="icofont-swoosh-up"></span></a>
    <!----- Script Start ----->
	<script src="<?php echo base_url();?>assets/js/jquery.min.js"></script>
	<script src="<?php echo base_url();?>assets/js/popper.min.js"></script>
	<script src="<?php echo base_url();?>assets/js/bootstrap.min.js"></script>
    <script src="<?php echo base_url();?>assets/js/swiper.min.js"></script>
	<script src="<?php echo base_url();?>assets/js/wow.min.js"></script>
	<script src="<?php echo base_url();?>assets/js/jquery.magnific-popup.min.js"></script>
	<script src="<?php echo base_url();?>assets/js/jquery.appear.js"></script>
	<script src="<?php echo base_url();?>assets/js/jquery.countTo.js"></script>
	<script src="<?php echo base_url();?>assets/js/toastr.min.js"></script>
	<script src="<?php echo base_url();?>assets/js/tilt.js"></script>
	<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
	<script src="<?php echo base_url();?>assets/js/front-custom.js?<?php echo time();?>"></script>
	<?php if (!empty($load_auth_form_assets)): ?>
	<!-- Student re-login popup (used by login.js after AJAX login) -->
	<div id="studentLogin" class="edu_popup_container mfp-hide">
		<div class="edu_popup_wrapper">
			<div class="edu_popup_inner text-center">
				<h4 class="edu_admin_title edu_logt_title"></h4>
				<h6 class="edu_admin_sub_title edu_logt_title"><?php echo html_escape($this->common->languageTranslator('ltr_already_logout'));?></h6>
				<input type="hidden" value="<?php echo base_url();?>" id="base_url">
				<button type="button" class="edu_btn changeStudentLogin mb-2" data-id=""><?php echo html_escape($this->common->languageTranslator('ltr_yes'));?></button>
				<button type="button" class="edu_btn edu_btn_black PopupCancelBtn ml-2 mb-2"><?php echo html_escape($this->common->languageTranslator('ltr_cancel'));?></button>
			</div>
		</div>
	</div>
	<script src="<?php echo base_url();?>assets/js/login.js?<?php echo time();?>"></script>
	<script src="<?php echo base_url();?>assets/js/valid.js?<?php echo time();?>"></script>
	<?php endif; ?>
	<?php if (!empty($load_register_otp_script)) { $this->load->view('frontend/register_otp_script'); } ?>
	<?php if (!empty($load_login_otp_script)) { $this->load->view('frontend/login_otp_script'); } ?>
  </body>
</html>