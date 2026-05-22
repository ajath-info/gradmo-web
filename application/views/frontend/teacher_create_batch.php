<div class="container my-5">
	<div class="row">
		<div class="col-md-8 mx-auto">
			<div class="card">
				<div class="card-header bg-primary text-white">
					<h4 class="mb-0"><?php echo isset($page_title) ? html_escape($page_title) : 'Create New Batch'; ?></h4>
				</div>
				<div class="card-body">
					<form id="batchForm" method="POST" enctype="multipart/form-data">
						<div class="form-group mb-3">
							<label for="batch_name" class="form-label">Batch Name *</label>
							<input type="text" class="form-control" id="batch_name" name="batch_name"
								value="<?php echo isset($batch_data) ? html_escape($batch_data['batch_name']) : ''; ?>"
								placeholder="Enter batch name" required>
						</div>

						<div class="form-group mb-3">
							<label for="institute_id" class="form-label">Institute</label>
							<select class="form-control" id="institute_id" name="institute_id">
								<option value="">-- Select Institute --</option>
								<?php if (!empty($institutes)): ?>
									<?php foreach ($institutes as $inst): ?>
										<option value="<?php echo (int) $inst['id']; ?>"
											<?php echo (isset($batch_data) && (int) $batch_data['institute_id'] === (int) $inst['id']) ? 'selected' : ''; ?>>
											<?php echo html_escape($inst['name']); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="form-group mb-3">
									<label for="start_date" class="form-label">Start Date *</label>
									<input type="date" class="form-control" id="start_date" name="start_date"
										value="<?php echo isset($batch_data) ? html_escape($batch_data['start_date']) : ''; ?>"
										required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group mb-3">
									<label for="end_date" class="form-label">End Date *</label>
									<input type="date" class="form-control" id="end_date" name="end_date"
										value="<?php echo isset($batch_data) ? html_escape($batch_data['end_date']) : ''; ?>"
										required>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="form-group mb-3">
									<label for="start_time" class="form-label">Start Time *</label>
									<input type="time" class="form-control" id="start_time" name="start_time"
										value="<?php echo isset($batch_data) ? html_escape(substr($batch_data['start_time'], 0, 5)) : ''; ?>"
										required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group mb-3">
									<label for="end_time" class="form-label">End Time *</label>
									<input type="time" class="form-control" id="end_time" name="end_time"
										value="<?php echo isset($batch_data) ? html_escape(substr($batch_data['end_time'], 0, 5)) : ''; ?>"
										required>
								</div>
							</div>
						</div>

						<div class="form-group mb-3">
							<label for="batch_mode" class="form-label">Mode *</label>
							<select class="form-control" id="batch_mode" name="batch_mode" required>
								<option value="">-- Select Mode --</option>
								<option value="Online" <?php echo (isset($batch_data) && $batch_data['batch_mode'] === 'Online') ? 'selected' : ''; ?>>Online</option>
								<option value="Offline" <?php echo (isset($batch_data) && $batch_data['batch_mode'] === 'Offline') ? 'selected' : ''; ?>>Offline</option>
							</select>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="form-group mb-3">
									<label for="batchType" class="form-label">Batch Type</label>
									<select class="form-control" id="batchType" name="batchType">
										<option value="1" <?php echo (!isset($batch_data) || (int) $batch_data['batch_type'] === 1) ? 'selected' : ''; ?>>Free</option>
										<option value="2" <?php echo (isset($batch_data) && (int) $batch_data['batch_type'] === 2) ? 'selected' : ''; ?>>Paid</option>
									</select>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group mb-3">
									<label for="batchPrice" class="form-label">Price (if Paid)</label>
									<input type="number" class="form-control" id="batchPrice" name="batchPrice"
										value="<?php echo isset($batch_data) ? html_escape($batch_data['batch_price']) : '0'; ?>"
										placeholder="0" min="0" step="0.01">
								</div>
							</div>
						</div>

						<div class="form-group mb-3">
							<label for="category" class="form-label">Category</label>
							<select class="form-control" id="category" name="category">
								<option value="">-- Select Category --</option>
								<?php if (!empty($categories)): ?>
									<?php foreach ($categories as $cat): ?>
										<option value="<?php echo (int) $cat['id']; ?>"
											<?php echo (isset($batch_data) && (int) $batch_data['cat_id'] === (int) $cat['id']) ? 'selected' : ''; ?>>
											<?php echo html_escape($cat['name']); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>

						<div class="form-group mb-3">
							<label for="batch_description" class="form-label">Description</label>
							<textarea class="form-control" id="batch_description" name="batch_description" rows="4"
								placeholder="Enter batch description"><?php echo isset($batch_data) ? html_escape($batch_data['description']) : ''; ?></textarea>
						</div>

						<div class="form-group mb-3">
							<label for="batch_image" class="form-label">Batch Image</label>
							<input type="file" class="form-control" id="batch_image" name="batch_image" accept="image/*">
							<?php if (isset($batch_data) && !empty($batch_data['batch_image'])): ?>
								<div id="current_image_preview" style="margin-top: 10px;">
									<small class="text-muted">Current image:</small><br>
									<img src="<?php echo base_url('uploads/batch_image/' . html_escape($batch_data['batch_image'])); ?>" alt="Current batch image" style="max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 4px;">
								</div>
							<?php endif; ?>
							<div id="image_preview" style="margin-top: 10px;"></div>
						</div>

						<input type="hidden" name="type" value="<?php echo isset($batch_data) ? 'edit' : 'add'; ?>">
						<?php if (isset($batch_data)): ?>
							<input type="hidden" name="batch_id" value="<?php echo (int) $batch_data['id']; ?>">
						<?php endif; ?>
						<input type="hidden" name="payMode" value="online">

						<div class="form-group">
							<button type="submit" class="btn btn-primary">
								<?php echo isset($batch_data) ? 'Update Batch' : 'Create Batch'; ?>
							</button>
							<a href="<?php echo base_url('batch/mylist'); ?>" class="btn btn-secondary">Cancel</a>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.getElementById('batch_image').addEventListener('change', function(e) {
	const file = e.target.files[0];
	const previewDiv = document.getElementById('image_preview');

	if (file) {
		const reader = new FileReader();
		reader.onload = function(event) {
			previewDiv.innerHTML = '<small class="text-muted">Selected image:</small><br>' +
				'<img src="' + event.target.result + '" alt="Selected batch image" style="max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 4px;">';
		};
		reader.readAsDataURL(file);
	} else {
		previewDiv.innerHTML = '';
	}
});

