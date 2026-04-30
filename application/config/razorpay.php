<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Razorpay backend (Orders API, verify, webhook).
|
| Razorpay has no separate sandbox URL; "test mode" means using test keys only.
| We default to test mode outside production unless RAZORPAY_MODE is set.
|
| Supported env vars:
| - RAZORPAY_MODE: test | live
| - RAZORPAY_TEST_KEY_ID / RAZORPAY_TEST_KEY_SECRET
| - RAZORPAY_LIVE_KEY_ID / RAZORPAY_LIVE_KEY_SECRET
| - RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET (legacy fallback)
| - RAZORPAY_WEBHOOK_SECRET
|
| If key values resolve to empty here, Razorpay controller uses payment_gateway_api_credentials
| (same as api/main/get_defaults_requirements), then general_settings.
*/
$default_mode = (defined('ENVIRONMENT') && ENVIRONMENT === 'production') ? 'live' : 'test';
$mode_env = getenv('RAZORPAY_MODE');
$mode = strtolower(trim($mode_env !== false ? $mode_env : $default_mode));
if ($mode !== 'live' && $mode !== 'test') {
	$mode = $default_mode;
}

$test_key_id = getenv('RAZORPAY_TEST_KEY_ID') ? getenv('RAZORPAY_TEST_KEY_ID') : '';
$test_key_secret = getenv('RAZORPAY_TEST_KEY_SECRET') ? getenv('RAZORPAY_TEST_KEY_SECRET') : '';
$live_key_id = getenv('RAZORPAY_LIVE_KEY_ID') ? getenv('RAZORPAY_LIVE_KEY_ID') : '';
$live_key_secret = getenv('RAZORPAY_LIVE_KEY_SECRET') ? getenv('RAZORPAY_LIVE_KEY_SECRET') : '';
$legacy_key_id = getenv('RAZORPAY_KEY_ID') ? getenv('RAZORPAY_KEY_ID') : '';
$legacy_key_secret = getenv('RAZORPAY_KEY_SECRET') ? getenv('RAZORPAY_KEY_SECRET') : '';

if ($mode === 'test') {
	$config['razorpay_key_id'] = $test_key_id !== '' ? $test_key_id : $legacy_key_id;
	$config['razorpay_key_secret'] = $test_key_secret !== '' ? $test_key_secret : $legacy_key_secret;
} else {
	$config['razorpay_key_id'] = $live_key_id !== '' ? $live_key_id : $legacy_key_id;
	$config['razorpay_key_secret'] = $live_key_secret !== '' ? $live_key_secret : $legacy_key_secret;
}

$config['razorpay_mode'] = $mode;
$config['razorpay_webhook_secret'] = getenv('RAZORPAY_WEBHOOK_SECRET') ? getenv('RAZORPAY_WEBHOOK_SECRET') : '';
