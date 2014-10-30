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
use crodas\FileUtil\Path;
use crodas\FileUtil\File;
use crodas\SimpleView\FixCode;

class Provider
{
    protected $file;
    protected $tmp;
    protected $tmpCache;
    protected $files;
    protected $ns;
    protected $pattern;
    protected $fnc;
    protected $dump;
    protected $is_prod;
    protected $alias;

    protected static $NS = array();

    public function getTemp()
    {
        return $this->tmp;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function addFile($file)
    {
        if (!in_array($file, $this->files)) {
            $this->files[] = $file;
            return true;
        }
        return false;
    }

    public function getInputFile()
    {
        return $this->file;
    }

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
        $this->dump    = $this->ns . '\\dump_configuration';
        $this->fnc     = $this->ns . '\\get_service';
        $this->is_prod = $this->ns . '\\is_production';

        if (isset(self::$NS[$this->ns])) {
            $this->fnc      = self::$NS[$this->ns] . '\\get_service';
            $this->dump     = self::$NS[$this->ns] . '\\dump_configuration';
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

        $default  = array();
        $dirs     = array();

        foreach ($config as $key => $value) {
            if (is_scalar($value)) {
                $default[$key] = $value;
            }
        }

        $services = new Services($this, $config, $annotations);
        $events   = new Events($this, $config, $annotations);

        if ($services->isExtensible()) {
            return $this->generate();
        }

        $events = $events->main($default);
        list($switch, $names) = $services->main($default);

        foreach (array_diff(array_keys($config), array_keys($switch)) as $key) {
            $default[$key] = $config[$key];
        }

        $prod  = empty($default['devel']);
        $ns    = $this->ns;
        $self  = $this;
        $alias = $this->alias;
        $code  = Template\Templates::get('services')
            ->render(compact('switch', 'ns', 'self', 'alias', 'prod', 'default', 'events'), true);

        File::write($this->tmp, FixCode::fix($code));

        if (is_callable($this->fnc)) {
            // PHP is a bitch, it won't let use re-define
            // a function, so we create the function inside another
            // namespace.
            foreach ($switch as &$service) {
                $service['file'] = Path::getRelative($service['object']['file'], __FILE__);
            }
            $ns   = __NAMESPACE__ . '\Generated\Stage_' . uniqid(true);
            $code = Template\Templates::get('services')
                ->render(compact('switch', 'ns', 'self', 'prod', 'default', 'events'), true);

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

    protected function getConfigArray($config, $raw = false)
    {
        if (!is_array($config)) {
            $pwd  = dirname($this->getInputFile());
            $config = str_replace("%{dir}", $pwd, $config);
            return var_export($config, true);
        }

        $array = "array(";
        foreach ($config as $key => $value) {
            $array .= var_export($key, true) . " => ";
            if ($value instanceof Compiler\ServiceCall) {
                if ($raw) {
                    $array .= var_export("%" . $value->name . "%", true) . ",\n";
                } else {
                    $array .= "get_service(" . var_export($value->name, true) . ", \$context),\n";
                }
            } else {
                $array .= $this->getConfigArray($value) . ",\n";
            }
        }

        return $array . ")";
    }

    public function getRawConfiguration($config)
    {
        return $this->getConfigArray($config, true);
    }


    public function getConfiguration($config)
    {
        return $this->getConfigArray($config);
    }

    public function dump()
    {
        $fnc = $this->dump;
        return $fnc();
    }

    public function get($name, $context = NULL)
    {
        $fnc = $this->fnc;
        return $fnc($name, $context);
    }
}
