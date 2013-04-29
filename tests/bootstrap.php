<?php

require __DIR__ . "/../lib/ServiceProvider/autoload.php";
require __DIR__ . "/../vendor/autoload.php";

function resetServices()
{
    foreach(glob(__DIR__ . '/generated/*') as $file) {
        unlink($file);
    }
    $dir =__DIR__ . '/features/plugins/barfoo/';
    @unlink("{$dir}/service.php");
    @rmdir($dir);
}

function getService()
{
    return new ServiceProvider\Provider(
        __DIR__ . "/features/config/foo.yml", 
        __DIR__ . "/features/plugins/", 
        __DIR__ . "/generated/service.php"
    );
}


