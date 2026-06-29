<section class="edu-auth-page">
	<div class="container py-4 py-md-5">
		<div class="row justify-content-center">
			<div class="col-xl-6 col-lg-7 col-md-10">
				<div class="edu-auth-shell">
					<div class="edu-auth-main">
						<div class="edu-auth-main-inner">
							<nav class="edu-auth-breadcrumb" aria-label="Breadcrumb">
								<a href="<?php echo base_url(); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_home')); ?></a>
								<span class="edu-auth-breadcrumb-sep" aria-hidden="true">/</span>
								<span>Verify account</span>
							</nav>
							<h1 class="edu-auth-heading"><?php echo html_escape($title); ?></h1>
							<div class="edu-auth-form-card edu_form_container_main edu_form_container withoutMapFrm">
								<?php if (!empty($verify_success)) { ?>
									<p class="text-success mb-3"><?php echo html_escape($verify_message); ?></p>
								<?php } else { ?>
									<p class="text-danger mb-3"><?php echo html_escape($verify_message); ?></p>
								<?php } ?>
								<div class="edu-auth-actions">
									<a class="edu-auth-btn edu-auth-btn--primary" href="<?php echo base_url('login'); ?>"><?php echo html_escape($this->common->languageTranslator('ltr_login')); ?></a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
