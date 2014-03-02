<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César Rodas                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace ServiceProvider;

use crodas\Path;
use crodas\File;

class Services extends Action
{
    protected $files = array();

    public function isExtensible()
    {
        if (empty($this->config['service-provider']['path'])) {
            return false;
        }
        $value   = $this->eval_variables($this->config['service-provider']['path'], $this->config);
        $dirs    = $this->validate_files($value, 'readable', 'path');
        $rebuild = false;
        foreach ($dirs as $dir) {
            if ($this->provider->addFile($dir)) {
                $rebuild = true;
            }
        }

        return $rebuild;
    }

    public function main(Array $default)
    {
        $annotations = $this->annotations;
        $services    = $annotations->get('Service');
        $parse       = array('name', 'definition', 'data');
        $names       = array();
        $switch      = array();
        $config      = $this->config;

        foreach($services as $object) {
            $this->provider->addFile($object['file']);
            foreach ($object as $annotation) {
                if ($annotation['method'] !== 'Service') {
                    continue;
                }
                $args = $annotation['args'];
                foreach ($parse as $pos => $_name) {
                    $$_name = !empty($args[$_name]) ? $args[$_name] : (!empty($args[$pos]) ? $args[$pos] : NULL);
                }

                if (empty($name)) {
                    throw new \RuntimeException("Missing service name in annotation");
                }

                if (empty($config[$name])) {
                    $config[$name] = array();
                }

                $params    = array_merge($default, $config[$name]);
                $has_value = !empty($config[$name]);

                foreach ((array)$definition as $property => $def) {
                    if (!array_key_exists($property, $params)) {
                        if (array_key_exists('default', $def)) {
                            $params[$property] = $def['default'];
                        } else if ($has_value) {
                            throw new \Exception("Missing configuration {$property} for service {$name}");
                        } else {
                            break;
                        }
                    }

                    $value =  $this->eval_variables($params[$property], $default);

                    if (!empty($def['type'])) {
                        switch ($def['type']) {
                        case 'integer':
                        case 'string':
                        case 'numeric':
                        case 'float':
                        case 'array':
                            $check = "is_{$def['type']}";
                            if (!$check($value)) {
                                throw new \Exception("{$property} should be {$def['type']}");
                            }
                            break;
                        case 'dir':
                        case 'file':
                        case 'path':
                            $type = $def['type'];
                            $type = $type == 'path' ? 'readable' : $type;
                            $params[$property] = $this->validate_file($value, $def['type'], $property);
                            break;
                        case 'array_dir':
                        case 'array_file':
                        case 'array_path':
                            $type = substr($def['type'], 6);
                            $type = $type == 'path' ? 'readable' : $type;
                            $params[$property] = $this->validate_files($value, $type, $property);
                            break;
                        case 'service':
                            if (!($value instanceof Compiler\ServiceCall)) {
                                throw new \Exception("{$property} should be any service");
                            }
                            break;
                        default:
                            if ($def['type'][0] == '&') {
                                $service = substr($def['type'], 1);
                                if (!($value instanceof Compiler\ServiceCall) || strcasecmp($value->name, $service) !== 0) {
                                    throw new \Exception("{$property} should be $service service");
                                }
                            }
                        } 
                    }
                }


                if (!is_array($definition)) {
                    if (!empty($definition)) {
                        throw new \RuntimeException("Invalid service configuration in annotation");
                    }
                    $definition = array();
                }

                $file   = Path::getRelative($object['file'], $this->provider->getTemp());
                $names  = array($name);
                $shared = !empty($args[2]['shared']);
                $switch[$name] = compact('names', 'params', 'data', 'object', 'file', 'definition', 'shared');
            }
        }

        foreach ($config as $name => $type) {
            if ($type instanceof Compiler\ServiceCall && !empty($switch[$type->name])) {
                $switch[$type->name]['names'][] = $name;
            }
        }

        return array($switch, $names);
    }
}
