<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.hw-list-shell { max-width: 960px; margin: 0 auto; }
.hw-card-link {
	display: block;
	text-decoration: none !important;
	color: inherit;
}
.hw-card-link .inst-batch-card {
	transition: box-shadow 0.15s ease, transform 0.15s ease;
	cursor: pointer;
}
.hw-card-link:hover .inst-batch-card {
	box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
	transform: translateY(-1px);
}
.hw-status {
	display: inline-flex;
	align-items: center;
	padding: 3px 10px;
	border-radius: 999px;
	font-size: 0.72rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.03em;
	margin-top: 6px;
}
.hw-status-pending { background: #fef3c7; color: #92400e; }
.hw-status-submitted { background: #dbeafe; color: #1e40af; }
.hw-status-evaluated { background: #dcfce7; color: #166534; }
.hw-card-actions {
	margin-top: 10px;
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	align-items: center;
}
@media (max-width: 575px) {
	.hw-toolbar { flex-direction: column; align-items: stretch !important; }
	.hw-toolbar .form-control { max-width: none !important; width: 100%; }
	.hw-toolbar .btn { width: 100%; }
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Homework</div>
	</div>
	<div class="inst-detail-container hw-list-shell">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card">
					<div class="hw-toolbar" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
						<input type="date" id="hwDate" class="form-control" style="max-width:220px;">
						<button type="button" class="btn btn-primary" id="hwReloadBtn">Refresh</button>
					</div>
				</div>
				<div id="hwMsg" class="small text-muted"></div>
				<div id="hwList" class="inst-card-grid"></div>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	'use strict';
	var endpoint = <?php echo json_encode((string) (isset($homework_api_url) ? $homework_api_url : site_url('api/batch/homework-list'))); ?>;
	var mySubUrl = <?php echo json_encode((string) (isset($my_submissions_api_url) ? $my_submissions_api_url : site_url('api/batch/my-homework-submissions'))); ?>;
	var viewBase = <?php echo json_encode((string) (isset($homework_view_url) ? $homework_view_url : site_url('homework/view'))); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var submissionMap = {};

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
		});
	}
	function setMsg(text, error) {
		var el = document.getElementById('hwMsg');
		el.className = error ? 'small text-danger' : 'small text-muted';
		el.textContent = text || '';
	}
	function viewUrl(homeworkId) {
		var q = '?homework_id=' + encodeURIComponent(homeworkId);
		if (batchId > 0) { q += '&batch_id=' + encodeURIComponent(batchId); }
		return viewBase + q;
	}
	function statusInfo(homeworkId) {
		var sub = submissionMap[String(homeworkId)] || submissionMap[homeworkId];
		if (!sub) {
			return { cls: 'hw-status-pending', label: 'Not submitted' };
		}
		if (sub.evalStatus === 1 || sub.evalStatus === '1') {
			return { cls: 'hw-status-evaluated', label: 'Evaluated' };
		}
		return { cls: 'hw-status-submitted', label: 'Submitted' };
	}
	function homeworkCard(item) {
		var id = item.id;
		var title = item.subjectName || 'Homework';
		var teacher = item.name || '';
		var date = item.date || '';
		var desc = item.description || '';
		var pdfUrl = item.attachmentUrl || '';
		var st = statusInfo(id);
		var pdfBlock = pdfUrl
			? '<span class="btn btn-sm btn-outline-secondary" onclick="event.preventDefault();event.stopPropagation();window.open(\'' + esc(pdfUrl) + '\',\'_blank\');"><i class="fas fa-file-pdf"></i> Handout</span>'
			: '';
		return '' +
			'<a class="hw-card-link" href="' + esc(viewUrl(id)) + '">' +
				'<div class="inst-batch-card">' +
					'<div class="inst-batch-logo"><i class="fas fa-book-open"></i></div>' +
					'<div class="inst-batch-content">' +
						'<h4>' + esc(title) + '</h4>' +
						'<p class="inst-batch-meta">' + esc(teacher) + (date ? ' | ' + esc(date) : '') + '</p>' +
						'<span class="hw-status ' + st.cls + '">' + esc(st.label) + '</span>' +
						'<p class="inst-batch-desc">' + esc(desc.length > 120 ? desc.slice(0, 120) + '…' : desc) + '</p>' +
						'<div class="hw-card-actions">' +
							pdfBlock +
							'<span class="btn btn-sm btn-success">View &amp; submit</span>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</a>';
	}
	function loadSubmissions() {
		var body = { access_token: token, page: 1, limit: 200 };
		if (batchId > 0) { body.batch_id = batchId; }
		return fetch(mySubUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (res) {
			submissionMap = {};
			if (!ok(res.status) || !res.data || !res.data.submissions) { return; }
			res.data.submissions.forEach(function (s) {
				var hid = s.homeworkId || s.homework_id;
				if (hid) { submissionMap[String(hid)] = s; }
			});
		}).catch(function () {});
	}
	function loadHomework() {
		setMsg('Loading homework…', false);
		document.getElementById('hwList').innerHTML = '';
		var payload = { access_token: token, page: 1, limit: 100 };
		if (batchId > 0) { payload.batch_id = batchId; }
		if (document.getElementById('hwDate').value) { payload.date = document.getElementById('hwDate').value; }
		Promise.all([
			loadSubmissions(),
			fetch(endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
				body: JSON.stringify(payload)
			}).then(function (r) { return r.json(); })
		]).then(function (results) {
			var res = results[1];
			var rows = (res && Array.isArray(res.homeWork)) ? res.homeWork : [];
			if (!rows.length) {
				document.getElementById('hwList').innerHTML = '<div class="inst-detail-summary-card text-muted">No homework found.</div>';
				setMsg((res && res.msg) ? String(res.msg) : '', false);
				return;
			}
			document.getElementById('hwList').innerHTML = rows.map(homeworkCard).join('');
			setMsg('', false);
		}).catch(function (err) {
			setMsg(err && err.message ? err.message : 'Failed to load homework', true);
		});
	}
	document.getElementById('hwReloadBtn').addEventListener('click', loadHomework);
	loadHomework();
})();
</script>
