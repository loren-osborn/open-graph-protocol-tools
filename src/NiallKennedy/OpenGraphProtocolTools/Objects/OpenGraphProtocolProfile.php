<?php
/**
 * Open Graph Protocol Tools
 *
 * @package open-graph-protocol-tools
 * @author Niall Kennedy <niall@niallkennedy.com>
 * @version 2.0
 * @copyright Public Domain
 */

namespace NiallKennedy\OpenGraphProtocolTools\Objects;

/**
 * Open Graph protocol person (profile)
 *
 * @link http://ogp.me/ Open Graph protocol
 */
class OpenGraphProtocolProfile extends OpenGraphProtocolObject
{
    /**
     * Property prefix
     * @var string
     */
    const PREFIX = 'profile';

    /**
     * prefix namespace
     * @var string
     */
    const NS = 'http://ogp.me/ns/profile#';

    /**
     * A person's given name
     * @var string
     */
    protected $firstName;

    /**
     * A person's last name
     * @var string
     */
    protected $lastName;

    /**
     * The profile's unique username
     * @var string
     */
    protected $username;

    /**
     * Gender: male or female
     */
    protected $gender;

    /**
     * Get the person's given name
     * @return string given name
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the person's given name
     * @param string $firstName given name
     */
    public function setFirstName($firstName)
    {
        if (is_string($firstName) && !empty($firstName)) {
            $this->firstName = $firstName;
        }

        return $this;
    }

    /**
     * The person's family name
     * @return string famil name
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set the person's family name
     * @param string $lastName family name
     */
    public function setLastName($lastName)
    {
        if (is_string($lastName) && !empty($lastName)) {
            $this->lastName = $lastName;
        }

        return $this;
    }

    /**
     * Person's username on your site
     * @return string account username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the account username
     * @param string $username username
     */
    public function setUsername($username)
    {
        if (is_string($username) && !empty($username)) {
            $this->username = $username;
        }

        return $this;
    }

    /**
     * The person's gender. male|female
     * @return string male|female
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set the person's gender
     * @param string $gender male|female
     */
    public function setGender($gender)
    {
        if (is_string($gender) && ($gender === 'male' || $gender === 'female')) {
            $this->gender = $gender;
        }

        return $this;
    }
}
