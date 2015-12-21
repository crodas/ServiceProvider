<?php

class SimpleTest extends \phpunit_framework_testcase
{
    public static function provider()
    {
        return array(
            array('foo.yml'),
            array('foo.ini'),
            array('foo.json'),
        );
    }

    /**
     *  @dataProvider provider
     */
    public function testCompile($zfile)
    {
        $services = getService($zfile);
        $this->assertTrue($services instanceof ServiceProvider\Provider);
        if ($zfile == 'foo.yml') {
            $foo = $services->get('foo');
            $this->assertTrue($foo['done']);
            $this->assertTrue(!empty($foo['zargs_1']));
            $this->assertTrue(!empty($foo['zargs_2']));
            $this->assertTrue(!empty($foo['zargs_3']));
            $this->assertFalse(defined('DEVELOPMENT_MODE'));
        } else {
            $this->assertTrue(DEVELOPMENT_MODE);
        }

        $this->assertTrue(is_array($services->dump()));
    }

    /**
     *  @depends testCompile
     */
    public function testRecompilation()
    {
        $file = __DIR__ . '/generated/foo.yml.php';
        $time = filemtime($file);


        // clear stat cache
        clearstatcache();
        sleep(1);
        $foo = getService();

        $this->assertEquals($time, filemtime($file));


        // "create" new plugin service
        sleep(1); // sleep a little bit
        $dir = __DIR__ . '/features/plugins/barfoo/';
        mkdir($dir);
        copy(__DIR__ . '/features/service.php', "$dir/service.php");

        // clear stat cache
        clearstatcache();
        $foo = getService();

        $this->assertNotEquals($time, filemtime($file));
    }

    /**
     *  @dataProvider provider
     *  @expectedException ServiceProvider\NotFoundException
     */
    public function testNoService($zfile)
    {
        getService($zfile)->get('foobar_ssss');
    }

    public function testNotSharedServices()
    {
        $this->assertEquals(
            getService()->get('barfoo', $this),
            getService()->get('barfoo', $this)
        );
        $this->assertEquals(
            getService()->get('barfoo', $this),
            getService()->get('barfoo', $this)
        );
    }

    public function testSharedServices()
    {
        $this->assertEquals(
            getService()->get('foobar', $this),
            getService()->get('foobar', $this)
        );
        $this->assertEquals(
            Service::foobar($this),
            Service::foobar($this)
        );
    }

    public function testServices()
    {
        $service1 = getService()->get('foobar', $this);
        $this->assertTrue($service1 instanceof \Stdclass);

        $service  = getService()->get('barfoo', $this);
        $service2 = getService()->get('xxx', $this);
        $this->assertEquals($service, $service2);
        $this->assertTrue(is_array($service));
        $this->assertTrue(is_callable($service['callback']));
        $this->assertEquals($service['barfoo'], $service1);

        $events = getService()->get('event_manager');
        $e = $events->trigger('foo.bar', array('this' => $this));
        $this->assertEquals($this->x, array(1,2));
        $this->assertEquals(2, $e->getCalls());
    }
}
