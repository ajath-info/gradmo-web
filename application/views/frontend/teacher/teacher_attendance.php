<div class="inst-detail-page">
	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-head">
				<h3>Add attendance (date + time + multiple students)</h3>
			</div>
			<div class="inst-list-filter-bar">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item">
						<label for="ta_add_date">Date</label>
						<input type="date" id="ta_add_date" class="edu_form_field">
					</div>
					<div class="inst-list-filter-item">
						<label for="ta_add_time">Time</label>
						<input type="text" id="ta_add_time" class="edu_form_field" placeholder="10:30">
					</div>
					<div class="inst-list-filter-item">
						<label for="ta_add_status">Day status (optional)</label>
						<select id="ta_add_status" class="edu_form_field">
							<option value="">Auto (present/late by time)</option>
							<option value="present">Present</option>
							<option value="absent">Absent</option>
							<!-- <option value="late">Late</option>
							<option value="half">Half day</option> -->
						</select>
					</div>
				</div>
				<div class="inst-list-filter-actions">
					<button type="button" class="btn btn-outline-secondary" id="ta_add_select_all">Select all</button>
					<button type="button" class="btn btn-outline-secondary" id="ta_add_clear_all">Clear</button>
					<button type="button" class="btn btn-primary" id="ta_add_submit">Save attendance</button>
				</div>
			</div>
			<div id="ta_add_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="ta_add_students_wrap" class="ta-add-students-wrap">
				<div class="inst-muted">Loading students...</div>
			</div>
		</div>
		<div class="inst-detail-panel">
			<div class="inst-panel-head">
				<h3>Attendance register (matrix)</h3>
			</div>
			<p class="inst-panel-intro">Students as rows, calendar days as columns. Click a cell to cycle: blank → Present → Late → Half day → Absent → blank. Then save.</p>
			<div class="inst-list-filter-bar">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item">
						<label for="ta_mx_year">Year</label>
						<input type="number" id="ta_mx_year" class="edu_form_field" min="2000" max="2100" step="1">
					</div>
					<div class="inst-list-filter-item">
						<label for="ta_mx_month">Month</label>
						<select id="ta_mx_month" class="edu_form_field">
							<option value="1">January</option>
							<option value="2">February</option>
							<option value="3">March</option>
							<option value="4">April</option>
							<option value="5">May</option>
							<option value="6">June</option>
							<option value="7">July</option>
							<option value="8">August</option>
							<option value="9">September</option>
							<option value="10">October</option>
							<option value="11">November</option>
							<option value="12">December</option>
						</select>
					</div>
					<div class="inst-list-filter-item">
						<label for="ta_mx_time">Default time (new marks)</label>
						<input type="text" id="ta_mx_time" class="edu_form_field" placeholder="Batch start or 09:00">
					</div>
				</div>
				<div class="inst-list-filter-actions">
					<button type="button" class="btn btn-outline-secondary" id="ta_mx_prev">← Prev month</button>
					<button type="button" class="btn btn-outline-secondary" id="ta_mx_next">Next month →</button>
					<button type="button" class="btn btn-outline-secondary" id="ta_mx_load_btn">Reload</button>
					<button type="button" class="btn btn-primary" id="ta_mx_save_btn">Save matrix</button>
				</div>
			</div>
			<div class="ta-mx-legend small px-2 mb-2">
				<span class="ta-mx-legend-item"><span class="ta-mx-chip ta-mx-p">P</span> <span class="ta-mx-legend-label ta-mx-legend-p">Present</span></span>
				<span class="ta-mx-legend-item"><span class="ta-mx-chip ta-mx-l">L</span> <span class="ta-mx-legend-label ta-mx-legend-l">Late</span></span>
				<span class="ta-mx-legend-item"><span class="ta-mx-chip ta-mx-h">H</span> <span class="ta-mx-legend-label ta-mx-legend-h">Half day</span></span>
				<span class="ta-mx-legend-item"><span class="ta-mx-chip ta-mx-a">A</span> <span class="ta-mx-legend-label ta-mx-legend-a">Absent</span></span>
				<span class="ta-mx-legend-item"><span class="ta-mx-chip ta-mx-e">—</span> <span class="ta-mx-legend-label ta-mx-legend-e">No mark</span></span>
			</div>
			<div id="ta_mx_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="ta_mx_wrap" class="ta-mx-wrap">
				<table class="ta-mx-table" id="ta_mx_table" cellpadding="0" cellspacing="0"></table>
			</div>
		</div>
	</div>
