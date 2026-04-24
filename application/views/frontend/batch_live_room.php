<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="javascript:history.back()" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Zoom Room</span>
	</div>
	<div class="inst-detail-container">
		<div id="lr_msg" class="inst-muted text-center py-3">Loading...</div>
		<div id="lr_body" class="inst-detail-hidden">
			<div class="inst-detail-summary-card">
				<p><strong id="lr_title"></strong></p>
				<p class="inst-batch-meta" id="lr_meta"></p>
				<p class="inst-batch-desc" id="lr_meeting"></p>
				<div style="display:flex;gap:10px;flex-wrap:wrap;">
					<button id="lr_join_embed" type="button" class="btn btn-success">Join in this page</button>
					<a id="lr_web" class="btn btn-primary" href="#" target="_blank" rel="noopener">Join on Web</a>
					<a id="lr_mobile" class="btn btn-outline-primary" href="#">Open Zoom App</a>
				</div>
			</div>
			<div id="lr_zoom_wrap" class="inst-detail-summary-card mt-3 inst-detail-hidden">
				<div id="zmmtg-root-embedded" style="width:100%;min-height:520px;"></div>
			</div>
		</div>
	</div>
</div>
<script src="https://source.zoom.us/2.9.7/lib/vendor/react.min.js"></script>
<script src="https://source.zoom.us/2.9.7/lib/vendor/react-dom.min.js"></script>
<script src="https://source.zoom.us/2.9.7/lib/vendor/redux.min.js"></script>
<script src="https://source.zoom.us/2.9.7/lib/vendor/redux-thunk.min.js"></script>
<script src="https://source.zoom.us/2.9.7/lib/vendor/lodash.min.js"></script>
<script src="https://source.zoom.us/2.9.7/zoom-meeting-embedded-2.9.7.min.js"></script>
<script>
(function () {
	var liveClassId = <?php echo (int) (isset($live_class_id) ? $live_class_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var detailsUrl = <?php echo json_encode((string) (isset($live_class_details_url) ? $live_class_details_url : site_url('api/batch/live-class-details'))); ?>;
	var currentMeeting = null;
	function ok(s) { return s === true || s === 'true'; }
	function showMsg(t) { document.getElementById('lr_msg').textContent = t || ''; }
	function joinEmbeddedZoom() {
		if (!currentMeeting || currentMeeting.type !== 'zoom') {
			showMsg('Embedded Zoom is available only for Zoom classes.');
			return;
		}
		if (!window.ZoomMtgEmbedded || !window.ZoomMtgEmbedded.createClient) {
			showMsg('Zoom SDK is not loaded. Please use Join on Web.');
			return;
		}
		if (!currentMeeting.sdkKey || !currentMeeting.signature || !currentMeeting.meetingNumber) {
			showMsg('Zoom join data is incomplete. Please use Join on Web.');
			return;
		}

		var wrap = document.getElementById('lr_zoom_wrap');
		wrap.classList.remove('inst-detail-hidden');
		showMsg('Starting embedded Zoom...');

		try {
			var zoomClient = window.ZoomMtgEmbedded.createClient();
			zoomClient.init({
				zoomAppRoot: document.getElementById('zmmtg-root-embedded'),
				language: 'en-US',
				patchJsMedia: true,
				leaveOnPageUnload: true
			}).then(function () {
				return zoomClient.join({
					sdkKey: currentMeeting.sdkKey,
					signature: currentMeeting.signature,
					meetingNumber: String(currentMeeting.meetingNumber),
					password: currentMeeting.password || '',
					userName: currentMeeting.displayName || 'Student'
				});
			}).then(function () {
				showMsg('');
			}).catch(function (e) {
				showMsg((e && (e.reason || e.message)) ? ('Zoom join failed: ' + (e.reason || e.message)) : 'Zoom join failed. You can use Join on Web.');
			});
		} catch (e) {
			showMsg('Could not initialize Zoom. You can use Join on Web.');
		}
	}
	if (liveClassId < 1) {
		showMsg('Invalid live class id.');
		return;
	}
	document.getElementById('lr_join_embed').addEventListener('click', joinEmbeddedZoom);
	fetch(detailsUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
		body: JSON.stringify({ live_class_id: liveClassId })
	}).then(function (r) { return r.json(); }).then(function (j) {
		if (!ok(j.status)) {
			showMsg(j.msg || 'Unable to load meeting.');
			return;
		}
		var row = j.liveClass || {};
		var m = row.meeting || {};
		currentMeeting = m;
		showMsg('');
		document.getElementById('lr_body').classList.remove('inst-detail-hidden');
		document.getElementById('lr_title').textContent = row.subjectName || 'Live Class';
		document.getElementById('lr_meta').textContent = [row.teacherName || '', row.date || '', row.startTime || ''].filter(Boolean).join(' | ');
		document.getElementById('lr_meeting').textContent = 'Meeting: ' + (m.meetingNumber || '-') + (m.password ? (' | Passcode: ' + m.password) : '');
		document.getElementById('lr_web').href = m.webJoinUrl || '#';
		document.getElementById('lr_mobile').href = m.mobileJoinUrl || '#';
		if (m.type !== 'zoom') {
			document.getElementById('lr_join_embed').disabled = true;
		}
	}).catch(function () {
		showMsg('Network error.');
	});
})();
</script>
