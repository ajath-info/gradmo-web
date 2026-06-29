<script>
jQuery(function ($) {
	var $root = $('#teacherBatchFormRoot');
	if (!$root.length) {
		return;
	}

	// auth-select must not wrap batch form dropdowns (select2 is used, same as admin).
	$root.find('.edu-auth-select').each(function () {
		var $wrap = $(this);
		var $sel = $wrap.find('select.edu-auth-select-native').first();
		if ($sel.length) {
			$sel.insertBefore($wrap);
			$sel.removeClass('edu-auth-select-native');
			$sel.removeAttr('data-edu-auth-select-built');
			$wrap.remove();
		}
	});

	var $plainTopSelects = $root.find('#category_dropdown, #subcategory_dropdown, #institute_id');
	var tcbSubjectsCache = [];

	function destroySelect2IfAny($el) {
		if (!$el || !$el.length) {
			return;
		}
		if ($el.hasClass('select2-hidden-accessible') && typeof $.fn.select2 === 'function') {
			try {
				$el.select2('destroy');
			} catch (e) { /* ignore */ }
		}
		$el.removeClass('select2-hidden-accessible');
		$el.css({
			position: '',
			width: '100%',
			height: '',
			clip: '',
			clipPath: '',
			overflow: '',
			opacity: '',
			left: '',
			top: '',
			display: ''
		});
	}

	function cleanupTopSelectDuplicates() {
		$plainTopSelects.each(function () {
			var $sel = $(this);
			destroySelect2IfAny($sel);
			$sel.siblings('.select2-container').remove();
			$sel.next('.select2-container').remove();
			$sel.prev('.select2-container').remove();
			$sel.show();
		});
	}

	function initPlainTopSelects() {
		cleanupTopSelectDuplicates();
	}

	// custom.js may have applied select2 before this script; remove duplicate widgets immediately.
	cleanupTopSelectDuplicates();

	function initSelect2In($scope) {
		if (typeof $.fn.select2 !== 'function') {
			return;
		}
		$scope.find('.edu_selectbox_with_search').not('#category_dropdown, #subcategory_dropdown, #institute_id, .tcb-native-select').each(function () {
			var $el = $(this);
			if ($el.hasClass('select2-hidden-accessible')) {
				return;
			}
			$el.select2({
				placeholder: $el.attr('data-placeholder') || '',
				width: '100%',
				dropdownParent: $root
			});
		});
	}

	function fillSelectOptions($select, items, placeholder, valueKey, labelKey, selectedId) {
		var html = '<option value="">' + (placeholder || '') + '</option>';
		if (items && items.length) {
			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				var val = String(item[valueKey] != null ? item[valueKey] : item.id);
				var label = item[labelKey] != null ? item[labelKey] : (item.name || '');
				var sel = (selectedId && String(selectedId) === val) ? ' selected' : '';
				html += '<option value="' + val + '"' + sel + '>' + label + '</option>';
			}
		}
		$select.html(html);
	}

	function initBatchDatepickers() {
		if (typeof $.fn.datepicker !== 'function') {
			return;
		}
		var $start = $root.find('#b_startDate');
		var $end = $root.find('#b_endDate');
		if (!$start.length || !$end.length) {
			return;
		}
		if (!$start.hasClass('hasDatepicker')) {
			$start.datepicker({
				dateFormat: 'dd-mm-yy',
				changeMonth: true,
				changeYear: true,
				yearRange: 'c-30:c+15',
				onSelect: function () {
					var minDate = $start.datepicker('getDate');
					$root.find('.edu_accordion_container .chooseDate').datepicker('option', 'minDate', minDate);
					$end.datepicker('option', 'minDate', minDate);
				}
			});
		}
		if (!$end.hasClass('hasDatepicker')) {
			$end.datepicker({
				dateFormat: 'dd-mm-yy',
				changeMonth: true,
				changeYear: true,
				yearRange: 'c-30:c+15',
				onSelect: function () {
					var maxDate = $end.datepicker('getDate');
					$root.find('.edu_accordion_container .chooseDate').datepicker('option', 'maxDate', maxDate);
				}
			});
		}
		$root.find('.chooseDate').not('#b_startDate, #b_endDate').each(function () {
			var $el = $(this);
			if (!$el.hasClass('hasDatepicker')) {
				$el.datepicker({
					changeMonth: true,
					changeYear: true,
					dateFormat: 'dd-mm-yy',
					yearRange: 'c-30:c+15'
				});
			}
		});
	}

	function initBatchClockpicker() {
		if (typeof $.fn.clockpicker !== 'function') {
			return;
		}
		$root.find('.chooseTime').each(function () {
			var $wrap = $(this);
			if ($wrap.data('clockpicker')) {
				return;
			}
			$wrap.clockpicker({
				placement: 'top',
				align: 'left',
				donetext: 'Done',
				twelvehour: true,
				autoclose: false,
				leadingZeroHours: false,
				upperCaseAmPm: true,
				leadingSpaceAmPm: true
			});
		});
	}

	function openBenefitPanel($parent) {
		if (!$parent || !$parent.length) {
			return;
		}
		var $head = $parent.children('span').first();
		$head.addClass('active');
		$head.find('.upDownI').removeClass('fa-angle-right').addClass('fa-angle-down');
		$parent.children('.edu_accordion_content').stop(true, true).slideDown(200);
	}

	function openSubjectPanel($parent) {
		openBenefitPanel($parent);
	}

	function normalizeSubjectsForForm(rows) {
		var out = [];
		if (!rows || !rows.length) {
			return out;
		}
		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			var sid = r.subjectId != null ? r.subjectId : r.id;
			var sname = r.subjectName != null ? r.subjectName : (r.subject_name != null ? r.subject_name : r.name);
			out.push({
				id: sid,
				subjectId: sid,
				subject_name: sname,
				subjectName: sname
			});
		}
		return out;
	}

	function applySubjectsToRows() {
		var placeholder = (typeof ltr_select_subject !== 'undefined') ? ltr_select_subject : 'Select subject';
		$root.find('.filter_subject').each(function () {
			var $sel = $(this);
			var selected = $sel.attr('data-selected') || $sel.val();
			fillSelectOptions($sel, tcbSubjectsCache, placeholder, 'subjectId', 'subjectName', selected);
			if (selected) {
				$sel.val(String(selected));
			}
			destroySelect2IfAny($sel);
			if (typeof $.fn.select2 === 'function') {
				$sel.select2({
					placeholder: $sel.attr('data-placeholder') || placeholder,
					width: '100%',
					dropdownParent: $root
				});
			}
		});
		$root.find('.subjectArrayDiv').text(JSON.stringify(tcbSubjectsCache));
	}

	function loadChaptersForSubject($subjectSelect) {
		var subjectId = $subjectSelect.val();
		var $row = $subjectSelect.closest('.edu_accord_parent');
		var $chapter = $row.find('.filter_chapter');
		var $teacher = $row.find('.filter_teacher');
		if (!subjectId) {
			return;
		}

		var chapterPh = (typeof ltr_select_chapter !== 'undefined') ? ltr_select_chapter : 'Select chapter';
		var teacherPh = (typeof ltr_select_teacher !== 'undefined') ? ltr_select_teacher : 'Select teacher';
		$chapter.html('<option value="">' + (typeof ltr_loading_msg !== 'undefined' ? ltr_loading_msg : 'Loading') + '...</option>');

		tcbApiPost('api/batch/teacher-batch-subject-chapters', { subjectId: subjectId }).done(function (resp) {
			if (!resp || resp.status !== 'true' || !resp.data) {
				if (typeof toastr !== 'undefined') {
					toastr.error((resp && resp.msg) ? resp.msg : (typeof ltr_something_msg !== 'undefined' ? ltr_something_msg : 'Error'));
				}
				return;
			}
			var chapters = resp.data.chapters || [];
			var html = '<option value="">' + chapterPh + '</option>';
			for (var i = 0; i < chapters.length; i++) {
				html += '<option value="' + chapters[i].id + '">' + chapters[i].name + '</option>';
			}
			destroySelect2IfAny($chapter);
			$chapter.html(html);
			if (typeof $.fn.select2 === 'function') {
				$chapter.select2({
					placeholder: $chapter.attr('data-placeholder') || chapterPh,
					width: '100%',
					dropdownParent: $root
				});
			}

			if ($teacher.length && resp.data.teachers && resp.data.teachers.length) {
				var tHtml = '<option value="">' + teacherPh + '</option>';
				for (var t = 0; t < resp.data.teachers.length; t++) {
					tHtml += '<option value="' + resp.data.teachers[t].id + '">' + resp.data.teachers[t].name + '</option>';
				}
				destroySelect2IfAny($teacher);
				$teacher.html(tHtml);
				if (typeof $.fn.select2 === 'function') {
					$teacher.select2({
						placeholder: $teacher.attr('data-placeholder') || teacherPh,
						width: '100%',
						dropdownParent: $root
					});
				}
			}

			$subjectSelect.closest('.edu_accord_parent').find('.edu_accordion_header span.subjects_name').text(
				$subjectSelect.find('option:selected').text()
			);
		});
	}

	<?php if (!empty($load_batch_dropdowns_via_api) && !empty($api_access_token)) { ?>
	var tcbApiToken = <?php echo json_encode($api_access_token); ?>;
	var tcbSubjectsUrl = <?php echo json_encode(!empty($batch_subjects_api_url) ? $batch_subjects_api_url : site_url('api/batch/batch-subjects')); ?>;
	var tcbCategoriesUrl = <?php echo json_encode(!empty($batch_categories_api_url) ? $batch_categories_api_url : site_url('api/batch/categories')); ?>;
	var tcbSubcategoriesUrl = <?php echo json_encode(!empty($batch_subcategories_api_url) ? $batch_subcategories_api_url : site_url('api/batch/subcategories')); ?>;

	function tcbApiPost(url, body) {
		var payload = body || {};
		payload.access_token = tcbApiToken;
		return $.ajax({
			method: 'POST',
			url: base_url + url,
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify(payload)
		});
	}

	function loadCategories() {
		var $cat = $root.find('#category_dropdown');
		var selected = parseInt($cat.attr('data-selected') || '0', 10) || 0;
		var ph = $cat.attr('data-placeholder') || 'Select category';
		$cat.html('<option value="">' + (typeof ltr_loading_msg !== 'undefined' ? ltr_loading_msg : 'Loading') + '...</option>');

		return $.ajax({
			method: 'POST',
			url: tcbCategoriesUrl,
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify({ access_token: tcbApiToken })
		}).done(function (resp) {
			if (!resp || resp.status !== 'true') {
				fillSelectOptions($cat, [], ph, 'id', 'name', 0);
				return;
			}
			var list = resp.data.categories || [];
			fillSelectOptions($cat, list, ph, 'id', 'name', selected);
			cleanupTopSelectDuplicates();
			if (selected > 0) {
				$cat.val(String(selected));
				loadSubcategories(selected, parseInt($root.find('#subcategory_dropdown').attr('data-selected') || '0', 10) || 0);
			}
		});
	}

	function loadSubcategories(categoryId, selectedSub) {
		var $sub = $root.find('#subcategory_dropdown');
		var ph = $sub.attr('data-placeholder') || 'Select subcategory';
		if (!categoryId) {
				fillSelectOptions($sub, [], ph, 'id', 'name', 0);
			cleanupTopSelectDuplicates();
			return $.Deferred().resolve().promise();
		}
		$sub.html('<option value="">' + (typeof ltr_loading_msg !== 'undefined' ? ltr_loading_msg : 'Loading') + '...</option>');

		return $.ajax({
			method: 'POST',
			url: tcbSubcategoriesUrl,
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify({ access_token: tcbApiToken, categoryId: categoryId })
		}).done(function (resp) {
			if (!resp || resp.status !== 'true') {
				fillSelectOptions($sub, [], ph, 'id', 'name', 0);
				cleanupTopSelectDuplicates();
				return;
			}
			var list = resp.data.subcategories || [];
			fillSelectOptions($sub, list, ph, 'id', 'name', selectedSub);
			cleanupTopSelectDuplicates();
			if (selectedSub) {
				$sub.val(String(selectedSub));
			}
		});
	}

	function loadSubjects() {
		// {{base_url}}api/batch/batch-subjects — omit batch_id on create/edit form (full subject catalog).
		var body = { access_token: tcbApiToken };
		return $.ajax({
			method: 'POST',
			url: tcbSubjectsUrl,
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify(body)
		}).done(function (resp) {
			if (!resp || resp.status !== 'true') {
				tcbSubjectsCache = [];
			} else {
				tcbSubjectsCache = normalizeSubjectsForForm(resp.data && resp.data.subjects ? resp.data.subjects : []);
			}
			applySubjectsToRows();
			$root.find('.filter_subject').each(function () {
				if ($(this).val()) {
					loadChaptersForSubject($(this));
				}
			});
		});
	}

	// API-driven dropdowns (teacher web + same endpoints as mobile app).
	loadCategories();
	loadSubjects();

	// Replace legacy ajaxcall handlers from backend.js on this page.
	$root.find('#category_dropdown').off('change').on('change.tcbApi', function (e) {
		e.stopImmediatePropagation();
		var catId = parseInt($(this).val() || '0', 10);
		loadSubcategories(catId, 0);
		window.setTimeout(function () {
			destroySelect2IfAny($root.find('#subcategory_dropdown'));
		}, 50);
	});

	$root.on('change.tcbApiSubject', '.filter_subject', function (e) {
		e.stopPropagation();
		loadChaptersForSubject($(this));
	});
	<?php } else { ?>
	$root.find('#category_dropdown').on('change.tcbBatch', function () {
		window.setTimeout(function () {
			destroySelect2IfAny($root.find('#subcategory_dropdown'));
		}, 50);
	});
	<?php } ?>

	initPlainTopSelects();
	initSelect2In($root);
	initBatchDatepickers();
	initBatchClockpicker();

	window.setTimeout(initPlainTopSelects, 400);
	$(window).on('load.tcbBatch', initPlainTopSelects);

	openBenefitPanel($root.find('.edu_accordion_container_heading .edu_accord_parent').first());
	openSubjectPanel($root.find('.edu_accordion_container .edu_accord_parent').first());

	$(document).on('click', '#teacherBatchFormRoot .AssignBatchHeading', function () {
		window.setTimeout(function () {
			openBenefitPanel($root.find('.edu_accordion_container_heading .edu_accord_parent').last());
		}, 150);
	});

	$(document).on('click', '#teacherBatchFormRoot .AssignSubBatch', function () {
		window.setTimeout(function () {
			applySubjectsToRows();
			initSelect2In($root.find('.edu_accordion_container .edu_accord_parent').last());
			initBatchDatepickers();
			initBatchClockpicker();
			openSubjectPanel($root.find('.edu_accordion_container .edu_accord_parent').last());
		}, 350);
	});

	<?php if (!empty($teacher_user_id)) { ?>
	var teacherId = <?php echo (int) $teacher_user_id; ?>;
	$root.on('change', '.filter_subject', function () {
		var $row = $(this).closest('.edu_accord_parent');
		window.setTimeout(function () {
			var $teacher = $row.find('.filter_teacher');
			if ($teacher.find('option[value="' + teacherId + '"]').length) {
				$teacher.val(String(teacherId)).trigger('change');
			}
		}, 800);
	});
	<?php } ?>
});
</script>
