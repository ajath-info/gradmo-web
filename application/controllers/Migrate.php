<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Run database migrations from CLI or (optionally) HTTP with a shared secret.
 *
 * CLI:  php index.php migrate
 * Web:  /migrate?key=YOUR_SECRET   (only if migration_runner_key is set in config/migration.php)
 */
class Migrate extends CI_Controller {

    public function index()
    {
        $this->run();
    }

    public function run()
    {
        $this->config->load('migration', TRUE);

        if ($this->config->item('migration_enabled', 'migration') !== TRUE) {
            return $this->_respond('Migrations are disabled in application/config/migration.php.', 403);
        }

        if (!$this->_authorize_runner()) {
            return $this->_respond('Unauthorized. Set migration_runner_key and pass ?key=... or POST key.', 403);
        }

        $this->load->library('migration');
        $result = $this->migration->latest();

        if ($result === FALSE) {
            return $this->_respond($this->migration->error_string(), 500);
        }

        $msg = 'Migrations completed successfully.';
        if ($result !== TRUE && is_string($result)) {
            $msg .= ' Current version: ' . $result;
        } elseif ($result === TRUE) {
            $msg .= ' Database is already up to date.';
        }

        return $this->_respond($msg, 200);
    }

    private function _authorize_runner()
    {
        if (is_cli()) {
            return TRUE;
        }

        $key = (string) $this->config->item('migration_runner_key', 'migration');
        if ($key === '') {
            return FALSE;
        }

        $provided = $this->input->get('key');
        if ($provided === NULL || $provided === '') {
            $provided = $this->input->post('key');
        }

        return hash_equals($key, (string) $provided);
    }

    private function _respond($message, $http_code)
    {
        if (is_cli()) {
            echo $message . PHP_EOL;
            exit($http_code >= 400 ? 1 : 0);
        }

        $this->output->set_status_header($http_code);
        $this->output->set_content_type('application/json', 'utf-8');
        echo json_encode(array(
            'status' => ($http_code < 400) ? 'ok' : 'error',
            'message' => $message
        ));
    }
}
