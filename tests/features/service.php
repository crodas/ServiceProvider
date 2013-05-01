<?php
namespace something;

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
