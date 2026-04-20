<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="edu_page_title_text">
					<h1>Update Profile</h1>
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
					<h4>Update profile details</h4>
					<div class="row">
						<div class="col-12"><div class="edu_field_holder"><input id="up_name" class="edu_form_field" type="text" placeholder="Name" value="<?php echo html_escape(isset($profile['name']) ? $profile['name'] : ''); ?>"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="up_email" class="edu_form_field" type="email" placeholder="Email" value="<?php echo html_escape(isset($profile['email']) ? $profile['email'] : ''); ?>"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="up_mobile" class="edu_form_field" type="text" placeholder="Mobile" maxlength="10" inputmode="numeric" value="<?php echo html_escape(isset($profile['mobile']) ? $profile['mobile'] : ''); ?>"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="up_address" class="edu_form_field" type="text" placeholder="Address" value="<?php echo html_escape(isset($profile['address']) ? $profile['address'] : ''); ?>"></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><input id="up_country" class="edu_form_field" type="text" placeholder="Country" value="<?php echo html_escape(isset($profile['country']) ? $profile['country'] : ''); ?>"></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><input id="up_state" class="edu_form_field" type="text" placeholder="State" value="<?php echo html_escape(isset($profile['state']) ? $profile['state'] : ''); ?>"></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><input id="up_city" class="edu_form_field" type="text" placeholder="City" value="<?php echo html_escape(isset($profile['city']) ? $profile['city'] : ''); ?>"></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><input id="up_pincode" class="edu_form_field" type="text" placeholder="Pincode" value="<?php echo html_escape(isset($profile['pincode']) ? $profile['pincode'] : ''); ?>"></div></div>
						<div class="col-12 mt-2 text-md-right"><button id="up_submit_btn" type="button" class="edu_btn edu_btn_black">Update Profile</button></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
(function () {
	var url = <?php echo json_encode(site_url('update-profile-submit')); ?>;
	function ok(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('up_submit_btn');
		if (!btn) { return; }
		btn.addEventListener('click', function () {
			var payload = {
				name: (document.getElementById('up_name').value || '').trim(),
				email: (document.getElementById('up_email').value || '').trim(),
				mobile: (document.getElementById('up_mobile').value || '').trim().replace(/\D/g, ''),
				address: (document.getElementById('up_address').value || '').trim(),
				country: (document.getElementById('up_country').value || '').trim(),
				state: (document.getElementById('up_state').value || '').trim(),
				city: (document.getElementById('up_city').value || '').trim(),
				pincode: (document.getElementById('up_pincode').value || '').trim()
			};
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(payload)
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || j.message || 'Profile updated'); }
				} else if (typeof toastr !== 'undefined') {
					toastr.error(j.msg || j.message || 'Profile update failed');
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			});
		});
	});
})();
</script>
