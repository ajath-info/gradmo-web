<style>
.stu-exam-page {
	max-width: 430px;
	margin: 0 auto;
	padding: 18px 16px 36px;
}
.stu-exam-topbar {
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	margin-bottom: 18px;
}
.stu-exam-back {
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
.stu-exam-title {
	font-size: 24px;
	font-weight: 700;
	color: #1f2433;
	margin: 0;
}
.stu-exam-msg {
	font-size: 14px;
	margin-bottom: 14px;
}
.stu-exam-msg.is-error {
	color: #cf3344;
}
.stu-exam-section {
	background: #eaf6ff;
	border-radius: 28px;
	padding: 14px;
	margin-bottom: 20px;
}
.stu-exam-section-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12px;
	padding: 4px 6px;
}
.stu-exam-section-head h2 {
	font-size: 18px;
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
}
.stu-exam-card {
	background: #fff;
	border-radius: 22px;
	padding: 14px;
	box-shadow: 0 12px 24px rgba(38, 76, 157, 0.08);
	overflow: hidden;
}
.stu-exam-image {
	width: 100%;
	height: 165px;
	border-radius: 18px;
	object-fit: cover;
	display: block;
	background: linear-gradient(135deg, #dde7ff, #f4f8ff);
}
.stu-exam-image.is-empty {
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 38px;
	color: #4c78f0;
}
.stu-exam-name {
	font-size: 16px;
	font-weight: 700;
	color: #323643;
	margin: 14px 0 10px;
}
.stu-exam-meta-row {
	display: flex;
	flex-wrap: wrap;
	gap: 10px 18px;
	font-size: 14px;
	color: #535c73;
	margin-bottom: 8px;
}
.stu-exam-meta-row strong {
	color: #212634;
	font-weight: 700;
}
.stu-exam-pills {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin: 12px 0 14px;
}
.stu-exam-pill {
	border-radius: 999px;
	padding: 5px 14px;
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
.stu-exam-action,
.stu-exam-link {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	min-height: 50px;
	border-radius: 999px;
	text-decoration: none;
	font-weight: 700;
	font-size: 15px;
	border: 0;
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
.stu-exam-result-box {
	background: #f5f7fb;
	border-radius: 14px;
	padding: 12px 14px;
	margin: 8px 0 14px;
}
.stu-exam-result-box h3 {
	font-size: 18px;
	margin: 0 0 4px;
	color: #252b39;
}
.stu-exam-result-box p {
	margin: 0;
	font-size: 14px;
	color: #5f6780;
}
.stu-exam-empty {
	background: #fff;
	border-radius: 18px;
	padding: 24px 18px;
	text-align: center;
	color: #68738d;
	font-size: 14px;
}
</style>

<div class="stu-exam-page">
	<div class="stu-exam-topbar">
		<a href="javascript:history.back()" class="stu-exam-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<h1 class="stu-exam-title">Upcoming Exams</h1>
	</div>

	<div id="stuExamMsg" class="stu-exam-msg"></div>

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

<script>
(function () {
	'use strict';

	var endpoint = <?php echo json_encode((string) (isset($student_exam_dashboard_api_url) ? $student_exam_dashboard_api_url : site_url('api/batch/student-exam-dashboard'))); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var attemptUrl = <?php echo json_encode((string) (isset($student_exam_attempt_url) ? $student_exam_attempt_url : site_url('batch/exam-attempt'))); ?>;
	var resultUrl = <?php echo json_encode((string) (isset($student_exam_result_url) ? $student_exam_result_url : site_url('batch/exam-result'))); ?>;

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
				'<a class="stu-exam-action primary" href="' + esc(buildHref(attemptUrl, item.id)) + '">Start Assessment</a>' +
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
				'<a class="stu-exam-action success" href="' + esc(buildHref(resultUrl, item.id)) + '">Completed</a>' +
			'</article>';
	}

	function renderSection(target, items, renderer, emptyText) {
		if (!items.length) {
			target.innerHTML = '<div class="stu-exam-empty">' + esc(emptyText) + '</div>';
			return;
		}
		target.innerHTML = items.map(renderer).join('');
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
