'use strict';

const fs = require('fs');
const path = require('path');

const ROUTE_KEYS_PATH = path.join(__dirname, 'api-route-keys.json');

/** Prefer snake_case API names; drop camelCase duplicates when snake exists. */
const CANONICAL_KEY = {
	batchId: 'batch_id',
	batchName: 'batch_name',
	batchMode: 'batch_mode',
	batchOfferPrice: 'batch_offer_price',
	batchPrice: 'batch_price',
	batchType: 'batch_type',
	categoryId: 'category_id',
	subcategoryId: 'subcategory_id',
	instituteId: 'institute_id',
	subjectId: 'subject_id',
	planId: 'plan_id',
	cat_id: 'category_id',
	categoryId: 'category_id',
	current_pass: 'current_password',
	new_pass: 'new_password',
	confirm_pass: 'confirm_password',
};

/** Keys to skip when a preferred alias is already present. */
const DROP_IF_CANONICAL = new Set(['batchId', 'batchName', 'categoryId', 'subcategoryId', 'instituteId', 'subjectId', 'planId', 'cat_id', 'current_pass', 'new_pass', 'confirm_pass']);

/** Extra keys not always found by static extraction. */
const ROUTE_EXTRA_KEYS = {
	'api/user/user_details': ['batch_id'],
	'api/user/payment-history': ['student_id', 'batch_id'],
	'api/plan/plans': ['batch_id'],
	'api/plan/promo-codes': ['plan_id', 'batch_id'],
	'api/payment/razorpay/create-order': ['plan_id', 'promo_id', 'batch_id'],
	'api/institute/details': ['latitude', 'longitude'],
	'api/batch/teacher-batches': ['search', 'list'],
	'api/batch/teacher-batch-subjects': ['batch_id'],
	'api/batch/batch-zoom-join': ['batch_id'],
};

const USES_PAGINATION =
	/-(list|submissions|listing|manage-list|submission-list|reviews-list|notifications-list|payment-history|batch-list|teacher-batches|courses-list|my-homework-submissions|homework-submissions|homework-list|upcoming-exam-list|exam-manage-list|institute-reviews-list|recorded-meeting-list|live-class-list|library-list|notes-list|video-lecture-list|slider-list)$/;

const KEY_SAMPLES = {
	username: 'user@example.com',
	password: '123456',
	name: 'John Doe',
	email: 'user@example.com',
	mobile: '9999999999',
	user_type: 'student',
	device_id: 'device-uuid-001',
	device_token: 'fcm-token-here',
	device_type: 'android',
	otp: '1234',
	batch_id: '6',
	homework_id: '1',
	submission_id: '1',
	exam_id: '1',
	book_id: '1',
	notes_id: '1',
	video_lecture_id: '1',
	live_class_id: '1',
	recorded_meeting_id: '1',
	institute_id: '69',
	review_id: '1',
	student_id: '20',
	subject_id: '1',
	country_id: '105',
	state_id: '1',
	page: '1',
	limit: '20',
	search: '',
	date: '2026-05-13',
	attendance_date: '2026-05-13',
	time: '10:30',
	description: 'Description text',
	submission_text: 'My answer',
	marks: '10',
	remark: 'Good work',
	eval_status: '1',
	answers: '{"1":"A"}',
	question_answer: '{"1":"A"}',
	start_time: '2026-05-13 10:00:00',
	started_at: '2026-05-13 10:00:00',
	entries: '[]',
	default_time: '10:30',
	amount_in_rupees: '1',
	amount_in_paise: '100',
	razorpay_order_id: '',
	razorpay_payment_id: '',
	razorpay_signature: '',
	order_id: '',
	payment_id: '',
	rating: '5',
	msg: 'Review message',
	latitude: '28.6139',
	longitude: '77.2090',
	order_field: 'name',
	order_type: 'ASC',
	list: 'All',
	notification_type: 'general',
	page_type: 'privacy_policy',
	topic: 'Topic',
	title: 'Title',
	subject: 'Subject',
	url: 'https://example.com',
	video_type: 'youtube',
	preview_type: 'url',
	agenda: 'Class agenda',
	duration: '60',
	start_time_zoom: '2026-05-13 10:00:00',
	timezone: 'Asia/Kolkata',
	batch_name: 'New Batch',
	institute_id_teacher: '69',
	question_ids: '1,2,3',
	total_marks: '100',
	total_question: '10',
	time_duration: '60',
	type: '1',
	format: '1',
	mock_sheduled_date: '2026-05-20',
	mock_sheduled_time: '10:00',
	marking_parcent: '25',
	sort_by: 'id',
	sort_dir: 'desc',
	sort: 'name',
	year: '2026',
	month: '5',
	day_status: 'present',
	attendance_status: '1',
	student_ids: '20,21',
	current_password: '123456',
	new_password: 'newpass123',
	confirm_password: 'newpass123',
	plan_id: '1',
	promo_id: '1',
	currency: 'INR',
	receipt: 'order_rcpt_1',
	admin_id: '1',
	eval_status_filter: '0',
	topic: 'Live class topic',
	action: 'end',
	sync: '0',
	recording_id: '',
	mode: 'preview',
	show_correct: '1',
	student_name: 'Student Name',
	pdf_file: '',
};

