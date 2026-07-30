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
				<button type="button" id="lr_zoom_record" class="btn btn-danger btn-sm">Start recording</button>
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
	var classStatusUrl = <?php echo json_encode((string) site_url('api/batch/class-status')); ?>;
	var endMeetingUrl = <?php echo json_encode((string) (isset($live_meeting_end_url) ? $live_meeting_end_url : site_url('api/batch/live-meeting-end'))); ?>;
	var recordingStartUrl = <?php echo json_encode((string) (isset($live_recording_start_url) ? $live_recording_start_url : site_url('api/batch/live-recording-start'))); ?>;
	var recordingStopUrl = <?php echo json_encode((string) (isset($live_recording_stop_url) ? $live_recording_stop_url : site_url('api/batch/live-recording-stop'))); ?>;
	var pageIsTeacherHost = <?php echo !empty($is_teacher_host) ? 'true' : 'false'; ?>;
	var currentMeeting = null;
	var zoomClient = null;
	var joinStarted = false;
	var zoomInitialized = false;
	var pollInterval = null;
	var lastClassStarted = false;
	var lastClassEnded = false;
	var recordingActive = false;

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
		if (card) {
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
				showAlert('Class ended. Recording is saving to Zoom cloud and will appear under Recorded meetings shortly.', false);
				setTimeout(function () {
					leaveClass();
				}, 2000);
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
				// Notify server that teacher has joined
				var notifyBody = { batch_id: batchId, action: 'host_joined' };
				if (liveClassId > 0) { notifyBody.live_class_id = liveClassId; }
				fetch(endMeetingUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
					body: JSON.stringify(notifyBody)
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

	fetchMeetingDetails().then(function (row) {
		var m = row.meeting || {};
		showMsg('');
		document.getElementById('lr_body').classList.remove('inst-detail-hidden');

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

		if (m.type !== 'zoom') {
			joinBtn.disabled = true;
			showAlert('This batch does not use Zoom.', true);
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
})();
</script>
