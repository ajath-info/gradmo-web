// Global image fallbacks for batch / teacher / institute images
(function () {
	var d = document;
	var defaults = {
		batch: (window.baseUrl || (d.getElementById('base_url') && d.getElementById('base_url').value) || '') + 'assets/images/default-batch.png',
		teacher: (window.baseUrl || (d.getElementById('base_url') && d.getElementById('base_url').value) || '') + 'assets/images/default-teacher.png',
		institute: (window.baseUrl || (d.getElementById('base_url') && d.getElementById('base_url').value) || '') + 'assets/images/default-institute.png'
	};

	function applyFallback(img) {
		if (!img || img.dataset.fallbackApplied === '1') {
			return;
		}
		var type = img.getAttribute('data-fallback-type') || '';
		var key = type && defaults[type] ? type : 'batch';
		if (!defaults[key]) {
			return;
		}
		img.dataset.fallbackApplied = '1';
		img.src = defaults[key];
	}

	document.addEventListener('error', function (e) {
		var t = e.target;
		if (t && t.tagName === 'IMG' && (t.hasAttribute('data-fallback-type') || t.hasAttribute('data-has-fallback'))) {
			applyFallback(t);
		}
	}, true);

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.forEach.call(document.querySelectorAll('img[data-fallback-type], img[data-has-fallback]'), function (img) {
			if (!img.getAttribute('src')) {
				applyFallback(img);
			}
		});
	});
})();

