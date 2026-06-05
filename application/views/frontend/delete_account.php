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

<section class="edu_form_wrapper enroll-wrapper contactpage da-page">
	<div class="container">
		<div class="row">
			<div class="col-lg-8 col-md-10 col-sm-12 col-12 mx-auto">
				<div class="edu_form_container_main edu_form_container withoutMapFrm">
					<h4>Delete your account</h4>
					<p class="text-danger mb-3">This action can deactivate your account. Please proceed carefully.</p>
					<div class="row">
						<div class="col-12">
							<div class="da-confirm-row loginLinks checkbox_holder">
								<input type="checkbox" id="da_confirm" name="da_confirm" value="1" aria-describedby="da_confirm_help">
								<label for="da_confirm" id="da_confirm_help">I understand and want to delete my account.</label>
							</div>
						</div>
						<div class="col-12 mt-3 text-md-right">
							<button id="da_submit_btn" type="button" class="edu_btn edu_btn_black" disabled>Delete Account</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<style>
.da-page .da-confirm-row {
	display: flex;
	align-items: flex-start;
	gap: 10px;
	margin: 12px 0 4px;
}
.da-page .da-confirm-row input[type="checkbox"] {
	flex: 0 0 auto;
	width: 18px;
	height: 18px;
	margin-top: 3px;
	accent-color: var(--Primary-Color, #4a6ae4);
	cursor: pointer;
	-webkit-appearance: checkbox;
	appearance: auto;
	opacity: 1;
	position: static;
}
.da-page .da-confirm-row label {
	flex: 1 1 auto;
	margin: 0;
	cursor: pointer;
	font-size: 15px;
	line-height: 1.45;
	color: #475569;
	position: static;
}
.da-page .da-confirm-row label:before,
.da-page .da-confirm-row label:after {
	display: none !important;
	content: none !important;
}
.da-page #da_submit_btn:disabled {
	opacity: 0.5;
	cursor: not-allowed;
	pointer-events: none;
}
</style>

<script>
(function () {
	var url = <?php echo json_encode(site_url('delete-account-submit')); ?>;
	function ok(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	function syncDeleteButton() {
		var btn = document.getElementById('da_submit_btn');
		var box = document.getElementById('da_confirm');
		if (!btn || !box) { return; }
		btn.disabled = !box.checked;
	}
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('da_submit_btn');
		var box = document.getElementById('da_confirm');
		if (!btn || !box) { return; }
		syncDeleteButton();
		box.addEventListener('change', syncDeleteButton);
		btn.addEventListener('click', function () {
			if (!box.checked) {
				if (typeof toastr !== 'undefined') { toastr.error('Please check the box to confirm account deletion.'); }
				return;
			}
			btn.disabled = true;
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({ confirmed: true })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || j.message || 'Account deleted'); }
					setTimeout(function () { window.location.href = <?php echo json_encode(site_url('logout')); ?>; }, 800);
				} else {
					btn.disabled = !box.checked;
					if (typeof toastr !== 'undefined') {
						toastr.error(j.msg || j.message || 'Delete account failed');
					}
				}
			}).catch(function () {
				btn.disabled = !box.checked;
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			});
		});
	});
})();
</script>
