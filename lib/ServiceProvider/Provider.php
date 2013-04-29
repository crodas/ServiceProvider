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
use Notoj\Annotations;
use Artifex;
use WatchFiles\Watch;

class Provider
{
    protected $file;
    protected $dir;
    protected $tmp;
    protected $tmpCache;
    protected $files;
    protected $ns;
    protected $func;

    public function __construct($file, $dir, $tmp)
    {
        if (!is_file($file)) {
            throw new \RuntimeException("File {$file} doesn't exists");
        }
        if (!is_dir($dir)) {
            $dirs = glob($dir);
            if (empty($dirs)) {
                throw new \RuntimeException("Dir {$dir} doesn't exists");
            }
        } else {
            $dirs = array($dir);
        }

        $this->file = $file;
        $this->dir  = $dirs;
        $this->tmp  = $tmp;
        $this->tmpCache = new Watch(substr($tmp, 0, -4)  . '.cache.php');
        $this->ns   = sha1(realpath($file));
        $this->fnc  = __NAMESPACE__ .'\\Generated\\Stage_' . $this->ns . '\\get_service';

        if (!$this->tmpCache->hasChanged()) {
            require_once $tmp;
            if (is_callable($this->fnc)) {
                return;
            }
        }

        if (!is_file($tmp)) {
            file_put_contents($tmp, '', LOCK_EX);
        }

        $this->generate();
    }

    protected function createCacheFiles($files)
    {
        foreach ($this->dir as $d) {
            $dir[] = $d;
        }
        foreach ($files as $file) {
            $dir[] = dirname($file);
        }

        $this->tmpCache
            ->watchFiles($files)
            ->watchDirs($dir)
            ->watch();
    }

    protected function generate()
    {
        $parser = new Parser;
        $parser->parse($this->file)->process();
        $config = $parser->getConfig();
        $files  = $parser->getFiles(); 

        $annotations = new Annotations;
        foreach ($this->dir as $dir) {
            $ann  = new AnnotationDir($dir);
            $ann->getAnnotations($annotations);
        }

        $parse = array('name', 'definition', 'data');
        $names  = array();
        $switch = array();

        $services = $annotations->get('Service');
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
                    continue;
                }

                $params = $config[$name];
                foreach ((array)$definition as $property => $def) {
                    if (!array_key_exists($property, $params)) {
                        if (array_key_exists('default', $def)) {
                            $params[$property] = $def['default'];
                        } else {
                            throw new \Exception("Missing configuration {$property} for service {$name}");
                        }
                    }
                }

                if (!is_array($definition)) {
                    throw new \RuntimeException("Invalid service configuration in annotation");
                }

                $file   = Watch::getRelativePath($object['file'], $this->tmp);
                $names  = array($name);
                $switch[$name] = compact('names', 'params', 'data', 'object', 'file', 'definition');
            }
        }

        foreach ($config as $name => $type) {
            if ($type instanceof Compiler\ServiceCall && !empty($switch[$type->name])) {
                $switch[$type->name]['names'][] = $name;
            }
        }


        $this->createCacheFiles($files);

        $ns   = $this->ns;
        $self = $this;
        $code = Artifex::load(__DIR__ . '/Compiler/services.tpl.php')
            ->setContext(compact('switch', 'ns', 'self'))
            ->run();
        Artifex::save($this->tmp, $code);
        if (is_callable($this->fnc)) {
            // PHP is a bitch, it won't let use re-define
            // a function, so we create the function inside another
            // namespace.
            foreach ($switch as &$service) {
                $service['file'] = Watch::getRelativePath($service['object']['file'], __FILE__);
            }
            $ns   = uniqid(true);
            $code = Artifex::load(__DIR__ . '/Compiler/services.tpl.php')
                ->setContext(compact('switch', 'ns', 'self'))
                ->run();
            $this->fnc = 'ServiceProvider\Generated\Stage_' . $ns . '\get_service';
            eval(substr($code, 5));
        } else {
            require $this->tmp;
        }
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
