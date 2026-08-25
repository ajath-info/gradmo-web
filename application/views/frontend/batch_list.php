<?php
// Single page for both "all" batches (batch/list) and "my" batches (batch/mylist).
$lf = isset($list_flag) ? (string) $list_flag : 'all';
$is_my = ($lf === 'my');
$page_title = $is_my ? 'My batches' : 'Active batches';
$alt_link = $is_my ? base_url('batch/list') : base_url('batch/mylist');
$alt_label = $is_my ? 'All batches' : 'My batches';
$intro = $is_my
	? 'Batches you are enrolled in (students) or created/assigned (teachers). Sign in required.'
	: 'All active batches on the platform. Use search to filter by name. Sign in required.';
?>
<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=7">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo html_escape(rtrim(base_url(), '/') . '/'); ?>" aria-label="Back to home"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title"><?php echo html_escape($page_title); ?></span>
	</div>

	<div class="inst-detail-container inst-list-page-container">
		<div class="inst-detail-panel inst-list-directory-panel">
			<div class="inst-panel-head">
				<h3 id="batch_title"><?php echo html_escape($page_title); ?></h3>
				<a class="inst-see-all" href="<?php echo html_escape($alt_link); ?>"><?php echo html_escape($alt_label); ?></a>
			</div>
			<!-- <p class="inst-list-intro"><?php echo html_escape($intro); ?></p> -->
			<div class="inst-list-filter-bar" role="search">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item inst-list-filter-grow">
						<label for="batch_my_search">Search</label>
						<input type="search" id="batch_my_search" class="edu_form_field" placeholder="Search by batch name…" autocomplete="off">
					</div>
				</div>
				<div class="inst-list-filter-actions">
					<button type="button" id="batch_my_apply" class="edu_btn edu_btn_black inst-list-apply-btn">Search</button>
				</div>
			</div>
			<div id="batch_my_list_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="batch_my_card_stack" class="inst-card-grid"></div>
			<div class="inst-list-pagination">
				<button type="button" id="batch_my_prev" class="edu_btn edu_btn_black" disabled>Previous</button>
				<span id="batch_my_page_info" class="inst-muted small"></span>
				<button type="button" id="batch_my_next" class="edu_btn edu_btn_black" disabled>Next</button>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	var dataUrl = <?php echo json_encode(isset($batch_list_data_url) ? $batch_list_data_url : ''); ?>;
	var accessToken = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var detailsBase = <?php echo json_encode(isset($batch_details_base) ? rtrim($batch_details_base, '/') : ''); ?>;
	var isTeacher = <?php echo isset($is_teacher) && $is_teacher ? 'true' : 'false'; ?>;
	var listFlag = <?php echo json_encode($lf); ?>;
	// Owner badges / edit-delete actions / created-vs-assigned split only make sense on the "my" page.
	var isMy = (listFlag === 'my');
	var page = 1;
	var limit = 10;
	var totalPages = 1;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function postJson(url, body) {
		body = body || {};
		// Direct API call needs the Bearer token (the API is shared with the mobile app).
		if (accessToken && !body.access_token) { body.access_token = accessToken; }
		var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
		if (accessToken) { headers.Authorization = 'Bearer ' + accessToken; }
		return fetch(url, {
			method: 'POST',
			headers: headers,
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); });
	}
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function deleteBatch(batchId) {
		if (!confirm('Are you sure you want to delete this batch?')) {
			return;
		}
		postJson('<?php echo site_url('batch/delete-batch'); ?>', { batch_id: batchId })
			.then(function (j) {
				if (ok(j.status)) {
					alert('Batch deleted successfully!');
					page = 1;
					load();
				} else {
					alert('Error: ' + (j.msg || 'Failed to delete batch'));
				}
			})
			.catch(function (e) {
				alert('Error deleting batch: ' + e.message);
			});
	}
	function renderBatchCard(stack, batch, isCreated) {
		var id = batch.batch_id || batch.batchId || batch.id || 0;
		var href = detailsBase + '?batch_id=' + encodeURIComponent(id);
		var name = batch.batchName || batch.title || batch.batch_name || batch.name || '';
		var imgUrl = (batch.batchImage || batch.logo || batch.batch_image || '').trim();
		var thumb = imgUrl
			? '<div class="batch-card-image"><img src="' + esc(imgUrl) + '" alt="' + esc(name) + '"></div>'
			: '<div class="batch-card-image batch-card-image-default"><i class="fas fa-book"></i></div>';
		var institute = batch.institute_name || batch.instituteName || '';
		var instructor = batch.instructor || '';
		var schedule = batch.schedule || '';
		var sd = batch.startDate || batch.start_date || '';
		var ed = batch.endDate || batch.end_date || '';
		var dates = '';
		if (sd || ed) {
			dates = esc(sd && ed ? (sd + ' – ' + ed) : (sd || ed));
		}

		// Lifecycle badge. is_expired is derived server-side from end_date/end_time; batch_status
		// is the admin's own Active/Inactive switch. Expired wins — the batch is over either way.
		var lifecycle = batch.lifecycle_status || '';
		var isExpired = lifecycle === 'expired' || batch.is_expired === 1 || batch.is_expired === '1' || batch.is_expired === true;
		var isDisabled = !isExpired && (lifecycle === 'inactive' || batch.batch_status === 0 || batch.batch_status === '0');
		var statusLabel = '';
		if (isExpired) {
			statusLabel = '<span class="batch-badge batch-badge-expired">Expired</span>';
		} else if (isDisabled) {
			statusLabel = '<span class="batch-badge batch-badge-inactive">Inactive</span>';
		}

		var div = document.createElement('div');
		div.className = 'batch-card' + (isExpired ? ' batch-card-expired' : '');

		var actions = '';
		var ownerLabel = '';
		// Teacher management UI is limited to the "my" page.
		if (isMy && isTeacher && isCreated) {
			ownerLabel = '<span class="batch-badge batch-badge-created">Created by you</span>';
			actions = '<div class="batch-card-actions">' +
				'<a href="<?php echo base_url('batch/create/'); ?>' + id + '" class="batch-btn batch-btn-primary">Edit</a>' +
				'<button type="button" class="batch-btn batch-btn-danger" onclick="deleteBatch(' + id + ')">Delete</button>' +
				'</div>';
		} else if (isMy && isTeacher && !isCreated) {
			ownerLabel = '<span class="batch-badge batch-badge-assigned">Assigned</span>';
		}

		div.innerHTML = thumb +
			'<div class="batch-card-content">' +
			'<div class="batch-card-header">' +
			'<h3 class="batch-card-title">' + esc(name) + '</h3>' +
			statusLabel +
			ownerLabel +
			'</div>' +
			(dates ? '<p class="batch-card-dates"><i class="far fa-calendar"></i> ' + dates + '</p>' : '') +
			(institute ? '<p class="batch-card-meta"><strong>Institute:</strong> ' + esc(institute) + '</p>' : '') +
			(instructor ? '<p class="batch-card-meta"><strong>Instructor:</strong> ' + esc(instructor) + '</p>' : '') +
			(schedule ? '<p class="batch-card-meta"><strong>Schedule:</strong> ' + esc(schedule) + '</p>' : '') +
			'</div>' +
			(actions ? '' : '<span class="batch-card-chevron"><i class="fas fa-chevron-right"></i></span>') +
			actions;

		div.style.cursor = 'pointer';
		div.addEventListener('click', function (e) {
			if (!e.target.closest('.batch-btn')) {
				window.location.href = href;
			}
		});

		stack.appendChild(div);
	}
	function render(list) {
		var stack = document.getElementById('batch_my_card_stack');
		stack.innerHTML = '';
		if (!list || !list.length) {
			stack.innerHTML = '<p class="inst-muted mb-0 px-2">No batches found.</p>';
			return;
		}

		// "All" page (or non-teacher): just render plain cards.
		if (!isMy || !isTeacher) {
			list.forEach(function (it) { renderBatchCard(stack, it, false); });
			return;
		}

		// "My" page for teachers: split created vs assigned with section headers.
		var createdBatches = [];
		var assignedBatches = [];
		list.forEach(function (it) {
			if (it.ownerType === 'created') { createdBatches.push(it); }
			else { assignedBatches.push(it); }
		});

		if (createdBatches.length > 0) {
			if (assignedBatches.length > 0) {
				var header1 = document.createElement('h4');
				header1.style.marginTop = '20px';
				header1.style.marginBottom = '10px';
				header1.textContent = 'Created by You';
				stack.appendChild(header1);
			}
			createdBatches.forEach(function (it) { renderBatchCard(stack, it, true); });
		}
		if (assignedBatches.length > 0) {
			if (createdBatches.length > 0) {
				var header2 = document.createElement('h4');
				header2.style.marginTop = '20px';
				header2.style.marginBottom = '10px';
				header2.textContent = 'Assigned by Admin';
				stack.appendChild(header2);
			}
			assignedBatches.forEach(function (it) { renderBatchCard(stack, it, false); });
		}
	}
	function load() {
		var body = {
			page: page,
			limit: limit,
			search: (document.getElementById('batch_my_search').value || '').trim(),
			list: listFlag
		};
		document.getElementById('batch_my_list_msg').textContent = 'Loading…';
		postJson(dataUrl, body).then(function (j) {
			var batches = (j.data && j.data.enrolled_batches) ? j.data.enrolled_batches : [];
			var pag = (j.data && j.data.pagination) ? j.data.pagination : {};
			if (!ok(j.status)) {
				document.getElementById('batch_my_list_msg').textContent = j.msg || j.message || 'Could not load batches.';
				render([]);
				document.getElementById('batch_my_page_info').textContent = '';
				document.getElementById('batch_my_prev').disabled = true;
				document.getElementById('batch_my_next').disabled = true;
				return;
			}
			document.getElementById('batch_my_list_msg').textContent = '';
			render(batches);
			totalPages = Math.max(1, parseInt(pag.totalPages, 10) || 1);
			document.getElementById('batch_my_page_info').textContent = 'Page ' + page + ' / ' + totalPages;
			document.getElementById('batch_my_prev').disabled = page <= 1;
			document.getElementById('batch_my_next').disabled = page >= totalPages;
		}).catch(function () {
			document.getElementById('batch_my_list_msg').textContent = 'Network error.';
			render([]);
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		// Teachers get a "Create Batch" button only on the "my" page.
		if (isMy && isTeacher) {
			var panelHead = document.querySelector('.inst-panel-head');
			if (panelHead) {
				var createBtn = document.createElement('a');
				createBtn.href = '<?php echo base_url('batch/create'); ?>';
				createBtn.className = 'inst-see-all';
				createBtn.textContent = 'Create Batch';
				panelHead.appendChild(createBtn);
			}
		}

		document.getElementById('batch_my_apply').addEventListener('click', function () { page = 1; load(); });
		document.getElementById('batch_my_search').addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				page = 1;
				load();
			}
		});
		document.getElementById('batch_my_prev').addEventListener('click', function () { if (page > 1) { page--; load(); } });
		document.getElementById('batch_my_next').addEventListener('click', function () { if (page < totalPages) { page++; load(); } });
		load();
	});
})();
</script>

