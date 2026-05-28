<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=9">
<style>
.es-shell { max-width: 960px; margin: 0 auto; }
.es-stat {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 14px;
	padding: 16px;
	margin-bottom: 16px;
	text-align: center;
	box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.es-stat strong { display: block; font-size: 1.6rem; color: #0f172a; }
.es-stat span { font-size: 0.85rem; color: #64748b; font-weight: 600; }
.es-toolbar {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: flex-end;
	margin-bottom: 14px;
}
.es-toolbar label { display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 4px; }
.es-row {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px 14px;
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 12px;
	margin-bottom: 10px;
	box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.es-row img {
	width: 44px;
	height: 44px;
	border-radius: 50%;
	object-fit: cover;
	background: #eef2ff;
}
.es-row-body { flex: 1; min-width: 0; }
.es-row-body h4 { margin: 0 0 4px; font-size: 0.95rem; font-weight: 700; color: #0f172a; }
.es-row-body p { margin: 0; font-size: 0.82rem; color: #64748b; }
.es-score {
	font-weight: 700;
	color: #2563eb;
	font-size: 0.9rem;
}
@media (max-width: 640px) {
	.es-toolbar > div { flex: 1 1 100%; }
	.es-row { flex-wrap: wrap; }
	.es-row .btn { width: 100%; }
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="<?php echo html_escape($teacher_exams_url . ($batch_id > 0 ? '?batch_id=' . (int) $batch_id : '')); ?>" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Exam submissions</div>
	</div>
	<div class="inst-detail-container es-shell">
		<div id="esMsg" class="inst-muted text-center py-3">Loading…</div>
		<div id="esBody" class="inst-detail-hidden">
			<div class="inst-detail-summary-card" style="margin-bottom:14px;">
				<h2 id="esExamName" style="margin:0 0 6px;font-size:1.1rem;font-weight:700;"></h2>
				<p id="esExamMeta" class="inst-batch-meta" style="margin:0;"></p>
			</div>
			<div class="es-stat">
				<strong id="esSubmittedCount">0</strong>
				<span>Students submitted</span>
			</div>
			<div class="es-toolbar">
				<div style="flex:1 1 180px;">
					<label for="esSearch">Search student</label>
					<input type="search" id="esSearch" class="form-control" placeholder="Name…">
				</div>
				<div>
					<label for="esSort">Sort by</label>
					<select id="esSort" class="form-control">
						<option value="date_desc">Submitted (newest)</option>
						<option value="date_asc">Submitted (oldest)</option>
						<option value="name_asc">Name (A–Z)</option>
						<option value="name_desc">Name (Z–A)</option>
						<option value="percentage_desc">Score (high)</option>
						<option value="percentage_asc">Score (low)</option>
					</select>
				</div>
				<div>
					<label>&nbsp;</label>
					<button type="button" class="btn btn-outline-secondary" id="esOmrBlank">Download ORM Sheet</button>
				</div>
				<div>
					<label>&nbsp;</label>
					<button type="button" class="btn btn-primary" id="esReload">Refresh</button>
				</div>
			</div>
			<div id="esList"></div>
			<div id="esPagination" class="text-center mt-3"></div>
		</div>
	</div>
</div>
<script src="<?php echo base_url('assets/js/exam-omr-download.js?v=2'); ?>"></script>
<script>
(function () {
	'use strict';
	var examId = <?php echo (int) (isset($exam_id) ? $exam_id : 0); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var listUrl = <?php echo json_encode((string) (isset($exam_submission_list_api_url) ? $exam_submission_list_api_url : '')); ?>;
	var detailBase = <?php echo json_encode((string) (isset($submission_detail_url) ? $submission_detail_url : '')); ?>;
	var omrApiUrl = <?php echo json_encode((string) (isset($exam_omr_sheet_api_url) ? $exam_omr_sheet_api_url : site_url('api/batch/exam-omr-sheet'))); ?>;
	var currentPage = 1;
	var searchTimer = null;

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(v) {
		var d = document.createElement('div');
		d.textContent = v == null ? '' : String(v);
		return d.innerHTML;
	}
	function detailHref(submissionId) {
		var q = '?submission_id=' + encodeURIComponent(submissionId) + '&exam_id=' + encodeURIComponent(examId);
		if (batchId > 0) { q += '&batch_id=' + encodeURIComponent(batchId); }
		return detailBase + q;
	}
	function load(page) {
		if (examId < 1) {
			document.getElementById('esMsg').textContent = 'Invalid exam.';
			return;
		}
		currentPage = page || 1;
		document.getElementById('esMsg').textContent = 'Loading…';
		document.getElementById('esBody').classList.add('inst-detail-hidden');
		fetch(listUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({
				access_token: token,
				exam_id: examId,
				search: document.getElementById('esSearch').value.trim(),
				sort: document.getElementById('esSort').value,
				page: currentPage,
				limit: 30
			})
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!ok(j.status) || !j.data) {
				document.getElementById('esMsg').textContent = (j && j.msg) || 'Could not load submissions.';
				return;
			}
			var exam = j.data.exam || {};
			document.getElementById('esExamName').textContent = exam.name || 'Exam';
			document.getElementById('esExamMeta').textContent = [
				exam.batchName || '',
				exam.scheduledDate || '',
				exam.scheduledTime || '',
				(exam.totalQuestion ? exam.totalQuestion + ' questions' : ''),
				(exam.timeDuration ? exam.timeDuration + ' min' : '')
			].filter(Boolean).join(' · ');
			var stats = j.data.stats || {};
			document.getElementById('esSubmittedCount').textContent = String(stats.submittedCount != null ? stats.submittedCount : (j.data.submissions || []).length);
			var rows = j.data.submissions || [];
			if (!rows.length) {
				document.getElementById('esList').innerHTML = '<p class="inst-muted text-center py-3">No students have submitted this exam yet.</p>';
			} else {
				var html = '';
				rows.forEach(function (row) {
					var img = row.studentImageUrl
						? '<img src="' + esc(row.studentImageUrl) + '" alt="">'
						: '<img src="" alt="" style="visibility:hidden;width:44px;height:44px;">';
					html += '<div class="es-row">' + img +
						'<div class="es-row-body">' +
							'<h4>' + esc(row.studentName) + '</h4>' +
							'<p>Submitted: ' + esc(row.submittedAt || '—') + ' · Attempted: ' + esc(row.attemptedQuestion) + '/' + esc(row.totalQuestion) +
							(row.timeTaken ? ' · Time: ' + esc(row.timeTaken) : '') + '</p>' +
						'</div>' +
						'<div class="es-score">' + esc(row.percentage) + '%</div>' +
						'<a class="btn btn-sm btn-primary" href="' + esc(detailHref(row.submissionId)) + '">View</a>' +
						'<button type="button" class="btn btn-sm btn-outline-secondary es-omr-filled" data-sid="' + esc(row.submissionId) + '">Download ORM Sheet</button>' +
					'</div>';
				});
				document.getElementById('esList').innerHTML = html;
				document.querySelectorAll('.es-omr-filled').forEach(function (btn) {
					btn.addEventListener('click', function () {
						var sid = parseInt(btn.getAttribute('data-sid'), 10);
						if (sid < 1 || typeof downloadExamOmrSheet !== 'function') { return; }
						var old = btn.textContent;
						btn.disabled = true;
						btn.textContent = '…';
						downloadExamOmrSheet({
							apiUrl: omrApiUrl, token: token, examId: examId,
							submissionId: sid, mode: 'filled', showCorrect: true
						}).catch(function (err) {
							alert(err && err.message ? err.message : 'Could not download ORM sheet.');
						}).then(function () { btn.disabled = false; btn.textContent = old; });
					});
				});
			}
			var pg = j.data.pagination || {};
			var pagHtml = '';
			if (pg.total_pages > 1) {
				if (currentPage > 1) {
					pagHtml += '<button type="button" class="btn btn-sm btn-outline-secondary es-page" data-page="' + (currentPage - 1) + '">Prev</button> ';
				}
				pagHtml += '<span class="mx-2 small text-muted">Page ' + currentPage + ' / ' + pg.total_pages + '</span>';
				if (currentPage < pg.total_pages) {
					pagHtml += ' <button type="button" class="btn btn-sm btn-outline-secondary es-page" data-page="' + (currentPage + 1) + '">Next</button>';
				}
			}
			document.getElementById('esPagination').innerHTML = pagHtml;
			document.querySelectorAll('.es-page').forEach(function (btn) {
				btn.addEventListener('click', function () {
					load(parseInt(btn.getAttribute('data-page'), 10));
				});
			});
			document.getElementById('esMsg').classList.add('inst-detail-hidden');
			document.getElementById('esBody').classList.remove('inst-detail-hidden');
		}).catch(function () {
			document.getElementById('esMsg').textContent = 'Network error.';
		});
	}
	document.getElementById('esOmrBlank').addEventListener('click', function () {
		var btn = document.getElementById('esOmrBlank');
		if (typeof downloadExamOmrSheet !== 'function') { alert('Download helper not loaded.'); return; }
		var old = btn.textContent;
		btn.disabled = true;
		btn.textContent = 'Preparing…';
		downloadExamOmrSheet({ apiUrl: omrApiUrl, token: token, examId: examId, mode: 'blank' }).catch(function (err) {
			alert(err && err.message ? err.message : 'Could not download ORM sheet.');
		}).then(function () { btn.disabled = false; btn.textContent = old; });
	});
	document.getElementById('esReload').addEventListener('click', function () { load(1); });
	document.getElementById('esSort').addEventListener('change', function () { load(1); });
	document.getElementById('esSearch').addEventListener('input', function () {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(function () { load(1); }, 350);
	});
	load(1);
})();
</script>
