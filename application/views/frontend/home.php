<?php
$home_site_logo = '';
if (!empty($site_Details) && is_array($site_Details) && !empty($site_Details[0]['site_logo'])) {
	$home_site_logo = base_url('uploads/site_data/' . $site_Details[0]['site_logo']);
}
$home_batches = (isset($batches) && is_array($batches)) ? $batches : array();
$home_resolve_batch_image = function ($raw) use ($home_site_logo) {
	$img = trim((string) $raw);
	if ($img === '') {
		return ($home_site_logo !== '') ? $home_site_logo : base_url('assets/images/home-banner-center.jpg');
	}
	$img = str_replace('\\', '/', $img);
	if (preg_match('/^https?:\/\//i', $img)) {
		return $img;
	}
	$img = ltrim($img, '/');
	if (strpos($img, 'uploads/batch_image/') === 0) {
		return batch_image_url(substr($img, strlen('uploads/batch_image/')));
	}
	if (strpos($img, 'batch_image/') === 0) {
		return batch_image_url(substr($img, strlen('batch_image/')));
	}
	return batch_image_url($img);
};
$home_promos = array();
if (!empty($home_batches)) {
	$promo_max = min(3, count($home_batches));
	for ($i = 0; $i < $promo_max; $i++) {
		$p_batch = $home_batches[$i];
		$p_name = isset($p_batch['batch_name']) ? trim((string) $p_batch['batch_name']) : ('Batch ' . ($i + 1));
		$p_img = $home_resolve_batch_image(isset($p_batch['batch_image']) ? $p_batch['batch_image'] : '');
		$home_promos[] = array(
			'title' => ($p_name !== '') ? $p_name : ('Batch ' . ($i + 1)),
			'subtitle' => 'Enroll now',
			'image' => $p_img,
		);
	}
}
while (count($home_promos) < 3) {
	$fallbacks = array(
		base_url('assets/images/home-banner-left.jpg'),
		base_url('assets/images/home-banner-center.jpg'),
		base_url('assets/images/home-banner-right.jpg'),
	);
	$idx = count($home_promos);
	$home_promos[] = array(
		'title' => ($idx === 0 ? 'IELTS Coaching' : ($idx === 1 ? 'Gradmo 20% OFF' : 'Upgrade Classes')),
		'subtitle' => ($idx === 1 ? 'Use code: XYZ123OFF' : 'Enroll now'),
		'image' => $fallbacks[$idx],
	);
}
$home_inst_listing = base_url('institute/listing');
$home_batch_list = base_url('batch/list');
$home_institute_api_url = isset($home_institute_api_url) ? $home_institute_api_url : site_url('api/institute/listing');
$home_institute_details_url = isset($home_institute_details_url) ? $home_institute_details_url : site_url('institute/details');
$home_api_access_token = isset($api_access_token) ? (string) $api_access_token : '';
$home_slider_api_url = site_url('api/batch/slider-list');
$home_login_url = base_url('login');
$home_default_card_image = 'data:image/svg+xml;utf8,' . rawurlencode(
	'<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675" viewBox="0 0 1200 675">' .
	'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#e8f0fe"/><stop offset="1" stop-color="#fff3e6"/></linearGradient></defs>' .
	'<rect width="1200" height="675" fill="url(#g)"/>' .
	'<circle cx="210" cy="200" r="75" fill="#4b6cf2" opacity="0.22"/>' .
	'<rect x="340" y="150" width="540" height="46" rx="12" fill="#2948c8" opacity="0.82"/>' .
	'<rect x="340" y="224" width="430" height="26" rx="10" fill="#3b5bdb" opacity="0.45"/>' .
	'<rect x="340" y="286" width="360" height="26" rx="10" fill="#f59e0b" opacity="0.45"/>' .
	'<rect x="150" y="420" width="900" height="105" rx="18" fill="#ffffff" opacity="0.65"/>' .
	'<text x="600" y="487" text-anchor="middle" font-family="Arial, sans-serif" font-size="38" fill="#1f2a56">E Academy</text>' .
	'</svg>'
);
?>
<style>
/* Home — full-bleed hero slider + search (do not duplicate .edu-home-promo-row rules in frontend-style.css) */
.edu-home-page { overflow-x: hidden; }
.edu-home-hero {
	background: #ffffff;
	padding: 0;
	margin: 0;
	border-bottom: 1px solid #dce7ff;
}
/* Edge-to-edge like the header bar */
.edu-home-slider-bleed {
	width: 100vw;
	max-width: 100%;
	margin-left: calc(50% - 50vw);
	margin-right: calc(50% - 50vw);
	position: relative;
	background: #0f172a;
}
.edu-home-slider-viewport {
	position: relative;
	overflow: hidden;
	width: 100%;
	min-height: clamp(200px, 32vw, 460px);
}
.edu-home-promo-track {
	display: flex;
	flex-direction: row;
	flex-wrap: nowrap;
	align-items: stretch;
	box-sizing: border-box;
	transition: transform 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
	will-change: transform;
}
.edu-home-promo-page {
	flex-shrink: 0;
	box-sizing: border-box;
	height: 100%;
	min-height: clamp(200px, 32vw, 460px);
}
.edu-home-hero-slide {
	position: relative;
	width: 100%;
	height: 100%;
	min-height: clamp(200px, 32vw, 460px);
	overflow: hidden;
}
.edu-home-hero-slide__media {
	position: absolute;
	inset: 0;
	z-index: 0;
}
.edu-home-hero-slide__media img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center;
	display: block;
}
.edu-home-hero-slide__overlay {
	position: absolute;
	inset: 0;
	z-index: 1;
	background: linear-gradient(105deg, rgba(15, 23, 42, 0.72) 0%, rgba(15, 23, 42, 0.35) 42%, rgba(15, 23, 42, 0.12) 100%);
}
.edu-home-hero-slide--i1 .edu-home-hero-slide__overlay {
	background: linear-gradient(105deg, rgba(30, 58, 138, 0.55) 0%, rgba(15, 23, 42, 0.25) 55%, rgba(15, 23, 42, 0.1) 100%);
}
.edu-home-hero-slide--i2 .edu-home-hero-slide__overlay {
	background: linear-gradient(105deg, rgba(180, 83, 9, 0.45) 0%, rgba(15, 23, 42, 0.35) 50%, rgba(15, 23, 42, 0.15) 100%);
}
.edu-home-hero-slide__content {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 22px 20px 26px;
	z-index: 2;
	max-width: 720px;
	box-sizing: border-box;
}
@media (min-width: 768px) {
	.edu-home-hero-slide__content { padding-left: 36px; padding-right: 36px; padding-bottom: 34px; }
}
.edu-home-hero-slide__title {
	margin: 0 0 8px;
	font-size: clamp(1.2rem, 2.8vw, 2.05rem);
	font-weight: 800;
	color: #fff;
	line-height: 1.2;
	text-shadow: 0 2px 18px rgba(0, 0, 0, 0.45);
	font-family: 'Montserrat', sans-serif;
	text-transform: none;
}
.edu-home-hero-slide__subtitle {
	margin: 0;
	font-size: clamp(0.88rem, 1.5vw, 1.08rem);
	font-weight: 500;
	color: rgba(255, 255, 255, 0.94);
	line-height: 1.45;
	text-shadow: 0 1px 10px rgba(0, 0, 0, 0.4);
}
.edu-home-slider-nav {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	z-index: 5;
	width: 44px;
	height: 44px;
	border: 0;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.94);
	color: #1e3a5f;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.18);
	cursor: pointer;
	display: none;
	align-items: center;
	justify-content: center;
	font-size: 14px;
	transition: background 0.2s ease, transform 0.2s ease;
}
.edu-home-slider-nav:hover {
	background: #fff;
	color: #0b8df7;
}
.edu-home-slider-nav.is-visible {
	display: flex;
}
.edu-home-slider-nav--prev { left: 14px; }
.edu-home-slider-nav--next { right: 14px; }
@media (max-width: 575px) {
	.edu-home-slider-nav { width: 38px; height: 38px; left: 8px; }
	.edu-home-slider-nav--next { right: 8px; }
}
.edu-home-hero-bottom {
	padding: 12px 16px 14px;
	background: #ffffff;
}
.edu-home-hero-inner {
	max-width: 1200px;
	margin: 0 auto;
	width: 100%;
	box-sizing: border-box;
}
.edu-home-promo-dots {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 8px;
	margin: 0 0 12px;
	flex-wrap: wrap;
}
.edu-home-promo-dot {
	width: 9px;
	height: 9px;
	border-radius: 50%;
	border: 0;
	background: #cfd8ea;
	padding: 0;
	cursor: pointer;
	transition: transform 0.2s ease, background 0.2s ease;
}
.edu-home-promo-dot.is-active {
	background: #0b8df7;
	transform: scale(1.15);
}
.edu-home-promo-dots.is-hidden {
	display: none;
}
.edu-home-search {
	display: flex;
	align-items: stretch;
	flex-wrap: nowrap;
	background: #fff;
	border: 1px solid #e8ecf4;
	border-radius: 4px;
	overflow: hidden;
	box-shadow: 0 6px 18px rgba(9, 18, 63, 0.24);
	max-width: 100%;
}
.edu-home-search input[type="search"],
.edu-home-search input[type="text"] {
	flex: 1 1 auto;
	min-width: 0;
	border: 0;
	padding: 16px 18px;
	font-size: 0.95rem;
	outline: none;
	background: transparent;
	color: #111827;
}
.edu-home-search input::placeholder {
	color: #9ca3af;
	font-style: italic;
}
.edu-home-search button {
	flex: 0 0 auto;
	background: #0b8df7;
	color: #fff;
	border: 0;
	padding: 16px 28px;
	font-weight: 600;
	font-size: 0.95rem;
	cursor: pointer;
	transition: background 0.2s ease;
	white-space: nowrap;
}
.edu-home-search button:hover {
	background: #0878d2;
	color: #fff;
}
@media (max-width: 480px) {
	.edu-home-search { flex-wrap: wrap; border-radius: 20px; }
	.edu-home-search input { width: 100%; padding: 14px 16px; }
	.edu-home-search button {
		width: 100%;
		border-radius: 0 0 18px 18px;
		padding: 14px 16px;
	}
}
.edu-home-wrap {
	max-width: 1200px;
	margin: 0 auto;
	padding: 14px 16px 44px;
	background: #ffffff;
}
.edu-home-nearby,
.edu-home-live {
	margin-bottom: 14px;
}
.edu-home-nearby .edu-home-scroll-row,
.edu-home-live .edu-home-live-stack {
	background: #ffffff;
	border-radius: 0;
	padding: 6px 0;
	border: 0;
}
.edu-home-section-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 14px;
}
.edu-home-section-head h2 {
	margin: 0;
	font-size: 1.15rem;
	font-weight: 700;
	color: #111827;
}
.edu-home-section-head a {
	font-size: 0.9rem;
	font-weight: 600;
	color: #4b6cf2;
	text-decoration: none;
}
.edu-home-section-head a:hover { text-decoration: underline; }
.edu-home-nearby-msg {
	display: none;
	font-size: 0.9rem;
	color: #64748b;
	margin: 0 0 12px;
	line-height: 1.5;
}
.edu-home-nearby-msg.is-visible {
	display: block;
}
.edu-home-nearby-msg a {
	color: #4b6cf2;
	font-weight: 600;
}
.edu-home-scroll-row {
	display: flex;
	gap: 14px;
	overflow-x: auto;
	padding-bottom: 8px;
	-webkit-overflow-scrolling: touch;
	scroll-snap-type: x mandatory;
}
.edu-home-scroll-row > * { scroll-snap-align: start; flex-shrink: 0; }
.edu-home-inst-card {
	width: 280px;
	max-width: 85vw;
	background: #fff;
	border-radius: 16px;
	padding: 14px 14px 16px;
	box-shadow: 0 4px 18px rgba(15, 23, 42, 0.07);
	border: 1px solid #eef0f5;
	text-decoration: none;
	color: inherit;
	display: block;
	transition: box-shadow 0.2s ease;
}
.edu-home-inst-card:hover {
	box-shadow: 0 8px 24px rgba(75, 108, 242, 0.12);
	color: inherit;
	text-decoration: none;
}
.edu-home-inst-head {
	display: flex;
	gap: 12px;
	align-items: center;
	margin-bottom: 8px;
}
.edu-home-inst-logo {
	width: 44px;
	height: 44px;
	border-radius: 12px;
	object-fit: cover;
	background: #eef2ff;
	border: 1px solid #e5e9f6;
	flex: 0 0 44px;
}
.edu-home-inst-title-wrap {
	min-width: 0;
}
.edu-home-badge {
	display: inline-block;
	font-size: 0.72rem;
	font-weight: 600;
	padding: 4px 10px;
	border-radius: 8px;
	background: #e8f0fe;
	color: #3a5fc9;
	margin-bottom: 10px;
}
.edu-home-inst-card h3 {
	margin: 0 0 4px;
	font-size: 1rem;
	font-weight: 700;
	color: #111827;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.edu-home-inst-card p {
	margin: 0;
	font-size: 0.85rem;
	color: #6b7280;
	line-height: 1.4;
	min-height: 36px;
}
.edu-home-live-stack {
	display: flex;
	gap: 22px;
	overflow-x: auto;
	padding: 4px 2px 14px;
	-webkit-overflow-scrolling: touch;
	scroll-snap-type: x proximity;
}
.edu-home-live-stack > * { scroll-snap-align: start; flex-shrink: 0; }
.edu-home-live-card {
	width: 300px;
	max-width: 84vw;
	background: #fff;
	border-radius: 18px;
	overflow: hidden;
	box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
	border: 1px solid #e8edf7;
	display: block;
	text-decoration: none;
	color: inherit;
	transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.edu-home-live-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
	color: inherit;
	text-decoration: none;
}
.edu-home-live-media {
	position: relative;
	aspect-ratio: 16 / 9;
	background: #eef2ff;
	overflow: hidden;
}
.edu-home-live-thumb {
	width: 100%;
	height: 100%;
	object-fit: cover;
	background: #eef2ff;
	display: block;
}
.edu-home-live-body { padding: 16px 18px 18px; }
.edu-home-live-body h3 {
	margin: 0 0 10px;
	font-size: 1.02rem;
	font-weight: 700;
	color: #111827;
	line-height: 1.35;
	min-height: 2.7em;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
.edu-home-live-meta {
	margin: 0 0 4px;
	font-size: 0.92rem;
	color: #4b5563;
	line-height: 1.45;
}
.edu-home-live-meta strong {
	color: #1f2937;
	font-weight: 700;
}
.edu-home-live-dates {
	margin: 10px 0 0;
	font-size: 0.9rem;
	color: #6b7280;
	line-height: 1.4;
}
.edu-home-live-empty {
	margin: 0;
	font-size: 0.92rem;
	color: #6b7280;
}
.edu-home-muted { color: #6b7280; font-size: 0.9rem; text-align: center; padding: 12px; }
@media (max-width: 575px) {
	.edu-home-live-stack { gap: 16px; }
	.edu-home-live-card { width: 270px; max-width: 86vw; }
}
</style>

<div class="edu-home-page">
	<div class="edu-home-hero">
		<div class="edu-home-slider-bleed">
			<div id="edu_home_slider_viewport" class="edu-home-slider-viewport" aria-label="Featured banners">
				<button type="button" id="edu_home_slider_prev" class="edu-home-slider-nav edu-home-slider-nav--prev" aria-label="Previous slide"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
				<button type="button" id="edu_home_slider_next" class="edu-home-slider-nav edu-home-slider-nav--next" aria-label="Next slide"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
				<div id="edu_home_promo_track" class="edu-home-promo-track"></div>
			</div>
		</div>
		<div class="edu-home-hero-bottom">
			<div class="edu-home-hero-inner">
				<div id="edu_home_promo_dots" class="edu-home-promo-dots" aria-label="Slide indicators"></div>
				<form class="edu-home-search" id="edu_home_search_form" action="<?php echo html_escape($home_inst_listing); ?>" method="get" role="search">
					<label for="edu_home_search_q" class="sr-only">Search institutes and batches</label>
					<input type="search" name="search" id="edu_home_search_q" placeholder="Search by City, Topic, or Institute Name" autocomplete="off">
					<button type="submit">Search</button>
				</form>
			</div>
		</div>
	</div>
	<div class="edu-home-wrap">
	<section class="edu-home-nearby" aria-labelledby="edu-home-nearby-title">
		<div class="edu-home-section-head">
			<h2 id="edu-home-nearby-title">Nearby Institutes</h2>
			<a href="<?php echo html_escape($home_inst_listing); ?>">View All</a>
		</div>
		<div id="edu_home_nearby_msg" class="edu-home-nearby-msg" role="status" aria-live="polite"></div>
		<div id="edu_home_nearby_row" class="edu-home-scroll-row"></div>
	</section>
	<script>
	(function () {
		var sliderApiUrl = <?php echo json_encode($home_slider_api_url); ?>;
		var apiUrl = <?php echo json_encode($home_institute_api_url); ?>;
		var token = <?php echo json_encode($home_api_access_token); ?>;
		var detailsBase = <?php echo json_encode(rtrim($home_institute_details_url, '/') . '/'); ?>;
		var listingUrl = <?php echo json_encode($home_inst_listing); ?>;
		var loginUrl = <?php echo json_encode($home_login_url); ?>;
		var defaultImage = <?php echo json_encode($home_default_card_image); ?>;
		var promoViewport = document.getElementById('edu_home_slider_viewport');
		var promoTrack = document.getElementById('edu_home_promo_track');
		var promoDots = document.getElementById('edu_home_promo_dots');
		var promoPrev = document.getElementById('edu_home_slider_prev');
		var promoNext = document.getElementById('edu_home_slider_next');
		var row = document.getElementById('edu_home_nearby_row');
		var msg = document.getElementById('edu_home_nearby_msg');
		if (!row || !msg || !promoViewport || !promoTrack || !promoDots) { return; }
		function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
		function esc(t) {
			var d = document.createElement('div');
			d.textContent = t == null ? '' : String(t);
			return d.innerHTML;
		}
		function badgeFromListingItem(it) {
			var ut = (it.userType != null ? String(it.userType) : '').trim().toLowerCase();
			if (ut === 'teacher') { return 'Teacher'; }
			if (ut === 'institute') { return 'Institute'; }
			if (ut === 'student') { return 'Student'; }
			var r = it.role != null ? parseInt(it.role, 10) : NaN;
			if (r === 3) { return 'Teacher'; }
			if (r === 4) { return 'Institute'; }
			var pm = (it.pay_mode == null ? '' : String(it.pay_mode)).toLowerCase();
			if (pm.indexOf('online') !== -1) { return 'Online'; }
			if (pm.indexOf('hybrid') !== -1) { return 'Hybrid'; }
			if (pm.indexOf('offline') !== -1) { return 'Offline'; }
			return 'Institute';
		}
		function addressLine(it) {
			var parts = [];
			if (it.address) { parts.push(String(it.address)); }
			else if (it.city) { parts.push(String(it.city)); }
			if (it.pincode && (!it.address || String(it.address).indexOf(String(it.pincode)) === -1)) {
				parts.push(String(it.pincode));
			}
			return parts.join(', ') || (it.city ? String(it.city) : '');
		}
		function normalizeBanners(items) {
			var list = Array.isArray(items) ? items : [];
			if (!list.length) {
				return <?php echo json_encode($home_promos); ?>;
			}
			return list.map(function (it, i) {
				var title = (it.heading || '').trim() || ('Banner ' + (i + 1));
				var subtitle = (it.subheading || '').trim() || 'Enroll now';
				var image = (it.image_url || '').trim() || defaultImage;
				if (!it.subheading && it.description) {
					subtitle = String(it.description).trim();
				}
				return { title: title, subtitle: subtitle, image: image };
			});
		}
		var PROMO_AUTOPLAY_MS = 7000;
		var promoState = { page: 0, pages: 0, timer: null, raw: [] };
		function applyPromoLayout() {
			var w = promoViewport.offsetWidth;
			var n = promoState.pages;
			if (n < 1) { n = 1; }
			if (w < 1) { return; }
			promoTrack.style.width = (w * n) + 'px';
			var pageEls = promoTrack.querySelectorAll('.edu-home-promo-page');
			for (var pi = 0; pi < pageEls.length; pi++) {
				pageEls[pi].style.flex = '0 0 ' + w + 'px';
				pageEls[pi].style.width = w + 'px';
				pageEls[pi].style.minWidth = w + 'px';
				pageEls[pi].style.maxWidth = w + 'px';
			}
			promoTrack.style.transform = 'translateX(-' + (promoState.page * w) + 'px)';
		}
		function renderPromoPages() {
			var items = promoState.raw.slice();
			if (!items.length) {
				items = <?php echo json_encode($home_promos); ?>;
			}
			var pages = [];
			for (var i = 0; i < items.length; i++) {
				pages.push([items[i]]);
			}
			if (!pages.length) {
				var fb = <?php echo json_encode($home_promos); ?>;
				for (var j = 0; j < fb.length; j++) {
					pages.push([fb[j]]);
				}
			}
			promoState.pages = pages.length;
			if (promoState.page >= promoState.pages) {
				promoState.page = 0;
			}
			promoTrack.innerHTML = '';
			promoDots.innerHTML = '';
			pages.forEach(function (group, pIdx) {
				var page = document.createElement('div');
				page.className = 'edu-home-promo-page';
				var item = group[0];
				var card = document.createElement('div');
				card.className = 'edu-home-hero-slide edu-home-hero-slide--i' + (pIdx % 3);
				card.innerHTML =
					'<div class="edu-home-hero-slide__media"><img src="' + esc(item.image) + '" alt="' + esc(item.title) + '"></div>' +
					'<div class="edu-home-hero-slide__overlay" aria-hidden="true"></div>' +
					'<div class="edu-home-hero-slide__content">' +
					'<div class="edu-home-hero-slide__title">' + esc(item.title) + '</div>' +
					'<p class="edu-home-hero-slide__subtitle">' + esc(item.subtitle) + '</p>' +
					'</div>';
				var img = card.querySelector('img');
				if (img) {
					img.onerror = function () { this.onerror = null; this.src = defaultImage; };
				}
				page.appendChild(card);
				promoTrack.appendChild(page);
				var dot = document.createElement('button');
				dot.type = 'button';
				dot.className = 'edu-home-promo-dot' + (pIdx === promoState.page ? ' is-active' : '');
				dot.setAttribute('aria-label', 'Go to slide ' + (pIdx + 1));
				dot.addEventListener('click', function () {
					promoState.page = pIdx;
					updatePromoPosition(true);
				});
				promoDots.appendChild(dot);
			});
			var n = promoState.pages;
			if (n <= 1) {
				promoDots.classList.add('is-hidden');
			} else {
				promoDots.classList.remove('is-hidden');
			}
			var showNav = n > 1;
			if (promoPrev) {
				if (showNav) { promoPrev.classList.add('is-visible'); } else { promoPrev.classList.remove('is-visible'); }
			}
			if (promoNext) {
				if (showNav) { promoNext.classList.add('is-visible'); } else { promoNext.classList.remove('is-visible'); }
			}
			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(applyPromoLayout);
			});
			syncPromoDots();
		}
		function syncPromoDots() {
			var dots = promoDots.querySelectorAll('.edu-home-promo-dot');
			for (var i = 0; i < dots.length; i++) {
				if (i === promoState.page) { dots[i].classList.add('is-active'); }
				else { dots[i].classList.remove('is-active'); }
			}
		}
		function updatePromoPosition(restartTimer) {
			applyPromoLayout();
			syncPromoDots();
			if (restartTimer) {
				startPromoAutoplay();
			}
		}
		function startPromoAutoplay() {
			if (promoState.timer) {
				window.clearInterval(promoState.timer);
				promoState.timer = null;
			}
			if (promoState.pages <= 1) { return; }
			promoState.timer = window.setInterval(function () {
				promoState.page = (promoState.page + 1) % promoState.pages;
				updatePromoPosition(false);
			}, PROMO_AUTOPLAY_MS);
		}
		function fetchPromos() {
			if (!token) {
				promoState.raw = <?php echo json_encode($home_promos); ?>;
				renderPromoPages();
				startPromoAutoplay();
				return;
			}
			fetch(sliderApiUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json',
					'Authorization': 'Bearer ' + token
				},
				body: JSON.stringify({ page: 1, limit: 30 })
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status) && j.data && Array.isArray(j.data.banners)) {
					promoState.raw = normalizeBanners(j.data.banners);
				} else {
					promoState.raw = <?php echo json_encode($home_promos); ?>;
				}
				renderPromoPages();
				startPromoAutoplay();
			}).catch(function () {
				promoState.raw = <?php echo json_encode($home_promos); ?>;
				renderPromoPages();
				startPromoAutoplay();
			});
		}
		if (promoPrev) {
			promoPrev.addEventListener('click', function () {
				var n = promoState.pages;
				if (n < 2) { return; }
				promoState.page = (promoState.page - 1 + n) % n;
				updatePromoPosition(true);
			});
		}
		if (promoNext) {
			promoNext.addEventListener('click', function () {
				var n = promoState.pages;
				if (n < 2) { return; }
				promoState.page = (promoState.page + 1) % n;
				updatePromoPosition(true);
			});
		}
		var promoResizeTimer = null;
		window.addEventListener('resize', function () {
			if (promoResizeTimer) { window.clearTimeout(promoResizeTimer); }
			promoResizeTimer = window.setTimeout(applyPromoLayout, 120);
		});
		fetchPromos();
		function renderCards(list) {
			row.innerHTML = '';
			if (!list || !list.length) {
				msg.innerHTML = 'No institutes found. <a href="' + esc(listingUrl) + '">Browse directory</a>';
				msg.classList.add('is-visible');
				return;
			}
			msg.textContent = '';
			msg.classList.remove('is-visible');
			list.forEach(function (it) {
				var id = it.instituteId != null ? it.instituteId : it.id;
				var a = document.createElement('a');
				a.className = 'edu-home-inst-card';
				a.href = detailsBase + '?institute_id=' + encodeURIComponent(id);
				var logoSrc = it.imageUrl || it.logo || it.image || it.instituteLogo || '';
				var head = document.createElement('div');
				head.className = 'edu-home-inst-head';
				var logo = document.createElement('img');
				logo.className = 'edu-home-inst-logo';
				logo.src = logoSrc || defaultImage;
				logo.alt = (it.name || 'Institute') + ' logo';
				logo.onerror = function () { this.onerror = null; this.src = defaultImage; };
				var titleWrap = document.createElement('div');
				titleWrap.className = 'edu-home-inst-title-wrap';
				var badge = document.createElement('span');
				badge.className = 'edu-home-badge';
				badge.textContent = badgeFromListingItem(it);
				var h = document.createElement('h3');
				h.textContent = it.name || 'Institute';
				var p = document.createElement('p');
				var line = addressLine(it);
				if (it.distanceKm != null && it.distanceKm !== '') {
					line = (line ? line + ' · ' : '') + it.distanceKm + ' km';
				}
				p.textContent = line || 'View details';
				titleWrap.appendChild(badge);
				titleWrap.appendChild(h);
				head.appendChild(logo);
				head.appendChild(titleWrap);
				a.appendChild(head);
				a.appendChild(p);
				row.appendChild(a);
			});
		}
		function fetchList(lat, lon) {
			var body = { page: 1, limit: 12, order_field: 'name', order_type: 'asc' };
			if (lat != null && lon != null && !isNaN(lat) && !isNaN(lon)) {
				body.order_field = 'distance';
				body.order_type = 'asc';
				body.latitude = lat;
				body.longitude = lon;
			}
			msg.textContent = 'Loading institutes…';
			msg.classList.add('is-visible');
			fetch(apiUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json',
					'Authorization': 'Bearer ' + token
				},
				body: JSON.stringify(body)
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (!ok(j.status)) {
					msg.innerHTML = esc(j.msg || 'Could not load institutes.') + ' <a href="' + esc(listingUrl) + '">Open directory</a>';
					return;
				}
				renderCards(j.institutes || []);
			}).catch(function () {
				msg.textContent = 'Network error. Try again later.';
			});
		}
		if (!token) {
			msg.innerHTML = 'Sign in to load institutes from your account. <a href="' + esc(loginUrl) + '">Log in</a> · <a href="' + esc(listingUrl) + '">Directory</a>';
			msg.classList.add('is-visible');
			return;
		}
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(
				function (pos) {
					fetchList(pos.coords.latitude, pos.coords.longitude);
				},
				function () {
					fetchList(null, null);
				},
				{ timeout: 10000, maximumAge: 300000 }
			);
		} else {
			fetchList(null, null);
		}
	})();
	</script>

	<section class="edu-home-live mt-4" aria-labelledby="edu-home-live-title">
		<div class="edu-home-section-head">
			<h2 id="edu-home-live-title">Live Classes</h2>
			<a href="<?php echo html_escape($home_batch_list); ?>">View All</a>
		</div>
		<div class="edu-home-live-stack">
			<?php
			if (!empty($home_batches)) {
				foreach ($home_batches as $hb) {
					$bid = isset($hb['id']) ? (int) $hb['id'] : 0;
					$bname = isset($hb['batch_name']) ? $hb['batch_name'] : 'Live Class';
					$bimg = $home_resolve_batch_image(isset($hb['batch_image']) ? $hb['batch_image'] : '');
					$home_instructor = isset($hb['instructor']) ? trim((string) $hb['instructor']) : '';
					$home_schedule = isset($hb['schedule']) ? trim((string) $hb['schedule']) : '';
					$home_start = isset($hb['start_date']) ? trim((string) $hb['start_date']) : '';
					$home_end = isset($hb['end_date']) ? trim((string) $hb['end_date']) : '';
					$home_dates = '';
					if ($home_start !== '' && $home_start !== '0000-00-00') {
						$home_dates = $home_start;
					}
					if ($home_end !== '' && $home_end !== '0000-00-00') {
						$home_dates = ($home_dates !== '') ? ($home_dates . ' — ' . $home_end) : $home_end;
					}
					$detail_url = $bid > 0 ? base_url('batch/details?batch_id=' . $bid) : base_url('batch/list');
					?>
			<a class="edu-home-live-card" href="<?php echo html_escape($detail_url); ?>">
				<div class="edu-home-live-media">
					<img class="edu-home-live-thumb" src="<?php echo html_escape($bimg !== '' ? $bimg : $home_default_card_image); ?>" alt="<?php echo html_escape($bname); ?>" onerror="this.onerror=null;this.src='<?php echo html_escape($home_default_card_image); ?>';">
				</div>
				<div class="edu-home-live-body">
					<h3><?php echo html_escape($bname); ?></h3>
					<?php if ($home_instructor !== '') { ?>
					<p class="edu-home-live-meta"><strong>Instructor:</strong> <?php echo html_escape($home_instructor); ?></p>
					<?php } ?>
					<?php if ($home_schedule !== '') { ?>
					<p class="edu-home-live-meta"><strong>Schedule:</strong> <?php echo html_escape($home_schedule); ?></p>
					<?php } ?>
					<?php if ($home_dates !== '') { ?>
					<p class="edu-home-live-dates"><?php echo html_escape($home_dates); ?></p>
					<?php } ?>
				</div>
			</a>
					<?php
				}
			} else {
				?>
			<a class="edu-home-live-card" href="<?php echo html_escape(base_url('batch/list')); ?>">
				<div class="edu-home-live-media">
					<img class="edu-home-live-thumb" src="<?php echo html_escape($home_default_card_image); ?>" alt="Sample batch">
				</div>
				<div class="edu-home-live-body">
					<h3>First Law of Motion</h3>
					<p class="edu-home-live-meta"><strong>Instructor:</strong> Demo Teacher</p>
					<p class="edu-home-live-meta"><strong>Schedule:</strong> 4:00 PM - 6:00 PM</p>
					<p class="edu-home-live-dates"><?php echo html_escape(date('Y-m-d')); ?></p>
				</div>
			</a>
				<?php
			}
			?>
		</div>
		<!-- <p class="edu-home-muted mb-0">Sign in to join Zoom live rooms from your batch.</p> -->
	</section>
	</div>
</div>

