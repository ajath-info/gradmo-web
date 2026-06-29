<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.exam-attempt-page {
	max-width: 820px;
	margin: 0 auto;
	padding: 18px 16px 34px;
}
.exam-attempt-topbar {
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	margin-bottom: 18px;
}
.exam-attempt-back {
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
.exam-attempt-title {
	margin: 0;
	font-size: 24px;
	font-weight: 700;
	color: #1f2433;
}
.exam-attempt-status {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 10px;
	color: #2f3850;
	font-size: 16px;
	font-weight: 600;
}
.exam-attempt-status-right {
	display: flex;
	align-items: center;
	gap: 10px;
}
.exam-attempt-timer {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 6px 12px;
	border-radius: 999px;
	background: #edf3ff;
	color: #3d67df;
	font-size: 13px;
	font-weight: 700;
}
.exam-attempt-progress {
	height: 6px;
	background: #cfd9ff;
	border-radius: 999px;
	overflow: hidden;
	margin-bottom: 18px;
}
.exam-attempt-progress-fill {
	height: 100%;
	width: 0;
	border-radius: inherit;
	background: linear-gradient(90deg, #507bf1, #6b86ff);
	transition: width 0.2s ease;
}
.exam-attempt-shell {
	background: #eaf6ff;
	border-radius: 28px;
	padding: 14px;
}
.exam-attempt-card {
	background: #fff;
	border-radius: 18px;
	padding: 18px 14px 16px;
	box-shadow: 0 14px 28px rgba(38, 76, 157, 0.08);
}
.exam-attempt-question {
	font-size: 16px;
	line-height: 1.65;
	color: #2f3545;
	margin: 0 0 16px;
}
.exam-attempt-image {
	width: 100%;
	max-height: 230px;
	object-fit: contain;
	border-radius: 16px;
	background: #f7f9fc;
	margin-bottom: 14px;
}
.exam-attempt-options {
	display: grid;
	gap: 12px;
	margin-top: 14px;
}
.exam-attempt-option {
	display: grid;
	grid-template-columns: 46px 1fr;
	align-items: stretch;
	border-radius: 12px;
	overflow: hidden;
	border: 1px solid #e5ebf5;
	background: #fff;
	cursor: pointer;
	transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.exam-attempt-option:hover {
	transform: translateY(-1px);
	box-shadow: 0 8px 20px rgba(34, 58, 126, 0.08);
}
.exam-attempt-option-key {
	display: flex;
	align-items: center;
	justify-content: center;
	background: #555;
	color: #fff;
	font-size: 28px;
	font-weight: 700;
}
.exam-attempt-option-text {
	padding: 14px 16px;
	font-size: 15px;
	line-height: 1.55;
	color: #3d4351;
}
.exam-attempt-option.is-active {
	border-color: #4f79ef;
}
.exam-attempt-option.is-active .exam-attempt-option-key,
.exam-attempt-option.is-active .exam-attempt-option-text {
	background: #4f79ef;
	color: #fff;
}
.exam-attempt-actions {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 12px;
	margin-top: 16px;
}
.exam-attempt-btn {
	min-height: 50px;
	border: 0;
	border-radius: 999px;
	font-size: 15px;
	font-weight: 700;
}
.exam-attempt-btn.secondary {
	background: #fff;
	color: #4568d8;
	border: 1px solid #d6e0ff;
}
.exam-attempt-btn.primary {
	background: linear-gradient(90deg, #4c78f0, #5970ef);
	color: #fff;
}
.exam-attempt-btn[disabled] {
	opacity: 0.65;
	cursor: not-allowed;
}
.exam-attempt-message {
	font-size: 14px;
	margin: 0 0 14px;
	color: #5c6784;
}
.exam-attempt-message.is-error {
	color: #c93548;
}
.exam-attempt-empty {
	background: #fff;
	border-radius: 18px;
	padding: 24px 16px;
	text-align: center;
	color: #6a7389;
	font-size: 14px;
}
</style>

<div class="inst-detail-page">
<div class="exam-attempt-page">
	<div class="exam-attempt-topbar">
		<a href="javascript:history.back()" class="exam-attempt-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<h1 class="exam-attempt-title">Assessment</h1>
	</div>

	<div id="examAttemptMessage" class="exam-attempt-message"></div>
	<div style="margin-bottom:12px;">
		<button type="button" id="examAttemptOmrBtn" class="btn btn-sm btn-outline-primary" style="width:100%;">Download ORM Sheet</button>
	</div>

	<div id="examAttemptApp">
		<div class="exam-attempt-status">
			<div id="examAttemptQuestionTitle">Question 1</div>
			<div class="exam-attempt-status-right">
				<div id="examAttemptTimer" class="exam-attempt-timer">00:00</div>
				<div id="examAttemptCounter">0/0</div>
			</div>
		</div>
		<div class="exam-attempt-progress">
			<div id="examAttemptProgress" class="exam-attempt-progress-fill"></div>
		</div>

		<div class="exam-attempt-shell">
			<div class="exam-attempt-card">
				<p id="examAttemptQuestion" class="exam-attempt-question"></p>
				<img id="examAttemptImage" class="exam-attempt-image" src="" alt="Question image" style="display:none;">
				<div id="examAttemptOptions" class="exam-attempt-options"></div>
				<div class="exam-attempt-actions">
					<button id="examAttemptPrev" type="button" class="exam-attempt-btn secondary">Previous</button>
					<button id="examAttemptNext" type="button" class="exam-attempt-btn primary">Next</button>
				</div>
			</div>
		</div>
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
	var omrApiUrl = <?php echo json_encode((string) (isset($exam_omr_sheet_api_url) ? $exam_omr_sheet_api_url : site_url('api/batch/exam-omr-sheet'))); ?>;
	var paperUrl = <?php echo json_encode((string) (isset($student_exam_paper_api_url) ? $student_exam_paper_api_url : site_url('api/batch/student-exam-paper'))); ?>;
	var submitUrl = <?php echo json_encode((string) (isset($student_submit_exam_api_url) ? $student_submit_exam_api_url : site_url('api/batch/student-submit-exam'))); ?>;
	var resultPageUrl = <?php echo json_encode((string) (isset($student_exam_result_page_url) ? $student_exam_result_page_url : site_url('batch/exam-result'))); ?>;
	var listPageUrl = <?php echo json_encode((string) (isset($student_exam_list_page_url) ? $student_exam_list_page_url : site_url('batch/exams'))); ?>;

	var refs = {
		message: document.getElementById('examAttemptMessage'),
		app: document.getElementById('examAttemptApp'),
		title: document.getElementById('examAttemptQuestionTitle'),
		counter: document.getElementById('examAttemptCounter'),
		timer: document.getElementById('examAttemptTimer'),
		progress: document.getElementById('examAttemptProgress'),
		question: document.getElementById('examAttemptQuestion'),
		image: document.getElementById('examAttemptImage'),
		options: document.getElementById('examAttemptOptions'),
		prev: document.getElementById('examAttemptPrev'),
		next: document.getElementById('examAttemptNext')
	};

	var state = {
		exam: null,
		questions: [],
		currentIndex: 0,
		answers: {},
		startedAt: '',
		submitting: false,
		timerId: null
	};

	function storageKey() {
		return 'edu_exam_session_' + examId;
	}

	function esc(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
		});
	}

	function setMessage(text, isError) {
		refs.message.textContent = text || '';
		refs.message.className = 'exam-attempt-message' + (isError ? ' is-error' : '');
	}

	function saveState() {
		try {
			window.localStorage.setItem(storageKey(), JSON.stringify({
				currentIndex: state.currentIndex,
				answers: state.answers,
				startedAt: state.startedAt
			}));
		} catch (e) {}
	}

	function loadSavedState() {
		try {
			var raw = window.localStorage.getItem(storageKey());
			if (!raw) {
				return null;
			}
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	}

	function clearSavedState() {
		try {
			window.localStorage.removeItem(storageKey());
		} catch (e) {}
	}

	function redirectToResult(done) {
		window.location.href = resultPageUrl + '?exam_id=' + encodeURIComponent(examId) + '&batch_id=' + encodeURIComponent(batchId) + (done ? '&done=1' : '');
	}

	function formatTime(totalSeconds) {
		totalSeconds = Math.max(0, totalSeconds);
		var hours = Math.floor(totalSeconds / 3600);
		var minutes = Math.floor((totalSeconds % 3600) / 60);
		var seconds = totalSeconds % 60;
		if (hours > 0) {
			return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
		}
		return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
	}

	function updateTimer() {
		if (!state.exam || !state.startedAt) {
			refs.timer.textContent = '00:00';
			return;
		}
		var startedAt = new Date(state.startedAt).getTime();
		if (!startedAt) {
			refs.timer.textContent = '00:00';
			return;
		}
		var remaining = Math.floor((startedAt + (Number(state.exam.timeDuration || 0) * 60 * 1000) - Date.now()) / 1000);
		if (remaining <= 0) {
			refs.timer.textContent = '00:00';
			if (state.timerId) {
				window.clearInterval(state.timerId);
				state.timerId = null;
			}
			submitExam(true);
			return;
		}
		refs.timer.textContent = formatTime(remaining);
	}

	function startTimer() {
		if (state.timerId) {
			window.clearInterval(state.timerId);
		}
		updateTimer();
		state.timerId = window.setInterval(updateTimer, 1000);
	}

	function setLoadingView(text) {
		setMessage(text || 'Loading assessment...', false);
		refs.title.textContent = 'Question';
		refs.counter.textContent = '0/0';
		refs.progress.style.width = '0';
		refs.question.textContent = 'Loading assessment...';
		refs.image.style.display = 'none';
		refs.options.innerHTML = '';
		refs.prev.disabled = true;
		refs.next.disabled = true;
	}

	function renderQuestion() {
		if (!state.questions.length) {
			refs.app.innerHTML = '<div class="exam-attempt-empty">No questions found for this exam.</div>';
			return;
		}
		var question = state.questions[state.currentIndex];
		var selected = state.answers[question.id] || '';
		refs.title.textContent = 'Question ' + (state.currentIndex + 1);
		refs.counter.textContent = (state.currentIndex + 1) + '/' + state.questions.length;
		refs.progress.style.width = (((state.currentIndex + 1) / state.questions.length) * 100) + '%';
		refs.question.textContent = question.question || 'Question';
		if (question.questionImageUrl) {
			refs.image.src = question.questionImageUrl;
			refs.image.style.display = '';
		} else {
			refs.image.removeAttribute('src');
			refs.image.style.display = 'none';
		}
		var labels = ['A', 'B', 'C', 'D'];
		refs.options.innerHTML = (question.options || []).map(function (optionText, index) {
			var label = labels[index] || String(index + 1);
			var activeClass = selected === label ? ' is-active' : '';
			return '' +
				'<button type="button" class="exam-attempt-option' + activeClass + '" data-answer="' + esc(label) + '">' +
					'<span class="exam-attempt-option-key">' + esc(label) + '</span>' +
					'<span class="exam-attempt-option-text">' + esc(optionText || ('Option ' + label)) + '</span>' +
				'</button>';
		}).join('');
		refs.prev.disabled = state.currentIndex === 0 || state.submitting;
		refs.next.disabled = state.submitting;
		refs.next.textContent = state.currentIndex === state.questions.length - 1 ? 'Submit' : 'Next';
		Array.prototype.forEach.call(refs.options.querySelectorAll('.exam-attempt-option'), function (button) {
			button.addEventListener('click', function () {
				if (state.submitting) {
					return;
				}
				state.answers[question.id] = button.getAttribute('data-answer') || '';
				saveState();
				renderQuestion();
			});
		});
	}

	function normalizeSavedState() {
		var saved = loadSavedState();
		if (!saved) {
			state.startedAt = new Date().toISOString();
			return;
		}
		if (saved.answers && typeof saved.answers === 'object') {
			state.answers = saved.answers;
		}
		if (typeof saved.currentIndex === 'number' && saved.currentIndex >= 0 && saved.currentIndex < state.questions.length) {
			state.currentIndex = saved.currentIndex;
		}
		state.startedAt = saved.startedAt || new Date().toISOString();
	}

	function submitExam(autoSubmit) {
		if (state.submitting) {
			return;
		}
		state.submitting = true;
		setMessage(autoSubmit ? 'Time is over. Submitting your exam...' : 'Submitting your exam...', false);
		refs.prev.disabled = true;
		refs.next.disabled = true;
		fetch(submitUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify({
				exam_id: examId,
				started_at: state.startedAt,
				answers: state.answers
			})
		}).then(function (response) {
			return response.json();
		}).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			if (!ok) {
				throw new Error((res && (res.msg || res.message)) || 'Could not submit exam.');
			}
			clearSavedState();
			redirectToResult(true);
		}).catch(function (error) {
			state.submitting = false;
			refs.prev.disabled = state.currentIndex === 0;
			refs.next.disabled = false;
			setMessage(error && error.message ? error.message : 'Could not submit exam.', true);
		});
	}

	function bindActions() {
		refs.prev.addEventListener('click', function () {
			if (state.currentIndex > 0) {
				state.currentIndex -= 1;
				saveState();
				renderQuestion();
			}
		});
		refs.next.addEventListener('click', function () {
			if (state.currentIndex === state.questions.length - 1) {
				submitExam(false);
				return;
			}
			state.currentIndex += 1;
			saveState();
			renderQuestion();
		});
	}

	function loadPaper() {
		if (examId < 1) {
			setMessage('Invalid exam id.', true);
			refs.app.innerHTML = '<div class="exam-attempt-empty">Please open the exam from the exams list.</div>';
			return;
		}
		setLoadingView('Loading assessment...');
		fetch(paperUrl, {
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
				throw new Error((res && (res.msg || res.message)) || 'Could not load assessment.');
			}
			var data = res.data || {};
			if (data.alreadySubmitted) {
				redirectToResult(false);
				return;
			}
			state.exam = data.exam || null;
			state.questions = Array.isArray(data.questions) ? data.questions : [];
			normalizeSavedState();
			saveState();
			setMessage('', false);
			renderQuestion();
			startTimer();
			bindActions();
		}).catch(function (error) {
			setMessage(error && error.message ? error.message : 'Could not load assessment.', true);
			refs.app.innerHTML = '<div class="exam-attempt-empty"><a class="exam-attempt-btn secondary" href="' + esc(listPageUrl + '?batch_id=' + batchId) + '">Back to Assessments</a></div>';
		});
	}

	var omrBtn = document.getElementById('examAttemptOmrBtn');
	if (omrBtn) {
		omrBtn.addEventListener('click', function () {
			if (typeof downloadExamOmrSheet !== 'function') {
				alert('Download helper not loaded.');
				return;
			}
			var old = omrBtn.textContent;
			omrBtn.disabled = true;
			omrBtn.textContent = 'Preparing…';
			downloadExamOmrSheet({ apiUrl: omrApiUrl, token: token, examId: examId, mode: 'blank' }).catch(function (err) {
				alert(err && err.message ? err.message : 'Could not download ORM sheet.');
			}).then(function () {
				omrBtn.disabled = false;
				omrBtn.textContent = old;
			});
		});
	}

	loadPaper();
})();
</script>
