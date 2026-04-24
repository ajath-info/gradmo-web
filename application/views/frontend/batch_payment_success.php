<link rel="stylesheet" href="<?php echo base_url('assets/css/institute-frontend.css'); ?>?v=7">
<div class="inst-detail-page">
	<div class="inst-detail-mobile-bar">
		<a class="inst-back" href="<?php echo site_url('batch/details?batch_id=' . (int) (isset($batch_id) ? $batch_id : 0)); ?>" aria-label="Back"><i class="fas fa-arrow-left"></i></a>
		<span class="inst-detail-mobile-title">Payment Success</span>
	</div>
	
	<div class="inst-detail-container">
		<div class="inst-detail-summary-card text-center">
			<div style="font-size:56px;color:#3b82f6;line-height:1;">✓</div>
			<h3 class="mt-2 mb-2">Successful purchase!</h3>
			<p class="inst-muted mb-3">We have received your payment request.</p>
			<p class="batch-list-meta mb-1"><strong>Amount:</strong> Rs <?php echo html_escape(isset($amount) ? $amount : ''); ?></p>
			<p class="batch-list-meta mb-1"><strong>Payment ID:</strong> <?php echo html_escape(isset($payment_id) ? $payment_id : ''); ?></p>
			<p class="batch-list-meta mb-3"><strong>Order ID:</strong> <?php echo html_escape(isset($order_id) ? $order_id : ''); ?></p>
			<a class="inst-submit-full d-inline-block text-center" href="<?php echo site_url('batch/details?batch_id=' . (int) (isset($batch_id) ? $batch_id : 0)); ?>" style="text-decoration:none;max-width:280px;">Start learning</a>
		</div>
	</div>
</div>
