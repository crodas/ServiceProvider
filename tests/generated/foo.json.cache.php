<?php

namespace WatchFiles\Generated\Label_74312ff6f137274cfe5a856397874fea49e7af9e;

function get_list() {
    return array(
        'files' => array(
            __DIR__ . "/../features/config/foo.json",
            __DIR__ . "/../features/plugins/barfoo/service.php",
            __DIR__ . "/../features/plugins/foobar/service.php",
        ),
        'dirs' => array(
            __DIR__ . "/../features/config",
            __DIR__ . "/../features/plugins/barfoo",
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
    0 => '/home/crodas/projects/newest/ServiceProvider/tests/features/config',
    1 => '/home/crodas/projects/newest/ServiceProvider/tests/features/plugins/barfoo',
    2 => '/home/crodas/projects/newest/ServiceProvider/tests/features/plugins/foobar',
  ),
  'files' => 
  array (
    0 => '/home/crodas/projects/newest/ServiceProvider/tests/features/config/foo.json',
    1 => '/home/crodas/projects/newest/ServiceProvider/tests/features/plugins/barfoo/service.php',
    2 => '/home/crodas/projects/newest/ServiceProvider/tests/features/plugins/foobar/service.php',
  ),
);
}

function has_changed()
{

    if (!is_dir(__DIR__ . "/../features/config") || filemtime(__DIR__ . "/../features/config") > 1383280532) {
        return __DIR__ . "/../features/config";
    }
    if (!is_dir(__DIR__ . "/../features/plugins/barfoo") || filemtime(__DIR__ . "/../features/plugins/barfoo") > 1383280705) {
        return __DIR__ . "/../features/plugins/barfoo";
    }
    if (!is_dir(__DIR__ . "/../features/plugins/foobar") || filemtime(__DIR__ . "/../features/plugins/foobar") > 1383280411) {
        return __DIR__ . "/../features/plugins/foobar";
    }
    if (!is_dir(__DIR__ . "/../features/plugins") || filemtime(__DIR__ . "/../features/plugins") > 1383280705) {
        return __DIR__ . "/../features/plugins";
    }

    if (!is_file(__DIR__ . "/../features/config/foo.json") || filemtime(__DIR__ . "/../features/config/foo.json") > 1382830141) {
        return __DIR__ . "/../features/config/foo.json";
    }
    if (!is_file(__DIR__ . "/../features/plugins/barfoo/service.php") || filemtime(__DIR__ . "/../features/plugins/barfoo/service.php") > 1383280705) {
        return __DIR__ . "/../features/plugins/barfoo/service.php";
    }
    if (!is_file(__DIR__ . "/../features/plugins/foobar/service.php") || filemtime(__DIR__ . "/../features/plugins/foobar/service.php") > 1383280411) {
        return __DIR__ . "/../features/plugins/foobar/service.php";
    }

    return false;
}
