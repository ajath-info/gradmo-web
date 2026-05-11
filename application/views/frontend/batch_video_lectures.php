<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<style>
.vl-shell { max-width: 1060px; margin: 0 auto; }
.vl-search-card { border-radius: 16px; box-shadow: 0 8px 28px rgba(30, 58, 138, 0.08); border: 1px solid #e7ecf7; }
.vl-search-wrap { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.vl-search-input { flex: 1 1 280px; min-height: 42px; border-radius: 10px; border: 1px solid #d7deed; padding: 0 12px; }
.vl-search-btn { min-height: 42px; border-radius: 10px; padding: 0 16px; font-weight: 600; }
.vl-msg { margin: 4px 2px 0; font-size: 13px; }
.vl-grid { display:grid; grid-template-columns: repeat(auto-fill,minmax(240px,1fr)); gap:14px; }
.vl-card { background:#fff; border:1px solid #e9eef8; border-radius:14px; box-shadow:0 7px 22px rgba(15, 23, 42, 0.07); overflow:hidden; transition: transform .14s ease, box-shadow .14s ease; }
.vl-card:hover { transform: translateY(-2px); box-shadow:0 12px 28px rgba(15, 23, 42, 0.11); }
.vl-thumb {
	position:relative;
	height:140px;
	background:linear-gradient(135deg,#1e3a8a,#2563eb);
	background-size:cover;
	background-position:center;
	cursor:pointer;
	overflow:hidden;
}
.vl-thumb--placeholder { background:linear-gradient(145deg,#0f172a,#334155); }
.vl-thumb video.vl-preview-video {
	position:absolute;
	inset:0;
	width:100%;
	height:100%;
	object-fit:cover;
	object-position:center;
	z-index:0;
	pointer-events:none;
	background:#0f172a;
}
.vl-thumb-overlay {
	position:absolute;
	inset:0;
	z-index:2;
	display:flex;
	align-items:center;
	justify-content:center;
	background:rgba(15,23,42,0.2);
	transition:background .18s ease;
}
.vl-card:hover .vl-thumb-overlay { background:rgba(15,23,42,.32); }
.vl-thumb-overlay span{
	width:52px;height:52px;border-radius:50%;
	background:rgba(255,255,255,.94);
	color:#1e3a8a;
	display:flex;
	align-items:center;
	justify-content:center;
	font-size:18px;
	box-shadow:0 4px 18px rgba(0,0,0,.22);
}
.vl-content { padding:12px 12px 14px; }
.vl-title { margin:0 0 4px; font-size:18px; font-weight:700; color:#0f172a; line-height:1.25; min-height:44px; }
.vl-meta { margin:0; color:#5b677d; font-size:12px; line-height:1.35; }
.vl-desc { margin:8px 0; color:#334155; font-size:13px; line-height:1.45; min-height:36px; }
.vl-date { margin:0 0 10px; color:#64748b; font-size:12px; }
@media (max-width: 640px) {
	.vl-grid { grid-template-columns: 1fr; }
	.vl-title { font-size:16px; min-height:0; }
}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Video Lectures</div>
	</div>

	<div class="inst-detail-container vl-shell">
		<div class="inst-detail-panel">
			<div class="inst-panel-stack">
				<div class="inst-detail-summary-card vl-search-card">
					<div class="vl-search-wrap">
						<input type="text" id="vlSearch" class="vl-search-input" placeholder="Search by title, topic, subject...">
						<button type="button" id="vlSearchBtn" class="btn btn-primary vl-search-btn"><i class="fas fa-search"></i> Search</button>
					</div>
				</div>
				<div id="vlMsg" class="small text-muted vl-msg"></div>
				<div id="vlList" class="vl-grid"></div>
			</div>
		</div>
	</div>
</div>
<div id="vlPlayerModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:9999;align-items:center;justify-content:center;padding:14px;">
	<div style="width:min(980px,96vw);background:#111;border-radius:14px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4);">
		<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:#1c1c1c;color:#fff;">
			<strong id="vlPlayerTitle" style="font-size:14px;">Video</strong>
			<button type="button" id="vlPlayerClose" class="btn btn-sm btn-light">Close</button>
		</div>
		<div id="vlPlayerBody" style="background:#000;min-height:220px;"></div>
	</div>
</div>

<script>
(function () {
	'use strict';
	var endpoint = <?php echo json_encode((string) (isset($video_list_api_url) ? $video_list_api_url : site_url('api/batch/video-lecture-list'))); ?>;
	var token = <?php echo json_encode((string) (isset($api_access_token) ? $api_access_token : '')); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var msgEl = document.getElementById('vlMsg');
	var listEl = document.getElementById('vlList');
	var searchEl = document.getElementById('vlSearch');
	var modalEl = document.getElementById('vlPlayerModal');
	var playerBodyEl = document.getElementById('vlPlayerBody');
	var playerTitleEl = document.getElementById('vlPlayerTitle');

	function esc(v) {
		return String(v == null ? '' : v).replace(/[&<>"']/g, function (m) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
		});
	}
	function setMsg(text, isError) {
		msgEl.className = isError ? 'small text-danger' : 'small text-muted';
		msgEl.textContent = text || '';
	}
	function youtubeThumbUrl(raw) {
		var u = String(raw || '');
		var m = u.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/i);
		return m && m[1] ? 'https://img.youtube.com/vi/' + m[1] + '/hqdefault.jpg' : '';
	}
	function isUploadedPath(u) {
		return /uploads[/\\]video[/\\]/i.test(String(u || ''));
	}
	function toEmbedUrl(raw) {
		var u = (raw || '').trim();
		if (!u) return '';
		var y = u.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/i);
		if (y && y[1]) return 'https://www.youtube.com/embed/' + y[1] + '?autoplay=1&rel=0';
		var v = u.match(/vimeo\.com\/(\d+)/i);
		if (v && v[1]) return 'https://player.vimeo.com/video/' + v[1] + '?autoplay=1';
		return '';
	}
	function isDirectVideoUrl(u) {
		return /\.(mp4|m4v|webm|ogg|mov)(\?|#|$)/i.test(String(u || ''));
	}
	function canUseInlineVideoThumb(u) {
		var s = String(u || '').trim();
		if (!s || youtubeThumbUrl(s)) return false;
		return isUploadedPath(s) || isDirectVideoUrl(s);
	}
	function setupVideoPreviewThumbnail(videoEl, srcUrl) {
		var clean = String(srcUrl || '').trim().split('#')[0];
		videoEl.muted = true;
		videoEl.defaultMuted = true;
		videoEl.playsInline = true;
		videoEl.setAttribute('playsinline', '');
		videoEl.setAttribute('muted', '');
		videoEl.preload = 'auto';
		videoEl.controls = false;
		videoEl.src = clean;
		var seekStarted = false;
		function seekToPosterFrame() {
			if (seekStarted) return;
			seekStarted = true;
			var seekT = 0.12;
			try {
				if (videoEl.duration && isFinite(videoEl.duration) && videoEl.duration > 0) {
					seekT = Math.min(1.8, Math.max(0.05, videoEl.duration * 0.04));
				}
			} catch (err) {}
			var finished = false;
			function finishPoster() {
				if (finished) return;
				finished = true;
				try { videoEl.pause(); } catch (e2) {}
			}
			videoEl.addEventListener('seeked', function onSk() {
				videoEl.removeEventListener('seeked', onSk);
				finishPoster();
			});
			try { videoEl.currentTime = seekT; } catch (e3) { finishPoster(); }
			window.setTimeout(finishPoster, 3500);
		}
		videoEl.addEventListener('loadeddata', seekToPosterFrame);
		videoEl.addEventListener('loadedmetadata', function () {
			if (videoEl.readyState >= 2) seekToPosterFrame();
		});
		videoEl.addEventListener('error', function () { videoEl.style.display = 'none'; });
		try { videoEl.load(); } catch (e4) {}
	}
	function closePlayer() {
		playerBodyEl.innerHTML = '';
		modalEl.style.display = 'none';
	}
	function openPlayer(title, url) {
		var cleanUrl = (url || '').trim();
		if (!cleanUrl) return;
		playerTitleEl.textContent = title || 'Video';
		var embed = toEmbedUrl(cleanUrl);
		var html = '';
		if (embed) {
			html = '<div style="position:relative;padding-top:56.25%;">' +
				'<iframe src="' + esc(embed) + '" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;"></iframe>' +
				'</div>';
		} else if (isDirectVideoUrl(cleanUrl)) {
			html = '<video controls autoplay playsinline style="display:block;width:100%;max-height:78vh;background:#000;">' +
				'<source src="' + esc(cleanUrl) + '">' +
				'</video>';
		} else {
			html = '<div style="padding:16px;color:#fff;">' +
				'<p>This URL may force download on server side. Open in new tab:</p>' +
				'<a href="' + esc(cleanUrl) + '" target="_blank" rel="noopener" class="btn btn-sm btn-primary">Open Video URL</a>' +
				'</div>';
		}
		playerBodyEl.innerHTML = html;
		modalEl.style.display = 'flex';
	}
	function buildCard(item) {
		var title = item.title || 'Video lecture';
		var meta = [item.subject || '', item.topic || ''].filter(Boolean).join(' | ');
		var date = item.addedAt || '';
		var desc = item.description || '';
		var url = item.url || '';

		var card = document.createElement('div');
		card.className = 'vl-card';

		var thumb = document.createElement('div');
		thumb.className = 'vl-thumb';
		var ytBg = youtubeThumbUrl(url);
		var useVid = canUseInlineVideoThumb(url);
		if (ytBg) {
			thumb.style.backgroundImage = 'url("' + ytBg.replace(/"/g, '') + '")';
		} else if (useVid) {
			var pv = document.createElement('video');
			pv.className = 'vl-preview-video';
			pv.setAttribute('aria-hidden', 'true');
			setupVideoPreviewThumbnail(pv, url);
			thumb.appendChild(pv);
		} else {
			thumb.classList.add('vl-thumb--placeholder');
		}
		var overlay = document.createElement('div');
		overlay.className = 'vl-thumb-overlay';
		overlay.innerHTML = '<span><i class="fas fa-play" aria-hidden="true"></i></span>';
		thumb.appendChild(overlay);

		function playThis() {
			if (url) openPlayer(title, url);
		}
		thumb.addEventListener('click', playThis);
		thumb.addEventListener('keydown', function (ev) {
			if (ev.key === 'Enter' || ev.key === ' ') {
				ev.preventDefault();
				playThis();
			}
		});
		thumb.tabIndex = 0;
		thumb.setAttribute('role', 'button');
		thumb.setAttribute('aria-label', 'Play ' + title);

		var body = document.createElement('div');
		body.className = 'vl-content';
		body.innerHTML =
			'<h4 class="vl-title">' + esc(title) + '</h4>' +
			'<p class="vl-meta">' + esc(meta || 'Video lecture') + '</p>' +
			'<p class="vl-desc">' + esc(desc || 'Tap the preview above to watch.') + '</p>' +
			'<p class="vl-date">' + esc(date) + '</p>';
		card.appendChild(thumb);
		card.appendChild(body);
		return card;
	}
	function loadVideos() {
		if (batchId < 1) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">Invalid batch id.</div>';
			return;
		}
		setMsg('Loading video lectures...', false);
		listEl.innerHTML = '';
		var payload = { batch_id: batchId, search: (searchEl.value || '').trim(), page: 1, limit: 100 };
		fetch(endpoint, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify(payload)
		}).then(function (r) { return r.json(); }).then(function (res) {
			var ok = res && (res.status === true || res.status === 'true');
			var rows = ok && res.data && Array.isArray(res.data.videoLectures) ? res.data.videoLectures : [];
			if (!ok) throw new Error((res && (res.msg || res.message)) || 'Unable to load videos');
			if (!rows.length) {
				listEl.innerHTML = '<div class="inst-detail-summary-card text-muted">No video lectures found for this batch.</div>';
				setMsg('', false);
				return;
			}
			for (var i = 0; i < rows.length; i++) {
				listEl.appendChild(buildCard(rows[i]));
			}
			setMsg('', false);
		}).catch(function (err) {
			listEl.innerHTML = '<div class="inst-detail-summary-card text-danger">Could not fetch video lectures.</div>';
			setMsg(err && err.message ? err.message : 'Request failed', true);
		});
	}
	document.getElementById('vlSearchBtn').addEventListener('click', loadVideos);
	document.getElementById('vlPlayerClose').addEventListener('click', closePlayer);
	modalEl.addEventListener('click', function (ev) {
		if (ev.target === modalEl) closePlayer();
	});
	loadVideos();
})();
</script>
