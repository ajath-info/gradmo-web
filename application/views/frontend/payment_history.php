<section class="edu_page_title_wrapper">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="edu_page_title_text">
					<h1><?php echo html_escape($this->common->languageTranslator('ltr_payment_history')); ?></h1>
				</div>
			</div>
		</div>
	</div>
</section>
<section class="edu_form_wrapper enroll-wrapper contactpage">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<p id="pay_hist_msg" class="text-muted small mb-2"></p>
				<div class="table-responsive">
					<table class="table table-striped table-bordered" id="pay_hist_table">
						<thead>
							<tr>
								<th>Batch</th>
								<th>Transaction</th>
								<th>Amount</th>
								<th>Date</th>
								<th>Invoice</th>
							</tr>
						</thead>
						<tbody id="pay_hist_rows"></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</section>
<style>
	.pay-inv-btn { display: inline-block; padding: 4px 10px; margin: 2px 4px 2px 0; border: 1px solid #2350ff; border-radius: 6px; color: #2350ff; font-size: 12px; font-weight: 600; text-decoration: none; }
	.pay-inv-btn:hover { background: #eef2ff; color: #2350ff; text-decoration: none; }
	.pay-inv-btn--dl { background: #2350ff; color: #fff; }
	.pay-inv-btn--dl:hover { background: #1a3fd6; color: #fff; }
</style>
<script>
(function () {
	var url = <?php echo json_encode(isset($payment_history_data_url) ? $payment_history_data_url : ''); ?>;
	var accessToken = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var invoiceBase = <?php echo json_encode(site_url('payment-history/invoice')); ?>;
	function ok(s) { return s === true || s === 'true' || s === 1 || s === '1'; }
	function esc(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}
	function load() {
		var msg = document.getElementById('pay_hist_msg');
		var tb = document.getElementById('pay_hist_rows');
		msg.textContent = 'Loading…';
		tb.innerHTML = '';
		// Direct API call needs the Bearer token (the API is shared with the mobile app).
		var body = { page: 1, per_page: 50 };
		if (accessToken) { body.access_token = accessToken; }
		var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
		if (accessToken) { headers.Authorization = 'Bearer ' + accessToken; }
		fetch(url, {
			method: 'POST',
			headers: headers,
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			msg.textContent = ok(j.status) ? '' : (j.msg || 'Could not load payments.');
			var rows = j.paymentData || [];
			if (!rows.length) {
				var emptyText = ok(j.status) ? (j.msg || 'No records.') : (j.msg || 'Could not load payments.');
				tb.innerHTML = '<tr><td colspan="5" class="text-muted">' + esc(emptyText) + '</td></tr>';
				return;
			}
			rows.forEach(function (p) {
				var tr = document.createElement('tr');
				tr.innerHTML =
					'<td>' + esc(p.batchName || ('#' + (p.batchId || p.batch_id || ''))) + '</td>' +
					'<td>' + esc(p.transactionId || p.transaction_id || '') + '</td>' +
					'<td>' + esc(p.amount != null ? String(p.amount) : '') + '</td>' +
					'<td>' + esc((p.createAt || p.create_at || '').toString().slice(0, 19)) + '</td>' +
					'<td class="text-nowrap">' + (p.id
						? '<a class="pay-inv-btn" href="' + esc(invoiceBase + '/' + encodeURIComponent(p.id)) + '" target="_blank" rel="noopener"><i class="fas fa-file-invoice"></i> View</a>' +
						  '<a class="pay-inv-btn pay-inv-btn--dl" href="' + esc(invoiceBase + '/' + encodeURIComponent(p.id) + '/download') + '" target="_blank" rel="noopener"><i class="fas fa-download"></i> PDF</a>'
						: '') + '</td>';
				tb.appendChild(tr);
			});
		}).catch(function () {
			msg.textContent = 'Network error.';
		});
	}
	document.addEventListener('DOMContentLoaded', load);
})();
</script>
