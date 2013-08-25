<?php
namespace ServiceProvider\Generated\Stage___ns__ 
{
    function get_service($service, $context = NULL)
    {
        static $services = array();

        switch ($service) {
        #* foreach ($switch as $args)
        #   $names  = $args['names']
        #   $config = $args['params']
        #   $data   = $args['data']
        #   $object = $args['object']
        #   $file   = $args['file']
        #   $definition  = $args['definition']
        #   include("service.tpl.php")
        # end
        default:
            throw new \ServiceProvider\NotFoundException("cannot find service {$service}");
        }

        return $return;
    }
}

#* if ($alias)
namespace
{
    use ServiceProvider\Generated\Stage___ns__ as f;

    class __alias__
    {
        public static function get($service, $context = null)
        {
            return f\get_service($service, $context);
        }
    }
}
#* end

