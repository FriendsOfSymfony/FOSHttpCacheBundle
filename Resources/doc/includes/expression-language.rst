``expression_language``
-----------------------

**type**: ``string``

If your application is using a `custom expression language`_ which is extended
from Symfony's `expression language component`_, you need to `define it as a service`_
and configure it as `expression_language` in the sections where you want to use it.

.. _expression language component: https://symfony.com/doc/current/components/expression_language.html
.. _define it as a service: https://symfony.com/doc/current/controller/service.html
.. _custom expression language: https://symfony.com/doc/current/components/expression_language/extending.html
