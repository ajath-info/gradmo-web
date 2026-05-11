<div class="inst-detail-page">
	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-head"><h3>Library Notes</h3></div>
			<p class="inst-panel-intro">Share note PDFs by subject. They appear alongside library books for students.</p>
			<div class="inst-list-filter-bar">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item"><label>Title</label><input type="text" id="tn_title" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>Subject</label><select id="tn_subject" class="edu_form_field"><option value="">Select subject</option></select></div>
					<div class="inst-list-filter-item"><label>Topic</label><input type="text" id="tn_topic" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>PDF File</label><input type="file" id="tn_file" class="edu_form_field"></div>
				</div>
				<div class="inst-list-filter-actions"><button type="button" class="btn btn-primary" id="tn_add">Upload note</button></div>
			</div>
			<div id="tn_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="tn_list" class="inst-card-grid"></div>
		</div>
	</div>
</div>
<script>
(function () {
	var token=<?php echo json_encode(isset($api_access_token)?$api_access_token:''); ?>;
	var batchId=<?php echo (int)(isset($batch_id)?$batch_id:0); ?>;
	var listUrl=<?php echo json_encode(isset($notes_list_api_url)?$notes_list_api_url:''); ?>;
	var addUrl=<?php echo json_encode(isset($notes_add_api_url)?$notes_add_api_url:''); ?>;
	var delUrl=<?php echo json_encode(isset($notes_delete_api_url)?$notes_delete_api_url:''); ?>;
	var subjectsUrl=<?php echo json_encode(site_url('api/batch/batch-subjects')); ?>;
	function esc(v){var d=document.createElement('div');d.textContent=v==null?'':String(v);return d.innerHTML;}
	function msg(t,e){var x=document.getElementById('tn_msg');x.className=e?'small text-danger px-2 mb-2':'small text-success px-2 mb-2';x.textContent=t||'';}
	function loadSubjects(){fetch(subjectsUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,batch_id:batchId})}).then(function(r){return r.json();}).then(function(j){var sel=document.getElementById('tn_subject');var rows=(j.data&&j.data.subjects)?j.data.subjects:[];var html='<option value=\"\">Select subject</option>';for(var i=0;i<rows.length;i++){html+='<option value=\"'+esc(rows[i].subjectId)+'\">'+esc(rows[i].subjectName)+'</option>';}sel.innerHTML=html;});}
	function load(){fetch(listUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,batch_id:batchId,page:1,limit:200})}).then(function(r){return r.json();}).then(function(j){var rows=(j.data&&j.data.notes)?j.data.notes:[];var html='';for(var i=0;i<rows.length;i++){var r=rows[i];var sub=[r.subject,r.topic].filter(function(x){return x;}).join(' · ');html+='<div class="inst-batch-card"><div class="inst-card-body"><p class="inst-card-title-sm">'+esc(r.title)+'</p>'+(sub?'<p class="inst-card-sub">'+esc(sub)+'</p>':'')+'<div class="inst-teacher-card-actions"><a class="btn btn-sm btn-outline-primary" href="'+esc(r.downloadUrl||'#')+'" target="_blank" rel="noopener"><i class="fas fa-file-alt"></i>View file</a><button type="button" class="btn btn-sm btn-outline-danger tn_del" data-id="'+esc(r.id)+'"><i class="fas fa-trash-alt"></i>Delete</button></div></div></div>';}document.getElementById('tn_list').innerHTML=html||'<p class="inst-muted">No notes found.</p>';var b=document.querySelectorAll('.tn_del');for(var k=0;k<b.length;k++){b[k].addEventListener('click',function(){remove(this.getAttribute('data-id'));});}});}
	function remove(id){fetch(delUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,notes_id:parseInt(id,10),batch_id:batchId})}).then(function(r){return r.json();}).then(function(j){msg((j&&j.msg)||'Deleted',!(j&&(j.status==='true'||j.status===true)));load();});}
	function add(){var fd=new FormData();fd.append('access_token',token);fd.append('batch_id',batchId);fd.append('title',document.getElementById('tn_title').value.trim());var s=document.getElementById('tn_subject');fd.append('subject',s && s.value ? s.options[s.selectedIndex].text : '');fd.append('topic',document.getElementById('tn_topic').value.trim());var f=document.getElementById('tn_file').files[0];if(f){fd.append('pdf_file',f);}fetch(addUrl,{method:'POST',headers:{'Authorization':'Bearer '+token},body:fd}).then(function(r){return r.json();}).then(function(j){msg((j&&j.msg)||'Uploaded',!(j&&(j.status==='true'||j.status===true)));load();});}
	document.addEventListener('DOMContentLoaded',function(){document.getElementById('tn_add').addEventListener('click',add);loadSubjects();load();});
})();
</script>
