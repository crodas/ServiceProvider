<?php
/**
 *  Example service
 */

/**
 *  @Service(activemongo, {
 *      host: {default: 'localhost'},
 *      user: {default: NULL},
 *      pass: {default: NULL},
 *      db: {required: true},
 *      class: {type: 'string'},
 *      opts: {default:{}, type: 'hash'}
 *  }, { shared: true })
 */
function activemongo_service($config)
{
    $conn = new \MongoClient($config['host'], $config['opts']);
    $db   = $conn->selectDB($config['db']);
    if ($config['user'] || $config['pass']) {
        $db->authenticate($config['user'], $config['pass']);
    }
    $mongo = new \ActiveMongo2\Connection($conn, $db);
    $mongo->registerNamespace($config['class']);
    return $mongo;
}

