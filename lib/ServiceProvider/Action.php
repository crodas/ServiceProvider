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

abstract class Action
{
    protected $config = array();
    protected $annotations;
    protected $provider;

    public function __construct(Provider $p, Array $config, $annotations)
    {
        $this->provider     = $p;
        $this->config       = $config;
        $this->annotations  = $annotations;
    }

    protected function validate_files($values, $type, $property)
    {
        $values = (Array)$values;
        $base   = dirname($this->provider->getInputFile());
        foreach ($values as $id => $path) {
            if ($path[0] != '/') {
                // relative path
                $path = $base . DIRECTORY_SEPARATOR . $path;
            }
            if (preg_match('/\*/', $path)) {
                $paths = 
                $values[$id] = array_filter(glob($path),  'is_' . $type);
            } else {
                $values[$id] = array($path);
            }
        }

        if (empty($values)) {
            return array();
        }

        $values = call_user_func_array('array_merge', $values);

        $paths = array();
        foreach ($values as $id => $realpath) {
            $paths[] = $this->validate_file($realpath, $type, $property . "." . $id);
        }
        return $paths;
    }

    protected function validate_file($value, $type, $property)
    {
        $realpath = $value;
        $pwd = dirname($this->provider->getInputFile());
        $realpath = str_replace("%{dir}", $pwd, $realpath);

        if ($realpath[0] != '/') {
            // RELAtive path
            $realpath = $pwd . DIRECTORY_SEPARATOR . $realpath;
        }
        $realpath = realpath($realpath);
        if (empty($realpath)) {
            throw new \RuntimeException("{$property}: Cannot find {$value} (relative to {$this->provider->getInputfile()})");
        }
        $check = "is_{$type}";
        if (!$check($realpath)) {
            throw new \RuntimeException("{$property}: {$realpath} is not a {$type}");
        }
        return $realpath;
    }

    protected function eval_variables($value, Array $default)
    {
        foreach ((array)$value as $id => $val) {
            if (!is_scalar($val)) {
                $value[$id] = $this->eval_variables($val, $default);
                continue;
            }
            preg_match_all("/%([a-z_][a-z_0-9]*)%/i", $val, $parts);
            if (!empty($parts[1])) {
                $rvalue = $val;
                foreach ($parts[1] as $v) {
                    if (array_key_exists($v, $default)) {
                        $rvalue = str_replace("%$v%", $default[$v], $rvalue);
                    } else {
                        throw new \RuntimeException("Cannot find variable %$v%");
                    }
                }
                if (is_scalar($value)) {
                    $value = $rvalue;
                } else {
                    $value[$id] = $rvalue;
                }
            }
        }
        return $value;
    }


}
