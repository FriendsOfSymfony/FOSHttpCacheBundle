<?php

namespace FOS\HttpCacheBundle\Tests\Functional\Fixtures\Session;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TestSessionStorage implements SessionStorageInterface
{
    private $started = false;

    private $bags = array();

    /**
     * Starts the session.
     *
     * @throws \RuntimeException If something goes wrong starting the session.
     *
     * @return bool True if started.
     *
     * @api
     */
    public function start()
    {
        $this->started = true;
    }

    /**
     * Checks if the session is started.
     *
     * @return bool True if started, false otherwise.
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Returns the session ID
     *
     * @return string The session ID or empty.
     *
     * @api
     */
    public function getId()
    {
        return 'test';
    }

    /**
     * Sets the session ID
     *
     * @param string $id
     *
     * @api
     */
    public function setId($id)
    {
    }

    /**
     * Returns the session name
     *
     * @return mixed The session name.
     *
     * @api
     */
    public function getName()
    {
        return 'TESTSESSID';
    }

    /**
     * Sets the session name
     *
     * @param string $name
     *
     * @api
     */
    public function setName($name)
    {
    }

    /**
     * Regenerates id that represents this storage.
     *
     * This method must invoke session_regenerate_id($destroy) unless
     * this interface is used for a storage object designed for unit
     * or functional testing where a real PHP session would interfere
     * with testing.
     *
     * Note regenerate+destroy should not clear the session data in memory
     * only delete the session data from persistent storage.
     *
     * @param bool $destroy  Destroy session when regenerating?
     * @param int  $lifetime Sets the cookie lifetime for the session cookie. A null value
     *                       will leave the system settings unchanged, 0 sets the cookie
     *                       to expire with browser session. Time is in seconds, and is
     *                       not a Unix timestamp.
     *
     * @return bool True if session regenerated, false if error
     *
     * @throws \RuntimeException If an error occurs while regenerating this storage
     *
     * @api
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        return true;
    }

    /**
     * Force the session to be saved and closed.
     *
     * This method must invoke session_write_close() unless this interface is
     * used for a storage object design for unit or functional testing where
     * a real PHP session would interfere with testing, in which case it
     * it should actually persist the session data if required.
     *
     * @throws \RuntimeException If the session is saved without being started, or if the session
     *                           is already closed.
     */
    public function save()
    {
    }

    /**
     * Clear all session data in memory.
     */
    public function clear()
    {
    }

    /**
     * Gets a SessionBagInterface by name.
     *
     * @param string $name
     *
     * @return SessionBagInterface
     *
     * @throws \InvalidArgumentException If the bag does not exist
     */
    public function getBag($name)
    {
        return $this->bags[$name];
    }

    /**
     * Registers a SessionBagInterface for use.
     *
     * @param SessionBagInterface $bag
     */
    public function registerBag(SessionBagInterface $bag)
    {
        if ($bag->getName() == "attributes") {
            $bag->set('_security_secured_area', serialize(new UsernamePasswordToken('user', 'user', 'in_memory', array('ROLE_USER'))));
        }

        $this->bags[$bag->getName()] = $bag;
    }

    /**
     * @return MetadataBag
     */
    public function getMetadataBag()
    {
        return null;
    }
}
