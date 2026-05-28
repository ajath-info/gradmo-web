/**
 * Download OMR / ORM answer sheet as PDF.
 * Requires: apiUrl, token, examId; optional submissionId, mode.
 * v2 — server returns pdfBase64 (FPDF), not HTML.
 */
(function (global) {
	'use strict';

	function apiOk(status) {
		return status === true || status === 'true' || status === 1 || status === '1';
	}

	function base64ToBlob(base64, contentType) {
		var binary = atob(base64);
		var len = binary.length;
		var bytes = new Uint8Array(len);
		for (var i = 0; i < len; i++) {
			bytes[i] = binary.charCodeAt(i);
		}
		return new Blob([bytes], { type: contentType || 'application/pdf' });
	}

	function downloadExamOmrSheet(options) {
		options = options || {};
		var apiUrl = options.apiUrl || '';
		var token = options.token || '';
		var examId = parseInt(options.examId, 10) || 0;
		if (!apiUrl || !token || examId < 1) {
			return Promise.reject(new Error('Missing OMR sheet configuration.'));
		}
		var body = {
			access_token: token,
			exam_id: examId,
			mode: options.mode || 'blank'
		};
		if (options.submissionId) {
			body.submission_id = parseInt(options.submissionId, 10);
			body.mode = 'filled';
		}
		if (options.studentName) {
			body.student_name = String(options.studentName);
		}

		return fetch(apiUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': 'Bearer ' + token
			},
			body: JSON.stringify(body)
		}).then(function (r) { return r.json(); }).then(function (j) {
			if (!apiOk(j.status) || !j.data) {
				throw new Error((j && j.msg) ? j.msg : 'Could not generate OMR sheet.');
			}
			var pdfBase64 = j.data.pdfBase64 || '';
			if (!pdfBase64) {
				throw new Error('PDF was not returned from the server.');
			}
			var fileName = j.data.fileName || 'OMR_Sheet.pdf';
			if (fileName.indexOf('.pdf') === -1) {
				fileName += '.pdf';
			}
			var blob = base64ToBlob(pdfBase64, j.data.contentType || 'application/pdf');
			var link = document.createElement('a');
			link.href = URL.createObjectURL(blob);
			link.download = fileName;
			document.body.appendChild(link);
			link.click();
			setTimeout(function () {
				URL.revokeObjectURL(link.href);
				link.remove();
			}, 300);
			return j;
		});
	}

	global.downloadExamOmrSheet = downloadExamOmrSheet;
})(typeof window !== 'undefined' ? window : this);
