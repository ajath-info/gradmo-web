<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=6">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo html_escape(rtrim(base_url(), '/') . '/'); ?>" aria-label="Back to home"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Batches</span>
	</div>
	
	<div class="inst-detail-container inst-list-page-container">
		<div class="inst-detail-panel inst-list-directory-panel">
			<div class="inst-panel-head">
				<h3>Active batches</h3>
				<a class="inst-see-all" href="<?php echo base_url('batch/mylist'); ?>">My batches</a>
			</div>
			<p class="inst-list-intro">All active batches on the platform. Use search to filter by name. Sign in required.</p>
			<div class="inst-list-filter-bar" role="search">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item inst-list-filter-grow">
						<label for="batch_search">Search</label>
						<input type="search" id="batch_search" class="edu_form_field" placeholder="Search by batch name…" autocomplete="off">
					</div>
				</div>
				<div class="inst-list-filter-actions">
					<button type="button" id="batch_apply" class="edu_btn edu_btn_black inst-list-apply-btn">Search</button>
				</div>
			</div>
			<div id="batch_list_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="batch_card_stack" class="inst-panel-stack"></div>
			<div class="inst-list-pagination">
				<button type="button" id="batch_prev" class="edu_btn edu_btn_black" disabled>Previous</button>
				<span id="batch_page_info" class="inst-muted small"></span>
				<button type="button" id="batch_next" class="edu_btn edu_btn_black" disabled>Next</button>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	var dataUrl = <?php echo json_encode(isset($batch_list_data_url) ? $batch_list_data_url : ''); ?>;
	var detailsBase = <?php echo json_encode(isset($batch_details_base) ? rtrim($batch_details_base, '/') : ''); ?>;
	var page = 1;
	var limit = 10;
	var totalPages = 1;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function postJson(body) {
		return fetch(dataUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); });
	}
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function render(list) {
		var stack = document.getElementById('batch_card_stack');
		stack.innerHTML = '';
		if (!list || !list.length) {
			stack.innerHTML = '<p class="inst-muted mb-0 px-2">No batches found.</p>';
			return;
		}
		list.forEach(function (it) {
			var id = it.batch_id || it.batchId || 0;
			var href = detailsBase + '?batch_id=' + encodeURIComponent(id);
			var name = it.batchName || it.title || it.batch_name || '';
			var imgUrl = (it.batchImage || it.logo || '').trim();
			var thumb = imgUrl
				? '<div class="inst-mini-logo"><img src="' + esc(imgUrl) + '" alt=""></div>'
				: '<div class="inst-mini-logo"><i class="fas fa-chalkboard-teacher" style="padding:12px;color:#f5a623;"></i></div>';
			var instructor = it.instructor || '';
			var schedule = it.schedule || '';
			var sd = it.start_date || '';
			var ed = it.end_date || '';
			var dates = '';
			if (sd || ed) {
				dates = esc(sd && ed ? (sd + ' – ' + ed) : (sd || ed));
			}
			var a = document.createElement('a');
			a.className = 'inst-batch-card';
			a.href = href;
			a.innerHTML = thumb +
				'<div class="inst-card-body">' +
				'<p class="inst-card-title-sm">' + esc(name) + '</p>' +
				(instructor ? '<p class="batch-list-meta"><strong>Instructor:</strong> ' + esc(instructor) + '</p>' : '') +
				(schedule ? '<p class="batch-list-meta"><strong>Schedule:</strong> ' + esc(schedule) + '</p>' : '') +
				(dates ? '<p class="batch-list-dates">' + dates + '</p>' : '') +
				'</div><span class="inst-card-chevron"><i class="fas fa-chevron-right"></i></span>';
			stack.appendChild(a);
		});
	}
	function load() {
		var body = {
			page: page,
			limit: limit,
			search: (document.getElementById('batch_search').value || '').trim(),
			list: 'All'
		};
		document.getElementById('batch_list_msg').textContent = 'Loading…';
		postJson(body).then(function (j) {
			var batches = (j.data && j.data.enrolled_batches) ? j.data.enrolled_batches : [];
			var pag = (j.data && j.data.pagination) ? j.data.pagination : {};
			if (!ok(j.status)) {
				document.getElementById('batch_list_msg').textContent = j.msg || j.message || 'Could not load batches.';
				render([]);
				document.getElementById('batch_page_info').textContent = '';
				document.getElementById('batch_prev').disabled = true;
				document.getElementById('batch_next').disabled = true;
				return;
			}
			document.getElementById('batch_list_msg').textContent = '';
			render(batches);
			totalPages = Math.max(1, parseInt(pag.totalPages, 10) || 1);
			document.getElementById('batch_page_info').textContent = 'Page ' + page + ' / ' + totalPages;
			document.getElementById('batch_prev').disabled = page <= 1;
			document.getElementById('batch_next').disabled = page >= totalPages;
		}).catch(function () {
			document.getElementById('batch_list_msg').textContent = 'Network error.';
			render([]);
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('batch_apply').addEventListener('click', function () { page = 1; load(); });
		document.getElementById('batch_search').addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				page = 1;
				load();
			}
		});
		document.getElementById('batch_prev').addEventListener('click', function () { if (page > 1) { page--; load(); } });
		document.getElementById('batch_next').addEventListener('click', function () { if (page < totalPages) { page++; load(); } });
		load();
	});
})();
</script>
