<?php

/**
 *  @PrepareService(foo)
 */
function prepare_fooar(Array $config)
{
    $config['zargs_1'] = mt_rand();
    return $config;
}

class FoobarMethod
{
    /**
     *  @PrepareService(foo)
     */
    public function prepare_foo3(Array $config)
    {
        $config['zargs_3'] = mt_rand();
        return $config;
    }

    /**
     *  @PrepareService(foo)
     */
    public static function prepare_foo2(Array $config)
    {
        $config['zargs_2'] = mt_rand();
        return $config;
    }
}

/**
 *  @Service(foo, {bar: {required: true}})
 */
function get_foo_service(Array $config, $context)
{
    $config['done'] = true;
    return $config;
}
