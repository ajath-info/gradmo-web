'use strict';
const fs = require('fs');
const path = require('path');

const routesPhp = fs.readFileSync(path.join(__dirname, '..', 'application', 'config', 'routes.php'), 'utf8');
const re = /\$route\['(api\/[^']+)'\]\s*=\s*'([^']+)'/g;
const routes = [];
let m;
while ((m = re.exec(routesPhp))) routes.push({ path: m[1], target: m[2] });

const root = path.join(__dirname, '..', 'application', 'controllers');

function readMethod(file, method) {
	if (!fs.existsSync(file)) return null;
	const src = fs.readFileSync(file, 'utf8');
	const reFn = new RegExp('function\\s+' + method + '\\s*\\([^)]*\\)\\s*\\{', 'g');
	const hit = reFn.exec(src);
	if (!hit) return null;
	let i = hit.index + hit[0].length;
	let depth = 1;
	const start = i;
	while (i < src.length && depth > 0) {
		const c = src[i++];
		if (c === '{') depth++;
		else if (c === '}') depth--;
	}
	return src.slice(start, i - 1);
}

function keysFrom(body) {
	const keys = new Set();
	if (!body) return keys;
	for (const mm of body.matchAll(/\$data\['([^']+)'\]/g)) keys.add(mm[1]);
	for (const mm of body.matchAll(/\$data\["([^"]+)"\]/g)) keys.add(mm[1]);
	for (const mm of body.matchAll(/\$_FILES\['([^']+)'\]/g)) keys.add(mm[1] + '|file');
	for (const mm of body.matchAll(/do_upload\('([^']+)'\)/g)) keys.add(mm[1] + '|file');
	// foreach ($fields as $field) { if (isset($data[$field]))
	const blockRe = /\$fields\s*=\s*array\s*\(([\s\S]*?)\)\s*;/g;
	let bm;
	while ((bm = blockRe.exec(body)) !== null) {
		for (const fm of bm[1].matchAll(/'([^']+)'/g)) keys.add(fm[1]);
	}
	return keys;
}

const out = {};
for (const r of routes) {
	const parts = r.target.split('/');
	const file = path.join(root, parts.slice(0, -1).join('/') + '.php');
	const method = parts[parts.length - 1];
	const body = readMethod(file, method);
	const keys = [...keysFrom(body)].sort();
	out[r.path] = { method, keys, found: !!body };
}

fs.writeFileSync(path.join(__dirname, 'api-route-keys.json'), JSON.stringify(out, null, 2));
console.log('Wrote api-route-keys.json, routes:', routes.length);
