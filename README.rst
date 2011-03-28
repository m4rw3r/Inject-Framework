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

In more general terms, the execution is done using the ``MiddlewareStack`` which contains
a series of middlewares which will process the request before it is handed to the
endpoint callback (which is either a closure or an object with ``__invoke()``).

What is middleware?
-------------------

If you have used Ruby on Rails or Ruby's Rack webserver interface you probably already
know what it is as it is almost a port.

A middleware is an object implementing ``Inject\Core\Middleware\MiddlewareInterface``
which specifies a basic interface for middleware. This interface enforces two public
methods: ``setNext(callback $next)`` (sets the callback pointing to the next layer/endpoint)
and ``__invoke($env)`` which performs the middleware logic and then (if the
middleware logic allows) forwards the request to the next middleware or endpoint.

The main reason for the usage of an interface is that it is not feasible to inject the
next middleware using the constructor of a middleware, mainly because it will not be
as fast or flexible in PHP as it is in ruby.

Here is an example middleware which´checks for the $_GET parameter "go" and returns
a 404 if it cannot find it::

  namespace MyNamespace;
  
  use \Inject\Core\Middleware\MiddlewareInterface;
  
  class BlockIfNotGo implements MiddlewareInterface
  {
      protected $next;
      
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
          
          $callback = $this->next;
          return $callback($env);
      }
  }

What is an endpoint?
--------------------

An endpoint is either a PHP object with an ``__invoke($env)`` method or a closure taking
a single parameter. Usually the main endpoint of your application will be the router
which in turn will initialize the controller specific middleware stack leading to the
action.

For a simple endpoint, see the ``\Inject\Core\CascadeEndpoint``.
This endpoint attempts several callbacks until one does not return a response with the
header ``X-Cascade`` set to ``pass``. So the associated callbacks will return a response
along the lines of ``array(404, array('X-Cascade' => 'pass'), '')`` if they do not process
the request.

This is also how the ``\Inject\Web\RouterEndpoint`` works, only that instead of generic
callbacks it attempts to call routes.

Response format
---------------

The format of the response is very simple; just a plain PHP array containing response code,
headers and content, in that order.

Example response array::

  array(200,
      array('Content-Type' => 'text/html; charset=utf-8'),
      '<?xml version="1.0" encoding="UTF-8"?>
      <!DOCTYPE html PUBLIC ...')