</div>
<style>
.ta-mx-wrap { overflow-x: auto; max-width: 100%; border: 1px solid rgba(0,0,0,.08); border-radius: 8px; background: #fff; }
.ta-mx-table { border-collapse: separate; border-spacing: 0; font-size: 12px; min-width: 100%; }
.ta-mx-table th, .ta-mx-table td { border-bottom: 1px solid #e8e8e8; border-right: 1px solid #eee; padding: 6px 4px; text-align: center; white-space: nowrap; }
.ta-mx-table th { background: #f6f7f9; font-weight: 600; color: #333; position: sticky; top: 0; z-index: 2; }
.ta-mx-table th.ta-mx-sticky-col, .ta-mx-table td.ta-mx-sticky-col { position: sticky; left: 0; z-index: 3; background: #fff; text-align: left; min-width: 140px; box-shadow: 2px 0 4px rgba(0,0,0,.04); }
.ta-mx-table thead th.ta-mx-sticky-col { z-index: 4; background: #f6f7f9; }
.ta-mx-name { font-weight: 600; color: #222; }
.ta-mx-sub { font-size: 11px; color: #777; font-weight: normal; }
.ta-mx-cell { cursor: pointer; width: 36px; min-width: 36px; user-select: none; transition: background .12s; }
.ta-mx-cell:hover { filter: brightness(0.97); }
.ta-mx-cell.ta-mx-p,
.ta-mx-chip.ta-mx-p { background: #d4edda; color: #155724; font-weight: 700; }
.ta-mx-cell.ta-mx-l,
.ta-mx-chip.ta-mx-l { background: #fff3cd; color: #856404; font-weight: 700; }
.ta-mx-cell.ta-mx-h,
.ta-mx-chip.ta-mx-h { background: #cce5ff; color: #004085; font-weight: 700; }
.ta-mx-cell.ta-mx-a,
.ta-mx-chip.ta-mx-a { background: #f8d7da; color: #721c24; font-weight: 700; }
.ta-mx-cell.ta-mx-e,
.ta-mx-chip.ta-mx-e { background: #ececec; color: #555; font-weight: 600; border: 1px dashed #bbb; }
.ta-mx-chip.ta-mx-p { border: 1px solid #b8dfc4; }
.ta-mx-chip.ta-mx-l { border: 1px solid #e6d399; }
.ta-mx-chip.ta-mx-h { border: 1px solid #9ec5fe; }
.ta-mx-chip.ta-mx-a { border: 1px solid #f1aeb5; }
.ta-mx-legend { line-height: 1.6; }
.ta-mx-legend-item { margin-right: 16px; display: inline-flex; align-items: center; gap: 4px; }
.ta-mx-chip { display: inline-block; min-width: 24px; text-align: center; border-radius: 6px; padding: 3px 6px; font-size: 12px; box-sizing: border-box; }
.ta-mx-legend-label { font-weight: 600; font-size: 13px; }
.ta-mx-legend-p { color: #155724; }
.ta-mx-legend-l { color: #856404; }
.ta-mx-legend-h { color: #004085; }
.ta-mx-legend-a { color: #721c24; }
.ta-mx-legend-e { color: #555; }
.ta-mx-th-date { font-size: 11px; line-height: 1.2; }
.ta-mx-th-date small { display: block; font-weight: 500; color: #666; }
.ta-add-students-wrap { border: 1px solid rgba(0,0,0,.08); border-radius: 8px; padding: 10px; max-height: 260px; overflow: auto; background: #fff; }
.ta-add-student-row { display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f0f0f0; padding: 6px 2px; }
.ta-add-student-row:last-child { border-bottom: 0; }
.ta-add-student-meta { color: #777; font-size: 12px; }
</style>
<script>
(function () {
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var token = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var addUrl = <?php echo json_encode(isset($attendance_save_api_url) ? $attendance_save_api_url : ''); ?>;
	var matrixUrl = <?php echo json_encode(isset($attendance_matrix_api_url) ? $attendance_matrix_api_url : ''); ?>;
	var matrixSaveUrl = <?php echo json_encode(isset($attendance_matrix_save_api_url) ? $attendance_matrix_save_api_url : ''); ?>;
	var students = [];
	var dates = [];
	var cellMap = {};
	var baseline = {};
	var batchStartTime = '';
	var activeRequests = 0;
	var bulkBtn = null;
	var bulkBtnDefaultText = '';
	var bulkInFlight = false;
	var matrixSaveBtn = null;
	var matrixSaveBtnDefaultText = '';
	var matrixSaveInFlight = false;

	var cycle = ['empty', 'present', 'late', 'half', 'absent'];

	function msg(t, err) {
		var m = document.getElementById('ta_mx_msg');
		m.className = err ? 'small text-danger px-2 mb-2' : 'small text-success px-2 mb-2';
		m.textContent = t || '';
	}
	function esc(v) {
		var d = document.createElement('div');
		d.textContent = v == null ? '' : String(v);
		return d.innerHTML;
	}
	function cellKey(sid, ymd) {
		return sid + '_' + ymd;
	}
	function getStatus(key) {
		var c = cellMap[key];
		return (c && c.status) ? c.status : 'empty';
	}
	function labelFor(st) {
		if (st === 'present') return 'P';
		if (st === 'late') return 'L';
		if (st === 'half') return 'H';
		if (st === 'absent') return 'A';
		return '—';
	}
	function clsFor(st) {
		if (st === 'present') return 'ta-mx-cell ta-mx-p';
		if (st === 'late') return 'ta-mx-cell ta-mx-l';
		if (st === 'half') return 'ta-mx-cell ta-mx-h';
		if (st === 'absent') return 'ta-mx-cell ta-mx-a';
		return 'ta-mx-cell ta-mx-e';
	}
	function normCell(obj) {
		if (!obj || !obj.status || obj.status === 'empty') return 'empty';
		return obj.status;
	}
	function mergeBaselineFromServer(cells) {
		baseline = {};
		cellMap = {};
		if (cells && typeof cells === 'object') {
			Object.keys(cells).forEach(function (k) {
				var v = cells[k];
				var st = (v && v.status) ? v.status : 'empty';
				cellMap[k] = { status: st, time: v && v.time ? v.time : '', attendanceId: v && v.attendanceId ? v.attendanceId : 0 };
				baseline[k] = { status: st };
			});
		}
	}
	function isDirtyKey(key) {
		var cur = normCell(cellMap[key]);
		var base = baseline[key] ? baseline[key].status : 'empty';
		return cur !== base;
	}
	function collectDirtyKeys() {
		var keys = {};
		Object.keys(cellMap).forEach(function (k) {
			if (isDirtyKey(k)) keys[k] = true;
		});
		Object.keys(baseline).forEach(function (k) {
			if (isDirtyKey(k)) keys[k] = true;
		});
		return Object.keys(keys);
	}
	function renderTable() {
		var tbl = document.getElementById('ta_mx_table');
		if (!students.length || !dates.length) {
			tbl.innerHTML = '<tbody><tr><td class="p-3 inst-muted">No data. Pick a month and reload, or add students to this batch.</td></tr></tbody>';
			return;
		}
		var h = '<thead><tr><th class="ta-mx-sticky-col">Name</th>';
		for (var i = 0; i < dates.length; i++) {
			var d = dates[i];
			h += '<th class="ta-mx-th-date">' + esc(String(d.day)) + '<small>' + esc((d.label || '').replace(/^\d+\s+/, '')) + '</small></th>';
		}
		h += '</tr></thead><tbody>';
		for (var s = 0; s < students.length; s++) {
			var stu = students[s];
			var sid = stu.studentId;
			h += '<tr><td class="ta-mx-sticky-col"><span class="ta-mx-name">' + esc(stu.name || '') + '</span>';
			h += '<div class="ta-mx-sub">' + esc(stu.mobile || stu.email || '') + '</div></td>';
			for (var j = 0; j < dates.length; j++) {
				var ymd = dates[j].date;
				var key = cellKey(sid, ymd);
				var st = getStatus(key);
				h += '<td class="' + clsFor(st) + '" data-key="' + esc(key) + '" data-sid="' + sid + '" data-date="' + esc(ymd) + '" title="Click to change">' + esc(labelFor(st)) + '</td>';
			}
			h += '</tr>';
		}
		h += '</tbody>';
		tbl.innerHTML = h;
		tbl.onclick = function (ev) {
			var td = ev.target.closest('td[data-key]');
			if (!td) return;
			var key = td.getAttribute('data-key');
			var cur = getStatus(key);
			var idx = cycle.indexOf(cur);
			if (idx < 0) idx = 0;
			var next = cycle[(idx + 1) % cycle.length];
			if (next === 'empty') {
				delete cellMap[key];
			} else {
				cellMap[key] = { status: next, time: '', attendanceId: cellMap[key] && cellMap[key].attendanceId ? cellMap[key].attendanceId : 0 };
			}
			var st2 = getStatus(key);
			td.className = clsFor(st2);
			td.textContent = labelFor(st2);
		};
	}
	function ymFromInputs() {
		var y = parseInt(document.getElementById('ta_mx_year').value, 10);
		var m = parseInt(document.getElementById('ta_mx_month').value, 10);
		return { year: y, month: m };
	}
	function addMsg(t, err) {
		var m = document.getElementById('ta_add_msg');
		m.className = err ? 'small text-danger px-2 mb-2' : 'small text-success px-2 mb-2';
		m.textContent = t || '';
	}
	function setLoader(show) {
		var nodes = document.querySelectorAll('.edu_preloader');
		Array.prototype.forEach.call(nodes, function (el) {
			el.style.backgroundColor = 'rgba(255,255,255,0.80)';
			el.style.display = show ? 'block' : 'none';
		});
	}
	function syncLoader() {
		setLoader(activeRequests > 0);
	}
	function setBulkBusy(busy) {
		if (!bulkBtn) return;
		bulkInFlight = !!busy;
		bulkBtn.disabled = !!busy;
		bulkBtn.textContent = busy ? 'Saving...' : bulkBtnDefaultText;
		activeRequests += busy ? 1 : -1;
		if (activeRequests < 0) activeRequests = 0;
		syncLoader();
	}
	function setMatrixSaveBusy(busy) {
		if (!matrixSaveBtn) return;
		matrixSaveInFlight = !!busy;
		matrixSaveBtn.disabled = !!busy;
		matrixSaveBtn.textContent = busy ? 'Saving...' : matrixSaveBtnDefaultText;
		activeRequests += busy ? 1 : -1;
		if (activeRequests < 0) activeRequests = 0;
		syncLoader();
	}
	function renderBulkStudents() {
		var wrap = document.getElementById('ta_add_students_wrap');
		if (!students.length) {
			wrap.innerHTML = '<div class="inst-muted">No students in this batch.</div>';
			return;
		}
		var h = '';
		for (var i = 0; i < students.length; i++) {
			var s = students[i] || {};
			var sid = parseInt(s.studentId || s.id || 0, 10);
			if (!sid) continue;
			h += '<label class="ta-add-student-row">';
			h += '<input type="checkbox" class="ta_add_student_ck" value="' + sid + '">';
			h += '<span><strong>' + esc(s.name || ('Student #' + sid)) + '</strong>';
			h += '<div class="ta-add-student-meta">' + esc(s.mobile || s.email || '') + '</div></span>';
			h += '</label>';
		}
		wrap.innerHTML = h || '<div class="inst-muted">No students in this batch.</div>';
	}
	function checkedStudentIds() {
		var out = [];
		var list = document.querySelectorAll('.ta_add_student_ck:checked');
		for (var i = 0; i < list.length; i++) {
			var v = parseInt(list[i].value, 10);
			if (v > 0) out.push(v);
		}
		return out;
	}
	function setAllStudentsChecked(on) {
		var list = document.querySelectorAll('.ta_add_student_ck');
		for (var i = 0; i < list.length; i++) list[i].checked = !!on;
	}
	function submitBulkAttendance() {
		if (bulkInFlight) {
			return;
		}
		if (!addUrl) {
			addMsg('Add attendance API URL is not configured.', true);
			return;
		}
		var date = (document.getElementById('ta_add_date').value || '').trim();
		var time = (document.getElementById('ta_add_time').value || '').trim();
		var dayStatus = (document.getElementById('ta_add_status').value || '').trim();
		var ids = checkedStudentIds();
		if (!date) { addMsg('Please select date.', true); return; }
		if (!time) { addMsg('Please enter time.', true); return; }
		if (!ids.length) { addMsg('Please select at least one student.', true); return; }
		addMsg('Saving attendance...', false);
		var payload = {
			access_token: token,
			batch_id: batchId,
			attendance_date: date,
			time: time,
			student_ids: ids
		};
		if (dayStatus) payload.day_status = dayStatus;
		setBulkBusy(true);
		fetch(addUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(payload)
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!(j && (j.status === 'true' || j.status === true))) {
				addMsg((j && j.msg) || 'Attendance save failed.', true);
				setBulkBusy(false);
				return;
			}
			addMsg((j && j.msg) ? j.msg : 'Attendance saved.', false);
			loadMatrix();
			setBulkBusy(false);
		}).catch(function () {
			addMsg('Network error while saving attendance.', true);
			setBulkBusy(false);
		});
	}
	function loadMatrix() {
		var ym = ymFromInputs();
		if (!(ym.year >= 2000 && ym.month >= 1 && ym.month <= 12)) {
			msg('Invalid year or month.', true);
			return;
		}
		if (!matrixUrl) {
			msg('Matrix API URL is not configured.', true);
			return;
		}
		msg('Loading…', false);
		fetch(matrixUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({ batch_id: batchId, year: ym.year, month: ym.month, access_token: token })
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!(j && (j.status === 'true' || j.status === true))) {
				msg((j && j.msg) || 'Failed to load matrix.', true);
				students = [];
				dates = [];
				renderTable();
				return;
			}
			var d = j.data || {};
			students = d.students || [];
			dates = d.dates || [];
			batchStartTime = d.batchStartTime || '';
			var tIn = document.getElementById('ta_mx_time');
			if (batchStartTime && (!tIn.value || !String(tIn.value).trim())) {
				tIn.value = batchStartTime;
			}
			var tAdd = document.getElementById('ta_add_time');
			if (batchStartTime && tAdd && (!tAdd.value || !String(tAdd.value).trim())) {
				tAdd.value = batchStartTime;
			}
			mergeBaselineFromServer(d.cells || {});
			msg(students.length ? '' : 'No students in this batch.', false);
			renderBulkStudents();
			renderTable();
		}).catch(function (err) {
			msg((err && err.message) ? err.message : 'Network error while loading matrix.', true);
		});
	}
	function saveMatrix() {
		if (matrixSaveInFlight) {
			return;
		}
		var dirty = collectDirtyKeys();
		if (!dirty.length) {
			msg('No changes to save.', false);
			return;
		}
		var defTime = document.getElementById('ta_mx_time').value.trim();
		var entries = [];
		for (var i = 0; i < dirty.length; i++) {
			var key = dirty[i];
			var parts = key.split('_');
			if (parts.length < 2) continue;
			var sid = parseInt(parts[0], 10);
			var ymd = parts.slice(1).join('_');
			var st = getStatus(key);
			entries.push({ student_id: sid, date: ymd, status: st });
		}
		msg('Saving…', false);
		setMatrixSaveBusy(true);
		fetch(matrixSaveUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({
				access_token: token,
				batch_id: batchId,
				default_time: defTime,
				entries: entries
			})
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!(j && (j.status === 'true' || j.status === true))) {
				msg((j && j.msg) || 'Save failed.', true);
				setMatrixSaveBusy(false);
				return;
			}
			msg(j.msg || 'Saved.', false);
			dirty.forEach(function (k) {
				var st = getStatus(k);
				if (st === 'empty') {
					delete baseline[k];
				} else {
					baseline[k] = { status: st };
				}
			});
			setMatrixSaveBusy(false);
		}).catch(function () {
			msg('Network error while saving.', true);
			setMatrixSaveBusy(false);
		});
	}
	function shiftMonth(delta) {
		var y = parseInt(document.getElementById('ta_mx_year').value, 10);
		var m = parseInt(document.getElementById('ta_mx_month').value, 10);
		m += delta;
		if (m > 12) { m = 1; y++; }
		if (m < 1) { m = 12; y--; }
		document.getElementById('ta_mx_year').value = y;
		document.getElementById('ta_mx_month').value = String(m);
		loadMatrix();
	}
	document.addEventListener('DOMContentLoaded', function () {
		if (batchId < 1) {
			msg('Missing batch_id in URL. Open this page from a batch.', true);
			addMsg('Missing batch_id in URL. Open this page from a batch.', true);
			return;
		}
		var now = new Date();
		document.getElementById('ta_add_date').value = now.toISOString().slice(0, 10);
		document.getElementById('ta_mx_year').value = now.getFullYear();
		document.getElementById('ta_mx_month').value = String(now.getMonth() + 1);
		bulkBtn = document.getElementById('ta_add_submit');
		bulkBtnDefaultText = bulkBtn ? bulkBtn.textContent : 'Save attendance';
		matrixSaveBtn = document.getElementById('ta_mx_save_btn');
		matrixSaveBtnDefaultText = matrixSaveBtn ? matrixSaveBtn.textContent : 'Save matrix';
		document.getElementById('ta_add_select_all').addEventListener('click', function () { setAllStudentsChecked(true); });
		document.getElementById('ta_add_clear_all').addEventListener('click', function () { setAllStudentsChecked(false); });
		document.getElementById('ta_add_submit').addEventListener('click', submitBulkAttendance);
		document.getElementById('ta_mx_load_btn').addEventListener('click', loadMatrix);
		document.getElementById('ta_mx_save_btn').addEventListener('click', saveMatrix);
		document.getElementById('ta_mx_prev').addEventListener('click', function () { shiftMonth(-1); });
		document.getElementById('ta_mx_next').addEventListener('click', function () { shiftMonth(1); });
		loadMatrix();
	});
})();
</script>
