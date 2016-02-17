<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache as BaseHttpCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

@trigger_error('The '.__NAMESPACE__.'\HttpCache class is deprecated since version 1.2 and will be removed in 2.0. Use FOS\HttpCacheBundle\SymfonyCache\EventDispatchingHttpCache instead.', E_USER_DEPRECATED);

/**
 * Base class for enhanced Symfony reverse proxy based on the symfony FrameworkBundle HttpCache.
 *
 * @deprecated Use FOS\HttpCacheBundle\SymfonyCache\EventDispatchingHttpCache instead.
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 *
 * {@inheritdoc}
 */
abstract class HttpCache extends BaseHttpCache
{
    /**
     * Hash for anonymous user.
     *
     * @deprecated Use the options on UserContextSubscriber instead
     */
    const ANONYMOUS_HASH = '38015b703d82206ebc01d17a39c727e5';

    /**
     * Accept header value to be used to request the user hash to the backend application.
     * It must match the one defined in FOSHttpCacheBundle's configuration.
     *
     * @deprecated Use the options on UserContextSubscriber instead
     */
    const USER_HASH_ACCEPT_HEADER = 'application/vnd.fos.user-context-hash';

    /**
     * Name of the header the user context hash will be stored into.
     * It must match the one defined in FOSHttpCacheBundle's configuration.
     *
     * @deprecated Use the options on UserContextSubscriber instead
     */
    const USER_HASH_HEADER = 'X-User-Context-Hash';

    /**
     * URI used with the forwarded request for user context hash generation.
     *
     * @deprecated Use the options on UserContextSubscriber instead
     */
    const USER_HASH_URI = '/_fos_user_context_hash';

    /**
     * HTTP Method used with the forwarded request for user context hash generation.
     *
     * @deprecated Use the options on UserContextSubscriber instead
     */
    const USER_HASH_METHOD = 'GET';

    /**
     * Prefix for session names.
     * Must match your session configuration.
     *
     * @deprecated Use the options on UserContextSubscriber instead
     */
    const SESSION_NAME_PREFIX = 'PHPSESSID';

    /**
     * @var UserContextSubscriber
     */
    private $subscriber;

    public function __construct(HttpKernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->subscriber = new UserContextSubscriber(array(
            'anonymous_hash' => static::ANONYMOUS_HASH,
            'user_hash_accept_header' => static::USER_HASH_ACCEPT_HEADER,
            'user_hash_header' => static::USER_HASH_HEADER,
            'user_hash_uri' => static::USER_HASH_URI,
            'user_hash_method' => static::USER_HASH_METHOD,
            'session_name_prefix' => static::SESSION_NAME_PREFIX,
        ));
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $event = new CacheEvent($this, $request);
        $this->subscriber->preHandle($event);
        if ($event->getResponse()) {
            return $event->getResponse();
        }

        return parent::handle($request, $type, $catch);
    }
}
