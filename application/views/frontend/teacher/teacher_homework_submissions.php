<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.hs-shell { max-width: 960px; margin: 0 auto; }
.hs-stats {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: 12px;
	margin-bottom: 16px;
}
.hs-stat {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 14px;
	padding: 14px;
	text-align: center;
	box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.hs-stat strong { display: block; font-size: 1.5rem; color: #0f172a; }
.hs-stat span { font-size: 0.82rem; color: #64748b; font-weight: 600; }
.hs-toolbar {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: flex-end;
	margin-bottom: 14px;
}
.hs-toolbar label { display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 4px; }
.hs-toolbar .form-control { min-height: 40px; border-radius: 10px; }
.hs-row {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px 14px;
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 12px;
	margin-bottom: 10px;
	box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.hs-row img {
	width: 44px;
	height: 44px;
	border-radius: 50%;
	object-fit: cover;
	background: #eef2ff;
}
.hs-row-body { flex: 1; min-width: 0; }
.hs-row-body h4 { margin: 0 0 4px; font-size: 0.95rem; font-weight: 700; color: #0f172a; }
.hs-row-body p { margin: 0; font-size: 0.82rem; color: #64748b; }
.hs-pill {
	display: inline-flex;
	padding: 3px 9px;
	border-radius: 999px;
	font-size: 0.7rem;
	font-weight: 700;
	text-transform: uppercase;
}
.hs-pill-submitted { background: #dbeafe; color: #1e40af; }
.hs-pill-pending { background: #fef3c7; color: #92400e; }
.hs-pill-evaluated { background: #dcfce7; color: #166534; }
@media (max-width: 640px) {
	.hs-stats { grid-template-columns: 1fr; }
	.hs-toolbar > div { flex: 1 1 100%; }
	.hs-row { flex-wrap: wrap; }
	.hs-row .btn { width: 100%; }
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="<?php echo html_escape($teacher_homework_url . ($batch_id > 0 ? '?batch_id=' . (int) $batch_id : '')); ?>" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Submissions</div>
	</div>
	<div class="inst-detail-container hs-shell">
		<div id="hsMsg" class="inst-muted text-center py-3">Loading…</div>
		<div id="hsBody" class="inst-detail-hidden">
			<div class="inst-detail-summary-card" style="margin-bottom:14px;">
				<h2 id="hsTitle" style="margin:0 0 6px;font-size:1.1rem;font-weight:700;"></h2>
				<p id="hsMeta" class="inst-batch-meta" style="margin:0;"></p>
			</div>
			<div class="hs-stats">
				<div class="hs-stat"><strong id="hsTotal">0</strong><span>Assigned students</span></div>
				<div class="hs-stat"><strong id="hsSubmitted">0</strong><span>Submitted</span></div>
				<div class="hs-stat"><strong id="hsPending">0</strong><span>Pending</span></div>
			</div>
			<div class="hs-toolbar">
				<div>
					<label for="hsFilter">Show</label>
					<select id="hsFilter" class="form-control">
						<option value="all">All students</option>
						<option value="submitted">Submitted only</option>
						<option value="pending">Pending only</option>
						<option value="evaluated">Evaluated only</option>
					</select>
				</div>
				<div>
					<label for="hsSort">Sort by</label>
					<select id="hsSort" class="form-control">
						<option value="name_asc">Name (A–Z)</option>
						<option value="name_desc">Name (Z–A)</option>
						<option value="date_desc">Submitted (newest)</option>
						<option value="date_asc">Submitted (oldest)</option>
					</select>
				</div>
				<div style="flex:1 1 180px;">
					<label for="hsSearch">Search student</label>
					<input type="search" id="hsSearch" class="form-control" placeholder="Type name…">
				</div>
			</div>
			<div id="hsList"></div>
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
	var submissionsUrl = <?php echo json_encode((string) (isset($homework_submissions_api_url) ? $homework_submissions_api_url : '')); ?>;
	var rosterUrl = <?php echo json_encode((string) (isset($attendance_roster_api_url) ? $attendance_roster_api_url : '')); ?>;
	var detailBase = <?php echo json_encode((string) (isset($submission_detail_url) ? $submission_detail_url : '')); ?>;
	var allRows = [];

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(v) {
		var d = document.createElement('div');
		d.textContent = v == null ? '' : String(v);
		return d.innerHTML;
	}
	function apiPost(url, body) {
		return fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); });
	}
	function detailHref(submissionId) {
		var q = '?submission_id=' + encodeURIComponent(submissionId) + '&homework_id=' + encodeURIComponent(homeworkId);
		if (batchId > 0) { q += '&batch_id=' + encodeURIComponent(batchId); }
		return detailBase + q;
	}
	function buildRows(roster, submissions) {
		var subByStudent = {};
		(submissions || []).forEach(function (s) {
			subByStudent[String(s.studentId)] = s;
		});
		var rows = [];
		(roster || []).forEach(function (st) {
			var sid = String(st.studentId);
			var sub = subByStudent[sid];
			rows.push({
				studentId: st.studentId,
				studentName: st.name || ('Student #' + sid),
				studentImageUrl: st.imageUrl || '',
				submitted: !!sub,
				submissionId: sub ? sub.id : 0,
				submittedAt: sub ? (sub.submittedAt || '') : '',
				evalStatus: sub ? sub.evalStatus : 0,
				evaluated: sub && (sub.evalStatus === 1 || sub.evalStatus === '1')
			});
		});
		(submissions || []).forEach(function (s) {
			if (!rows.some(function (r) { return String(r.studentId) === String(s.studentId); })) {
				rows.push({
					studentId: s.studentId,
					studentName: s.studentName || ('Student #' + s.studentId),
					studentImageUrl: s.studentImageUrl || '',
					submitted: true,
					submissionId: s.id,
					submittedAt: s.submittedAt || '',
					evalStatus: s.evalStatus,
					evaluated: s.evalStatus === 1 || s.evalStatus === '1'
				});
			}
		});
		return rows;
	}
	function updateStats(rows) {
		var total = rows.length;
		var submitted = rows.filter(function (r) { return r.submitted; }).length;
		document.getElementById('hsTotal').textContent = String(total);
		document.getElementById('hsSubmitted').textContent = String(submitted);
		document.getElementById('hsPending').textContent = String(Math.max(0, total - submitted));
	}
	function renderList() {
		var filter = document.getElementById('hsFilter').value;
		var sort = document.getElementById('hsSort').value;
		var search = document.getElementById('hsSearch').value.trim().toLowerCase();
		var rows = allRows.slice();
		if (filter === 'submitted') {
			rows = rows.filter(function (r) { return r.submitted; });
		} else if (filter === 'pending') {
			rows = rows.filter(function (r) { return !r.submitted; });
		} else if (filter === 'evaluated') {
			rows = rows.filter(function (r) { return r.evaluated; });
		}
		if (search) {
			rows = rows.filter(function (r) {
				return String(r.studentName || '').toLowerCase().indexOf(search) !== -1;
			});
		}
		rows.sort(function (a, b) {
			if (sort === 'name_desc') {
				return String(b.studentName).localeCompare(String(a.studentName));
			}
			if (sort === 'date_desc') {
				return String(b.submittedAt || '').localeCompare(String(a.submittedAt || ''));
			}
			if (sort === 'date_asc') {
				return String(a.submittedAt || '').localeCompare(String(b.submittedAt || ''));
			}
			return String(a.studentName).localeCompare(String(b.studentName));
		});
		if (!rows.length) {
			document.getElementById('hsList').innerHTML = '<p class="inst-muted text-center py-3">No students match this filter.</p>';
			return;
		}
		var html = '';
		rows.forEach(function (r) {
			var img = r.studentImageUrl
				? '<img src="' + esc(r.studentImageUrl) + '" alt="">'
				: '<img src="" alt="" style="visibility:hidden;width:44px;height:44px;">';
			var pill = !r.submitted
				? '<span class="hs-pill hs-pill-pending">Pending</span>'
				: (r.evaluated
					? '<span class="hs-pill hs-pill-evaluated">Evaluated</span>'
					: '<span class="hs-pill hs-pill-submitted">Submitted</span>');
			var meta = r.submitted
				? ('Submitted: ' + esc(r.submittedAt || '—'))
				: 'No submission yet';
			var action = r.submitted
				? '<a class="btn btn-sm btn-primary" href="' + esc(detailHref(r.submissionId)) + '">Review</a>'
				: '<span class="btn btn-sm btn-outline-secondary disabled">No file</span>';
			html += '<div class="hs-row">' + img +
				'<div class="hs-row-body"><h4>' + esc(r.studentName) + ' ' + pill + '</h4><p>' + meta + '</p></div>' +
				action + '</div>';
		});
		document.getElementById('hsList').innerHTML = html;
	}
	function load() {
		if (homeworkId < 1) {
			document.getElementById('hsMsg').textContent = 'Invalid homework.';
			return;
		}
		var rosterBody = { access_token: token, batch_id: batchId, page: 1, limit: 500 };
		var subBody = { access_token: token, homework_id: homeworkId, page: 1, limit: 500 };
		Promise.all([
			apiPost(detailsUrl, { access_token: token, homework_id: homeworkId }),
			batchId > 0 ? apiPost(rosterUrl, rosterBody) : Promise.resolve({ status: 'true', data: { students: [], pagination: { total: 0 } } }),
			apiPost(submissionsUrl, subBody)
		]).then(function (res) {
			var det = res[0];
			var rosterRes = res[1];
			var subRes = res[2];
			if (!ok(det.status) || !det.data || !det.data.homework) {
				document.getElementById('hsMsg').textContent = (det && det.msg) || 'Homework not found.';
				return;
			}
			var hw = det.data.homework;
			document.getElementById('hsTitle').textContent = hw.subjectName || 'Homework';
			document.getElementById('hsMeta').textContent = [hw.date || '', hw.description || ''].filter(Boolean).join(' · ');
			var roster = (ok(rosterRes.status) && rosterRes.data && rosterRes.data.students) ? rosterRes.data.students : [];
			var submissions = (ok(subRes.status) && subRes.data && subRes.data.submissions) ? subRes.data.submissions : [];
			allRows = buildRows(roster, submissions);
			updateStats(allRows);
			document.getElementById('hsMsg').classList.add('inst-detail-hidden');
			document.getElementById('hsBody').classList.remove('inst-detail-hidden');
			renderList();
		}).catch(function () {
			document.getElementById('hsMsg').textContent = 'Could not load submissions.';
		});
	}
	['hsFilter', 'hsSort'].forEach(function (id) {
		document.getElementById(id).addEventListener('change', renderList);
	});
	document.getElementById('hsSearch').addEventListener('input', renderList);
	load();
})();
</script>
