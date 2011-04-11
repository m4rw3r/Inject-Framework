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

  phix inject:app <yourappname> <appfolder>

This will generate an application skeleton with the name ``<yourappname>`` and place that
in ``<appfolder>``.

This command requires that the ``src/php/Inject/`` folder is placed in a folder in your
PHP installation's include path (automatically done by PEAR), or that you run the
command in the InjectFramework project directory or that you use the
``--include=<path/to/inject/frameworks/src/php/dir>`` switch for Phix.

*Note:* This command does not yet generate a fully functional application skeleton as
many core framework features are still missing.

Creating the ``index.php`` file
-------------------------------

To be able to run your application, you must have a PHP file which acts as
an entry point. This entry point will set a few of PHP's configuration settings,
load the Autoloader (`PSR-0`_ compliant) and finally call the ``ServerAdapter``
which will initialize the ``$env`` variable, call your application's ``stack()``
method to run it and finally it will pass on the information to the browser.

In ``src/php/www/`` you have a sample ``index.php.sample`` file. Copy this file
to your document root of your web-server (or wherever your web-server's PHP
environment will find and be able to execute it) and rename it to ``index.php``
(recommended filename).

Then you have to make two changes to this file:

1. Change the path to the autoloader, depending on if it is in PHP's include path
   or not, depending on where you have put it::
   
     include 'Inject/Autoloader.php';

2. Change the ``\Sample\Application`` class name to the application name of your
   application (``\<yourappname>\Application``)::
   
     $r = \MyApp\Application::instance()->stack()->run($env);

.. _`PSR-0`: http://groups.google.com/group/php-standards/web/psr-0-final-proposal

.. TODO: Change the paragraphs above to allow for the new ServerAdapter interface

Basic principles
================

The basic principle of the framework is like an onion; it consists of layers.
Each of these layers will perform a specific set of actions, either pre-processing
the request, post-processing the response, implementing application flow logic
or all of the above.

A layer in the framework is called "Middleware", as it lies in between the browser
and your controller action. These middleware can be added and removed depending
on the needs of your application.

.. TODO: More

For more detailed specifications, see `Middleware Specifications`_.

.. _`Middleware Specifications`: https://github.com/m4rw3r/Inject-Framework/blob/develop/SPEC.rst





