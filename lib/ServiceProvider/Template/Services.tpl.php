<?php

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
                        if (!is_callable({{@$handler['function']}})) {
                            require {{@$handler['file']}};
                        }
                        \{{$handler['function']}}($event);
                    @else
                        if (!class_exists({{@$handler['class']}}, false)) {
                            require {{@$handler['file']}};
                        }
                        $object = new \{{$handler['class']}};
                        $object->{{$handler['function']}}($event);
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

@if (!empty($alias))
namespace
{
    use {{$ns}} as f;

    class {{$alias}}
    {
        @foreach ($default as $key => $value)
            @if (is_scalar($value))
        static ${{$key}} = {{@$value}};
            @end
        @end

        public static function get($service, $context = null)
        {
            return f\get_service($service, $context);
        }

        @foreach ($switch as $service)
            @foreach ($service['names'] as $name)
                @if (preg_match("/^[a-z][a-z0-9_]*$/", $name))
        public static function {{$name}}($context = null)
        {
            return f\get_service({{@$name}}, $context);
        }
                @end
            @end
        @end
    }
}
@end
