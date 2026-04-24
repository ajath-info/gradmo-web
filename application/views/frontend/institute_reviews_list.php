<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=1">
<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 inst-hero">
				<h1>Institute reviews</h1>
				<p class="inst-muted mb-0"><a href="<?php echo site_url('institute/listing'); ?>">← Institutes</a></p>
			</div>
		</div>
	</div>
</section>
<section class="edu_form_wrapper enroll-wrapper">
	<div class="container inst-page-wrap">
		<div class="inst-toolbar">
			<div class="form-group">
				<label class="small text-muted mb-1" for="irv_status">Status</label>
				<select id="irv_status" class="edu_form_field">
					<option value="">All</option>
					<option value="0">Pending</option>
					<option value="1">Approved</option>
				</select>
			</div>
			<div class="form-group">
				<label class="small text-muted mb-1">&nbsp;</label>
				<button type="button" id="irv_reload" class="edu_btn edu_btn_black btn-block">Load</button>
			</div>
		</div>
		<div id="irv_msg" class="inst-muted mb-2"></div>
		<div class="inst-table-wrap">
			<table class="inst-table" id="irv_table">
				<thead>
					<tr>
						<th>ID</th>
						<th>User</th>
						<th>Rating</th>
						<th>Message</th>
						<th>Status</th>
						<th></th>
					</tr>
				</thead>
				<tbody id="irv_tbody"></tbody>
			</table>
		</div>
		<div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
			<button type="button" id="irv_prev" class="edu_btn edu_btn_black" disabled>Previous</button>
			<span id="irv_page" class="inst-muted small"></span>
			<button type="button" id="irv_next" class="edu_btn edu_btn_black" disabled>Next</button>
		</div>
	</div>
</section>
<script>
(function () {
	var dataUrl = <?php echo json_encode(isset($institute_reviews_data_url) ? $institute_reviews_data_url : ''); ?>;
	var approveUrl = <?php echo json_encode(isset($institute_approve_review_submit_url) ? $institute_approve_review_submit_url : ''); ?>;
	var page = 1;
	var totalPages = 1;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function load() {
		var st = document.getElementById('irv_status').value;
		var body = { page: page, per_page: 15 };
		if (st !== '') { body.status = parseInt(st, 10); }
		document.getElementById('irv_msg').textContent = 'Loading…';
		fetch(dataUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			document.getElementById('irv_msg').textContent = ok(j.status) ? '' : (j.msg || 'Error');
			var rows = j.reviews || [];
			var tb = document.getElementById('irv_tbody');
			tb.innerHTML = '';
			if (!rows.length) {
				tb.innerHTML = '<tr><td colspan="6" class="inst-muted">No reviews.</td></tr>';
			} else {
				rows.forEach(function (rw) {
					var tr = document.createElement('tr');
					var stn = parseInt(rw.status, 10) === 1 ? 'Approved' : 'Pending';
					var btn = parseInt(rw.status, 10) === 0
						? '<button type="button" class="edu_btn edu_btn_black btn-sm irv-approve" data-id="' + esc(rw.id) + '">Approve</button>'
						: '';
					tr.innerHTML =
						'<td>' + esc(rw.id) + '</td>' +
						'<td>' + esc(rw.userType || '') + ' #' + esc(rw.userId || '') + '</td>' +
						'<td>' + esc(rw.rating) + '</td>' +
						'<td>' + esc(rw.msg) + '</td>' +
						'<td>' + esc(stn) + '</td>' +
						'<td>' + btn + '</td>';
					tb.appendChild(tr);
				});
			}
			var p = j.pagination || {};
			totalPages = Math.max(1, parseInt(p.totalPages, 10) || 1);
			document.getElementById('irv_page').textContent = 'Page ' + page + ' / ' + totalPages;
			document.getElementById('irv_prev').disabled = page <= 1;
			document.getElementById('irv_next').disabled = page >= totalPages;
		}).catch(function () {
			document.getElementById('irv_msg').textContent = 'Network error.';
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('irv_reload').addEventListener('click', function () { page = 1; load(); });
		document.getElementById('irv_prev').addEventListener('click', function () { if (page > 1) { page--; load(); } });
		document.getElementById('irv_next').addEventListener('click', function () { if (page < totalPages) { page++; load(); } });
		document.getElementById('irv_tbody').addEventListener('click', function (e) {
			var t = e.target;
			if (!t || !t.classList || !t.classList.contains('irv-approve')) { return; }
			var id = parseInt(t.getAttribute('data-id'), 10);
			if (!id) { return; }
			fetch(approveUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({ review_id: id })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || 'Approved'); }
					load();
				} else if (typeof toastr !== 'undefined') { toastr.error(j.msg || 'Failed'); }
			});
		});
		load();
	});
})();
</script>
