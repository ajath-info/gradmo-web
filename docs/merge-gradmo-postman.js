/**
 * Merges uploads/Gradmo App.postman_collection.json with missing routes from routes.php (314-415).
 * Run: node docs/merge-gradmo-postman.js
 */
const fs = require('fs');
const path = require('path');
const os = require('os');

const uploadName = 'c__Users_Gajendra_Desktop_Gradmo_App.postman_collection-L1-L2125-0.json';
const candidates = [
	path.join(__dirname, 'gradmo-app-source.postman_collection.json'),
	path.join(__dirname, '..', 'uploads', uploadName),
	path.join(os.homedir(), '.cursor', 'projects', 'c-xampp-htdocs-education', 'uploads', uploadName),
	process.argv[2],
].filter(Boolean);

let src = null;
for (const c of candidates) {
	if (c && fs.existsSync(c)) {
		src = c;
		break;
	}
}
if (!src) {
	console.error('Source collection not found. Place your export as:\n  docs/gradmo-app-source.postman_collection.json\nor pass path: node docs/merge-gradmo-postman.js "C:/path/Gradmo App.postman_collection.json"');
	process.exit(1);
}
const outPath = path.join(__dirname, 'Gradmo_App.postman_collection.json');

const j = JSON.parse(fs.readFileSync(src, 'utf8'));

// Idempotent: remove previous merge folder if re-running
j.item = (j.item || []).filter(function (it) {
	return it.name !== 'API — routes.php (314–415) additions';
});

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

function text(key, value, disabled) {
	const o = { key, value: String(value), type: 'text' };
	if (disabled) o.disabled = true;
	return o;
}

