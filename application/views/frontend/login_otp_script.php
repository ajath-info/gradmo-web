<script>
(function () {
	var sendOtpUrl = <?php echo json_encode(site_url('login-otp-send')); ?>;
	var verifyOtpUrl = <?php echo json_encode(site_url('login-otp-verify')); ?>;
	var RESEND_COOLDOWN_SEC = 45;
	var resendIntervalId = null;

	function okSend(j) {
		return j.status === true || j.status === 'true' || j.status === 1 || j.status === '1';
	}

	function clearResendCooldown() {
		if (resendIntervalId !== null) {
			clearInterval(resendIntervalId);
			resendIntervalId = null;
		}
		var rb = document.getElementById('otp_resend_btn');
		var ct = document.getElementById('otp_resend_cooldown_text');
		if (rb) {
			rb.disabled = true;
		}
		if (ct) {
			ct.textContent = '';
		}
	}

	function startResendCooldown() {
		clearResendCooldown();
		var rb = document.getElementById('otp_resend_btn');
		var ct = document.getElementById('otp_resend_cooldown_text');
		if (!rb || !ct) {
			return;
		}
		var left = RESEND_COOLDOWN_SEC;
		rb.disabled = true;
		function refresh() {
			if (left <= 0) {
				ct.textContent = '';
				rb.disabled = false;
				return false;
			}
			ct.textContent = 'Resend in ' + left + 's';
			return true;
		}
		refresh();
		resendIntervalId = setInterval(function () {
			left--;
			if (!refresh()) {
				clearInterval(resendIntervalId);
				resendIntervalId = null;
			}
		}, 1000);
	}

	function showOtpLoginStep2() {
		var wrapCode = document.getElementById('otp_login_code_wrap');
		var wrapVerify = document.getElementById('otp_login_verify_wrap');
		if (wrapCode) { wrapCode.classList.remove('d-none'); }
		if (wrapVerify) { wrapVerify.classList.remove('d-none'); }
		var codeInput = document.getElementById('otp_login_code');
		if (codeInput) {
			codeInput.focus();
		}
	}

	function resetOtpLoginStep2() {
		clearResendCooldown();
		var wrapCode = document.getElementById('otp_login_code_wrap');
		var wrapVerify = document.getElementById('otp_login_verify_wrap');
		if (wrapCode) { wrapCode.classList.add('d-none'); }
		if (wrapVerify) { wrapVerify.classList.add('d-none'); }
		var codeInput = document.getElementById('otp_login_code');
		if (codeInput) { codeInput.value = ''; }
	}
	function okVerify(j) {
		return j.status === true || j.status === 'true' || j.status === '1';
	}

	document.addEventListener('DOMContentLoaded', function () {
		var pwInput = document.getElementById('password_show');
		var pwBtn = document.querySelector('.edu-auth-toggle-pw');
		if (pwBtn && pwInput) {
			pwBtn.addEventListener('click', function () {
				var icon = pwBtn.querySelector('.hide_show, i');
				if (pwInput.type === 'password') {
					pwInput.type = 'text';
					if (icon) {
						icon.classList.remove('fa-eye-slash');
						icon.classList.add('fa-eye');
					}
				} else {
					pwInput.type = 'password';
					if (icon) {
						icon.classList.remove('fa-eye');
						icon.classList.add('fa-eye-slash');
					}
				}
			});
		}

		var sendBtn = document.getElementById('otp_send_btn');
		var verifyBtn = document.getElementById('otp_verify_login_btn');
		if (!sendBtn || !verifyBtn) {
			return;
		}
		var otpTabLink = document.getElementById('tab-otp-link');
		if (otpTabLink) {
			otpTabLink.addEventListener('shown.bs.tab', function () {
				resetOtpLoginStep2();
			});
		}

		function requestLoginOtp(isResend) {
			var mobile = (document.getElementById('otp_login_mobile').value || '').trim().replace(/\D/g, '');
			var user_type = (document.getElementById('otp_login_user_type').value || 'student').toLowerCase();
			if (mobile.length !== 10) {
				if (typeof toastr !== 'undefined') { toastr.error('Enter a valid 10-digit mobile number.'); }
				return;
			}
			var body = { user_type: user_type, mobile: mobile };
			fetch(sendOtpUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(body)
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (okSend(j)) {
					if (typeof toastr !== 'undefined') {
						toastr.success(j.msg || (isResend ? 'OTP sent again.' : 'OTP sent.'));
					}
					showOtpLoginStep2();
					startResendCooldown();
				} else {
					if (typeof toastr !== 'undefined') { toastr.error(j.msg || 'Could not send OTP'); }
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			});
		}

		sendBtn.addEventListener('click', function () {
			requestLoginOtp(false);
		});

		var resendBtn = document.getElementById('otp_resend_btn');
		if (resendBtn) {
			resendBtn.addEventListener('click', function () {
				if (resendBtn.disabled) {
					return;
				}
				requestLoginOtp(true);
			});
		}
		verifyBtn.addEventListener('click', function () {
			var mobile = (document.getElementById('otp_login_mobile').value || '').trim().replace(/\D/g, '');
			var user_type = (document.getElementById('otp_login_user_type').value || 'student').toLowerCase();
			var otp = (document.getElementById('otp_login_code').value || '').trim();
			if (otp.length < 4) {
				if (typeof toastr !== 'undefined') { toastr.error('Enter the OTP.'); }
				return;
			}
			if (mobile.length !== 10) {
				if (typeof toastr !== 'undefined') { toastr.error('Enter the same 10-digit mobile you used to request OTP.'); }
				return;
			}
			var body = { user_type: user_type, otp: otp, mobile: mobile };
			fetch(verifyOtpUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify(body)
			}).then(function (r) { return r.json(); }).then(function (j) {
				if (okVerify(j)) {
					var ws = j.web_session;
					if (ws && ws.url && (ws.status === 1 || ws.status === '1')) {
						if (typeof toastr !== 'undefined') { toastr.success(ws.msg || j.msg || 'Logged in'); }
						setTimeout(function () { window.location.href = ws.url; }, 600);
					} else {
						if (typeof toastr !== 'undefined') { toastr.success(j.msg || 'Verified.'); }
					}
				} else {
					if (typeof toastr !== 'undefined') { toastr.error(j.msg || 'Invalid OTP'); }
				}
			}).catch(function () {
				if (typeof toastr !== 'undefined') { toastr.error('Network error.'); }
			});
		});
	});
})();
</script>
