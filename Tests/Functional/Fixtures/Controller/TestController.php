<?php

namespace FOS\HttpCacheBundle\Tests\Functional\Fixtures\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller
{
    public function testAction()
    {
        return new Response('hello');
    }
} 