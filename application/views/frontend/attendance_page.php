<div class="inst-detail-page att-dash-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Attendance</div>
	</div>

	<div class="inst-detail-container att-dash-container">
		<div class="inst-detail-panel att-dash-panel">
			<div id="attMsg" class="att-dash-msg mb-3"></div>
			<div class="att-dash-grid">
				<!-- Left: calendar -->
				<section class="att-dash-col att-dash-cal" aria-labelledby="attDashCalTitle">
					<h2 class="att-dash-section-title" id="attDashCalTitle">Attendance</h2>
					<div class="att-dash-card att-dash-cal-inner">
						<div id="attTeacherFilters" class="att-teacher-filters att-hidden">
							<select id="attTeacherBatch" class="edu_form_field att-teacher-batch">
								<option value="">All My Batches</option>
							</select>
							<input type="date" id="attTeacherDate" class="edu_form_field att-teacher-date">
							<button type="button" id="attTeacherApply" class="btn btn-primary btn-sm">Apply</button>
						</div>
						<div class="att-dash-month-row">
							<div class="att-dash-month-label" id="attMonthLabel">--</div>
							<div class="att-dash-month-nav">
								<button type="button" class="btn btn-sm btn-outline-secondary" id="attPrevMonth" aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>
								<button type="button" class="btn btn-sm btn-outline-secondary" id="attNextMonth" aria-label="Next month"><i class="fas fa-chevron-right"></i></button>
							</div>
						</div>
						<p class="att-dash-legend small text-muted mb-2">
							<span class="att-leg att-leg-present">Present</span>
							<span class="att-leg att-leg-late">Late</span>
							<span class="att-leg att-leg-half">Half day</span>
							<span class="att-leg att-leg-absent">Absent</span>
							<span class="att-leg att-leg-weekend">Weekend</span>
						</p>
						<div id="attCalendarGrid"></div>
						<div id="attTeacherRoster" class="att-teacher-roster att-hidden"></div>
					</div>
				</section>

				<!-- Middle: donut -->
				<section class="att-dash-col att-dash-donut-wrap" aria-labelledby="attDonutTitle">
					<h2 class="att-dash-section-title att-dash-sr-mobile" id="attDonutTitle">Summary</h2>
					<div class="att-dash-card att-dash-donut-card">
						<p class="att-dash-donut-caption" id="attDonutCaption">Attendance for the month : <strong id="attSummaryText">0/0 (0%)</strong></p>
						<div class="att-donut-visual" id="attDonutVisual" aria-hidden="true">
							<div class="att-donut-ring" id="attDonutRing"></div>
							<div class="att-donut-center">
								<span class="att-donut-center-main" id="attDonutCenterMain">0/0</span>
								<span class="att-donut-center-sub">Classes</span>
							</div>
						</div>
					</div>
				</section>

				<!-- Right: bar stats -->
				<section class="att-dash-col att-dash-bars" aria-labelledby="attBarsTitle">
					<div class="att-dash-bars-head">
						<h2 class="att-dash-section-title att-dash-sr-mobile" id="attBarsTitle">Breakdown</h2>
						<?php
						$att_view_all = base_url('batch/mylist');
						?>
						<a href="<?php echo html_escape($att_view_all); ?>" class="att-dash-view-all">VIEW ALL</a>
					</div>
					<div class="att-dash-card att-dash-bars-card">
						<ul class="att-stat-list" id="attStatList"></ul>
					</div>
				</section>
			</div>
		</div>
	</div>
</div>

