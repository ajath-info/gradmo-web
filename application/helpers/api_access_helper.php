<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared access-token helpers (same rules as api/user/Home.php).
 */

if (!function_exists('api_get_access_token_from_request')) {
    function api_get_access_token_from_request($CI)
    {
        $auth_header = $CI->input->get_request_header('Authorization', true);
        if (!empty($auth_header) && preg_match('/Bearer\s*:?\s*(.+)/i', $auth_header, $matches)) {
            return trim($matches[1]);
        }

        if (!empty($_REQUEST['access_token'])) {
            return trim(preg_replace('/^Bearer\s*:?\s*/i', '', $_REQUEST['access_token']));
        }

        if (!empty($_REQUEST['token'])) {
            return trim(preg_replace('/^Bearer\s*:?\s*/i', '', $_REQUEST['token']));
        }

        return '';
    }
}

if (!function_exists('api_parse_access_token')) {
    function api_parse_access_token($CI, $token)
    {
        $token = trim((string) $token);
        if (preg_match('/^Bearer\s*:?\s*(.+)$/i', $token, $matches)) {
            $token = trim($matches[1]);
        }

        if (empty($token) || strpos($token, '.') === false) {
            return false;
        }

        list($payload_b64, $signature) = explode('.', $token, 2);
        $secret = $CI->config->item('encryption_key');
        if (empty($secret)) {
            $secret = 'education_api_secret_key';
        }

        $expected_signature = hash_hmac('sha256', $payload_b64, $secret);
        if (!hash_equals($expected_signature, $signature)) {
            return false;
        }

        $payload_json = base64_decode(strtr($payload_b64, '-_', '+/'));
        $payload = json_decode($payload_json, true);

        if (!is_array($payload) || empty($payload['uid']) || empty($payload['ut']) || empty($payload['exp'])) {
            return false;
        }

        if ((int) $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }
}

if (!function_exists('api_require_valid_access_token')) {
    /**
     * @return array|false  Payload on success, false if unauthorized (response already sent).
     */
    function api_require_valid_access_token($CI)
    {
        $token = api_get_access_token_from_request($CI);
        $payload = api_parse_access_token($CI, $token);

        if ($payload === false) {
            $CI->output->set_content_type('application/json', 'utf-8');
            echo json_encode(array(
                'status' => 'false',
                'msg' => 'Unauthorized: invalid or expired access token'
            ));
            return false;
        }

        return $payload;
    }
}
