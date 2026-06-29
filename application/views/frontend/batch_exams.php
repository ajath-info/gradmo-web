<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.stu-exam-page .stu-exam-shell {
	max-width: 1180px;
	margin: 0 auto;
}
.stu-exam-page .inst-detail-container {
	max-width: none;
	padding-top: 8px;
}
.stu-exam-msg {
	font-size: 14px;
	margin-bottom: 14px;
}
.stu-exam-msg.is-error {
	color: #cf3344;
}
.stu-exam-sections-grid {
	display: flex;
	flex-direction: column;
	gap: 20px;
}
@media (min-width: 992px) {
	.stu-exam-sections-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 24px;
		align-items: start;
	}
}
.stu-exam-section {
	background: #eaf6ff;
	border-radius: 20px;
	padding: 16px;
}
.stu-exam-section-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12px;
	padding: 4px 6px;
}
.stu-exam-section-head h2 {
	font-size: 1.1rem;
	font-weight: 700;
	color: #1f2433;
	margin: 0;
}
.stu-exam-count {
	font-size: 12px;
	color: #5f6b87;
}
.stu-exam-stack {
	display: grid;
	gap: 16px;
	grid-template-columns: 1fr;
}
@media (min-width: 576px) {
	.stu-exam-stack {
		grid-template-columns: repeat(2, 1fr);
	}
}
@media (min-width: 1200px) {
	.stu-exam-sections-grid .stu-exam-stack {
		grid-template-columns: 1fr;
	}
}
.stu-exam-card {
	background: #fff;
	border-radius: 16px;
	padding: 14px;
	box-shadow: 0 8px 20px rgba(38, 76, 157, 0.08);
	overflow: hidden;
	height: 100%;
	display: flex;
	flex-direction: column;
}
.stu-exam-image {
	width: 100%;
	height: 140px;
	border-radius: 12px;
	object-fit: cover;
	display: block;
	background: linear-gradient(135deg, #dde7ff, #f4f8ff);
}
.stu-exam-image.is-empty {
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 32px;
	color: #4c78f0;
}
.stu-exam-name {
	font-size: 1rem;
	font-weight: 700;
	color: #323643;
	margin: 12px 0 8px;
}
.stu-exam-meta-row {
	display: flex;
	flex-wrap: wrap;
	gap: 8px 16px;
	font-size: 0.88rem;
	color: #535c73;
	margin-bottom: 6px;
}
.stu-exam-meta-row strong {
	color: #212634;
	font-weight: 700;
}
.stu-exam-pills {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin: 10px 0 12px;
}
.stu-exam-pill {
	border-radius: 999px;
	padding: 4px 12px;
	font-size: 11px;
	font-weight: 600;
	background: #fff;
	border: 1px solid #dce5ff;
	color: #4f5f88;
}
.stu-exam-pill.primary {
	border-color: #5a8cff;
	color: #3373ff;
}
.stu-exam-pill.secondary {
	border-color: #ffb26a;
	color: #ff8c21;
}
.stu-exam-actions {
	margin-top: auto;
	display: flex;
	flex-direction: column;
	gap: 8px;
}
.stu-exam-action,
.stu-exam-link {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	min-height: 44px;
	border-radius: 999px;
	text-decoration: none;
	font-weight: 700;
	font-size: 14px;
	border: 0;
	cursor: pointer;
	transition: transform 0.15s ease;
}
.stu-exam-action:hover,
.stu-exam-link:hover {
	transform: translateY(-1px);
	text-decoration: none;
}
.stu-exam-action.primary {
	background: linear-gradient(90deg, #4c78f0, #5970ef);
	color: #fff;
}
.stu-exam-action.success {
	background: #21b56f;
	color: #fff;
}
.stu-exam-action.omr-blank {
	background: #fff;
	border: 1px solid #cbd5e1;
	color: #1e40af;
}
.stu-exam-action.omr-filled {
	background: #fff;
	border: 1px solid #cbd5e1;
	color: #166534;
}
.stu-exam-result-box {
	background: #f5f7fb;
	border-radius: 12px;
	padding: 10px 12px;
	margin: 6px 0 10px;
}
.stu-exam-result-box h3 {
	font-size: 1rem;
	margin: 0 0 4px;
	color: #252b39;
}
.stu-exam-result-box p {
	margin: 0;
	font-size: 0.88rem;
	color: #5f6780;
}
.stu-exam-empty {
	background: #fff;
	border-radius: 14px;
	padding: 24px 18px;
	text-align: center;
	color: #68738d;
	font-size: 14px;
	grid-column: 1 / -1;
}
@media (min-width: 768px) {
	.stu-exam-action,
	.stu-exam-link {
		width: auto;
		min-width: 160px;
		align-self: flex-start;
	}
}
</style>

<div class="inst-detail-page stu-exam-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Upcoming Exams</div>
	</div>
	<div class="inst-detail-container stu-exam-shell">
		<div class="inst-detail-panel">
			<div class="inst-panel-head">
				<h3>Exams</h3>
			</div>
			<p class="inst-panel-intro">View upcoming assessments and your completed exam results for this batch.</p>

			<div id="stuExamMsg" class="stu-exam-msg"></div>

			<div class="stu-exam-sections-grid">
				<section class="stu-exam-section">
					<div class="stu-exam-section-head">
						<h2>Upcoming Exams</h2>
						<span id="stuUpcomingCount" class="stu-exam-count"></span>
					</div>
					<div id="stuUpcomingList" class="stu-exam-stack"></div>
				</section>

				<section class="stu-exam-section">
					<div class="stu-exam-section-head">
						<h2>Completed Exams</h2>
						<span id="stuCompletedCount" class="stu-exam-count"></span>
					</div>
					<div id="stuCompletedList" class="stu-exam-stack"></div>
				</section>
			</div>
		</div>
	</div>
</div>
<script src="<?php echo base_url('assets/js/exam-omr-download.js?v=2'); ?>"></script>
<script>
(function () {
	'use strict';

	var endpoint = <?php echo json_encode((string) (isset($student_exam_dashboard_api_url) ? $student_exam_dashboard_api_url : site_url('api/batch/student-exam-dashboard'))); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var attemptUrl = <?php echo json_encode((string) (isset($student_exam_attempt_url) ? $student_exam_attempt_url : site_url('batch/exam-attempt'))); ?>;
	var resultUrl = <?php echo json_encode((string) (isset($student_exam_result_url) ? $student_exam_result_url : site_url('batch/exam-result'))); ?>;
	var omrApiUrl = <?php echo json_encode((string) (isset($exam_omr_sheet_api_url) ? $exam_omr_sheet_api_url : site_url('api/batch/exam-omr-sheet'))); ?>;

	function bindOmrButton(btn, examId, mode) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (typeof downloadExamOmrSheet !== 'function') {
				alert('Download helper not loaded.');
				return;
			}
			var old = btn.textContent;
			btn.disabled = true;
			btn.textContent = 'Preparing…';
			downloadExamOmrSheet({ apiUrl: omrApiUrl, token: token, examId: examId, mode: mode }).catch(function (err) {
				alert(err && err.message ? err.message : 'Could not download ORM sheet.');
			}).then(function () {
				btn.disabled = false;
				btn.textContent = old;
			});
		});
	}

	var msgEl = document.getElementById('stuExamMsg');
	var upcomingEl = document.getElementById('stuUpcomingList');
	var completedEl = document.getElementById('stuCompletedList');
	var upcomingCountEl = document.getElementById('stuUpcomingCount');
	var completedCountEl = document.getElementById('stuCompletedCount');

	function esc(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
		});
	}

	function setMessage(text, isError) {
		msgEl.textContent = text || '';
		msgEl.className = 'stu-exam-msg' + (isError ? ' is-error' : '');
	}

	function buildHref(base, examId) {
		return base + '?exam_id=' + encodeURIComponent(examId) + '&batch_id=' + encodeURIComponent(batchId);
	}

	function renderImage(url, title) {
		if (url) {
			return '<img class="stu-exam-image" src="' + esc(url) + '" alt="' + esc(title) + '">';
		}
		return '<div class="stu-exam-image is-empty"><i class="fas fa-file-alt"></i></div>';
	}

	function renderUpcomingCard(item) {
		return '' +
			'<article class="stu-exam-card">' +
				renderImage(item.cardImageUrl, item.name) +
				'<h3 class="stu-exam-name">' + esc(item.name || 'Exam') + '</h3>' +
				'<div class="stu-exam-meta-row">' +
					'<span><strong>Questions :</strong> ' + esc(item.totalQuestion || 0) + '</span>' +
					'<span><strong>Duration :</strong> ' + esc(item.timeDuration || 0) + ' Mins</span>' +
				'</div>' +
				'<div class="stu-exam-meta-row">' +
					'<span><strong>Complete By :</strong> ' + esc(item.completeBy || '') + '</span>' +
				'</div>' +
				'<div class="stu-exam-pills">' +
					'<span class="stu-exam-pill primary">' + esc(item.examTypeLabel || 'Mock Test') + '</span>' +
					'<span class="stu-exam-pill secondary">' + esc(item.batchName || 'Batch') + '</span>' +
				'</div>' +
				'<div class="stu-exam-actions">' +
					'<a class="stu-exam-action primary" href="' + esc(buildHref(attemptUrl, item.id)) + '">Start Assessment</a>' +
					'<button type="button" class="stu-exam-action omr-blank exb-omr-btn" data-exam-id="' + esc(item.id) + '" data-mode="blank">Download ORM Sheet</button>' +
				'</div>' +
			'</article>';
	}

	function renderCompletedCard(item) {
		return '' +
			'<article class="stu-exam-card">' +
				renderImage(item.cardImageUrl, item.name) +
				'<h3 class="stu-exam-name">' + esc(item.name || 'Exam') + '</h3>' +
				'<div class="stu-exam-meta-row">' +
					'<span><strong>Questions :</strong> ' + esc(item.totalQuestion || 0) + '</span>' +
					'<span><strong>Duration :</strong> ' + esc(item.timeDuration || 0) + ' Mins</span>' +
				'</div>' +
				'<div class="stu-exam-meta-row">' +
					'<span><strong>Assigned :</strong> ' + esc(item.assignedDate || '') + '</span>' +
				'</div>' +
				'<div class="stu-exam-result-box">' +
					'<h3>Score : ' + esc(item.scoreLabel || '0/0') + ' (' + esc(item.percentage || 0) + '%)</h3>' +
					'<p><strong>Remarks :</strong> ' + esc(item.remarks || '-') + '</p>' +
				'</div>' +
				'<div class="stu-exam-actions">' +
					'<a class="stu-exam-action success" href="' + esc(buildHref(resultUrl, item.id)) + '">Completed</a>' +
					'<button type="button" class="stu-exam-action omr-filled exb-omr-btn" data-exam-id="' + esc(item.id) + '" data-mode="filled">Download ORM Sheet</button>' +
				'</div>' +
			'</article>';
	}

	function renderSection(target, items, renderer, emptyText) {
		if (!items.length) {
			target.innerHTML = '<div class="stu-exam-empty">' + esc(emptyText) + '</div>';
			return;
		}
		target.innerHTML = items.map(renderer).join('');
		target.querySelectorAll('.exb-omr-btn').forEach(function (btn) {
			bindOmrButton(btn, parseInt(btn.getAttribute('data-exam-id'), 10), btn.getAttribute('data-mode') || 'blank');
		});
	}

	function loadDashboard() {
		if (batchId < 1) {
			setMessage('Invalid batch id.', true);
			upcomingEl.innerHTML = '<div class="stu-exam-empty">Please open this page from a valid batch.</div>';
			completedEl.innerHTML = '<div class="stu-exam-empty">No completed exams found.</div>';
			return;
		}
		setMessage('Loading exams...', false);
		fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify({ batch_id: batchId })
		}).then(function (response) {
			return response.json();
		}).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			if (!ok) {
				throw new Error((res && (res.msg || res.message)) || 'Unable to load exams.');
			}
			var data = res.data || {};
			var upcoming = Array.isArray(data.upcomingExams) ? data.upcomingExams : [];
			var completed = Array.isArray(data.completedExams) ? data.completedExams : [];
			upcomingCountEl.textContent = upcoming.length ? (upcoming.length + ' items') : '';
			completedCountEl.textContent = completed.length ? (completed.length + ' items') : '';
			renderSection(upcomingEl, upcoming, renderUpcomingCard, 'No upcoming exams for this batch yet.');
			renderSection(completedEl, completed, renderCompletedCard, 'You have not completed any exam in this batch yet.');
			setMessage('', false);
		}).catch(function (error) {
			setMessage(error && error.message ? error.message : 'Request failed.', true);
			upcomingEl.innerHTML = '<div class="stu-exam-empty">Could not load upcoming exams.</div>';
			completedEl.innerHTML = '<div class="stu-exam-empty">Could not load completed exams.</div>';
		});
	}

	loadDashboard();
})();
</script>
