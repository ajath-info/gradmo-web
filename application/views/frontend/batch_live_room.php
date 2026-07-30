<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
#lr_alert {
	display: none;
	margin-bottom: 12px;
	padding: 12px 14px;
	border-radius: 10px;
	background: #fff8e6;
	border: 1px solid #f0d78c;
	color: #5c4a12;
	font-size: 0.92rem;
	line-height: 1.45;
}
#lr_alert.lr-alert-show { display: block; }
#lr_alert.lr-alert-error { background: #fdecea; border-color: #f5c6c6; color: #7a1f1f; }
.lr-actions { margin-top: 8px; }
#lr_zoom_wrap.lr-zoom-active {
	position: fixed;
	inset: 0;
	z-index: 9999;
	background: #1a1a1a;
	padding: 0;
	margin: 0;
	border-radius: 0;
	max-width: none;
}
#lr_zoom_wrap.lr-zoom-active #zmmtg-root-embedded {
	min-height: 100vh !important;
	height: 100vh !important;
}
#lr_zoom_close {
	display: none;
	position: fixed;
	top: 12px;
	right: 12px;
	z-index: 10000;
}
#lr_zoom_record {
	display: none;
	position: fixed;
	top: 12px;
	right: 120px;
	z-index: 10000;
}
#lr_zoom_wrap.lr-zoom-active #lr_zoom_close { display: inline-block; }
#lr_zoom_wrap.lr-zoom-active #lr_zoom_record.lr-record-show { display: inline-block; }
.lr-recordings {
	margin-top: 18px;
}
.lr-recordings-head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 10px;
	flex-wrap: wrap;
	margin-bottom: 12px;
}
.lr-recordings-tools {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
	align-items: center;
}
.lr-recordings-search {
	min-height: 40px;
	border-radius: 10px;
	border: 1px solid #d7deed;
	padding: 0 12px;
	min-width: 220px;
}
.lr-recordings-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
	gap: 14px;
}
.lr-record-card {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 14px;
	box-shadow: 0 7px 22px rgba(15, 23, 42, 0.07);
	padding: 14px 14px 16px;
}
.lr-record-card h4 {
	margin: 0 0 6px;
	font-size: 1rem;
	font-weight: 700;
	color: #0f172a;
	line-height: 1.35;
}
.lr-record-meta {
	margin: 0 0 10px;
	font-size: 0.86rem;
	color: #64748b;
	line-height: 1.4;
}
.lr-record-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}
.lr-record-pagination {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12px;
	flex-wrap: wrap;
	margin-top: 16px;
}
.lr-page-info { font-size: 13px; color: #64748b; min-width: 110px; text-align: center; }
.lr-batch-zoom-panel {
	background: #fff;
	border-radius: 14px;
	padding: 16px 18px;
	margin-bottom: 16px;
	box-shadow: 0 7px 20px rgba(17, 24, 39, 0.08);
	border: 1px solid #edf0f5;
}
.lr-batch-zoom-panel h3 {
	font-size: 1.05rem;
	font-weight: 700;
	margin: 0 0 8px;
	color: #121212;
}
.lr-batch-zoom-panel p {
	margin: 0 0 12px;
	font-size: 0.92rem;
	color: #606774;
}
.lr-batch-zoom-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: center;
}
.lr-batch-zoom-panel #lr_alert {
	margin-bottom: 12px;
}
.lr-batch-zoom-actions .btn {
	border-radius: 10px;
	font-weight: 600;
}
#lrPlayerModal {
	display: none;
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.72);
	z-index: 10001;
	align-items: center;
	justify-content: center;
	padding: 14px;
}
#lrPlayerModal.lr-open { display: flex; }
.lr-player-box {
	width: min(980px, 96vw);
	background: #111;
	border-radius: 14px;
	overflow: hidden;
	box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}
