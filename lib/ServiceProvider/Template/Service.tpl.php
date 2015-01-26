@foreach ($service['names'] as $name)
case {{@$name}}:
@end

    @if (!empty($service['shared']))
    if (!empty($services[{{@$name}}])) {
        return $services[{{@$name}}];
    }
    @end

    $config = {{ $self->getConfiguration($service['params']) }};

    // {{ get_class($service['object']) }}
    @if ($service['object']->isFunction())
    if (!is_callable({{@$service['object']->getName()}})) {
        require __DIR__ . {{@$service['file']}};
    }
    $return = \{{ $service['object']->getName() }}($config, $context, array($this, __FUNCTION__));
    @else
    if (!class_exists({{@$service['object']->getClass()->getName()}}, false)) {
        require __DIR__ . {{@$service['file']}};
    }
    $object = new \{{$service['object']->getClass()->getName()}};
    $return = $object->{{$service['object']->getName()}}($config, $context, array($this, __FUNCTION__));
    @end

    @if (!empty($service['shared']))
    $services[{{@$name}}] = $return;
    @end
    break;
