<?php
/**
 * Session Library wrapper
 *
 * Some CodeIgniter installations expect the session library entrypoint at:
 * system/libraries/Session.php
 * while the actual implementation lives in:
 * system/libraries/Session/Session.php
 */
defined('BASEPATH') OR exit('No direct script access allowed');

require_once dirname(__FILE__).'/Session/Session.php';

