<?php
namespace something;

/**
 *  @EventSubscriber(foo.bar, 10)
 */
function on_foo_bar_2($event) {
    $args = $event->getArguments();
    $args['this']->x = array(1);
}

/**
 *  @EventSubscriber(foo.bar, 5)
 */
function on_foo_bar($event) {
    $args = $event->getArguments();
    $args['this']->x[] = 2;
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
