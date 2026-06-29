/**
 * Shared helpers for docs/old_collocton.json (Postman v2.1).
 */
'use strict';

/** API first path segment → folder label (matches routes “model”) */
const MODEL_ORDER = [
	{ segment: 'user', folder: 'User' },
	{ segment: 'main', folder: 'Main' },
	{ segment: 'batch', folder: 'Batch' },
	{ segment: 'institute', folder: 'Institute' },
	{ segment: 'courses', folder: 'Courses' },
	{ segment: 'plan', folder: 'Plan' },
	{ segment: 'payment', folder: 'Payment' },
];

/**
 * Flatten nested folders to a list of request items only.
 * @param {Array} items collection.item (may be folders or requests)
 * @returns {Array}
 */
function flattenRequestItems(items) {
	const out = [];
	if (!Array.isArray(items)) {
		return out;
	}
	for (const it of items) {
		if (it.request) {
			out.push(it);
		} else if (Array.isArray(it.item)) {
			out.push.apply(out, flattenRequestItems(it.item));
		}
	}
	return out;
}

/**
 * Group flat requests into Postman folders by first URL path segment after api/.
 * @param {Array} flatRequests
 * @returns {Array<{name:string, item:Array}>}
 */
function groupItemsByApiModel(flatRequests) {
	const buckets = new Map();
	for (const m of MODEL_ORDER) {
		buckets.set(m.segment, []);
	}
	const misc = [];

	for (const it of flatRequests) {
		const pathArr = it.request && it.request.url && it.request.url.path;
		const seg = Array.isArray(pathArr) && pathArr.length ? pathArr[0] : null;
		if (seg && buckets.has(seg)) {
			buckets.get(seg).push(it);
		} else {
			misc.push(it);
		}
	}

	const folders = [];
	for (const m of MODEL_ORDER) {
		const arr = buckets.get(m.segment);
		if (arr.length) {
			folders.push({ name: m.folder, item: arr });
		}
	}
	if (misc.length) {
		folders.push({ name: 'Other', item: misc });
	}
	return folders;
}

module.exports = {
	MODEL_ORDER,
	flattenRequestItems,
	groupItemsByApiModel,
};
