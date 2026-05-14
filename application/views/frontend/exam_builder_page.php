<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=9">
<div class="inst-detail-page exb-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Create Exam</div>
	</div>
	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-head">
				<h3>Create Exam</h3>
				<?php if (!empty($legacy_question_manage_url)) { ?>
				<a class="inst-see-all" href="<?php echo html_escape(isset($legacy_question_manage_url) ? $legacy_question_manage_url : '#'); ?>">Legacy Question Manager</a>
				<?php } ?>
			</div>
			<p class="inst-panel-intro">Create a test for your mobile app and website in two steps: save exam details, then add questions with options and the correct answer.</p>

			<div id="exbMsg" class="inst-muted small px-1 mb-3"></div>
			<div id="exbProgress" class="exb-upload-progress exb-hidden" aria-live="polite">
				<div class="exb-upload-progress-bar"><span id="exbProgressFill" class="exb-upload-progress-fill"></span></div>
				<div id="exbProgressText" class="exb-upload-progress-text">Preparing upload...</div>
			</div>

			<div class="exb-layout">
				<section class="exb-card">
					<div class="exb-card-head">
						<span class="exb-step-pill">Step 1</span>
						<h4>Assessment</h4>
					</div>
					<div class="exb-field-grid">
						<div class="exb-field">
							<label>Batch</label>
							<select id="exbBatch" class="edu_form_field">
								<option value="">Select batch</option>
							</select>
						</div>
						<div class="exb-field">
							<label>Test Name</label>
							<input type="text" id="exbName" class="edu_form_field" placeholder="Test Name">
						</div>
						<div class="exb-field">
							<label>Duration (In Minutes)</label>
							<input type="number" id="exbDuration" class="edu_form_field" min="1" placeholder="60">
						</div>
						<div class="exb-field">
							<label>Due Date</label>
							<input type="date" id="exbDate" class="edu_form_field">
						</div>
						<div class="exb-field">
							<label>Due Time</label>
							<input type="time" id="exbTime" class="edu_form_field">
						</div>
					</div>
					<div class="exb-actions">
						<button type="button" id="exbOpenQuestions" class="btn btn-primary">Add Questions</button>
						<button type="button" id="exbCancelEdit" class="btn btn-outline-secondary exb-hidden">Cancel Edit</button>
					</div>
				</section>

				<section class="exb-card exb-card-wide" id="exbQuestionSection">
					<div class="exb-card-head">
						<span class="exb-step-pill">Step 2</span>
						<h4>Add Questions</h4>
					</div>
					<div class="exb-question-label" id="exbQuestionLabel">Question 1</div>
					<div class="exb-field-grid exb-question-grid">
						<div class="exb-field">
							<label>Subject</label>
							<select id="exbSubject" class="edu_form_field">
								<option value="">Select subject</option>
							</select>
						</div>
						<div class="exb-field">
							<label>Chapter</label>
							<select id="exbChapter" class="edu_form_field">
								<option value="">Select chapter</option>
							</select>
						</div>
						<div class="exb-field exb-field-full">
							<label>Question</label>
							<textarea id="exbQuestion" class="edu_form_field" rows="3" placeholder="Question"></textarea>
						</div>
						<div class="exb-field">
							<label>Add Image</label>
							<input type="file" id="exbImage" class="edu_form_field" accept="image/*">
						</div>
						<div class="exb-field">
							<label>Question Marks</label>
							<input type="number" id="exbMarks" class="edu_form_field" min="1" step="0.01" value="1">
						</div>
						<div class="exb-field">
							<label>Option 1</label>
							<input type="text" id="exbOption1" class="edu_form_field" placeholder="Option 1">
						</div>
						<div class="exb-field">
							<label>Option 2</label>
							<input type="text" id="exbOption2" class="edu_form_field" placeholder="Option 2">
						</div>
						<div class="exb-field">
							<label>Option 3</label>
							<input type="text" id="exbOption3" class="edu_form_field" placeholder="Option 3">
						</div>
						<div class="exb-field">
							<label>Option 4</label>
							<input type="text" id="exbOption4" class="edu_form_field" placeholder="Option 4">
						</div>
					</div>
					<div class="exb-answer-wrap">
						<div class="exb-answer-label">Correct Answer</div>
						<div class="exb-answer-buttons">
							<button type="button" class="exb-answer-btn" data-answer="1">1</button>
							<button type="button" class="exb-answer-btn" data-answer="2">2</button>
							<button type="button" class="exb-answer-btn" data-answer="3">3</button>
							<button type="button" class="exb-answer-btn" data-answer="4">4</button>
						</div>
					</div>
					<div class="exb-actions">
						<button type="button" id="exbAddQuestion" class="btn btn-outline-primary">Add Question</button>
						<button type="button" id="exbCancelQuestionEdit" class="btn btn-outline-secondary exb-hidden">Cancel Question Edit</button>
						<button type="button" id="exbFinish" class="btn btn-primary">Finish Test</button>
					</div>
					<div id="exbLocalList" class="exb-local-list"></div>
				</section>

				<section class="exb-card exb-card-wide">
					<div class="exb-card-head">
						<span class="exb-step-pill">Saved</span>
						<h4>Created Exams</h4>
					</div>
					<div id="exbSavedList" class="inst-card-grid"></div>
				</section>
			</div>
		</div>
	</div>
