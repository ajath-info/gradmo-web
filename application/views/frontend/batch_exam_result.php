<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.exam-result-page {
	max-width: 720px;
	margin: 0 auto;
	padding: 18px 16px 40px;
}
.exam-result-topbar {
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	margin-bottom: 18px;
}
.exam-result-back {
	position: absolute;
	left: 0;
	top: 0;
	width: 38px;
	height: 38px;
	border-radius: 999px;
	background: #fff;
	border: 1px solid #e5ebff;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	color: #1d2433;
	text-decoration: none;
}
.exam-result-title {
	margin: 0;
	font-size: 24px;
	font-weight: 700;
	color: #1f2433;
}
.exam-result-msg {
	font-size: 14px;
	margin-bottom: 14px;
	color: #5e6885;
}
.exam-result-msg.is-error {
	color: #cb3548;
}
.exam-result-success {
	text-align: center;
	padding: 32px 16px 22px;
}
.exam-result-success-icon {
	width: 86px;
	height: 86px;
	border-radius: 999px;
	margin: 0 auto 16px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #4d7af0, #6a8cff);
	color: #fff;
	font-size: 34px;
	box-shadow: 0 16px 30px rgba(71, 104, 214, 0.24);
}
.exam-result-success h2 {
	margin: 0 0 8px;
	font-size: 32px;
	font-weight: 700;
	color: #111827;
}
.exam-result-success p {
	margin: 0;
	font-size: 18px;
	color: #4d5568;
}
.exam-result-card {
	background: #fff;
	border-radius: 22px;
	box-shadow: 0 14px 30px rgba(38, 76, 157, 0.08);
	overflow: hidden;
	margin-top: 10px;
}
.exam-result-image {
	width: 100%;
	height: 190px;
	object-fit: cover;
	background: linear-gradient(135deg, #dde7ff, #f4f8ff);
}
.exam-result-image.is-empty {
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 42px;
	color: #4c78f0;
}
.exam-result-body {
	padding: 16px 16px 20px;
}
.exam-result-name {
	margin: 0 0 10px;
	font-size: 24px;
	font-weight: 700;
	color: #323643;
}
.exam-result-meta,
.exam-result-score {
	font-size: 15px;
	color: #586178;
	margin-bottom: 8px;
}
.exam-result-meta strong,
.exam-result-score strong {
	color: #202636;
	font-weight: 700;
}
.exam-result-divider {
	height: 1px;
	background: #edf0f6;
	margin: 14px -16px;
}
.exam-result-summary {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 12px;
	margin-top: 12px;
}
.exam-result-stat {
	background: #f6f8fc;
	border-radius: 14px;
	padding: 12px 14px;
}
.exam-result-stat-label {
	font-size: 12px;
	color: #6f7890;
	margin-bottom: 4px;
}
.exam-result-stat-value {
	font-size: 19px;
	font-weight: 700;
	color: #1d2331;
}
.exam-result-action {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	min-height: 52px;
	border-radius: 999px;
	border: 0;
	background: linear-gradient(90deg, #4c78f0, #5970ef);
	color: #fff;
	font-size: 16px;
	font-weight: 700;
	text-decoration: none;
	margin-top: 26px;
}
.exam-result-empty {
	background: #fff;
	border-radius: 18px;
	padding: 24px 16px;
	text-align: center;
	color: #6d7690;
}
</style>

<div class="inst-detail-page">
<div class="exam-result-page">
	<div class="exam-result-topbar">
		<a href="javascript:history.back()" class="exam-result-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<h1 class="exam-result-title">Assessment</h1>
	</div>

	<div id="examResultMsg" class="exam-result-msg"></div>

	<div id="examResultSuccess" class="exam-result-success" style="display:none;">
		<div class="exam-result-success-icon"><i class="fas fa-check"></i></div>
		<h2>Test Completed!</h2>
		<p>Your test is successfully completed!!</p>
	</div>

	<div id="examResultApp"></div>
</div>
</div>
<script src="<?php echo base_url('assets/js/exam-omr-download.js?v=2'); ?>"></script>
<script>
(function () {
	'use strict';

	var examId = <?php echo (int) (isset($exam_id) ? $exam_id : 0); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var showDone = <?php echo !empty($exam_done) ? 'true' : 'false'; ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var endpoint = <?php echo json_encode((string) (isset($student_exam_result_api_url) ? $student_exam_result_api_url : site_url('api/batch/student-exam-result'))); ?>;
	var listPageUrl = <?php echo json_encode((string) (isset($student_exam_list_page_url) ? $student_exam_list_page_url : site_url('batch/exams'))); ?>;
	var omrApiUrl = <?php echo json_encode((string) (isset($exam_omr_sheet_api_url) ? $exam_omr_sheet_api_url : site_url('api/batch/exam-omr-sheet'))); ?>;

	function downloadOmrFilled(btn) {
		if (typeof downloadExamOmrSheet !== 'function') {
			alert('Download helper not loaded.');
			return;
		}
		var old = btn ? btn.textContent : '';
		if (btn) { btn.disabled = true; btn.textContent = 'Preparing…'; }
		downloadExamOmrSheet({ apiUrl: omrApiUrl, token: token, examId: examId, mode: 'filled' }).catch(function (err) {
			alert(err && err.message ? err.message : 'Could not download ORM sheet.');
		}).then(function () {
			if (btn) { btn.disabled = false; btn.textContent = old; }
		});
	}

	var msgEl = document.getElementById('examResultMsg');
	var appEl = document.getElementById('examResultApp');
	var successEl = document.getElementById('examResultSuccess');

	function esc(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
		});
	}

	function setMessage(text, isError) {
		msgEl.textContent = text || '';
		msgEl.className = 'exam-result-msg' + (isError ? ' is-error' : '');
	}

	function renderImage(url, title) {
		if (url) {
			return '<img class="exam-result-image" src="' + esc(url) + '" alt="' + esc(title) + '">';
		}
		return '<div class="exam-result-image is-empty"><i class="fas fa-file-alt"></i></div>';
	}

	function renderResult(exam, result) {
		return '' +
			'<article class="exam-result-card">' +
				renderImage(exam.cardImageUrl, exam.name) +
				'<div class="exam-result-body">' +
					'<h2 class="exam-result-name">' + esc(exam.name || 'Exam') + '</h2>' +
					'<div class="exam-result-meta"><strong>Assigned :</strong> ' + esc(result.assignedDate || exam.scheduledDate || '-') + '</div>' +
					'<div class="exam-result-divider"></div>' +
					'<div class="exam-result-score"><strong>Score :</strong> ' + esc(result.scoreLabel || '0/0') + ' (' + esc(result.percentage || 0) + '%)</div>' +
					'<div class="exam-result-score"><strong>Remarks :</strong> ' + esc(result.remarks || '-') + '</div>' +
					'<div class="exam-result-summary">' +
						'<div class="exam-result-stat"><div class="exam-result-stat-label">Correct</div><div class="exam-result-stat-value">' + esc(result.correctAnswers || 0) + '</div></div>' +
						'<div class="exam-result-stat"><div class="exam-result-stat-label">Wrong</div><div class="exam-result-stat-value">' + esc(result.wrongAnswers || 0) + '</div></div>' +
						'<div class="exam-result-stat"><div class="exam-result-stat-label">Attempted</div><div class="exam-result-stat-value">' + esc(result.attemptedQuestion || 0) + '</div></div>' +
						'<div class="exam-result-stat"><div class="exam-result-stat-label">Time Taken</div><div class="exam-result-stat-value">' + esc(result.timeTaken || '--') + '</div></div>' +
					'</div>' +
					'<button type="button" class="exam-result-action exam-omr-download" style="border:0;cursor:pointer;width:100%;margin-bottom:10px;background:#1e40af;">Download ORM Sheet</button>' +
					'<a class="exam-result-action" href="' + esc(listPageUrl + '?batch_id=' + encodeURIComponent(batchId)) + '">Back to Assessments</a>' +
				'</div>' +
			'</article>';
	}

	function loadResult() {
		if (examId < 1) {
			setMessage('Invalid exam id.', true);
			appEl.innerHTML = '<div class="exam-result-empty">Please open this page from the exams list.</div>';
			return;
		}
		if (showDone) {
			successEl.style.display = '';
		}
		setMessage('Loading result...', false);
		appEl.innerHTML = '<div class="exam-result-empty">Loading result...</div>';
		fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify({ exam_id: examId })
		}).then(function (response) {
			return response.json();
		}).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			if (!ok) {
				throw new Error((res && (res.msg || res.message)) || 'Could not load result.');
			}
			var data = res.data || {};
			appEl.innerHTML = renderResult(data.exam || {}, data.result || {});
			var omrBtn = appEl.querySelector('.exam-omr-download');
			if (omrBtn) {
				omrBtn.addEventListener('click', function () { downloadOmrFilled(omrBtn); });
			}
			setMessage('', false);
		}).catch(function (error) {
			setMessage(error && error.message ? error.message : 'Could not load result.', true);
			appEl.innerHTML = '<div class="exam-result-empty"><a class="exam-result-action" href="' + esc(listPageUrl + '?batch_id=' + encodeURIComponent(batchId)) + '">Back to Assessments</a></div>';
		});
	}

	loadResult();
})();
</script>
