<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\Fixtures\Session;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * {@inheritdoc}
 */
class TestSessionStorage implements SessionStorageInterface
{
    private $started = false;

    private $bags = [];

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        return $this->started = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'TESTSESSID';
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBag($name)
    {
        return $this->bags[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag)
    {
        if ($bag->getName() === 'attributes') {
            $bag->set('_security_secured_area', serialize(new UsernamePasswordToken('user', 'user', 'in_memory', ['ROLE_USER'])));
        }

        $this->bags[$bag->getName()] = $bag;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataBag()
    {
        return;
    }
}
