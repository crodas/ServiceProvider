<?php
namespace ServiceProvider\Generated\Stage_4319bf57f4c41d7be27890785589dd716839b5aa 
{
    function get_service($service, $context = NULL)
    {
        static $services = array();

        switch ($service) {
        case 'barfoo':
            $config = array (
          'arg1' => 'demo',
          'devel' => true,
          'barfoo' => get_service('foobar', $context),
          'service' => 1,
          'foobar' => get_service('foobar', $context),
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
          'arg1' => 'demo',
          'devel' => true,
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
        case 'arg1':
            $return = 'xxx';
            break;

        case 'devel':
            $return = true;
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


