<div class="inst-detail-page">
	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-head">
				<h3>Exams</h3>
				<a class="inst-see-all" href="<?php echo html_escape(isset($legacy_question_manage_url) ? $legacy_question_manage_url : '#'); ?>">Manage Questions (Legacy)</a>
			</div>
			<p class="inst-panel-intro">Schedule exam metadata here; use the legacy tool to attach questions.</p>
			<div class="inst-list-filter-bar">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item"><label>Name</label><input type="text" id="te_name" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>Total Questions</label><input type="number" id="te_q" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>Total Marks</label><input type="number" id="te_marks" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>Date</label><input type="date" id="te_date" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>Time</label><input type="text" id="te_time" class="edu_form_field" placeholder="10:00 AM"></div>
				</div>
				<div class="inst-list-filter-actions"><button type="button" class="btn btn-primary" id="te_add">Add exam</button></div>
			</div>
			<div id="te_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="te_list" class="inst-card-grid"></div>
		</div>
	</div>
</div>
<script>
(function () {
	var token=<?php echo json_encode(isset($api_access_token)?$api_access_token:''); ?>;
	var batchId=<?php echo (int)(isset($batch_id)?$batch_id:0); ?>;
	var listUrl=<?php echo json_encode(isset($exam_list_api_url)?$exam_list_api_url:''); ?>;
	var addUrl=<?php echo json_encode(isset($exam_add_api_url)?$exam_add_api_url:''); ?>;
	var delUrl=<?php echo json_encode(isset($exam_delete_api_url)?$exam_delete_api_url:''); ?>;
	var addBtn=null;
	var addBtnDefaultText='';
	var addInFlight=false;
	function esc(v){var d=document.createElement('div');d.textContent=v==null?'':String(v);return d.innerHTML;}
	function msg(t,e){var x=document.getElementById('te_msg');x.className=e?'small text-danger px-2 mb-2':'small text-success px-2 mb-2';x.textContent=t||'';}
	function setLoader(show){var nodes=document.querySelectorAll('.edu_preloader');Array.prototype.forEach.call(nodes,function(el){el.style.backgroundColor='rgba(255,255,255,0.80)';el.style.display=show?'block':'none';});}
	function setAddBusy(busy){if(!addBtn){return;}addInFlight=!!busy;addBtn.disabled=!!busy;addBtn.textContent=busy?'Saving...':addBtnDefaultText;setLoader(!!busy);}
	function load(){fetch(listUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,batch_id:batchId,page:1,limit:200})}).then(function(r){return r.json();}).then(function(j){var rows=(j.data&&j.data.upcomingExams)?j.data.upcomingExams:[];var html='';for(var i=0;i<rows.length;i++){var r=rows[i];var when=[r.scheduledDate,r.scheduledTime].filter(function(x){return x;}).join(' · ');html+='<div class="inst-batch-card"><div class="inst-card-body"><p class="inst-card-title-sm">'+esc(r.name)+'</p>'+(when?'<p class="inst-card-sub">'+esc(when)+'</p>':'')+'<p class="inst-teacher-card-text">Questions: '+esc(r.totalQuestion||0)+' · Marks: '+esc(r.totalMarks||0)+'</p><div class="inst-teacher-card-actions"><button type="button" class="btn btn-sm btn-outline-danger te_del" data-id="'+esc(r.id)+'"><i class="fas fa-trash-alt"></i>Delete</button></div></div></div>';}document.getElementById('te_list').innerHTML=html||'<p class="inst-muted">No exams found.</p>';var b=document.querySelectorAll('.te_del');for(var k=0;k<b.length;k++){b[k].addEventListener('click',function(){remove(this.getAttribute('data-id'));});}});}
	function remove(id){fetch(delUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,exam_id:parseInt(id,10)})}).then(function(r){return r.json();}).then(function(j){msg((j&&j.msg)||'Deleted',!(j&&(j.status==='true'||j.status===true)));load();});}
	function add(){if(addInFlight){return;}setAddBusy(true);fetch(addUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,batch_id:batchId,name:document.getElementById('te_name').value.trim(),total_question:parseInt(document.getElementById('te_q').value||0,10),total_marks:parseFloat(document.getElementById('te_marks').value||0),mock_sheduled_date:document.getElementById('te_date').value,mock_sheduled_time:document.getElementById('te_time').value,time_duration:'60'})}).then(function(r){return r.json();}).then(function(j){var ok=!!(j&&(j.status==='true'||j.status===true));msg((j&&j.msg)||'Added',!ok);if(ok){document.getElementById('te_name').value='';document.getElementById('te_q').value='';document.getElementById('te_marks').value='';document.getElementById('te_time').value='';}load();setAddBusy(false);},function(){msg('Could not add exam.',true);setAddBusy(false);});}
	document.addEventListener('DOMContentLoaded',function(){document.getElementById('te_date').value=(new Date()).toISOString().slice(0,10);addBtn=document.getElementById('te_add');addBtnDefaultText=addBtn?addBtn.textContent:'Add exam';if(addBtn){addBtn.addEventListener('click',add);}load();});
})();
</script>
