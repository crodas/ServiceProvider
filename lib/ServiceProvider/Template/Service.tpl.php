@foreach ($service['names'] as $name)
case {{@$name}}:
@end

    @if (!empty($service['shared']))
    if (!empty($services[{{@$name}}])) {
        return $services[{{@$name}}];
    }
    @end

    $config = {{ $self->getConfiguration($service['params']) }};

    @if ($service['object']->isFunction())
    if (!is_callable({{@$service['object']['function']}})) {
        require __DIR__ . {{@$service['file']}};
    }
    $return = \{{ $service['object']['function'] }}($config, $context, __FUNCTION__);
    @else
    if (!class_exists({{@$service['object']['class']}}, false)) {
        require __DIR__ . {{@$service['file']}};
    }
    $object = new \{{$service['object']['class']}};
    $return = $object->{{$service['object']['function']}}($config, $context, __FUNCTION__);
    @end

    @if (!empty($service['shared']))
    $services[{{@$name}}] = $return;
    @end
    break;
