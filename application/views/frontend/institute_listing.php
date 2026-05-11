<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=5">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo html_escape(rtrim(base_url(), '/') . '/'); ?>" aria-label="Back to home"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Institutes</span>
	</div>

	<div class="inst-detail-container inst-list-page-container">
		<div class="inst-detail-panel inst-list-directory-panel">
			<div class="inst-panel-head">
				<h3>Directory</h3>
				<?php if (! empty($show_institute_reviews_link)) { ?>
				<a class="inst-see-all" href="<?php echo html_escape($institute_reviews_list_url); ?>">Manage reviews</a>
				<?php } ?>
			</div>
			<p class="inst-list-intro">Browse institutes. Sign in required.</p>
			<div class="inst-list-filter-bar" role="search">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item inst-list-filter-grow">
						<label for="inst_search">Search</label>
						<input type="search" id="inst_search" class="edu_form_field" placeholder="Name, city, email…" autocomplete="off">
					</div>
					<div class="inst-list-filter-item">
						<label for="inst_city">City</label>
						<select id="inst_city" class="edu_form_field" aria-label="City filter">
							<option value="">All cities</option>
						</select>
					</div>
					<div class="inst-list-filter-item">
						<label for="inst_order">Sort</label>
						<select id="inst_order" class="edu_form_field">
							<option value="name|asc">Name A–Z</option>
							<option value="name|desc">Name Z–A</option>
						</select>
					</div>
				</div>
				<div class="inst-list-filter-actions">
					<button type="button" id="inst_apply" class="edu_btn edu_btn_black inst-list-apply-btn">Apply</button>
				</div>
			</div>
			<div id="inst_list_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="inst_card_stack" class="inst-card-grid"></div>
			<div class="inst-list-pagination">
				<button type="button" id="inst_prev" class="edu_btn edu_btn_black" disabled>Previous</button>
				<span id="inst_page_info" class="inst-muted small"></span>
				<button type="button" id="inst_next" class="edu_btn edu_btn_black" disabled>Next</button>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	var dataUrl = <?php echo json_encode(isset($institute_listing_data_url) ? $institute_listing_data_url : ''); ?>;
	var cityListUrl = <?php echo json_encode(isset($institute_city_list_url) ? $institute_city_list_url : ''); ?>;
	var detailsBase = <?php echo json_encode(isset($institute_details_url) ? $institute_details_url : ''); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var page = 1;
	var totalPages = 1;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function postJson(body) {
		return fetch(dataUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); });
	}
	function fetchCityOptions() {
		var sel = document.getElementById('inst_city');
		if (!cityListUrl || !sel) { return Promise.resolve(); }
		return fetch(cityListUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: '{}'
		}).then(function (r) { return r.json(); }).then(function (j) {
			sel.innerHTML = '';
			var all = document.createElement('option');
			all.value = '';
			all.textContent = 'All cities';
			sel.appendChild(all);
			if (!ok(j.status) || !j.cities || !j.cities.length) { return; }
			j.cities.forEach(function (row) {
				var c = (row && row.city != null) ? String(row.city).trim() : '';
				if (c === '') { return; }
				var o = document.createElement('option');
				o.value = c;
				o.textContent = c;
				sel.appendChild(o);
			});
		}).catch(function () { /* keep All cities only */ });
	}
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function starsHtml(n) {
		n = Math.max(0, Math.min(5, parseInt(n, 10) || 0));
		var o = '<span class="inst-detail-rating-stars">';
		for (var i = 0; i < 5; i++) { o += (i < n ? '★' : '☆'); }
		return o + '</span>';
	}
	function render(list) {
		var stack = document.getElementById('inst_card_stack');
		stack.innerHTML = '';
		if (!list || !list.length) {
			stack.innerHTML = '<p class="inst-muted mb-0 px-2">No institutes or teachers found.</p>';
			return;
		}
		list.forEach(function (it) {
			var id = it.instituteId || it.institute_id || 0;
			var href = detailsBase + (detailsBase.indexOf('?') >= 0 ? '&' : '?') + 'institute_id=' + encodeURIComponent(id);
			var profileType = (it.profileType || it.userType || '').toString().toLowerCase() === 'teacher' ? 'teacher' : 'institute';
			var profileLabel = it.profileTypeLabel || (profileType === 'teacher' ? 'Teacher' : 'Institute');
			var avg = it.rating && typeof it.rating.averageRating !== 'undefined' ? Number(it.rating.averageRating) : 0;
			var tr = it.rating && it.rating.totalReviews != null ? it.rating.totalReviews : 0;
			var loc = [it.city, it.state].filter(Boolean).join(', ');
			var imgUrl = (it.imageUrl || '').trim();
			var thumb = '<div class="inst-mini-logo"><img src="' + esc(imgUrl) + '" alt="" data-fallback-type="' + esc(profileType) + '" data-has-fallback="1"></div>';
			var a = document.createElement('a');
			a.className = 'inst-batch-card';
			a.href = href;
			a.innerHTML = thumb +
				'<div class="inst-card-body">' +
				'<p class="inst-card-title-sm">' + esc(it.name || '') + '</p>' +
				'<p class="inst-card-sub"><strong>Type:</strong> ' + esc(profileLabel) + '</p>' +
				(loc ? '<p class="inst-card-sub">' + esc(loc) + '</p>' : '') +
				'<p class="inst-card-sub">' + starsHtml(Math.round(avg)) + ' <strong>' + esc(avg.toFixed(1)) + '</strong> (' + esc(tr) + ')</p>' +
				'</div><span class="inst-card-chevron"><i class="fas fa-chevron-right"></i></span>';
			stack.appendChild(a);
		});
	}
	function load() {
		var ord = (document.getElementById('inst_order').value || 'name|asc').split('|');
		var body = {
			page: page,
			per_page: 12,
			order_field: ord[0] || 'name',
			order_type: ord[1] || 'asc',
			search: (document.getElementById('inst_search').value || '').trim(),
			city: (document.getElementById('inst_city').value || '').trim()
		};
		if (batchId > 0) { body.batch_id = batchId; }
		document.getElementById('inst_list_msg').textContent = 'Loading…';
		postJson(body).then(function (j) {
			document.getElementById('inst_list_msg').textContent = ok(j.status) ? '' : (j.msg || 'Failed to load.');
			if (!ok(j.status)) { render([]); return; }
			render(j.institutes || []);
			var p = j.pagination || {};
			totalPages = Math.max(1, parseInt(p.totalPages, 10) || 1);
			document.getElementById('inst_page_info').textContent = 'Page ' + page + ' / ' + totalPages;
			document.getElementById('inst_prev').disabled = page <= 1;
			document.getElementById('inst_next').disabled = page >= totalPages;
		}).catch(function () {
			document.getElementById('inst_list_msg').textContent = 'Network error.';
			render([]);
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('inst_apply').addEventListener('click', function () { page = 1; load(); });
		document.getElementById('inst_search').addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				page = 1;
				load();
			}
		});
		document.getElementById('inst_prev').addEventListener('click', function () { if (page > 1) { page--; load(); } });
		document.getElementById('inst_next').addEventListener('click', function () { if (page < totalPages) { page++; load(); } });
		var citySel = document.getElementById('inst_city');
		if (citySel) {
			citySel.addEventListener('change', function () { page = 1; load(); });
		}
		fetchCityOptions().then(function () { load(); });
	});
})();
</script>
