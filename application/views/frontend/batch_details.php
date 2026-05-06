<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
#bd_body.bd-locked .bd-lockable {
	filter: blur(4px);
	opacity: 0.75;
	pointer-events: none;
	user-select: none;
}
#bd_lock_hint {
	margin-top: 12px;
	font-size: 0.9rem;
	text-align: center;
	color: #4d4a81;
	font-weight: 600;
}
.bd-summary-mini {
	margin: 4px 0 0;
	font-size: 1.02rem;
	font-weight: 600;
	color: #2f2f2f;
}
.bd-mod-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 16px;
}
.bd-mod-tile {
	display: block;
	text-decoration: none !important;
	background: #fff;
	border-radius: 14px;
	padding: 16px 12px 14px;
	min-height: 132px;
	box-shadow: 0 7px 20px rgba(17, 24, 39, 0.08);
	border: 1px solid #edf0f5;
	text-align: center;
}
.bd-mod-icon {
	width: 62px;
	height: 62px;
	margin: 0 auto 10px;
	border-radius: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 26px;
	background: #eff3ff;
	color: #3f58cf;
}
.bd-mod-title {
	display: block;
	font-weight: 700;
	font-size: 1rem;
	line-height: 1.3;
	color: #121212;
}
.bd-mod-sub {
	display: block;
	margin-top: 5px;
	font-size: .86rem;
	color: #606774;
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo site_url('batch/list'); ?>" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Batch Details</span>
	</div>
	
	<div class="inst-detail-container">
		<div id="bd_msg" class="inst-muted text-center py-3">Loading...</div>
		<div id="bd_body" class="inst-detail-hidden">
			<div class="inst-detail-summary-card bd-lockable">
				<div class="inst-detail-summary-row">
					<img id="bd_logo" class="inst-detail-logo-lg" src="" alt="" style="display:none;">
					<div id="bd_logo_ph" class="inst-detail-logo-lg inst-avatar-placeholder">B</div>
					<div class="inst-detail-summary-main">
						<h2 id="bd_name" class="inst-detail-name"></h2>
						<p id="bd_teacher" class="bd-summary-mini"></p>
						<p id="bd_schedule" class="batch-list-meta"></p>
						<p id="bd_dates" class="batch-list-dates"></p>
						<p id="bd_desc" class="inst-card-sub"></p>
					</div>
				</div>
			</div>
			<div class="inst-detail-panel bd-lockable">
				<div class="inst-panel-head"><h3>Modules</h3></div>
				<div id="bd_modules" class="inst-panel-stack"></div>
			</div>
			<div id="bd_lock_hint" class="inst-detail-hidden">Preview is locked. Complete payment to unlock batch details.</div>
			<button type="button" id="bd_next_btn" class="inst-submit-full">Continue</button>
		</div>
	</div>
</div>
<script>
(function () {
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var accessToken = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var dataUrl = <?php echo json_encode(isset($batch_details_data_url) ? $batch_details_data_url : ''); ?>;
	var paymentPlanUrl = <?php echo json_encode(isset($batch_payment_plan_url) ? $batch_payment_plan_url : ''); ?>;
	var liveClassesUrl = <?php echo json_encode(site_url('batch/live-classes')); ?>;
	var libraryPageUrl = <?php echo json_encode(site_url('library')); ?>;
	var canEnrollForBatch = true;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function truthy(v) { return v === true || v === 1 || v === '1' || v === 'true'; }
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function modTile(title, sub, icon, href) {
		var open = href ? '<a class="bd-mod-tile" href="' + esc(href) + '">' : '<div class="bd-mod-tile">';
		var close = href ? '</a>' : '</div>';
		return open +
			'<span class="bd-mod-icon"><i class="' + esc(icon) + '"></i></span>' +
			'<span class="bd-mod-title">' + esc(title) + '</span>' +
			'<span class="bd-mod-sub">' + esc(sub) + '</span>' +
			close;
	}
	function load() {
		if (batchId < 1) {
			document.getElementById('bd_msg').textContent = 'Invalid batch id.';
			return;
		}
		var body = { batch_id: batchId };
		if (accessToken) { body.access_token = accessToken; }
		var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
		if (accessToken) { headers.Authorization = 'Bearer ' + accessToken; }
		fetch(dataUrl, {
			method: 'POST',
			headers: headers,
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!ok(j.status)) {
				document.getElementById('bd_msg').textContent = j.msg || 'Could not load batch details.';
				return;
			}
			var b = {};
			if (j.data && typeof j.data === 'object') {
				b = j.data;
			} else if (j.batch_details && typeof j.batch_details === 'object') {
				b = j.batch_details;
			}
			document.getElementById('bd_msg').textContent = '';
			document.getElementById('bd_body').classList.remove('inst-detail-hidden');
			document.getElementById('bd_name').textContent = b.batchName || b.title || '';
			document.getElementById('bd_teacher').textContent = b.instructor || b.teacherName || '';
			document.getElementById('bd_schedule').textContent = b.schedule || '';
			document.getElementById('bd_dates').textContent = [b.start_date || '', b.end_date || ''].filter(Boolean).join(' - ');
			document.getElementById('bd_desc').textContent = b.description || '';
			if (b.logo || b.batchImage) {
				document.getElementById('bd_logo').src = b.logo || b.batchImage;
				document.getElementById('bd_logo').style.display = 'block';
				document.getElementById('bd_logo_ph').style.display = 'none';
			}
			var m = b.modules || {};
			document.getElementById('bd_modules').innerHTML = '<div class="bd-mod-grid">' +
				modTile('Live classes', (m.live_classes && m.live_classes.is_live) ? 'Live now' : 'Tap to open', 'fas fa-broadcast-tower', liveClassesUrl + '?batch_id=' + encodeURIComponent(batchId)) +
				modTile('Video Lectures', (m.video_lectures && m.video_lectures.count != null) ? ('Videos: ' + m.video_lectures.count) : 'No data', 'fas fa-play-circle', null) +
				modTile('Library', (m.library && m.library.book_count != null) ? ('Books: ' + m.library.book_count) : 'Tap to open', 'fas fa-book', libraryPageUrl + '?batch_id=' + encodeURIComponent(batchId)) +
				modTile('Attendance', (m.attendance && m.attendance.marked != null) ? ('Marked: ' + m.attendance.marked) : 'No data', 'fas fa-clipboard-check', null) +
				modTile('Exams', (m.upcoming_exams && m.upcoming_exams.count != null) ? ('Upcoming: ' + m.upcoming_exams.count) : 'No data', 'fas fa-file-alt', null) +
				modTile('Homework', (m.homework && m.homework.today_count != null) ? ('Today: ' + m.homework.today_count) : 'No data', 'fas fa-pencil-alt', null) +
			'</div>';
			var canEnroll = truthy(b.canEnroll);
			canEnrollForBatch = canEnroll;
			document.getElementById('bd_next_btn').textContent = canEnroll ? 'Pay Now' : 'Continue';
			document.getElementById('bd_body').classList.toggle('bd-locked', canEnroll);
			document.getElementById('bd_lock_hint').classList.toggle('inst-detail-hidden', !canEnroll);
		}).catch(function () {
			document.getElementById('bd_msg').textContent = 'Network error.';
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('bd_next_btn').addEventListener('click', function () {
			if (canEnrollForBatch) {
				window.location.href = paymentPlanUrl + '?batch_id=' + encodeURIComponent(batchId);
				return;
			}
			window.location.href = liveClassesUrl + '?batch_id=' + encodeURIComponent(batchId);
		});
		load();
	});
})();
</script>
