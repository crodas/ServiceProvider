<?php

namespace WatchFiles\Generated\Label_54e7ac7067958ee46689a9a03fecfe1806d767a9;

function get_list() {
    return array(
        'files' => array(
            __DIR__ . "/../features/plugins/foobar/service.php",
        ),
        'dirs' => array(
            __DIR__ . "/../features/plugins/foobar",
            __DIR__ . "/../features/plugins",
        ),
        'glob' => array(
                "/home/crodas/projects/newest/ServiceProvider/tests/features/plugins/*/service.php",
        )
    );
}

function get_watched_files() {
    return array (
  'globs' => 
  array (
    0 => '/home/crodas/projects/newest/ServiceProvider/tests/features/plugins/*/service.php',
  ),
  'dirs' => 
  array (
  ),
  'files' => 
  array (
  ),
);
}

function has_changed()
{

    if (!is_dir(__DIR__ . "/../features/plugins/foobar") || filemtime(__DIR__ . "/../features/plugins/foobar") > 1383280411) {
        return __DIR__ . "/../features/plugins/foobar";
    }
    if (!is_dir(__DIR__ . "/../features/plugins") || filemtime(__DIR__ . "/../features/plugins") > 1383280703) {
        return __DIR__ . "/../features/plugins";
    }

    if (!is_file(__DIR__ . "/../features/plugins/foobar/service.php") || filemtime(__DIR__ . "/../features/plugins/foobar/service.php") > 1383280411) {
        return __DIR__ . "/../features/plugins/foobar/service.php";
    }

    return false;
}
