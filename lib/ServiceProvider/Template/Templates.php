<?php
/**
 *  This file was generated with crodas/SimpleView (https://github.com/crodas/SimpleView)
 *  Do not edit this file.
 *
 */

namespace {

    class base_template_0dbaaa8e22c297efe0e3d4e8af754e27f1d53fd5
    {
        protected $parent;
        protected $child;
        protected $context;

        public function yield_parent($name, $args)
        {
            $method = "section_" . sha1($name);

            if (is_callable(array($this->parent, $method))) {
                $this->parent->$method(array_merge($this->context, $args));
                return true;
            }

            if ($this->parent) {
                return $this->parent->yield_parent($name, $args);
            }

            return false;
        }

        public function do_yield($name, Array $args = array())
        {
            if ($this->child) {
                // We have a children template, we are their base
                // so let's see if they have implemented by any change
                // this section
                if ($this->child->do_yield($name, $args)) {
                    // yes!
                    return true;
                }
            }

            // Do I have this section defined?
            $method = "section_" . sha1($name);
            if (is_callable(array($this, $method))) {
                // Yes!
                $this->$method(array_merge($this->context, $args));
                return true;
            }

            // No :-(
            return false;
        }

    }

    /** 
     *  Template class generated from Services.tpl.php
     */
    class class_c96c365346bcf33d93d2a2d073d94f6f8e159b26 extends base_template_0dbaaa8e22c297efe0e3d4e8af754e27f1d53fd5
    {

        public function render(Array $vars = array(), $return = false)
        {
            $this->context = $vars;

            extract($vars);
            if ($return) {
                ob_start();
            }
            echo "<?php\n\nnamespace " . ($ns) . "\n{\n    class EventManager\n    {\n        public function trigger(\$name, Array \$args = array())\n        {\n            \$event = new \\ServiceProvider\\Event(\$name, \$args);\n\n            switch (strtolower(\$name)) {\n";
            foreach($events as $name => $handlers) {
                echo "            case ";
                var_export($name);
                echo ":\n";
                foreach($handlers as $i => $handler) {
                    if ($handler->isFunction()) {
                        echo "                        if (!is_callable(";
                        var_export($handler['function']);
                        echo ")) {\n                            require ";
                        var_export($handler['file']);
                        echo ";\n                        }\n                        \\" . ($handler['function']) . "(\$event);\n";
                    }
                    else {
                        echo "                        if (!class_exists(";
                        var_export($handler['class']);
                        echo ", false)) {\n                            require ";
                        var_export($handler['file']);
                        echo ";\n                        }\n                        \$object = new \\" . ($handler['class']) . ";\n                        \$object->" . ($handler['function']) . "(\$event);\n";
                    }
                    echo "                    if (\$event->isPropagationStopped()) {\n                        \$event->setCalls(" . ($i+1) . ");\n                        return \$event;\n                    }\n";
                }
                echo "                \$event->setCalls(" . ($i+1) . ");\n                break;\n\n";
            }
            echo "            }\n\n            return \$event;\n        }\n    }\n\n    function get_service(\$service, \$context = NULL)\n    {\n        static \$services = array();\n\n        switch (\$service) {\n        case 'event_manager':\n            if (!empty(\$services['event_manager'])) {\n                return \$services['event_manager'];\n            }\n            \$return = new EventManager;\n            \$services['event_manager'] = \$return;\n            break;\n";
            foreach($switch as $service) {
                ServiceProvider\Template\Templates::exec('service', compact('service'), $this->context);
            }
            foreach($default as $key => $value) {
                if (!$value instanceof ServiceProvider\Compiler\ServiceCall) {
                    echo "        case ";
                    var_export($key);
                    echo ":\n            \$return = ";
                    var_export($value);
                    echo "; \n            break;\n";
                }
            }
            echo "        default:\n            throw new \\ServiceProvider\\NotFoundException(\"cannot find service {\$service}\");\n        }\n\n        return \$return;\n    }\n}\n\n";
            if (!empty($alias)) {
                echo "namespace\n{\n    use " . ($ns) . " as f;\n\n    class " . ($alias) . "\n    {\n";
                foreach($default as $key => $value) {
                    if (is_scalar($value)) {
                        echo "        static \$" . ($key) . " = ";
                        var_export($value);
                        echo ";\n";
                    }
                }
                echo "\n        public static function get(\$service, \$context = null)\n        {\n            return f\\get_service(\$service, \$context);\n        }\n\n        public static function event_manager()\n        {\n            return f\\get_service('event_manager');\n        }\n\n";
                foreach($switch as $service) {
                    foreach($service['names'] as $name) {
                        if (preg_match("/^[a-z][a-z0-9_]*$/", $name)) {
                            echo "        public static function " . ($name) . "(\$context = null)\n        {\n            return f\\get_service(";
                            var_export($name);
                            echo ", \$context);\n        }\n";
                        }
                    }
                }
                echo "    }\n}\n";
            }

            if ($return) {
                return ob_get_clean();
            }

        }
    }

