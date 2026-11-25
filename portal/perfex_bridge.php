<?php

/**
 * Perfex CRM embedded bootstrap.
 *
 * Loads CodeIgniter + Perfex services/helpers WITHOUT:
 * - executing a controller
 * - dispatching a route
 * - rendering a Perfex page
 * - making an HTTP request
 *
 * Usage from /account:
 *
 * require_once __DIR__ . '/connect/perfex_bridge.php';
 *
 * $staffId = get_staff_user_id();
 */

if (defined('PERFEX_BRIDGE_BOOTSTRAPPED')) {
    return;
}

define('PERFEX_BRIDGE_BOOTSTRAPPED', true);

/*
|--------------------------------------------------------------------------
| Paths
|--------------------------------------------------------------------------
*/

$perfex_root = __DIR__ . DIRECTORY_SEPARATOR;

if (!is_dir($perfex_root . 'system')) {
    throw new RuntimeException(
        'Perfex system directory not found: ' . $perfex_root . 'system'
    );
}

if (!is_dir($perfex_root . 'application')) {
    throw new RuntimeException(
        'Perfex application directory not found: ' . $perfex_root . 'application'
    );
}

/*
|--------------------------------------------------------------------------
| Prevent collision with another CodeIgniter instance
|--------------------------------------------------------------------------
*/

if (defined('BASEPATH')) {
    throw new RuntimeException(
        'BASEPATH is already defined. Another CodeIgniter instance is already loaded.'
    );
}

/*
|--------------------------------------------------------------------------
| CodeIgniter environment
|--------------------------------------------------------------------------
*/

if (!defined('ENVIRONMENT')) {
    define(
        'ENVIRONMENT',
        isset($_SERVER['CI_ENV'])
            ? $_SERVER['CI_ENV']
            : 'production'
    );
}

/*
|--------------------------------------------------------------------------
| CodeIgniter constants normally defined by index.php
|--------------------------------------------------------------------------
*/

define('SELF', basename(__FILE__));

define(
    'BASEPATH',
    $perfex_root . 'system' . DIRECTORY_SEPARATOR
);

define(
    'FCPATH',
    $perfex_root
);

define(
    'SYSDIR',
    'system'
);

define(
    'APPPATH',
    $perfex_root . 'application' . DIRECTORY_SEPARATOR
);

define(
    'VIEWPATH',
    APPPATH . 'views' . DIRECTORY_SEPARATOR
);

/*
|--------------------------------------------------------------------------
| CodeIgniter version
|--------------------------------------------------------------------------
*/

if (!defined('CI_VERSION')) {
    define('CI_VERSION', '3.1.11');
}

/*
|--------------------------------------------------------------------------
| Application constants
|--------------------------------------------------------------------------
*/

if (
    file_exists(
        APPPATH .
        'config/' .
        ENVIRONMENT .
        '/constants.php'
    )
) {
    require_once
        APPPATH .
        'config/' .
        ENVIRONMENT .
        '/constants.php';
}

if (file_exists(APPPATH . 'config/constants.php')) {
    require_once APPPATH . 'config/constants.php';
}

/*
|--------------------------------------------------------------------------
| CodeIgniter global functions
|--------------------------------------------------------------------------
*/

require_once BASEPATH . 'core/Common.php';

/*
|--------------------------------------------------------------------------
| Composer
|--------------------------------------------------------------------------
|
| config_item() loads application/config/config.php.
|
| In Perfex, config.php loads app-config.php, therefore:
|
| APP_DB_*
| APP_ENC_KEY
| SESS_DRIVER
| SESS_SAVE_PATH
| APP_BASE_URL
| etc.
|
| become available here.
|
*/

$composer_autoload = config_item('composer_autoload');

