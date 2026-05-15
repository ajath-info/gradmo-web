<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="javascript:history.back()" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Live Classes</span>
	</div>
	
	<div class="inst-detail-container">
		<div id="lc_msg" class="inst-muted text-center py-3">Loading...</div>
		<div id="lc_list" class="inst-card-grid"></div>
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
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function joinHref(row) {
		if (row.isBatchZoom == 1 || row.liveClassId === 0 || row.liveClassId === '0') {
			return roomUrl + '?live_class_id=0&batch_id=' + encodeURIComponent(batchId);
		}
		return roomUrl + '?live_class_id=' + encodeURIComponent(row.liveClassId || 0);
	}
	function card(row) {
		var liveBadge = row.isLive ? '<span class="badge badge-success">Live</span>' : '<span class="badge badge-secondary">Ended</span>';
		return '<div class="inst-batch-card"><div class="inst-batch-content">' +
			'<h4>' + esc(row.subjectName || 'Live Class') + ' ' + liveBadge + '</h4>' +
			'<p class="inst-batch-meta">' + esc(row.teacherName || '') + ' | ' + esc(row.date || '') + ' ' + esc(row.startTime || '') + '</p>' +
			'<a class="btn btn-primary btn-sm" href="' + esc(joinHref(row)) + '">Join</a>' +
		'</div></div>';
	}
	if (batchId < 1) {
		msgEl.textContent = 'Invalid batch id.';
		return;
	}
	fetch(listUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
		body: JSON.stringify({ batch_id: batchId, page: 1, limit: 50 })
	}).then(function (r) { return r.json(); }).then(function (j) {
		if (!(j && (j.status === true || j.status === 'true'))) {
			msgEl.textContent = (j && (j.msg || j.message)) || 'Could not load live classes.';
			return;
		}
		var rows = (j.data && j.data.liveClasses) ? j.data.liveClasses : [];
		msgEl.textContent = rows.length ? '' : 'No live classes available.';
		listEl.innerHTML = rows.map(card).join('');
	}).catch(function () {
		msgEl.textContent = 'Network error.';
	});
})();
</script>