    /** 
     *  Template class generated from Service.tpl.php
     */
    class class_0e7c6437a035d77bb64635a76bc7506fb01ef5c7 extends base_template_0dbaaa8e22c297efe0e3d4e8af754e27f1d53fd5
    {

        public function render(Array $vars = array(), $return = false)
        {
            $this->context = $vars;

            extract($vars);
            if ($return) {
                ob_start();
            }
            foreach($service['names'] as $name) {
                echo "case ";
                var_export($name);
                echo ":\n";
            }
            echo "\n";
            if (!empty($service['shared'])) {
                echo "    if (!empty(\$services[";
                var_export($name);
                echo "])) {\n        return \$services[";
                var_export($name);
                echo "];\n    }\n";
            }
            echo "\n    \$config = " . ($self->getConfiguration($service['params'])) . ";\n\n";
            if ($service['object']->isFunction()) {
                echo "    if (!is_callable(";
                var_export($service['object']['function']);
                echo ")) {\n        require __DIR__ . ";
                var_export($service['file']);
                echo ";\n    }\n    \$return = \\" . ($service['object']['function']) . "(\$config, \$context);\n";
            }
            else {
                echo "    if (!class_exists(";
                var_export($service['object']['class']);
                echo ", false)) {\n        require __DIR__ . ";
                var_export($service['file']);
                echo ";\n    }\n    \$object = new \\" . ($service['object']['class']) . ";\n    \$return = \$object->" . ($service['object']['function']) . "(\$config, \$context);\n";
            }
            echo "\n";
            if (!empty($service['shared'])) {
                echo "    \$services[";
                var_export($name);
                echo "] = \$return;\n";
            }
            echo "    break;\n";

            if ($return) {
                return ob_get_clean();
            }

        }
    }

}

namespace ServiceProvider\Template {

    class Templates
    {
        public static function getAll()
        {
            return array (
                0 => 'services',
                1 => 'service',
            );
        }

        public static function exec($name, Array $context = array(), Array $global = array())
        {
            $tpl = self::get($name);
            return $tpl->render(array_merge($global, $context));
        }

        public static function get($name, Array $context = array())
        {
            static $classes = array (
                'services.tpl.php' => 'class_c96c365346bcf33d93d2a2d073d94f6f8e159b26',
                'services' => 'class_c96c365346bcf33d93d2a2d073d94f6f8e159b26',
                'service.tpl.php' => 'class_0e7c6437a035d77bb64635a76bc7506fb01ef5c7',
                'service' => 'class_0e7c6437a035d77bb64635a76bc7506fb01ef5c7',
            );
            $name = strtolower($name);
            if (empty($classes[$name])) {
                throw new \RuntimeException("Cannot find template $name");
            }

            $class = "\\" . $classes[$name];
            return new $class;
        }
    }

}
