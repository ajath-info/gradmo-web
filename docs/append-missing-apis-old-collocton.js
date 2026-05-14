/**
 * Appends missing routes (routes.php ~314–415) to docs/old_collocton.json
 * using the same Postman shape as existing items (POST, formdata, url, response).
 * After append, requests are regrouped into folders by API model (user, main, batch, …).
 *
 * Run from repo root: node docs/append-missing-apis-old-collocton.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { flattenRequestItems, groupItemsByApiModel } = require('./old-collocton-helpers');

const collectionPath = path.join(__dirname, 'old_collocton.json');

function uuid() {
	return crypto.randomUUID();
}

function fd(key, value, disabled) {
	const o = { key, value: String(value), type: 'text', uuid: uuid() };
	if (disabled) o.disabled = true;
	return o;
}

function makeItem(name, pathParts, formdata) {
	const pathStr = pathParts.join('/');
	return {
		name,
		request: {
			method: 'POST',
			header: [],
			body: {
				mode: 'formdata',
				formdata: formdata,
			},
			url: {
				raw: '{{base_url}}api/' + pathStr,
				host: ['{{base_url}}api'],
				path: pathParts,
			},
		},
		response: [],
	};
}

const common = [
	fd('batch_id', '1'),
	fd('page', '1'),
	fd('limit', '20'),
	fd('search', '', true),
];

/** [postmanName, pathSegments, formdata[]] — only paths not already in collection */
const toAppend = [
	['approve-review', ['main', 'approve-review'], [fd('review_id', '1')]],
	['institute-reviews-list', ['main', 'institute-reviews-list'], [fd('page', '1'), fd('limit', '20'), fd('status', '0', true)]],
	['review-detail', ['main', 'review-detail'], [fd('review_id', '1')]],
	['update-review', ['main', 'update-review'], [fd('review_id', '1'), fd('rating', '5'), fd('msg', 'updated')]],
	['delete-review', ['main', 'delete-review'], [fd('review_id', '1')]],
	['payment/razorpay/verify-payment', ['payment', 'razorpay', 'verify-payment'], [
		fd('razorpay_order_id', ''),
		fd('razorpay_payment_id', ''),
		fd('razorpay_signature', ''),
		fd('student_id', '1'),
		fd('batch_id', '1'),
	]],
	['payment/razorpay/fetch-payment', ['payment', 'razorpay', 'fetch-payment'], [fd('payment_id', '')]],
	['payment/razorpay/order-status', ['payment', 'razorpay', 'order-status'], [fd('order_id', '')]],
	['payment/razorpay/webhook', ['payment', 'razorpay', 'webhook'], [fd('payload', '{}', true)]],
	['batch-subjects', ['batch', 'batch-subjects'], [fd('batch_id', '1')]],
	['attendance-roster', ['batch', 'attendance-roster'], [fd('batch_id', '1'), fd('date', '2026-05-13')]],
	['attendance-roster-matrix', ['batch', 'attendance-roster-matrix'], [fd('batch_id', '1'), fd('year', '2026'), fd('month', '5')]],
	['attendance-matrix-save', ['batch', 'attendance-matrix-save'], [fd('batch_id', '1'), fd('default_time', '10:30'), fd('entries', '[]')]],
	['batch-homework-list', ['batch', 'homework-list'], [...common]],
	['batch-homework-details', ['batch', 'homework-details'], [fd('homework_id', '1')]],
	['batch-homework-add', ['batch', 'homework-add'], [fd('batch_id', '1'), fd('subject_id', '1'), fd('description', 'Homework', true)]],
	['batch-homework-edit', ['batch', 'homework-edit'], [fd('homework_id', '1')]],
	['batch-homework-delete', ['batch', 'homework-delete'], [fd('homework_id', '1')]],
	['batch-homework-submit', ['batch', 'homework-submit'], [fd('homework_id', '1'), fd('submission_text', 'My answer')]],
	['batch-homework-submissions', ['batch', 'homework-submissions'], [fd('homework_id', '1'), fd('batch_id', '1'), fd('page', '1'), fd('limit', '20')]],
	['batch-homework-submission-details', ['batch', 'homework-submission-details'], [fd('submission_id', '1')]],
	['batch-homework-evaluate', ['batch', 'homework-evaluate'], [fd('submission_id', '1'), fd('marks', '10'), fd('remark', 'Good')]],
	['batch-my-homework-submissions', ['batch', 'my-homework-submissions'], [...common]],
	['batch-library-add-book', ['batch', 'library-add-book'], [fd('batch_id', '1'), fd('title', 'Book', true)]],
	['batch-library-edit-book', ['batch', 'library-edit-book'], [fd('book_id', '1'), fd('batch_id', '1')]],
	['batch-library-delete-book', ['batch', 'library-delete-book'], [fd('book_id', '1')]],
	['batch-library-book-details', ['batch', 'library-book-details'], [fd('book_id', '1'), fd('batch_id', '1')]],
	['batch-notes-list', ['batch', 'notes-list'], [fd('batch_id', '1'), fd('page', '1'), fd('limit', '20'), fd('search', '', true)]],
	['batch-notes-add', ['batch', 'notes-add'], [fd('batch_id', '1'), fd('title', 'Note title'), fd('topic', 'Topic'), fd('subject', 'Subject')]],
	['batch-notes-edit', ['batch', 'notes-edit'], [fd('notes_id', '1'), fd('batch_id', '1'), fd('title', 'Updated title', true)]],
	['batch-notes-delete', ['batch', 'notes-delete'], [fd('notes_id', '1'), fd('batch_id', '1')]],
	['batch-notes-details', ['batch', 'notes-details'], [fd('notes_id', '1'), fd('batch_id', '1')]],
	['batch-live-class-list', ['batch', 'live-class-list'], [fd('batch_id', '1'), fd('page', '1'), fd('limit', '20')]],
	['batch-live-class-details', ['batch', 'live-class-details'], [fd('live_class_id', '1')]],
	['batch-video-lecture-list', ['batch', 'video-lecture-list'], [...common]],
	['batch-video-lecture-details', ['batch', 'video-lecture-details'], [fd('video_lecture_id', '1')]],
	['batch-video-lecture-add', ['batch', 'video-lecture-add'], [fd('batch_id', '1')]],
	['batch-video-lecture-edit', ['batch', 'video-lecture-edit'], [fd('video_lecture_id', '1')]],
	['batch-video-lecture-delete', ['batch', 'video-lecture-delete'], [fd('video_lecture_id', '1')]],
	['batch-upcoming-exam-list', ['batch', 'upcoming-exam-list'], [...common]],
	['batch-upcoming-exam-details', ['batch', 'upcoming-exam-details'], [fd('exam_id', '1')]],
	['batch-student-exam-dashboard', ['batch', 'student-exam-dashboard'], [fd('batch_id', '1')]],
	['batch-student-exam-paper', ['batch', 'student-exam-paper'], [fd('exam_id', '1')]],
	['batch-student-submit-exam', ['batch', 'student-submit-exam'], [fd('exam_id', '1'), fd('started_at', '2026-05-13 10:00:00'), fd('answers', '{"1":"A"}')]],
	['batch-student-exam-result', ['batch', 'student-exam-result'], [fd('exam_id', '1')]],
	['batch-exam-manage-list', ['batch', 'exam-manage-list'], [...common]],
	['batch-exam-add', ['batch', 'exam-add'], [fd('batch_id', '1'), fd('name', 'Mock'), fd('type', '1'), fd('format', '1')]],
	['batch-exam-edit', ['batch', 'exam-edit'], [fd('exam_id', '1'), fd('batch_id', '1')]],
	['batch-exam-delete', ['batch', 'exam-delete'], [fd('exam_id', '1')]],
	['batch-chapters', ['batch', 'batch-chapters'], [fd('batch_id', '1'), fd('subject_id', '1')]],
];

const j = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));
const flat = flattenRequestItems(j.item || []);
const seen = new Set();
for (const it of flat) {
	if (it.request && it.request.url && Array.isArray(it.request.url.path)) {
		seen.add(it.request.url.path.join('/'));
	}
}

const appended = [];
for (const [name, parts, fields] of toAppend) {
	const key = parts.join('/');
	if (seen.has(key)) {
		continue;
	}
	appended.push(makeItem(name, parts, fields));
	seen.add(key);
}

const mergedFlat = flat.concat(appended);
j.item = groupItemsByApiModel(mergedFlat);

fs.writeFileSync(collectionPath, JSON.stringify(j, null, 2) + '\n', 'utf8');
console.log('Updated:', collectionPath);
console.log('Appended', appended.length, 'new requests; collection regrouped by API model.');
console.log('Folders:', j.item.map((f) => f.name + ' (' + f.item.length + ')').join(', '));
