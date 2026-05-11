<div class="inst-detail-page">
	<div class="inst-detail-container">
		<div class="inst-detail-panel">
			<div class="inst-panel-head"><h3>Library Books</h3></div>
			<p class="inst-panel-intro">Upload PDFs for this batch. Students open them from the batch library tile.</p>
			<div class="inst-list-filter-bar">
				<div class="inst-list-filter-fields">
					<div class="inst-list-filter-item"><label>Title</label><input type="text" id="tb_title" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>Subject</label><select id="tb_subject" class="edu_form_field"><option value="">Select subject</option></select></div>
					<div class="inst-list-filter-item"><label>Topic</label><input type="text" id="tb_topic" class="edu_form_field"></div>
					<div class="inst-list-filter-item"><label>PDF File</label><input type="file" id="tb_file" class="edu_form_field"></div>
				</div>
				<div class="inst-list-filter-actions"><button type="button" class="btn btn-primary" id="tb_add">Upload book</button></div>
			</div>
			<div id="tb_msg" class="inst-muted small px-2 mb-2"></div>
			<div id="tb_list" class="inst-card-grid"></div>
		</div>
	</div>
</div>
<script>
(function () {
	var token = <?php echo json_encode(isset($api_access_token) ? $api_access_token : ''); ?>;
	var batchId = <?php echo (int) (isset($batch_id) ? $batch_id : 0); ?>;
	var listUrl = <?php echo json_encode(isset($library_list_api_url) ? $library_list_api_url : ''); ?>;
	var addUrl = <?php echo json_encode(isset($library_add_api_url) ? $library_add_api_url : ''); ?>;
	var delUrl = <?php echo json_encode(isset($library_delete_api_url) ? $library_delete_api_url : ''); ?>;
	var subjectsUrl=<?php echo json_encode(site_url('api/batch/batch-subjects')); ?>;
	var addBtn = null;
	var addBtnDefaultText = '';
	var addInFlight = false;
	function msg(t, e){var x=document.getElementById('tb_msg');x.className=e?'small text-danger px-2 mb-2':'small text-success px-2 mb-2';x.textContent=t||'';}
	function esc(v){var d=document.createElement('div');d.textContent=v==null?'':String(v);return d.innerHTML;}
	function setLoader(show){var nodes=document.querySelectorAll('.edu_preloader');Array.prototype.forEach.call(nodes,function(el){el.style.backgroundColor='rgba(255,255,255,0.80)';el.style.display=show?'block':'none';});}
	function setAddBusy(busy){
		if(!addBtn){return;}
		addInFlight = !!busy;
		addBtn.disabled = !!busy;
		addBtn.textContent = busy ? 'Uploading...' : addBtnDefaultText;
		setLoader(!!busy);
	}
	function loadSubjects(){fetch(subjectsUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,batch_id:batchId})}).then(function(r){return r.json();}).then(function(j){var sel=document.getElementById('tb_subject');var rows=(j.data&&j.data.subjects)?j.data.subjects:[];var html='<option value=\"\">Select subject</option>';for(var i=0;i<rows.length;i++){html+='<option value=\"'+esc(rows[i].subjectId)+'\">'+esc(rows[i].subjectName)+'</option>';}sel.innerHTML=html;});}
	function load(){
		fetch(listUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,batch_id:batchId,page:1,limit:200})})
		.then(function(r){return r.json();}).then(function(j){
			var rows=(j.data&&j.data.library)?j.data.library:[];
			var html='';
			for(var i=0;i<rows.length;i++){var r=rows[i];var sub=[r.subject,r.topic].filter(function(x){return x;}).join(' · ');html+='<div class="inst-batch-card"><div class="inst-card-body"><p class="inst-card-title-sm">'+esc(r.title)+'</p>'+(sub?'<p class="inst-card-sub">'+esc(sub)+'</p>':'')+'<div class="inst-teacher-card-actions"><a class="btn btn-sm btn-outline-primary" href="'+esc(r.downloadUrl||'#')+'" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i>View file</a><button type="button" class="btn btn-sm btn-outline-danger tb_del" data-id="'+esc(r.id)+'"><i class="fas fa-trash-alt"></i>Delete</button></div></div></div>';}
			document.getElementById('tb_list').innerHTML=html||'<p class="inst-muted">No books found.</p>';
			var btns=document.querySelectorAll('.tb_del');for(var k=0;k<btns.length;k++){btns[k].addEventListener('click',function(){remove(this.getAttribute('data-id'));});}
		});
	}
	function remove(id){
		fetch(delUrl,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},body:JSON.stringify({access_token:token,book_id:parseInt(id,10),batch_id:batchId})})
		.then(function(r){return r.json();}).then(function(j){msg((j&&j.msg)||'Deleted',!(j&& (j.status==='true'||j.status===true)));load();});
	}
	function add(){
		if(addInFlight){return;}
		var fd=new FormData();fd.append('access_token',token);fd.append('batch_id',batchId);fd.append('title',document.getElementById('tb_title').value.trim());var s=document.getElementById('tb_subject');fd.append('subject',s && s.value ? s.options[s.selectedIndex].text : '');fd.append('topic',document.getElementById('tb_topic').value.trim());
		var f=document.getElementById('tb_file').files[0];if(f){fd.append('pdf_file',f);}
		setAddBusy(true);
		fetch(addUrl,{method:'POST',headers:{'Authorization':'Bearer '+token},body:fd})
		.then(function(r){return r.json();})
		.then(function(j){
			var ok = !!(j && (j.status==='true'||j.status===true));
			msg((j&&j.msg)||'Uploaded',!ok);
			if(ok){
				document.getElementById('tb_title').value='';
				document.getElementById('tb_topic').value='';
				document.getElementById('tb_subject').value='';
				document.getElementById('tb_file').value='';
			}
			load();
			setAddBusy(false);
		}, function(){
			msg('Could not upload book.', true);
			setAddBusy(false);
		});
	}
	document.addEventListener('DOMContentLoaded',function(){
		addBtn=document.getElementById('tb_add');
		addBtnDefaultText=addBtn ? addBtn.textContent : 'Upload book';
		if(addBtn){addBtn.addEventListener('click',add);}
		loadSubjects();
		load();
	});
})();
</script>
