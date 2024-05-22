<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

require_once __DIR__ . '/wpubaseplugin.php';

$wpuBasePlugin->uninstall();
