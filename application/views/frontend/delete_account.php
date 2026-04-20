<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="edu_page_title_text">
					<h1>Delete Account</h1>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="edu_form_wrapper enroll-wrapper contactpage">
	<div class="container">
		<div class="row">
			<div class="col-lg-8 col-md-10 col-sm-12 col-12 mx-auto">
				<div class="edu_form_container_main edu_form_container withoutMapFrm">
					<h4>Delete your account</h4>
					<p class="text-danger mb-3">This action can deactivate your account. Please proceed carefully.</p>
					<div class="row">
						<div class="col-12">
							<label class="loginLinks checkbox_holder mt-2">
								<input type="checkbox" id="da_confirm">
								<span class="ml-2">I understand and want to delete my account.</span>
							</label>
						</div>
						<div class="col-12 mt-3 text-md-right"><button id="da_submit_btn" type="button" class="edu_btn edu_btn_black">Delete Account</button></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
(function () {
	var url = <?php echo json_encode(site_url('delete-account-submit')); ?>;
	function ok(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('da_submit_btn');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			if (!document.getElementById('da_confirm').checked) {
				if (typeof toastr !== 'undefined') { toastr.error('Please confirm before deleting account.'); }
				return;
			}
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({})
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || j.message || 'Account deleted'); }
					setTimeout(function () { window.location.href = <?php echo json_encode(site_url('logout')); ?>; }, 800);
				} else if (typeof toastr !== 'undefined') {
					toastr.error(j.msg || j.message || 'Delete account failed');
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			});
		});
	});
})();
</script>
