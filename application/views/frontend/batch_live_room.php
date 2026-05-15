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
#lr_zoom_wrap.lr-zoom-active #lr_zoom_close { display: inline-block; }
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="javascript:history.back()" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Live class</span>
	</div>
	<div class="inst-detail-container">
		<div id="lr_msg" class="inst-muted text-center py-3">Loading...</div>
		<div id="lr_body" class="inst-detail-hidden">
			<div class="inst-detail-summary-card" id="lr_card">
				<p><strong id="lr_title"></strong></p>
				<p class="inst-batch-meta" id="lr_meta"></p>
				<p class="inst-batch-desc" id="lr_meeting"></p>
				<div id="lr_alert" role="alert"></div>
				<p class="inst-muted" id="lr_help" style="font-size:0.9rem;margin-bottom:8px;">
					Classes open only inside this website. Zoom does not open in a separate app or browser tab.
				</p>
				<div class="lr-actions">
					<button id="lr_join_embed" type="button" class="btn btn-success btn-lg">Join class</button>
				</div>
			</div>
			<div id="lr_zoom_wrap" class="inst-detail-summary-card mt-3 inst-detail-hidden">
				<button type="button" id="lr_zoom_close" class="btn btn-light btn-sm">Leave class</button>
				<div id="zmmtg-root-embedded" style="width:100%;min-height:520px;"></div>
			</div>
		</div>
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
	var pageIsTeacherHost = <?php echo !empty($is_teacher_host) ? 'true' : 'false'; ?>;
	var currentMeeting = null;
	var zoomClient = null;
	var joinStarted = false;

	function ok(s) { return s === true || s === 'true'; }
	function showMsg(t) { document.getElementById('lr_msg').textContent = t || ''; }
	function showAlert(t, isError) {
		var el = document.getElementById('lr_alert');
		if (!t) { el.className = ''; el.textContent = ''; return; }
		el.textContent = t;
		el.className = 'lr-alert-show' + (isError ? ' lr-alert-error' : '');
	}
	function isJoinReady(m) {
		return m && (m.joinReady === 1 || m.joinReady === '1' || (m.sdkKey && m.signature && m.meetingNumber));
	}
	function leaveClass() {
		var wrap = document.getElementById('lr_zoom_wrap');
		wrap.classList.remove('lr-zoom-active', 'inst-detail-hidden');
		document.getElementById('lr_card').classList.remove('inst-detail-hidden');
		document.getElementById('lr_join_embed').disabled = false;
		document.getElementById('lr_join_embed').textContent = pageIsTeacherHost ? 'Start / join class' : 'Join class';
		joinStarted = false;
		showMsg('');
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
		if (joinStarted) { return; }
		if (!window.ZoomMtgEmbedded || !window.ZoomMtgEmbedded.createClient) {
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
			}).then(function () { return runZoomJoin(m); });
		}).then(function () {
			btn.textContent = 'In class';
		}).catch(function (e) {
			var err = (e && (e.reason || e.message)) ? String(e.reason || e.message) : 'Could not join.';
			if (currentMeeting && currentMeeting.signatureAlt && err.toLowerCase().indexOf('signature') !== -1) {
				return runZoomJoin(currentMeeting, currentMeeting.signatureAlt).then(function () {
					btn.textContent = 'In class';
				}).catch(function (e2) {
					err = (e2 && (e2.reason || e2.message)) ? String(e2.reason || e2.message) : err;
					joinStarted = false;
					btn.disabled = false;
					btn.textContent = pageIsTeacherHost ? 'Start / join class' : 'Join class';
					leaveClass();
					var hint = (currentMeeting && currentMeeting.sdkConfigHint) ? (' ' + currentMeeting.sdkConfigHint) : '';
					showAlert('Could not join in-page: ' + err + hint, true);
				});
			}
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
	document.getElementById('lr_zoom_close').addEventListener('click', leaveClass);

	fetchMeetingDetails().then(function (row) {
		var m = row.meeting || {};
		showMsg('');
		document.getElementById('lr_body').classList.remove('inst-detail-hidden');

		var isHost = (m.isHost === 1 || m.isHost === '1' || m.role === 1 || m.role === '1');
		document.getElementById('lr_title').textContent = row.subjectName || 'Live Class';
		document.getElementById('lr_meta').textContent = [row.teacherName || '', row.date || '', row.startTime || ''].filter(Boolean).join(' | ');
		document.getElementById('lr_meeting').textContent = m.meetingNumber
			? ('Meeting ID: ' + m.meetingNumber)
			: 'Meeting not available.';

		var joinBtn = document.getElementById('lr_join_embed');
		joinBtn.textContent = (isHost || pageIsTeacherHost) ? 'Start / join class' : 'Join class';

		if (m.type !== 'zoom') {
			joinBtn.disabled = true;
			showAlert('This batch does not use Zoom.', true);
			return;
		}
		if (!isJoinReady(m)) {
			showAlert(m.sdkConfigHint || 'In-page Zoom is not configured yet. Admin must add Meeting SDK Key + Secret.', true);
			return;
		}
	}).catch(function (e) {
		showMsg('');
		showAlert((e && e.message) ? e.message : 'Network error loading class.', true);
	});
})();
</script>
