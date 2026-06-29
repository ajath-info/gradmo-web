<style>
.inst-upload-progress{margin:10px 8px 14px;padding:10px 12px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff}
.inst-upload-progress.is-hidden{display:none}
.inst-upload-progress-bar{width:100%;height:10px;border-radius:999px;background:#e5edf8;overflow:hidden}
.inst-upload-progress-fill{display:block;height:100%;width:0;background:linear-gradient(90deg,#2563eb,#38bdf8);transition:width .2s ease}
.inst-upload-progress-text{margin-top:8px;font-size:.84rem;font-weight:600;color:#334155}
</style>
<div class="inst-detail-page">
	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-head"><h3>Homework</h3></div>
			<p class="inst-panel-intro">Create assignments by subject and date. Add instructions and/or attach a PDF handout. Students see homework on the batch homework page.</p>
			<div class="inst-list-filter-bar inst-list-filter-bar--secondary">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item"><label>Filter by date</label><input type="date" id="th_filter_date" class="edu_form_field"><button type="button" class="btn btn-sm btn-outline-secondary" id="th_filter_clear">Show all</button></div>
				</div>
			</div>
			<div class="inst-list-filter-bar">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item"><label>Subject</label><select id="th_subject_id" class="edu_form_field"><option value="">Select subject</option></select></div>
					<div class="inst-list-filter-item"><label>Assignment date</label><input type="date" id="th_date" class="edu_form_field"></div>
					<div class="inst-list-filter-item inst-list-filter-grow"><label>Description</label><textarea id="th_desc" class="edu_form_field" rows="3" placeholder="Instructions for students (optional if you attach a PDF)"></textarea></div>
					<div class="inst-list-filter-item"><label>Handout PDF (optional)</label><input type="file" id="th_pdf" class="edu_form_field" accept=".pdf,application/pdf"></div>
				</div>
				<div class="inst-list-filter-actions"><button type="button" class="btn btn-primary" id="th_add">Add homework</button></div>
			</div>
			<div id="th_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="th_progress" class="inst-upload-progress is-hidden" aria-live="polite">
				<div class="inst-upload-progress-bar"><span id="th_progress_fill" class="inst-upload-progress-fill"></span></div>
				<div id="th_progress_text" class="inst-upload-progress-text">Preparing upload...</div>
			</div>
			<div id="th_list" class="inst-card-grid"></div>
		</div>
	</div>
</div>
<script>
(function () {
	var token=<?php echo json_encode(isset($api_access_token)?$api_access_token:''); ?>;
	var batchId=<?php echo (int)(isset($batch_id)?$batch_id:0); ?>;
	var listUrl=<?php echo json_encode(isset($homework_list_api_url)?$homework_list_api_url:''); ?>;
	var subjectsUrl=<?php echo json_encode(isset($batch_subjects_api_url)?$batch_subjects_api_url:''); ?>;
	var addUrl=<?php echo json_encode(isset($homework_add_api_url)?$homework_add_api_url:''); ?>;
	var delUrl=<?php echo json_encode(isset($homework_delete_api_url)?$homework_delete_api_url:''); ?>;
	var submissionsPageUrl=<?php echo json_encode(isset($homework_submissions_page_url)?$homework_submissions_page_url:''); ?>;
	var addBtn=null;
	var addBtnDefaultText='';
	var addInFlight=false;
	var progressWrap=null;
	var progressFill=null;
	var progressText=null;
	function esc(v){var d=document.createElement('div');d.textContent=v==null?'':String(v);return d.innerHTML;}
	function msg(t,e){var x=document.getElementById('th_msg');x.className=e?'small text-danger px-2 mb-2':'small text-success px-2 mb-2';x.textContent=t||'';}
	function setLoader(show){var nodes=document.querySelectorAll('.edu_preloader');Array.prototype.forEach.call(nodes,function(el){el.style.backgroundColor='rgba(255,255,255,0.80)';el.style.display=show?'block':'none';});}
	function setProgress(percent,text){if(!progressWrap||!progressFill||!progressText){return;}progressWrap.classList.remove('is-hidden');if(percent!=null&&!isNaN(percent)){progressFill.style.width=Math.max(0,Math.min(100,percent))+'%';}progressText.textContent=text||'Uploading...';}
	function resetProgress(){if(!progressWrap||!progressFill||!progressText){return;}progressWrap.classList.add('is-hidden');progressFill.style.width='0%';progressText.textContent='Preparing upload...';}
	function setAddBusy(busy){if(!addBtn){return;}addInFlight=!!busy;addBtn.disabled=!!busy;addBtn.textContent=busy?'Saving...':addBtnDefaultText;setLoader(!!busy);if(busy){setProgress(0,'Preparing upload...');}else{resetProgress();}}
	function uploadFormData(url,fd){return new Promise(function(resolve,reject){var xhr=new XMLHttpRequest();xhr.open('POST',url,true);xhr.setRequestHeader('Authorization','Bearer '+token);xhr.upload.addEventListener('progress',function(ev){if(ev.lengthComputable){var percent=Math.round((ev.loaded/ev.total)*100);setProgress(percent,percent>=100?'Upload complete, processing on server...':('Uploading... '+percent+'%'));}else{setProgress(null,'Uploading...');}});xhr.upload.addEventListener('load',function(){setProgress(100,'Upload complete, processing on server...');});xhr.onload=function(){var body=xhr.responseText||'{}';try{resolve(JSON.parse(body));}catch(err){reject(err);}};xhr.onerror=function(){reject(new Error('Network error'));};xhr.onabort=function(){reject(new Error('Upload aborted'));};xhr.send(fd);});}
	function loadSubjects(){fetch(subjectsUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,batch_id:batchId})}).then(function(r){return r.json();}).then(function(j){var sel=document.getElementById('th_subject_id');var rows=(j.data&&j.data.subjects)?j.data.subjects:[];var html='<option value=\"\">Select subject</option>';for(var i=0;i<rows.length;i++){html+='<option value=\"'+esc(rows[i].subjectId)+'\">'+esc(rows[i].subjectName)+'</option>';}sel.innerHTML=html;});}
	function listPayload(){var body={access_token:token,batch_id:batchId,page:1,limit:500};var fd=document.getElementById('th_filter_date');if(fd&&fd.value){body.date=fd.value;}return body;}
	function load(){if(batchId<1){msg('Missing batch. Open homework from batch details.',true);document.getElementById('th_list').innerHTML='<p class="inst-muted">No batch selected.</p>';return;}fetch(listUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify(listPayload())}).then(function(r){return r.json();}).then(function(j){var rows=(j&&Array.isArray(j.homeWork))?j.homeWork:[];var total=(j&&j.pagination)?(parseInt(j.pagination.totalRecords,10)||parseInt(j.pagination.total,10)||rows.length):rows.length;if(!isNaN(total)&&total>rows.length){msg('Showing '+rows.length+' of '+total+'. Narrow with the date filter or contact support.',true);}else if(rows.length){var fd=document.getElementById('th_filter_date');msg(fd&&fd.value?('Showing '+rows.length+' for '+fd.value):('Showing all '+rows.length+' for this batch'),false);}else if(j&&j.msg){msg(j.msg,true);}else{msg('',false);}var html='';for(var i=0;i<rows.length;i++){var r=rows[i];var desc=(r.description||'').toString();var pdfUrl=(r.attachmentUrl||'').toString();var showDesc=desc!==''&&!(pdfUrl&&desc==='See attached PDF.');var pdfLink=pdfUrl?'<a class="btn btn-sm btn-outline-primary" href="'+esc(pdfUrl)+'" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i>Handout PDF</a>':'';var subsHref=submissionsPageUrl+'?homework_id='+encodeURIComponent(r.id)+'&batch_id='+encodeURIComponent(batchId);html+='<div class="inst-batch-card"><div class="inst-card-body"><p class="inst-card-title-sm">'+esc(r.subjectName||('Subject #'+r.subjectId))+'</p><p class="inst-card-sub">'+esc(r.date||'')+'</p>'+(showDesc?'<p class="inst-teacher-card-text">'+esc(desc)+'</p>':'')+'<div class="inst-teacher-card-actions">'+pdfLink+'<a class="btn btn-sm btn-success" href="'+esc(subsHref)+'"><i class="fas fa-users"></i> Submissions</a><button type="button" class="btn btn-sm btn-outline-danger th_del" data-id="'+esc(r.id)+'"><i class="fas fa-trash-alt"></i>Delete</button></div></div></div>';}document.getElementById('th_list').innerHTML=html||'<p class="inst-muted">No homework found.</p>';var b=document.querySelectorAll('.th_del');for(var k=0;k<b.length;k++){b[k].addEventListener('click',function(){remove(this.getAttribute('data-id'));});}});}
	function remove(id){fetch(delUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,homework_id:parseInt(id,10)})}).then(function(r){return r.json();}).then(function(j){msg((j&&j.msg)||'Deleted',!(j&&(j.status==='true'||j.status===true)));load();});}
	function add(){if(addInFlight){return;}var subjectId=parseInt(document.getElementById('th_subject_id').value||0,10);if(!subjectId){msg('Please select subject.',true);return;}var f=document.getElementById('th_pdf').files[0];var desc=document.getElementById('th_desc').value.trim();if(!desc&&!f){msg('Enter a description and/or choose a PDF.',true);return;}var fd=new FormData();fd.append('access_token',token);fd.append('batch_id',batchId);fd.append('subject_id',subjectId);fd.append('date',document.getElementById('th_date').value);fd.append('description',desc);if(f){fd.append('pdf_file',f);}setAddBusy(true);uploadFormData(addUrl,fd).then(function(j){var ok=!!(j&&(j.status==='true'||j.status===true));msg((j&&j.msg)||'Added',!ok);if(ok){document.getElementById('th_subject_id').value='';document.getElementById('th_pdf').value='';document.getElementById('th_desc').value='';}load();setAddBusy(false);},function(){msg('Could not add homework.',true);setAddBusy(false);});}
	document.addEventListener('DOMContentLoaded',function(){document.getElementById('th_date').value=(new Date()).toISOString().slice(0,10);addBtn=document.getElementById('th_add');addBtnDefaultText=addBtn?addBtn.textContent:'Add homework';progressWrap=document.getElementById('th_progress');progressFill=document.getElementById('th_progress_fill');progressText=document.getElementById('th_progress_text');if(addBtn){addBtn.addEventListener('click',add);}var filterDate=document.getElementById('th_filter_date');var filterClear=document.getElementById('th_filter_clear');if(filterDate){filterDate.addEventListener('change',load);}if(filterClear){filterClear.addEventListener('click',function(){if(filterDate){filterDate.value='';}load();});}loadSubjects();load();});
})();
</script>
