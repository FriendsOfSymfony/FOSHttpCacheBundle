<?php
header('Cache-Control: max-age=3600');
header(sprintf('Content-Type: %s', $_SERVER['HTTP_ACCEPT']));
header('X-Cache-Debug: 1');
header('Vary: Accept');