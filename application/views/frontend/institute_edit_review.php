<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=1">
<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 inst-hero">
				<h1>Edit review</h1>
				<p class="inst-muted mb-0"><a href="<?php echo site_url('institute/listing'); ?>">← Institutes</a></p>
			</div>
		</div>
	</div>
</section>
<section class="edu_form_wrapper enroll-wrapper">
	<div class="container inst-page-wrap">
		<div class="row justify-content-center">
			<div class="col-12 col-lg-7">
				<div id="ier_msg" class="inst-muted mb-2"></div>
				<div class="edu_form_container_main edu_form_container withoutMapFrm" id="ier_form" style="display:none;">
					<input type="hidden" id="ier_review_id" value="<?php echo (int) (isset($review_id) ? $review_id : 0); ?>">
					<div class="edu_field_holder">
						<label class="small text-muted">Rating</label>
						<select id="ier_rating" class="edu_form_field">
							<option value="5">5</option>
							<option value="4">4</option>
							<option value="3">3</option>
							<option value="2">2</option>
							<option value="1">1</option>
						</select>
					</div>
					<div class="edu_field_holder">
						<label class="small text-muted">Your review</label>
						<textarea id="ier_body" class="edu_form_field" rows="5"></textarea>
					</div>
					<div class="inst-actions">
						<a class="edu_btn" href="<?php echo site_url('institute/delete-review?review_id=' . (int) (isset($review_id) ? $review_id : 0)); ?>">Delete review</a>
						<button type="button" id="ier_save" class="edu_btn edu_btn_black">Save changes</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<script>
(function () {
	var detailUrl = <?php echo json_encode(isset($institute_review_detail_data_url) ? $institute_review_detail_data_url : ''); ?>;
	var saveUrl = <?php echo json_encode(isset($institute_update_review_submit_url) ? $institute_update_review_submit_url : ''); ?>;
	var rid = <?php echo (int) (isset($review_id) ? $review_id : 0); ?>;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	document.addEventListener('DOMContentLoaded', function () {
		if (rid < 1) {
			document.getElementById('ier_msg').textContent = 'Invalid review.';
			return;
		}
		document.getElementById('ier_msg').textContent = 'Loading…';
		fetch(detailUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: JSON.stringify({ review_id: rid })
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!ok(j.status) || !j.review) {
				document.getElementById('ier_msg').textContent = j.msg || 'Could not load review.';
				return;
			}
			document.getElementById('ier_msg').textContent = '';
			document.getElementById('ier_form').style.display = 'block';
			document.getElementById('ier_rating').value = String(j.review.rating || 5);
			document.getElementById('ier_body').value = j.review.msg || '';
		}).catch(function () {
			document.getElementById('ier_msg').textContent = 'Network error.';
		});
		document.getElementById('ier_save').addEventListener('click', function () {
			var rating = parseInt(document.getElementById('ier_rating').value, 10);
			var msg = (document.getElementById('ier_body').value || '').trim();
			if (msg === '') {
				if (typeof toastr !== 'undefined') { toastr.error('Review text required'); }
				return;
			}
			fetch(saveUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({ review_id: rid, rating: rating, msg: msg })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || 'Saved'); }
					window.location.href = <?php echo json_encode(site_url('institute/listing')); ?>;
				} else if (typeof toastr !== 'undefined') { toastr.error(j.msg || 'Failed'); }
			});
		});
	});
})();
</script>
