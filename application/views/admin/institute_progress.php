<section class="edu_admin_content">
	<div class="edu_admin_right sectionHolder">
		<div class="edu_main_wrapper edu_table_wrapper">
			<div class="edu_admin_informationdiv sectionHolder">
				<?php if (!empty($institute_data)) { $inst = $institute_data[0]; ?>
					<h4 class="edu_sub_title"><?php echo html_escape($inst['name']);?></h4>
					<div class="table-responsive">
						<table class="table table-striped table-hover">
							<tr><th>Email</th><td><?php echo html_escape($inst['email']);?></td></tr>
							<tr><th>Country</th><td><?php echo html_escape(isset($inst['country']) ? $inst['country'] : '');?></td></tr>
							<tr><th>State</th><td><?php echo html_escape(isset($inst['state']) ? $inst['state'] : '');?></td></tr>
							<tr><th>City</th><td><?php echo html_escape(isset($inst['city']) ? $inst['city'] : '');?></td></tr>
							<tr><th>Pincode</th><td><?php echo html_escape(isset($inst['pincode']) ? $inst['pincode'] : '');?></td></tr>
							<tr><th>Address</th><td><?php echo html_escape(isset($inst['address']) ? $inst['address'] : '');?></td></tr>
							<tr><th>Latitude</th><td><?php echo html_escape(isset($inst['lat']) ? $inst['lat'] : (isset($inst['latitude']) ? $inst['latitude'] : ''));?></td></tr>
							<tr><th>Longitude</th><td><?php echo html_escape(isset($inst['long']) ? $inst['long'] : (isset($inst['longitude']) ? $inst['longitude'] : ''));?></td></tr>
						</table>
					</div>
				<?php } else { ?>
					<div class="eac_text eac_page_re">Institute not found.</div>
				<?php } ?>
			</div>
		</div>
	</div>
</section>
