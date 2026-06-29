<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=9">
<style>
.ed-shell { max-width: 820px; margin: 0 auto; }
.ed-section {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 14px;
	padding: 16px;
	margin-bottom: 14px;
	box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
}
.ed-section h3 { margin: 0 0 10px; font-size: 1rem; font-weight: 700; color: #0f172a; }
.ed-student { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.ed-student img { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; background: #eef2ff; }
.ed-meta { margin: 0; font-size: 0.88rem; color: #64748b; line-height: 1.45; }
.ed-stats {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 10px;
	margin-top: 10px;
}
.ed-stat-box {
	background: #f8fafc;
	border: 1px solid #e2e8f0;
	border-radius: 10px;
	padding: 10px 12px;
}
.ed-stat-box strong { display: block; font-size: 1.1rem; color: #0f172a; }
.ed-stat-box span { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; }
.ed-q {
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	padding: 12px;
	margin-bottom: 10px;
	background: #fafbfc;
}
.ed-q.is-correct { border-color: #86efac; background: #f0fdf4; }
.ed-q.is-wrong { border-color: #fecaca; background: #fef2f2; }
.ed-q.is-skipped { border-color: #fde68a; background: #fffbeb; }
.ed-q-head { font-weight: 700; color: #0f172a; margin-bottom: 8px; font-size: 0.92rem; }
.ed-q-text { margin: 0 0 8px; color: #334155; line-height: 1.45; white-space: pre-wrap; }
.ed-opt { font-size: 0.85rem; color: #475569; margin: 2px 0; }
.ed-opt.is-selected { font-weight: 700; color: #1d4ed8; }
.ed-ans-line { margin-top: 8px; font-size: 0.84rem; }
@media (max-width: 575px) {
	.ed-stats { grid-template-columns: 1fr; }
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="<?php
			$back = $submissions_list_url . '?exam_id=' . (int) (isset($exam_id) ? $exam_id : 0);
			if (!empty($batch_id)) { $back .= '&batch_id=' . (int) $batch_id; }
			echo html_escape($back);
		?>" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Submission details</div>
	</div>
	<div class="inst-detail-container ed-shell">
		<div id="edMsg" class="inst-muted text-center py-3">Loading…</div>
		<div id="edBody" class="inst-detail-hidden">
			<div class="ed-section">
				<div class="ed-student">
					<img id="edAvatar" src="" alt="">
					<div>
						<h2 id="edStudent" style="margin:0 0 4px;font-size:1.1rem;font-weight:700;"></h2>
						<p class="ed-meta" id="edExamLine"></p>
						<p class="ed-meta" id="edSubmittedLine"></p>
					</div>
				</div>
				<div class="ed-stats" id="edStats"></div>
			</div>
			<div class="ed-section">
				<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:10px;">
					<h3 style="margin:0;">Answers</h3>
					<button type="button" class="btn btn-sm btn-primary" id="edOmrDownload">Download ORM Sheet</button>
				</div>
				<div id="edAnswers"></div>
			</div>
		</div>
	</div>
</div>
<script src="<?php echo base_url('assets/js/exam-omr-download.js?v=2'); ?>"></script>
<script>
(function () {
	'use strict';
	var submissionId = <?php echo (int) (isset($submission_id) ? $submission_id : 0); ?>;
	var examId = <?php echo (int) (isset($exam_id) ? $exam_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var detailsUrl = <?php echo json_encode((string) (isset($exam_submission_details_api_url) ? $exam_submission_details_api_url : '')); ?>;
	var omrApiUrl = <?php echo json_encode((string) (isset($exam_omr_sheet_api_url) ? $exam_omr_sheet_api_url : site_url('api/batch/exam-omr-sheet'))); ?>;

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(v) {
		var d = document.createElement('div');
		d.textContent = v == null ? '' : String(v);
		return d.innerHTML;
	}
	function statBox(label, value) {
		return '<div class="ed-stat-box"><strong>' + esc(value) + '</strong><span>' + esc(label) + '</span></div>';
	}
	function renderAnswers(answers) {
		if (!answers || !answers.length) {
			return '<p class="ed-meta">No answer data available.</p>';
		}
		var html = '';
		answers.forEach(function (a) {
			var cls = 'ed-q';
			if (!a.attempted || a.attempted === 0 || a.attempted === '0') {
				cls += ' is-skipped';
			} else if (a.isCorrect === 1 || a.isCorrect === '1') {
				cls += ' is-correct';
			} else {
				cls += ' is-wrong';
			}
			var opts = Array.isArray(a.options) ? a.options : [];
			var optHtml = '';
			var letters = ['A', 'B', 'C', 'D'];
			for (var i = 0; i < opts.length; i++) {
				var letter = letters[i] || String(i + 1);
				var sel = (a.studentAnswer && a.studentAnswer === letter) ? ' is-selected' : '';
				optHtml += '<div class="ed-opt' + sel + '">' + esc(letter) + '. ' + esc(opts[i]) + '</div>';
			}
			var status = (!a.attempted || a.attempted === 0) ? 'Not attempted'
				: ((a.isCorrect === 1 || a.isCorrect === '1') ? 'Correct' : 'Wrong');
			html += '<div class="' + cls + '">' +
				'<div class="ed-q-head">Q' + esc(a.displayIndex) + ' · ' + esc(status) + '</div>' +
				(a.questionImageUrl ? '<p><img src="' + esc(a.questionImageUrl) + '" alt="" style="max-width:100%;border-radius:8px;margin-bottom:8px;"></p>' : '') +
				'<p class="ed-q-text">' + esc(a.question) + '</p>' +
				optHtml +
				'<p class="ed-ans-line"><strong>Student:</strong> ' + esc(a.studentAnswer || '—') +
				' · <strong>Correct:</strong> ' + esc(a.correctAnswer || '—') + '</p>' +
			'</div>';
		});
		return html;
	}
	function load() {
		if (submissionId < 1) {
			document.getElementById('edMsg').textContent = 'Invalid submission.';
			return;
		}
		fetch(detailsUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({ access_token: token, submission_id: submissionId })
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!ok(j.status) || !j.data) {
				document.getElementById('edMsg').textContent = (j && j.msg) || 'Could not load submission.';
				return;
			}
			var sub = j.data.submission || {};
			var exam = j.data.exam || {};
			var student = j.data.student || {};
			document.getElementById('edStudent').textContent = student.studentName || sub.studentName || 'Student';
			if (student.studentImageUrl) {
				document.getElementById('edAvatar').src = student.studentImageUrl;
			}
			document.getElementById('edExamLine').textContent = (exam.name || sub.examName || 'Exam') + (exam.batchName ? (' · ' + exam.batchName) : '');
			document.getElementById('edSubmittedLine').textContent = 'Submitted: ' + (sub.submittedAt || '—') +
				(sub.timeTaken ? (' · Time taken: ' + sub.timeTaken) : '');
			document.getElementById('edStats').innerHTML =
				statBox('Total questions', sub.totalQuestion) +
				statBox('Attempted', sub.attemptedQuestion) +
				statBox('Correct', sub.correctAnswers) +
				statBox('Score %', sub.percentage) +
				statBox('Marks', sub.scoreLabel || sub.score) +
				statBox('Status', sub.statusLabel || 'Submitted');
			document.getElementById('edAnswers').innerHTML = renderAnswers(j.data.answers || []);
			document.getElementById('edMsg').classList.add('inst-detail-hidden');
			document.getElementById('edBody').classList.remove('inst-detail-hidden');
		}).catch(function () {
			document.getElementById('edMsg').textContent = 'Network error.';
		});
	}
	document.getElementById('edOmrDownload').addEventListener('click', function () {
		var btn = document.getElementById('edOmrDownload');
		if (typeof downloadExamOmrSheet !== 'function') { alert('Download helper not loaded.'); return; }
		var old = btn.textContent;
		btn.disabled = true;
		btn.textContent = 'Preparing…';
		downloadExamOmrSheet({
			apiUrl: omrApiUrl,
			token: token,
			examId: examId,
			submissionId: submissionId,
			mode: 'filled',
			showCorrect: true
		}).catch(function (err) {
			alert(err && err.message ? err.message : 'Could not download ORM sheet.');
		}).then(function () { btn.disabled = false; btn.textContent = old; });
	});

	load();
})();
</script>
