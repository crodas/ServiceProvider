<?php

class Something
{
    /**
     *  @Service(foobar, {
     *      foo: {default: 'foobar'} 
     *  }, {foo:bar})
     */
    function foobar($config, $context)
    {
        $context->assertTrue(true);
        $context->assertTrue(is_array($config));
        $context->assertTrue($config['foo'] == 'foobar');
        return new \Stdclass;
    }

}
