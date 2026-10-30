<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Payment invoice. Rendered on screen (with toolbar) and by Dompdf (for_pdf = true).
 * Layout uses tables only — Dompdf 0.8 has no flexbox/grid support.
 *
 * @var array $inv   Payment_invoice::load() result
 * @var bool  $for_pdf
 * @var array $links  download / back URLs for the on-screen toolbar
 */
$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$m = function ($n) { return 'Rs. ' . number_format((float) $n, 2); };
$s = $inv['seller'];
$st = $inv['student'];
$paid_ok = in_array($inv['status'], array('SUCCESS', 'CAPTURED', 'PAID'), true);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice <?php echo $e($inv['invoice_no']); ?></title>
<style>
	@page { margin: 22px 24px; }
	* { box-sizing: border-box; }
	body { margin: 0; font-family: "DejaVu Sans", "Segoe UI", Roboto, Arial, sans-serif; font-size: 12px; color: #1f2937; background: <?php echo $for_pdf ? '#ffffff' : '#eef2f8'; ?>; }
	.sheet { background: #fff; max-width: 820px; margin: <?php echo $for_pdf ? '0' : '24px auto'; ?>; <?php echo $for_pdf ? '' : 'box-shadow: 0 10px 30px rgba(15,23,42,.10); border-radius: 12px; overflow: hidden;'; ?> }
	table { width: 100%; border-collapse: collapse; }
	td, th { vertical-align: top; }
	.band { background: #2350ff; color: #fff; }
	.band td { padding: 22px 28px; }
	.brand-name { font-size: 22px; font-weight: bold; letter-spacing: .3px; }
	.brand-sub { font-size: 11px; opacity: .9; margin-top: 4px; line-height: 1.5; }
	/* Site logo is white on transparent with wide padding: show it on the blue band, trim the padding. */
	.logo { display: block; margin: -34px 0 -30px -14px; }
	.inv-title { font-size: 26px; font-weight: bold; letter-spacing: 3px; text-align: right; }
	.inv-no { text-align: right; font-size: 12px; margin-top: 4px; opacity: .95; }
	.pad { padding: 22px 28px 0; }
	.label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 6px; font-weight: bold; }
	.party-name { font-size: 14px; font-weight: bold; color: #111827; margin-bottom: 3px; }
	.muted { color: #6b7280; line-height: 1.6; }
	.meta td { padding: 3px 0; }
	.meta .k { color: #6b7280; width: 46%; }
	.meta .v { color: #111827; font-weight: bold; text-align: right; }
	.badge { display: <?php echo $for_pdf ? 'inline' : 'inline-block'; ?>; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; letter-spacing: .5px; }
	.badge-ok { background: #dcfce7; color: #166534; }
	.badge-other { background: #fef3c7; color: #92400e; }
	.items { margin-top: 18px; }
	.items th { background: #f1f5ff; color: #2350ff; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; text-align: left; padding: 10px 12px; border-bottom: 2px solid #2350ff; }
	.items td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
	.items .num { text-align: right; white-space: nowrap; }
	.item-title { font-weight: bold; color: #111827; }
	.item-desc { color: #6b7280; font-size: 11px; margin-top: 2px; }
	.totals td { padding: 6px 12px; }
	.totals .k { text-align: right; color: #4b5563; }
	.totals .v { text-align: right; width: 150px; white-space: nowrap; }
	.totals .disc .v { color: #16a34a; }
	.grand td { background: #2350ff; color: #fff; font-size: 14px; font-weight: bold; padding: 12px; }
	.words { margin-top: 14px; padding: 10px 12px; background: #f8fafc; border-left: 3px solid #2350ff; font-size: 11px; color: #374151; }
	.foot { padding: 18px 28px 24px; margin-top: 18px; border-top: 1px dashed #d1d5db; font-size: 10.5px; color: #6b7280; line-height: 1.6; }
	.thanks { font-size: 13px; font-weight: bold; color: #2350ff; margin-bottom: 4px; }
	.toolbar { max-width: 820px; margin: 18px auto 0; padding: 0 4px; text-align: right; }
	.btn { display: inline-block; margin-left: 8px; padding: 10px 18px; border-radius: 8px; font: 600 13px "Segoe UI", Roboto, Arial, sans-serif; text-decoration: none; cursor: pointer; border: 1px solid #2350ff; }
	.btn-primary { background: #2350ff; color: #fff; }
	.btn-ghost { background: #fff; color: #2350ff; }
	.btn-back { float: left; margin-left: 0; border-color: #cbd5e1; color: #334155; background: #fff; }
	@media (max-width: 640px) {
		.band td, .pad { padding-left: 16px; padding-right: 16px; }
		/* Only the outer two-column cells stack; nested tables (payment meta) keep their rows. */
		.stack > tbody > tr > td, .stack > tr > td { display: block; width: 100% !important; padding-right: 0 !important; padding-bottom: 14px; }
		.inv-title, .inv-no { text-align: left; }
		.sheet { margin: 12px; }
		.toolbar { padding: 0 12px; }
		.btn { padding: 9px 12px; margin-left: 4px; }
		.meta .v { word-break: break-all; }
		.totals .v, .grand td:last-child { width: 110px !important; }
	}
	/* While html2pdf captures the sheet: flat white page, fixed A4-ish width, no shadow. */
	.pdf-capture .sheet { box-shadow: none; border-radius: 0; margin: 0 auto; width: 794px; max-width: 794px; }
	@media print {
		body { background: #fff; }
		.toolbar { display: none; }
		.sheet { box-shadow: none; margin: 0; border-radius: 0; }
		.band, .grand td, .items th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
	}
</style>
</head>
<body>
<?php if (!$for_pdf) { ?>
<div class="toolbar">
	<?php if (!empty($links['back'])) { ?><a class="btn btn-back" href="<?php echo $e($links['back']); ?>">&larr; Payment History</a><?php } ?>
	<a class="btn btn-ghost" href="javascript:window.print()">Print</a>
	<?php if (!empty($links['download'])) { ?><a class="btn btn-primary" id="inv_dl" href="javascript:void(0)">Download PDF</a><?php } ?>
</div>
<?php } ?>
<div class="sheet">
	<table class="band stack">
		<tr>
			<td style="width:58%;">
				<?php if ($s['logo'] !== '') { ?><img class="logo" src="<?php echo $s['logo']; ?>" width="<?php echo (int) $s['logo_w']; ?>" height="<?php echo (int) $s['logo_h']; ?>" style="width:<?php echo (int) $s['logo_w']; ?>px; height:<?php echo (int) $s['logo_h']; ?>px;" alt="<?php echo $e($s['name']); ?>">
				<?php } else { ?><div class="brand-name"><?php echo $e($s['name']); ?></div><?php } ?>
				<div class="brand-sub">
					<?php if ($s['address'] !== '') { echo $e($s['address']) . '<br>'; } ?>
					<?php echo $e(implode('  |  ', array_filter(array($s['mobile'], $s['email'], $s['website'])))); ?>
					<?php if ($s['gstin'] !== '') { echo '<br>GSTIN: ' . $e($s['gstin']); } ?>
				</div>
			</td>
			<td style="width:42%;">
				<div class="inv-title">INVOICE</div>
				<div class="inv-no"># <?php echo $e($inv['invoice_no']); ?></div>
				<div class="inv-no"><?php echo $e($inv['date_label']); ?></div>
			</td>
		</tr>
	</table>

	<div class="pad">
		<table class="stack">
			<tr>
				<td style="width:50%; padding-right:18px;">
					<div class="label">Billed To</div>
					<div class="party-name"><?php echo $e($st['name'] !== '' ? $st['name'] : 'Student'); ?></div>
					<div class="muted">
						<?php if ($st['enrollment_id'] !== '') { echo 'Enrollment ID: ' . $e($st['enrollment_id']) . '<br>'; } ?>
						<?php if ($st['mobile'] !== '') { echo $e($st['mobile']) . '<br>'; } ?>
						<?php if ($st['email'] !== '') { echo $e($st['email']) . '<br>'; } ?>
						<?php if ($st['address'] !== '') { echo $e($st['address']); } ?>
					</div>
				</td>
				<td style="width:50%;">
					<div class="label">Payment Details</div>
					<table class="meta">
						<tr><td class="k">Status</td><td class="v"><span class="badge <?php echo $paid_ok ? 'badge-ok' : 'badge-other'; ?>"><?php echo $e($paid_ok ? 'PAID' : $inv['status']); ?></span></td></tr>
						<tr><td class="k">Payment Mode</td><td class="v"><?php echo $e($inv['mode_label']); ?></td></tr>
						<?php if ($inv['transaction_id'] !== '' && strpos($inv['transaction_id'], 'PROMO-') !== 0) { ?>
						<tr><td class="k">Transaction ID</td><td class="v"><?php echo $e($inv['transaction_id']); ?></td></tr>
						<?php } ?>
						<?php if ($inv['order_id'] !== '') { ?>
						<tr><td class="k">Order ID</td><td class="v"><?php echo $e($inv['order_id']); ?></td></tr>
						<?php } ?>
						<?php if ($inv['batch']['institute'] !== '') { ?>
						<tr><td class="k">Institute</td><td class="v"><?php echo $e($inv['batch']['institute']); ?></td></tr>
						<?php } ?>
					</table>
				</td>
			</tr>
		</table>

		<table class="items">
			<thead>
				<tr>
					<th style="width:8%;">#</th>
					<th>Description</th>
					<th class="num" style="width:26%; text-align:right;">Amount</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($inv['items'] as $i => $it) { ?>
				<tr>
					<td><?php echo $i + 1; ?></td>
					<td>
						<div class="item-title"><?php echo $e($it['title']); ?></div>
						<div class="item-desc"><?php echo $e($it['desc']); ?></div>
					</td>
					<td class="num"><?php echo $m($it['amount']); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>

		<table class="totals" style="margin-top:8px;">
			<tr><td class="k">Subtotal</td><td class="v"><?php echo $m($inv['subtotal']); ?></td></tr>
			<?php if ($inv['discount'] > 0) { ?>
			<tr class="disc"><td class="k">Promo Discount<?php echo $inv['promo_code'] !== '' ? ' (' . $e($inv['promo_code']) . ')' : ''; ?></td><td class="v">- <?php echo $m($inv['discount']); ?></td></tr>
			<?php } ?>
			<tr><td class="k">Taxable Amount</td><td class="v"><?php echo $m($inv['taxable']); ?></td></tr>
			<tr><td class="k">GST @ <?php echo $e(rtrim(rtrim(number_format($inv['gst_rate'], 2), '0'), '.')); ?>%</td><td class="v"><?php echo $m($inv['gst']); ?></td></tr>
		</table>
		<table style="margin-top:6px;">
			<tr class="grand"><td style="text-align:right;">Total Paid</td><td style="text-align:right; width:150px; white-space:nowrap;"><?php echo $m($inv['total']); ?></td></tr>
		</table>
		<div class="words"><strong>Amount in words:</strong> <?php echo $e($inv['total_words']); ?></div>
	</div>

	<div class="foot">
		<div class="thanks">Thank you for learning with <?php echo $e($s['name']); ?>!</div>
		Amounts are inclusive of GST. This is a computer-generated invoice and does not require a signature.
		<?php if ($s['email'] !== '') { ?>For any query, write to <?php echo $e($s['email']); ?>.<?php } ?>
	</div>
</div>
<?php if (!$for_pdf) { ?><div style="height:24px;"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(function () {
	// Client-side PDF of the invoice sheet (A4). Same markup as the screen view.
	var fileName = <?php echo json_encode($inv['invoice_no'] . '.pdf'); ?>;
	var btn = document.getElementById('inv_dl');
	function save() {
		if (typeof html2pdf === 'undefined') { window.print(); return; }
		var sheet = document.querySelector('.sheet');
		if (btn) { btn.textContent = 'Preparing PDF…'; }
		document.body.classList.add('pdf-capture');
		html2pdf().set({
			margin: [6, 6, 6, 6],
			filename: fileName,
			image: { type: 'jpeg', quality: 0.98 },
			html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
			jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
			pagebreak: { mode: ['avoid-all'] }
		}).from(sheet).save().then(function () {
			document.body.classList.remove('pdf-capture');
			if (btn) { btn.textContent = 'Download PDF'; }
		});
	}
	if (btn) { btn.addEventListener('click', save); }
	<?php if (!empty($links['autodownload'])) { ?>
	window.addEventListener('load', function () { setTimeout(save, 300); });
	<?php } ?>
})();
</script>
<?php } ?>
</body>
</html>
