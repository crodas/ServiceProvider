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

use Notoj\Dir as AnnotationDir;
use Notoj\File as AnnotationFile;
use Notoj\Annotations;
use Artifex;
use WatchFiles\Watch;
use crodas\Path;
use crodas\File;

class Provider
{
    protected $file;
    protected $tmp;
    protected $tmpCache;
    protected $files;
    protected $ns;
    protected $pattern;
    protected $func;
    protected $is_prod;
    protected $alias;

    protected static $NS = array();

    public function __construct($file, $pattern, $tmp, $alias = '')
    {
        if (!is_file($file)) {
            throw new \RuntimeException("File {$file} doesn't exists");
        }
        
        $this->pattern = $pattern;

        $this->file    = $file;
        $this->tmp     = $tmp;
        $this->alias   = $alias;
        $this->ns      = __NAMESPACE__ .'\\Generated\\Stage_' .sha1(realpath($file));
        $this->fnc     = $this->ns . '\\get_service';
        $this->is_prod = $this->ns . '\\is_production';

        if (isset(self::$NS[$this->ns])) {
            $this->fnc      = self::$NS[$this->ns] . '\\get_service';
            $this->is_prod  = self::$NS[$this->ns] . '\\is_production';
        }

        if (is_file($tmp)) {
            require_once $tmp;
            $prod = $this->is_prod;
            if (is_callable($this->is_prod) && $prod()) {
                /** we are in production mode :-) */
                return;
            }
        }
        
        $this->tmpCache = new Watch(substr($tmp, 0, -4)  . '.cache.php');
        $this->tmpCache->watchGlob($pattern);

        if (!$this->tmpCache->hasChanged() && is_callable($this->fnc)) {
            return;
        }
        $this->tmpCache->watch();

        $files = $this->tmpCache->getFiles();
        if (empty($files)) {
            throw new \RuntimeException("Cannot find {$pattern}");
        }

        $this->files  = $files;

        $this->generate();
    }

    protected function generate()
    {
        $parser = new Parser;
        $parser->parse($this->file)->process();
        $config = $parser->getConfig();
        $files  = $parser->getFiles(); 

        $annotations = new Annotations;
        foreach ($this->files as $input) {
            if (is_dir($input)) {
                $ann  = new AnnotationDir($input);
            } else if (is_file($input)) {
                $ann  = new AnnotationFile($input);
            } else {
                throw new \Exception("Cannot read {$input}");
            }
            $ann->getAnnotations($annotations);
        }

        $parse = array('name', 'definition', 'data');
        $names  = array();
        $switch = array();

        $services = $annotations->get('Service');
        $default  = array();
        foreach ($config as $key => $value) {
            if (is_scalar($value)) {
                $default[$key] = $value;
            }
        }

        foreach($services as $object) {
            $files[] = $object['file'];
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

                $params = array_merge($default, $config[$name]);
                if (empty($config[$name])) {
                    continue;
                }

                foreach ((array)$definition as $property => $def) {
                    if (!array_key_exists($property, $params)) {
                        if (array_key_exists('default', $def)) {
                            $params[$property] = $def['default'];
                        } else {
                            throw new \Exception("Missing configuration {$property} for service {$name}");
                        }
                    }

                    $value = $params[$property];

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

                $file   = Path::getRelative($object['file'], $this->tmp);
                $names  = array($name);
                $switch[$name] = compact('names', 'params', 'data', 'object', 'file', 'definition');
            }
        }

        foreach ($config as $name => $type) {
            if ($type instanceof Compiler\ServiceCall && !empty($switch[$type->name])) {
                $switch[$type->name]['names'][] = $name;
            }
        }

        $dirs = array();
        foreach ($files as $file) {
            $dirs[] = dirname($file);
        }

        $prod  = empty($default['devel']);
        $ns    = $this->ns;
        $self  = $this;
        $alias = $this->alias;
        $code  = Artifex::load(__DIR__ . '/Compiler/services.tpl.php')
            ->setContext(compact('switch', 'ns', 'self', 'alias', 'prod'))
            ->run();

        File::write($this->tmp, $code);

        if (is_callable($this->fnc)) {
            // PHP is a bitch, it won't let use re-define
            // a function, so we create the function inside another
            // namespace.
            foreach ($switch as &$service) {
                $service['file'] = Path::getRelative($service['object']['file'], __FILE__);
            }
            $ns   = __NAMESPACE__ . '\Generated\Stage_' . uniqid(true);
            $code = Artifex::load(__DIR__ . '/Compiler/services.tpl.php')
                ->setContext(compact('switch', 'ns', 'self', 'prod'))
                ->run();
            self::$NS[ $this->ns ] = $ns;
            $this->fnc     = $ns . '\get_service';
            $this->is_prod = $ns . '\is_production'; 
            eval(substr($code, 5));
        } else {
            require $this->tmp;
        }

        $this->tmpCache->watchFiles($files)
            ->watchDirs($dirs)
            ->watch();

    }

    public function getConfiguration($config)
    {
        $str = var_export($config, true);
        $str = preg_replace('@[ \t\n]+ServiceProvider\\\\Compiler\\\\ServiceCall[^>]+?\> +([^,]+?)[^\)]+\)\)@smU', ' get_service(\1, $context)', $str);
        $str = str_replace('%{dir}', "' . __DIR__ . '", $str);
        return $str;
    }

    public function get($name, $context = NULL)
    {
        $fnc = $this->fnc;
        return $fnc($name, $context);
    }
}