const KEY_DESCRIPTIONS = {
	user_type: 'student | teacher | institute',
	device_type: 'android | ios',
	pdf_file: 'PDF file (homework/notes/library)',
	submission_file: 'Student submission attachment',
	video_file: 'Video file upload',
	image: 'Profile image file or base64 string',
	entries: 'JSON array: [{student_id,date,time,day_status}]',
	answers: 'JSON map question_id => answer',
	question_answer: 'Alternate JSON answers field',
	eval_status: '0=pending, 1=evaluated',
	list: 'All | My (batch-list)',
	order_field: 'name | distance (institute listing)',
	contact_no: 'Alias for mobile on profile update',
	mobile: 'Phone number',
	page_type: 'CMS page slug/key',
};

function loadRouteKeys() {
	if (!fs.existsSync(ROUTE_KEYS_PATH)) {
		throw new Error('Missing api-route-keys.json — run: node docs/extract-api-keys.js');
	}
	return JSON.parse(fs.readFileSync(ROUTE_KEYS_PATH, 'utf8'));
}

function normalizeKey(raw) {
	const base = raw.replace('|file', '');
	return CANONICAL_KEY[base] || base;
}

function fieldFromSpec(spec, textFn) {
	const isFile = spec.endsWith('|file');
	const key = isFile ? spec.replace('|file', '') : spec;
	if (isFile) {
		const o = { key, type: 'file', src: [] };
		if (KEY_DESCRIPTIONS[key]) o.description = KEY_DESCRIPTIONS[key];
		return o;
	}
	const sample = KEY_SAMPLES[key] !== undefined ? KEY_SAMPLES[key] : '';
	const desc = KEY_DESCRIPTIONS[key];
	return textFn(key, sample, desc);
}

/**
 * Build form-data fields for a route from controller-extracted keys.
 * @param {string} routePath
 * @param {function} textFn - (key, value, description?, disabled?) => object
 */
function fieldsForRoute(routePath, textFn) {
	const ROUTE_KEYS = loadRouteKeys();
	const info = ROUTE_KEYS[routePath];
	let specs = info && info.keys ? [...info.keys] : [];
	const extras = ROUTE_EXTRA_KEYS[routePath] || [];
	for (const k of extras) {
		if (!specs.includes(k)) specs.push(k);
	}

	const byCanon = new Map();
	for (const spec of specs) {
		const raw = spec.replace('|file', '');
		if (DROP_IF_CANONICAL.has(raw)) {
			const canon = normalizeKey(raw);
			if (specs.some((s) => s.replace('|file', '') === canon)) continue;
		}
		const canon = normalizeKey(raw);
		const isFile = spec.endsWith('|file');
		const normalized = isFile ? canon + '|file' : canon;
		if (!byCanon.has(canon)) byCanon.set(canon, normalized);
		else if (isFile) byCanon.set(canon, normalized);
	}

	if (USES_PAGINATION.test(routePath)) {
		if (!byCanon.has('page')) byCanon.set('page', 'page');
		if (!byCanon.has('limit')) byCanon.set('limit', 'limit');
	}
	// homework-list uses pagination but name does not match suffix list
	if (routePath === 'api/batch/homework-list') {
		if (!byCanon.has('page')) byCanon.set('page', 'page');
		if (!byCanon.has('limit')) byCanon.set('limit', 'limit');
	}
	if (routePath === 'api/batch/library-list' || routePath === 'api/batch/notes-list') {
		if (!byCanon.has('page')) byCanon.set('page', 'page');
		if (!byCanon.has('limit')) byCanon.set('limit', 'limit');
	}
	if (routePath === 'api/batch/upcoming-exam-list' || routePath === 'api/batch/exam-manage-list') {
		if (!byCanon.has('sort_by')) byCanon.set('sort_by', 'sort_by');
		if (!byCanon.has('sort_dir')) byCanon.set('sort_dir', 'sort_dir');
	}

	// institute listing uses page/limit via pagination helper
	if (routePath === 'api/institute/listing') {
		if (!byCanon.has('page')) byCanon.set('page', 'page');
		if (!byCanon.has('limit')) byCanon.set('limit', 'limit');
	}

	const order = [...byCanon.values()];
	return order.map((s) => fieldFromSpec(s, textFn));
}

module.exports = { fieldsForRoute, loadRouteKeys };
