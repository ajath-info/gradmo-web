<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="edu_page_title_text">
					<h1>Update Profile</h1>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="edu_form_wrapper enroll-wrapper contactpage">
	<div class="container">
		<div class="row">
			<div class="col-lg-8 col-md-10 col-sm-12 col-12 mx-auto">
				<div class="edu_form_container_main edu_form_container withoutMapFrm">
					<h4 class="mb-3">Personal information</h4>
					<div class="row">
						<div class="col-md-6"><div class="edu_field_holder"><input id="up_name" class="edu_form_field" type="text" placeholder="First name" maxlength="10" value="<?php echo html_escape(isset($profile['name']) ? $profile['name'] : ''); ?>"></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><input id="up_last_name" class="edu_form_field" type="text" placeholder="Last name" maxlength="10" value="<?php echo html_escape(isset($profile['last_name']) ? $profile['last_name'] : ''); ?>"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="up_email" class="edu_form_field" type="email" placeholder="Email" value="<?php echo html_escape(isset($profile['email']) ? $profile['email'] : ''); ?>"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="up_mobile" class="edu_form_field" type="text" placeholder="Phone number" maxlength="10" inputmode="numeric" value="<?php echo html_escape(isset($profile['mobile']) ? $profile['mobile'] : ''); ?>"></div></div>
						<div class="col-12"><div class="edu_field_holder"><input id="up_address" class="edu_form_field" type="text" placeholder="Address" value="<?php echo html_escape(isset($profile['address']) ? $profile['address'] : ''); ?>"></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><select id="up_country" class="edu_form_field" aria-label="Country"><option value="">Select country</option></select></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><select id="up_state" class="edu_form_field" aria-label="State" disabled><option value="">Select state</option></select></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><select id="up_city" class="edu_form_field" aria-label="City" disabled><option value="">Select city</option></select></div></div>
						<div class="col-md-6"><div class="edu_field_holder"><input id="up_pincode" class="edu_form_field" type="text" placeholder="Pincode" value="<?php echo html_escape(isset($profile['pincode']) ? $profile['pincode'] : ''); ?>"></div></div>
					</div>
					<h4 class="mt-4 mb-3">Educational information</h4>
					<div class="row">
						<div class="col-12"><div class="edu_field_holder"><input id="up_school_college" class="edu_form_field" type="text" placeholder="School / college name" value="<?php echo html_escape(isset($profile['school_college_name']) ? $profile['school_college_name'] : ''); ?>"></div></div>
						<?php if (! empty($profile['show_grade_field'])) { ?>
						<div class="col-12"><div class="edu_field_holder"><input id="up_grade" class="edu_form_field" type="text" placeholder="Grade" value="<?php echo html_escape(isset($profile['grade']) ? $profile['grade'] : ''); ?>"></div></div>
						<?php } ?>
					</div>
					<div class="row mt-3">
						<div class="col-12 text-md-right"><button id="up_submit_btn" type="button" class="edu_btn edu_btn_black">Save</button></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
