<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=9">
<style>
.tv-page { max-width: 1120px; margin: 0 auto; }
.tv-form-card {
	background: #fff;
	border: 1px solid #e9eef8;
	border-radius: 16px;
	padding: 18px 18px 20px;
	box-shadow: 0 8px 28px rgba(30, 58, 138, 0.06);
	margin-bottom: 28px;
}
.tv-form-card h3 { margin: 0 0 6px; font-size: 1.1rem; font-weight: 700; color: #0f172a; }
.tv-form-card .inst-panel-intro { margin: 0 0 16px; color: #64748b; font-size: 0.9rem; line-height: 1.45; }
.tv-recorded-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	margin-bottom: 16px;
	padding: 0 2px;
}
.tv-recorded-head h2 {
	margin: 0;
	font-size: 1.15rem;
	font-weight: 700;
	color: #334155;
	letter-spacing: -0.02em;
}
.tv-recorded-head a {
	font-size: 0.78rem;
	font-weight: 700;
	letter-spacing: 0.06em;
	color: #2563eb;
	text-decoration: none;
}
.tv-recorded-head a:hover { text-decoration: underline; }
.tv-recorded-scroll {
	display: flex;
	flex-wrap: nowrap;
	gap: 20px;
	overflow-x: auto;
	padding: 4px 2px 18px;
	-webkit-overflow-scrolling: touch;
	scroll-snap-type: x proximity;
}
.tv-recorded-scroll::-webkit-scrollbar { height: 6px; }
.tv-recorded-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.tv-vcard {
	flex: 0 0 260px;
	width: 260px;
	max-width: 82vw;
	scroll-snap-align: start;
	background: #fff;
	border: 1px solid #e8ecf4;
	border-radius: 16px;
	overflow: hidden;
	box-shadow: 0 6px 22px rgba(15, 23, 42, 0.06);
	transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.tv-vcard:hover {
	box-shadow: 0 12px 32px rgba(15, 23, 42, 0.1);
	transform: translateY(-2px);
}
.tv-vcard-thumb {
	position: relative;
	height: 148px;
	background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #64748b 100%);
	background-size: cover;
	background-position: center;
	cursor: pointer;
	overflow: hidden;
}
.tv-vcard-thumb video.tv-vcard-preview-video {
	position: absolute;
	inset: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center;
	z-index: 0;
	pointer-events: none;
	background: #0f172a;
}
.tv-vcard-thumb--file {
	background: linear-gradient(145deg, #0f172a, #334155);
}
.tv-vcard-play {
	position: absolute;
	inset: 0;
	z-index: 2;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(15, 23, 42, 0.22);
	transition: background 0.2s ease;
}
.tv-vcard:hover .tv-vcard-play { background: rgba(15, 23, 42, 0.35); }
.tv-vcard-play span {
	width: 56px;
	height: 56px;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.92);
	color: #1e3a8a;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}
.tv-vcard-body { padding: 14px 14px 16px; }
.tv-vcard-title {
	margin: 0 0 10px;
	font-size: 0.98rem;
	font-weight: 700;
	color: #0f172a;
	line-height: 1.35;
	min-height: 2.7em;
	display: -webkit-box;
	line-clamp: 2;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
.tv-vcard-meta {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 10px;
	font-size: 0.8rem;
	color: #64748b;
}
.tv-vcard-meta-left { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tv-vcard-meta-right {
	flex-shrink: 0;
	display: inline-flex;
	align-items: center;
	gap: 5px;
	color: #64748b;
}
.tv-vcard-actions {
	display: flex;
	justify-content: flex-end;
	margin-top: 10px;
	padding-top: 10px;
	border-top: 1px solid #f1f5f9;
}
.tv-vcard-actions .btn { font-size: 0.75rem; padding: 4px 10px; }
.tv-player-modal {
	display: none;
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.75);
	z-index: 10050;
	align-items: center;
	justify-content: center;
	padding: 14px;
}
.tv-player-modal.is-open { display: flex; }
.tv-player-box {
	width: min(960px, 96vw);
	background: #111;
	border-radius: 14px;
	overflow: hidden;
	box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
}
.tv-player-head {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px 14px;
	background: #1a1a1a;
	color: #fff;
}
.tv-player-head strong { font-size: 14px; font-weight: 600; }
.tv-player-body { background: #000; min-height: 200px; }
.inst-upload-progress{margin:10px 0 0;padding:10px 12px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff}
.inst-upload-progress.is-hidden{display:none}
.inst-upload-progress-bar{width:100%;height:10px;border-radius:999px;background:#e5edf8;overflow:hidden}
.inst-upload-progress-fill{display:block;height:100%;width:0;background:linear-gradient(90deg,#2563eb,#38bdf8);transition:width .2s ease}
.inst-upload-progress-text{margin-top:8px;font-size:.84rem;font-weight:600;color:#334155}
@media (max-width: 576px) {
	.tv-vcard { flex-basis: 240px; width: 240px; }
	.tv-vcard-thumb { height: 132px; }
}
</style>

<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a href="javascript:history.back()" class="inst-detail-mobile-back" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<div class="inst-detail-mobile-title">Recorded Lectures</div>
	</div>

	<div class="inst-detail-container tv-page">
		<div class="inst-detail-panel tv-form-card">
			<h3>Add video lecture</h3>
			<p class="inst-panel-intro">Paste a <strong>YouTube</strong> link or upload a file — learners see the same card layout on their batch page.</p>
			<div class="inst-list-filter-bar">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item"><label>Title</label><input type="text" id="tv_title" class="edu_form_field" placeholder="e.g. Customer Experience: Journey Mapping"></div>
					<div class="inst-list-filter-item"><label>Topic</label><input type="text" id="tv_topic" class="edu_form_field" placeholder="Optional topic"></div>
					<div class="inst-list-filter-item"><label>Subject</label><select id="tv_subject" class="edu_form_field"><option value="">Select subject</option></select></div>
					<div class="inst-list-filter-item"><label>Video URL</label><input type="text" id="tv_url" class="edu_form_field" placeholder="https://www.youtube.com/watch?v=…"></div>
					<div class="inst-list-filter-item"><label>Video file (optional)</label><input type="file" id="tv_file" class="edu_form_field" accept="video/*,.mp4,.webm,.mov,.m4v"></div>
				</div>
				<div class="inst-list-filter-actions"><button type="button" class="btn btn-primary" id="tv_add">Save video</button></div>
			</div>
			<div id="tv_quota" class="inst-muted small px-2 mb-0 mt-2" style="color:#475569;font-weight:600;"></div>
			<div id="tv_msg" class="inst-muted small px-2 mb-0 mt-2"></div>
			<div id="tv_progress" class="inst-upload-progress is-hidden" aria-live="polite">
				<div class="inst-upload-progress-bar"><span id="tv_progress_fill" class="inst-upload-progress-fill"></span></div>
				<div id="tv_progress_text" class="inst-upload-progress-text">Preparing upload...</div>
			</div>
		</div>

		<section class="tv-recorded-block" aria-labelledby="tv-recorded-title">
			<div class="tv-recorded-head">
				<h2 id="tv-recorded-title">Recorded Lectures</h2>
				<?php
				$tv_batch_id = (int) (isset($batch_id) ? $batch_id : 0);
				$tv_view_all = isset($student_video_lectures_url) ? (string) $student_video_lectures_url : site_url('batch/video-lectures?batch_id=' . $tv_batch_id);
				if ($tv_batch_id > 0) {
					?>
				<a href="<?php echo html_escape($tv_view_all); ?>">VIEW ALL</a>
				<?php } else { ?>
				<span class="small text-muted">Select a batch</span>
				<?php } ?>
			</div>
			<div id="tv_list" class="tv-recorded-scroll" role="list"></div>
		</section>
	</div>
</div>

<div id="tvPlayerModal" class="tv-player-modal" role="dialog" aria-modal="true" aria-labelledby="tvPlayerTitleLabel">
	<div class="tv-player-box">
		<div class="tv-player-head">
			<strong id="tvPlayerTitleLabel">Video</strong>
			<button type="button" class="btn btn-sm btn-light" id="tvPlayerClose">Close</button>
		</div>
		<div id="tvPlayerBody" class="tv-player-body"></div>
	</div>
</div>

<script>
(function () {
	'use strict';
	var token = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var listUrl = <?php echo json_encode(isset($video_list_api_url) ? $video_list_api_url : ''); ?>;
	var addUrl = <?php echo json_encode(isset($video_add_api_url) ? $video_add_api_url : ''); ?>;
	var delUrl = <?php echo json_encode(isset($video_delete_api_url) ? $video_delete_api_url : ''); ?>;
	var subjectsUrl = <?php echo json_encode(site_url('api/batch/batch-subjects')); ?>;
	var addBtn = null;
	var addBtnDefaultText = '';
	var addInFlight = false;
	var progressWrap = null;
	var progressFill = null;
	var progressText = null;

	var quotaLimitSeconds = 330 * 3600;
	var quotaUsedSeconds = 0;

	var modalEl = document.getElementById('tvPlayerModal');
	var playerBodyEl = document.getElementById('tvPlayerBody');
	var playerTitleEl = document.getElementById('tvPlayerTitleLabel');

	function fmtHM(seconds) {
		var s = Math.max(0, Math.round(seconds || 0));
		var h = Math.floor(s / 3600);
		var m = Math.floor((s % 3600) / 60);
		return h + ' h ' + m + ' m';
	}
	function renderQuota() {
		var el = document.getElementById('tv_quota');
		if (!el) return;
		var remaining = Math.max(0, quotaLimitSeconds - quotaUsedSeconds);
		el.textContent = 'Yearly usage: ' + fmtHM(quotaUsedSeconds) + ' / ' + fmtHM(quotaLimitSeconds)
			+ ' (' + fmtHM(remaining) + ' remaining)';
		el.style.color = remaining <= 0 ? '#dc2626' : '#475569';
	}
	var MAX_VIDEO_SECONDS = 86400; // 24h sanity ceiling for a single lecture
	function readVideoDuration(file) {
		return new Promise(function (resolve) {
			try {
				var v = document.createElement('video');
				v.preload = 'metadata';
				var done = false;
				function finish(sec) {
					if (done) return;
					done = true;
					try { window.URL.revokeObjectURL(v.src); } catch (e) {}
					var d = isFinite(sec) && sec > 0 ? Math.round(sec) : 0;
					if (d > MAX_VIDEO_SECONDS) d = 0; // implausible -> treat as unmeasured
					resolve(d);
				}
				v.onloadedmetadata = function () {
					// Some MP4/WebM files report Infinity or a bogus value until we seek to the end.
					if (v.duration === Infinity || isNaN(v.duration) || v.duration > MAX_VIDEO_SECONDS) {
						v.ontimeupdate = function () {
							v.ontimeupdate = null;
							finish(v.duration);
						};
						try { v.currentTime = 1e101; } catch (e2) { finish(0); }
					} else {
						finish(v.duration);
					}
				};
				v.onerror = function () { finish(0); };
				window.setTimeout(function () { finish(v.duration); }, 8000); // safety timeout
				v.src = window.URL.createObjectURL(file);
			} catch (err) { resolve(0); }
		});
	}

	function esc(v) {
		var d = document.createElement('div');
		d.textContent = v == null ? '' : String(v);
		return d.innerHTML;
	}
	function msg(t, err) {
		var x = document.getElementById('tv_msg');
		x.className = err ? 'small text-danger px-2 mb-0 mt-2' : 'small text-success px-2 mb-0 mt-2';
		x.textContent = t || '';
	}
	function setLoader(show) {
		var nodes = document.querySelectorAll('.edu_preloader');
		Array.prototype.forEach.call(nodes, function (el) {
			el.style.backgroundColor = 'rgba(255,255,255,0.80)';
			el.style.display = show ? 'block' : 'none';
		});
	}
	function setProgress(percent, text) {
		if (!progressWrap || !progressFill || !progressText) return;
		progressWrap.classList.remove('is-hidden');
		if (percent != null && !isNaN(percent)) {
			progressFill.style.width = Math.max(0, Math.min(100, percent)) + '%';
		}
		progressText.textContent = text || 'Uploading...';
	}
	function resetProgress() {
		if (!progressWrap || !progressFill || !progressText) return;
		progressWrap.classList.add('is-hidden');
		progressFill.style.width = '0%';
		progressText.textContent = 'Preparing upload...';
	}
	function setAddBusy(busy) {
		if (!addBtn) return;
		addInFlight = !!busy;
		addBtn.disabled = !!busy;
		addBtn.textContent = busy ? 'Saving...' : addBtnDefaultText;
		setLoader(!!busy);
		if (busy) {
			setProgress(0, 'Preparing upload...');
		} else {
			resetProgress();
		}
	}
	function uploadFormData(url, fd) {
		return new Promise(function (resolve, reject) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', url, true);
			xhr.setRequestHeader('Authorization', 'Bearer ' + token);
			xhr.upload.addEventListener('progress', function (ev) {
				if (ev.lengthComputable) {
					var percent = Math.round((ev.loaded / ev.total) * 100);
					setProgress(percent, percent >= 100 ? 'Upload complete, processing on server...' : ('Uploading... ' + percent + '%'));
				} else {
					setProgress(null, 'Uploading...');
				}
			});
			xhr.upload.addEventListener('load', function () {
				setProgress(100, 'Upload complete, processing on server...');
			});
			xhr.onload = function () {
				var body = xhr.responseText || '{}';
				try {
					resolve(JSON.parse(body));
				} catch (err) {
					reject(err);
				}
			};
			xhr.onerror = function () { reject(new Error('Network error')); };
			xhr.onabort = function () { reject(new Error('Upload aborted')); };
			xhr.send(fd);
		});
	}
	function youtubeId(raw) {
		var u = String(raw || '');
		var m = u.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/i);
		return m && m[1] ? m[1] : '';
	}
	function isUploadedFileUrl(u) {
		return /uploads[/\\]video[/\\]/i.test(String(u || ''));
	}
	function canUseInlineVideoThumb(u) {
		var s = String(u || '').trim();
		if (!s || youtubeId(s)) return false;
		return isUploadedFileUrl(s) || isDirectVideoUrl(s);
	}
	function setupVideoPreviewThumbnail(videoEl, srcUrl) {
		var clean = String(srcUrl || '').trim();
		videoEl.muted = true;
		videoEl.defaultMuted = true;
		videoEl.playsInline = true;
		videoEl.setAttribute('playsinline', '');
		videoEl.setAttribute('muted', '');
		videoEl.preload = 'auto';
		videoEl.controls = false;
		videoEl.src = clean.split('#')[0];
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
			var onSeeked = function () {
				videoEl.removeEventListener('seeked', onSeeked);
				finishPoster();
			};
			videoEl.addEventListener('seeked', onSeeked);
			try {
				videoEl.currentTime = seekT;
			} catch (e3) {
				finishPoster();
			}
			window.setTimeout(finishPoster, 3500);
		}
		videoEl.addEventListener('loadeddata', seekToPosterFrame);
		videoEl.addEventListener('loadedmetadata', function () {
			if (videoEl.readyState >= 2) seekToPosterFrame();
		});
		videoEl.addEventListener('error', function () {
			videoEl.style.display = 'none';
		});
		try { videoEl.load(); } catch (e4) {}
	}
	function thumbUrlForItem(url) {
		var id = youtubeId(url);
		if (id) {
			return 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg';
		}
		return '';
	}
	function formatAddedAt(s) {
		if (!s) return '—';
		var d = new Date(String(s).replace(' ', 'T'));
		if (isNaN(d.getTime())) return String(s).slice(0, 10);
		return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
	}
	function metaLeftLabel(item) {
		var u = item.url || '';
		if (youtubeId(u)) return 'YouTube';
		if (isUploadedFileUrl(u) || /\.(mp4|webm|m4v|mov|ogg)(\?|#|$)/i.test(u)) return 'Uploaded';
		return item.topic || item.subject || 'Video';
	}
	function metaRightLabel(item) {
		return formatAddedAt(item.addedAt);
	}
	function toEmbedUrl(raw) {
		var u = String(raw || '').trim();
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
	function closePlayer() {
		playerBodyEl.innerHTML = '';
		modalEl.classList.remove('is-open');
		document.body.style.overflow = '';
	}
	function openPlayer(title, url) {
		var cleanUrl = String(url || '').trim();
		if (!cleanUrl) return;
		playerTitleEl.textContent = title || 'Video';
		var embed = toEmbedUrl(cleanUrl);
		var html = '';
		if (embed) {
			html = '<div style="position:relative;padding-top:56.25%;">' +
				'<iframe src="' + esc(embed) + '" title="' + esc(title) + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;"></iframe></div>';
		} else if (isDirectVideoUrl(cleanUrl)) {
			html = '<video controls autoplay playsinline style="display:block;width:100%;max-height:78vh;background:#000;">' +
				'<source src="' + esc(cleanUrl) + '"></video>';
		} else {
			html = '<div style="padding:18px;color:#fff;">' +
				'<p class="mb-2">Open this link in a new tab if playback is not available here.</p>' +
				'<a href="' + esc(cleanUrl) + '" target="_blank" rel="noopener" class="btn btn-sm btn-primary">Open video</a></div>';
		}
		playerBodyEl.innerHTML = html;
		modalEl.classList.add('is-open');
		document.body.style.overflow = 'hidden';
	}

	function buildCard(item) {
		var title = item.title || 'Video lecture';
		var url = item.url || '';
		var thumb = thumbUrlForItem(url);
		var useVid = canUseInlineVideoThumb(url);
		var wrap = document.createElement('article');
		wrap.className = 'tv-vcard';
		wrap.setAttribute('role', 'listitem');

		var thumbEl = document.createElement('div');
		thumbEl.className = 'tv-vcard-thumb' + ((thumb || useVid) ? '' : ' tv-vcard-thumb--file');
		if (thumb) {
			thumbEl.style.backgroundImage = 'url("' + thumb.replace(/"/g, '') + '")';
		} else if (useVid) {
			var prv = document.createElement('video');
			prv.className = 'tv-vcard-preview-video';
			prv.setAttribute('aria-hidden', 'true');
			setupVideoPreviewThumbnail(prv, url);
			thumbEl.appendChild(prv);
		}
		thumbEl.setAttribute('role', 'button');
		thumbEl.setAttribute('tabindex', '0');
		thumbEl.setAttribute('aria-label', 'Play ' + title);

		var play = document.createElement('div');
		play.className = 'tv-vcard-play';
		play.innerHTML = '<span><i class="fas fa-play" aria-hidden="true"></i></span>';
		thumbEl.appendChild(play);

		function playNow() {
			openPlayer(title, url);
		}
		thumbEl.addEventListener('click', playNow);
		thumbEl.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				playNow();
			}
		});

		var body = document.createElement('div');
		body.className = 'tv-vcard-body';
		var h = document.createElement('div');
		h.className = 'tv-vcard-title';
		h.textContent = title;

		var meta = document.createElement('div');
		meta.className = 'tv-vcard-meta';
		var ml = document.createElement('span');
		ml.className = 'tv-vcard-meta-left';
		ml.textContent = metaLeftLabel(item);
		var mr = document.createElement('span');
		mr.className = 'tv-vcard-meta-right';
		mr.innerHTML = '<i class="far fa-clock" aria-hidden="true"></i> ' + esc(metaRightLabel(item));
		meta.appendChild(ml);
		meta.appendChild(mr);

		var actions = document.createElement('div');
		actions.className = 'tv-vcard-actions';
		var delBtn = document.createElement('button');
		delBtn.type = 'button';
		delBtn.className = 'btn btn-sm btn-outline-danger tv_del';
		delBtn.setAttribute('data-id', String(item.id));
		delBtn.innerHTML = '<i class="fas fa-trash-alt" aria-hidden="true"></i> Delete';
		delBtn.addEventListener('click', function (e) {
			e.stopPropagation();
			remove(String(item.id));
		});
		actions.appendChild(delBtn);

		body.appendChild(h);
		body.appendChild(meta);
		body.appendChild(actions);
		wrap.appendChild(thumbEl);
		wrap.appendChild(body);
		return wrap;
	}

	function loadSubjects() {
		fetch(subjectsUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({ access_token: token, batch_id: batchId })
		}).then(function (r) { return r.json(); }).then(function (j) {
			var sel = document.getElementById('tv_subject');
			var isOk = j && (j.status === true || j.status === 'true');
			var rows = isOk && j.data && j.data.subjects ? j.data.subjects : [];
			var html = '<option value="">Select subject</option>';
			for (var i = 0; i < rows.length; i++) {
				html += '<option value="' + esc(String(rows[i].subjectId)) + '">' + esc(rows[i].subjectName || ('Subject #' + rows[i].subjectId)) + '</option>';
			}
			sel.innerHTML = html;
			if (!isOk) {
				msg((j && j.msg) ? j.msg : 'Unable to load subjects', true);
				return;
			}
			if (!rows.length) {
				msg('No subjects mapped for this teacher in this batch.', true);
			}
		}).catch(function () {
			msg('Network error while loading subjects', true);
		});
	}

	function load() {
		var listEl = document.getElementById('tv_list');
		listEl.innerHTML = '';
		if (batchId < 1) {
			listEl.innerHTML = '<p class="text-muted small px-1">Invalid batch.</p>';
			return;
		}
		fetch(listUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({ access_token: token, batch_id: batchId, page: 1, limit: 200 })
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (j.data && typeof j.data.quotaLimitSeconds !== 'undefined') {
				quotaLimitSeconds = parseInt(j.data.quotaLimitSeconds, 10) || quotaLimitSeconds;
				quotaUsedSeconds = parseInt(j.data.quotaUsedSeconds, 10) || 0;
				renderQuota();
			}
			var rows = j.data && j.data.videoLectures ? j.data.videoLectures : [];
			if (!rows.length) {
				listEl.innerHTML = '<p class="text-muted small px-1 py-3">No videos yet. Add a YouTube URL or upload a file above.</p>';
				return;
			}
			for (var i = 0; i < rows.length; i++) {
				listEl.appendChild(buildCard(rows[i]));
			}
		}).catch(function () {
			listEl.innerHTML = '<p class="text-danger small px-1">Could not load videos.</p>';
		});
	}

	function remove(id) {
		if (!window.confirm('Delete this video lecture?')) return;
		fetch(delUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
			body: JSON.stringify({ access_token: token, video_lecture_id: parseInt(id, 10), batch_id: batchId })
		}).then(function (r) { return r.json(); }).then(function (j) {
			msg((j && j.msg) || 'Deleted', !(j && (j.status === 'true' || j.status === true)));
			load();
		});
	}

	function submitAdd(fd) {
		setAddBusy(true);
		uploadFormData(addUrl, fd)
			.then(function (j) {
				var ok = !!(j && (j.status === 'true' || j.status === true));
				msg((j && j.msg) || 'Saved', !ok);
				if (ok) {
					document.getElementById('tv_title').value = '';
					document.getElementById('tv_topic').value = '';
					document.getElementById('tv_subject').value = '';
					document.getElementById('tv_url').value = '';
					document.getElementById('tv_file').value = '';
				}
				load();
				setAddBusy(false);
			}, function () {
				msg('Could not save video.', true);
				setAddBusy(false);
			});
	}

	function add() {
		if (addInFlight) return;
		var fd = new FormData();
		fd.append('access_token', token);
		fd.append('batch_id', batchId);
		fd.append('title', document.getElementById('tv_title').value.trim());
		fd.append('topic', document.getElementById('tv_topic').value.trim());
		var s = document.getElementById('tv_subject');
		fd.append('subject', s && s.value ? s.options[s.selectedIndex].text : '');
		fd.append('url', document.getElementById('tv_url').value.trim());
		var f = document.getElementById('tv_file').files[0];
		if (!f) {
			submitAdd(fd);
			return;
		}
		fd.append('video_file', f);
		setAddBusy(true);
		setProgress(0, 'Reading video duration...');
		readVideoDuration(f).then(function (dur) {
			fd.append('duration_seconds', dur || 0);
			if (dur > 0) {
				var remaining = Math.max(0, quotaLimitSeconds - quotaUsedSeconds);
				if (dur > remaining) {
					setAddBusy(false);
					msg('This video is ' + fmtHM(dur) + ', but only ' + fmtHM(remaining)
						+ ' remain of the 330-hour yearly limit for this batch.', true);
					return;
				}
			}
			submitAdd(fd);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		addBtn = document.getElementById('tv_add');
		addBtnDefaultText = addBtn ? addBtn.textContent : 'Save video';
		progressWrap = document.getElementById('tv_progress');
		progressFill = document.getElementById('tv_progress_fill');
		progressText = document.getElementById('tv_progress_text');
		if (addBtn) addBtn.addEventListener('click', add);
		document.getElementById('tvPlayerClose').addEventListener('click', closePlayer);
		modalEl.addEventListener('click', function (ev) {
			if (ev.target === modalEl) closePlayer();
		});
		loadSubjects();
		load();
	});
})();
</script>
