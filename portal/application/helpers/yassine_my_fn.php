<?php
hooks()->add_filter('admin_url', 'replace_admin_with_partners_url');

function replace_admin_with_partners_url($url) {
    return str_replace('/admin/', '/partners/', $url);
}

