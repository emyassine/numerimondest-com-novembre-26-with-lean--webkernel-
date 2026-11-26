<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Numerimondes Offerings
Description: Perfex staff interface for the shared SQLite offerings catalogue.
Version: 1.0.0
Requires at least: 2.3.*
*/

define('NM_OFFERINGS_MODULE_NAME', 'nm_offerings');

hooks()->add_action('admin_init', 'nm_offerings_register_permissions');
hooks()->add_action('admin_init', 'nm_offerings_register_menu');
register_language_files(NM_OFFERINGS_MODULE_NAME, [NM_OFFERINGS_MODULE_NAME]);

function nm_offerings_register_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view' => _l('permission_view') . ' (' . _l('permission_global') . ')',
            'edit' => _l('permission_edit'),
            'publish' => 'Publish catalogue',
        ],
    ];
    register_staff_capabilities(NM_OFFERINGS_MODULE_NAME, $capabilities, _l('nm_offerings'));
}

function nm_offerings_register_menu()
{
    if (has_permission(NM_OFFERINGS_MODULE_NAME, '', 'view')) {
        get_instance()->app_menu->add_sidebar_menu_item(NM_OFFERINGS_MODULE_NAME, [
            'name' => _l('nm_offerings'),
            'href' => admin_url('nm-offerings'),
            'position' => 36,
            'icon' => 'fa fa-cubes',
        ]);
    }
}