(function () {
	var url = <?php echo json_encode(site_url('update-profile-submit')); ?>;
	var urlCountry = <?php echo json_encode(site_url('api/main/country-list')); ?>;
	var urlState = <?php echo json_encode(site_url('api/main/state-list')); ?>;
	var urlCity = <?php echo json_encode(site_url('api/main/city-list')); ?>;
	var bootGeo = <?php echo json_encode(array(
		'country' => isset($profile['country']) ? (string) $profile['country'] : '',
		'state' => isset($profile['state']) ? (string) $profile['state'] : '',
		'city' => isset($profile['city']) ? (string) $profile['city'] : '',
	)); ?>;
	var showGradeField = <?php echo ! empty($profile['show_grade_field']) ? 'true' : 'false'; ?>;
	function ok(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	function apiPostJson(u, body) {
		return fetch(u, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: JSON.stringify(body || {})
		}).then(function (r) { return r.json(); });
	}
	function selectOptionByLabel(sel, label) {
		if (!sel || !label) { return ''; }
		var t = String(label).trim().toLowerCase();
		if (t === '') { return ''; }
		for (var i = 0; i < sel.options.length; i++) {
			var o = sel.options[i];
			if (!o.value) { continue; }
			if (String(o.textContent).trim().toLowerCase() === t) {
				sel.selectedIndex = i;
				return o.value;
			}
		}
		return '';
	}
	function selectedOptionText(sel) {
		if (!sel || sel.selectedIndex < 0) { return ''; }
		var o = sel.options[sel.selectedIndex];
		if (!o || !o.value) { return ''; }
		return String(o.textContent || '').trim();
	}
	function loadCountries() {
		var sel = document.getElementById('up_country');
		return apiPostJson(urlCountry, { limit: 500 }).then(function (j) {
			sel.innerHTML = '<option value="">Select country</option>';
			if (ok(j.status) && j.countries && j.countries.length) {
				j.countries.forEach(function (c) {
					var o = document.createElement('option');
					o.value = String(c.id);
					o.textContent = c.name || '';
					sel.appendChild(o);
				});
			}
		});
	}
	function loadStates(countryId) {
		var selS = document.getElementById('up_state');
		var selC = document.getElementById('up_city');
		selC.innerHTML = '<option value="">Select city</option>';
		selC.disabled = true;
		var id = parseInt(countryId, 10);
		if (!id) {
			selS.innerHTML = '<option value="">Select state</option>';
			selS.disabled = true;
			return Promise.resolve();
		}
		selS.disabled = false;
		return apiPostJson(urlState, { country_id: id, limit: 500 }).then(function (j) {
			selS.innerHTML = '<option value="">Select state</option>';
			if (ok(j.status) && j.states && j.states.length) {
				j.states.forEach(function (s) {
					var o = document.createElement('option');
					o.value = String(s.id);
					o.textContent = s.name || '';
					selS.appendChild(o);
				});
			}
		});
	}
	function loadCities(stateId) {
		var selC = document.getElementById('up_city');
		var id = parseInt(stateId, 10);
		if (!id) {
			selC.innerHTML = '<option value="">Select city</option>';
			selC.disabled = true;
			return Promise.resolve();
		}
		selC.disabled = false;
		return apiPostJson(urlCity, { state_id: id, limit: 500 }).then(function (j) {
			selC.innerHTML = '<option value="">Select city</option>';
			if (ok(j.status) && j.cities && j.cities.length) {
				j.cities.forEach(function (c) {
					var o = document.createElement('option');
					o.value = String(c.id);
					o.textContent = c.city || '';
					selC.appendChild(o);
				});
			}
		});
	}
	function bootstrapGeoCascade() {
		var wantC = (bootGeo.country || '').trim();
		var wantS = (bootGeo.state || '').trim();
		var wantCt = (bootGeo.city || '').trim();
		return loadCountries().then(function () {
			var selC = document.getElementById('up_country');
			if (wantC) { selectOptionByLabel(selC, wantC); }
			var cid = (selC.value || '').trim();
			return loadStates(cid).then(function () {
				var selS = document.getElementById('up_state');
				if (wantS) { selectOptionByLabel(selS, wantS); }
				var sid = (selS.value || '').trim();
				return loadCities(sid).then(function () {
					var selCt = document.getElementById('up_city');
					if (wantCt) { selectOptionByLabel(selCt, wantCt); }
				});
			});
		});
	}
	document.addEventListener('DOMContentLoaded', function () {
		var btn = document.getElementById('up_submit_btn');
		var selCountry = document.getElementById('up_country');
		var selState = document.getElementById('up_state');
		var selCity = document.getElementById('up_city');
		if (!btn || !selCountry || !selState || !selCity) { return; }
		bootstrapGeoCascade().catch(function () {
			if (typeof toastr !== 'undefined') { toastr.error('Could not load location lists.'); }
		});
		selCountry.addEventListener('change', function () {
			loadStates(this.value).catch(function () {});
		});
		selState.addEventListener('change', function () {
			loadCities(this.value).catch(function () {});
		});
		btn.addEventListener('click', function () {
			var firstName = (document.getElementById('up_name').value || '').trim();
			var lastNameEl = document.getElementById('up_last_name');
			var lastName = lastNameEl ? (lastNameEl.value || '').trim() : '';
			if (!firstName) {
				if (typeof toastr !== 'undefined') { toastr.error('Please enter your first name.'); }
				return;
			}
			if (firstName.length > 10) {
				if (typeof toastr !== 'undefined') { toastr.error('First name must be at most 10 characters.'); }
				return;
			}
			
			var payload = {
				name: firstName,
				last_name: lastName,
				email: (document.getElementById('up_email').value || '').trim(),
				mobile: (document.getElementById('up_mobile').value || '').trim().replace(/\D/g, ''),
				address: (document.getElementById('up_address').value || '').trim(),
				country: selectedOptionText(selCountry),
				state: selectedOptionText(selState),
				city: selectedOptionText(selCity),
				pincode: (document.getElementById('up_pincode').value || '').trim(),
				school_college_name: (document.getElementById('up_school_college').value || '').trim()
			};
			if (showGradeField) {
				var g = document.getElementById('up_grade');
				payload.grade = g ? (g.value || '').trim() : '';
			}
			btn.disabled = true;
			var prev = btn.textContent;
			btn.textContent = 'Saving…';
			fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(payload)
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (ok(j.status)) {
					if (typeof toastr !== 'undefined') { toastr.success(j.msg || j.message || 'Profile updated'); }
					window.location.reload();
					return;
				}
				if (typeof toastr !== 'undefined') {
					toastr.error(j.msg || j.message || 'Profile update failed');
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			}).finally(function () {
				btn.disabled = false;
				btn.textContent = prev;
			});
		});
	});
})();
</script>
