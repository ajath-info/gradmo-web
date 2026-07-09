<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
	/* Notifications: full-width rows (a vertical list), not a tiled grid. */
	#notifList.notif-list-rows { display: flex; flex-direction: column; gap: 12px; }
	#notifList.notif-list-rows .inst-batch-card { width: 100%; display: flex; align-items: flex-start; gap: 14px; position: relative; }
	#notifList.notif-list-rows .inst-batch-content { flex: 1 1 auto; min-width: 0; }
	#notifList.notif-list-rows .inst-batch-desc { white-space: normal; }
	/* Seen vs unseen background. */
	.notif-card.is-unread { background: #eaf2ff; border-left: 4px solid #3787FF; }
	.notif-card.is-read { background: #ffffff; opacity: .9; }
	.notif-actions { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; align-items: center; }
	.notif-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
	.notif-unread-dot { width: 9px; height: 9px; border-radius: 50%; background: #3787FF; display: inline-block; }
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Notifications</div>
	</div>


	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card">
					<div class="notif-toolbar">
						<select id="notifType" class="form-control" style="max-width:220px;">
							<option value="">All types</option>
							<option value="homeWork">Homework</option>
							<option value="liveClass">Live class</option>
							<option value="upcomingExam">Upcoming exam</option>
						</select>
						<button type="button" id="notifRefresh" class="btn btn-primary">Refresh</button>
						<!-- <button type="button" id="notifReadAll" class="btn btn-outline-primary">Mark all read</button>
						<button type="button" id="notifClearAll" class="btn btn-outline-danger">Clear all</button> -->
					</div>
				</div>
				<div id="notifMsg" class="small text-muted"></div>
				<div id="notifList" class="notif-list-rows"></div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	'use strict';
	var endpoint = <?php echo json_encode((string) (isset($notifications_api_url) ? $notifications_api_url : site_url('api/main/all_notifications-list'))); ?>;
	var readUrl = <?php echo json_encode((string) (isset($notifications_read_url) ? $notifications_read_url : site_url('api/main/notifications-read'))); ?>;
	var deleteUrl = <?php echo json_encode((string) (isset($notifications_delete_url) ? $notifications_delete_url : site_url('api/main/notifications-delete'))); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var msgEl = document.getElementById('notifMsg');
	var listEl = document.getElementById('notifList');
	var typeEl = document.getElementById('notifType');

	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
		});
	}
	function setMsg(text, isError) {
		msgEl.className = isError ? 'small text-danger' : 'small text-muted';
		msgEl.textContent = text || '';
	}
	function api(url, body) {
		return fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(body || {})
		}).then(function (r) { return r.json(); });
	}
	function isRead(item) {
		var r = item.read;
		return r === 1 || r === '1' || r === true;
	}
	function renderItem(item) {
		var message = item.msg || '';
		var time = item.time || '';
		var type = item.notificationType || item.title || '';
		var url = item.url || '';
		var did = item.detailId != null ? item.detailId : '';
		var read = isRead(item);
		var html = '' +
			'<div class="inst-batch-card notif-card ' + (read ? 'is-read' : 'is-unread') + '" data-detail="' + esc(did) + '" data-read="' + (read ? '1' : '0') + '">' +
				'<div class="inst-batch-logo"><i class="fas fa-bell"></i></div>' +
				'<div class="inst-batch-content">' +
					'<h4>' + (read ? '' : '<span class="notif-unread-dot"></span> ') + esc(type || 'Notification') + '</h4>' +
					'<p class="inst-batch-desc">' + esc(message) + '</p>' +
					'<p class="inst-batch-meta">' + esc(time) + '</p>' +
					'<div class="notif-actions">';
		if (url) {
			html += '<a class="btn btn-sm btn-outline-primary notif-open" href="' + esc(url) + '" target="_blank" rel="noopener">Open link</a>';
		}
		if (!read) {
			html += '<button type="button" class="btn btn-sm btn-outline-secondary notif-read-one">Mark read</button>';
		}
		html += '</div></div></div>';
		return html;
	}

	function markCardRead(card) {
		if (!card || card.getAttribute('data-read') === '1') { return; }
		var did = card.getAttribute('data-detail');
		card.setAttribute('data-read', '1');
		card.classList.remove('is-unread');
		card.classList.add('is-read');
		var dot = card.querySelector('.notif-unread-dot');
		if (dot) { dot.remove(); }
		var btn = card.querySelector('.notif-read-one');
		if (btn) { btn.remove(); }
		if (did !== '') { api(readUrl, { detail_id: did }); }
	}

	function load() {
		setMsg('Loading notifications...', false);
		listEl.innerHTML = '';
		var payload = { page: 1, limit: 50 };
		if (typeEl.value) { payload.notification_type = typeEl.value; }
		api(endpoint, payload).then(function (res) {
			if (!res || !(res.status === true || res.status === 'true')) {
				throw new Error((res && (res.msg || res.message)) || 'Unable to load notifications');
			}
			var rows = Array.isArray(res.notifications) ? res.notifications : [];
			if (!rows.length) {
				listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">No notifications found.</div>';
				setMsg('', false);
				return;
			}
			var html = '';
			for (var i = 0; i < rows.length; i++) { html += renderItem(rows[i]); }
			listEl.innerHTML = html;
			setMsg('', false);
		}).catch(function (err) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-danger">Could not fetch notifications.</div>';
			setMsg(err && err.message ? err.message : 'Request failed', true);
		});
	}

	// Delegated clicks: open link (also marks read), mark-read, delete.
	listEl.addEventListener('click', function (e) {
		var card = e.target.closest ? e.target.closest('.notif-card') : null;
		if (!card) { return; }
		if (e.target.closest('.notif-open')) { markCardRead(card); return; }
		if (e.target.closest('.notif-read-one')) { markCardRead(card); return; }
		if (e.target.closest('.notif-delete-one')) {
			var did = card.getAttribute('data-detail');
			if (did !== '') { api(deleteUrl, { detail_id: did }); }
			card.parentNode.removeChild(card);
			if (!listEl.querySelector('.notif-card')) {
				listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">No notifications found.</div>';
			}
			return;
		}
		// Clicking the card body (not a button) also marks it read.
		markCardRead(card);
	});

	// Buttons are optional (some may be hidden in the markup) — guard each before binding.
	var refreshBtn = document.getElementById('notifRefresh');
	if (refreshBtn) { refreshBtn.addEventListener('click', load); }

	var readAllBtn = document.getElementById('notifReadAll');
	if (readAllBtn) {
		readAllBtn.addEventListener('click', function () {
			api(readUrl, {}).then(function () { load(); });
		});
	}

	var clearAllBtn = document.getElementById('notifClearAll');
	if (clearAllBtn) {
		clearAllBtn.addEventListener('click', function () {
			if (!window.confirm('Clear all notifications?')) { return; }
			api(deleteUrl, {}).then(function () { load(); });
		});
	}

	load();
})();
</script>
