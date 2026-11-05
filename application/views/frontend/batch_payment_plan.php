<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=8">
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo site_url('batch/details?batch_id=' . (int) (isset($batch_id) ? $batch_id : 0)); ?>" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Payment Summary</span>
	</div>
	
	<div class="inst-detail-container">
		<div class="inst-detail-summary-card bp-summary-card">
			<h3 class="bp-title">Payment Summary</h3>
			<div id="bp_msg" class="inst-muted small mb-2"></div>
			<div id="bp_plans" class="inst-card-grid mb-2"></div>
			<div class="bp-break"></div>
			<div class="bp-row"><span>Tuition Fee</span><strong id="bp_monthly_fee">Rs 0/mo</strong></div>
			<div class="bp-row bp-row-mini"><span>x <span id="bp_months">1</span> month(s)</span><strong id="bp_subtotal_multi">Rs 0</strong></div>
			<div class="bp-break"></div>
			<div class="bp-row"><span>Batch Enrollment Fee</span><strong id="bp_enrollment_fee">Rs 499</strong></div>
			<div class="bp-row bp-row-mini"><span>(One time in 1 year)</span><span></span></div>
			<div class="bp-break"></div>
			<div class="bp-row" id="bp_disc_row" style="display:none"><span>Promo Discount</span><strong id="bp_discount">- Rs 0</strong></div>
			<div class="bp-row" id="bp_gst_row"><span>GST (<span id="bp_gst_pct">18</span>%)</span><strong id="bp_gst">Rs 0</strong></div>
			<div class="bp-row bp-grand"><span>Grand Total</span><strong id="bp_grand">Rs 0</strong></div>
			<div class="bp-row bp-row-mini"><span>(Incl. of all Taxes)</span><span></span></div>
			<div class="bp-code-wrap">
				<label for="bp_code">Promo Code</label>
				<div class="bp-code-row">
					<input id="bp_code" class="edu_form_field" placeholder="Enter Your Promo Code">
					<button type="button" id="bp_apply" class="bp-apply">Apply</button>
				</div>
			</div>
			<div class="bp-row bp-payfor"><span>Make Payment For</span><strong id="bp_payfor">Rs 0</strong></div>
			<div class="bp-row bp-row-mini"><span>(Incl. of all Taxes)</span><span></span></div>
			<button type="button" id="bp_pay" class="inst-submit-full" disabled>Make Payment</button>
		</div>
	</div>
