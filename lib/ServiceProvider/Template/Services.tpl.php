<?php

@set($ns, 'Generate\x' . uniqid(True))

namespace {{$ns}}
{

class EventManager
{
    public function trigger($name, Array $args = array())
    {
        $event = new \ServiceProvider\Event($name, $args);

        switch (strtolower($name)) {
        @foreach ($events as $name => $handlers)
        case {{@$name}}:
            @foreach ($handlers as $i => $handler)
                @if ($handler->isFunction())
                    if (!is_callable({{@$handler->getObject()->getName()}})) {
                        require {{@$handler->getFile()}};
                    }
                    \{{$handler->getObject()->GetName()}}($event);
                @else
                    if (!class_exists({{@$handler->getObject()->getClass()->getName()}}, false)) {
                        require {{@$handler->getFile()}};
                    }
                    $object = new \{{$handler->getObject()->getClass()->GetName()}};
                    $object->{{$handler->getObject()->getName()}}($event);
                @end
                if ($event->isPropagationStopped()) {
                    $event->setCalls({{$i+1}});
                    return $event;
                }
            @end
            $event->setCalls({{$i+1}});
            break;

        @end
        }

        return $event;
    }
}

class Services
{

    function dump_configuration()
    {
        return array(
            @foreach ($default as $key => $value)
                @if (!($value instanceof ServiceProvider\Compiler\ServiceCall))
                {{@$key}} =>  {{$self->getRawConfiguration($value)}},
                @end
            @end
            @foreach ($switch as $service)
                @if (!$service['has_value']) 
                    @continue
                @end
                @set($rname, null)
                @foreach($service['names'] as $name)
                    @if (empty($rname)) 
                        {{@$name}} => {{ $self->getRawConfiguration($service['params']) }},
                        @set($rname, $name)
                    @else 
                        {{@$name}} => {{@'%' . $rname . '%'}},
                    @end
                @end
            @end
        );
    }

    function get_service($service, $context = NULL)
    {
        static $services = array();

        switch ($service) {
        case 'event_manager':
            if (!empty($services['event_manager'])) {
                return $services['event_manager'];
            }
            $return = new EventManager;
            $services['event_manager'] = $return;
            break;
        @foreach ($switch as $service)
            @include('service', compact('service'))
        @end
        @foreach ($default as $key => $value)
        @if (!$value instanceof ServiceProvider\Compiler\ServiceCall)
        case {{@$key}}:
            $return = {{@$value}}; 
            break;
        @end
        @end
        default:
            throw new \ServiceProvider\NotFoundException("cannot find service {$service}");
        }

        return $return;
    }
}

} # End

@if (!empty($alias))
namespace
{
if (!class_exists({{@$alias}}, false)) {


    class {{$alias}}
    {
        protected static $event;
        protected static $services;

        public static function __setClass($event, $services)
        {
            self::$event = $event;
            self::$services = $services;
        }

        @foreach ($default as $key => $value)
            @if (!preg_match("/^[a-z][a-z0-9_]*$/i", $key))
                @continue
            @end
            @if (is_scalar($value))
        static ${{$key}} = {{@$value}};
            @elif (is_array($value))
        static ${{$key}} = {{@ServiceProvider\Services::safeArray($value)}};
            @end
        @end

        public static function dumpConfig()
        {
            return self::$services->dump_configuration();
        }

        public static function get($service, $context = null)
        {
            return self::$services->get_service($service, $context);
        }

        public static function event_manager()
        {
            return self::$event;
        }

        public static function __callStatic($name, Array $args)
        {
            return self::$services->get_service($name, empty($args[0]) ? NULL : $args[0]);
        }
    }
}

{{$alias}}::__setClass(new {{$ns}}\EventManager, new {{$ns}}\Services);
}
@end

namespace
{
    return array(
        'event' => new {{$ns}}\EventManager,
        'services' => new {{$ns}}\Services,
    );
}