if ($composer_autoload) {
    if ($composer_autoload === true) {
        $composer_file =
            APPPATH .
            'vendor' .
            DIRECTORY_SEPARATOR .
            'autoload.php';

        if (file_exists($composer_file)) {
            require_once $composer_file;
        }
    } elseif (is_string($composer_autoload)) {
        if (file_exists($composer_autoload)) {
            require_once $composer_autoload;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Benchmark
|--------------------------------------------------------------------------
*/

$BM =& load_class('Benchmark', 'core');

$BM->mark('total_execution_time_start');
$BM->mark('loading_time:_base_classes_start');

/*
|--------------------------------------------------------------------------
| Hooks
|--------------------------------------------------------------------------
*/

$EXT =& load_class('Hooks', 'core');

/*
|--------------------------------------------------------------------------
| pre_system hook
|--------------------------------------------------------------------------
*/

$EXT->call_hook('pre_system');

/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
*/

$CFG =& load_class('Config', 'core');

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
|
| We are embedding Perfex inside another application request.
|
| The parent application may be receiving POST requests which obviously do
| not contain Perfex's CSRF token.
|
| We therefore disable Perfex CSRF validation for THIS embedded runtime only.
|
| This does NOT modify Perfex's config.php.
|
*/

$CFG->set_item('csrf_protection', true);

$config =& get_config();

$config['csrf_protection'] = true;

/*
|--------------------------------------------------------------------------
| Charset
|--------------------------------------------------------------------------
*/

$charset = strtoupper(
    config_item('charset') ?: 'UTF-8'
);

ini_set(
    'default_charset',
    $charset
);

/*
|--------------------------------------------------------------------------
| mbstring
|--------------------------------------------------------------------------
*/

if (!defined('MB_ENABLED')) {
    if (extension_loaded('mbstring')) {
        define('MB_ENABLED', true);

        @ini_set(
            'mbstring.internal_encoding',
            $charset
        );

        mb_substitute_character('none');
    } else {
        define('MB_ENABLED', false);
    }
}

/*
|--------------------------------------------------------------------------
| iconv
|--------------------------------------------------------------------------
*/

if (!defined('ICONV_ENABLED')) {
    if (extension_loaded('iconv')) {
        define('ICONV_ENABLED', true);

        @ini_set(
            'iconv.internal_encoding',
            $charset
        );
    } else {
        define('ICONV_ENABLED', false);
    }
}

/*
|--------------------------------------------------------------------------
| Compatibility files
|--------------------------------------------------------------------------
*/

require_once BASEPATH . 'core/compat/mbstring.php';
require_once BASEPATH . 'core/compat/hash.php';
require_once BASEPATH . 'core/compat/password.php';
require_once BASEPATH . 'core/compat/standard.php';

/*
|--------------------------------------------------------------------------
| UTF-8
|--------------------------------------------------------------------------
*/

$UNI =& load_class('Utf8', 'core');

/*
|--------------------------------------------------------------------------
| URI
|--------------------------------------------------------------------------
|
| We instantiate URI because some CodeIgniter/Perfex libraries expect
| $CI->uri to exist.
|
| It DOES NOT execute a controller.
|
*/

$URI =& load_class('URI', 'core');

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
|
| Router is initialized only so libraries expecting $CI->router keep working.
|
| We DO NOT execute the selected controller.
|
*/

$RTR =& load_class('Router', 'core');

/*
|--------------------------------------------------------------------------
| Output
|--------------------------------------------------------------------------
|
| Some libraries/helpers expect $CI->output.
|
| Nothing will be displayed by this bootstrap.
|
*/

$OUT =& load_class('Output', 'core');

/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

$SEC =& load_class('Security', 'core');

/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/

$IN =& load_class('Input', 'core');

/*
|--------------------------------------------------------------------------
| Language
|--------------------------------------------------------------------------
*/

$LANG =& load_class('Lang', 'core');

/*
|--------------------------------------------------------------------------
| Base Controller
|--------------------------------------------------------------------------
*/

require_once BASEPATH . 'core/Controller.php';

/*
|--------------------------------------------------------------------------
| get_instance()
|--------------------------------------------------------------------------
*/

if (!function_exists('get_instance')) {
    function &get_instance()
    {
        return CI_Controller::get_instance();
    }
}

/*
|--------------------------------------------------------------------------
| Perfex's App_Controller
|--------------------------------------------------------------------------
|
| Perfex uses:
|
| $config['subclass_prefix'] = 'App_';
|
| therefore this normally loads:
|
| application/core/App_Controller.php
|
*/

$subclass_prefix =
    $CFG->config['subclass_prefix']
    ?? 'App_';

$app_controller_file =
    APPPATH .
    'core/' .
    $subclass_prefix .
    'Controller.php';

if (file_exists($app_controller_file)) {
    require_once $app_controller_file;
}

/*
|--------------------------------------------------------------------------
| Finish framework base initialization
|--------------------------------------------------------------------------
*/

$BM->mark('loading_time:_base_classes_end');

/*
|--------------------------------------------------------------------------
| CREATE THE CODEIGNITER SUPER OBJECT
|--------------------------------------------------------------------------
|
| THIS is the important bit.
|
| We instantiate CI_Controller directly.
|
| Its constructor:
|
| - creates the global CI instance
| - exposes Config
| - exposes Input
| - exposes Security
| - exposes Lang
| - exposes Router
| - exposes Output
| - initializes Loader
| - runs application/config/autoload.php
|
| BUT:
|
| no application controller is executed.
|
*/

$CI = new CI_Controller();

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
|
| Ensure DB exists even if Perfex changes its autoload configuration.
|
*/

if (!isset($CI->db)) {
    $CI->load->database();
}

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
|
| This will read the existing Perfex session cookie:
|
| sp_session
|
| Your Perfex config uses cookie_path "/",
| therefore the cookie is also sent to /account/.
|
*/

if (!isset($CI->session)) {
    $CI->load->library('session');
}

/*
|--------------------------------------------------------------------------
| Load ALL Perfex application helpers
|--------------------------------------------------------------------------
|
| This means you don't need:
|
| $CI->load->helper(...)
|
| every time.
|
*/

$helper_files =
    glob(
        APPPATH .
        'helpers' .
        DIRECTORY_SEPARATOR .
        '*_helper.php'
    );

if ($helper_files !== false) {
    sort($helper_files);

    foreach ($helper_files as $helper_file) {
        require_once $helper_file;
    }
}

/*
|--------------------------------------------------------------------------
| Convenience accessor
|--------------------------------------------------------------------------
|
| perfex()->db
| perfex()->session
| perfex()->load
| perfex()->config
|
*/

if (!function_exists('perfex')) {
    function &perfex()
    {
        $CI =& get_instance();

        return $CI;
    }
}

/*
|--------------------------------------------------------------------------
| Sanity checks
|--------------------------------------------------------------------------
*/

if (!function_exists('get_staff_user_id')) {
    throw new RuntimeException(
        'Perfex bootstrap succeeded but get_staff_user_id() was not loaded.'
    );
}

if (!function_exists('get_option')) {
    throw new RuntimeException(
        'Perfex bootstrap succeeded but get_option() was not loaded.'
    );
}
