The Tag Handler
===============

Service to work with :doc:`Cache Tagging <../features/tagging>`. You can add tags to the
handler and generate invalidation requests that are queued in the invalidator.

A response listener checks the ``TagHandler`` to detect tags that need to be
set on a response. As with other invalidation operations, invalidation requests
are flushed to the caching proxy :ref:`after the response has been sent <flushing>`.

.. _tag_handler_addtags:

``addTags()``
-------------

Add tags to be sent with the response::

    $tagHandler->addTags(array('some-tag', 'other-tag'));

This method can be called regardless of whether the response object already
exists or not.

``invalidateTags()``
--------------------

Invalidate cache tags::

    $tagHandler->invalidateTags(array('some-tag', 'other-tag'));
