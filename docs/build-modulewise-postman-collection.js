/**
 * Builds docs/Gradmo_App_Modulewise.postman_collection.json from docs/old_collocton.json
 * — dedupes by HTTP method + api path (no query), nests Batch/User into subfolders.
 *
 * Run: node docs/build-modulewise-postman-collection.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { flattenRequestItems } = require('./old-collocton-helpers');

const ROOT = path.join(__dirname);
const SRC = path.join(ROOT, 'old_collocton.json');
const OUT = path.join(ROOT, 'Gradmo_App_Modulewise.postman_collection.json');

function methodOf(it) {
	const m = it.request && it.request.method;
	return (m && String(m).toUpperCase()) || 'GET';
}

function pathSegments(it) {
	const u = it.request && it.request.url;
	if (!u) {
		return [];
	}
	if (Array.isArray(u.path) && u.path.length) {
		return u.path.map((p) => String(p).replace(/\/+$/, ''));
	}
	const raw = typeof u.raw === 'string' ? u.raw : '';
	const q = raw.split('?')[0];
	const m = q.match(/api\/([^?#]+)/i);
	if (!m) {
		return [];
	}
	return m[1].split('/').filter(Boolean);
}

function dedupeKey(it) {
	const segs = pathSegments(it);
	if (!segs.length) {
		return `${methodOf(it)}|__empty__`;
	}
	return `${methodOf(it)}|${segs.join('/')}`;
}

function userSubfolder(segs) {
	const tail = segs.slice(1).join('/').toLowerCase();
	const p2 = (segs[1] || '').toLowerCase();
	if (/^(signup|login|send-otp|verify-otp|logout)$/.test(p2)) {
		return 'Auth';
	}
	if (/^(update-profile|update-password|change-password|delete-account)$/.test(p2)) {
		return 'Profile & account';
	}
	if (p2.includes('attendance')) {
		return 'Attendance';
	}
	return 'Other';
}

function batchSubfolder(segs) {
	const p2 = (segs[1] || '').toLowerCase();
	if (!p2) {
		return 'General';
	}
	if (p2.startsWith('homework')) {
		return 'Homework';
	}
	if (p2.startsWith('notes')) {
		return 'Notes';
	}
	if (p2.startsWith('video-lecture')) {
		return 'Video lectures';
	}
	if (p2.startsWith('library')) {
		return 'Library';
	}
	if (p2.startsWith('live-class')) {
		return 'Live class';
	}
	if (p2.includes('attendance') || p2.includes('roster') || p2.includes('matrix')) {
		return 'Attendance';
	}
	if (
		p2.includes('exam') ||
		p2.startsWith('student-exam') ||
		p2.startsWith('upcoming-exam')
	) {
		return 'Exams';
	}
	if (p2.startsWith('batch-') || p2 === 'slider-list' || p2 === 'batch-list' || p2 === 'batch-details') {
		return 'Batch & chapters';
	}
	return 'General';
}

function mainSubfolder(segs) {
	const p2 = (segs[1] || '').toLowerCase();
	if (/country-list|state-list|city-list/.test(p2)) {
		return 'Locations';
	}
	if (/review/.test(p2) || p2 === 'post-enquiry') {
		return 'Reviews & enquiry';
	}
	if (p2 === 'pages' || p2 === 'site-details' || p2 === 'get_defaults_requirements') {
		return 'Site & content';
	}
	if (p2 === 'notifications-list') {
		return 'Notifications';
	}
	return 'General';
}

const MODULE_ORDER = [
	{ id: 'user', label: 'User' },
	{ id: 'main', label: 'Main' },
	{ id: 'batch', label: 'Batch' },
	{ id: 'institute', label: 'Institute' },
	{ id: 'courses', label: 'Courses' },
	{ id: 'plan', label: 'Plan' },
	{ id: 'payment', label: 'Payment' },
];

function nestUnderSubfolders(moduleId, flatItems) {
	if (moduleId === 'user') {
		const by = new Map();
		for (const it of flatItems) {
			const sub = userSubfolder(pathSegments(it));
			if (!by.has(sub)) {
				by.set(sub, []);
			}
			by.get(sub).push(it);
		}
		const order = ['Auth', 'Profile & account', 'Attendance', 'Other'];
		return order.filter((n) => by.has(n)).map((n) => ({ name: n, item: by.get(n) }));
	}
	if (moduleId === 'batch') {
		const by = new Map();
		for (const it of flatItems) {
			const sub = batchSubfolder(pathSegments(it));
			if (!by.has(sub)) {
				by.set(sub, []);
			}
			by.get(sub).push(it);
		}
		const order = [
			'General',
			'Batch & chapters',
			'Attendance',
			'Homework',
			'Library',
			'Notes',
			'Live class',
			'Video lectures',
			'Exams',
		];
		return order.filter((n) => by.has(n)).map((n) => ({ name: n, item: by.get(n) }));
	}
	if (moduleId === 'main') {
		const by = new Map();
		for (const it of flatItems) {
			const sub = mainSubfolder(pathSegments(it));
			if (!by.has(sub)) {
				by.set(sub, []);
			}
			by.get(sub).push(it);
		}
		const order = ['Locations', 'Site & content', 'Notifications', 'Reviews & enquiry', 'General'];
		return order.filter((n) => by.has(n)).map((n) => ({ name: n, item: by.get(n) }));
	}
	return flatItems;
}

function buildCollection() {
	const j = JSON.parse(fs.readFileSync(SRC, 'utf8'));
	const flat = flattenRequestItems(j.item || []);
	const seen = new Map();
	for (const it of flat) {
		const k = dedupeKey(it);
		if (!seen.has(k)) {
			seen.set(k, it);
		}
	}
	const unique = Array.from(seen.values());

	const byModule = new Map();
	const other = [];
	for (const m of MODULE_ORDER) {
		byModule.set(m.id, []);
	}
	for (const it of unique) {
		const segs = pathSegments(it);
		const first = segs[0] ? segs[0].toLowerCase() : '';
		if (byModule.has(first)) {
			byModule.get(first).push(it);
		} else {
			other.push(it);
		}
	}

	const topFolders = [];
	for (const m of MODULE_ORDER) {
		const arr = byModule.get(m.id);
		if (!arr.length) {
			continue;
		}
		const nested = nestUnderSubfolders(m.id, arr);
		const isNestedFolders = nested.length && nested[0].item && Array.isArray(nested[0].item);
		if (isNestedFolders) {
			topFolders.push({ name: m.label, item: nested });
		} else {
			topFolders.push({ name: m.label, item: arr });
		}
	}
	if (other.length) {
		topFolders.push({ name: 'Other', item: other });
	}

	const outDoc = {
		info: {
			_postman_id: require('crypto').randomUUID(),
			name: 'Gradmo App — module wise',
			description:
				'Generated from docs/old_collocton.json: one request per endpoint (deduped), grouped by API module. Regenerate: node docs/build-modulewise-postman-collection.js',
			schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
		},
		item: topFolders,
		variable: j.variable || [{ key: 'base_url', value: 'http://localhost/education/' }],
	};

	fs.writeFileSync(OUT, JSON.stringify(outDoc, null, 2) + '\n', 'utf8');
	console.log('Wrote', OUT);
	console.log(
		'Folders:',
		topFolders.map((f) => f.name + (f.item && f.item[0] && f.item[0].item ? ` (${f.item.length} subfolders)` : ` (${f.item.length} requests)`)).join(', '),
	);
	console.log('Requests (deduped):', unique.length, 'from', flat.length, 'original');
}

buildCollection();
