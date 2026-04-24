<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Homework</div>
	</div>


	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card">
					<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
						<input type="date" id="hwDate" class="form-control" style="max-width:220px;">
						<button type="button" class="btn btn-primary" id="hwReloadBtn">Refresh</button>
					</div>
				</div>
				<div id="hwMsg" class="small text-muted"></div>
				<div id="hwList"></div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	'use strict';
	var endpoint = '<?php echo site_url('api/batch/homework-list'); ?>';
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var dateEl = document.getElementById('hwDate');
	var msgEl = document.getElementById('hwMsg');
	var listEl = document.getElementById('hwList');

	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
		});
	}

	function setMsg(text, error) {
		msgEl.className = error ? 'small text-danger' : 'small text-muted';
		msgEl.textContent = text || '';
	}

	function homeworkCard(item) {
		var title = item.subjectName || 'Homework';
		var teacher = item.name || '';
		var date = item.date || '';
		var desc = item.description || '';
		return '' +
			'<div class="inst-batch-card">' +
				'<div class="inst-batch-logo"><i class="fas fa-book-open"></i></div>' +
				'<div class="inst-batch-content">' +
					'<h4>' + esc(title) + '</h4>' +
					'<p class="inst-batch-meta">' + esc(teacher) + (date ? ' | ' + esc(date) : '') + '</p>' +
					'<p class="inst-batch-desc">' + esc(desc) + '</p>' +
				'</div>' +
			'</div>';
	}

	function loadHomework() {
		setMsg('Loading homework...', false);
		listEl.innerHTML = '';
		var payload = { page: 1, limit: 100 };
		if (batchId > 0) {
			payload.batch_id = batchId;
		}
		if (dateEl.value) {
			payload.date = dateEl.value;
		}

		fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify(payload)
		})
		.then(function (r) { return r.json(); })
		.then(function (res) {
			if (!res || !(res.status === true || res.status === 'true')) {
				throw new Error((res && (res.msg || res.message)) || 'Unable to load homework');
			}
			var rows = Array.isArray(res.homeWork) ? res.homeWork : [];
			if (!rows.length) {
				listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">No homework found.</div>';
				setMsg('', false);
				return;
			}
			var html = '';
			for (var i = 0; i < rows.length; i++) {
				html += homeworkCard(rows[i]);
			}
			listEl.innerHTML = html;
			setMsg('', false);
		})
		.catch(function (err) {
			setMsg(err && err.message ? err.message : 'Failed to load homework', true);
			listEl.innerHTML = '<div class="inst-detail-summary-card text-danger">Could not fetch homework.</div>';
		});
	}

	document.getElementById('hwReloadBtn').addEventListener('click', loadHomework);
	loadHomework();
})();
</script>
