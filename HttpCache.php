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

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache as BaseHttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Base class for enhanced Symfony reverse proxy.
 *
 * @author Jérôme Vieilledent <lolautruche@gmail.com> (courtesy of eZ Systems AS)
 *
 * {@inheritdoc}
 */
abstract class HttpCache extends BaseHttpCache
{
    /**
     * Hash for anonymous user.
     */
    const ANONYMOUS_HASH = '38015b703d82206ebc01d17a39c727e5';

    /**
     * Accept header value to be used to request the user hash to the backend application.
     * It must match the one defined in FOSHttpCacheBundle's configuration.
     */
    const USER_HASH_ACCEPT_HEADER = 'application/vnd.fos.user-context-hash';

    /**
     * Name of the header the user context hash will be stored into.
     * It must match the one defined in FOSHttpCacheBundle's configuration.
     */
    const USER_HASH_HEADER = 'X-User-Context-Hash';

    /**
     * URI used with the forwarded request for user context hash generation.
     */
    const USER_HASH_URI = '/_fos_user_context_hash';

    /**
     * HTTP Method used with the forwarded request for user context hash generation.
     */
    const USER_HASH_METHOD = 'GET';

    /**
     * Prefix for session names.
     * Must match your session configuration.
     */
    const SESSION_NAME_PREFIX = 'PHPSESSID';

    /**
     * Generated user hash.
     *
     * @var string
     */
    private $userHash;

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!$this->isInternalRequest($request)) {
            // Prevent tampering attacks on the hash mechanism
            if ($request->headers->get('accept') === static::USER_HASH_ACCEPT_HEADER
                || $request->headers->get(static::USER_HASH_HEADER) !== null) {
                return new Response('', 400);
            }

            if ($request->isMethodSafe()) {
                $request->headers->set(static::USER_HASH_HEADER, $this->getUserHash($request));
            }
        }

        return parent::handle($request, $type, $catch);
    }

    /**
     * Checks if passed request object is to be considered internal (e.g. for user hash lookup).
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isInternalRequest(Request $request)
    {
        return $request->attributes->get('internalRequest', false) === true;
    }

    /**
     * Returns the user context hash for $request.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getUserHash(Request $request)
    {
        if (isset($this->userHash)) {
            return $this->userHash;
        }

        if ($this->isAnonymous($request)) {
            return $this->userHash = static::ANONYMOUS_HASH;
        }

        // Forward the request to generate the user hash
        $forwardReq = $this->generateForwardRequest($request);
        $resp = $this->handle($forwardReq);
        // Store the user hash in memory for sub-requests (processed in the same thread).
        $this->userHash = $resp->headers->get(static::USER_HASH_HEADER);

        return $this->userHash;
    }

    /**
     * Checks if current request is considered anonymous.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isAnonymous(Request $request)
    {
        foreach ($request->cookies as $name => $value) {
            if ($this->isSessionName($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if passed string can be considered as a session name, such as would be used in cookies.
     *
     * @param string $name
     *
     * @return bool
     */
    private function isSessionName($name)
    {
        return strpos($name, static::SESSION_NAME_PREFIX) === 0;
    }

    /**
     * Generates the request object that will be forwarded to get the user context hash.
     *
     * @param Request $request
     *
     * @return Request
     */
    private function generateForwardRequest(Request $request)
    {
        $forwardReq = Request::create(static::USER_HASH_URI, static::USER_HASH_METHOD, array(), array(), array(), $request->server->all());
        $forwardReq->attributes->set('internalRequest', true);
        $forwardReq->headers->set('Accept', static::USER_HASH_ACCEPT_HEADER);
        $this->cleanupForwardRequest($forwardReq, $request);

        return $forwardReq;
    }

    /**
     * Cleans up request to forward for user hash generation.
     * Cleans cookie header to only get proper sessionIds in it. This is to make the hash request cacheable.
     *
     * @param Request $forwardReq
     * @param Request $originalRequest
     */
    protected function cleanupForwardRequest(Request $forwardReq, Request $originalRequest)
    {
        $sessionIds = array();
        foreach ($originalRequest->cookies as $name => $value) {
            if ( $this->isSessionName($name)) {
                $sessionIds[$name] = $value;
                $forwardReq->cookies->set($name, $value);
            }
        }
        $forwardReq->headers->set('Cookie', http_build_query($sessionIds, '', '; '));
    }
}
