<?php

namespace ServiceProvider\Parser;

use Symfony\Component\Yaml\Yaml as sfYaml;

class Yaml
{
    public function parse($parser, $file)
    {
        $data = sfYaml::parse($file);
        if (!empty($data['include'])) {
            foreach ($data['include'] as $f) {
                $parser->parse($f);
            }
            unset($data['include']);
        }
        foreach ($data as $key => $value) {
            $parser->define($key, $value);
        }
    }
}
