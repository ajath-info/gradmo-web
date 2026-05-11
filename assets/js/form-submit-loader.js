(function () {
	if (window.__formSubmitLoaderInit) {
		return;
	}
	window.__formSubmitLoaderInit = true;

	var ARMED_ATTR = 'data-submit-loader-armed';
	var LOCKED_ATTR = 'data-submit-loader-locked';
	var DISABLED_ATTR = 'data-submit-loader-disabled';
	var IGNORE_ATTR = 'data-no-submit-loader';

	function getForm(node) {
		if (!node) {
			return null;
		}
		if (node.tagName === 'FORM') {
			return node;
		}
		if (typeof node.closest === 'function') {
			return node.closest('form');
		}
		return null;
	}

	function isSubmitControl(el) {
		if (!el || !el.tagName) {
			return false;
		}
		var tag = el.tagName.toUpperCase();
		if (tag === 'BUTTON') {
			var btnType = (el.getAttribute('type') || 'submit').toLowerCase();
			return btnType === 'submit';
		}
		if (tag === 'INPUT') {
			var inputType = (el.getAttribute('type') || '').toLowerCase();
			return inputType === 'submit' || inputType === 'image';
		}
		return false;
	}

	function showLoader() {
		var nodes = document.querySelectorAll('.edu_preloader');
		Array.prototype.forEach.call(nodes, function (el) {
			el.style.backgroundColor = 'rgba(255,255,255,0.80)';
			el.style.display = 'block';
		});
	}

	function unlockForm(form) {
		if (!form) {
			return;
		}
		form.removeAttribute(ARMED_ATTR);
		form.removeAttribute(LOCKED_ATTR);
		Array.prototype.forEach.call(form.querySelectorAll('button, input[type="submit"], input[type="image"]'), function (el) {
			if (el.getAttribute(DISABLED_ATTR) === '1') {
				el.disabled = false;
				el.removeAttribute(DISABLED_ATTR);
			}
		});
	}

	function lockForm(form) {
		if (!form) {
			return;
		}
		form.setAttribute(LOCKED_ATTR, '1');
		form.removeAttribute(ARMED_ATTR);
		Array.prototype.forEach.call(form.querySelectorAll('button, input[type="submit"], input[type="image"]'), function (el) {
			if (!isSubmitControl(el) || el.disabled) {
				return;
			}
			el.setAttribute(DISABLED_ATTR, '1');
			el.disabled = true;
		});
		showLoader();
	}

	document.addEventListener('click', function (event) {
		var target = event.target;
		if (!target) {
			return;
		}
		var control = typeof target.closest === 'function'
			? target.closest('button, input[type="submit"], input[type="image"]')
			: target;
		if (!isSubmitControl(control)) {
			return;
		}
		var form = control.form || getForm(control);
		if (!form || form.hasAttribute(IGNORE_ATTR)) {
			return;
		}
		if (form.getAttribute(ARMED_ATTR) === '1' || form.getAttribute(LOCKED_ATTR) === '1') {
			event.preventDefault();
			event.stopPropagation();
		}
	}, true);

	document.addEventListener('submit', function (event) {
		var form = getForm(event.target);
		if (!form || form.hasAttribute(IGNORE_ATTR)) {
			return;
		}
		if (form.getAttribute(LOCKED_ATTR) === '1') {
			event.preventDefault();
			return;
		}
		if (form.getAttribute(ARMED_ATTR) === '1') {
			event.preventDefault();
			return;
		}

		form.setAttribute(ARMED_ATTR, '1');
		window.setTimeout(function () {
			if (event.defaultPrevented) {
				unlockForm(form);
				return;
			}
			lockForm(form);
		}, 0);
	}, true);

	window.addEventListener('pageshow', function () {
		Array.prototype.forEach.call(document.querySelectorAll('form[' + LOCKED_ATTR + '], form[' + ARMED_ATTR + ']'), function (form) {
			unlockForm(form);
		});
	});
})();
