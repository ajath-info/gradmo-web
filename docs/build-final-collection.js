/**
 * Build final_collection.json — Gradmo_App unchanged + Zoom folder appended only.
 * Does NOT modify any existing request keys, form fields, or URLs.
 * Run: node docs/build-final-collection.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const GRADMO = path.join(__dirname, 'Gradmo_App.postman_collection.json');
const ZOOM = path.join(__dirname, '..', 'Zoom_APIs.postman_collection.json');
const OUT = path.join(__dirname, '..', 'final_collection.json');

const gradmo = JSON.parse(fs.readFileSync(GRADMO, 'utf8'));
const zoom = JSON.parse(fs.readFileSync(ZOOM, 'utf8'));

// Deep clone — existing collection body is copied verbatim.
const final = JSON.parse(JSON.stringify(gradmo));

// Only append Zoom as a new top-level folder (no edits to prior items).
final.item.push({
	name: 'Zoom (live class & recordings)',
	description:
		(zoom.info && zoom.info.description) ||
		'Zoom REST, live class, recordings. Credentials: zoom_api_credentials.',
	item: JSON.parse(JSON.stringify(zoom.item || [])),
});

// Collection metadata: name only; keep _postman_id, schema, exporter fields from Gradmo.
final.info = final.info || {};
final.info.name = 'final_collection';
final.info.description =
	(final.info.description || '') +
	'\n\n+ Folder "Zoom (live class & recordings)" appended without changing existing requests.';

// Add collection variables only if missing (do not change existing variable keys/values).
const existingVarKeys = new Set((final.variable || []).map((v) => v.key));
for (const v of zoom.variable || []) {
	if (!existingVarKeys.has(v.key)) {
		final.variable.push(JSON.parse(JSON.stringify(v)));
		existingVarKeys.add(v.key);
	}
}

fs.writeFileSync(OUT, JSON.stringify(final, null, 2));

const countRequests = (items, n) => {
	n = n || 0;
	for (const it of items || []) {
		if (it.request) n++;
		if (it.item) n = countRequests(it.item, n);
	}
	return n;
};

console.log('Written:', OUT);
console.log('Top-level folders/items:', final.item.length);
console.log('Total requests (approx):', countRequests(final.item));
