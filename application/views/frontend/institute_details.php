<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=2">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo site_url('institute/listing'); ?>" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title" id="inst_d_mobile_title">Profile details</span>
	</div>
	<div class="inst-detail-container" id="inst_detail_root">
		<div id="inst_d_msg" class="inst-muted text-center py-3"></div>
		<div id="inst_d_body" class="inst-detail-hidden">
			<div class="inst-detail-summary-card">
				<div class="inst-detail-summary-row">
					<img id="inst_d_logo" class="inst-detail-logo-lg" src="" alt="" style="display:none;">
					<div id="inst_d_logo_ph" class="inst-detail-logo-lg inst-avatar-placeholder inst-detail-hidden"></div>
					<div class="inst-detail-summary-main">
						<div class="inst-detail-rating-row" id="inst_d_rating_block"></div>
						<h2 id="inst_d_name" class="inst-detail-name"></h2>
						<ul class="inst-detail-contact-list" id="inst_d_contact"></ul>
					</div>
				</div>
			</div>
			<div class="inst-detail-panel" id="inst_panel_batches">
				<div class="inst-panel-head">
					<h3 id="inst_d_batches_title">Batches</h3>
					<a href="javascript:void(0)" class="inst-see-all" id="inst_batches_toggle" style="display:none;">See all</a>
				</div>
				<div id="inst_d_batches" class="inst-card-grid"></div>
			</div>
			<div class="inst-detail-panel" id="inst_panel_reviews">
				<div class="inst-panel-head">
					<h3 id="inst_d_reviews_title">Ratings &amp; reviews</h3>
					<a href="javascript:void(0)" class="inst-see-all" id="inst_reviews_toggle" style="display:none;">See all</a>
				</div>
				<div id="inst_d_reviews" class="inst-card-grid"></div>
				<?php if (! empty($web_logged_in)) { ?>
				<a id="inst_d_add_review" class="inst-write-review-cta" href="#">Write a review</a>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
<script>
(function () {
	var iid = <?php echo (int) (isset($institute_id) ? $institute_id : 0); ?>;
	var dataUrl = <?php echo json_encode(isset($institute_details_data_url) ? $institute_details_data_url : ''); ?>;
	var addReviewBase = <?php echo json_encode(isset($add_review_url) ? rtrim($add_review_url, '/') : ''); ?>;
	var batchPreview = 3;
	var reviewPreview = 2;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function starsHtml(n) {
		n = Math.max(0, Math.min(5, parseInt(n, 10) || 0));
		var o = '<span class="inst-detail-rating-stars">';
		for (var i = 0; i < 5; i++) { o += (i < n ? '★' : '☆'); }
		return o + '</span>';
	}
	function formatDate(s) {
		if (!s) { return ''; }
		var t = Date.parse(s);
		if (!isNaN(t)) {
			try {
				return new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(t));
			} catch (e) { /* ignore */ }
		}
		return String(s).slice(0, 10);
	}
	function timeRange(b) {
		var a = (b.start_time || b.startTime || '').toString().slice(0, 5);
		var z = (b.end_time || b.endTime || '').toString().slice(0, 5);
		if (a && z) { return a + ' – ' + z; }
		return '';
	}
	function postJson(body) {
		return fetch(dataUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); });
	}
	function buildBatchCard(b, extraHidden) {
		var bid = b.id || b.batchId || '';
		var title = b.batch_name || b.title || b.name || ('Batch #' + bid);
		var sub = timeRange(b) || '';
		var batchListBase = '<?php echo rtrim(base_url(), '/'); ?>/batch';
		var href = bid ? (batchListBase + '/details?batch_id=' + encodeURIComponent(bid)) : (batchListBase + '/list');
		var cls = 'inst-batch-card' + (extraHidden ? ' inst-b-extra inst-detail-hidden' : '');
		return '<a class="' + cls + '" href="' + esc(href) + '">' +
			'<div class="inst-mini-logo"><i class="fas fa-layer-group" style="padding:12px;color:#f5a623;"></i></div>' +
			'<div class="inst-card-body">' +
			'<p class="inst-card-title-sm">' + esc(title) + '</p>' +
			(sub ? '<p class="inst-card-sub">' + esc(sub) + '</p>' : '') +
			'</div><span class="inst-card-chevron"><i class="fas fa-chevron-right"></i></span></a>';
	}
	function buildReviewCard(x, extraHidden) {
		var uid = x.user_id != null ? x.user_id : x.userId;
		var ut = (x.user_type || x.userType || 'User').toString();
		var label = ut.charAt(0).toUpperCase() + ut.slice(1) + (uid ? ' · #' + uid : '');
		var rt = x.rating != null ? parseInt(x.rating, 10) : 0;
		var dt = formatDate(x.createdAt || x.created_at || '');
		var msg = (x.msg || '').toString();
		var snippet = msg.length > 110 ? msg.slice(0, 110) + '…' : msg;
		var cls = 'inst-review-card' + (extraHidden ? ' inst-r-extra inst-detail-hidden' : '');
		return '<div class="' + cls + '">' +
			'<div class="inst-avatar inst-avatar-placeholder">' + esc(ut.charAt(0).toUpperCase()) + '</div>' +
			'<div class="inst-card-body">' +
			'<p class="inst-card-title-sm">' + esc(label) + ' ' + starsHtml(rt) + '</p>' +
			(dt ? '<p class="inst-card-sub">' + esc(dt) + '</p>' : '') +
			(msg ? '<p class="inst-review-snippet">' + esc(snippet) + '</p>' : '') +
			'</div></div>';
	}
	function wireExpand(btnId, extraClass, total, preview) {
		var btn = document.getElementById(btnId);
		if (!btn || total <= preview) { return; }
		btn.style.display = '';
		var expanded = false;
		btn.addEventListener('click', function () {
			expanded = !expanded;
			document.querySelectorAll('.' + extraClass).forEach(function (el) {
				el.classList.toggle('inst-detail-hidden', !expanded);
			});
			btn.textContent = expanded ? 'Show less' : 'See all';
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		if (iid < 1) {
			document.getElementById('inst_d_msg').textContent = 'Missing profile. Open this page from the directory.';
			return;
		}
		document.getElementById('inst_d_msg').textContent = 'Loading…';
		postJson({ institute_id: iid, reviews_limit: 40 }).then(function (j) {
			if (!ok(j.status) || !j.institute) {
				document.getElementById('inst_d_msg').textContent = j.msg || 'Could not load profile.';
				return;
			}
			document.getElementById('inst_d_msg').textContent = '';
			document.getElementById('inst_d_body').classList.remove('inst-detail-hidden');
			var ins = j.institute;
			var profileType = (ins.profileType || ins.userType || '').toString().toLowerCase() === 'teacher' ? 'teacher' : 'institute';
			var profileLabel = ins.profileTypeLabel || (profileType === 'teacher' ? 'Teacher' : 'Institute');
			var imgUrl = (ins.imageUrl || '').trim();
			var hero = document.getElementById('inst_d_hero');
			var heroWrap = document.getElementById('inst_d_hero_wrap');
			if (hero && heroWrap) {
				if (imgUrl) {
					hero.src = imgUrl;
					hero.style.display = 'block';
					hero.alt = ins.name || '';
					heroWrap.style.background = '#dce4f5';
				} else {
					hero.style.display = 'none';
				}
			}
			var logo = document.getElementById('inst_d_logo');
			var lph = document.getElementById('inst_d_logo_ph');
			if (imgUrl) {
				logo.src = imgUrl;
				logo.alt = ins.name || '';
				logo.style.display = 'block';
				lph.classList.add('inst-detail-hidden');
			} else {
				logo.style.display = 'none';
				lph.textContent = (ins.name || 'I').charAt(0).toUpperCase();
				lph.classList.remove('inst-detail-hidden');
			}
			var r = j.rating || {};
			var avg = r.averageRating != null ? Number(r.averageRating) : 0;
			var tr = r.totalReviews != null ? r.totalReviews : 0;
			var ratingBlock = document.getElementById('inst_d_rating_block');
			if (profileType === 'institute') {
				ratingBlock.innerHTML = starsHtml(Math.round(avg)) +
					'<span class="inst-detail-rating-num">' + esc(avg.toFixed(1)) + ' (' + esc(tr) + ')</span>';
			} else {
				ratingBlock.innerHTML = '<span class="inst-detail-rating-num">' + esc(profileLabel) + '</span>';
			}
			document.getElementById('inst_d_mobile_title').textContent = profileLabel + ' details';
			document.getElementById('inst_d_name').textContent = ins.name || profileLabel;
			var loc = [ins.address, ins.city, ins.state, ins.pincode, ins.country].filter(Boolean).join(', ');
			var contacts = [];
			contacts.push('<li><span class="inst-ci"><i class="fas fa-user-tag"></i></span><span>' + esc(profileLabel) + '</span></li>');
			if (ins.mobile) {
				contacts.push('<li><span class="inst-ci"><i class="fas fa-phone-alt"></i></span><span>' + esc(ins.mobile) + '</span></li>');
			}
			if (ins.email) {
				contacts.push('<li><span class="inst-ci"><i class="fas fa-envelope"></i></span><a href="mailto:' + esc(ins.email) + '">' + esc(ins.email) + '</a></li>');
			}
			if (loc) {
				contacts.push('<li><span class="inst-ci"><i class="fas fa-map-marker-alt"></i></span><span>' + esc(loc) + '</span></li>');
			}
			document.getElementById('inst_d_contact').innerHTML = contacts.length ? contacts.join('') : '<li class="inst-muted">No contact on file.</li>';
			var ar = document.getElementById('inst_d_add_review');
			if (ar) {
				if (profileType === 'institute') {
					ar.href = addReviewBase + '?institute_id=' + encodeURIComponent(iid);
					ar.style.display = '';
				} else {
					ar.style.display = 'none';
				}
			}
			document.getElementById('inst_d_batches_title').textContent = profileType === 'teacher' ? 'Assigned batches' : 'Batches';
			var bb = j.batches || [];
			var bh = document.getElementById('inst_d_batches');
			if (!bb.length) {
				bh.innerHTML = '<p class="inst-muted mb-0 px-2">No batches listed.</p>';
			} else {
				bh.innerHTML = bb.map(function (b, idx) {
					return buildBatchCard(b, idx >= batchPreview);
				}).join('');
				wireExpand('inst_batches_toggle', 'inst-b-extra', bb.length, batchPreview);
			}
			var rev = j.reviews || [];
			var rh = document.getElementById('inst_d_reviews');
			var reviewPanel = document.getElementById('inst_panel_reviews');
			if (profileType !== 'institute') {
				reviewPanel.style.display = 'none';
			} else if (!rev.length) {
				rh.innerHTML = '<p class="inst-muted mb-0 px-2">No reviews yet.</p>';
			} else {
				rh.innerHTML = rev.map(function (x, idx) {
					return buildReviewCard(x, idx >= reviewPreview);
				}).join('');
				wireExpand('inst_reviews_toggle', 'inst-r-extra', rev.length, reviewPreview);
			}
		}).catch(function () {
			document.getElementById('inst_d_msg').textContent = 'Network error.';
		});
	});
})();
</script>