</div>

<style>
.exb-page .inst-detail-container { max-width: 1180px; }
.exb-layout { display: grid; gap: 18px; }
.exb-card {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 18px;
	padding: 18px;
	box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
}
.exb-card-wide { width: 100%; }
.exb-card-head {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 16px;
	flex-wrap: wrap;
}
.exb-card-head h4 {
	margin: 0;
	font-size: 1.05rem;
	font-weight: 700;
	color: #0f172a;
}
.exb-step-pill {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 5px 12px;
	border-radius: 999px;
	background: #dbeafe;
	color: #2563eb;
	font-size: 0.78rem;
	font-weight: 700;
}
.exb-field-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 14px;
}
.exb-question-grid {
	grid-template-columns: repeat(2, minmax(0, 1fr));
}
.exb-field { min-width: 0; }
.exb-field-full { grid-column: 1 / -1; }
.exb-field label,
.exb-answer-label,
.exb-question-label {
	display: block;
	margin-bottom: 8px;
	font-size: 0.86rem;
	font-weight: 700;
	color: #0f172a;
}
.exb-question-label { margin-top: 4px; }
.exb-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	margin-top: 18px;
}
.exb-answer-wrap {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
	margin-top: 14px;
}
.exb-answer-buttons {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}
.exb-answer-btn {
	min-width: 48px;
	height: 48px;
	border: 0;
	border-radius: 12px;
	background: #e2eff9;
	color: #0f172a;
	font-weight: 700;
	box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.08);
}
.exb-answer-btn.is-active {
	background: #3b82f6;
	color: #fff;
	box-shadow: 0 8px 18px rgba(59, 130, 246, 0.25);
}
.exb-local-list {
	margin-top: 18px;
	display: flex;
	flex-direction: column;
	gap: 10px;
}
.exb-local-item {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: 12px;
	padding: 14px;
	border-radius: 14px;
	background: #f8fafc;
	border: 1px solid #e2e8f0;
}
.exb-local-title {
	margin: 0 0 6px;
	font-size: 0.94rem;
	font-weight: 700;
	color: #0f172a;
}
.exb-local-meta {
	margin: 0;
	font-size: 0.8rem;
	color: #64748b;
	line-height: 1.45;
}
.exb-hidden { display: none !important; }
.exb-local-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
}
.exb-local-edit,
.exb-local-remove {
	border: 0;
	background: transparent;
	font-size: 0.85rem;
	font-weight: 700;
	padding: 4px 0;
}
.exb-local-edit {
	color: #2563eb;
}
.exb-local-remove {
	color: #dc2626;
}
.exb-upload-progress {
	margin: 0 4px 18px;
	padding: 10px 12px;
	border: 1px solid #dbeafe;
	border-radius: 12px;
	background: #f8fbff;
}
.exb-upload-progress-bar {
	width: 100%;
	height: 10px;
	border-radius: 999px;
	background: #e5edf8;
	overflow: hidden;
}
.exb-upload-progress-fill {
	display: block;
	height: 100%;
	width: 0;
	background: linear-gradient(90deg, #2563eb, #38bdf8);
	transition: width 0.2s ease;
}
.exb-upload-progress-text {
	margin-top: 8px;
	font-size: 0.84rem;
	font-weight: 600;
	color: #334155;
}
.exb-empty {
	padding: 14px;
	border-radius: 14px;
	border: 1px dashed #cbd5e1;
	color: #64748b;
	background: #fff;
}
@media (max-width: 767px) {
	.exb-field-grid,
	.exb-question-grid {
		grid-template-columns: 1fr;
	}
	.exb-local-item {
		flex-direction: column;
	}
}
</style>

<script>
(function () {
	'use strict';

	var token = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var initialBatchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var roleLabel = <?php echo json_encode(isset($exam_builder_role_label) ? $exam_builder_role_label : 'Teacher'); ?>;
	var batchOptionsUrl = <?php echo json_encode(isset($batch_mylist_data_url) ? $batch_mylist_data_url : ''); ?>;
	var subjectsUrl = <?php echo json_encode(isset($batch_subjects_api_url) ? $batch_subjects_api_url : ''); ?>;
	var chaptersUrl = <?php echo json_encode(isset($batch_chapters_api_url) ? $batch_chapters_api_url : ''); ?>;
	var listUrl = <?php echo json_encode(isset($exam_list_api_url) ? $exam_list_api_url : ''); ?>;
	var detailsUrl = <?php echo json_encode(isset($exam_details_api_url) ? $exam_details_api_url : ''); ?>;
	var addUrl = <?php echo json_encode(isset($exam_add_api_url) ? $exam_add_api_url : ''); ?>;
	var editUrl = <?php echo json_encode(isset($exam_edit_api_url) ? $exam_edit_api_url : ''); ?>;
	var deleteUrl = <?php echo json_encode(isset($exam_delete_api_url) ? $exam_delete_api_url : ''); ?>;

	var state = {
		selectedAnswer: '',
		questions: [],
		batchNameMap: {},
		saving: false,
		currentExamId: 0,
		currentQuestionEditIndex: -1,
		defaultDate: '',
		defaultTime: ''
	};

	var refs = {
		msg: document.getElementById('exbMsg'),
		batch: document.getElementById('exbBatch'),
		name: document.getElementById('exbName'),
		duration: document.getElementById('exbDuration'),
		date: document.getElementById('exbDate'),
		time: document.getElementById('exbTime'),
		subject: document.getElementById('exbSubject'),
		chapter: document.getElementById('exbChapter'),
		question: document.getElementById('exbQuestion'),
		image: document.getElementById('exbImage'),
		marks: document.getElementById('exbMarks'),
		option1: document.getElementById('exbOption1'),
		option2: document.getElementById('exbOption2'),
		option3: document.getElementById('exbOption3'),
		option4: document.getElementById('exbOption4'),
		openQuestions: document.getElementById('exbOpenQuestions'),
		cancelEdit: document.getElementById('exbCancelEdit'),
		addQuestion: document.getElementById('exbAddQuestion'),
		cancelQuestionEdit: document.getElementById('exbCancelQuestionEdit'),
		finish: document.getElementById('exbFinish'),
		questionLabel: document.getElementById('exbQuestionLabel'),
		localList: document.getElementById('exbLocalList'),
		savedList: document.getElementById('exbSavedList'),
		questionSection: document.getElementById('exbQuestionSection'),
		progress: document.getElementById('exbProgress'),
		progressFill: document.getElementById('exbProgressFill'),
		progressText: document.getElementById('exbProgressText')
	};

	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
		});
	}

	function showMessage(text, isError) {
		refs.msg.className = isError ? 'small text-danger px-1 mb-3' : 'small text-success px-1 mb-3';
		refs.msg.textContent = text || '';
	}

	function setLoader(show) {
		var nodes = document.querySelectorAll('.edu_preloader');
		Array.prototype.forEach.call(nodes, function (el) {
			el.style.backgroundColor = 'rgba(255,255,255,0.80)';
			el.style.display = show ? 'block' : 'none';
		});
	}

	function setUploadProgress(percent, text) {
		if (!refs.progress || !refs.progressFill || !refs.progressText) {
			return;
		}
		refs.progress.classList.remove('exb-hidden');
		if (percent != null && !isNaN(percent)) {
			refs.progressFill.style.width = Math.max(0, Math.min(100, percent)) + '%';
		}
		refs.progressText.textContent = text || 'Uploading...';
	}

	function resetUploadProgress() {
		if (!refs.progress || !refs.progressFill || !refs.progressText) {
			return;
		}
		refs.progress.classList.add('exb-hidden');
		refs.progressFill.style.width = '0%';
		refs.progressText.textContent = 'Preparing upload...';
	}

	function uploadFormData(url, formData) {
		return new Promise(function (resolve, reject) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', url, true);
			xhr.setRequestHeader('Authorization', 'Bearer ' + token);
			xhr.upload.addEventListener('progress', function (ev) {
				if (ev.lengthComputable) {
					var percent = Math.round((ev.loaded / ev.total) * 100);
					setUploadProgress(percent, percent >= 100 ? 'Upload complete, processing on server...' : ('Uploading... ' + percent + '%'));
				} else {
					setUploadProgress(null, 'Uploading...');
				}
			});
			xhr.upload.addEventListener('load', function () {
				setUploadProgress(100, 'Upload complete, processing on server...');
			});
			xhr.onload = function () {
				var body = xhr.responseText || '{}';
				try {
					resolve(JSON.parse(body));
				} catch (err) {
					reject(err);
				}
			};
			xhr.onerror = function () {
				reject(new Error('Network error'));
			};
			xhr.onabort = function () {
				reject(new Error('Upload aborted'));
			};
			xhr.send(formData);
		});
	}

	function postWebsiteJson(url, payload) {
		return fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: JSON.stringify(payload || {})
		}).then(function (r) { return r.json(); });
	}

	function postApiJson(url, payload) {
		return fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify(payload || {})
		}).then(function (r) { return r.json(); });
	}

	function setSelectedAnswer(answer) {
		state.selectedAnswer = String(answer || '');
		var buttons = document.querySelectorAll('.exb-answer-btn');
		Array.prototype.forEach.call(buttons, function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-answer') === state.selectedAnswer);
		});
	}

	function setExamMode(editing) {
		var isEditing = !!editing;
		refs.openQuestions.textContent = isEditing ? 'Edit Questions' : 'Add Questions';
		if (!state.saving) {
			refs.finish.textContent = isEditing ? 'Update Test' : 'Finish Test';
		}
		if (refs.cancelEdit) {
			refs.cancelEdit.classList.toggle('exb-hidden', !isEditing);
		}
	}

	function setQuestionMode(editing) {
		var isEditing = !!editing;
		refs.addQuestion.textContent = isEditing ? 'Update Question' : 'Add Question';
		if (refs.cancelQuestionEdit) {
			refs.cancelQuestionEdit.classList.toggle('exb-hidden', !isEditing);
		}
	}

	function currentBatchId() {
		return parseInt(refs.batch.value || 0, 10) || 0;
	}

	function validateMeta() {
		var batchId = currentBatchId();
		var duration = parseInt(refs.duration.value || 0, 10) || 0;
		var meta = {
			batch_id: batchId,
			name: refs.name.value.trim(),
			time_duration: duration,
			mock_sheduled_date: refs.date.value,
			mock_sheduled_time: refs.time.value
		};
		if (meta.batch_id < 1) {
			showMessage('Please select a batch.', true);
			return null;
		}
		if (!meta.name) {
			showMessage('Please enter test name.', true);
			return null;
		}
		if (meta.time_duration < 1) {
			showMessage('Please enter duration in minutes.', true);
			return null;
		}
		if (!meta.mock_sheduled_date) {
			showMessage('Please select due date.', true);
			return null;
		}
		if (!meta.mock_sheduled_time) {
			showMessage('Please select due time.', true);
			return null;
		}
		return meta;
	}

	function defaultDateValue() {
		return state.defaultDate || '';
	}

	function defaultTimeValue() {
		return state.defaultTime || '';
	}

	function clearMetaFields(preserveBatch) {
		if (!preserveBatch) {
			refs.batch.value = '';
		}
		refs.name.value = '';
		refs.duration.value = '';
		refs.date.value = defaultDateValue();
		refs.time.value = defaultTimeValue();
	}

	function resetQuestionForm() {
		refs.question.value = '';
		refs.image.value = '';
		refs.subject.value = '';
		refs.chapter.innerHTML = '<option value="">Select chapter</option>';
		refs.option1.value = '';
		refs.option2.value = '';
		refs.option3.value = '';
		refs.option4.value = '';
		refs.marks.value = '1';
		setSelectedAnswer('');
		state.currentQuestionEditIndex = -1;
		setQuestionMode(false);
		refs.questionLabel.textContent = 'Question ' + (state.questions.length + 1);
	}

	function resetBuilderToCreateMode(preserveBatch) {
		state.currentExamId = 0;
		state.questions = [];
		clearMetaFields(!!preserveBatch);
		resetQuestionForm();
		renderLocalQuestions();
		setExamMode(false);
	}

	function renderLocalQuestions() {
		if (!state.questions.length) {
			refs.localList.innerHTML = '<div class="exb-empty">No questions added yet.</div>';
			refs.questionLabel.textContent = 'Question 1';
			return;
		}
		var html = '';
		for (var i = 0; i < state.questions.length; i++) {
			var item = state.questions[i];
			var imageText = '';
			if (item.imageFile) {
				imageText = ' | Image: ' + esc(item.imageFile.name);
			} else if (item.question_image) {
				imageText = ' | Image attached';
			}
			html += '' +
				'<div class="exb-local-item">' +
					'<div>' +
						'<p class="exb-local-title">Question ' + (i + 1) + '</p>' +
						'<p class="exb-local-meta">' + esc(item.question) + '</p>' +
						'<p class="exb-local-meta">Correct: Option ' + esc(item.correct_option) + ' | Marks: ' + esc(item.question_mask) + imageText + '</p>' +
					'</div>' +
					'<div class="exb-local-actions">' +
						'<button type="button" class="exb-local-edit" data-index="' + i + '">Edit</button>' +
						'<button type="button" class="exb-local-remove" data-index="' + i + '">Remove</button>' +
					'</div>' +
				'</div>';
		}
		refs.localList.innerHTML = html;
		refs.questionLabel.textContent = 'Question ' + (state.questions.length + 1);
		var editButtons = refs.localList.querySelectorAll('.exb-local-edit');
		Array.prototype.forEach.call(editButtons, function (btn) {
			btn.addEventListener('click', function () {
				var idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
				if (idx > -1) {
					loadQuestionIntoForm(idx);
				}
			});
		});
		var removeButtons = refs.localList.querySelectorAll('.exb-local-remove');
		Array.prototype.forEach.call(removeButtons, function (btn) {
			btn.addEventListener('click', function () {
				var idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
				if (idx > -1) {
					state.questions.splice(idx, 1);
					if (state.currentQuestionEditIndex === idx) {
						resetQuestionForm();
					} else if (state.currentQuestionEditIndex > idx) {
						state.currentQuestionEditIndex -= 1;
					}
					renderLocalQuestions();
				}
			});
		});
	}

	function normalizeOptionNumber(answer) {
		var raw = String(answer == null ? '' : answer).toUpperCase();
		if (raw === 'A' || raw === '1') return '1';
		if (raw === 'B' || raw === '2') return '2';
		if (raw === 'C' || raw === '3') return '3';
		if (raw === 'D' || raw === '4') return '4';
		return '';
	}

	function parseQuestionOptions(value) {
		if (Array.isArray(value)) {
			return value.slice(0, 4);
		}
		if (typeof value === 'string' && value) {
			try {
				var parsed = JSON.parse(value);
				if (Array.isArray(parsed)) {
					return parsed.slice(0, 4);
				}
			} catch (e) {}
		}
		return ['', '', '', ''];
	}

	function setChapterOptions(rows) {
		var html = '<option value="">Select chapter</option>';
		for (var i = 0; i < rows.length; i++) {
			html += '<option value="' + esc(rows[i].chapterId) + '">' + esc(rows[i].chapterName) + '</option>';
		}
		refs.chapter.innerHTML = html;
	}

	function loadChapters() {
		var batchId = currentBatchId();
		var subjectId = parseInt(refs.subject.value || 0, 10) || 0;
		if (batchId < 1 || subjectId < 1) {
			setChapterOptions([]);
			return Promise.resolve();
		}
		return postApiJson(chaptersUrl, { batch_id: batchId, subject_id: subjectId }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var rows = ok && res.data && Array.isArray(res.data.chapters) ? res.data.chapters : [];
			setChapterOptions(rows);
		}).catch(function () {
			setChapterOptions([]);
		});
	}

	function loadSubjects() {
		var batchId = currentBatchId();
		refs.subject.innerHTML = '<option value="">Select subject</option>';
		setChapterOptions([]);
		if (batchId < 1) {
			return Promise.resolve();
		}
		return postApiJson(subjectsUrl, { batch_id: batchId }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var rows = ok && res.data && Array.isArray(res.data.subjects) ? res.data.subjects : [];
			var html = '<option value="">Select subject</option>';
			for (var i = 0; i < rows.length; i++) {
				html += '<option value="' + esc(rows[i].subjectId) + '">' + esc(rows[i].subjectName) + '</option>';
			}
			refs.subject.innerHTML = html;
		}).catch(function () {
			refs.subject.innerHTML = '<option value="">Select subject</option>';
		});
	}

	function loadBatchOptions() {
		return postWebsiteJson(batchOptionsUrl, { page: 1, limit: 100 }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var rows = ok && res.data && Array.isArray(res.data.enrolled_batches) ? res.data.enrolled_batches : [];
			var html = '<option value="">Select batch</option>';
			state.batchNameMap = {};
			for (var i = 0; i < rows.length; i++) {
				var row = rows[i];
				var bid = parseInt(row.batchId || row.batch_id || 0, 10);
				if (bid < 1) {
					continue;
				}
				var name = row.batchName || row.batch_name || ('Batch #' + bid);
				state.batchNameMap[bid] = name;
				html += '<option value="' + bid + '"' + (bid === initialBatchId ? ' selected' : '') + '>' + esc(name) + '</option>';
			}
			refs.batch.innerHTML = html;
		}).catch(function () {
			showMessage('Could not load batches.', true);
		});
	}

	function renderSavedExams(rows) {
		if (!rows.length) {
			refs.savedList.innerHTML = '<div class="exb-empty">No exams found for the selected batch.</div>';
			return;
		}
		var html = '';
		for (var i = 0; i < rows.length; i++) {
			var row = rows[i];
			html += '' +
				'<div class="inst-batch-card">' +
					'<div class="inst-card-body">' +
						'<p class="inst-card-title-sm">' + esc(row.name || 'Exam') + '</p>' +
						'<p class="inst-card-sub">' + esc([(row.scheduledDate || ''), (row.scheduledTime || '')].filter(Boolean).join(' | ')) + '</p>' +
						'<p class="inst-teacher-card-text">Questions: ' + esc(row.totalQuestion || row.questionCount || 0) + ' | Marks: ' + esc(row.totalMarks || 0) + ' | Duration: ' + esc(row.timeDuration || '-') + ' min</p>' +
						'<div class="inst-teacher-card-actions">' +
							'<button type="button" class="btn btn-sm btn-outline-primary exb-edit" data-id="' + esc(row.id) + '"><i class="fas fa-pen"></i>Edit</button>' +
							'<button type="button" class="btn btn-sm btn-outline-danger exb-del" data-id="' + esc(row.id) + '"><i class="fas fa-trash-alt"></i>Delete</button>' +
						'</div>' +
					'</div>' +
				'</div>';
		}
		refs.savedList.innerHTML = html;
		var editButtons = refs.savedList.querySelectorAll('.exb-edit');
		Array.prototype.forEach.call(editButtons, function (btn) {
			btn.addEventListener('click', function () {
				var examId = parseInt(btn.getAttribute('data-id') || 0, 10);
				if (examId > 0) {
					loadExamDetails(examId);
				}
			});
		});
		var deleteButtons = refs.savedList.querySelectorAll('.exb-del');
		Array.prototype.forEach.call(deleteButtons, function (btn) {
			btn.addEventListener('click', function () {
				var examId = parseInt(btn.getAttribute('data-id') || 0, 10);
				if (examId > 0) {
					deleteExam(examId);
				}
			});
		});
	}

	function hydrateQuestions(rows) {
		var list = [];
		if (!Array.isArray(rows)) {
			return list;
		}
		for (var i = 0; i < rows.length; i++) {
			var row = rows[i] || {};
			var options = parseQuestionOptions(row.options);
			list.push({
				question_id: parseInt(row.id || row.question_id || 0, 10) || 0,
				subject_id: parseInt(row.subjectId || row.subject_id || 0, 10) || 0,
				chapter_id: parseInt(row.chapterId || row.chapter_id || 0, 10) || 0,
				question: (row.question || '').toString(),
				options: [
					(options[0] || '').toString(),
					(options[1] || '').toString(),
					(options[2] || '').toString(),
					(options[3] || '').toString()
				],
				correct_option: normalizeOptionNumber(row.answer),
				answer: (row.answer || '').toString(),
				question_mask: parseFloat(row.questionMask || row.question_mask || 1) || 1,
				question_image: (row.questionImage || row.question_image || '').toString(),
				questionImageUrl: (row.questionImageUrl || '').toString(),
				imageFile: null
			});
		}
		return list;
	}

	function loadExamDetails(examId) {
		setLoader(true);
		postApiJson(detailsUrl, { exam_id: examId }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var exam = ok ? (res.exam || (res.data && res.data.exam) || null) : null;
			if (!ok || !exam) {
				throw new Error((res && (res.msg || res.message)) || 'Could not load exam');
			}
			state.currentExamId = parseInt(exam.id || examId, 10) || examId;
			if (parseInt(exam.batchId || 0, 10) > 0) {
				refs.batch.value = String(parseInt(exam.batchId, 10));
			}
			refs.name.value = exam.name || '';
			refs.duration.value = exam.timeDuration || '';
			refs.date.value = exam.scheduledDate || '';
			refs.time.value = exam.scheduledTime || '';
			state.questions = hydrateQuestions(exam.questionDetails || []);
			renderLocalQuestions();
			resetQuestionForm();
			setExamMode(true);
			showMessage('Loaded exam for editing. Update any question and save again.', false);
			return loadSubjects().then(function () {
				refs.questionSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		}).catch(function (err) {
			showMessage(err && err.message ? err.message : 'Could not load exam.', true);
		}).then(function () {
			setLoader(false);
		});
	}

	function loadExamList() {
		var batchId = currentBatchId();
		if (batchId < 1) {
			renderSavedExams([]);
			return;
		}
		postApiJson(listUrl, { batch_id: batchId, page: 1, limit: 50 }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var rows = ok && res.data && Array.isArray(res.data.exams) ? res.data.exams : [];
			if (!ok) {
				throw new Error((res && res.msg) || 'Could not load exams');
			}
			renderSavedExams(rows);
		}).catch(function (err) {
			renderSavedExams([]);
			showMessage(err && err.message ? err.message : 'Could not load exams.', true);
		});
	}

	function deleteExam(examId) {
		if (!window.confirm('Delete this exam?')) {
			return;
		}
		setLoader(true);
		postApiJson(deleteUrl, { exam_id: examId }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			showMessage((res && res.msg) || (ok ? 'Exam deleted.' : 'Could not delete exam.'), !ok);
			if (ok) {
				if (state.currentExamId === examId) {
					resetBuilderToCreateMode(true);
				}
				loadExamList();
			}
		}).catch(function () {
			showMessage('Could not delete exam.', true);
		}).then(function () {
			setLoader(false);
		});
	}

	function openQuestionStep() {
		var meta = validateMeta();
		if (!meta) {
			return;
		}
		showMessage((state.currentExamId > 0 ? 'Editing saved exam.' : roleLabel + ' exam details saved locally.') + ' Add questions below.', false);
		refs.questionSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function loadQuestionIntoForm(index) {
		var item = state.questions[index];
		if (!item) {
			return;
		}
		state.currentQuestionEditIndex = index;
		setQuestionMode(true);
		refs.questionLabel.textContent = 'Edit Question ' + (index + 1);
		refs.question.value = item.question || '';
		refs.option1.value = item.options[0] || '';
		refs.option2.value = item.options[1] || '';
		refs.option3.value = item.options[2] || '';
		refs.option4.value = item.options[3] || '';
		refs.marks.value = item.question_mask || 1;
		refs.image.value = '';
		refs.subject.value = item.subject_id ? String(item.subject_id) : '';
		setSelectedAnswer(item.correct_option || normalizeOptionNumber(item.answer));
		loadChapters().then(function () {
			refs.chapter.value = item.chapter_id ? String(item.chapter_id) : '';
		});
		refs.questionSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function addQuestionLocally() {
		if (!validateMeta()) {
			return;
		}
		var subjectId = parseInt(refs.subject.value || 0, 10) || 0;
		if (subjectId < 1) {
			showMessage('Please select subject.', true);
			return;
		}
		var options = [
			refs.option1.value.trim(),
			refs.option2.value.trim(),
			refs.option3.value.trim(),
			refs.option4.value.trim()
		];
		if (!refs.question.value.trim()) {
			showMessage('Please enter question text.', true);
			return;
		}
		if (options.join('').trim() === '' || options.some(function (v) { return !v; })) {
			showMessage('Please fill all four options.', true);
			return;
		}
		if (!state.selectedAnswer) {
			showMessage('Please choose the correct answer.', true);
			return;
		}
		var existing = state.currentQuestionEditIndex > -1 ? state.questions[state.currentQuestionEditIndex] : null;
		var question = {
			question_id: existing ? (parseInt(existing.question_id || 0, 10) || 0) : 0,
			subject_id: subjectId,
			chapter_id: parseInt(refs.chapter.value || 0, 10) || 0,
			question: refs.question.value.trim(),
			options: options,
			correct_option: state.selectedAnswer,
			answer: ({'1': 'A', '2': 'B', '3': 'C', '4': 'D'})[state.selectedAnswer] || '',
			question_mask: parseFloat(refs.marks.value || 1) || 1,
			question_image: existing ? (existing.question_image || '') : '',
			questionImageUrl: existing ? (existing.questionImageUrl || '') : '',
			imageFile: refs.image.files[0] || null
		};
		var wasEditingQuestion = state.currentQuestionEditIndex > -1;
		if (wasEditingQuestion) {
			state.questions[state.currentQuestionEditIndex] = question;
		} else {
			state.questions.push(question);
		}
		renderLocalQuestions();
		resetQuestionForm();
		showMessage(wasEditingQuestion ? 'Question updated. You can continue editing.' : 'Question added. You can add more or finish the test.', false);
	}

	function buildExamFormData(meta) {
		var fd = new FormData();
		fd.append('batch_id', meta.batch_id);
		fd.append('name', meta.name);
		fd.append('time_duration', meta.time_duration);
		fd.append('mock_sheduled_date', meta.mock_sheduled_date);
		fd.append('mock_sheduled_time', meta.mock_sheduled_time);
		fd.append('type', '1');
		fd.append('format', '2');
		fd.append('marking_parcent', '0');
		if (state.currentExamId > 0) {
			fd.append('exam_id', state.currentExamId);
		}

		var totalMarks = 0;
		var payloadQuestions = [];
		for (var i = 0; i < state.questions.length; i++) {
			var item = state.questions[i];
			var imageField = item.imageFile ? ('question_image_' + i) : '';
			totalMarks += parseFloat(item.question_mask || 1) || 1;
			payloadQuestions.push({
				question_id: item.question_id || 0,
				subject_id: item.subject_id,
				chapter_id: item.chapter_id,
				question: item.question,
				options: item.options,
				correct_option: item.correct_option,
				answer: item.answer,
				question_mask: item.question_mask,
				question_image: item.question_image || '',
				image_field: imageField
			});
			if (item.imageFile) {
				fd.append(imageField, item.imageFile);
			}
		}
		fd.append('total_question', String(state.questions.length));
		fd.append('total_marks', String(totalMarks));
		fd.append('questions_json', JSON.stringify(payloadQuestions));
		return fd;
	}

	function finishExam() {
		var meta = validateMeta();
		if (!meta) {
			return;
		}
		if (!state.questions.length) {
			showMessage('Please add at least one question.', true);
			return;
		}
		if (state.saving) {
			return;
		}
		state.saving = true;
		setLoader(true);
		setUploadProgress(0, 'Preparing upload...');
		refs.finish.disabled = true;
		refs.finish.textContent = state.currentExamId > 0 ? 'Updating...' : 'Saving...';

		uploadFormData(state.currentExamId > 0 ? editUrl : addUrl, buildExamFormData(meta)).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			showMessage((res && res.msg) || (ok ? (state.currentExamId > 0 ? 'Exam updated successfully.' : 'Exam created successfully.') : 'Could not save exam.'), !ok);
			if (!ok) {
				return;
			}
			resetBuilderToCreateMode(true);
			loadSubjects();
			loadExamList();
		}).catch(function () {
			showMessage('Could not save exam.', true);
		}).then(function () {
			state.saving = false;
			setLoader(false);
			resetUploadProgress();
			refs.finish.disabled = false;
			setExamMode(state.currentExamId > 0);
		});
	}

	Array.prototype.forEach.call(document.querySelectorAll('.exb-answer-btn'), function (btn) {
		btn.addEventListener('click', function () {
			setSelectedAnswer(btn.getAttribute('data-answer'));
		});
	});

	refs.batch.addEventListener('change', function () {
		loadSubjects().then(loadChapters);
		loadExamList();
	});
	refs.subject.addEventListener('change', loadChapters);
	refs.openQuestions.addEventListener('click', openQuestionStep);
	if (refs.cancelEdit) {
		refs.cancelEdit.addEventListener('click', function () {
			resetBuilderToCreateMode(true);
			loadSubjects();
			showMessage('Edit cancelled. You can create a new exam now.', false);
		});
	}
	refs.addQuestion.addEventListener('click', addQuestionLocally);
	if (refs.cancelQuestionEdit) {
		refs.cancelQuestionEdit.addEventListener('click', function () {
			resetQuestionForm();
			showMessage('Question edit cancelled.', false);
		});
	}
	refs.finish.addEventListener('click', finishExam);

	document.addEventListener('DOMContentLoaded', function () {
		var now = new Date();
		state.defaultDate = now.toISOString().slice(0, 10);
		state.defaultTime = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
		refs.date.value = state.defaultDate;
		refs.time.value = state.defaultTime;
		renderLocalQuestions();
		setExamMode(false);
		setQuestionMode(false);
		loadBatchOptions().then(function () {
			return loadSubjects();
		}).then(function () {
			return loadExamList();
		});
	});
})();
</script>
