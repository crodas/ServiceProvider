<?php
namespace ServiceProvider\Generated\Stage_d45121175025fff4d3fa023c3ef63d0118e6340a 
{
    function get_service($service, $context = NULL)
    {
        static $services = array();

        switch ($service) {
        case 'barfoo':
            $config = array (
          'devel' => '1',
          'barfoo' => get_service('foobar', $context),
          'service' => '1',
          'foobar' => get_service('foobar', $context),
          'arg1' => 'demo',
          'xx' => 5.1,
        );
        
            if (!is_callable('something\\foobar')) {
                require __DIR__ . '/../features/plugins/barfoo/service.php';
            }
            $return = \something\foobar($config, $context);
            break;
        case 'foobar':
            if (!empty($services['foobar'])) {
                return $services['foobar'];
            }
            $config = array (
          'devel' => '1',
          'arg1' => 'demo',
          'foo' => 'foobar',
          'empty' => '',
          'yyy' => '/home/crodas/projects/newest/ServiceProvider/tests/features',
          'xxx' => '/home/crodas/projects/newest/ServiceProvider/tests/features/service.php',
        );
        
            if (!class_exists('Something', false)) {
                require __DIR__ . '/../features/plugins/foobar/service.php';
            }
            $object = new \Something;
            $return = $object->foobar($config, $context);
            $services['foobar'] = $return;
            break;
        case 'devel':
            $return = '1';
            break;

        default:
            throw new \ServiceProvider\NotFoundException("cannot find service {$service}");
        }

        return $return;
    }

    function is_production()
    {
        return false;
    }
}


