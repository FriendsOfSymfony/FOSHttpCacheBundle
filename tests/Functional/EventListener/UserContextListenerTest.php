<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserContextListenerTest extends WebTestCase
{
    public function testHashLookup()
    {
        $client = static::createClient();

        $client->request('GET', '/_fos_user_context_hash', [
            'accept' => 'application/vnd.fos.user-context-hash',
        ]);
        $response = $client->getResponse();

        $this->assertTrue($response->headers->has('X-User-Context-Hash'), 'X-User-Context-Hash header missing on the response');
    }
}
