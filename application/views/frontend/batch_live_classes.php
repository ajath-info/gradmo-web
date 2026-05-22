<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.lc-shell { max-width: 1080px; margin: 0 auto; }
.lc-panel {
	background: #e8eef9;
	border-radius: 16px;
	padding: 16px 14px 18px;
	border: 1px solid rgba(77, 74, 129, 0.1);
	box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
}
.lc-panel-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	flex-wrap: wrap;
	margin-bottom: 8px;
}
.lc-panel-head h3 {
	margin: 0;
	font-size: 1.15rem;
	font-weight: 700;
	color: #1a1a2e;
}
.lc-panel-intro {
	margin: 0 0 16px;
	font-size: 0.88rem;
	color: #64748b;
	line-height: 1.45;
	max-width: 640px;
}
.lc-panel-links {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	font-size: 0.88rem;
	font-weight: 600;
}
.lc-panel-links a {
	color: var(--Primary-Color, #4d4a81);
	text-decoration: none;
}
.lc-panel-links a:hover { text-decoration: underline; }
.lc-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
	gap: 16px;
}
.lc-card {
	display: flex;
	flex-direction: column;
	background: #fff;
	border: 1px solid rgba(77, 74, 129, 0.12);
	border-radius: 16px;
	box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
	border-top: 3px solid var(--Primary-Color, #4d4a81);
	overflow: hidden;
	height: 100%;
	min-height: 0;
}
.lc-card.is-live {
	border-top-color: #16a34a;
	box-shadow: 0 6px 22px rgba(22, 163, 74, 0.12);
}
.lc-card-body {
	display: flex;
	flex-direction: column;
	flex: 1;
	padding: 16px 16px 18px;
	min-width: 0;
}
.lc-card-top {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	margin-bottom: 12px;
}
.lc-card-icon {
	flex: 0 0 48px;
	width: 48px;
	height: 48px;
	border-radius: 12px;
	background: linear-gradient(145deg, #eff3ff 0%, #e0e9ff 100%);
	color: var(--Primary-Color, #4d4a81);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 1.2rem;
}
.lc-card.is-live .lc-card-icon {
	background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%);
	color: #16a34a;
}
.lc-card-head {
	flex: 1;
	min-width: 0;
}
.lc-card-title {
	margin: 0 0 6px;
	font-size: 1rem;
	font-weight: 700;
	color: #0f172a;
	line-height: 1.35;
	word-break: break-word;
}
.lc-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 4px 10px;
	border-radius: 999px;
	font-size: 0.72rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	line-height: 1.2;
	white-space: nowrap;
}
.lc-badge-live {
	background: #dcfce7;
	color: #166534;
}
.lc-badge-live::before {
	content: '';
	width: 7px;
	height: 7px;
	border-radius: 50%;
	background: #22c55e;
	box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25);
	animation: lc-pulse 1.4s ease-in-out infinite;
}
.lc-badge-ended {
	background: #f1f5f9;
	color: #64748b;
}
@keyframes lc-pulse {
	0%, 100% { opacity: 1; transform: scale(1); }
	50% { opacity: 0.55; transform: scale(0.92); }
}
.lc-meta {
	margin: 0 0 6px;
	font-size: 0.86rem;
	color: #64748b;
	line-height: 1.45;
}
.lc-meta:last-of-type { margin-bottom: 0; }
.lc-meta i {
	width: 16px;
	margin-right: 4px;
	color: #94a3b8;
}
.lc-type {
	display: inline-block;
	margin-top: 10px;
	font-size: 0.75rem;
	font-weight: 600;
	color: #475569;
	background: #f8fafc;
	border: 1px solid #e2e8f0;
	border-radius: 6px;
	padding: 3px 8px;
}
.lc-card-actions {
	margin-top: auto;
	padding-top: 14px;
}
.lc-join-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	width: 100%;
	min-height: 44px;
	border-radius: 10px;
	font-weight: 600;
	font-size: 0.92rem;
	text-decoration: none !important;
}
.lc-join-btn i { font-size: 0.85rem; }
.lc-empty {
	grid-column: 1 / -1;
	text-align: center;
	padding: 40px 20px;
	background: #fff;
	border-radius: 14px;
	border: 1px dashed #cbd5e1;
	color: #64748b;
}
.lc-empty i {
	display: block;
	font-size: 2rem;
	color: #94a3b8;
	margin-bottom: 12px;
}
.lc-empty p { margin: 0; font-size: 0.95rem; }
@media (max-width: 575.98px) {
	.lc-panel { padding: 12px 10px 14px; }
	.lc-grid { grid-template-columns: 1fr; }
}
</style>
<div class="inst-detail-page lc-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="javascript:history.back()" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Live Classes</span>
	</div>
	<div class="inst-detail-container lc-shell">
		<div class="lc-panel">
			<div class="lc-panel-head">
				<h3>Live Classes</h3>
				<div class="lc-panel-links">
					<?php if (!empty($batch_details_url) && (int) (isset($batch_id) ? $batch_id : 0) > 0) { ?>
					<a href="<?php echo html_escape($batch_details_url . '?batch_id=' . (int) $batch_id); ?>"><i class="fas fa-layer-group"></i> Batch</a>
					<?php } ?>
					<?php if (!empty($recorded_meetings_url) && (int) (isset($batch_id) ? $batch_id : 0) > 0) { ?>
					<a href="<?php echo html_escape($recorded_meetings_url . '?batch_id=' . (int) $batch_id); ?>"><i class="fas fa-video"></i> Recordings</a>
					<?php } ?>
				</div>
			</div>
			<p class="lc-panel-intro">Join your scheduled live sessions here. Classes open inside this website — you do not need the Zoom app.</p>
			<div id="lc_msg" class="inst-muted text-center py-3">Loading…</div>
			<div id="lc_list" class="lc-grid" role="list"></div>
		</div>
	</div>
