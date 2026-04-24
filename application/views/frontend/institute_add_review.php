<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=2">
<div class="inst-review-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo site_url('institute/details?institute_id=' . (int) (isset($institute_id) ? $institute_id : 0)); ?>" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Write a review</span>
	</div>
	<div class="inst-review-body">
		<input type="hidden" id="arv_institute_id" value="<?php echo (int) (isset($institute_id) ? $institute_id : 0); ?>">
		<input type="hidden" id="arv_rating" value="5">
		<div class="inst-review-stars-wrap">
			<label for="arv_star_5">Your rating</label>
			<div class="inst-review-stars" id="arv_stars" role="radiogroup" aria-label="Rating">
				<button type="button" data-val="1" id="arv_star_1" aria-label="1 star"><span>★</span></button>
				<button type="button" data-val="2" id="arv_star_2" aria-label="2 stars"><span>★</span></button>
				<button type="button" data-val="3" id="arv_star_3" aria-label="3 stars"><span>★</span></button>
				<button type="button" data-val="4" id="arv_star_4" aria-label="4 stars"><span>★</span></button>
				<button type="button" data-val="5" id="arv_star_5" aria-label="5 stars"><span>★</span></button>
			</div>
		</div>
		<div class="inst-review-text-shell">
			<textarea id="arv_msg" rows="6" placeholder="Write feedback…" aria-label="Review text"></textarea>
		</div>
		<button type="button" id="arv_submit" class="inst-submit-full">Submit</button>
	</div>
</div>
<script>
(function () {
	var submitUrl = <?php echo json_encode(isset($institute_add_review_submit_url) ? $institute_add_review_submit_url : ''); ?>;
	var detailUrl = <?php echo json_encode(site_url('institute/details?institute_id=') . (int) (isset($institute_id) ? $institute_id : 0)); ?>;
	var ratingInput = document.getElementById('arv_rating');
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function paintStars(val) {
		val = Math.max(1, Math.min(5, parseInt(val, 10) || 5));
		ratingInput.value = String(val);
		document.querySelectorAll('#arv_stars button').forEach(function (btn) {
			var v = parseInt(btn.getAttribute('data-val'), 10);
			btn.classList.toggle('is-on', v <= val);
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		paintStars(5);
		document.getElementById('arv_stars').addEventListener('click', function (e) {
			var t = e.target.closest('button[data-val]');
			if (!t) { return; }
			paintStars(t.getAttribute('data-val'));
		});
		document.getElementById('arv_submit').addEventListener('click', function () {
			var btn = document.getElementById('arv_submit');
			var iid = parseInt(document.getElementById('arv_institute_id').value, 10);
			var rating = parseInt(ratingInput.value, 10);
			var msg = (document.getElementById('arv_msg').value || '').trim();
			if (iid < 1 || msg === '') {
				if (typeof toastr !== 'undefined') { toastr.error('Please enter your review.'); }
				return;
			}
			btn.disabled = true;
			fetch(submitUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({ institute_id: iid, rating: rating, msg: msg })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || 'Submitted'); }
					window.location.href = detailUrl;
				} else {
					if (typeof toastr !== 'undefined') { toastr.error(j.msg || 'Failed'); }
					btn.disabled = false;
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error'); }
				btn.disabled = false;
			});
		});
	});
})();
</script>
