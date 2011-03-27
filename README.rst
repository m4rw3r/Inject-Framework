================
Inject Framework
================

Lightweight stack-based PHP framework based on ideas from Ruby's Rack and Ruby on Rails 3.

Features
========

* Lightweight but extendable, only include the middleware and endpoints you want to use.
* Fast, no need to execute stuff you don't need.

Requirements
============

* PHP >= 5.3
* Reflection Extension
* PCRE Extension
* Tokenizer Extension

Basic principles
================

The basic principle of the framework is a chain of layers — so called middleware — which
perform specific actions and then passes the request on to the next layer, ultimately
reaching the controller's action method.

In more general terms, the execution is done using the MiddlewareStack which contains
a series of middlewares which will process the request before it is handed to the
endpoint callback (which is a plain PHP callback).

What is middleware?
-------------------

If you have used Ruby on Rails or Ruby's Rack webserver interface you probably already
know what it is as it is almost a port.

A middleware is an object implementing ``Inject\Core\Middleware\MiddlewareInterface``
which specifies a basic interface for middleware. This interface enforces two public
methods: ``setNext(callback $next)`` (sets the callback pointing to the next layer/endpoint)
and ``__invoke($env)`` which performs the middleware logic and then (if the
middleware logic allows) forwards the request to the next middleware or endpoint.

Here is an example middleware which´checks for the $_GET parameter "go" and returns
a 404 if it cannot find it::

  namespace MyNamespace;
  
  use \Inject\Core\Middleware\MiddlewareInterface;
  
  class HasGo implements MiddlewareInterface
  {
      proteted $next;
      
      public function setNext($callback)
      {
          $this->next = $callback;
      }

      public function __invoke($env)
      {
          if( ! isset($_GET['go']))
          {
              return array(404, array(), 'Page not found');
          }
          
          $callback = $next;
          return $callback($env);
      }
  }

What is an endpoint?
--------------------

An endpoint is either a PHP object with an ``__invoke($env)`` method or a closure taking
a single parameter. Usually the main endpoint of your application will be the router
which in turn will initialize the controller specific middleware stack leading to the
action.