.lr-player-head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px 12px;
	background: #1c1c1c;
	color: #fff;
}
.lr-player-body {
	background: #000;
	min-height: 280px;
}
.lr-player-body iframe,
.lr-player-body video {
	width: 100%;
	min-height: 280px;
	border: 0;
	display: block;
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="javascript:history.back()" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Live class</span>
	</div>
	<div class="inst-detail-container">
		<div id="lr_msg" class="inst-muted text-center py-3">Loading...</div>
		<div id="lr_body" class="inst-detail-hidden">
			<?php if (!empty($is_teacher_host)) { ?>
			<div id="lr_batch_zoom_panel" class="lr-batch-zoom-panel">
				<h3><i class="fas fa-video" aria-hidden="true"></i> Zoom Meeting (This Batch)</h3>
				<p id="lr_batch_zoom_status">Checking Zoom link…</p>
				<div id="lr_alert" role="alert"></div>
				<div class="lr-batch-zoom-actions">
					<button type="button" id="lr_batch_zoom_create" class="btn btn-primary btn-sm">Create Zoom link</button>
					<button type="button" id="lr_batch_zoom_join" class="btn btn-outline-primary btn-sm inst-detail-hidden">Join live class</button>
				</div>
			</div>
			<?php } ?>
			<div class="inst-detail-summary-card<?php echo !empty($is_teacher_host) ? ' inst-detail-hidden' : ''; ?>" id="lr_card">
				<p><strong id="lr_title"></strong></p>
				<p class="inst-batch-meta" id="lr_meta"></p>
				<p class="inst-batch-desc" id="lr_meeting"></p>
				<?php if (empty($is_teacher_host)) { ?>
				<div id="lr_alert" role="alert"></div>
				<?php } ?>
				<p class="inst-muted" id="lr_help" style="font-size:0.9rem;margin-bottom:8px;">
					Classes open only inside this website. Zoom does not open in a separate app or browser tab.
				</p>
				<div class="lr-actions">
					<button id="lr_join_embed" type="button" class="btn btn-success btn-lg">Join class</button>
				</div>
			</div>
			<div id="lr_zoom_wrap" class="inst-detail-summary-card mt-3 inst-detail-hidden">
				<button type="button" id="lr_zoom_record" class="btn btn-danger btn-sm">Start recording</button>
				<button type="button" id="lr_zoom_close" class="btn btn-light btn-sm">Leave class</button>
				<div id="zmmtg-root-embedded" style="width:100%;min-height:520px;"></div>
			</div>
			<div class="inst-detail-summary-card lr-recordings">
				<div class="lr-recordings-head">
					<div>
						<p style="margin:0;font-weight:700;font-size:1.05rem;color:#0f172a;">Recorded meetings</p>
						<p class="inst-muted" style="margin:4px 0 0;">Watch or download cloud recordings for this batch from the same page.</p>
					</div>
					<div class="lr-recordings-tools">
						<input type="search" id="lrRecordingSearch" class="lr-recordings-search" placeholder="Search recordings">
						<button type="button" id="lrRecordingSearchBtn" class="btn btn-primary btn-sm">Search</button>
						<?php if (!empty($is_teacher_host)) { ?>
						<button type="button" id="lrRecordingSyncBtn" class="btn btn-outline-secondary btn-sm">Refresh from Zoom</button>
						<?php } ?>
					</div>
				</div>
				<div id="lr_recordings_msg" class="inst-muted text-center py-2">Loading recordings…</div>
				<div id="lr_recordings_list" class="lr-recordings-grid" role="list"></div>
				<div class="lr-record-pagination inst-detail-hidden" id="lr_recordings_pagination">
					<button type="button" id="lrRecordingPrev" class="btn btn-outline-primary btn-sm" disabled>Previous</button>
					<span id="lrRecordingPageInfo" class="lr-page-info"></span>
					<button type="button" id="lrRecordingNext" class="btn btn-outline-primary btn-sm" disabled>Next</button>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="lrPlayerModal" role="dialog" aria-modal="true" aria-labelledby="lrPlayerTitle">
	<div class="lr-player-box">
		<div class="lr-player-head">
			<strong id="lrPlayerTitle">Recording</strong>
			<button type="button" id="lrPlayerClose" class="btn btn-sm btn-light">Close</button>
		</div>
		<div id="lrPlayerBody" class="lr-player-body"></div>
	</div>
</div>
<script src="https://source.zoom.us/3.8.10/lib/vendor/react.min.js"></script>
<script src="https://source.zoom.us/3.8.10/lib/vendor/react-dom.min.js"></script>
<script src="https://source.zoom.us/3.8.10/lib/vendor/redux.min.js"></script>
<script src="https://source.zoom.us/3.8.10/lib/vendor/redux-thunk.min.js"></script>
<script src="https://source.zoom.us/3.8.10/lib/vendor/lodash.min.js"></script>
<script src="https://source.zoom.us/3.8.10/zoom-meeting-embedded-3.8.10.min.js"></script>
<script>
(function () {
	var liveClassId = <?php echo (int) (isset($live_class_id) ? $live_class_id : 0); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var detailsUrl = <?php echo json_encode((string) (isset($live_class_details_url) ? $live_class_details_url : site_url('api/batch/live-class-details'))); ?>;
	var classStatusUrl = <?php echo json_encode((string) site_url('api/batch/class-status')); ?>;
	var endMeetingUrl = <?php echo json_encode((string) (isset($live_meeting_end_url) ? $live_meeting_end_url : site_url('api/batch/live-meeting-end'))); ?>;
	var recordingStartUrl = <?php echo json_encode((string) (isset($live_recording_start_url) ? $live_recording_start_url : site_url('api/batch/live-recording-start'))); ?>;
	var recordingStopUrl = <?php echo json_encode((string) (isset($live_recording_stop_url) ? $live_recording_stop_url : site_url('api/batch/live-recording-stop'))); ?>;
	var recordingsListUrl = <?php echo json_encode((string) (isset($recorded_meeting_list_url) ? $recorded_meeting_list_url : site_url('api/batch/recorded-meeting-list'))); ?>;
	var recordingsSyncUrl = <?php echo json_encode((string) (isset($recorded_meeting_sync_url) ? $recorded_meeting_sync_url : site_url('api/batch/recorded-meeting-sync'))); ?>;
	var zoomDetailsUrl = <?php echo json_encode((string) site_url('api/batch/batch-zoom-details')); ?>;
	var zoomCreateUrl = <?php echo json_encode((string) site_url('api/batch/batch-zoom-create')); ?>;
	var batchDetailsUrl = <?php echo json_encode((string) (isset($batch_details_url) ? $batch_details_url : site_url('batch/details'))); ?>;
	var pageIsTeacherHost = <?php echo !empty($is_teacher_host) ? 'true' : 'false'; ?>;
	var currentMeeting = null;
	var zoomClient = null;
	var joinStarted = false;
	var zoomInitialized = false;
	var pollInterval = null;
	var lastClassStarted = false;
	var lastClassEnded = false;
	var recordingActive = false;
	var recordingsPage = 1;
	var recordingsLimit = 8;
	var recordingsTotalPages = 1;
	var recordingsTotalRecords = 0;
	var recordingsSearch = '';

	function ok(s) { return s === true || s === 'true'; }
	function showMsg(t) { document.getElementById('lr_msg').textContent = t || ''; }
	function showAlert(t, isError) {
		var el = document.getElementById('lr_alert');
		if (!t) { el.className = ''; el.textContent = ''; return; }
		el.textContent = t;
		el.className = 'lr-alert-show' + (isError ? ' lr-alert-error' : '');
	}
	function esc(v) {
		var d = document.createElement('div');
		d.textContent = v == null ? '' : String(v);
		return d.innerHTML;
	}
	function authHeaders() {
		return {
			'Content-Type': 'application/json',
			'Accept': 'application/json',
			'Authorization': 'Bearer ' + token
		};
	}
	function refreshBatchZoomPanel() {
		if (!pageIsTeacherHost || !token) { return; }
		var statusEl = document.getElementById('lr_batch_zoom_status');
		var btnCreate = document.getElementById('lr_batch_zoom_create');
		var btnJoin = document.getElementById('lr_batch_zoom_join');
		if (!statusEl || !btnCreate || !btnJoin) { return; }
		statusEl.textContent = 'Checking Zoom link…';
		btnCreate.disabled = false;
		btnJoin.classList.add('inst-detail-hidden');
		fetch(zoomDetailsUrl, {
			method: 'POST',
			headers: authHeaders(),
			body: JSON.stringify({ batch_id: batchId, access_token: token })
		}).then(function (r) { return r.json(); }).then(function (j) {
			var okz = ok(j.status);
			var z = (j.data && j.data.zoom) ? j.data.zoom : {};
			var active = okz && (z.isActive === 1 || z.isActive === '1' || z.meetingStatus === 1 || z.meetingStatus === '1')
				&& (z.zoomMeetingId || z.joinUrl);
			// Fallback for older API payloads that only return active meetings.
			if (!active && okz && (z.zoomMeetingId || z.joinUrl) && z.isActive == null && z.meetingStatus == null) {
				active = true;
			}
			if (active) {
				statusEl.textContent = 'Zoom is linked. Everyone joins only inside your website/app (Live classes).';
				btnCreate.textContent = 'Zoom already linked';
				btnCreate.disabled = true;
				btnJoin.textContent = 'Join live class';
				btnJoin.classList.remove('inst-detail-hidden');
				return;
			}
			statusEl.textContent = 'No Zoom meeting yet. Create one to generate a join link for this batch (Server-to-Server Zoom must be configured on the server).';
			btnCreate.textContent = 'Create Zoom link';
			btnCreate.disabled = false;
			btnJoin.classList.add('inst-detail-hidden');
		}).catch(function () {
			statusEl.textContent = 'Could not check Zoom status. Try again or verify Zoom API credentials.';
			btnCreate.textContent = 'Create Zoom link';
			btnCreate.disabled = false;
			btnJoin.classList.add('inst-detail-hidden');
		});
	}
	function isJoinReady(m) {
		return m && (m.joinReady === 1 || m.joinReady === '1' || (m.sdkKey && m.signature && m.meetingNumber));
	}
	function leaveClass() {
		console.log('[Leave] Leaving class, cleaning up Zoom');

		// Disconnect from Zoom meeting
		if (zoomClient && zoomInitialized) {
			try {
				console.log('[Leave] Calling zoomClient.leave()');
				zoomClient.leave();
			} catch (e) {
				console.log('[Leave] Error leaving Zoom:', e.message);
			}
		}

		var wrap = document.getElementById('lr_zoom_wrap');
		if (wrap) {
			wrap.classList.remove('lr-zoom-active');
			wrap.classList.add('inst-detail-hidden');
		}
		var card = document.getElementById('lr_card');
		if (card && !pageIsTeacherHost) {
			card.classList.remove('inst-detail-hidden');
		}
		var btn = document.getElementById('lr_join_embed');
		if (btn) {
			btn.disabled = false;
			btn.textContent = pageIsTeacherHost ? 'Start / join class' : 'Join class';
		}
		var closeBtn = document.getElementById('lr_zoom_close');
		if (closeBtn) {
			closeBtn.style.display = 'none';
			closeBtn.textContent = 'Leave class';
			closeBtn.disabled = false;
		}

		// CRITICAL: Reset ALL flags so user can rejoin without page refresh
		joinStarted = false;
		zoomInitialized = false;

		console.log('[Leave] Cleanup complete');
		showMsg('');
		showAlert('');
	}

	function onZoomConnectionChange(payload) {
		// Called when Zoom connection changes (Closed, Failed, etc)
		if (payload && (payload.state === 'Closed' || payload.state === 'Fail')) {
			console.log('Zoom disconnected. State:', payload.state);
			leaveClass();
		}
	}

	// TEACHER: End class and disconnect all students
	function endClassForAllStudents() {
		if (!pageIsTeacherHost) {
			showAlert('Only teachers can end the class.', true);
			return;
		}
		if (!confirm('Are you sure you want to end the class for all students? Recording will stop and save to cloud.')) {
			return;
		}

		var closeBtn = document.getElementById('lr_zoom_close');
		var recBtn = document.getElementById('lr_zoom_record');
		if (closeBtn) {
			closeBtn.disabled = true;
			closeBtn.textContent = 'Ending class...';
		}
		if (recBtn) { recBtn.disabled = true; }

		// Call API to end meeting and notify all students
		var body = { batch_id: batchId, access_token: token };
		if (liveClassId > 0) { body.live_class_id = liveClassId; }

		fetch(endMeetingUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (ok(j.status)) {
				recordingActive = false;
				leaveClass();
				refreshBatchZoomPanel();
				loadRecordings();
				showAlert('Class ended. Recording is saving to Zoom cloud. Create a new Zoom link to start the next session.', false);
				var closeBtn2 = document.getElementById('lr_zoom_close');
				var recBtn2 = document.getElementById('lr_zoom_record');
				if (closeBtn2) {
					closeBtn2.disabled = false;
					closeBtn2.textContent = pageIsTeacherHost ? 'End Class' : 'Leave class';
				}
				if (recBtn2) { recBtn2.disabled = false; }
			} else {
				showAlert('Error ending class: ' + (j.msg || 'Unknown error'), true);
				if (closeBtn) {
					closeBtn.disabled = false;
					closeBtn.textContent = 'End class';
				}
				if (recBtn) { recBtn.disabled = false; }
			}
		}).catch(function (e) {
			showAlert('Could not end class: ' + (e.message || 'Network error'), true);
			if (closeBtn) {
				closeBtn.disabled = false;
				closeBtn.textContent = 'End class';
			}
			if (recBtn) { recBtn.disabled = false; }
		});
	}

	function syncRecordButton() {
		var recBtn = document.getElementById('lr_zoom_record');
		if (!recBtn || !pageIsTeacherHost) { return; }
		recBtn.classList.add('lr-record-show');
		recBtn.disabled = false;
		if (recordingActive) {
			recBtn.textContent = 'Stop recording';
			recBtn.className = 'btn btn-warning btn-sm lr-record-show';
		} else {
			recBtn.textContent = 'Start recording';
			recBtn.className = 'btn btn-danger btn-sm lr-record-show';
		}
	}

	function toggleCloudRecording() {
		if (!pageIsTeacherHost) {
			showAlert('Only teachers can control recording.', true);
			return;
		}
		var recBtn = document.getElementById('lr_zoom_record');
		var starting = !recordingActive;
		var url = starting ? recordingStartUrl : recordingStopUrl;
		if (recBtn) {
			recBtn.disabled = true;
			recBtn.textContent = starting ? 'Starting…' : 'Stopping…';
		}
		var body = { batch_id: batchId, access_token: token };
		if (liveClassId > 0) { body.live_class_id = liveClassId; }
		fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (ok(j.status)) {
				recordingActive = starting;
				syncRecordButton();
				showAlert(starting
					? 'Cloud recording started. It will save when you stop recording or end the class.'
					: 'Recording stopped. Zoom is processing the cloud file — check Recorded meetings in a few minutes.', false);
			} else {
				showAlert((j && j.msg) ? j.msg : 'Could not control recording.', true);
				syncRecordButton();
			}
		}).catch(function (e) {
			showAlert('Could not control recording: ' + (e.message || 'Network error'), true);
			syncRecordButton();
		});
	}
	function recordingAuthHeaders() {
		return {
			'Content-Type': 'application/json',
			'Accept': 'application/json',
			'Authorization': 'Bearer ' + token
		};
	}
	function parseJsonResponse(r) {
		return r.text().then(function (text) {
			var t = (text || '').trim();
			if (t.indexOf('<') === 0) {
				throw new Error('Server returned HTML instead of JSON.');
			}
			try { return JSON.parse(t); } catch (e) { throw new Error('Invalid JSON from server'); }
		});
	}
	function recordingsBody(extra) {
		var body = { batch_id: batchId, page: recordingsPage, limit: recordingsLimit, access_token: token };
		if (recordingsSearch) { body.search = recordingsSearch; }
		if (extra && typeof extra === 'object') {
			for (var k in extra) { if (Object.prototype.hasOwnProperty.call(extra, k)) { body[k] = extra[k]; } }
		}
		return body;
	}
	function showRecordingsMsg(t, isError) {
		var el = document.getElementById('lr_recordings_msg');
		el.textContent = t || '';
		el.className = 'text-center py-2 ' + (isError ? 'text-danger' : 'inst-muted');
	}
	function recordingMeta(row) {
		var parts = [];
		if (row.recordingStart) { parts.push(String(row.recordingStart).replace('T', ' ').substring(0, 16)); }
		if (row.durationMinutes) { parts.push(row.durationMinutes + ' min'); }
		if (row.fileSizeLabel) { parts.push(row.fileSizeLabel); }
		return parts.join(' · ');
	}
	function recordingCard(row) {
		var play = row.playUrl || row.downloadUrl || '';
		var meta = recordingMeta(row);
		return '<article class="lr-record-card" role="listitem">' +
			'<h4>' + esc(row.topic || 'Recorded class') + '</h4>' +
			(meta ? '<p class="lr-record-meta">' + esc(meta) + '</p>' : '') +
			'<div class="lr-record-actions">' +
			(play
				? '<button type="button" class="btn btn-primary btn-sm lr-rec-play" data-play="' + esc(play) + '" data-title="' + esc(row.topic || 'Recording') + '"><i class="fas fa-play"></i> Watch</button>'
				: '<span class="inst-muted small">No playback URL</span>') +
			(row.downloadUrl
				? ' <a class="btn btn-outline-secondary btn-sm" href="' + esc(row.downloadUrl) + '" target="_blank" rel="noopener noreferrer"><i class="fas fa-download"></i> Download</a>'
				: '') +
			'</div>' +
		'</article>';
	}
	function updateRecordingsPagination() {
		var pag = document.getElementById('lr_recordings_pagination');
		if (recordingsTotalRecords < 1) {
			pag.classList.add('inst-detail-hidden');
			return;
		}
		pag.classList.remove('inst-detail-hidden');
		document.getElementById('lrRecordingPageInfo').textContent = 'Page ' + recordingsPage + ' / ' + recordingsTotalPages;
		document.getElementById('lrRecordingPrev').disabled = recordingsPage <= 1;
		document.getElementById('lrRecordingNext').disabled = recordingsPage >= recordingsTotalPages;
	}
	function openRecordingPlayer(url, title) {
		var modal = document.getElementById('lrPlayerModal');
		var body = document.getElementById('lrPlayerBody');
		document.getElementById('lrPlayerTitle').textContent = title || 'Recording';
		body.innerHTML = '';
		if (/\.(mp4|webm|ogg)(\?|$)/i.test(url)) {
			var v = document.createElement('video');
			v.controls = true;
			v.playsInline = true;
			v.src = url;
			body.appendChild(v);
		} else {
			var iframe = document.createElement('iframe');
			iframe.src = url;
			iframe.allow = 'autoplay; fullscreen';
			iframe.title = title || 'Recording';
			body.appendChild(iframe);
		}
		modal.classList.add('lr-open');
	}
	function closeRecordingPlayer() {
		document.getElementById('lrPlayerModal').classList.remove('lr-open');
		document.getElementById('lrPlayerBody').innerHTML = '';
	}
	function loadRecordings(opts) {
		opts = opts || {};
		if (batchId < 1) {
			showRecordingsMsg('Invalid batch id.', true);
			return;
		}
		showRecordingsMsg('Loading recordings…', false);
		document.getElementById('lr_recordings_list').innerHTML = '';
		fetch(recordingsListUrl, {
			method: 'POST',
			headers: recordingAuthHeaders(),
			body: JSON.stringify(recordingsBody(opts.sync ? { sync: 1 } : {}))
		}).then(parseJsonResponse).then(function (j) {
			if (!ok(j.status)) {
				showRecordingsMsg((j && (j.msg || j.message)) || 'Could not load recordings.', true);
				return;
			}
			var data = j.data || {};
			var rows = data.recordedMeetings || [];
			var p = data.pagination || {};
			recordingsTotalRecords = parseInt(p.totalRecords || p.total || 0, 10) || 0;
			recordingsTotalPages = parseInt(p.totalPages || 1, 10) || 1;
			recordingsPage = parseInt(p.page || recordingsPage, 10) || recordingsPage;
			if (data.syncError && rows.length === 0) {
				showRecordingsMsg(data.syncError, true);
			} else {
				showRecordingsMsg(rows.length ? '' : 'No recorded meetings for this batch yet. Recordings appear here after a Zoom class with cloud recording is completed.');
			}
			document.getElementById('lr_recordings_list').innerHTML = rows.map(recordingCard).join('');
			updateRecordingsPagination();
		}).catch(function (e) {
			showRecordingsMsg((e && e.message) ? e.message : 'Network error.', true);
		});
	}
	function fetchMeetingDetails() {
		var detailsBody = { live_class_id: liveClassId };
		if (liveClassId === 0 && batchId > 0) {
			detailsBody.batch_id = batchId;
		}
		return fetch(detailsUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(detailsBody)
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!ok(j.status)) {
				throw new Error(j.msg || 'You cannot join this class.');
			}
			var row = j.liveClass || {};
			var m = row.meeting || {};
			currentMeeting = m;
			return row;
		});
	}

	// CRITICAL: Fetch real-time class status for polling
	function fetchClassStatus() {
		var body = { batch_id: batchId };
		if (liveClassId > 0) { body.live_class_id = liveClassId; }
		return fetch(classStatusUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!ok(j.status)) {
				throw new Error(j.msg || 'Could not fetch class status.');
			}
			return j.data || {};
		});
	}
	function runZoomJoin(m, signatureOverride) {
		var mn = String(m.meetingNumber || '').replace(/\D/g, '');
		var sig = signatureOverride || m.signature;
		var joinOpts = {
			sdkKey: m.sdkKey,
			clientId: m.sdkKey,
			signature: sig,
			meetingNumber: mn,
			password: m.password || '',
			userName: m.displayName || (pageIsTeacherHost ? 'Teacher' : 'Student')
		};
		return zoomClient.join(joinOpts);
	}
	function joinEmbeddedZoom() {
		console.log('[Join] joinEmbeddedZoom called, joinStarted=' + joinStarted + ', zoomInitialized=' + zoomInitialized);
		if (joinStarted) {
			console.log('[Join] Already joining, skipping');
			return;
		}
		if (!window.ZoomMtgEmbedded || !window.ZoomMtgEmbedded.createClient) {
			console.log('[Join] Zoom SDK not loaded');
			showAlert('Zoom Meeting SDK failed to load. Refresh and try again.', true);
			return;
		}
		var wrap = document.getElementById('lr_zoom_wrap');
		var btn = document.getElementById('lr_join_embed');
		joinStarted = true;
		btn.disabled = true;
		btn.textContent = 'Connecting…';
		showAlert('');
		showMsg('');
		fetchMeetingDetails().then(function (row) {
			var m = row.meeting || currentMeeting;
			if (m.type !== 'zoom' || !isJoinReady(m)) {
				throw new Error(m.sdkConfigHint || 'Meeting SDK is not configured on the server.');
			}
			document.getElementById('lr_card').classList.add('inst-detail-hidden');
			wrap.classList.remove('inst-detail-hidden');
			wrap.classList.add('lr-zoom-active');
			if (!zoomClient) {
				zoomClient = window.ZoomMtgEmbedded.createClient();
			}
			return zoomClient.init({
				zoomAppRoot: document.getElementById('zmmtg-root-embedded'),
				language: 'en-US',
				patchJsMedia: true,
				leaveOnPageUnload: true
			}).then(function () {
				zoomInitialized = true;
				return runZoomJoin(m);
			});
		}).then(function () {
			btn.textContent = 'In class';
			// For teachers: show "End Class" button
			if (pageIsTeacherHost) {
				var closeBtn = document.getElementById('lr_zoom_close');
				if (closeBtn) {
					closeBtn.style.display = 'inline-block';
					closeBtn.textContent = 'End Class';
					closeBtn.disabled = false;
				}
				syncRecordButton();
				// Notify server that teacher has joined (also auto-starts cloud recording)
				var notifyBody = { batch_id: batchId, action: 'host_joined' };
				if (liveClassId > 0) { notifyBody.live_class_id = liveClassId; }
				fetch(endMeetingUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
					body: JSON.stringify(notifyBody)
				}).then(function (r) { return r.json(); }).then(function (j) {
					var data = (j && j.data) ? j.data : {};
					if (ok(j.status) && (data.recordingStarted === true || data.recordingStarted === 1 || data.recordingStarted === '1' || data.recordingStatus === 'recording')) {
						recordingActive = true;
						syncRecordButton();
						showAlert('Class started. Cloud recording is ON.', false);
					} else if (ok(j.status) && data.recordingError) {
						showAlert('Class started, but cloud recording did not start: ' + data.recordingError + ' Use Start recording if needed.', true);
					}
				}).catch(function () {
					// Silently continue even if notification fails
				});
			}
		}).catch(function (e) {
			var err = (e && (e.reason || e.message)) ? String(e.reason || e.message) : 'Could not join.';
			console.log('[Join] Error:', err, e);
			if (currentMeeting && currentMeeting.signatureAlt && err.toLowerCase().indexOf('signature') !== -1) {
				console.log('[Join] Retrying with alt signature');
				return runZoomJoin(currentMeeting, currentMeeting.signatureAlt).then(function () {
					btn.textContent = 'In class';
					// Show "End Class" button for teachers
					if (pageIsTeacherHost) {
						var closeBtn = document.getElementById('lr_zoom_close');
						if (closeBtn) {
							closeBtn.style.display = 'inline-block';
							closeBtn.textContent = 'End Class';
							closeBtn.disabled = false;
						}
						syncRecordButton();
					}
				}).catch(function (e2) {
					err = (e2 && (e2.reason || e2.message)) ? String(e2.reason || e2.message) : err;
					console.log('[Join] Alt signature failed:', err);
					zoomInitialized = false;
					joinStarted = false;
					btn.disabled = false;
					btn.textContent = pageIsTeacherHost ? 'Start / join class' : 'Join class';
					leaveClass();
					var hint = (currentMeeting && currentMeeting.sdkConfigHint) ? (' ' + currentMeeting.sdkConfigHint) : '';
					showAlert('Could not join in-page: ' + err + hint, true);
				});
			}
			zoomInitialized = false;
			joinStarted = false;
			btn.disabled = false;
			btn.textContent = pageIsTeacherHost ? 'Start / join class' : 'Join class';
			leaveClass();
			var hint = (currentMeeting && currentMeeting.sdkConfigHint) ? (' ' + currentMeeting.sdkConfigHint) : '';
			showAlert('Could not join in-page: ' + err + hint, true);
		});
	}

	if (liveClassId < 1 && batchId < 1) {
		showMsg('Invalid live class or batch.');
		return;
	}
	document.getElementById('lr_join_embed').addEventListener('click', joinEmbeddedZoom);

	// Close button behavior depends on user role
	var closeBtn = document.getElementById('lr_zoom_close');
	closeBtn.addEventListener('click', function () {
		if (pageIsTeacherHost) {
			endClassForAllStudents();
		} else {
			leaveClass();
		}
	});
	var recordBtn = document.getElementById('lr_zoom_record');
	if (recordBtn) {
		recordBtn.addEventListener('click', toggleCloudRecording);
	}
	document.getElementById('lr_recordings_list').addEventListener('click', function (ev) {
		var btn = ev.target.closest('.lr-rec-play');
		if (!btn) { return; }
		openRecordingPlayer(btn.getAttribute('data-play') || '', btn.getAttribute('data-title') || 'Recording');
	});
	document.getElementById('lrPlayerClose').addEventListener('click', closeRecordingPlayer);
	document.getElementById('lrPlayerModal').addEventListener('click', function (ev) {
		if (ev.target === this) { closeRecordingPlayer(); }
	});
	document.getElementById('lrRecordingSearchBtn').addEventListener('click', function () {
		recordingsSearch = (document.getElementById('lrRecordingSearch').value || '').trim();
		recordingsPage = 1;
		loadRecordings();
	});
	document.getElementById('lrRecordingSearch').addEventListener('keydown', function (ev) {
		if (ev.key === 'Enter') {
			ev.preventDefault();
			recordingsSearch = (this.value || '').trim();
			recordingsPage = 1;
			loadRecordings();
		}
	});
	var syncBtn = document.getElementById('lrRecordingSyncBtn');
	if (syncBtn) {
		syncBtn.addEventListener('click', function () {
			loadRecordings({ sync: 1 });
		});
	}
	document.getElementById('lrRecordingPrev').addEventListener('click', function () {
		if (recordingsPage > 1) {
			recordingsPage -= 1;
			loadRecordings();
		}
	});
	document.getElementById('lrRecordingNext').addEventListener('click', function () {
		if (recordingsPage < recordingsTotalPages) {
			recordingsPage += 1;
			loadRecordings();
		}
	});

	var batchZoomCreate = document.getElementById('lr_batch_zoom_create');
	if (batchZoomCreate) {
		batchZoomCreate.addEventListener('click', function () {
			if (batchZoomCreate.disabled) { return; }
			var topic = (document.getElementById('lr_title') && document.getElementById('lr_title').textContent)
				? document.getElementById('lr_title').textContent.trim() : ('Batch ' + batchId);
			batchZoomCreate.disabled = true;
			batchZoomCreate.textContent = 'Creating…';
			fetch(zoomCreateUrl, {
				method: 'POST',
				headers: authHeaders(),
				body: JSON.stringify({ batch_id: batchId, topic: topic || 'Live class', access_token: token })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					showAlert(j.msg || 'Zoom meeting created', false);
					refreshBatchZoomPanel();
					// Reload meeting details so Join can use the new meeting
					fetchMeetingDetails().then(function (row) {
						currentMeeting = row.meeting || currentMeeting;
						var m = currentMeeting || {};
						document.getElementById('lr_meeting').textContent = m.meetingNumber
							? ('Meeting ID: ' + m.meetingNumber)
							: 'Meeting not available.';
					}).catch(function () {});
					return;
				}
				batchZoomCreate.disabled = false;
				batchZoomCreate.textContent = 'Create Zoom link';
				showAlert((j && j.msg) ? j.msg : 'Could not create Zoom meeting', true);
			}).catch(function (e) {
				batchZoomCreate.disabled = false;
				batchZoomCreate.textContent = 'Create Zoom link';
				showAlert((e && e.message) ? e.message : 'Network error creating Zoom meeting', true);
			});
		});
	}
	var batchZoomJoin = document.getElementById('lr_batch_zoom_join');
	if (batchZoomJoin) {
		batchZoomJoin.addEventListener('click', function () {
			var joinBtn = document.getElementById('lr_join_embed');
			if (joinBtn && !joinBtn.disabled) {
				joinBtn.click();
			} else {
				showAlert('Meeting is not ready to join yet. Wait a moment or refresh.', true);
			}
		});
	}

	fetchMeetingDetails().then(function (row) {
		var m = row.meeting || {};
		showMsg('');
		document.getElementById('lr_body').classList.remove('inst-detail-hidden');
		refreshBatchZoomPanel();

		var isHost = (m.isHost === 1 || m.isHost === '1' || m.role === 1 || m.role === '1');
		var classStarted = m.classStarted === 1 || m.classStarted === '1' || (m.hostJoinedAt && m.hostJoinedAt !== '');
		var classEnded = m.endedAt && m.endedAt !== '';

		document.getElementById('lr_title').textContent = row.subjectName || 'Live Class';
		document.getElementById('lr_meta').textContent = [row.teacherName || '', row.date || '', row.startTime || ''].filter(Boolean).join(' | ');
		document.getElementById('lr_meeting').textContent = m.meetingNumber
			? ('Meeting ID: ' + m.meetingNumber)
			: 'Meeting not available.';

		var joinBtn = document.getElementById('lr_join_embed');
		joinBtn.textContent = (isHost || pageIsTeacherHost) ? 'Start / join class' : 'Join class';
		// Teacher uses "Join live class" / "Create Zoom link" on the Zoom Meeting card.
		if (pageIsTeacherHost) {
			var actions = joinBtn.parentNode;
			if (actions) { actions.classList.add('inst-detail-hidden'); }
		}

		if (m.type !== 'zoom') {
			joinBtn.disabled = true;
			showAlert('This batch does not use Zoom.', true);
			return;
		}
		if (!m.meetingNumber) {
			joinBtn.disabled = true;
			if (pageIsTeacherHost) {
				showAlert('Class ended or no Zoom link yet. Click Create Zoom link to start a new session.', false);
			} else {
				showAlert('No live Zoom meeting yet. Please wait for the teacher to create one.', false);
			}
			return;
		}
		if (!isJoinReady(m)) {
			showAlert(m.sdkConfigHint || 'In-page Zoom is not configured yet. Admin must add Meeting SDK Key + Secret.', true);
			return;
		}

		// Check time validation - can user join at this time?
		if (m.canJoin === 0 || m.canJoin === '0') {
			joinBtn.disabled = true;
			showAlert(m.timeMessage || 'You cannot join the class at this time.', false);
			// Still start polling for students so they can join when allowed
			if (!pageIsTeacherHost && !isHost) {
				startStudentStatusPolling();
			}
			return;
		}

		// Check if class has been ended
		if (classEnded) {
			if (pageIsTeacherHost || isHost) {
				// Teacher: offer to start a new session
				joinBtn.disabled = false;
				showAlert('Previous class session ended. You can start a new session now.', false);
				return;
			} else {
				// Student: show class ended message
				joinBtn.disabled = true;
				showAlert('This class session has ended.', false);
				return;
			}
		}

		// For students: Always start polling to detect class start/end
		if (!pageIsTeacherHost && !isHost) {
			// Start polling regardless of current status
			startStudentStatusPolling();

			if (!classStarted) {
				joinBtn.disabled = true;
				showAlert('Please wait for the teacher to start the class.', false);
				lastClassStarted = false;
				return;
			}
		}
	}).catch(function (e) {
		showMsg('');
		document.getElementById('lr_body').classList.remove('inst-detail-hidden');
		refreshBatchZoomPanel();
		var joinBtn = document.getElementById('lr_join_embed');
		if (joinBtn) {
			joinBtn.disabled = true;
			if (pageIsTeacherHost && joinBtn.parentNode) {
				joinBtn.parentNode.classList.add('inst-detail-hidden');
			}
		}
		document.getElementById('lr_title').textContent = 'Live Class';
		document.getElementById('lr_meeting').textContent = 'Meeting not available.';
		showAlert((e && e.message) ? e.message : 'Network error loading class.', true);
	});

	// STUDENT POLLING: Monitor class status every 2 seconds
	function startStudentStatusPolling() {
		if (pageIsTeacherHost) { return; } // Teachers don't poll
		if (pollInterval) { return; } // Already polling

		console.log('[LiveClass] Starting student status polling');

		pollInterval = setInterval(function () {
			if (joinStarted) {
				// Student is in meeting - check if teacher ended class
				fetchClassStatus().then(function (status) {
					console.log('[Poll] In meeting:', { shouldAutoDisconnect: status.shouldAutoDisconnect, lastClassEnded: lastClassEnded, joinStarted: joinStarted });

					// Reset lastClassEnded if teacher started a new session
					if (status.classStarted && lastClassEnded) {
						lastClassEnded = false;
					}

					// CRITICAL: Auto-disconnect when teacher ends class
					if (status.shouldAutoDisconnect && !lastClassEnded) {
						console.log('[Poll] AUTO-DISCONNECT TRIGGERED');
						lastClassEnded = true;
						if (joinStarted) {
							leaveClass();
							showAlert('Teacher ended the class. You have been disconnected.', false);
							setTimeout(function () {
								window.location.reload();
							}, 3000);
						}
					}
				}).catch(function (e) {
					console.log('[Poll] Error fetching status:', e.message);
				});
			} else {
				// Student is NOT in meeting - check if teacher started
				fetchClassStatus().then(function (status) {
					console.log('[Poll] Waiting:', { classStarted: status.classStarted, lastClassStarted: lastClassStarted });

					if (status.classStarted && !lastClassStarted) {
						// Teacher just started - enable join button
						lastClassStarted = true;
						lastClassEnded = false;
						var joinBtn = document.getElementById('lr_join_embed');
						if (joinBtn) {
							joinBtn.disabled = false;
							showAlert('Teacher started the class! You can now join.', false);
							setTimeout(function () { showAlert(''); }, 5000);
						}
					}
					if (status.classEnded && !status.classStarted) {
						// Class is over
						var joinBtn = document.getElementById('lr_join_embed');
						if (joinBtn) {
							joinBtn.disabled = true;
							showAlert('This class session has ended.', false);
						}
					}
				}).catch(function (e) {
					console.log('[Poll] Error fetching status:', e.message);
				});
			}
		}, 2000); // Poll every 2 seconds
	}

	// Stop polling when page unloads
	window.addEventListener('beforeunload', function () {
		if (pollInterval) {
			clearInterval(pollInterval);
		}
	});
	loadRecordings();
})();
</script>
