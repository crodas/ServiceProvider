ServiceProvider
===============

Little configuration manager and dependency injection.

It is an attempt to build a simple library to define services, and how to consume them. A *service* is a configurable resource that is consumed by our software, think of it as a `mysql_connect` resource, where you can have different configuration for you local and production environment.

A service can be defined using annotations. 

```php
/**
 *  @Service(mysql, {
 *    host: {default:"localhost", type: string},
 *    user: {default:"root", type: string},
 *    pass: {default:"", type: string},
 *    port: {default: 3306, type: integer},
 *    db: {type: string},
 *  })  
 */
function get_mysql_service(Array $config)
{
    return new mysqli(
      $config['host'], 
      $config['user'], 
      $config['pass'], 
      $config['db'],
      $config['port']
    );
}
```

That's it, we just defined a service which in a reallity it is a function or method which returns something. In its annotation we defined its name and their configuration validation.

When you want to get access to the mysql service

```php
$service = new \ServiceProvider\Provider(
  'production.config.yml',  // the configuration file
  'where/services/are/defined/',  // where the files are defined.  It can use * comodin
  'production.generated.php' // to improve things we generate code, here is where to save it
);
$db = $service->get('mysql');
$db->query("SELECT * FROM users");
```

Events
------

As a bonus this library support events triggering/handling the right way. Basically at generation time it would look for `@EventSubscriber(<name>, [<preference>=0])` over a method or function. To trigger an event is very simple:


```php
$service = new \ServiceProvider\Provider(
  'production.config.yml',  // the configuration file
  'where/services/are/defined/',  // where the files are defined.  It can use * comodin
  'production.generated.php' // to improve things we generate code, here is where to save it
);

$events = $service->get('event_manager');
$result = $events->trigger('foo.bar', array('arg1' => 'foo'));
var_dump('this event had ' . $result->getCalls() . ' handlers');

```

And somewhere in the code

```php
/**
 *  @EventSubscriber(foo.bar)
 */
function some_handler($event)
{
    $args = $event->getArguments();
    $event->stopPropagation(); /* I'm the last one */
}
