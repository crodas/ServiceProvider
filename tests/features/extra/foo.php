<?php

/**
 *  @Service(foo, {bar: {required: true}})
 */
function get_foo_service(Array $config)
{
    $config['done'] = true;
    return $config;
}
