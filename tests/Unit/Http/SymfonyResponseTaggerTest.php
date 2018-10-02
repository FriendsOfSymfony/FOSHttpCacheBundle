<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Unit\Http;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SymfonyResponseTaggerTest extends TestCase
{
    public function testTagResponse()
    {
        $tags1 = ['post-1', 'posts'];
        $tags2 = ['post-2'];
        $tags3 = ['different'];

        $symfonyResponseTagger1 = new SymfonyResponseTagger();
        $response = new Response();
        $response->headers->set('X-Cache-Tags', '');
        $symfonyResponseTagger1->addTags($tags1);
        $symfonyResponseTagger1->tagSymfonyResponse($response);
        $this->assertTrue($response->headers->has('X-Cache-Tags'));
        $this->assertEquals(implode(',', $tags1), $response->headers->get('X-Cache-Tags'));
        $this->assertFalse($symfonyResponseTagger1->hasTags());

        $symfonyResponseTagger2 = new SymfonyResponseTagger();
        $symfonyResponseTagger2->addTags($tags2);
        $symfonyResponseTagger2->tagSymfonyResponse($response);
        $this->assertEquals(implode(',', array_merge($tags2, $tags1)), $response->headers->get('X-Cache-Tags'));

        $symfonyResponseTagger3 = new SymfonyResponseTagger();
        $symfonyResponseTagger3->addTags($tags3);
        $symfonyResponseTagger3->tagSymfonyResponse($response, true);
        $this->assertEquals(implode(',', $tags3), $response->headers->get('X-Cache-Tags'));
    }
}
