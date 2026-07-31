<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.rm-shell { max-width: 960px; margin: 0 auto; }
.rm-toolbar {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: center;
	margin-bottom: 14px;
}
.rm-search {
	flex: 1 1 220px;
	min-height: 42px;
	border-radius: 10px;
	border: 1px solid #d7deed;
	padding: 0 12px;
}
.rm-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
	gap: 14px;
}
.rm-card {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 14px;
	box-shadow: 0 7px 22px rgba(15, 23, 42, 0.07);
	padding: 14px 14px 16px;
}
.rm-card h4 {
	margin: 0 0 6px;
	font-size: 1rem;
	font-weight: 700;
	color: #0f172a;
	line-height: 1.35;
}
.rm-meta {
	margin: 0 0 10px;
	font-size: 0.86rem;
	color: #64748b;
	line-height: 1.4;
}
.rm-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}
.rm-pagination {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12px;
	flex-wrap: wrap;
	margin-top: 16px;
}
.rm-page-info { font-size: 13px; color: #64748b; min-width: 110px; text-align: center; }
#rmPlayerModal {
	display: none;
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.72);
	z-index: 9999;
	align-items: center;
	justify-content: center;
	padding: 14px;
}
#rmPlayerModal.rm-open { display: flex; }
.rm-player-box {
	width: min(980px, 96vw);
	background: #111;
	border-radius: 14px;
	overflow: hidden;
	box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}
