================
Middleware Ideas
================

Authentication
==============

Will block access if the user is not logged in, instead it will redirect to a login page or similar.

JS Minification
===============

Minifies the JS and also checks for modifications, finally sending an X-Sendfile header to the webserver.
Will only work if *.js requests are sent to the front-controller (index.php).

Query Caching
=============



Response Caching
================

Caches responses which are not dynamic, (depending on returned headers from the rest of the app?)

Session
=======



Locale detection
================

Detects the locale based on a set of possible locales and the Accept-Language header.
The result of this is saved in $env['web.locale'] or similar, falls back to the default
value if it is not possible to match a language or if the header does not exists.

File-type detection
===================

Detects the format the client is requesting using the http accept header combined with
as set of available formats. The result is saved in $env['web.format'] or something
similar, falls back to default if not possible to match a format.