</div>
<script>
(function () {
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var listUrl = <?php echo json_encode((string) (isset($live_class_list_url) ? $live_class_list_url : site_url('api/batch/live-class-list'))); ?>;
	var roomUrl = <?php echo json_encode((string) (isset($live_class_room_url) ? $live_class_room_url : site_url('batch/live-room'))); ?>;
	var msgEl = document.getElementById('lc_msg');
	var listEl = document.getElementById('lc_list');

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function joinHref(row, autoJoin) {
		var q = '?batch_id=' + encodeURIComponent(batchId);
		if (row.isBatchZoom == 1 || row.liveClassId === 0 || row.liveClassId === '0') {
			q += '&live_class_id=0';
		} else {
			q += '&live_class_id=' + encodeURIComponent(row.liveClassId || 0);
		}
		if (autoJoin) { q += '&join=1'; }
		return roomUrl + q;
	}
	function redirectIfSingleClass(rows) {
		if (!rows || rows.length !== 1) { return false; }
		window.location.replace(joinHref(rows[0], true));
		return true;
	}
	function formatSchedule(row) {
		var parts = [];
		if (row.date) { parts.push(String(row.date)); }
		if (row.startTime) { parts.push(String(row.startTime)); }
		if (row.endTime && row.endTime !== '0000-00-00 00:00:00') { parts.push('– ' + String(row.endTime)); }
		return parts.join(' ');
	}
	function card(row) {
		var isLive = !!(row.isLive === 1 || row.isLive === '1' || row.isLive === true);
		var title = row.subjectName || 'Live Class';
		var teacher = row.teacherName || '';
		var chapter = row.chapterName || '';
		var schedule = formatSchedule(row);
		var typeLabel = row.typeLabel || (row.isBatchZoom == 1 ? 'Zoom' : '');
		var badge = isLive
			? '<span class="lc-badge lc-badge-live">Live now</span>'
			: '<span class="lc-badge lc-badge-ended">Ended</span>';
		var metaTeacher = teacher
			? '<p class="lc-meta"><i class="fas fa-user" aria-hidden="true"></i>' + esc(teacher) + '</p>'
			: '';
		var metaWhen = schedule
			? '<p class="lc-meta"><i class="fas fa-clock" aria-hidden="true"></i>' + esc(schedule) + '</p>'
			: '';
		var metaChapter = chapter
			? '<p class="lc-meta"><i class="fas fa-book" aria-hidden="true"></i>' + esc(chapter) + '</p>'
			: '';
		var typeHtml = typeLabel ? '<span class="lc-type">' + esc(typeLabel) + '</span>' : '';
		var joinLabel = isLive ? 'Join class' : 'Open room';
		var joinClass = isLive ? 'btn btn-success lc-join-btn' : 'btn btn-primary lc-join-btn';

		return '<article class="lc-card' + (isLive ? ' is-live' : '') + '" role="listitem">' +
			'<div class="lc-card-body">' +
				'<div class="lc-card-top">' +
					'<div class="lc-card-icon" aria-hidden="true"><i class="fas fa-video"></i></div>' +
					'<div class="lc-card-head">' +
						'<h4 class="lc-card-title">' + esc(title) + '</h4>' +
						badge +
					'</div>' +
				'</div>' +
				metaTeacher + metaWhen + metaChapter + typeHtml +
				'<div class="lc-card-actions">' +
					'<a class="' + joinClass + '" href="' + esc(joinHref(row, true)) + '">' +
						'<i class="fas fa-sign-in-alt" aria-hidden="true"></i> ' + joinLabel +
					'</a>' +
				'</div>' +
			'</div>' +
		'</article>';
	}
	function emptyState(text) {
		return '<div class="lc-empty">' +
			'<i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>' +
			'<p>' + esc(text) + '</p>' +
		'</div>';
	}

	if (batchId < 1) {
		msgEl.textContent = 'Invalid batch id.';
		msgEl.className = 'text-danger text-center py-3';
		return;
	}

	fetch(listUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'Authorization': 'Bearer ' + token
		},
		body: JSON.stringify({ batch_id: batchId, page: 1, limit: 50 })
	}).then(function (r) { return r.json(); }).then(function (j) {
		if (!ok(j.status)) {
			msgEl.textContent = (j && (j.msg || j.message)) || 'Could not load live classes.';
			msgEl.className = 'text-danger text-center py-3';
			listEl.innerHTML = '';
			return;
		}
		var rows = (j.data && j.data.liveClasses) ? j.data.liveClasses : [];
		if (redirectIfSingleClass(rows)) { return; }
		msgEl.textContent = '';
		msgEl.className = 'inst-detail-hidden';
		if (!rows.length) {
			listEl.innerHTML = emptyState('No live classes available for this batch right now.');
			return;
		}
		listEl.innerHTML = rows.map(card).join('');
	}).catch(function () {
		msgEl.textContent = 'Network error. Please try again.';
		msgEl.className = 'text-danger text-center py-3';
		listEl.innerHTML = '';
	});
})();
</script>
