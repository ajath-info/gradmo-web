<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Attendance</div>
	</div>


	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card">
					<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
						<div style="font-weight:700;font-size:22px;" id="attMonthLabel">--</div>
						<div style="display:flex;gap:8px;">
							<button type="button" class="btn btn-sm btn-outline-secondary" id="attPrevMonth"><i class="fas fa-arrow-left"></i></button>
							<button type="button" class="btn btn-sm btn-outline-secondary" id="attNextMonth"><i class="fas fa-arrow-right"></i></button>
						</div>
					</div>
					<div id="attCalendarGrid" style="margin-top:14px;"></div>
					<div style="margin-top:14px;font-size:16px;">
						Attendance for the month : <strong id="attSummaryText">0/0 (0%)</strong>
					</div>
					<div id="attMsg" class="mt-2"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	'use strict';

	var endpoint = '<?php echo site_url('api/user/attendance-list'); ?>';
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var state = {
		year: new Date().getFullYear(),
		month: new Date().getMonth() + 1,
		presentDays: []
	};

	var monthLabelEl = document.getElementById('attMonthLabel');
	var calendarEl = document.getElementById('attCalendarGrid');
	var summaryEl = document.getElementById('attSummaryText');
	var msgEl = document.getElementById('attMsg');

	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
		});
	}

	function showMessage(text, type) {
		var cls = type === 'error' ? 'text-danger' : 'text-muted';
		msgEl.className = cls + ' mt-2';
		msgEl.textContent = text || '';
	}

	function daysInMonth(year, month) {
		return new Date(year, month, 0).getDate();
	}

	function buildCalendar() {
		var d = new Date(state.year, state.month - 1, 1);
		var firstDay = d.getDay();
		var totalDays = daysInMonth(state.year, state.month);
		var monthName = d.toLocaleString('default', { month: 'long' });
		monthLabelEl.textContent = monthName + ' ' + state.year;

		var weekTitles = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
		var html = '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;">';
		for (var w = 0; w < weekTitles.length; w++) {
			html += '<div style="text-align:center;font-weight:700;font-size:12px;">' + weekTitles[w] + '</div>';
		}

		for (var i = 0; i < firstDay; i++) {
			html += '<div></div>';
		}

		for (var day = 1; day <= totalDays; day++) {
			var mark = state.presentDays.indexOf(day) !== -1;
			var bg = mark ? '#27ae60' : '#f4f6f8';
			var color = mark ? '#fff' : '#333';
			html += '<div style="height:34px;border-radius:18px;background:' + bg + ';color:' + color + ';display:flex;align-items:center;justify-content:center;font-size:13px;">' + day + '</div>';
		}
		html += '</div>';
		calendarEl.innerHTML = html;
	}

	function loadAttendance() {
		showMessage('Loading attendance...', 'info');
		var payload = {
			month: state.month,
			year: state.year,
			page: 1,
			limit: 500
		};
		if (batchId > 0) {
			payload.batch_id = batchId;
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
			var rows = Array.isArray(res.attendance) ? res.attendance : [];
			var dayMap = {};
			for (var i = 0; i < rows.length; i++) {
				var rawDate = (rows[i].attendance_date || rows[i].date || '').toString();
				var parts = rawDate.split('-');
				if (parts.length === 3 && parseInt(parts[0], 10) === state.year && parseInt(parts[1], 10) === state.month) {
					dayMap[parseInt(parts[2], 10)] = true;
				}
			}
			state.presentDays = Object.keys(dayMap).map(function (k) { return parseInt(k, 10); });
			state.presentDays.sort(function (a, b) { return a - b; });
			buildCalendar();

			var summary = res.summary || {};
			var present = parseInt(summary.daysPresent || state.presentDays.length, 10) || 0;
			var dim = parseInt(summary.daysInMonth || daysInMonth(state.year, state.month), 10) || 0;
			var pct = (summary.attendancePercent != null) ? Number(summary.attendancePercent) : (dim > 0 ? ((present / dim) * 100) : 0);
			summaryEl.textContent = present + '/' + dim + ' (' + pct.toFixed(2).replace(/\.00$/, '') + '%)';
			showMessage('', 'info');
		})
		.catch(function (err) {
			state.presentDays = [];
			buildCalendar();
			summaryEl.textContent = '0/0 (0%)';
			showMessage(err && err.message ? err.message : 'Failed to load attendance', 'error');
		});
	}

	document.getElementById('attPrevMonth').addEventListener('click', function () {
		state.month -= 1;
		if (state.month < 1) {
			state.month = 12;
			state.year -= 1;
		}
		loadAttendance();
	});

	document.getElementById('attNextMonth').addEventListener('click', function () {
		state.month += 1;
		if (state.month > 12) {
			state.month = 1;
			state.year += 1;
		}
		loadAttendance();
	});

	buildCalendar();
	loadAttendance();
})();
</script>
