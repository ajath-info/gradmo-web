<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Upcoming Exams</div>
	</div>

	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card">
					<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
						<input type="text" id="exSearch" class="form-control" placeholder="Search exam name, duration..." style="max-width:320px;">
						<button type="button" id="exSearchBtn" class="btn btn-primary">Search</button>
					</div>
				</div>
				<div id="exMsg" class="small text-muted"></div>
				<div id="exList" class="inst-card-grid"></div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	'use strict';
	var endpoint = <?php echo json_encode((string) (isset($upcoming_exam_list_api_url) ? $upcoming_exam_list_api_url : site_url('api/batch/upcoming-exam-list'))); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var msgEl = document.getElementById('exMsg');
	var listEl = document.getElementById('exList');
	var searchEl = document.getElementById('exSearch');

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
		var title = item.name || 'Exam';
		var date = item.scheduledDate || '';
		var time = item.scheduledTime || '';
		var duration = item.timeDuration ? ('Duration: ' + item.timeDuration) : '';
		var marks = item.totalMarks != null ? ('Total marks: ' + item.totalMarks) : '';
		var questions = item.totalQuestion != null ? ('Questions: ' + item.totalQuestion) : '';
		return '' +
			'<div class="inst-batch-card">' +
				'<div class="inst-batch-logo"><i class="fas fa-file-alt"></i></div>' +
				'<div class="inst-batch-content">' +
					'<h4>' + esc(title) + '</h4>' +
					'<p class="inst-batch-meta">' + esc([date, time].filter(Boolean).join(' | ')) + '</p>' +
					'<p class="inst-batch-desc">' + esc([duration, questions, marks].filter(Boolean).join(' | ')) + '</p>' +
				'</div>' +
			'</div>';
	}
	function loadExams() {
		if (batchId < 1) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">Invalid batch id.</div>';
			return;
		}
		setMsg('Loading exams...', false);
		listEl.innerHTML = '';
		var payload = { batch_id: batchId, search: (searchEl.value || '').trim(), page: 1, limit: 100 };
		fetch(endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(payload)
		}).then(function (r) { return r.json(); }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var rows = ok && res.data && Array.isArray(res.data.upcomingExams) ? res.data.upcomingExams : [];
			if (!ok) throw new Error((res && (res.msg || res.message)) || 'Unable to load exams');
			if (!rows.length) {
				listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">No upcoming exams for this batch.</div>';
				setMsg('', false);
				return;
			}
			var html = '';
			for (var i = 0; i < rows.length; i++) html += renderItem(rows[i]);
			listEl.innerHTML = html;
			setMsg('', false);
		}).catch(function (err) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-danger">Could not fetch exams.</div>';
			setMsg(err && err.message ? err.message : 'Request failed', true);
		});
	}
	document.getElementById('exSearchBtn').addEventListener('click', loadExams);
	loadExams();
})();
</script>
