/**
 * Regroups docs/old_collocton.json into Postman folders by API model
 * (user, main, batch, institute, courses, plan, payment, Other).
 *
 * Run: node docs/regroup-old-collocton.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { flattenRequestItems, groupItemsByApiModel } = require('./old-collocton-helpers');

const collectionPath = path.join(__dirname, 'old_collocton.json');
const j = JSON.parse(fs.readFileSync(collectionPath, 'utf8'));

const flat = flattenRequestItems(j.item || []);
j.item = groupItemsByApiModel(flat);

const note =
	'\n\nCollection requests are grouped by API model (user, main, batch, …). Regenerate with: node docs/regroup-old-collocton.js';
j.info = j.info || {};
j.info.description = (j.info.description || '') + note;

fs.writeFileSync(collectionPath, JSON.stringify(j, null, 2) + '\n', 'utf8');
console.log('Regrouped', collectionPath);
console.log('Folders:', j.item.map((f) => f.name + ' (' + (f.item ? f.item.length : 0) + ')').join(', '));
