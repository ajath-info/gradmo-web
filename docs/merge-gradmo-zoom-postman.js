/**
 * Merge Gradmo_App + Zoom_APIs into one Postman collection.
 * Run: node docs/merge-gradmo-zoom-postman.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const GRADMO = path.join(__dirname, 'Gradmo_App.postman_collection.json');
const ZOOM = path.join(__dirname, '..', 'Zoom_APIs.postman_collection.json');
const OUT = path.join(__dirname, '..', 'Gradmo_Complete_API.postman_collection.json');

function collectPathKeys(items, set) {
	if (!set) set = new Set();
	for (const it of items || []) {
		if (it.request && it.request.url && Array.isArray(it.request.url.path)) {
			set.add(it.request.method + '|' + it.request.url.path.join('/'));
		}
		if (it.item) collectPathKeys(it.item, set);
	}
	return set;
}

function dedupeFolderItems(zoomItems, seen) {
	const out = [];
	for (const it of zoomItems || []) {
		if (it.item) {
			const nested = dedupeFolderItems(it.item, seen);
			if (nested.length) {
				out.push({ ...it, item: nested });
			}
			continue;
		}
		if (!it.request || !it.request.url || !Array.isArray(it.request.url.path)) {
			out.push(it);
			continue;
		}
		const key = it.request.method + '|' + it.request.url.path.join('/');
		if (seen.has(key)) {
			continue;
		}
		seen.add(key);
		out.push(it);
	}
	return out;
}

const gradmo = JSON.parse(fs.readFileSync(GRADMO, 'utf8'));
const zoom = JSON.parse(fs.readFileSync(ZOOM, 'utf8'));

const merged = {
	info: {
		_postman_id: 'gradmo-complete-api-collection',
		name: 'Gradmo Complete API (App + Zoom)',
		description:
			'Merged Gradmo mobile/web APIs and Zoom / live class APIs.\n\n' +
			'Variables: base_url, access_token, batch_id, live_class_id\n\n' +
			'• Top-level folders: original Gradmo requests + route sync folders\n' +
			'• Folder "Zoom (live class & recordings)": Zoom-focused requests from Zoom_APIs collection\n\n' +
			'Auth: Bearer {{access_token}} except zoom-webhook and zoom-cron-sync.',
		schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
	},
	item: [
		...(gradmo.item || []),
		{
			name: 'Zoom (live class & recordings)',
			description: zoom.info && zoom.info.description ? zoom.info.description : 'Zoom REST, live class, recordings, webhook.',
			item: zoom.item || [],
		},
	],
	auth: gradmo.auth || {
		type: 'bearer',
		bearer: [{ key: 'token', value: '{{access_token}}', type: 'string' }],
	},
	event: gradmo.event || [],
	variable: [],
};

const varKeys = new Set();
for (const src of [gradmo, zoom]) {
	for (const v of src.variable || []) {
		if (!varKeys.has(v.key)) {
			varKeys.add(v.key);
			merged.variable.push(v);
		}
	}
}
if (!merged.variable.some((v) => v.key === 'base_url')) {
	merged.variable.unshift({ key: 'base_url', value: 'http://localhost/education/' });
}
if (!merged.variable.some((v) => v.key === 'access_token')) {
	merged.variable.push({ key: 'access_token', value: '' });
}
if (!merged.variable.some((v) => v.key === 'batch_id')) {
	merged.variable.push({ key: 'batch_id', value: '3' });
}
if (!merged.variable.some((v) => v.key === 'live_class_id')) {
	merged.variable.push({ key: 'live_class_id', value: '0' });
}

fs.writeFileSync(OUT, JSON.stringify(merged, null, 2));
console.log('Written:', OUT);
console.log('Gradmo top-level items:', (gradmo.item || []).length);
console.log('Zoom subfolders/requests:', (zoom.item || []).length);
