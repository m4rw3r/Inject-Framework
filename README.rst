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

Required
--------

* PHP >= 5.3
* PCRE Extension
* PCRE with UTF-8 support (``--enable-utf8``)
* Reflection Extension
* Tokenizer Extension
* SPL (Standard PHP Library) Extension

Optional
--------

* Iconv Extension (for ``\Inject\Core\Middleware\Utf8Filter``)
* Phix__ (for console commands, optional)

PCRE (usually with UTF-8 support), Reflection, SPL and Tokenizer extensions are already
included in the default PHP configuration.

.. __: http://blog.stuartherbert.com/php/2011/03/21/introducing-phix/

Getting started
===============

Install Phix (optional)
-----------------------

The recommended way to install Phix_ is to use the `PEAR Installer`_:

::

  sudo pear channel-discover pear.gradwell.com
  sudo pear install --alldeps Gradwell/phix

.. _Phix: http://github.com/Gradwell/phix
.. _PEAR Installer: http://pear.php.net/

Install InjectFramework
-----------------------

Currently no readily made PEAR package is provided, but you can build one yourself if
you have the tools::

  phing pear-package
  cd dist
  pear install InjectFramework-<version>.tgz

If you do not want to install it via PEAR, just place the contents of the ``src/php/``
directory somewhere in your PHP installation's include path.

Creating your first application
-------------------------------

If you have  Phix installed you can just run::

  phing inject:app <yourappname> <appfolder>

This will generate an application skeleton with the name <yourappname> and place that
in <appfolder>.

This command requires that the ``src/php/Inject/`` folder is placed in a folder in your
PHP installation's include path (automatically done by PEAR), or that you run the
command in the InjectFramework project directory or that you use the
``--include=<path/to/inject/frameworks/src/php/dir>`` switch for Phix.

*Note:* This command does not yet generate a fully function application skeleton yet as
many framework features are still missing.

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

For a simple middleware which does something more useful, look at
``\Inject\Core\Middleware\RunTimer`` which times the execution of all the following
middleware and endpoint(s) and code called by those.

What is an endpoint?
--------------------

An endpoint is a PHP object implementing ``__invoke($env)`` method (which also includes
PHP closures taking a single parameter). Usually the main endpoint of your application
will be the router (``\Inject\Web\RouterEndpoint``) which in turn will initialize the
controller specific middleware stack leading to the controller's action.

Simple endpoint::

  class MyEndpoint
  {
      public function __invoke($env)
      {
          return array(200, array('Content-Type' => 'text/plain'), 'Hello World!');
      }
  }

For a more complicated endpoint, see the ``\Inject\Core\CascadeEndpoint``.
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
``Content-Length`` header is not needed, as it will be generated by ``\Inject\Web\Responder``.

Example response array::

  array(200,
      array('Content-Type' => 'text/html; charset=utf-8'),
      '<?xml version="1.0" encoding="UTF-8"?>
      <!DOCTYPE html PUBLIC ...')







