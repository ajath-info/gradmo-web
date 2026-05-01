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
			<div id="bp_plans" class="inst-panel-stack mb-2"></div>
			<div class="bp-break"></div>
			<div class="bp-row"><span>Tuition Fee</span><strong id="bp_monthly_fee">Rs 0/mo</strong></div>
			<div class="bp-row bp-row-mini"><span>x <span id="bp_months">1</span> month(s)</span><strong id="bp_subtotal_multi">Rs 0</strong></div>
			<div class="bp-break"></div>
			<div class="bp-row"><span>Batch Enrollment Fee</span><strong id="bp_enrollment_fee">Rs 499</strong></div>
			<div class="bp-row bp-row-mini"><span>(One time in 1 year)</span><span></span></div>
			<div class="bp-break"></div>
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
	var selected = null;
	var promos = [];
	var appliedPromo = null;
	var monthlyTuition = 0;
	var lastOrderId = '';
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
		if (String(appliedPromo.discountType || '').toUpperCase() === 'PERCENT') {
			return subTotal * Number(appliedPromo.discountValue || 0) / 100;
		}
		return Number(appliedPromo.discountValue || 0);
	}
	function totals() {
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
		document.getElementById('bp_monthly_fee').textContent = money(monthlyTuition) + '/mo';
		document.getElementById('bp_months').textContent = t.months;
		document.getElementById('bp_subtotal_multi').textContent = money(t.tuition);
		document.getElementById('bp_enrollment_fee').textContent = money(t.enrollment);
		document.getElementById('bp_grand').textContent = money(t.pay);
		document.getElementById('bp_payfor').textContent = money(t.pay);
		document.getElementById('bp_pay').textContent = 'Make Payment';
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
		var code = (document.getElementById('bp_code').value || '').trim().toUpperCase();
		appliedPromo = null;
		if (code) {
			appliedPromo = promos.find(function (x) { return String(x.code || '').toUpperCase() === code; }) || null;
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
		post(orderUrl, {
			amount_in_rupees: t.pay,
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
						window.location.href = successUrl + '?batch_id=' + encodeURIComponent(batchId) + '&payment_id=' + encodeURIComponent(resp.razorpay_payment_id) + '&order_id=' + encodeURIComponent(resp.razorpay_order_id) + '&amount=' + encodeURIComponent(t.pay.toFixed(2));
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
	document.addEventListener('DOMContentLoaded', function () {
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
			if (bd && Object.prototype.hasOwnProperty.call(bd, 'canEnroll') && !truthy(bd.canEnroll)) {
				document.getElementById('bp_msg').textContent = 'This batch is already paid/enrolled. You cannot pay again.';
				document.getElementById('bp_pay').disabled = true;
				document.getElementById('bp_pay').textContent = 'Already Enrolled';
				return;
			}
			var offer = Number(bd.batch_offer_price || 0);
			var regular = Number(bd.batch_price || 0);
			monthlyTuition = offer > 0 ? offer : regular;
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
