#* // <?php
#* foreach ($names as $name)
case __@name__:
#* end
    #* if ($data['shared'])
    if (!empty($services[__@name__])) {
        return $services[__@name__];
    }
    #* end

    #* $config = $self->getConfiguration($config)
    $config = __config__;

    #*
    # $funct = $object['function']
    # if ($object->isFunction())
    if (!is_callable(__@funct__)) {
        require __DIR__ . __@file__;
    }
    $return = \__funct__($config, $context);
    #* 
    # else
    #   $class = $object['class']  
    if (!class_exists(__@class__, false)) {
        require __DIR__ . __@file__;
    }
    $object = new \__class__;
    $return = $object->__funct__($config, $context);
    #*  end

    #* if ($data['shared'])
    $services[__@name__] = $return;
    #* end
    break;


