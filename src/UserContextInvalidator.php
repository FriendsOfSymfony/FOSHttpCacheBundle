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

use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;

class UserContextInvalidator
{
    /**
     * Service used to ban hash request.
     *
     * @var BanCapable
     */
    private $banner;

    /**
     * Accept header.
     *
     * @var string
     */
    private $acceptHeader;

    /**
     * User identifier headers.
     *
     * @var string[]
     */
    private $userIdentifierHeaders;

    public function __construct(BanCapable $banner, $userIdentifierHeaders, $acceptHeader)
    {
        $this->banner = $banner;
        $this->acceptHeader = $acceptHeader;
        $this->userIdentifierHeaders = $userIdentifierHeaders;
    }

    /**
     * Invalidate the user context hash.
     *
     * @param string $sessionId
     */
    public function invalidateContext($sessionId)
    {
        foreach ($this->userIdentifierHeaders as $header) {
            $this->banner->ban([
                'accept' => $this->acceptHeader,
                $header => sprintf('.*%s.*', $sessionId),
            ]);
        }
    }
}
