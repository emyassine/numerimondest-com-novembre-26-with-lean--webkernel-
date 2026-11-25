<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** @return list<array<string,mixed>> */
function nm_offerings_all($includeDetailOnly = false)
{
    $CI = &get_instance();
    $CI->load->library('nm_offerings/nm_offerings_catalog');
    return $CI->nm_offerings_catalog->all((bool) $includeDetailOnly);
}

/** @return array<string,mixed>|null */
function nm_offerings_find($id)
{
    if (!is_string($id) || $id === '') return null;
    $CI = &get_instance();
    $CI->load->library('nm_offerings/nm_offerings_catalog');
    return $CI->nm_offerings_catalog->find($id);
}
