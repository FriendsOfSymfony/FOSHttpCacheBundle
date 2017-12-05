Contributing
============

We are happy for contributions. Before you invest a lot of time however, best
open an issue on GitHub to discuss your idea. Then we can coordinate efforts
if somebody is already working on the same thing. If your idea is specific to
the Symfony framework, it belongs into the ``FOSHttpCacheBundle``, otherwise
it should go into the ``FOSHttpCache`` library.
You can also find us in the #friendsofsymfony channel of `the Symfony Slack`_.

When you change code, you can run the tests as described in :doc:`testing`.

Building the Documentation
--------------------------

First `install Sphinx`_ and `install enchant`_ (e.g. ``sudo apt-get install enchant``),
then download the requirements:

.. code-block:: bash

    $ pip install -r Resources/doc/requirements.txt

To build the docs:

.. code-block:: bash

    $ cd doc
    $ make html
    $ make spelling
    

.. _install Sphinx: http://sphinx-doc.org/latest/install.html
.. _install enchant: http://www.abisource.com/projects/enchant/
.. _the Symfony Slack: https://symfony.com/slack-invite