<style>
.batch-card {
	display: flex;
	flex-direction: column;
	background: #fff;
	border-radius: 8px;
	overflow: hidden;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	transition: all 0.3s ease;
	position: relative;
	height: 100%;
}

.batch-card:hover {
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
	transform: translateY(-2px);
}

.batch-card-image {
	width: 100%;
	height: 200px;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	overflow: hidden;
	display: flex;
	align-items: center;
	justify-content: center;
}

.batch-card-image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.batch-card-image-default {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	font-size: 48px;
}

.batch-card-content {
	flex: 1;
	padding: 16px;
	display: flex;
	flex-direction: column;
}

.batch-card-header {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: 8px;
	margin-bottom: 12px;
}

.batch-card-title {
	margin: 0;
	font-size: 16px;
	font-weight: 600;
	color: #333;
	flex: 1;
	line-height: 1.4;
}

.batch-badge {
	display: inline-block;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 600;
	white-space: nowrap;
}

.batch-badge-created {
	background: #e3f2fd;
	color: #1976d2;
}

.batch-badge-assigned {
	background: #f5f5f5;
	color: #666;
}

.batch-badge-expired {
	background: #fdecea;
	color: #c62828;
}

.batch-badge-inactive {
	background: #f5f5f5;
	color: #8a8a8a;
}

