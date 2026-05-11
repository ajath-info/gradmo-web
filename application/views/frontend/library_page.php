<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Library</div>
	</div>


	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card">
					<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
						<input type="text" id="libSearch" class="form-control" placeholder="Search title, topic or subject..." style="max-width:320px;">
						<button type="button" id="libSearchBtn" class="btn btn-primary">Search</button>
					</div>
				</div>
				<div id="libMsg" class="small text-muted"></div>
				<div id="libList" class="inst-card-grid"></div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	'use strict';
	var endpoint = <?php echo json_encode((string) (isset($library_api_url) ? $library_api_url : site_url('api/batch/library-list'))); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var myBatchesUrl = <?php echo json_encode((string) (isset($my_batches_url) ? $my_batches_url : site_url('batch/mylist'))); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var msgEl = document.getElementById('libMsg');
	var listEl = document.getElementById('libList');
	var searchEl = document.getElementById('libSearch');

	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
		});
	}

	function setMsg(text, isError) {
		msgEl.className = isError ? 'small text-danger' : 'small text-muted';
		msgEl.textContent = text || '';
	}

	function renderItem(item) {
		var title = item.title || 'Untitled';
		var sub = [item.subject || '', item.topic || '', item.fileSize || ''].filter(Boolean).join(' | ');
		var date = item.addedAt || '';
		var downloadUrl = item.downloadUrl || '';
		var html = '' +
			'<div class="inst-batch-card">' +
				'<div class="inst-batch-logo"><i class="fas fa-book-open"></i></div>' +
				'<div class="inst-batch-content">' +
					'<h4>' + esc(title) + '</h4>' +
					'<p class="inst-batch-meta">' + esc(sub) + '</p>' +
					'<p class="inst-batch-desc">' + esc(date) + '</p>';
		if (downloadUrl) {
			html += '<a class="btn btn-sm btn-outline-primary" href="' + esc(downloadUrl) + '" target="_blank" rel="noopener">View PDF</a>';
		}
		html += '</div></div>';
		return html;
	}

	function loadLibrary() {
		if (batchId < 1) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">Select a batch first to open library. <a href="' + esc(myBatchesUrl) + '">Go to My Batches</a></div>';
			setMsg('', false);
			return;
		}
		setMsg('Loading library...', false);
		listEl.innerHTML = '';
		var payload = {
			batch_id: batchId,
			search: (searchEl.value || '').trim(),
			page: 1,
			limit: 30
		};
		fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify(payload)
		}).then(function (r) {
			return r.json();
		}).then(function (res) {
			if (!res || !(res.status === true || res.status === 'true')) {
				throw new Error((res && (res.msg || res.message)) || 'Unable to load library');
			}
			var data = res.data || {};
			var rows = Array.isArray(data.library) ? data.library : [];
			if (!rows.length) {
				listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">No library books found for this batch.</div>';
				setMsg('', false);
				return;
			}
			var html = '';
			for (var i = 0; i < rows.length; i++) {
				html += renderItem(rows[i]);
			}
			listEl.innerHTML = html;
			setMsg('', false);
		}).catch(function (err) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-danger">Could not fetch library list.</div>';
			setMsg(err && err.message ? err.message : 'Request failed', true);
		});
	}

	document.getElementById('libSearchBtn').addEventListener('click', loadLibrary);
	loadLibrary();
})();
</script>
