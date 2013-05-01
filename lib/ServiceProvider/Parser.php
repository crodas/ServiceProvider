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

class Parser
{
    protected $stack  = array();
    protected $config = array();
    protected $files  = array();

    public function getFiles()
    {
        return $this->files;
    }

    protected function getValue($var)
    {
        $parts = explode(".", $var);
        $value = &$this->config;
        foreach ($parts as $part) {
            if (!is_array($value)) {
                throw new \RuntimeException(implode(".", $parts) . " is not a vector");
            }
            if (!array_key_exists($part, $value)) {
                throw new \RuntimeException("Cannot find $part inside " . print_r($value, true));
            }
            $value = &$value[$part];
        }

        if (count($parts) == 1) {
            return new Compiler\ServiceCall($parts[0]);
        }

        return $value;
    }

    protected function processVariables(Array &$config)
    {
        foreach ($config as $key => $value) {
            if (is_string($value) && $value[0] == substr($value, -1) && $value[0] == '%') {
                $config[$key] = $this->getValue(substr($value, 1, -1));
            } else if (is_array($value)) {
                $this->processVariables($config[$key]);
            }
        }
    }

    protected function merge(&$arr1, $arr2)
    {
        foreach ($arr2 as $key => $value) {
            if (empty($arr1[$key]) || !is_array($arr1[$key])) {
                $arr1[$key] = $value;
            } else {
                $this->merge($arr1[$key], $value);
            }
        }
    }

    public function define($name, $value)
    {
        if (!array_key_exists($name, $this->config)) {
            $this->config[$name] = $value;
        } else {
            $this->merge($this->config[$name], $value);
        }
        return $this;
    }

    public function parse($file)
    {
        if (count($this->stack) > 0 && !is_file($file)) {
            foreach ($this->stack as $dir) {
                if (is_file($dir . '/' . $file)) {
                    $file = $dir . '/' . $file;
                    break;
                }
            }
            if (!is_file($file)) {
                throw new \RuntimeException("cannot load file {$file}");
            }
        }

        $this->files[] = $file;
        $this->stack[] = dirname($file);
        $parser = new Parser\Yaml;
        $parser->parse($this, $file);

        return $this;
    }

    public function process()
    { 
        if (empty($this->config)) {
            throw new \RuntimeException("There is not nothing to process");
        }

        $this->processVariables($this->config);

        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