.rm-player-head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px 12px;
	background: #1c1c1c;
	color: #fff;
}
.rm-player-body {
	background: #000;
	min-height: 280px;
}
.rm-player-body iframe,
.rm-player-body video {
	width: 100%;
	min-height: 280px;
	border: 0;
	display: block;
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="javascript:history.back()" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Recorded meetings</span>
	</div>
	<div class="inst-detail-container rm-shell">
		<div class="rm-toolbar">
			<input type="search" id="rmSearch" class="rm-search" placeholder="Search by title…" aria-label="Search recordings">
			<button type="button" id="rmSearchBtn" class="btn btn-primary btn-sm">Search</button>
			<?php if (!empty($is_teacher_or_institute)) { ?>
			<button type="button" id="rmSyncBtn" class="btn btn-outline-secondary btn-sm">Refresh from Zoom</button>
			<?php } ?>
		</div>
		<div id="rm_msg" class="inst-muted text-center py-3">Loading…</div>
		<div id="rm_list" class="rm-grid" role="list"></div>
		<div class="rm-pagination inst-detail-hidden" id="rm_pagination">
			<button type="button" id="rmPrev" class="btn btn-outline-primary btn-sm" disabled>Previous</button>
			<span id="rmPageInfo" class="rm-page-info"></span>
			<button type="button" id="rmNext" class="btn btn-outline-primary btn-sm" disabled>Next</button>
		</div>
	</div>
</div>
<div id="rmPlayerModal" role="dialog" aria-modal="true" aria-labelledby="rmPlayerTitle">
	<div class="rm-player-box">
		<div class="rm-player-head">
			<strong id="rmPlayerTitle">Recording</strong>
			<button type="button" id="rmPlayerClose" class="btn btn-sm btn-light">Close</button>
		</div>
		<div id="rmPlayerBody" class="rm-player-body"></div>
	</div>
</div>

<!-- Upload Recording Modal -->
<div id="rmUploadModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9998; align-items: center; justify-content: center;">
	<div style="background: #fff; border-radius: 14px; padding: 24px; max-width: 500px; width: 90%;">
		<h3 style="margin: 0 0 16px; font-size: 1.1rem;">Upload Recording</h3>
		<form id="rmUploadForm" enctype="multipart/form-data">
			<div style="margin-bottom: 16px;">
				<label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Recording Title (optional)</label>
				<input type="text" id="rmUploadTitle" placeholder="e.g., Class Recording - June 5" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
			</div>
			<div style="margin-bottom: 16px;">
				<label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Video File (MP4, MOV, AVI, MKV, WebM) *</label>
				<input type="file" id="rmUploadFile" accept="video/*" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
				<small style="color: #666; display: block; margin-top: 4px;">Max size: 2GB</small>
			</div>
			<div style="margin-bottom: 20px;">
				<label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem;">Description (optional)</label>
				<textarea id="rmUploadDesc" placeholder="Add notes about this recording..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; min-height: 80px; font-family: inherit;"></textarea>
			</div>
			<div style="display: flex; gap: 10px; justify-content: flex-end;">
				<button type="button" id="rmUploadCancel" class="btn btn-outline-secondary btn-sm">Cancel</button>
				<button type="submit" id="rmUploadSubmit" class="btn btn-success btn-sm">Upload</button>
			</div>
		</form>
	</div>
</div>

<script>
(function () {
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var listUrl = <?php echo json_encode((string) (isset($recorded_meeting_list_url) ? $recorded_meeting_list_url : site_url('api/batch/recorded-meeting-list'))); ?>;
	var syncUrl = <?php echo json_encode((string) (isset($recorded_meeting_sync_url) ? $recorded_meeting_sync_url : site_url('api/batch/recorded-meeting-sync'))); ?>;
	var streamBase = <?php echo json_encode(site_url('api/batch/recorded-meeting-stream')); ?>;
	var canSync = <?php echo !empty($is_teacher_or_institute) ? 'true' : 'false'; ?>;
	var currentPage = 1;
	var pageLimit = 12;
	var totalPages = 1;
	var totalRecords = 0;
	var searchTerm = '';

	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(s) {
		var d = document.createElement("div");
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function authHeaders() {
		return {
			'Content-Type': 'application/json',
			'Accept': 'application/json',
			'Authorization': 'Bearer ' + token
		};
	}
	function requestBody(extra) {
		var body = { batch_id: batchId, page: currentPage, limit: pageLimit, access_token: token };
		if (searchTerm) { body.search = searchTerm; }
		if (extra && typeof extra === 'object') {
			for (var k in extra) { if (Object.prototype.hasOwnProperty.call(extra, k)) { body[k] = extra[k]; } }
		}
		return body;
	}
	function parseJsonResponse(r) {
		return r.text().then(function (text) {
			var t = (text || '').trim();
			if (t.indexOf('<') === 0) {
				throw new Error('Server returned HTML instead of JSON. Check you are logged in and the API URL is correct.');
			}
			try { return JSON.parse(t); } catch (e) { throw new Error('Invalid JSON from server'); }
		});
	}
	function formatWhen(row) {
		var parts = [];
		if (row.recordingStart) { parts.push(String(row.recordingStart).replace('T', ' ').substring(0, 16)); }
		if (row.durationMinutes) { parts.push(row.durationMinutes + ' min'); }
		if (row.fileSizeLabel) { parts.push(row.fileSizeLabel); }
		return parts.join(' · ');
	}
	function card(row) {
		var canPlay = !!(row.canPlay === 1 || row.canPlay === '1' || row.streamUrl);
		var recId = parseInt(row.id || 0, 10) || 0;
		var meta = formatWhen(row);
		return '<article class="rm-card" role="listitem">' +
			'<h4>' + esc(row.topic || 'Recorded class') + '</h4>' +
			(meta ? '<p class="rm-meta">' + esc(meta) + '</p>' : '') +
			'<div class="rm-actions">' +
			(canPlay && recId > 0
				? '<button type="button" class="btn btn-primary btn-sm rm-play" data-id="' + esc(String(recId)) + '" data-title="' + esc(row.topic || 'Recording') + '"><i class="fas fa-play"></i> Watch</button>'
				: '<span class="inst-muted small">Processing…</span>') +
			'</div>' +
		'</article>';
	}
	function updatePagination() {
		var pag = document.getElementById('rm_pagination');
		if (totalRecords < 1) {
			pag.classList.add('inst-detail-hidden');
			return;
		}
		pag.classList.remove('inst-detail-hidden');
		document.getElementById('rmPageInfo').textContent = 'Page ' + currentPage + ' / ' + totalPages;
		document.getElementById('rmPrev').disabled = currentPage <= 1;
		document.getElementById('rmNext').disabled = currentPage >= totalPages;
	}
	function showMsg(t, isError) {
		var el = document.getElementById('rm_msg');
		el.textContent = t || '';
		el.className = 'text-center py-3 ' + (isError ? 'text-danger' : 'inst-muted');
	}
	function openPlayer(recId, title) {
		var modal = document.getElementById('rmPlayerModal');
		var body = document.getElementById('rmPlayerBody');
		document.getElementById('rmPlayerTitle').textContent = title || 'Recording';
		body.innerHTML = '';
		recId = parseInt(recId, 10) || 0;
		if (recId < 1 || !token) {
			body.innerHTML = '<div style="padding:24px;text-align:center;"><p style="margin:0;">Recording is not available for in-app playback.</p></div>';
			modal.classList.add('rm-open');
			return;
		}
		var url = streamBase + (streamBase.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(String(recId)) + '&access_token=' + encodeURIComponent(token);
		var v = document.createElement('video');
		v.controls = true;
		v.setAttribute('controlsList', 'nodownload');
		v.disablePictureInPicture = true;
		v.playsInline = true;
		v.autoplay = true;
		v.preload = 'metadata';
		v.src = url;
		v.style.width = '100%';
		v.style.minHeight = '360px';
		v.style.background = '#000';
		v.addEventListener('contextmenu', function (e) { e.preventDefault(); });
		v.addEventListener('error', function () {
			body.innerHTML = '<div style="padding:24px;text-align:center;"><p style="margin:0;">Could not play this recording right now. Try again in a few minutes.</p></div>';
		});
		body.appendChild(v);
		modal.classList.add('rm-open');
	}
	function closePlayer() {
		document.getElementById('rmPlayerModal').classList.remove('rm-open');
		document.getElementById('rmPlayerBody').innerHTML = '';
	}
	function loadList(opts) {
		opts = opts || {};
		if (batchId < 1) {
			showMsg('Invalid batch id.', true);
			return;
		}
		showMsg('Loading…', false);
		document.getElementById('rm_list').innerHTML = '';
		var body = requestBody(opts.sync ? { sync: 1 } : {});
		fetch(listUrl, { method: 'POST', headers: authHeaders(), body: JSON.stringify(body) })
			.then(parseJsonResponse)
			.then(function (j) {
				if (!ok(j.status)) {
					showMsg((j && (j.msg || j.message)) || 'Could not load recordings.', true);
					return;
				}
				var data = j.data || {};
				var rows = data.recordedMeetings || [];
				var p = data.pagination || {};
				totalRecords = parseInt(p.totalRecords || p.total || 0, 10) || 0;
				totalPages = parseInt(p.totalPages || 1, 10) || 1;
				currentPage = parseInt(p.page || currentPage, 10) || currentPage;
				if (data.syncError && rows.length === 0) {
					showMsg(data.syncError, true);
				} else {
					showMsg(rows.length ? '' : 'No recorded meetings for this batch yet. Recordings appear here after a Zoom class with cloud recording is completed.');
				}
				document.getElementById('rm_list').innerHTML = rows.map(card).join('');
				updatePagination();
			})
			.catch(function (e) {
				showMsg((e && e.message) ? e.message : 'Network error.', true);
			});
	}

	if (batchId < 1) {
		showMsg('Invalid batch id.', true);
		return;
	}

	document.getElementById('rm_list').addEventListener('click', function (ev) {
		var btn = ev.target.closest('.rm-play');
		if (!btn) { return; }
		openPlayer(
			btn.getAttribute('data-id') || '',
			btn.getAttribute('data-title') || 'Recording'
		);
	});
	document.getElementById('rmPlayerClose').addEventListener('click', closePlayer);
	document.getElementById('rmPlayerModal').addEventListener('click', function (ev) {
		if (ev.target === document.getElementById('rmPlayerModal')) { closePlayer(); }
	});
	document.getElementById('rmSearchBtn').addEventListener('click', function () {
		searchTerm = (document.getElementById('rmSearch').value || '').trim();
		currentPage = 1;
		loadList();
	});
	document.getElementById('rmSearch').addEventListener('keydown', function (ev) {
		if (ev.key === 'Enter') {
			searchTerm = (document.getElementById('rmSearch').value || '').trim();
			currentPage = 1;
			loadList();
		}
	});
	document.getElementById('rmPrev').addEventListener('click', function () {
		if (currentPage > 1) { currentPage -= 1; loadList(); }
	});
	document.getElementById('rmNext').addEventListener('click', function () {
		if (currentPage < totalPages) { currentPage += 1; loadList(); }
	});
	var syncBtn = document.getElementById('rmSyncBtn');
	if (syncBtn && canSync) {
		syncBtn.addEventListener('click', function () {
			syncBtn.disabled = true;
			syncBtn.textContent = 'Refreshing…';
			fetch(syncUrl, { method: 'POST', headers: authHeaders(), body: JSON.stringify({ batch_id: batchId, access_token: token }) })
				.then(parseJsonResponse)
				.then(function (j) {
					syncBtn.disabled = false;
					syncBtn.textContent = 'Refresh from Zoom';
					if (!ok(j.status) && typeof toastr !== 'undefined') {
						toastr.error((j && j.msg) || 'Sync failed');
					} else if (ok(j.status) && typeof toastr !== 'undefined') {
						toastr.success((j && j.msg) || 'Synced');
					}
					currentPage = 1;
					loadList({ sync: 1 });
				})
				.catch(function () {
					syncBtn.disabled = false;
					syncBtn.textContent = 'Refresh from Zoom';
					if (typeof toastr !== 'undefined') { toastr.error('Network error'); }
				});
		});
	}

	loadList({ sync: 1 });
})();
</script>
