<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=7">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo html_escape(rtrim(base_url(), '/') . '/'); ?>" aria-label="Back to home"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Search</span>
	</div>

	<div class="inst-detail-container inst-list-page-container">
		<div class="inst-detail-panel inst-list-directory-panel">
			<div class="inst-panel-head">
				<h3>Search</h3>
			</div>
			<p class="inst-list-intro">Search across batches, institutes and teachers. Sign in required.</p>
			<div class="inst-list-filter-bar" role="search">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item inst-list-filter-grow">
						<label for="gs_search">Search</label>
						<input type="search" id="gs_search" class="edu_form_field" placeholder="Search by name…" autocomplete="off" value="<?php echo html_escape(isset($search_key) ? $search_key : ''); ?>">
					</div>
				</div>
				<div class="inst-list-filter-actions">
					<button type="button" id="gs_apply" class="edu_btn edu_btn_black inst-list-apply-btn">Search</button>
				</div>
			</div>
			<div id="gs_msg" class="inst-muted small px-2 mb-2"></div>

			<div class="inst-panel-head mt-3"><h3>Batches</h3></div>
			<div id="gs_batches" class="inst-card-grid"></div>

			<div class="inst-panel-head mt-3"><h3>Institutes</h3></div>
			<div id="gs_institutes" class="inst-card-grid"></div>

			<div class="inst-panel-head mt-3"><h3>Teachers</h3></div>
			<div id="gs_teachers" class="inst-card-grid"></div>
		</div>
	</div>
</div>
<script>
(function () {
	var dataUrl = <?php echo json_encode(isset($global_search_url) ? $global_search_url : ''); ?>;
	var accessToken = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var detailsBase = <?php echo json_encode(isset($batch_details_base) ? rtrim($batch_details_base, '/') : ''); ?>;
	var instituteDetailsBase = <?php echo json_encode(isset($institute_details_base) ? rtrim($institute_details_base, '/') : ''); ?>;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function postJson(body) {
		body = body || {};
		if (accessToken && !body.access_token) { body.access_token = accessToken; }
		var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
		if (accessToken) { headers.Authorization = 'Bearer ' + accessToken; }
		return fetch(dataUrl, { method: 'POST', headers: headers, body: JSON.stringify(body) }).then(function (r) { return r.json(); });
	}
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function renderBatches(list) {
		var stack = document.getElementById('gs_batches');
		stack.innerHTML = '';
		if (!list || !list.length) { stack.innerHTML = '<p class="inst-muted mb-0 px-2">No batches found.</p>'; return; }
		list.forEach(function (it) {
			var id = it.batch_id || it.batchId || 0;
			var href = detailsBase + '?batch_id=' + encodeURIComponent(id);
			var name = it.batchName || it.title || it.batch_name || '';
			var imgUrl = (it.batchImage || it.logo || '').trim();
			var thumb = '<div class="inst-mini-logo"><img src="' + esc(imgUrl) + '" alt="" data-fallback-type="batch" data-has-fallback="1"></div>';
			var instructor = it.instructor || '';
			var inst = it.institute_name || it.instituteName || '';
			var a = document.createElement('a');
			a.className = 'inst-batch-card';
			a.href = href;
			a.innerHTML = thumb +
				'<div class="inst-card-body">' +
				'<p class="inst-card-title-sm">' + esc(name) + '</p>' +
				(inst ? '<p class="batch-list-meta"><strong>Institute:</strong> ' + esc(inst) + '</p>' : '') +
				(instructor ? '<p class="batch-list-meta"><strong>Instructor:</strong> ' + esc(instructor) + '</p>' : '') +
				'</div><span class="inst-card-chevron"><i class="fas fa-chevron-right"></i></span>';
			stack.appendChild(a);
		});
	}
	function renderUsers(targetId, list, emptyMsg, idKey) {
		var stack = document.getElementById(targetId);
		stack.innerHTML = '';
		if (!list || !list.length) { stack.innerHTML = '<p class="inst-muted mb-0 px-2">' + emptyMsg + '</p>'; return; }
		list.forEach(function (it) {
			var id = it[idKey] || it.instituteId || it.Id || it.id || 0;
			var name = [it.name, (it.lastName || it.last_name)].filter(Boolean).join(' ').trim() || (it.name || '');
			var email = it.email || '';
			var imgUrl = (it.image || '').trim();
			var thumb = '<div class="inst-mini-logo"><img src="' + esc(imgUrl) + '" alt="" data-fallback-type="user" data-has-fallback="1"></div>';
			// Both institutes and teachers open the institute/details page (institute_id = their id).
			var href = instituteDetailsBase + '?institute_id=' + encodeURIComponent(id);
			var card = document.createElement('a');
			card.className = 'inst-batch-card';
			card.href = href;
			card.innerHTML = thumb +
				'<div class="inst-card-body">' +
				'<p class="inst-card-title-sm">' + esc(name) + '</p>' +
				(email ? '<p class="batch-list-meta">' + esc(email) + '</p>' : '') +
				'</div><span class="inst-card-chevron"><i class="fas fa-chevron-right"></i></span>';
			stack.appendChild(card);
		});
	}
	function load() {
		var body = { search: (document.getElementById('gs_search').value || '').trim() };
		document.getElementById('gs_msg').textContent = 'Loading…';
		postJson(body).then(function (j) {
			if (!ok(j.status)) {
				document.getElementById('gs_msg').textContent = j.msg || j.message || 'Could not load results.';
				renderBatches([]); renderUsers('gs_institutes', [], 'No institutes found.'); renderUsers('gs_teachers', [], 'No teachers found.');
				return;
			}
			document.getElementById('gs_msg').textContent = '';
			var d = j.data || {};
			renderBatches(d.batchList || []);
			renderUsers('gs_institutes', d.instituteList || [], 'No institutes found.', 'instituteId');
			renderUsers('gs_teachers', d.teacher_list || [], 'No teachers found.', 'Id');
		}).catch(function () {
			document.getElementById('gs_msg').textContent = 'Network error.';
			renderBatches([]); renderUsers('gs_institutes', [], 'No institutes found.'); renderUsers('gs_teachers', [], 'No teachers found.');
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('gs_apply').addEventListener('click', function () { load(); });
		document.getElementById('gs_search').addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { e.preventDefault(); load(); }
		});
		load();
	});
})();
</script>
