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
| If key values resolve to empty here, app falls back to general_settings:
| - razorpay_key_id, razorpay_secret_key, razorpay_webhook_secret
*/
$default_mode = (defined('ENVIRONMENT') && ENVIRONMENT === 'production') ? 'live' : 'test';
$mode_env = getenv('RAZORPAY_MODE');
$mode = strtolower(trim($mode_env !== false ? $mode_env : $default_mode));
if ($mode !== 'live' && $mode !== 'test') {
	$mode = $default_mode;
}

// Local test fallback (env vars still take priority).
$local_test_key_id = 'rzp_test_SfkbMrldtSUP2K';
$local_test_key_secret = '8OTEFKB5HW3anFsBE5ev5ln4';

$test_key_id = getenv('RAZORPAY_TEST_KEY_ID') ? getenv('RAZORPAY_TEST_KEY_ID') : $local_test_key_id;
$test_key_secret = getenv('RAZORPAY_TEST_KEY_SECRET') ? getenv('RAZORPAY_TEST_KEY_SECRET') : $local_test_key_secret;
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
