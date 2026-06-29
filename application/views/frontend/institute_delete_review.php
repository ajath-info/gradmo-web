<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=1">
<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 inst-hero">
				<h1>Delete review</h1>
				<p class="inst-muted mb-0">This removes your pending review permanently.</p>
			</div>
		</div>
	</div>
</section>
<section class="edu_form_wrapper enroll-wrapper">
	<div class="container inst-page-wrap">
		<div class="row justify-content-center">
			<div class="col-12 col-md-8 text-center">
				<p class="mb-4">Review ID: <strong><?php echo (int) (isset($review_id) ? $review_id : 0); ?></strong></p>
				<div class="inst-actions justify-content-center">
					<a class="edu_btn" href="<?php echo site_url('institute/listing'); ?>">Cancel</a>
					<button type="button" id="idr_go" class="edu_btn edu_btn_black">Delete</button>
				</div>
				<p id="idr_msg" class="inst-muted mt-3"></p>
			</div>
		</div>
	</div>
</section>
<script>
(function () {
	var url = <?php echo json_encode(isset($institute_delete_review_submit_url) ? $institute_delete_review_submit_url : ''); ?>;
	var rid = <?php echo (int) (isset($review_id) ? $review_id : 0); ?>;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('idr_go').addEventListener('click', function () {
			if (rid < 1) { return; }
			document.getElementById('idr_msg').textContent = 'Deleting…';
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({ review_id: rid })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || 'Deleted'); }
					window.location.href = <?php echo json_encode(site_url('institute/listing')); ?>;
				} else {
					document.getElementById('idr_msg').textContent = j.msg || 'Failed';
					if (typeof toastr !== 'undefined') { toastr.error(j.msg || 'Failed'); }
				}
			}).catch(function () {
				document.getElementById('idr_msg').textContent = 'Network error.';
			});
		});
	});
})();
</script>
