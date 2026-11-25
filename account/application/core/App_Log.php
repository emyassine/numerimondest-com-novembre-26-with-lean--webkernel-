<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_Log extends CI_Log
{
    public function write_log($level, $msg)
    {
        if (PHP_SAPI === 'cli-server' && strcasecmp($level, 'error') === 0) {
            $mount = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
            error_log(sprintf('[CodeIgniter %s] %s', $mount, $msg));
        }

        return parent::write_log($level, $msg);
    }
}