document.getElementById('batchForm').addEventListener('submit', function(e) {
	e.preventDefault();

	const formData = new FormData(this);
	const submitBtn = this.querySelector('button[type="submit"]');
	const originalText = submitBtn.textContent;
	submitBtn.disabled = true;
	submitBtn.textContent = 'Processing...';

	fetch('<?php echo site_url('ajaxcall/addbatch'); ?>', {
		method: 'POST',
		body: formData,
		headers: {
			'X-Requested-With': 'XMLHttpRequest'
		}
	})
	.then(response => response.json())
	.then(data => {
		submitBtn.disabled = false;
		submitBtn.textContent = originalText;

		if (data.status === '1' || data.status === 1) {
			alert('Batch <?php echo isset($batch_data) ? 'updated' : 'created'; ?> successfully!');
			window.location.href = '<?php echo base_url('batch/mylist'); ?>';
		} else {
			alert('Error: ' + (data.msg || 'Failed to <?php echo isset($batch_data) ? 'update' : 'create'; ?> batch'));
		}
	})
	.catch(error => {
		submitBtn.disabled = false;
		submitBtn.textContent = originalText;
		alert('Error: ' + error.message);
	});
});
</script>

<style>
.card {
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.card-header {
	border-bottom: 3px solid #0052cc;
	padding: 20px;
}
.form-group label {
	font-weight: 500;
	color: #333;
}
.btn {
	padding: 8px 20px;
	font-weight: 500;
}
</style>
