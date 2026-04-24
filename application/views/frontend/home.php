<?php
$home_site_logo = '';
if (!empty($site_Details) && is_array($site_Details) && !empty($site_Details[0]['site_logo'])) {
	$home_site_logo = base_url('uploads/site_data/' . $site_Details[0]['site_logo']);
}
$home_batches = (isset($batches) && is_array($batches)) ? $batches : array();
$home_inst_listing = base_url('institute/listing');
$home_batch_list = base_url('batch/list');
$home_institute_api_url = isset($home_institute_api_url) ? $home_institute_api_url : site_url('api/institute/listing');
$home_institute_details_url = isset($home_institute_details_url) ? $home_institute_details_url : site_url('institute/details');
$home_api_access_token = isset($api_access_token) ? (string) $api_access_token : '';
$home_login_url = base_url('login');
?>
<style>
/* Home hero — matches landing banner (promo cards + search) */
.edu-home-page { overflow-x: hidden; }
.edu-home-hero {
	background: #f4f6f9;
	padding: 28px 16px 32px;
	margin-bottom: 8px;
}
.edu-home-hero-inner {
	max-width: 1080px;
	margin: 0 auto;
}
.edu-home-promo-row {
	display: flex;
	flex-wrap: wrap;
	gap: 14px;
	margin-bottom: 22px;
	align-items: stretch;
}
.edu-home-promo {
	flex: 1 1 0;
	min-width: 0;
	min-height: 96px;
	border-radius: 20px;
	padding: 20px 16px;
	text-align: center;
	font-weight: 700;
	font-size: 1.05rem;
	line-height: 1.35;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 2px 14px rgba(15, 23, 42, 0.06);
	border: none;
}
.edu-home-promo--blue {
	background: #4b6cf2;
	color: #fff;
}
.edu-home-promo--outline {
	background: #fff;
	color: #111827;
	border: 1px solid #e8ecf4;
	box-shadow: 0 2px 14px rgba(15, 23, 42, 0.05);
}
.edu-home-promo--orange {
	background: #f59e0b;
	color: #fff;
	box-shadow: 0 2px 14px rgba(245, 158, 11, 0.25);
}
@media (max-width: 767px) {
	.edu-home-promo-row { flex-direction: column; }
	.edu-home-promo { flex: 1 1 auto; min-height: 84px; }
}
.edu-home-search {
	display: flex;
	align-items: stretch;
	flex-wrap: nowrap;
	background: #fff;
	border: 1px solid #e8ecf4;
	border-radius: 22px;
	overflow: hidden;
	box-shadow: 0 4px 20px rgba(15, 23, 42, 0.07);
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
	background: #3b5bdb;
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
	background: #324fd1;
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
	max-width: 1080px;
	margin: 0 auto;
	padding: 20px 16px 36px;
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
	width: 260px;
	max-width: 85vw;
	background: #fff;
	border-radius: 16px;
	padding: 16px 16px 18px;
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
	margin: 0 0 6px;
	font-size: 1rem;
	font-weight: 700;
	color: #111827;
}
.edu-home-inst-card p {
	margin: 0;
	font-size: 0.85rem;
	color: #6b7280;
	line-height: 1.4;
}
.edu-home-live-stack { display: flex; flex-direction: column; gap: 18px; }
.edu-home-live-card {
	background: #fff;
	border-radius: 18px;
	overflow: hidden;
	box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
	border: 1px solid #eef0f5;
}
.edu-home-live-thumb {
	width: 100%;
	aspect-ratio: 16 / 9;
	object-fit: cover;
	background: #eef1f8;
	display: block;
}
.edu-home-live-body { padding: 16px 16px 18px; }
.edu-home-live-body h3 {
	margin: 0 0 8px;
	font-size: 1.05rem;
	font-weight: 700;
	color: #111827;
}
.edu-home-live-date { font-size: 0.88rem; color: #6b7280; margin-bottom: 12px; }
.edu-home-tag-row { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.edu-home-tag {
	font-size: 0.72rem;
	font-weight: 600;
	padding: 5px 10px;
	border-radius: 8px;
}
.edu-home-tag--blue { background: #e8f0fe; color: #3a5fc9; }
.edu-home-tag--orange { background: #fff3e6; color: #c65f0a; }
.edu-home-btn-join {
	display: block;
	width: 100%;
	text-align: center;
	background: #4b6cf2;
	color: #fff !important;
	font-weight: 600;
	padding: 12px 16px;
	border-radius: 12px;
	text-decoration: none;
	border: 0;
	cursor: pointer;
	font-size: 0.95rem;
	transition: background 0.2s ease;
}
.edu-home-btn-join:hover { background: #3b5bdb; color: #fff !important; text-decoration: none; }
.edu-home-muted { color: #6b7280; font-size: 0.9rem; text-align: center; padding: 12px; }
</style>

<div class="edu-home-page">
	<div class="edu-home-hero">
		<div class="edu-home-hero-inner">
			<div class="edu-home-promo-row" aria-label="Promotions">
				<div class="edu-home-promo edu-home-promo--blue">IELTS Coaching</div>
				<div class="edu-home-promo edu-home-promo--outline">20% OFF</div>
				<div class="edu-home-promo edu-home-promo--orange">Join Now</div>
			</div>
			<form class="edu-home-search" id="edu_home_search_form" action="<?php echo html_escape($home_inst_listing); ?>" method="get" role="search">
				<label for="edu_home_search_q" class="sr-only">Search institutes and batches</label>
				<input type="search" name="search" id="edu_home_search_q" placeholder="Search by City, Topic, or Institute Name" autocomplete="off">
				<button type="submit">Search</button>
			</form>
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
		var apiUrl = <?php echo json_encode($home_institute_api_url); ?>;
		var token = <?php echo json_encode($home_api_access_token); ?>;
		var detailsBase = <?php echo json_encode(rtrim($home_institute_details_url, '/') . '/'); ?>;
		var listingUrl = <?php echo json_encode($home_inst_listing); ?>;
		var loginUrl = <?php echo json_encode($home_login_url); ?>;
		var row = document.getElementById('edu_home_nearby_row');
		var msg = document.getElementById('edu_home_nearby_msg');
		if (!row || !msg) { return; }
		function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
		function esc(t) {
			var d = document.createElement('div');
			d.textContent = t == null ? '' : String(t);
			return d.innerHTML;
		}
		function badgeFromPayMode(pm) {
			var s = (pm == null ? '' : String(pm)).toLowerCase();
			if (s.indexOf('online') !== -1) { return 'Online'; }
			if (s.indexOf('hybrid') !== -1) { return 'Hybrid'; }
			if (s.indexOf('offline') !== -1) { return 'Offline'; }
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
				var badge = document.createElement('span');
				badge.className = 'edu-home-badge';
				badge.textContent = badgeFromPayMode(it.pay_mode);
				var h = document.createElement('h3');
				h.textContent = it.name || 'Institute';
				var p = document.createElement('p');
				var line = addressLine(it);
				if (it.distanceKm != null && it.distanceKm !== '') {
					line = (line ? line + ' · ' : '') + it.distanceKm + ' km';
				}
				p.textContent = line || 'View details';
				a.appendChild(badge);
				a.appendChild(h);
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
					$bimg = '';
					if (!empty($hb['batch_image'])) {
						$bimg = base_url('uploads/batch_image/' . $hb['batch_image']);
					} elseif ($home_site_logo !== '') {
						$bimg = $home_site_logo;
					}
					$dt = '';
					if (!empty($hb['start_date']) && $hb['start_date'] !== '0000-00-00') {
						$ts = strtotime($hb['start_date']);
						if ($ts) {
							$dt = date('F jS, Y', $ts);
						}
					}
					if ($dt === '') {
						$dt = date('F jS, Y');
					}
					$detail_url = $bid > 0 ? base_url('courses-details/' . $bid) : base_url('courses-offered');
					?>
			<article class="edu-home-live-card">
				<?php if ($bimg !== '') { ?>
				<img class="edu-home-live-thumb" src="<?php echo html_escape($bimg); ?>" alt="">
				<?php } else { ?>
				<div class="edu-home-live-thumb" role="img" aria-label=""></div>
				<?php } ?>
				<div class="edu-home-live-body">
					<h3><?php echo html_escape($bname); ?></h3>
					<div class="edu-home-live-date"><?php echo html_escape($dt); ?></div>
					<div class="edu-home-tag-row">
						<span class="edu-home-tag edu-home-tag--blue">Batch</span>
						<span class="edu-home-tag edu-home-tag--orange">Live</span>
					</div>
					<a class="edu-home-btn-join" href="<?php echo html_escape($detail_url); ?>">Join Live Class</a>
				</div>
			</article>
					<?php
				}
			} else {
				?>
			<article class="edu-home-live-card">
				<div class="edu-home-live-thumb" style="background: linear-gradient(135deg,#e8f0fe,#fff3e6); min-height: 180px;"></div>
				<div class="edu-home-live-body">
					<h3>First Law of Motion</h3>
					<div class="edu-home-live-date"><?php echo html_escape(date('F jS, Y')); ?></div>
					<div class="edu-home-tag-row">
						<span class="edu-home-tag edu-home-tag--blue">Physics</span>
						<span class="edu-home-tag edu-home-tag--orange">12th Grade</span>
					</div>
					<a class="edu-home-btn-join" href="<?php echo html_escape(base_url('courses-offered')); ?>">Join Live Class</a>
				</div>
			</article>
				<?php
			}
			?>
		</div>
		<!-- <p class="edu-home-muted mb-0">Sign in to join Zoom live rooms from your batch.</p> -->
	</section>
	</div>
</div>

