<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.hr-shell { max-width: 720px; margin: 0 auto; }
.hr-section {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 14px;
	padding: 16px;
	margin-bottom: 14px;
	box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
}
.hr-section h3 { margin: 0 0 10px; font-size: 1rem; font-weight: 700; color: #0f172a; }
.hr-student {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 10px;
}
.hr-student img {
	width: 52px;
	height: 52px;
	border-radius: 50%;
	object-fit: cover;
	background: #eef2ff;
}
.hr-meta { margin: 0; font-size: 0.9rem; color: #64748b; line-height: 1.45; }
.hr-text { margin: 0; white-space: pre-wrap; word-break: break-word; color: #334155; }
.hr-pill {
	display: inline-flex;
	padding: 4px 10px;
	border-radius: 999px;
	font-size: 0.72rem;
	font-weight: 700;
	text-transform: uppercase;
}
.hr-pill-submitted { background: #dbeafe; color: #1e40af; }
.hr-pill-evaluated { background: #dcfce7; color: #166534; }
@media (max-width: 575px) {
	.hr-section .btn { width: 100%; margin-bottom: 6px; }
	.hr-eval-actions { display: flex; flex-direction: column; gap: 8px; }
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="<?php
			$back = $submissions_list_url . '?homework_id=' . (int) (isset($homework_id) ? $homework_id : 0);
			if (!empty($batch_id)) { $back .= '&batch_id=' . (int) $batch_id; }
			echo html_escape($back);
		?>" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Review submission</div>
	</div>
	<div class="inst-detail-container hr-shell">
		<div id="hrMsg" class="inst-muted text-center py-3">Loading…</div>
		<div id="hrBody" class="inst-detail-hidden">
			<div class="hr-section">
				<div class="hr-student">
					<img id="hrAvatar" src="" alt="">
					<div>
						<h2 id="hrStudent" style="margin:0 0 4px;font-size:1.1rem;font-weight:700;"></h2>
						<span id="hrStatusPill" class="hr-pill hr-pill-submitted"></span>
						<p class="hr-meta" id="hrSubmittedAt" style="margin-top:6px;"></p>
					</div>
				</div>
				<p class="hr-meta" id="hrHomeworkMeta"></p>
			</div>
			<div class="hr-section">
				<h3>Student answer</h3>
				<p class="hr-text" id="hrAnswer"></p>
				<div id="hrFiles" style="margin-top:10px;"></div>
			</div>
			<div class="hr-section">
				<h3>Evaluation</h3>
				<div class="form-group">
					<label for="hrMarks">Marks (optional)</label>
					<input type="number" step="0.01" min="0" id="hrMarks" class="form-control" placeholder="e.g. 8.5">
				</div>
				<div class="form-group">
					<label for="hrRemark">Remark</label>
					<textarea id="hrRemark" class="form-control" rows="3" placeholder="Feedback for the student…"></textarea>
				</div>
				<div id="hrFormMsg" class="small text-danger mb-2"></div>
				<div class="hr-eval-actions">
					<button type="button" class="btn btn-success" id="hrSaveEval">Save evaluation</button>
					<button type="button" class="btn btn-outline-secondary" id="hrMarkPending">Mark as pending review</button>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	'use strict';
	var submissionId = <?php echo (int) (isset($submission_id) ? $submission_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var detailsUrl = <?php echo json_encode((string) (isset($submission_details_api_url) ? $submission_details_api_url : '')); ?>;
	var evaluateUrl = <?php echo json_encode((string) (isset($homework_evaluate_api_url) ? $homework_evaluate_api_url : '')); ?>;
	var currentSub = null;
	var saveInFlight = false;

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(v) {
		var d = document.createElement('div');
		d.textContent = v == null ? '' : String(v);
		return d.innerHTML;
	}
	function setMsg(t, err) {
		var el = document.getElementById('hrMsg');
		el.className = err ? 'text-danger text-center py-3' : 'inst-muted text-center py-3';
		el.textContent = t || '';
	}
	function setFormMsg(t) { document.getElementById('hrFormMsg').textContent = t || ''; }
	function render(sub) {
		currentSub = sub;
		var evaluated = sub.evalStatus === 1 || sub.evalStatus === '1';
		document.getElementById('hrStudent').textContent = sub.studentName || 'Student';
		var av = document.getElementById('hrAvatar');
		if (sub.studentImageUrl) {
			av.src = sub.studentImageUrl;
			av.style.visibility = 'visible';
		}
		var pill = document.getElementById('hrStatusPill');
		pill.className = 'hr-pill ' + (evaluated ? 'hr-pill-evaluated' : 'hr-pill-submitted');
		pill.textContent = evaluated ? 'Evaluated' : 'Submitted';
		document.getElementById('hrSubmittedAt').textContent = sub.submittedAt ? ('Submitted: ' + sub.submittedAt) : '';
		document.getElementById('hrHomeworkMeta').textContent = [
			sub.subjectName || '',
			sub.homeworkDate || '',
			sub.homeworkDescription || ''
		].filter(Boolean).join(' · ');
		document.getElementById('hrAnswer').textContent = sub.submissionText || '(No text answer)';
		document.getElementById('hrFiles').innerHTML = sub.attachmentUrl
			? '<a class="btn btn-outline-primary" href="' + esc(sub.attachmentUrl) + '" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i> Download submission</a>'
			: '<p class="hr-meta">No file attached.</p>';
		document.getElementById('hrMarks').value = sub.marks != null && sub.marks !== '' ? String(sub.marks) : '';
		document.getElementById('hrRemark').value = sub.remark || '';
	}
	function saveEvaluation(evalStatus) {
		if (saveInFlight || !currentSub) { return; }
		setFormMsg('');
		var marksVal = document.getElementById('hrMarks').value.trim();
		var body = {
			access_token: token,
			submission_id: submissionId,
			remark: document.getElementById('hrRemark').value.trim(),
			eval_status: evalStatus ? 1 : 0
		};
		if (marksVal !== '') { body.marks = parseFloat(marksVal); }
		saveInFlight = true;
		document.getElementById('hrSaveEval').disabled = true;
		fetch(evaluateUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			saveInFlight = false;
			document.getElementById('hrSaveEval').disabled = false;
			if (!ok(j.status)) {
				setFormMsg((j && j.msg) || 'Could not save.');
				return;
			}
			if (typeof toastr !== 'undefined') { toastr.success((j && j.msg) || 'Saved'); }
			load();
		}).catch(function () {
			saveInFlight = false;
			document.getElementById('hrSaveEval').disabled = false;
			setFormMsg('Network error.');
		});
	}
	function load() {
		if (submissionId < 1) {
			setMsg('Invalid submission.', true);
			return;
		}
		fetch(detailsUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({ access_token: token, submission_id: submissionId })
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!ok(j.status) || !j.data || !j.data.submission) {
				setMsg((j && j.msg) || 'Submission not found.', true);
				return;
			}
			render(j.data.submission);
			setMsg('');
			document.getElementById('hrBody').classList.remove('inst-detail-hidden');
		}).catch(function () {
			setMsg('Could not load submission.', true);
		});
	}
	document.getElementById('hrSaveEval').addEventListener('click', function () { saveEvaluation(1); });
	document.getElementById('hrMarkPending').addEventListener('click', function () { saveEvaluation(0); });
	load();
})();
</script>
