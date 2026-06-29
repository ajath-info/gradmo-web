<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.hw-detail-shell { max-width: 720px; margin: 0 auto; }
.hw-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 5px 12px;
	border-radius: 999px;
	font-size: 0.78rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.03em;
}
.hw-badge-pending { background: #fef3c7; color: #92400e; }
.hw-badge-submitted { background: #dbeafe; color: #1e40af; }
.hw-badge-evaluated { background: #dcfce7; color: #166534; }
.hw-section {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 14px;
	padding: 16px;
	margin-bottom: 14px;
	box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
}
.hw-section h3 {
	margin: 0 0 10px;
	font-size: 1rem;
	font-weight: 700;
	color: #0f172a;
}
.hw-meta { margin: 0 0 8px; font-size: 0.9rem; color: #64748b; line-height: 1.45; }
.hw-desc { margin: 0; white-space: pre-wrap; word-break: break-word; color: #334155; line-height: 1.5; }
.hw-file-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.hw-upload-progress {
	margin: 10px 0;
	padding: 10px 12px;
	border: 1px solid #dbeafe;
	border-radius: 12px;
	background: #f8fbff;
}
.hw-upload-progress.is-hidden { display: none; }
.hw-upload-progress-bar {
	width: 100%;
	height: 10px;
	border-radius: 999px;
	background: #e5edf8;
	overflow: hidden;
}
.hw-upload-progress-fill {
	display: block;
	height: 100%;
	width: 0;
	background: linear-gradient(90deg, #2563eb, #38bdf8);
	transition: width 0.2s ease;
}
.hw-upload-progress-text { margin-top: 8px; font-size: 0.84rem; font-weight: 600; color: #334155; }
.hw-eval-box {
	background: #f0fdf4;
	border: 1px solid #bbf7d0;
	border-radius: 10px;
	padding: 12px;
	margin-top: 10px;
}
@media (max-width: 575px) {
	.hw-section { padding: 14px 12px; }
	.hw-detail-shell .btn-lg { width: 100%; }
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="<?php echo html_escape($homework_list_url . ($batch_id > 0 ? '?batch_id=' . (int) $batch_id : '')); ?>" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Homework details</div>
	</div>
	<div class="inst-detail-container hw-detail-shell">
		<div id="hdMsg" class="inst-muted text-center py-3">Loading…</div>
		<div id="hdBody" class="inst-detail-hidden">
			<div class="hw-section">
				<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;">
					<h2 id="hdTitle" style="margin:0;font-size:1.15rem;font-weight:700;color:#0f172a;"></h2>
					<span id="hdStatusBadge" class="hw-badge hw-badge-pending"></span>
				</div>
				<p class="hw-meta" id="hdMeta"></p>
				<p class="hw-desc" id="hdDesc"></p>
				<div id="hdHandout" class="hw-file-list"></div>
			</div>
			<div id="hdSubmissionSection" class="hw-section inst-detail-hidden">
				<h3>Your submission</h3>
				<p class="hw-meta" id="hdSubmittedAt"></p>
				<p class="hw-desc" id="hdSubmissionText"></p>
				<div id="hdSubmissionFiles" class="hw-file-list"></div>
				<div id="hdEvalBox" class="hw-eval-box inst-detail-hidden">
					<p class="hw-meta" style="margin:0;"><strong>Marks:</strong> <span id="hdMarks"></span></p>
					<p class="hw-meta" style="margin:8px 0 0;"><strong>Remark:</strong> <span id="hdRemark"></span></p>
				</div>
			</div>
			<div id="hdSubmitSection" class="hw-section">
				<h3 id="hdSubmitHeading">Submit homework</h3>
				<p class="hw-meta">Add your answer as text and/or upload a file (PDF, image, document).</p>
				<div class="form-group">
					<label for="hdText">Answer / notes</label>
					<textarea id="hdText" class="form-control" rows="4" placeholder="Type your answer here…"></textarea>
				</div>
				<div class="form-group">
					<label for="hdFile">Attachment (optional)</label>
					<input type="file" id="hdFile" class="form-control-file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,image/*,application/pdf">
				</div>
				<div id="hdProgress" class="hw-upload-progress is-hidden" aria-live="polite">
					<div class="hw-upload-progress-bar"><span id="hdProgressFill" class="hw-upload-progress-fill"></span></div>
					<div id="hdProgressText" class="hw-upload-progress-text">Uploading…</div>
				</div>
				<div id="hdFormMsg" class="small text-danger mb-2"></div>
				<button type="button" class="btn btn-success btn-lg" id="hdSubmitBtn">Submit homework</button>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	'use strict';
	var homeworkId = <?php echo (int) (isset($homework_id) ? $homework_id : 0); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var detailsUrl = <?php echo json_encode((string) (isset($homework_details_api_url) ? $homework_details_api_url : '')); ?>;
	var submitUrl = <?php echo json_encode((string) (isset($homework_submit_api_url) ? $homework_submit_api_url : '')); ?>;
	var mySubUrl = <?php echo json_encode((string) (isset($my_submissions_api_url) ? $my_submissions_api_url : '')); ?>;
	var submitInFlight = false;

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
		});
	}
	function apiPost(url, body) {
		return fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); });
	}
	function setMsg(t, err) {
		var el = document.getElementById('hdMsg');
		el.className = err ? 'text-danger text-center py-3' : 'inst-muted text-center py-3';
		el.textContent = t || '';
	}
	function setFormMsg(t) {
		document.getElementById('hdFormMsg').textContent = t || '';
	}
	function setBadge(kind, label) {
		var el = document.getElementById('hdStatusBadge');
		el.className = 'hw-badge hw-badge-' + kind;
		el.textContent = label;
	}
	function fileLink(url, label) {
		if (!url) { return ''; }
		return '<a class="btn btn-sm btn-outline-primary" href="' + esc(url) + '" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i> ' + esc(label || 'Download') + '</a>';
	}
	function renderSubmission(sub) {
		var sec = document.getElementById('hdSubmissionSection');
		if (!sub) {
			sec.classList.add('inst-detail-hidden');
			setBadge('pending', 'Not submitted');
			document.getElementById('hdSubmitHeading').textContent = 'Submit homework';
			return;
		}
		sec.classList.remove('inst-detail-hidden');
		var evaluated = sub.evalStatus === 1 || sub.evalStatus === '1';
		setBadge(evaluated ? 'evaluated' : 'submitted', evaluated ? 'Evaluated' : 'Submitted');
		document.getElementById('hdSubmittedAt').textContent = sub.submittedAt ? ('Submitted: ' + sub.submittedAt) : '';
		document.getElementById('hdSubmissionText').textContent = sub.submissionText || '';
		document.getElementById('hdSubmissionFiles').innerHTML = sub.attachmentUrl
			? fileLink(sub.attachmentUrl, 'Your uploaded file')
			: '';
		var evalBox = document.getElementById('hdEvalBox');
		if (evaluated) {
			evalBox.classList.remove('inst-detail-hidden');
			document.getElementById('hdMarks').textContent = sub.marks != null && sub.marks !== '' ? String(sub.marks) : '—';
			document.getElementById('hdRemark').textContent = sub.remark || '—';
		} else {
			evalBox.classList.add('inst-detail-hidden');
		}
		document.getElementById('hdSubmitHeading').textContent = 'Update submission';
		if (sub.submissionText) {
			document.getElementById('hdText').value = sub.submissionText;
		}
	}
	function renderHomework(hw) {
		document.getElementById('hdTitle').textContent = hw.subjectName || 'Homework';
		var meta = [];
		if (hw.teacherName || hw.name) { meta.push(hw.teacherName || hw.name); }
		if (hw.date) { meta.push(hw.date); }
		document.getElementById('hdMeta').textContent = meta.join(' · ');
		document.getElementById('hdDesc').textContent = hw.description || '';
		document.getElementById('hdHandout').innerHTML = hw.attachmentUrl
			? fileLink(hw.attachmentUrl, 'Teacher handout (PDF)')
			: '';
	}
	function uploadSubmit(fd) {
		return new Promise(function (resolve, reject) {
			var xhr = new XMLHttpRequest();
			var prog = document.getElementById('hdProgress');
			var fill = document.getElementById('hdProgressFill');
			var ptxt = document.getElementById('hdProgressText');
			xhr.open('POST', submitUrl, true);
			xhr.setRequestHeader('Authorization', 'Bearer ' + token);
			xhr.upload.addEventListener('progress', function (ev) {
				prog.classList.remove('is-hidden');
				if (ev.lengthComputable) {
					var pct = Math.round((ev.loaded / ev.total) * 100);
					fill.style.width = pct + '%';
					ptxt.textContent = pct >= 100 ? 'Processing…' : ('Uploading… ' + pct + '%');
				}
			});
			xhr.onload = function () {
				prog.classList.add('is-hidden');
				fill.style.width = '0%';
				try { resolve(JSON.parse(xhr.responseText || '{}')); }
				catch (e) { reject(e); }
			};
			xhr.onerror = function () { reject(new Error('Network error')); };
			xhr.send(fd);
		});
	}
	function loadPage() {
		if (homeworkId < 1) {
			setMsg('Invalid homework.', true);
			return;
		}
		Promise.all([
			apiPost(detailsUrl, { access_token: token, homework_id: homeworkId }),
			apiPost(mySubUrl, { access_token: token, homework_id: homeworkId, batch_id: batchId > 0 ? batchId : undefined, page: 1, limit: 5 })
		]).then(function (results) {
			var det = results[0];
			var mine = results[1];
			if (!ok(det.status) || !det.data || !det.data.homework) {
				setMsg((det && det.msg) || 'Homework not found.', true);
				return;
			}
			var hw = det.data.homework;
			renderHomework(hw);
			var sub = null;
			if (ok(mine.status) && mine.data && mine.data.submissions && mine.data.submissions.length) {
				sub = mine.data.submissions[0];
			}
			renderSubmission(sub);
			setMsg('');
			document.getElementById('hdBody').classList.remove('inst-detail-hidden');
		}).catch(function () {
			setMsg('Could not load homework.', true);
		});
	}
	function doSubmit() {
		if (submitInFlight) { return; }
		var text = document.getElementById('hdText').value.trim();
		var file = document.getElementById('hdFile').files[0];
		if (!text && !file) {
			setFormMsg('Enter your answer and/or choose a file to upload.');
			return;
		}
		setFormMsg('');
		var fd = new FormData();
		fd.append('access_token', token);
		fd.append('homework_id', String(homeworkId));
		if (text) { fd.append('submission_text', text); }
		if (file) { fd.append('submission_file', file); }
		var btn = document.getElementById('hdSubmitBtn');
		submitInFlight = true;
		btn.disabled = true;
		btn.textContent = 'Submitting…';
		uploadSubmit(fd).then(function (j) {
			submitInFlight = false;
			btn.disabled = false;
			btn.textContent = 'Submit homework';
			if (!ok(j.status)) {
				setFormMsg((j && j.msg) || 'Submission failed.');
				return;
			}
			document.getElementById('hdFile').value = '';
			if (typeof toastr !== 'undefined') { toastr.success((j && j.msg) || 'Submitted'); }
			loadPage();
		}).catch(function () {
			submitInFlight = false;
			btn.disabled = false;
			btn.textContent = 'Submit homework';
			setFormMsg('Network error. Please try again.');
		});
	}
	document.getElementById('hdSubmitBtn').addEventListener('click', doSubmit);
	loadPage();
})();
</script>