<style>
.att-dash-page .inst-detail-container { max-width: 1180px; }
.att-dash-grid {
	display: grid;
	grid-template-columns: minmax(280px, 1.15fr) minmax(240px, 0.95fr) minmax(260px, 1fr);
	gap: 20px;
	align-items: start;
}
@media (max-width: 991px) {
	.att-dash-grid {
		grid-template-columns: 1fr;
	}
	.att-dash-sr-mobile {
		position: absolute;
		width: 1px;
		height: 1px;
		padding: 0;
		margin: -1px;
		overflow: hidden;
		clip: rect(0,0,0,0);
		white-space: nowrap;
		border: 0;
	}
}
.att-dash-section-title {
	margin: 0 0 10px;
	font-size: 0.72rem;
	font-weight: 700;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: #94a3b8;
}
.att-dash-card {
	background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
	border: 1px solid #e2e8f0;
	border-radius: 16px;
	padding: 16px 16px 18px;
	box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
}
.att-dash-month-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 10px;
	flex-wrap: wrap;
	margin-bottom: 6px;
}
.att-dash-month-label {
	font-weight: 700;
	font-size: 1.05rem;
	color: #2563eb;
}
.att-dash-month-nav { display: flex; gap: 6px; }
.att-dash-legend .att-leg { margin-right: 6px; margin-bottom: 4px; }

