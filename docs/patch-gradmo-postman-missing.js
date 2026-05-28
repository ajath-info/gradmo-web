/**
 * Appends missing api/batch/* requests to docs/Gradmo_App.postman_collection.json
 * Run: node docs/patch-gradmo-postman-missing.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const OUT = path.join(__dirname, 'Gradmo_App.postman_collection.json');
const FOLDER_NAME = 'API — routes (416–461) Zoom, exams, teacher';

function text(key, value, disabled) {
	const o = { key, value: String(value), type: 'text' };
	if (disabled) o.disabled = true;
	return o;
}

function makePost(displayName, pathParts, formdataFields, method) {
	return {
		name: displayName,
		request: {
			method: method || 'POST',
			header: [],
			body:
				method === 'GET'
					? undefined
					: {
							mode: 'formdata',
							formdata: formdataFields,
						},
			url: {
				raw: '{{base_url}}api/' + pathParts.join('/'),
				host: ['{{base_url}}api'],
				path: pathParts,
			},
		},
		response: [],
	};
}

const common = [text('batch_id', '1'), text('page', '1'), text('limit', '20')];

const routes = [
	makePost('api/batch/class-status', ['batch', 'class-status'], [
		text('batch_id', '1'),
		text('live_class_id', '0', true),
	]),
	makePost('api/batch/live-meeting-end (host_joined)', ['batch', 'live-meeting-end'], [
		text('batch_id', '1'),
		text('action', 'host_joined'),
	]),
	makePost('api/batch/live-meeting-end (end)', ['batch', 'live-meeting-end'], [
		text('batch_id', '1'),
		text('live_class_id', '1', true),
	]),
	makePost('api/batch/live-class-details (batch zoom)', ['batch', 'live-class-details'], [
		text('live_class_id', '0'),
		text('batch_id', '1'),
	]),
	makePost('api/batch/batch-zoom-details', ['batch', 'batch-zoom-details'], [text('batch_id', '1')]),
	makePost('api/batch/batch-zoom-create', ['batch', 'batch-zoom-create'], [
		text('batch_id', '1'),
		text('topic', 'Live class'),
		text('duration', '60'),
		text('timezone', 'Asia/Kolkata'),
	]),
	makePost('api/batch/batch-zoom-update', ['batch', 'batch-zoom-update'], [
		text('batch_id', '1'),
		text('topic', 'Updated', true),
	]),
	makePost('api/batch/batch-zoom-delete', ['batch', 'batch-zoom-delete'], [text('batch_id', '1')]),
	makePost('api/batch/batch-zoom-join', ['batch', 'batch-zoom-join'], [text('batch_id', '1')]),
	makePost('api/batch/batch-notify-students', ['batch', 'batch-notify-students'], [
		text('batch_id', '1'),
		text('notification_type', 'live'),
		text('msg', 'Live class started'),
		text('url', '', true),
	]),
	makePost('api/batch/recorded-meeting-list', ['batch', 'recorded-meeting-list'], [
		...common,
		text('sync', '1', true),
	]),
	makePost('api/batch/recorded-meeting-details', ['batch', 'recorded-meeting-details'], [
		text('recorded_meeting_id', '1'),
	]),
	makePost('api/batch/recorded-meeting-sync', ['batch', 'recorded-meeting-sync'], [text('batch_id', '1')]),
	makePost('api/batch/zoom-cron-sync', ['batch', 'zoom-cron-sync'], [], 'GET'),
	makePost('api/batch/exam-submission-list', ['batch', 'exam-submission-list'], [
		text('exam_id', '1'),
		text('batch_id', '1'),
		text('page', '1'),
		text('limit', '20'),
	]),
	makePost('api/batch/exam-submission-details', ['batch', 'exam-submission-details'], [
		text('submission_id', '1'),
	]),
	makePost('api/batch/exam-omr-sheet', ['batch', 'exam-omr-sheet'], [
		text('exam_id', '1'),
		text('batch_id', '1'),
	]),
	makePost('api/batch/categories', ['batch', 'categories'], []),
	makePost('api/batch/subcategories', ['batch', 'subcategories'], [text('category_id', '1')]),
	makePost('api/batch/teacher-batches', ['batch', 'teacher-batches'], [
		text('page', '1'),
		text('limit', '20'),
	]),
	makePost('api/batch/teacher-batch-form-options', ['batch', 'teacher-batch-form-options'], []),
	makePost('api/batch/teacher-batch-subjects', ['batch', 'teacher-batch-subjects'], [text('batch_id', '1')]),
	makePost('api/batch/teacher-batch-subject-chapters', ['batch', 'teacher-batch-subject-chapters'], [
		text('batch_id', '1'),
		text('subject_id', '1'),
	]),
	makePost('api/batch/teacher-batch-edit', ['batch', 'teacher-batch-edit'], [text('batch_id', '1')]),
	makePost('api/batch/teacher-create-batch', ['batch', 'teacher-create-batch'], [
		text('batch_name', 'New batch'),
		text('category_id', '1'),
	]),
	makePost('api/batch/teacher-update-batch', ['batch', 'teacher-update-batch'], [
		text('batch_id', '1'),
		text('batch_name', 'Updated batch', true),
	]),
	makePost('api/batch/teacher-delete-batch', ['batch', 'teacher-delete-batch'], [text('batch_id', '1')]),
];

const j = JSON.parse(fs.readFileSync(OUT, 'utf8'));
j.item = (j.item || []).filter((it) => it.name !== FOLDER_NAME);

function existingPaths(items, set) {
	if (!set) set = new Set();
	for (const it of items || []) {
		if (it.request && it.request.url && Array.isArray(it.request.url.path)) {
			set.add(it.request.url.path.join('/'));
		}
		if (it.item) existingPaths(it.item, set);
	}
	return set;
}

const seen = existingPaths(j.item);
const newItems = routes.filter((r) => {
	const p = r.request.url.path.join('/');
	return !seen.has(p);
});

j.item.push({
	name: FOLDER_NAME,
	description:
		'Endpoints from routes.php lines 408–461 (Zoom, recordings, teacher batch CRUD, exam submissions). Import Zoom_APIs.postman_collection.json for a Zoom-focused set.',
	item: newItems,
});

fs.writeFileSync(OUT, JSON.stringify(j, null, 2));
console.log('Updated:', OUT);
console.log('Added requests:', newItems.length);