function makeItem(displayName, pathParts, formdataFields) {
	const pathStr = pathParts.join('/');
	return {
		name: displayName,
		request: {
			method: 'POST',
			header: [],
			body: {
				mode: 'formdata',
				formdata: formdataFields,
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
	text('batch_id', '1'),
	text('page', '1'),
	text('limit', '20'),
	text('search', '', true),
];

const additions = [
	['main/approve-review', ['main', 'approve-review'], [text('review_id', '1')]],
	['main/institute-reviews-list', ['main', 'institute-reviews-list'], [text('page', '1'), text('limit', '20'), text('status', '0', true)]],
	['main/review-detail', ['main', 'review-detail'], [text('review_id', '1')]],
	['main/update-review', ['main', 'update-review'], [text('review_id', '1'), text('rating', '5'), text('msg', 'updated')]],
	['main/delete-review', ['main', 'delete-review'], [text('review_id', '1')]],
	['payment/razorpay/verify-payment', ['payment', 'razorpay', 'verify-payment'], [
		text('razorpay_order_id', ''),
		text('razorpay_payment_id', ''),
		text('razorpay_signature', ''),
		text('student_id', '1'),
		text('batch_id', '1'),
	]],
	['payment/razorpay/fetch-payment', ['payment', 'razorpay', 'fetch-payment'], [text('payment_id', '')]],
	['payment/razorpay/order-status', ['payment', 'razorpay', 'order-status'], [text('order_id', '')]],
	['payment/razorpay/webhook', ['payment', 'razorpay', 'webhook'], [text('payload', '{}', true)]],
	['batch/batch-subjects', ['batch', 'batch-subjects'], [text('batch_id', '1')]],
	['batch/attendance-roster', ['batch', 'attendance-roster'], [text('batch_id', '1'), text('date', '2026-05-13')]],
	['batch/attendance-roster-matrix', ['batch', 'attendance-roster-matrix'], [text('batch_id', '1'), text('year', '2026'), text('month', '5')]],
	['batch/attendance-matrix-save', ['batch', 'attendance-matrix-save'], [text('batch_id', '1'), text('default_time', '10:30'), text('entries', '[]')]],
	['batch/homework-list', ['batch', 'homework-list'], [...common]],
	['batch/homework-details', ['batch', 'homework-details'], [text('homework_id', '1')]],
	['batch/homework-add', ['batch', 'homework-add'], [text('batch_id', '1'), text('subject_id', '1'), text('description', 'Homework', true)]],
	['batch/homework-edit', ['batch', 'homework-edit'], [text('homework_id', '1')]],
	['batch/homework-delete', ['batch', 'homework-delete'], [text('homework_id', '1')]],
	['batch/homework-submit', ['batch', 'homework-submit'], [text('homework_id', '1'), text('submission_text', 'My answer')]],
	['batch/homework-submissions', ['batch', 'homework-submissions'], [text('homework_id', '1'), text('batch_id', '1'), text('page', '1'), text('limit', '20')]],
	['batch/homework-submission-details', ['batch', 'homework-submission-details'], [text('submission_id', '1')]],
	['batch/homework-evaluate', ['batch', 'homework-evaluate'], [text('submission_id', '1'), text('marks', '10'), text('remark', 'Good')]],
	['batch/my-homework-submissions', ['batch', 'my-homework-submissions'], [...common]],
	['batch/library-add-book', ['batch', 'library-add-book'], [text('batch_id', '1'), text('title', 'Book', true)]],
	['batch/library-edit-book', ['batch', 'library-edit-book'], [text('book_id', '1'), text('batch_id', '1')]],
	['batch/library-delete-book', ['batch', 'library-delete-book'], [text('book_id', '1')]],
	['batch/library-book-details', ['batch', 'library-book-details'], [text('book_id', '1'), text('batch_id', '1')]],
	['batch/notes-list', ['batch', 'notes-list'], [text('batch_id', '1'), text('page', '1'), text('limit', '20'), text('search', '', true)]],
	['batch/notes-add', ['batch', 'notes-add'], [text('batch_id', '1'), text('title', 'Note title'), text('topic', 'Topic'), text('subject', 'Subject')]],
	['batch/notes-edit', ['batch', 'notes-edit'], [text('notes_id', '1'), text('batch_id', '1'), text('title', 'Updated title', true)]],
	['batch/notes-delete', ['batch', 'notes-delete'], [text('notes_id', '1'), text('batch_id', '1')]],
	['batch/notes-details', ['batch', 'notes-details'], [text('notes_id', '1'), text('batch_id', '1')]],
	['batch/live-class-list', ['batch', 'live-class-list'], [text('batch_id', '1'), text('page', '1'), text('limit', '20')]],
	['batch/live-class-details', ['batch', 'live-class-details'], [text('live_class_id', '1')]],
	['batch/video-lecture-list', ['batch', 'video-lecture-list'], [...common]],
	['batch/video-lecture-details', ['batch', 'video-lecture-details'], [text('video_lecture_id', '1')]],
	['batch/video-lecture-add', ['batch', 'video-lecture-add'], [text('batch_id', '1')]],
	['batch/video-lecture-edit', ['batch', 'video-lecture-edit'], [text('video_lecture_id', '1')]],
	['batch/video-lecture-delete', ['batch', 'video-lecture-delete'], [text('video_lecture_id', '1')]],
	['batch/upcoming-exam-list', ['batch', 'upcoming-exam-list'], [...common]],
	['batch/upcoming-exam-details', ['batch', 'upcoming-exam-details'], [text('exam_id', '1')]],
	['batch/student-exam-dashboard', ['batch', 'student-exam-dashboard'], [text('batch_id', '1')]],
	['batch/student-exam-paper', ['batch', 'student-exam-paper'], [text('exam_id', '1')]],
	['batch/student-submit-exam', ['batch', 'student-submit-exam'], [text('exam_id', '1'), text('started_at', '2026-05-13 10:00:00'), text('answers', '{"1":"A"}')]],
	['batch/student-exam-result', ['batch', 'student-exam-result'], [text('exam_id', '1')]],
	['batch/exam-manage-list', ['batch', 'exam-manage-list'], [...common]],
	['batch/exam-add', ['batch', 'exam-add'], [text('batch_id', '1'), text('name', 'Mock'), text('type', '1'), text('format', '1')]],
	['batch/exam-edit', ['batch', 'exam-edit'], [text('exam_id', '1'), text('batch_id', '1')]],
	['batch/exam-delete', ['batch', 'exam-delete'], [text('exam_id', '1')]],
	['batch/batch-chapters', ['batch', 'batch-chapters'], [text('batch_id', '1'), text('subject_id', '1')]],
];

const newItems = [];
for (const [key, pathParts, fields] of additions) {
	if (seen.has(key)) continue;
	const display = 'api/' + pathParts.join('/');
	newItems.push(makeItem(display, pathParts, fields));
}

const folder = {
	name: 'API — routes.php (314–415) additions',
	description:
		'Matches application/config/routes.php. Same style as this collection: POST, formdata, url host {{base_url}}api, path segments. Bearer auth on collection applies. PHP read_request_data() accepts form fields and JSON.',
	item: newItems,
};

j.item = j.item.concat(folder);
j.info = j.info || {};
const note =
	'\n\nFolder "API — routes.php (314–415) additions" lists endpoints missing from the original export.';
	j.info.description = (j.info.description || '') + note;

fs.writeFileSync(outPath, JSON.stringify(j, null, 2));
console.log('Source:', src);
console.log('Output:', outPath);
console.log('New requests:', newItems.length);