</div>
<script>
(function () {
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var uid = <?php echo (int) (isset($batch_uid) ? $batch_uid : 0); ?>;
	var accessToken = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var plansUrl = <?php echo json_encode(isset($batch_plans_data_url) ? $batch_plans_data_url : ''); ?>;
	var promoUrl = <?php echo json_encode(isset($batch_promo_codes_data_url) ? $batch_promo_codes_data_url : ''); ?>;
	var paymentHistoryUrl = <?php echo json_encode(isset($batch_payment_history_data_url) ? $batch_payment_history_data_url : ''); ?>;
	var detailsUrl = <?php echo json_encode(isset($batch_details_data_url) ? $batch_details_data_url : ''); ?>;
	var prefetchDetails = <?php echo json_encode(isset($batch_details_prefetch) && is_array($batch_details_prefetch) ? $batch_details_prefetch : array()); ?>;
	var orderUrl = <?php echo json_encode(isset($batch_create_order_url) ? $batch_create_order_url : ''); ?>;
	var verifyUrl = <?php echo json_encode(isset($batch_verify_payment_url) ? $batch_verify_payment_url : ''); ?>;
	var successUrl = <?php echo json_encode(isset($batch_payment_success_url) ? $batch_payment_success_url : ''); ?>;
	// Free institute (users.paid = 0): no payment — show an Enroll button that enrolls directly.
	var isFreeInstitute = <?php echo (isset($is_free_institute) && $is_free_institute) ? 'true' : 'false'; ?>;
	var freeEnrollUrl = <?php echo json_encode(isset($batch_free_enroll_url) ? $batch_free_enroll_url : ''); ?>;
	var selected = null;
	var promos = [];
	var appliedPromo = null;
	var monthlyTuition = 0;
	var lastOrderId = '';
	// Application-fee breakdown (499/99 rules) from the server; when present it governs the price.
	// GST % from Common::gst_percent() — applied on the amount after promo discount (same as create-order).
	var gstPercent = <?php echo json_encode(isset($gst_percent) ? (float) $gst_percent : 18); ?>;
	var appFee = <?php echo json_encode(isset($application_fee) && is_array($application_fee) ? $application_fee : null); ?>;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function truthy(v) { return v === true || v === 'true' || v === 1 || v === '1'; }
	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
	function money(n) { return 'Rs ' + (Math.round(Number(n || 0) * 100) / 100).toFixed(2); }
	function post(url, body) {
		body = body || {};
		if (accessToken && !body.access_token) { body.access_token = accessToken; }
		var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
		if (accessToken) { headers.Authorization = 'Bearer ' + accessToken; }
		return fetch(url, {
			method: 'POST',
			headers: headers,
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); });
	}
	function planMonths() {
		if (!selected) { return 1; }
		var d = parseInt(selected.validityDays, 10) || 30;
		return Math.max(1, Math.round(d / 30));
	}
	function baseAmount() {
		if (!selected) { return 0; }
		return Number(selected.amount || 0);
	}
	function discountAmount(subTotal) {
		if (!appliedPromo) { return 0; }
		var d = (String(appliedPromo.discountType || '').toUpperCase() === 'PERCENT')
			? subTotal * Number(appliedPromo.discountValue || 0) / 100
			: Number(appliedPromo.discountValue || 0);
		// Optional cap (promo_codes.max_discount) — same rule as Common::apply_promo_to_fee().
		var cap = Number(appliedPromo.maxDiscount || 0);
		if (cap > 0 && d > cap) { d = cap; }
		return d;
	}
	// Final totals = pre-tax totals + GST on the payable amount (0 stays 0, so 100% promo is still free).
	function totals() {
		var t = preTaxTotals();
		t.taxable = t.pay;
		t.gst = Math.round(t.taxable * gstPercent) / 100;
		t.pay = Math.round((t.taxable + t.gst) * 100) / 100;
		return t;
	}
	function preTaxTotals() {
		// Application-fee rules take priority over plan/tuition pricing when available.
		// Promo discount is taken off the total; the server re-applies the same rule in create-order.
		if (appFee && Number(appFee.total) > 0) {
			var feeTotal = Number(appFee.total);
			var feeDisc = Math.round(discountAmount(feeTotal) * 100) / 100;
			feeDisc = Math.max(0, Math.min(feeDisc, feeTotal));
			var feePay = Math.round((feeTotal - feeDisc) * 100) / 100;
			// Below Rs 1 is treated as free (100% promo) — enrolled directly, no Razorpay.
			if (feePay < 1) { feePay = 0; feeDisc = feeTotal; }
			return {
				enrollment: Number(appFee.application_fee || 0), // application fee (499 / 99)
				tuition: Number(appFee.batch_fee || 0),          // monthly fee x months (0 for first time)
				disc: feeDisc,
				pay: feePay,
				months: Number(appFee.months || 1)                // plan months from the server (Yearly = 12)
			};
		}
		var months = planMonths();
		var enrollment = baseAmount();
		var tuition = monthlyTuition * months;
		var beforeDiscount = tuition + enrollment;
		var disc = discountAmount(beforeDiscount);
		if (disc > beforeDiscount) { disc = beforeDiscount; }
		var pay = Math.max(1, beforeDiscount - disc);
		return { enrollment: enrollment, tuition: tuition, disc: disc, pay: pay, months: months };
	}
	function paintSummary() {
		var t = totals();
		var monthly = (appFee && appFee.monthly_fee != null) ? Number(appFee.monthly_fee) : monthlyTuition;
		document.getElementById('bp_monthly_fee').textContent = money(monthly) + '/mo';
		document.getElementById('bp_months').textContent = t.months;
		document.getElementById('bp_subtotal_multi').textContent = money(t.tuition);
		document.getElementById('bp_enrollment_fee').textContent = money(t.enrollment);
		document.getElementById('bp_disc_row').style.display = t.disc > 0 ? '' : 'none';
		document.getElementById('bp_discount').textContent = '- ' + money(t.disc);
		document.getElementById('bp_gst_pct').textContent = String(gstPercent);
		document.getElementById('bp_gst').textContent = money(t.gst);
		document.getElementById('bp_grand').textContent = money(t.pay);
		document.getElementById('bp_payfor').textContent = money(t.pay);
		document.getElementById('bp_pay').textContent = (appliedPromo && t.pay <= 0) ? 'Enroll Now' : 'Make Payment';
		document.getElementById('bp_pay').disabled = !selected;
	}
	function renderPlans(list) {
		var root = document.getElementById('bp_plans');
		root.innerHTML = '';
		if (!list.length) {
			root.innerHTML = '<p class="inst-muted mb-0">No plans available.</p>';
			return;
		}
		list.forEach(function (p, i) {
			var a = document.createElement('a');
			a.href = 'javascript:void(0)';
			a.className = 'inst-batch-card bp-plan-card';
			a.innerHTML = '<div class="inst-card-body"><p class="inst-card-title-sm">' + esc(p.planName || 'Plan') + '</p><p class="batch-list-meta">' + esc((p.planType || '').replace('_', ' ')) + '</p><p class="inst-card-sub">' + money(p.amount || 0) + ' / ' + esc(Math.max(1, Math.round((parseInt(p.validityDays, 10) || 30) / 30))) + ' month</p></div>';
			a.style.borderColor = '#3b82f6';
			root.appendChild(a);
		});
	}
	function parseDate(s) {
		if (!s) { return null; }
		var d = new Date(String(s).replace(' ', 'T'));
		return isNaN(d.getTime()) ? null : d;
	}
	function choosePlanByHistory(plans, paymentData) {
		var now = new Date();
		var oneYearAgo = new Date(now.getTime() - (365 * 24 * 60 * 60 * 1000));
		var hasRecentPayment = false;
		(paymentData || []).forEach(function (r) {
			var dt = parseDate(r.createAt || r.create_at || '');
			if (dt && dt >= oneYearAgo) { hasRecentPayment = true; }
		});
		var requiredType = hasRecentPayment ? 'RENEWAL' : 'FIRST_PAYMENT';
		var chosen = (plans || []).find(function (p) { return String(p.planType || '').toUpperCase() === requiredType; });
		if (!chosen && plans && plans.length) { chosen = plans[0]; }
		return { chosen: chosen || null, requiredType: requiredType };
	}
	function applyPromo() {
		// Compare without spaces / case so "October 2" and "OCTOBER2" both match.
		function norm(s) { return String(s || '').replace(/\s+/g, '').toUpperCase(); }
		var code = norm(document.getElementById('bp_code').value);
		appliedPromo = null;
		if (code) {
			appliedPromo = promos.find(function (x) { return norm(x.code) === code; }) || null;
			if (!appliedPromo) {
				document.getElementById('bp_msg').textContent = 'Invalid promo code.';
				paintSummary();
				return;
			}
			document.getElementById('bp_msg').textContent = 'Promo applied: ' + code;
		} else {
			document.getElementById('bp_msg').textContent = '';
		}
		paintSummary();
	}
	function startPay() {
		if (!selected) { return; }
		var t = totals();
		document.getElementById('bp_msg').textContent = '';
		// 100% promo: nothing to pay — enroll directly (server re-validates the promo).
		if (appliedPromo && t.pay <= 0) {
			freeEnroll();
			return;
		}
		post(orderUrl, {
			amount_in_rupees: t.pay,
			promo_code: appliedPromo ? String(appliedPromo.code || '') : '',
			currency: 'INR',
			notes: { batch_id: String(batchId), plan_id: String(selected.planId || '') }
		}).then(function (j) {
			if (!ok(j.status)) { document.getElementById('bp_msg').textContent = j.msg || 'Could not create order.'; return; }
			var order = j.order || {};
			lastOrderId = order.id || '';
			var rz = new Razorpay({
				key: j.keyId || (typeof rzp_key !== 'undefined' ? rzp_key : ''),
				amount: order.amount,
				currency: order.currency || 'INR',
				name: '<?php echo html_escape($this->common->siteTitle); ?>',
				description: 'Batch payment',
				order_id: order.id,
				modal: {
					ondismiss: function () {
						document.getElementById('bp_msg').textContent = 'Payment cancelled. You can retry.';
					}
				},
				handler: function (resp) {
					post(verifyUrl, {
						razorpay_order_id: resp.razorpay_order_id,
						razorpay_payment_id: resp.razorpay_payment_id,
						razorpay_signature: resp.razorpay_signature,
						student_id: uid,
						batch_id: batchId
					}).then(function (v) {
						if (!ok(v.status)) { document.getElementById('bp_msg').textContent = v.msg || 'Verification failed.'; return; }
						window.location.href = successUrl + '?batch_id=' + encodeURIComponent(batchId) + '&payment_id=' + encodeURIComponent(resp.razorpay_payment_id) + '&order_id=' + encodeURIComponent(resp.razorpay_order_id) + '&amount=' + encodeURIComponent(((Number(order.amount) || t.pay * 100) / 100).toFixed(2));
					}).catch(function () { document.getElementById('bp_msg').textContent = 'Verification network error.'; });
				}
			});
			rz.on('payment.failed', function (resp) {
				var em = (resp && resp.error && resp.error.description) ? resp.error.description : 'Payment failed. Please try another method.';
				document.getElementById('bp_msg').textContent = em + (lastOrderId ? (' (Order: ' + lastOrderId + ')') : '');
			});
			rz.open();
		}).catch(function () { document.getElementById('bp_msg').textContent = 'Payment request failed.'; });
	}
	function freeEnroll() {
		var btn = document.getElementById('bp_pay');
		btn.disabled = true;
		btn.textContent = 'Enrolling...';
		post(freeEnrollUrl, {
			batch_id: batchId,
			promo_code: appliedPromo ? String(appliedPromo.code || '') : '',
			plan_id: selected && selected.planId ? String(selected.planId) : ''
		}).then(function (v) {
			if (!ok(v.status)) {
				document.getElementById('bp_msg').textContent = v.msg || 'Could not enroll.';
				btn.disabled = false;
				btn.textContent = 'Enroll Now';
				return;
			}
			window.location.href = v.redirect || (successUrl + '?batch_id=' + encodeURIComponent(batchId) + '&amount=0');
		}).catch(function () {
			document.getElementById('bp_msg').textContent = 'Network error.';
			btn.disabled = false;
			btn.textContent = 'Enroll Now';
		});
	}
	function setupFreeInstitute() {
		var plansEl = document.getElementById('bp_plans');
		if (plansEl) { plansEl.style.display = 'none'; }
		var codeWrap = document.querySelector('.bp-code-wrap');
		if (codeWrap) { codeWrap.style.display = 'none'; }
		document.getElementById('bp_monthly_fee').textContent = 'Rs 0.00/mo';
		document.getElementById('bp_subtotal_multi').textContent = money(0);
		document.getElementById('bp_enrollment_fee').textContent = money(0);
		document.getElementById('bp_gst_row').style.display = 'none';
		document.getElementById('bp_grand').textContent = money(0);
		document.getElementById('bp_payfor').textContent = money(0);
		document.getElementById('bp_msg').textContent = 'This institute is free — no payment required.';
		var btn = document.getElementById('bp_pay');
		btn.textContent = 'Enroll Now';
		btn.disabled = false;
		btn.addEventListener('click', freeEnroll);
	}
	document.addEventListener('DOMContentLoaded', function () {
		if (isFreeInstitute) { setupFreeInstitute(); return; }
		var detailsPromise = (prefetchDetails && Object.keys(prefetchDetails).length)
			? Promise.resolve({ status: 'true', data: prefetchDetails })
			: post(detailsUrl, { batch_id: batchId });
		Promise.all([
			post(plansUrl, { page: 1, limit: 20 }),
			post(promoUrl, { page: 1, limit: 100 }),
			detailsPromise,
			post(paymentHistoryUrl, { page: 1, limit: 200, batch_id: batchId })
		]).then(function (res) {
			if (!ok(res[0].status)) { document.getElementById('bp_msg').textContent = res[0].msg || 'Could not load plans.'; return; }
			promos = (ok(res[1].status) && res[1].data && res[1].data.promoCodes) ? res[1].data.promoCodes : [];
			var bd = {};
			if (ok(res[2].status)) {
				if (res[2].data && typeof res[2].data === 'object') {
					bd = res[2].data;
				} else if (res[2].batch_details && typeof res[2].batch_details === 'object') {
					bd = res[2].batch_details;
				}
			}
			if (bd && truthy(bd.enrollmentClosed)) {
				document.getElementById('bp_msg').textContent = 'This batch has expired. Enrollment is closed.';
				document.getElementById('bp_pay').disabled = true;
				document.getElementById('bp_pay').textContent = 'Batch Expired';
				return;
			}
			if (bd && Object.prototype.hasOwnProperty.call(bd, 'canEnroll') && !truthy(bd.canEnroll)) {
				document.getElementById('bp_msg').textContent = 'This batch is already paid/enrolled. You cannot pay again.';
				document.getElementById('bp_pay').disabled = true;
				document.getElementById('bp_pay').textContent = 'Already Enrolled';
				return;
			}
			var offer = Number(bd.batch_offer_price || 0);
			var regular = Number(bd.batch_price || 0);
			// Batch fee = offer price (final selling price) when set and below the regular price,
			// otherwise the regular batch price. Same rule as Common::compute_application_fee().
			monthlyTuition = Math.max(0, (offer > 0 && (regular <= 0 || offer < regular)) ? offer : regular);
			var plans = (res[0].data && res[0].data.plans) ? res[0].data.plans : [];
			var payRows = (ok(res[3].status) && res[3].paymentData) ? res[3].paymentData : [];
			var decision = choosePlanByHistory(plans, payRows);
			selected = decision.chosen;
			if (!selected) { document.getElementById('bp_msg').textContent = 'No plan available.'; return; }
			renderPlans([selected]);
			document.getElementById('bp_msg').textContent = 'Selected plan type: ' + decision.requiredType.replace('_', ' ');
			paintSummary();
		}).catch(function () { document.getElementById('bp_msg').textContent = 'Network error.'; });
		document.getElementById('bp_apply').addEventListener('click', applyPromo);
		document.getElementById('bp_pay').addEventListener('click', startPay);
	});
})();
</script>
