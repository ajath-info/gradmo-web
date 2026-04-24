<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Notifications</div>
	</div>


	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card">
					<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
						<select id="notifType" class="form-control" style="max-width:220px;">
							<option value="">All types</option>
							<option value="homeWork">Homework</option>
							<option value="liveClass">Live class</option>
							<option value="upcomingExam">Upcoming exam</option>
						</select>
						<button type="button" id="notifRefresh" class="btn btn-primary">Refresh</button>
					</div>
				</div>
				<div id="notifMsg" class="small text-muted"></div>
				<div id="notifList"></div>
			</div>
		</div>
	</div>
</div>

<script>
(function () {
	'use strict';
	var endpoint = <?php echo json_encode((string) (isset($notifications_api_url) ? $notifications_api_url : site_url('api/main/notifications-list'))); ?>;
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

	function renderItem(item) {
		var message = item.msg || '';
		var time = item.time || '';
		var type = item.notificationType || '';
		var url = item.url || '';
		var html = '' +
			'<div class="inst-batch-card">' +
				'<div class="inst-batch-logo"><i class="fas fa-bell"></i></div>' +
				'<div class="inst-batch-content">' +
					'<h4>' + esc(type || 'Notification') + '</h4>' +
					'<p class="inst-batch-desc">' + esc(message) + '</p>' +
					'<p class="inst-batch-meta">' + esc(time) + '</p>';
		if (url) {
			html += '<a class="btn btn-sm btn-outline-primary" href="' + esc(url) + '" target="_blank" rel="noopener">Open link</a>';
		}
		html += '</div></div>';
		return html;
	}

	function load() {
		setMsg('Loading notifications...', false);
		listEl.innerHTML = '';
		var payload = { page: 1, limit: 30 };
		if (typeEl.value) {
			payload.notification_type = typeEl.value;
		}
		fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify(payload)
		}).then(function (r) {
			return r.json();
		}).then(function (res) {
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
			for (var i = 0; i < rows.length; i++) {
				html += renderItem(rows[i]);
			}
			listEl.innerHTML = html;
			setMsg('', false);
		}).catch(function (err) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-danger">Could not fetch notifications.</div>';
			setMsg(err && err.message ? err.message : 'Request failed', true);
		});
	}

	document.getElementById('notifRefresh').addEventListener('click', load);
	load();
})();
</script>
