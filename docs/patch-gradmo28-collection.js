'use strict';

/**
 * Patch Gradmo28_may_collection.json — fix form-data keys from API controllers.
 * Run: node docs/patch-gradmo28-collection.js
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { fieldsForRoute } = require('./postman-route-fields');

const COLLECTION = path.join(__dirname, 'Gradmo28_may_collection.json');
const EXTRACT = path.join(__dirname, 'extract-api-keys.js');

const PATCH_ROUTE =
	/^api\/(batch\/(attendance|homework|my-homework|library|live-class|class-status|live-meeting|recorded-meeting|batch-zoom|batch-notify|video-lecture|upcoming-exam|student-exam|exam-)|user\/(add-attendance|attendance-list))/;

function text(key, value, description, disabled) {
	const o = { key, value: String(value), type: 'text' };
	if (description) o.description = description;
	if (disabled) o.disabled = true;
	return o;
}

function routeFromRequest(req) {
	const p = req && req.url && req.url.path;
	if (!Array.isArray(p) || p.length < 2) return null;
	return 'api/' + p.join('/');
}

function shouldPatch(route) {
	return PATCH_ROUTE.test(route);
}

function mergeFormdata(newFields, oldFields) {
	const oldMap = new Map();
	for (const f of oldFields || []) {
		oldMap.set(f.key, f);
	}
	return newFields.map((f) => {
		const old = oldMap.get(f.key);
		if (!old) return { ...f };
		const merged = { ...f };
		if (f.type === 'text' && old.value !== undefined && old.value !== '') {
			merged.value = old.value;
		}
		if (old.disabled) merged.disabled = true;
		if (old.description && !merged.description) merged.description = old.description;
		return merged;
	});
}

let patched = 0;

function walk(items) {
	if (!Array.isArray(items)) return;
	for (const item of items) {
		if (item.item) {
			walk(item.item);
			continue;
		}
		const req = item.request;
		if (!req || req.method !== 'POST') continue;
		const route = routeFromRequest(req);
		if (!route || !shouldPatch(route)) continue;

		const newFields = fieldsForRoute(route, text);
		const oldFields = req.body && req.body.formdata ? req.body.formdata : [];
		req.body = { mode: 'formdata', formdata: mergeFormdata(newFields, oldFields) };

		// Move query params into body for user add-attendance
		if (route === 'api/user/add-attendance' && req.url.query) {
			const qmap = new Map(req.url.query.map((q) => [q.key, q.value]));
			req.body.formdata = req.body.formdata.map((f) => {
				if (qmap.has(f.key) && (!f.value || f.value === '')) {
					return { ...f, value: qmap.get(f.key) };
				}
				return f;
			});
			delete req.url.query;
			req.url.raw = req.url.raw.split('?')[0];
		}

		patched++;
	}
}

execFileSync(process.execPath, [EXTRACT], { stdio: 'inherit' });

const doc = JSON.parse(fs.readFileSync(COLLECTION, 'utf8'));
walk(doc.item);
fs.writeFileSync(COLLECTION, JSON.stringify(doc, null, 2) + '\n', 'utf8');
console.log('Patched', patched, 'requests in', COLLECTION);
