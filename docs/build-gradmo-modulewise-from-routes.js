/**
 * Build Gradmo App — module wise Postman collection from application/config/routes.php
 * with correct form-data keys per endpoint (no query-string params in URL).
 *
 * Run: node docs/build-gradmo-modulewise-from-routes.js
 * Output: docs/Gradmo_App_Modulewise.postman_collection.json
 */
'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const { fieldsForRoute: fieldsFromControllers } = require('./postman-route-fields');

const ROUTES_FILE = path.join(__dirname, '..', 'application', 'config', 'routes.php');
const OUT = path.join(__dirname, 'Gradmo_App_Modulewise.postman_collection.json');
const EXTRACT_SCRIPT = path.join(__dirname, 'extract-api-keys.js');

function text(key, value, description, disabled) {
	const o = { key, value: String(value), type: 'text' };
	if (description) o.description = description;
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

/** @returns {{key:string,value:string,type:string,description?:string,disabled?:boolean}[]} */
function fieldsForRoute(routePath) {
	return fieldsFromControllers(routePath, text);
}

function _legacyFieldsForRoute_UNUSED(routePath) {
	const parts = routePath.split('/');
	const mod = parts[1] || '';
	const last = parts[parts.length - 1] || '';

	// --- User ---
	if (mod === 'user') {
		if (last === 'signup') {
			return [
				text('name', 'John Doe'),
				text('email', 'user@example.com'),
				text('mobile', '9999999999'),
				text('password', '123456'),
				text('user_type', 'student', 'student | teacher | institute'),
				text('device_id', 'device-uuid-001'),
				text('device_token', 'fcm-token-here', 'FCM push token'),
				text('device_type', 'android', 'android | ios'),
			];
		}
		if (last === 'login') {
			return [
				text('username', 'user@example.com', 'Email or enrollment ID for students'),
				text('password', '123456'),
				text('user_type', 'student', 'student | teacher | institute'),
				text('device_id', 'device-uuid-001'),
				text('device_token', 'fcm-token-here'),
				text('device_type', 'android'),
			];
		}
		if (last === 'send-otp') {
			return [
				text('mobile', '9999999999'),
				text('user_type', 'student', 'student | teacher | institute'),
			];
		}
		if (last === 'verify-otp') {
			return [
				text('mobile', '9999999999'),
				text('otp', '1234'),
				text('user_type', 'student', 'student | teacher | institute'),
				text('device_id', 'device-uuid-001', '', true),
				text('device_token', 'fcm-token-here', '', true),
				text('device_type', 'android', '', true),
			];
		}
		if (last === 'logout') {
			return [text('student_id', '', 'Only if needed', true)];
		}
		if (last === 'update-profile') {
			return [
				text('name', 'John Doe'),
				text('email', 'user@example.com', '', true),
				text('mobile', '9999999999'),
				text('user_type', 'student', 'student | teacher | institute'),
				text('address', 'Street address'),
				text('country', 'India'),
				text('state', 'Uttar Pradesh'),
				text('city', 'Noida'),
				text('pincode', '201301'),
				text('school_college_name', 'College name', 'Students'),
				text('grade', 'A', 'Students'),
				text('image', '', 'File upload or image URL', true),
			];
		}
		if (last === 'update-password') {
			return [
				text('mobile', '9999999999'),
				text('password', 'newpassword123'),
				text('confirm_password', 'newpassword123'),
				text('user_type', 'student', 'student | teacher | institute'),
			];
		}
		if (last === 'change-password') {
			return [
				text('current_password', '123456'),
				text('new_password', 'newpassword123'),
				text('confirm_password', 'newpassword123'),
			];
		}
		if (last === 'delete-account') {
			return [];
		}
		if (last === 'user_details') {
			return [text('batch_id', '6', 'Optional')];
		}
		if (last === 'payment-history') {
			return [
				text('student_id', '20', 'Student only; omit if caller is student'),
				text('batch_id', '6', '', true),
				text('page', '1'),
				text('limit', '20'),
			];
		}
		if (last === 'attendance-list') {
			return [
				text('batch_id', '6'),
				text('date', '2026-05-13', 'YYYY-MM-DD', true),
				text('month', '5', '1-12', true),
				text('year', '2026', '', true),
				text('search', '', '', true),
			];
		}
		if (last === 'add-attendance') {
			return [
				text('batch_id', '6'),
				text('student_id', '20'),
				text('attendance_date', '2026-05-13', 'YYYY-MM-DD'),
				text('time', '10:30', 'HH:MM or HH.MM'),
				text('day_status', 'present', 'present | late | absent', true),
			];
		}
	}

	// --- Main ---
	if (mod === 'main') {
		if (last === 'country-list') return [];
		if (last === 'state-list') return [text('country_id', '105')];
		if (last === 'city-list') return [text('state_id', '1')];
		if (last === 'pages') return [text('key', 'privacy_policy', 'pages.key slug', true)];
		if (last === 'site-details') {
			return [
				text('zoom', '1', '', true),
				text('payment', '1', '', true),
			];
		}
		if (last === 'get_defaults_requirements') return [];
		if (last === 'notifications-list') {
			return [text('page', '1'), text('limit', '20')];
		}
		if (last === 'post-enquiry') {
			return [
				text('name', 'John Doe'),
				text('mobile', '9999999999'),
				text('email', 'user@example.com'),
				text('subject', 'Enquiry subject'),
				text('message', 'Message text'),
			];
		}
		if (last === 'add-review') {
			return [
				text('institute_id', '69'),
				text('rating', '5', '1-5'),
				text('msg', 'Review message'),
			];
		}
		if (last === 'reviews-list') {
			return [
				text('institute_id', '69'),
				text('page', '1'),
				text('limit', '20'),
			];
		}
		if (last === 'institute-reviews-list') {
			return [
				text('page', '1'),
				text('limit', '20'),
				text('status', '0', '0=pending 1=approved', true),
			];
		}
		if (last === 'review-detail' || last === 'approve-review' || last === 'delete-review') {
			return [text('review_id', '1')];
		}
		if (last === 'update-review') {
			return [
				text('review_id', '1'),
				text('rating', '5'),
				text('msg', 'Updated review text'),
			];
		}
	}

	// --- Institute ---
	if (mod === 'institute') {
		if (last === 'listing') {
			return [
				text('page', '1'),
				text('limit', '20'),
				text('search', '', '', true),
				text('city', '', '', true),
				text('list', 'my', 'Set to "my" for enrolled institutes', true),
				text('order_field', 'name', 'name | distance'),
				text('order_type', 'ASC', 'ASC | DESC'),
				text('latitude', '28.6139', 'Required if order_field=distance'),
				text('longitude', '77.2090', 'Required if order_field=distance'),
				text('batch_id', '6', '', true),
			];
		}
		if (last === 'details') {
			return [
				text('institute_id', '69'),
				text('latitude', '28.6139', '', true),
				text('longitude', '77.2090', '', true),
			];
		}
		if (last === 'city-list') return [text('search', '', '', true)];
	}

	// --- Courses / Plan ---
	if (mod === 'courses' && last === 'courses-list') {
		return [text('page', '1', '', true), text('limit', '20', '', true)];
	}
	if (mod === 'plan') {
		if (last === 'plans') return [text('batch_id', '6', '', true)];
		if (last === 'promo-codes') {
			return [text('plan_id', '1', '', true), text('batch_id', '6', '', true)];
		}
	}

	// --- Payment ---
	if (mod === 'payment') {
		if (last === 'create-order') {
			return [
				text('amount_in_rupees', '1'),
				text('plan_id', '1', '', true),
				text('promo_id', '1', '', true),
				text('batch_id', '6'),
			];
		}
		if (last === 'verify-payment') {
			return [
				text('razorpay_order_id', ''),
				text('razorpay_payment_id', ''),
				text('razorpay_signature', ''),
				text('student_id', '20', '', true),
				text('batch_id', '6', '', true),
			];
		}
		if (last === 'fetch-payment') return [text('payment_id', '')];
		if (last === 'order-status') return [text('order_id', '')];
		if (last === 'webhook') return [text('payload', '{}', 'Raw JSON body', true)];
	}

	// --- Batch (shared patterns) ---
	if (mod === 'batch') {
		if (last === 'slider-list') return [text('search', '', '', true)];
		if (last === 'batch-list' || last === 'teacher-batches') {
			return [
				text('page', '1'),
				text('limit', '20'),
				text('search', '', '', true),
				text('list', 'All', 'All | My', true),
			];
		}
		if (last === 'batch-details' || last === 'batch-subjects' || last === 'class-status') {
			return [text('batch_id', '6')];
		}
		if (last === 'batch-chapters') {
			return [text('batch_id', '6'), text('subject_id', '1')];
		}
		if (last === 'categories') return [];
		if (last === 'subcategories') return [text('category_id', '1')];
		if (last === 'attendance-roster') {
			return [text('batch_id', '6'), text('date', '2026-05-13')];
		}
		if (last === 'attendance-roster-matrix') {
			return [text('batch_id', '6'), text('year', '2026'), text('month', '5')];
		}
		if (last === 'attendance-matrix-save') {
			return [
				text('batch_id', '6'),
				text('default_time', '10:30', '', true),
				text('entries', '[]', 'JSON array of {student_id,date,time,day_status}'),
			];
		}
		if (last.startsWith('homework')) {
			if (last === 'homework-list' || last === 'my-homework-submissions') {
				return [
					text('batch_id', '6'),
					text('page', '1'),
					text('limit', '20'),
					text('search', '', '', true),
				];
			}
			if (last === 'homework-add') {
				return [
					text('batch_id', '6'),
					text('subject_id', '1'),
					text('description', 'Homework description'),
					text('date', '2026-05-13', '', true),
				];
			}
			if (last === 'homework-edit') {
				return [
					text('homework_id', '1'),
					text('batch_id', '6', '', true),
					text('description', 'Updated text', '', true),
				];
			}
			if (last === 'homework-submit') {
				return [
					text('homework_id', '1'),
					text('submission_text', 'My answer'),
				];
			}
			if (last === 'homework-submissions') {
				return [
					text('homework_id', '1'),
					text('batch_id', '6'),
					text('page', '1'),
					text('limit', '20'),
				];
			}
			if (last === 'homework-submission-details' || last === 'homework-evaluate') {
				const fields = [text('submission_id', '1')];
				if (last === 'homework-evaluate') {
					fields.push(text('marks', '10'));
					fields.push(text('remark', 'Good work'));
				}
				return fields;
			}
			return [text('homework_id', '1')];
		}
		if (last.startsWith('library')) {
			if (last === 'library-list') return [text('batch_id', '6'), text('page', '1', '', true), text('limit', '20', '', true)];
			if (last === 'library-add-book') {
				return [
					text('batch_id', '6'),
					text('title', 'Book title'),
					text('author', 'Author', '', true),
					text('description', '', '', true),
				];
			}
			if (last === 'library-edit-book') {
				return [text('book_id', '1'), text('batch_id', '6'), text('title', 'Updated title', '', true)];
			}
			return [text('book_id', '1'), text('batch_id', '6', '', true)];
		}
		if (last.startsWith('notes')) {
			if (last === 'notes-list') {
				return [text('batch_id', '6'), text('page', '1'), text('limit', '20'), text('search', '', '', true)];
			}
			if (last === 'notes-add') {
				return [
					text('batch_id', '6'),
					text('title', 'Note title'),
					text('topic', 'Topic'),
					text('subject', 'Subject'),
					text('description', '', '', true),
				];
			}
			if (last === 'notes-edit') {
				return [text('notes_id', '1'), text('batch_id', '6'), text('title', 'Updated title', '', true)];
			}
			return [text('notes_id', '1'), text('batch_id', '6', '', true)];
		}
		if (last.startsWith('live-class') || last === 'live-meeting-end') {
			if (last === 'live-class-list') {
				return [text('batch_id', '6'), text('page', '1'), text('limit', '20')];
			}
			return [text('live_class_id', '1'), text('batch_id', '6', '', true)];
		}
		if (last.startsWith('recorded-meeting')) {
			if (last === 'recorded-meeting-list') {
				return [text('batch_id', '6'), text('page', '1'), text('limit', '20')];
			}
			return [text('recorded_meeting_id', '1'), text('batch_id', '6', '', true)];
		}
		if (last.startsWith('batch-zoom') || last === 'batch-notify-students' || last === 'recorded-meeting-sync') {
			return [text('batch_id', '6')];
		}
		if (last.startsWith('video-lecture')) {
			if (last === 'video-lecture-list') {
				return [text('batch_id', '6'), text('page', '1'), text('limit', '20'), text('search', '', '', true)];
			}
			if (last === 'video-lecture-add') {
				return [
					text('batch_id', '6'),
					text('title', 'Lecture title', '', true),
					text('video_url', 'https://example.com/video.mp4', '', true),
				];
			}
			return [text('video_lecture_id', '1'), text('batch_id', '6', '', true)];
		}
		if (last.includes('exam')) {
			if (last === 'upcoming-exam-list' || last === 'exam-manage-list' || last === 'exam-submission-list') {
				return [text('batch_id', '6'), text('page', '1'), text('limit', '20'), text('search', '', '', true)];
			}
			if (last === 'student-exam-dashboard') return [text('batch_id', '6')];
			if (last === 'student-submit-exam') {
				return [
					text('exam_id', '1'),
					text('started_at', '2026-05-13 10:00:00', '', true),
					text('answers', '{"1":"A"}', 'JSON map question_id => answer'),
				];
			}
			if (last === 'exam-add') {
				return [
					text('batch_id', '6'),
					text('name', 'Mock Test'),
					text('type', '1', '', true),
					text('format', '1', '', true),
				];
			}
			if (last === 'exam-edit') return [text('exam_id', '1'), text('batch_id', '6')];
			if (last === 'exam-omr-sheet') return [text('exam_id', '1'), text('batch_id', '6')];
			if (last === 'exam-submission-details') return [text('submission_id', '1', '', true), text('exam_id', '1', '', true)];
			return [text('exam_id', '1')];
		}
		if (last.startsWith('teacher-')) {
			if (last === 'teacher-create-batch') {
				return [
					text('batch_name', 'New Batch'),
					text('category_id', '1'),
					text('subcategory_id', '1', '', true),
					text('description', '', '', true),
				];
			}
			if (last === 'teacher-batch-subjects') return [text('batch_id', '6')];
			if (last === 'teacher-batch-subject-chapters') {
				return [text('batch_id', '6'), text('subject_id', '1')];
			}
			if (last === 'teacher-batch-form-options') return [];
			if (last === 'teacher-batch-edit' || last === 'teacher-update-batch' || last === 'teacher-delete-batch') {
				return [text('batch_id', '6')];
			}
		}
		if (last === 'zoom-webhook') return [];
		if (last === 'zoom-cron-sync' || last === 'zoom-debug') return [];
	}

	return [text('batch_id', '6', '', true)];
}

function friendlyName(routePath) {
	const parts = routePath.split('/');
	const last = parts[parts.length - 1] || routePath;
	return last
		.split('-')
		.map((w) => w.charAt(0).toUpperCase() + w.slice(1))
		.join(' ');
}

function makeRequest(routePath) {
	const pathParts = routePath.split('/');
	const method =
		routePath.endsWith('zoom-cron-sync') || routePath.endsWith('zoom-debug') ? 'GET' : 'POST';
	const noAuth =
		routePath.endsWith('zoom-webhook') ||
		routePath.endsWith('zoom-cron-sync') ||
		routePath.endsWith('razorpay/webhook');

	const req = {
		method,
		header: [
			{ key: 'Authorization', value: 'Bearer {{access_token}}', description: 'JWT from login/verify-otp' },
		],
		url: {
			raw: '{{base_url}}' + routePath,
			host: ['{{base_url}}api'],
			path: pathParts.slice(1),
		},
	};

	if (method === 'POST') {
		const formdata = fieldsForRoute(routePath);
		req.body = { mode: 'formdata', formdata };
	}
	if (noAuth) {
		req.auth = { type: 'noauth' };
		delete req.header;
	}
	// Public auth endpoints — no Bearer required
	const seg2 = pathParts[2] || '';
	if (
		pathParts[1] === 'user' &&
		['signup', 'login', 'send-otp', 'verify-otp', 'update-password'].includes(seg2)
	) {
		req.header = [];
		req.auth = { type: 'noauth' };
	}
	if (routePath === 'api/main/post-enquiry' || routePath === 'api/main/pages') {
		req.header = [];
		req.auth = { type: 'noauth' };
	}

	return req;
}

function userSubfolder(routePath) {
	const p2 = routePath.split('/')[2] || '';
	if (/^(signup|login|send-otp|verify-otp|logout)$/.test(p2)) return 'Auth';
	if (/^(update-profile|update-password|change-password|delete-account)$/.test(p2)) return 'Profile & account';
	if (p2.includes('attendance')) return 'Attendance';
	return 'Other';
}

function batchSubfolder(routePath) {
	const p2 = routePath.split('/')[2] || '';
	if (p2.startsWith('homework') || p2 === 'my-homework-submissions') return 'Homework';
	if (p2.startsWith('notes')) return 'Notes';
	if (p2.startsWith('video-lecture')) return 'Video lectures';
	if (p2.startsWith('library')) return 'Library';
	if (p2.startsWith('live-class') || p2.startsWith('recorded-meeting') || p2.includes('zoom')) return 'Live class';
	if (p2.includes('attendance') || p2.includes('roster') || p2.includes('matrix')) return 'Attendance';
	if (p2.includes('exam') || p2.startsWith('student-exam') || p2.startsWith('upcoming-exam')) return 'Exams';
	if (p2.startsWith('teacher-')) return 'Teacher batches';
	if (p2.startsWith('batch-') || p2 === 'slider-list' || p2 === 'batch-list') return 'Batch & chapters';
	return 'General';
}

function mainSubfolder(routePath) {
	const p2 = routePath.split('/')[2] || '';
	if (/country-list|state-list|city-list/.test(p2)) return 'Locations';
	if (/review/.test(p2) || p2 === 'post-enquiry') return 'Reviews & enquiry';
	if (p2 === 'pages' || p2 === 'site-details' || p2 === 'get_defaults_requirements') return 'Site & content';
	if (p2 === 'notifications-list') return 'Notifications';
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

function nestModule(moduleId, items) {
	if (moduleId === 'user') {
		const order = ['Auth', 'Profile & account', 'Attendance', 'Other'];
		const by = new Map();
		for (const it of items) {
			const sub = userSubfolder(it._route);
			if (!by.has(sub)) by.set(sub, []);
			by.get(sub).push(it);
		}
		return order.filter((n) => by.has(n)).map((n) => ({ name: n, item: by.get(n) }));
	}
	if (moduleId === 'batch') {
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
			'Teacher batches',
		];
		const by = new Map();
		for (const it of items) {
			const sub = batchSubfolder(it._route);
			if (!by.has(sub)) by.set(sub, []);
			by.get(sub).push(it);
		}
		return order.filter((n) => by.has(n)).map((n) => ({ name: n, item: by.get(n) }));
	}
	if (moduleId === 'main') {
		const order = ['Locations', 'Site & content', 'Notifications', 'Reviews & enquiry', 'General'];
		const by = new Map();
		for (const it of items) {
			const sub = mainSubfolder(it._route);
			if (!by.has(sub)) by.set(sub, []);
			by.get(sub).push(it);
		}
		return order.filter((n) => by.has(n)).map((n) => ({ name: n, item: by.get(n) }));
	}
	return items;
}

function build() {
	execFileSync(process.execPath, [EXTRACT_SCRIPT], { stdio: 'inherit' });
	const php = fs.readFileSync(ROUTES_FILE, 'utf8');
	const routes = parseRoutes(php);
	const byModule = new Map();
	for (const m of MODULE_ORDER) byModule.set(m.id, []);

	for (const routePath of routes) {
		const mod = routePath.split('/')[1];
		const item = {
			name: friendlyName(routePath),
			request: makeRequest(routePath),
			response: [],
			_route: routePath,
		};
		if (byModule.has(mod)) {
			byModule.get(mod).push(item);
		}
	}

	const topFolders = [];
	for (const m of MODULE_ORDER) {
		const arr = byModule.get(m.id);
		if (!arr.length) continue;
		const nested = nestModule(m.id, arr);
		const isNested = nested.length && nested[0].item && Array.isArray(nested[0].item);
		// strip internal _route before write
		const strip = (list) =>
			list.map((entry) => {
				if (entry.item && Array.isArray(entry.item)) {
					return { name: entry.name, item: strip(entry.item) };
				}
				const { _route, ...rest } = entry;
				return rest;
			});
		if (isNested) {
			topFolders.push({ name: m.label, item: strip(nested) });
		} else {
			topFolders.push({ name: m.label, item: strip(arr) });
		}
	}

	const outDoc = {
		info: {
			_postman_id: crypto.randomUUID(),
			name: 'Gradmo App — module wise',
			description:
				'Generated from application/config/routes.php with form-data body keys matching API controllers. Regenerate: node docs/build-gradmo-modulewise-from-routes.js\n\nSet collection variable base_url (e.g. http://localhost/education/). After login/verify-otp, set access_token from response.',
			schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
		},
		item: topFolders,
		variable: [
			{ key: 'base_url', value: 'http://localhost/education/' },
			{ key: 'access_token', value: '' },
		],
	};

	fs.writeFileSync(OUT, JSON.stringify(outDoc, null, 2) + '\n', 'utf8');
	console.log('Wrote', OUT);
	console.log('API routes:', routes.length);
	for (const m of MODULE_ORDER) {
		const n = byModule.get(m.id).length;
		if (n) console.log(' ', m.label + ':', n);
	}
}

build();
