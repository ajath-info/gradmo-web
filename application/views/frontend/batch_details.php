<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo site_url('batch/list'); ?>" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Batch Details</span>
	</div>
	
	<div class="inst-detail-container">
		<div id="bd_msg" class="inst-muted text-center py-3">Loading...</div>
		<div id="bd_body" class="inst-detail-hidden">
			<div class="inst-detail-summary-card">
				<div class="inst-detail-summary-row">
					<img id="bd_logo" class="inst-detail-logo-lg" src="" alt="" style="display:none;">
					<div id="bd_logo_ph" class="inst-detail-logo-lg inst-avatar-placeholder">B</div>
					<div class="inst-detail-summary-main">
						<h2 id="bd_name" class="inst-detail-name"></h2>
						<p id="bd_schedule" class="batch-list-meta"></p>
						<p id="bd_dates" class="batch-list-dates"></p>
						<p id="bd_desc" class="inst-card-sub"></p>
					</div>
				</div>
			</div>
			<div class="inst-detail-panel">
				<div class="inst-panel-head"><h3>Modules</h3></div>
				<div id="bd_modules" class="inst-panel-stack"></div>
			</div>
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
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function modCard(title, sub) {
		return '<div class="inst-batch-card"><div class="inst-card-body"><p class="inst-card-title-sm">' + esc(title) + '</p><p class="inst-card-sub">' + esc(sub) + '</p></div></div>';
	}
	function modCardLink(title, sub, href) {
		return '<a class="inst-batch-card" style="text-decoration:none;" href="' + esc(href) + '"><div class="inst-card-body"><p class="inst-card-title-sm">' + esc(title) + '</p><p class="inst-card-sub">' + esc(sub) + '</p></div></a>';
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
			var b = j.data || {};
			document.getElementById('bd_msg').textContent = '';
			document.getElementById('bd_body').classList.remove('inst-detail-hidden');
			document.getElementById('bd_name').textContent = b.batchName || b.title || '';
			document.getElementById('bd_schedule').textContent = b.schedule || '';
			document.getElementById('bd_dates').textContent = [b.start_date || '', b.end_date || ''].filter(Boolean).join(' - ');
			document.getElementById('bd_desc').textContent = b.description || '';
			if (b.logo || b.batchImage) {
				document.getElementById('bd_logo').src = b.logo || b.batchImage;
				document.getElementById('bd_logo').style.display = 'block';
				document.getElementById('bd_logo_ph').style.display = 'none';
			}
			var m = b.modules || {};
			document.getElementById('bd_modules').innerHTML =
				modCardLink('Live Classes', (m.live_classes && m.live_classes.is_live) ? 'Live now' : 'Not live', liveClassesUrl + '?batch_id=' + encodeURIComponent(batchId)) +
				modCard('Video Lectures', (m.video_lectures && m.video_lectures.count != null) ? m.video_lectures.count : '0') +
				modCardLink('Library', (m.library && m.library.book_count != null) ? ('Books: ' + m.library.book_count) : 'No data', libraryPageUrl + '?batch_id=' + encodeURIComponent(batchId)) +
				modCard('Attendance', (m.attendance && m.attendance.marked != null) ? ('Marked: ' + m.attendance.marked) : 'No data') +
				modCard('Upcoming Exams', (m.upcoming_exams && m.upcoming_exams.count != null) ? m.upcoming_exams.count : '0') +
				modCard('Homework', (m.homework && m.homework.today_count != null) ? ('Today: ' + m.homework.today_count) : 'No data');
			var canEnroll = !!b.canEnroll;
			document.getElementById('bd_next_btn').textContent = canEnroll ? 'Enroll to Unlock' : 'Continue';
		}).catch(function () {
			document.getElementById('bd_msg').textContent = 'Network error.';
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('bd_next_btn').addEventListener('click', function () {
			window.location.href = paymentPlanUrl + '?batch_id=' + encodeURIComponent(batchId);
		});
		load();
	});
})();
</script>