.att-cal-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
.att-cal-head { text-align: center; font-weight: 700; font-size: 11px; color: #64748b; }
.att-cal-cell {
	min-height: 40px;
	border-radius: 10px;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	font-size: 12px;
	font-weight: 600;
	border: 1px solid transparent;
	padding: 4px 2px;
	box-sizing: border-box;
	gap: 1px;
}
.att-cal-daynum { font-size: 12px; font-weight: 700; line-height: 1.1; }
.att-cal-time {
	font-size: 9px;
	font-weight: 600;
	line-height: 1.05;
	opacity: 0.95;
	max-width: 100%;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.att-cal-cell.att-neutral .att-cal-time { color: #64748b; }
.att-cal-cell.att-weekend .att-cal-time { color: #64748b; }
.att-cal-cell.att-blank { background: transparent; border: none; min-height: 8px; }
.att-cal-cell.att-neutral { background: #f4f6f8; color: #333; border-color: rgba(0,0,0,.06); }
.att-cal-cell.att-present { background: #22c55e; color: #fff; }
.att-cal-cell.att-late { background: #f59e0b; color: #1a1a2e; }
.att-cal-cell.att-half { background: linear-gradient(135deg, #22c55e 50%, #f59e0b 50%); color: #fff; text-shadow: 0 0 2px rgba(0,0,0,.35); }
.att-cal-cell.att-absent { background: #ef4444; color: #fff; }
.att-cal-cell.att-weekend { background: #e2e8f0; color: #64748b; border-color: rgba(0,0,0,.06); }
.att-leg { display: inline-block; padding: 2px 6px; border-radius: 6px; font-size: 10px; font-weight: 600; }
.att-leg-present { background: #22c55e; color: #fff; }
.att-leg-late { background: #f59e0b; color: #1a1a2e; }
.att-leg-half { background: linear-gradient(135deg, #22c55e 50%, #f59e0b 50%); color: #fff; }
.att-leg-absent { background: #ef4444; color: #fff; }
.att-leg-weekend { background: #e2e8f0; color: #64748b; }

.att-dash-donut-card { text-align: center; }
.att-dash-donut-caption {
	margin: 0 0 14px;
	font-size: 0.88rem;
	color: #475569;
	line-height: 1.4;
}
.att-dash-donut-caption strong { color: #0f172a; font-weight: 700; }
.att-donut-visual {
	position: relative;
	width: min(220px, 80vw);
	height: min(220px, 80vw);
	margin: 0 auto;
}
.att-donut-ring {
	position: absolute;
	inset: 0;
	border-radius: 50%;
	transition: background 0.35s ease;
	box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
}
.att-donut-center {
	position: absolute;
	inset: 18%;
	border-radius: 50%;
	background: #fff;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	box-shadow: 0 2px 12px rgba(15, 23, 42, 0.08);
}
.att-donut-center-main {
	font-size: clamp(1.25rem, 4vw, 1.65rem);
	font-weight: 800;
	color: #0f172a;
	line-height: 1.1;
}
.att-donut-center-sub {
	font-size: 0.75rem;
	font-weight: 600;
	color: #64748b;
	margin-top: 2px;
}

.att-dash-bars-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 10px;
}
.att-dash-bars-head .att-dash-section-title { margin-bottom: 0; }
.att-dash-view-all {
	font-size: 0.72rem;
	font-weight: 700;
	letter-spacing: 0.08em;
	color: #2563eb;
	text-decoration: none;
}
.att-dash-view-all:hover { text-decoration: underline; }
.att-stat-list {
	list-style: none;
	padding: 0;
	margin: 0;
	display: flex;
	flex-direction: column;
	gap: 14px;
}
.att-stat-row { margin: 0; }
.att-stat-top {
	display: flex;
	justify-content: space-between;
	align-items: baseline;
	margin-bottom: 6px;
	font-size: 0.875rem;
	font-weight: 600;
}
.att-stat-label-present { color: #16a34a; }
.att-stat-label-late { color: #d97706; }
.att-stat-label-half { color: #0d9488; }
.att-stat-label-absent { color: #dc2626; }
.att-stat-count { color: #334155; font-weight: 700; }
.att-stat-track {
	height: 10px;
	border-radius: 6px;
	background: #e2e8f0;
	overflow: hidden;
}
.att-stat-fill {
	height: 100%;
	border-radius: 6px;
	min-width: 0;
	transition: width 0.4s ease;
}
.att-stat-fill-present { background: linear-gradient(90deg, #22c55e, #4ade80); }
.att-stat-fill-late { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.att-stat-fill-half { background: linear-gradient(90deg, #14b8a6, #2dd4bf); }
.att-stat-fill-absent { background: linear-gradient(90deg, #ef4444, #f87171); }

.att-dash-msg { font-size: 0.875rem; }
.att-hidden { display: none !important; }
.att-teacher-filters {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: center;
	margin-bottom: 14px;
}
.att-teacher-batch {
	min-width: 220px;
	flex: 1 1 240px;
}
.att-teacher-date {
	min-width: 180px;
	flex: 0 1 190px;
}
.att-teacher-roster {
	display: flex;
	flex-direction: column;
	gap: 10px;
	margin-top: 12px;
}
.att-teacher-empty {
	padding: 14px 12px;
	border-radius: 12px;
	background: #fff;
	border: 1px dashed #cbd5e1;
	color: #64748b;
	font-size: 0.92rem;
	text-align: center;
}
.att-teacher-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	padding: 12px;
	border-radius: 14px;
	background: #fff;
	border: 1px solid #e2e8f0;
	box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.att-teacher-main {
	min-width: 0;
	display: flex;
	align-items: center;
	gap: 10px;
}
.att-teacher-avatar {
	width: 42px;
	height: 42px;
	border-radius: 50%;
	object-fit: cover;
	background: #e2e8f0;
	flex: 0 0 42px;
}
.att-teacher-avatar-ph {
	width: 42px;
	height: 42px;
	border-radius: 50%;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	background: #dbeafe;
	color: #1d4ed8;
	font-weight: 700;
	flex: 0 0 42px;
}
.att-teacher-text {
	min-width: 0;
}
.att-teacher-name {
	margin: 0;
	font-size: 0.96rem;
	font-weight: 700;
	color: #0f172a;
	line-height: 1.3;
}
.att-teacher-meta {
	margin: 3px 0 0;
	font-size: 0.8rem;
	color: #64748b;
	line-height: 1.35;
}
.att-teacher-side {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 6px;
	flex-shrink: 0;
}
.att-status-chip {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 4px 10px;
	border-radius: 999px;
	font-size: 0.76rem;
	font-weight: 700;
	letter-spacing: 0.02em;
}
.att-status-chip.att-status-present { background: #dcfce7; color: #166534; }
.att-status-chip.att-status-late { background: #fef3c7; color: #92400e; }
.att-status-chip.att-status-half { background: #ccfbf1; color: #0f766e; }
.att-status-chip.att-status-absent { background: #fee2e2; color: #b91c1c; }
.att-status-time {
	font-size: 0.78rem;
	font-weight: 600;
	color: #475569;
}
@media (max-width: 640px) {
	.att-teacher-row {
		flex-direction: column;
		align-items: stretch;
	}
	.att-teacher-side {
		align-items: flex-start;
	}
}
</style>

<script>
(function () {
	'use strict';

	var endpoint = <?php echo json_encode(isset($attendance_api_url) ? $attendance_api_url : site_url('api/user/attendance-list')); ?>;
	var batchOptionsEndpoint = <?php echo json_encode(isset($attendance_batch_options_url) ? $attendance_batch_options_url : site_url('batch/mylist-data')); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var teacherInitial = <?php echo !empty($attendance_is_teacher) ? 'true' : 'false'; ?>;
	var state = {
		year: new Date().getFullYear(),
		month: new Date().getMonth() + 1,
		dayStatus: {},
		dayTime: {},
		calendarApi: {},
		teacherMode: false
	};

	var monthLabelEl = document.getElementById('attMonthLabel');
	var calendarEl = document.getElementById('attCalendarGrid');
	var summaryEl = document.getElementById('attSummaryText');
	var msgEl = document.getElementById('attMsg');
	var donutRingEl = document.getElementById('attDonutRing');
	var donutCenterMainEl = document.getElementById('attDonutCenterMain');
	var statListEl = document.getElementById('attStatList');
	var donutCaptionEl = document.getElementById('attDonutCaption');
	var donutCenterSubEl = document.querySelector('.att-donut-center-sub');
	var teacherFiltersEl = document.getElementById('attTeacherFilters');
	var teacherBatchEl = document.getElementById('attTeacherBatch');
	var teacherDateEl = document.getElementById('attTeacherDate');
	var teacherApplyEl = document.getElementById('attTeacherApply');
	var teacherRosterEl = document.getElementById('attTeacherRoster');
	var monthRowEl = document.querySelector('.att-dash-month-row');
	var legendEl = document.querySelector('.att-dash-legend');
	var calTitleEl = document.getElementById('attDashCalTitle');
	var batchNameMap = {};

	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
		});
	}

	function showMessage(text, type) {
		var cls = type === 'error' ? 'text-danger' : 'text-muted';
		msgEl.className = 'att-dash-msg ' + cls;
		msgEl.textContent = text || '';
	}

	function postJson(url, payload, headers) {
		return fetch(url, {
			method: 'POST',
			headers: headers || {},
			body: JSON.stringify(payload || {})
		}).then(function (r) { return r.json(); });
	}

	function teacherStatusLabel(status) {
		if (status === 'present') return 'Present';
		if (status === 'late') return 'Late';
		if (status === 'half') return 'Half day';
		return 'Absent';
	}

	function setTeacherMode(enabled) {
		state.teacherMode = !!enabled;
		if (teacherFiltersEl) teacherFiltersEl.classList.toggle('att-hidden', !enabled);
		if (teacherRosterEl) teacherRosterEl.classList.toggle('att-hidden', !enabled);
		if (monthRowEl) monthRowEl.classList.toggle('att-hidden', enabled);
		if (legendEl) legendEl.classList.toggle('att-hidden', enabled);
		if (calendarEl) calendarEl.classList.toggle('att-hidden', enabled);
		if (calTitleEl) calTitleEl.textContent = enabled ? 'Teacher Attendance' : 'Attendance';
		if (donutCenterSubEl) donutCenterSubEl.textContent = enabled ? 'Students' : 'Classes';
		if (donutCaptionEl) {
			donutCaptionEl.innerHTML = enabled
				? 'Attendance for the selected date : <strong id="attSummaryText">' + esc(summaryEl.textContent || '0/0 (0%)') + '</strong>'
				: 'Attendance for the month : <strong id="attSummaryText">' + esc(summaryEl.textContent || '0/0 (0%)') + '</strong>';
			summaryEl = document.getElementById('attSummaryText');
		}
	}

	function loadTeacherBatchOptions() {
		if (!teacherBatchEl) {
			return Promise.resolve();
		}
		teacherBatchEl.innerHTML = '<option value="">All My Batches</option>';
		batchNameMap = {};
		return postJson(batchOptionsEndpoint, { page: 1, limit: 100 }, {
			'Content-Type': 'application/json',
			'Accept': 'application/json',
			'X-Requested-With': 'XMLHttpRequest'
		}).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var rows = ok && res.data && Array.isArray(res.data.enrolled_batches) ? res.data.enrolled_batches : [];
			for (var i = 0; i < rows.length; i++) {
				var row = rows[i] || {};
				var bid = parseInt(row.batchId || row.batch_id || 0, 10);
				if (bid < 1 || batchNameMap[bid]) continue;
				var name = row.batchName || row.batch_name || ('Batch #' + bid);
				batchNameMap[bid] = name;
				var option = document.createElement('option');
				option.value = String(bid);
				option.textContent = name;
				if (batchId > 0 && bid === batchId) {
					option.selected = true;
				}
				teacherBatchEl.appendChild(option);
			}
		}).catch(function () {
			// Page should still work without dropdown options.
		});
	}

	function renderTeacherRoster(students) {
		teacherRosterEl.innerHTML = '';
		if (!students || !students.length) {
			teacherRosterEl.innerHTML = '<div class="att-teacher-empty">No students found for the selected date and batch.</div>';
			return;
		}
		for (var i = 0; i < students.length; i++) {
			var row = students[i] || {};
			var status = String(row.status || 'absent').toLowerCase();
			var avatar = row.image
				? '<img class="att-teacher-avatar" src="' + esc(row.image) + '" alt="' + esc(row.name || 'Student') + '" onerror="this.onerror=null;this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-flex\';">' +
				  '<span class="att-teacher-avatar-ph" style="display:none;">' + esc(((row.name || 'S').charAt(0) || 'S').toUpperCase()) + '</span>'
				: '<span class="att-teacher-avatar-ph">' + esc(((row.name || 'S').charAt(0) || 'S').toUpperCase()) + '</span>';
			var batchText = row.batchName || (row.batchId ? ('Batch #' + row.batchId) : '');
			var timeText = row.time ? ('Time: ' + row.time) : (status === 'absent' ? 'No attendance marked' : '');
			var item = document.createElement('div');
			item.className = 'att-teacher-row';
			item.innerHTML =
				'<div class="att-teacher-main">' + avatar +
					'<div class="att-teacher-text">' +
						'<p class="att-teacher-name">' + esc(row.name || 'Student') + '</p>' +
						'<p class="att-teacher-meta">' + esc(batchText || 'Batch not assigned') + '</p>' +
					'</div>' +
				'</div>' +
				'<div class="att-teacher-side">' +
					'<span class="att-status-chip att-status-' + esc(status) + '">' + esc(teacherStatusLabel(status)) + '</span>' +
					(timeText ? '<span class="att-status-time">' + esc(timeText) + '</span>' : '') +
				'</div>';
			teacherRosterEl.appendChild(item);
		}
	}

	function daysInMonth(year, month) {
		return new Date(year, month, 0).getDate();
	}

	function pad2(n) {
		return n < 10 ? '0' + n : String(n);
	}

	function ymdForDay(year, month, day) {
		return year + '-' + pad2(month) + '-' + pad2(day);
	}

	function todayYmd() {
		var t = new Date();
		return t.getFullYear() + '-' + pad2(t.getMonth() + 1) + '-' + pad2(t.getDate());
	}

	function resolveDayStatus(day) {
		var fromRow = state.dayStatus[day] || '';
		if (fromRow) {
			return fromRow;
		}
		var ymd = ymdForDay(state.year, state.month, day);
		var today = todayYmd();
		if (ymd > today) {
			return '';
		}
		var rawCal = state.calendarApi[ymd];
		var cal = (rawCal != null ? String(rawCal) : '').toLowerCase().trim();
		if (cal === 'half_day' || cal === 'halfday') {
			return 'half';
		}
		if (cal === 'present' || cal === 'late' || cal === 'half') {
			return cal;
		}
		if (cal === 'absent') {
			return 'absent';
		}
		if (cal === 'weekend') {
			return 'weekend';
		}
		if (cal === 'future' || cal === 'none') {
			return '';
		}
		var dow = new Date(state.year, state.month - 1, day).getDay();
		if (batchId > 0) {
			if (dow === 0 || dow === 6) {
				return 'weekend';
			}
			return 'absent';
		}
		return '';
	}

	function statusRank(st) {
		switch (st) {
			case 'absent': return 4;
			case 'late': return 3;
			case 'half': return 2;
			case 'present': return 1;
			default: return 0;
		}
	}

	function rowToStatus(row) {
		var s = (row.status != null ? String(row.status) : '').toLowerCase().trim();
		if (s === 'half_day' || s === 'halfday') {
			return 'half';
		}
		if (s === 'absent') {
			return 'absent';
		}
		if (s === 'late') {
			return 'late';
		}
		if (s === 'present') {
			return 'present';
		}
		var late = row.is_late === 1 || row.is_late === true || row.is_late === '1';
		return late ? 'late' : 'present';
	}

	function formatTimeLabel(t) {
		if (t == null) {
			return '';
		}
		var s = String(t).trim();
		if (!s) {
			return '';
		}
		if (/^\d{1,2}\.\d{2}$/.test(s)) {
			return s.replace('.', ':');
		}
		var m = s.match(/^(\d{1,2}):(\d{2})/);
		if (m) {
			return parseInt(m[1], 10) + ':' + m[2];
		}
		return s.length > 8 ? s.slice(0, 8) : s;
	}

	function buildCalendar() {
		var d = new Date(state.year, state.month - 1, 1);
		var firstDay = d.getDay();
		var totalDays = daysInMonth(state.year, state.month);
		var monthName = d.toLocaleString('default', { month: 'long' });
		monthLabelEl.textContent = monthName + ' ' + state.year;

		var weekTitles = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
		var html = '<div class="att-cal-grid">';
		for (var w = 0; w < weekTitles.length; w++) {
			html += '<div class="att-cal-head">' + weekTitles[w] + '</div>';
		}

		for (var i = 0; i < firstDay; i++) {
			html += '<div class="att-cal-cell att-blank"></div>';
		}

		for (var day = 1; day <= totalDays; day++) {
			var st = resolveDayStatus(day);
			var cellClass = 'att-cal-cell att-neutral';
			var title = '';
			if (st === 'present') {
				cellClass = 'att-cal-cell att-present';
				title = 'Present';
			} else if (st === 'late') {
				cellClass = 'att-cal-cell att-late';
				title = 'Late';
			} else if (st === 'half') {
				cellClass = 'att-cal-cell att-half';
				title = 'Half day';
			} else if (st === 'absent') {
				cellClass = 'att-cal-cell att-absent';
				title = 'Absent';
			} else if (st === 'weekend') {
				cellClass = 'att-cal-cell att-weekend';
				title = 'Weekend';
			}
			var timeStr = state.dayTime[day] ? formatTimeLabel(state.dayTime[day]) : '';
			if (timeStr) {
				title = title ? title + ' · ' + timeStr : timeStr;
			}
			var inner = '<span class="att-cal-daynum">' + day + '</span>';
			if (timeStr) {
				inner += '<span class="att-cal-time">' + esc(timeStr) + '</span>';
			}
			html += '<div class="' + cellClass + '"' + (title ? ' title="' + esc(title) + '"' : '') + '>' + inner + '</div>';
		}
		html += '</div>';
		calendarEl.innerHTML = html;
	}

	var COL_PRESENT = '#22c55e';
	var COL_LATE = '#f59e0b';
	var COL_HALF = '#14b8a6';
	var COL_ABSENT = '#ef4444';
	var COL_EMPTY = '#e2e8f0';

	function updateDashboard(summary) {
		var p = parseInt(summary.countPresent, 10) || 0;
		var l = parseInt(summary.countLate, 10) || 0;
		var h = parseInt(summary.countHalf, 10) || 0;
		var a = parseInt(summary.countAbsent, 10) || 0;
		var daysPresent = parseInt(summary.daysPresent, 10);
		var dimRaw = parseInt(summary.daysInMonth, 10);
		var dim = isNaN(dimRaw) ? daysInMonth(state.year, state.month) : dimRaw;
		var pct = summary.attendancePercent != null ? Number(summary.attendancePercent) : (dim > 0 ? ((daysPresent / dim) * 100) : 0);
		if (isNaN(daysPresent)) daysPresent = p + l + h;

		summaryEl.textContent = daysPresent + '/' + dim + ' (' + pct.toFixed(2).replace(/\.00$/, '') + '%)';
		donutCenterMainEl.textContent = daysPresent + '/' + dim;

		var sum = p + l + h + a;
		if (sum < 1) {
			donutRingEl.style.background = 'conic-gradient(' + COL_EMPTY + ' 0deg 360deg)';
		} else {
			var g = [];
			var ang = 0;
			function seg(count, color) {
				if (count < 1) return;
				var deg = (count / sum) * 360;
				var start = ang;
				ang += deg;
				g.push(color + ' ' + start + 'deg ' + ang + 'deg');
			}
			seg(p, COL_PRESENT);
			seg(l, COL_LATE);
			seg(h, COL_HALF);
			seg(a, COL_ABSENT);
			if (ang < 360) {
				g.push(COL_EMPTY + ' ' + ang + 'deg 360deg');
			}
			donutRingEl.style.background = 'conic-gradient(' + g.join(', ') + ')';
		}

		var denom = sum > 0 ? sum : 1;
		var rows = [
			{ key: 'present', label: 'Present', count: p, cls: 'att-stat-label-present', fill: 'att-stat-fill-present' },
			{ key: 'late', label: 'Late', count: l, cls: 'att-stat-label-late', fill: 'att-stat-fill-late' },
			{ key: 'half', label: 'Half day', count: h, cls: 'att-stat-label-half', fill: 'att-stat-fill-half' },
			{ key: 'absent', label: 'Absent', count: a, cls: 'att-stat-label-absent', fill: 'att-stat-fill-absent' }
		];
		statListEl.innerHTML = '';
		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			var pctBar = Math.min(100, Math.round((r.count / denom) * 1000) / 10);
			var li = document.createElement('li');
			li.className = 'att-stat-row';
			li.innerHTML =
				'<div class="att-stat-top">' +
				'<span class="' + r.cls + '">' + esc(r.label) + '</span>' +
				'<span class="att-stat-count">' + esc(String(r.count)) + '</span></div>' +
				'<div class="att-stat-track"><div class="' + r.fill + ' att-stat-fill" style="width:' + pctBar + '%"></div></div>';
			statListEl.appendChild(li);
		}
	}

	function loadAttendance() {
		showMessage('Loading attendance...', 'info');
		var payload;
		if (state.teacherMode) {
			payload = {
				date: teacherDateEl && teacherDateEl.value ? teacherDateEl.value : todayYmd(),
				page: 1,
				limit: 1000
			};
			var teacherBatchId = teacherBatchEl && teacherBatchEl.value ? parseInt(teacherBatchEl.value, 10) : 0;
			if (teacherBatchId > 0) {
				payload.batch_id = teacherBatchId;
			}
		} else {
			payload = {
				month: state.month,
				year: state.year,
				page: 1,
				limit: 500
			};
			if (batchId > 0) {
				payload.batch_id = batchId;
			}
		}

		fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify(payload)
		})
		.then(function (r) { return r.json(); })
		.then(function (res) {
			if (!res || !(res.status === true || res.status === 'true')) {
				throw new Error((res && (res.msg || res.message)) || 'Unable to load attendance');
			}
			if (String(res.userType || '').toLowerCase() === 'teacher') {
				setTeacherMode(true);
				state.dayStatus = {};
				state.dayTime = {};
				state.calendarApi = {};
				renderTeacherRoster(Array.isArray(res.students) ? res.students : []);
				updateDashboard(res.summary || {
					daysPresent: parseInt(res.presentCount, 10) || 0,
					daysInMonth: parseInt(res.totalStudents, 10) || 0,
					attendancePercent: 0,
					countPresent: 0,
					countLate: 0,
					countHalf: 0,
					countAbsent: 0
				});
				showMessage('', 'info');
				return;
			}
			setTeacherMode(false);
			var rows = Array.isArray(res.attendance) ? res.attendance : [];
			var byDom = {};
			for (var i = 0; i < rows.length; i++) {
				var rawDate = (rows[i].attendance_date || rows[i].date || '').toString();
				var parts = rawDate.split('-');
				if (parts.length === 3 && parseInt(parts[0], 10) === state.year && parseInt(parts[1], 10) === state.month) {
					var dom = parseInt(parts[2], 10);
					if (!byDom[dom]) {
						byDom[dom] = [];
					}
					byDom[dom].push(rows[i]);
				}
			}
			var dayMap = {};
			var timeMap = {};
			for (var dom in byDom) {
				if (!Object.prototype.hasOwnProperty.call(byDom, dom)) {
					continue;
				}
				var list = byDom[dom];
				var best = list[0];
				var bestSt = rowToStatus(best);
				var bestR = statusRank(bestSt);
				for (var j = 1; j < list.length; j++) {
					var st = rowToStatus(list[j]);
					var r = statusRank(st);
					if (r > bestR) {
						best = list[j];
						bestSt = st;
						bestR = r;
					} else if (r === bestR) {
						best = list[j];
					}
				}
				dayMap[dom] = bestSt;
				timeMap[dom] = best.time != null ? String(best.time).trim() : '';
			}
			state.dayStatus = dayMap;
			state.dayTime = timeMap;
			state.calendarApi = (res.calendar && typeof res.calendar === 'object') ? res.calendar : {};
			buildCalendar();

			var summary = res.summary || {};
			updateDashboard(summary);
			showMessage('', 'info');
		})
		.catch(function (err) {
			state.dayStatus = {};
			state.dayTime = {};
			state.calendarApi = {};
			if (!state.teacherMode) {
				buildCalendar();
			} else {
				renderTeacherRoster([]);
			}
			updateDashboard({
				daysPresent: 0,
				daysInMonth: state.teacherMode ? 0 : daysInMonth(state.year, state.month),
				attendancePercent: 0,
				countPresent: 0,
				countLate: 0,
				countHalf: 0,
				countAbsent: 0
			});
			summaryEl.textContent = '0/0 (0%)';
			showMessage(err && err.message ? err.message : 'Failed to load attendance', 'error');
		});
	}

	document.getElementById('attPrevMonth').addEventListener('click', function () {
		if (state.teacherMode) {
			return;
		}
		state.month -= 1;
		if (state.month < 1) {
			state.month = 12;
			state.year -= 1;
		}
		loadAttendance();
	});

	document.getElementById('attNextMonth').addEventListener('click', function () {
		if (state.teacherMode) {
			return;
		}
		state.month += 1;
		if (state.month > 12) {
			state.month = 1;
			state.year += 1;
		}
		loadAttendance();
	});

	if (teacherDateEl) {
		teacherDateEl.value = todayYmd();
	}
	if (teacherApplyEl) {
		teacherApplyEl.addEventListener('click', loadAttendance);
	}
	if (teacherBatchEl) {
		teacherBatchEl.addEventListener('change', loadAttendance);
	}
	if (teacherDateEl) {
		teacherDateEl.addEventListener('change', loadAttendance);
	}

	setTeacherMode(teacherInitial);
	buildCalendar();
	updateDashboard({
		daysPresent: 0,
		daysInMonth: teacherInitial ? 0 : daysInMonth(state.year, state.month),
		attendancePercent: 0,
		countPresent: 0,
		countLate: 0,
		countHalf: 0,
		countAbsent: 0
	});
	(teacherInitial ? loadTeacherBatchOptions() : Promise.resolve()).then(function () {
		loadAttendance();
	});
})();
</script>
