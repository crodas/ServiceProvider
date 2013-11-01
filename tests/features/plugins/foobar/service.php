<?php

class Something
{
    /**
     *  @Service(foobar, {
     *      arg1: {default: 'demo'},
     *      foo: {default: 'foobar'},
     *      empty: {default: ''},
     *      yyy: {type: dir, default: '../'},
     *      xxx: {type: file, default: '../service.php'},
     *      x: {type: array_file, default: []},
     *      y: {type: array_dir, default: []},
     *  }, {shared:true})
     */
    function foobar($config, $context)
    {
        $context->assertTrue(true);
        $context->assertTrue(is_array($config));
        $context->assertTrue($config['foo'] == 'foobar');
        return new \Stdclass;
    }

}
