<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Security\Http\Logout;

use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class ContextInvalidationLogoutHandler implements LogoutHandlerInterface
{
    /**
     * Service used to ban hash request
     *
     * @var \FOS\HttpCache\ProxyClient\Invalidation\BanInterface
     */
    private $banner;

    /**
     * Accept header
     *
     * @var string
     */
    private $acceptHeader;

    /**
     * User identifier headers
     *
     * @var string[]
     */
    private $userIdentifierHeaders;

    public function __construct(BanInterface $banner, $userIdentifierHeaders, $acceptHeader)
    {
        $this->banner                = $banner;
        $this->acceptHeader          = $acceptHeader;
        $this->userIdentifierHeaders = $userIdentifierHeaders;
    }

    /**
     * Invalidate the user context hash
     *
     * @param Request        $request
     * @param Response       $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $sessionId = $request->getSession()->getId();

        foreach ($this->userIdentifierHeaders as $header) {
            $this->banner->ban(array(
                'accept' => $this->acceptHeader,
                $header => sprintf('.*%s.*', $sessionId),
            ));
        }
    }
}
