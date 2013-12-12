<?php

namespace {{$ns}}
{
    function get_service($service, $context = NULL)
    {
        static $services = array();

        switch ($service) {
        @foreach ($switch as $service)
            @include('service', compact('service'))
        @end
        @foreach ($default as $key => $value)
        case {{@$key}}:
            $return = {{@$value}};
            break;
        @end
        default:
            throw new \ServiceProvider\NotFoundException("cannot find service {$service}");
        }

        return $return;
    }
}

@if (!empty($alias))
namespace
{
    use {{$ns}} as f;

    class {{$alias}}
    {
        public static function get($service, $context = null)
        {
            return f\get_service($service, $context);
        }

        @foreach ($switch as $service)
            @foreach ($service['names'] as $name)
        public static function {{$name}}($context = null)
        {
            return f\get_service({{@$name}}, $context);
        }
            @end
        @end
    }
}
@end
