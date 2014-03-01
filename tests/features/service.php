<?php
namespace something;

/**
 *  @EventSubscriber(foo.bar, 10)
 */
function on_foo_bar_2($event) {
    die('here');
}

/**
 *  @EventSubscriber(foo.bar, 5)
 */
function on_foo_bar($event) {
    die('here');
}

/**
 *  @Service(barfoo, {
 *      barfoo: {type: &foobar},
 *      xx: {type: numeric, default: 5.1}
 *  })
 */
function foobar($config, $context) 
{
    return $config;
}
