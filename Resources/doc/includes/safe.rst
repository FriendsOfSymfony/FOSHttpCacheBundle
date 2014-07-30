1. the HTTP request matches *all* criteria defined under ``match``
2. the HTTP request is :term:`safe` (GET or HEAD)
3. the HTTP response is considered :term:`cacheable` (override with
   :ref:`additional_cacheable_status` and :ref:`match_response`).
