<?php

defined('BASEPATH') or exit('No direct script access allowed');
/** @var array<string, string> $route CI_Router::$routes. */
$route ??= [];
$prefix = 'admin/nm_offerings';
$slug = 'admin/nm-offerings';
// Human-readable URLs for the nm_offerings Perfex module.
$route[$slug] = $prefix;
$route["{$slug}/create"] = "{$prefix}/create";
$route["{$slug}/edit/(:any)"] = "{$prefix}/edit/\$1";
$route["{$slug}/save"] = "{$prefix}/save";
$route["{$slug}/publish"] = "{$prefix}/publish";
unset($prefix, $slug);
