<?php
/**
 *  Autoloader function generated by crodas/Autoloader
 *
 *  https://github.com/crodas/Autoloader
 *
 *  This is a generated file, do not modify it.
 */

spl_autoload_register(function ($class) {
    /*
        This array has a map of (class => file)
    */

    // classes {{{
    static $classes = array (
  'serviceprovider\\notfoundexception' => '/NotFoundException.php',
  'serviceprovider\\parser\\yaml' => '/Parser/YAML.php',
  'serviceprovider\\parser\\yml' => '/Parser/YML.php',
  'serviceprovider\\parser\\ini' => '/Parser/INI.php',
  'serviceprovider\\compiler\\servicecall' => '/Compiler/ServiceCall.php',
  'serviceprovider\\parser' => '/Parser.php',
  'serviceprovider\\provider' => '/Provider.php',
);
    // }}}

    // deps {{{
    static $deps    = array (
  'serviceprovider\\parser\\yml' => 
  array (
    0 => 'serviceprovider\\parser\\yaml',
  ),
);
    // }}}

    $class = strtolower($class);
    if (isset($classes[$class])) {
        if (!empty($deps[$class])) {
            foreach ($deps[$class] as $zclass) {
                if (!class_exists($zclass, false)) {
                    require __DIR__  . $classes[$zclass];
                }
            }
        }

        if (!class_exists($class, false)) {

            require __DIR__  . $classes[$class];

        }
        return true;
    }

    return false;
});


