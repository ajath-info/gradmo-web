/**
 * Sync all api/* routes from application/config/routes.php into Gradmo_App.postman_collection.json
 * Run: node docs/sync-postman-from-routes.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROUTES = path.join(__dirname, '..', 'application', 'config', 'routes.php');
const OUT = path.join(__dirname, 'Gradmo_App.postman_collection.json');
const FOLDER = 'API — synced from routes.php (remaining)';

function text(key, value, disabled) {
	const o = { key, value: String(value), type: 'text' };
	if (disabled) o.disabled = true;
	return o;
}

function parseRoutes(php) {
	const re = /\$route\['(api\/[^']+)'\]/g;
	const paths = new Set();
	let m;
	while ((m = re.exec(php)) !== null) {
		paths.add(m[1]);
	}
	return [...paths].sort();
}

function defaultFields(routePath) {
	const parts = routePath.split('/');
	const last = parts[parts.length - 1];
	if (last.includes('batch-list') || last.includes('homework-list') || last.includes('video-lecture-list') || last.includes('exam-manage-list') || last.includes('upcoming-exam-list') || last.includes('recorded-meeting-list') || last.includes('teacher-batches')) {
		return [text('batch_id', '1'), text('page', '1'), text('limit', '20')];
	}
	if (last.includes('batch-details') || last.includes('batch-subjects') || last.includes('batch-chapters')) {
		return [text('batch_id', '1')];
	}
	if (last.includes('batch-chapters')) {
		return [text('batch_id', '1'), text('subject_id', '1')];
	}
	if (last.includes('subcategories')) {
		return [text('category_id', '1')];
	}
	if (last.includes('live-class-details')) {
		return [text('live_class_id', '1'), text('batch_id', '1', true)];
	}
	if (last.includes('live-class-list') || last.includes('class-status')) {
		return [text('batch_id', '1')];
	}
	if (last.includes('live-meeting-end')) {
		return [text('batch_id', '1'), text('action', 'host_joined', true), text('live_class_id', '', true)];
	}
	if (last.startsWith('batch-zoom') || last.includes('batch-notify') || last.includes('recorded-meeting-sync')) {
		return [text('batch_id', '1')];
	}
	if (last.includes('recorded-meeting-details')) {
		return [text('recorded_meeting_id', '1')];
	}
	if (last.includes('homework')) {
		if (last.includes('list') || last.includes('my-')) return [text('batch_id', '1'), text('page', '1'), text('limit', '20')];
		if (last.includes('submit')) return [text('homework_id', '1')];
		if (last.includes('evaluate')) return [text('submission_id', '1'), text('marks', '10')];
		if (last.includes('submission-details')) return [text('submission_id', '1')];
		if (last.includes('submissions')) return [text('homework_id', '1'), text('batch_id', '1')];
		return [text('homework_id', '1')];
	}
	if (last.includes('exam')) {
		if (last.includes('submit')) return [text('exam_id', '1'), text('answers', '{}')];
		if (last.includes('submission')) return [text('exam_id', '1'), text('batch_id', '1')];
		if (last.includes('omr')) return [text('exam_id', '1'), text('batch_id', '1')];
		if (last.includes('add')) return [text('batch_id', '1'), text('name', 'Test exam')];
		return [text('exam_id', '1')];
	}
	if (last.includes('video-lecture')) {
		return last.includes('list') ? [text('batch_id', '1'), text('page', '1'), text('limit', '20')] : [text('video_lecture_id', '1')];
	}
	if (last.includes('library')) {
		return last.includes('list') ? [text('batch_id', '1')] : [text('book_id', '1')];
	}
	if (last.includes('notes')) {
		return last.includes('list') ? [text('batch_id', '1')] : [text('notes_id', '1')];
	}
	if (last.includes('attendance')) {
		if (last.includes('matrix-save')) return [text('batch_id', '1'), text('entries', '[]')];
		if (last.includes('matrix')) return [text('batch_id', '1'), text('year', '2026'), text('month', '5')];
		return [text('batch_id', '1'), text('date', '2026-05-25')];
	}
	if (last.includes('teacher')) {
		if (last.includes('create')) return [text('batch_name', 'New batch'), text('category_id', '1')];
		if (last.includes('update') || last.includes('edit') || last.includes('delete')) return [text('batch_id', '1')];
		if (last.includes('subject-chapters')) return [text('batch_id', '1'), text('subject_id', '1')];
		if (last.includes('subjects')) return [text('batch_id', '1')];
		return [text('page', '1'), text('limit', '20')];
	}
	if (parts[1] === 'user') {
		if (last.includes('login') || last.includes('signup')) return [text('email', 'test@example.com'), text('password', '123456')];
		if (last.includes('otp')) return [text('mobile', '9999999999')];
		return [];
	}
	if (parts[1] === 'main') {
		if (last.includes('review')) return [text('review_id', '1')];
		if (last.includes('enquiry')) return [text('name', 'Test'), text('email', 'a@b.com'), text('message', 'Hi')];
		return [];
	}
	if (parts[1] === 'payment') {
		return [text('order_id', ''), text('razorpay_payment_id', '', true)];
	}
	if (parts[1] === 'institute') {
		return [text('page', '1'), text('limit', '20')];
	}
	return [text('batch_id', '1', true)];
}

function makeItem(routePath) {
	const pathParts = routePath.split('/').slice(1);
	const method = routePath.endsWith('zoom-cron-sync') ? 'GET' : 'POST';
	const noAuth = routePath.endsWith('zoom-webhook') || routePath.endsWith('zoom-cron-sync');
	const req = {
		method,
		header: [],
		url: {
			raw: '{{base_url}}' + routePath,
			host: ['{{base_url}}api'],
			path: pathParts,
		},
	};
	if (method === 'POST') {
		req.body = { mode: 'formdata', formdata: defaultFields(routePath) };
	}
	if (noAuth) {
		req.auth = { type: 'noauth' };
	}
	return {
		name: routePath,
		request: req,
		response: [],
	};
}

function collectPaths(items, set) {
	if (!set) set = new Set();
	for (const it of items || []) {
		if (it.request && it.request.url && Array.isArray(it.request.url.path)) {
			set.add(it.request.url.path.join('/'));
		}
		if (it.item) collectPaths(it.item, set);
	}
	return set;
}

const php = fs.readFileSync(ROUTES, 'utf8');
const allRoutes = parseRoutes(php);
const j = JSON.parse(fs.readFileSync(OUT, 'utf8'));
const seen = collectPaths(j.item);

const missing = allRoutes.filter((r) => {
	const key = r.replace(/^api\//, '');
	return !seen.has(key);
});

j.item = (j.item || []).filter((it) => it.name !== FOLDER);

const newItems = missing.map(makeItem);
if (newItems.length) {
	j.item.push({
		name: FOLDER,
		description: 'Auto-added ' + new Date().toISOString().slice(0, 10) + ' from routes.php. Bearer {{access_token}} on collection applies except zoom-webhook / zoom-cron-sync.',
		item: newItems,
	});
}

j.variable = j.variable || [];
const hasBase = j.variable.some((v) => v.key === 'base_url');
if (!hasBase) {
	j.variable.unshift({ key: 'base_url', value: 'http://localhost/education/' });
}
const hasToken = j.variable.some((v) => v.key === 'access_token');
if (!hasToken) {
	j.variable.push({ key: 'access_token', value: '' });
}

fs.writeFileSync(OUT, JSON.stringify(j, null, 2));
console.log('Routes in routes.php:', allRoutes.length);
console.log('Already in collection:', seen.size);
console.log('Added to folder "' + FOLDER + '":', newItems.length);
if (newItems.length) {
	console.log(newItems.map((i) => i.name).join('\n'));
}