/* Ended batches stay clickable (past content is still reachable) but read as archived. */
.batch-card-expired .batch-card-image {
	filter: grayscale(0.8);
	opacity: 0.75;
}

.batch-card-expired .batch-card-title {
	color: #6b6b6b;
}

.batch-card-dates {
	margin: 8px 0;
	font-size: 13px;
	color: #666;
	display: flex;
	align-items: center;
	gap: 6px;
}

.batch-card-meta {
	margin: 6px 0;
	font-size: 13px;
	color: #777;
}

.batch-card-actions {
	display: flex;
	gap: 8px;
	margin-top: auto;
	padding-top: 12px;
	border-top: 1px solid #eee;
}

.batch-btn {
	flex: 1;
	padding: 8px 12px;
	border: none;
	border-radius: 4px;
	font-size: 13px;
	font-weight: 600;
	cursor: pointer;
	text-decoration: none;
	text-align: center;
	transition: all 0.2s ease;
	display: inline-block;
}

.batch-btn-primary {
	background: #667eea;
	color: white;
}

.batch-btn-primary:hover {
	background: #5568d3;
}

.batch-btn-danger {
	background: #f44336;
	color: white;
}

.batch-btn-danger:hover {
	background: #da190b;
}

.batch-card-chevron {
	position: absolute;
	top: 50%;
	right: 16px;
	transform: translateY(-50%);
	color: #ccc;
	font-size: 20px;
}

.inst-card-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
	gap: 20px;
	margin-bottom: 20px;
}

@media (max-width: 768px) {
	.inst-card-grid {
		grid-template-columns: 1fr;
	}

	.batch-card-image {
		height: 150px;
	}
}
</style>
