<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Optional Zoom host user id (avoids GET /users/email, so User read scopes are not required).
 * Find it: Zoom web → Admin → Users → open host → User ID.
 * Leave empty to use zoom_api_credentials.zoom_host_user_id, then zoom_host_email lookup.
 */
$config['zoom_host_user_id'] = '';

/**
 * Zoom Meeting SDK (Web) — separate from Server-to-Server OAuth used to create meetings.
 * Create a "Meeting SDK" app in Zoom Marketplace and paste SDK Key + Secret here,
 * or store them in zoom_api_credentials.android_api_key / android_api_secret.
 */
// Optional override (wins over DB when both are set). Use General app Development Client ID + Secret on localhost.
$config['meeting_sdk_key'] = '';
$config['meeting_sdk_secret'] = '';

/**
 * Zoom Webhook Secret for recording notifications.
 * Get this from Zoom App → Event Subscriptions → Webhook Token.
 * Leave empty to disable webhook signature verification.
 */
$config['webhook_secret'] = 'lo0ud2QiTCezuxZZ0mnSfQ';
