<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Mocks a user object for use in testing
 */
namespace RDev\Tests\Models\Mocks;
use RDev\Models;

class User implements Models\IEntity
{
    /** @var int The user Id */
    private $id = -1;
    /** @var int The Id of an imaginary aggregate root (eg child) of this user */
    private $aggregateRootId = -1;
    /** @var string The username */
    private $username = "";

    /**
     * @param int $id The user Id
     * @param string $username The username
     */
    public function __construct($id, $username)
    {
        $this->id = $id;
        $this->username = $username;
    }

    /**
     * @return int
     */
    public function getAggregateRootId()
    {
        return $this->aggregateRootId;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param int $aggregateRootId
     */
    public function setAggregateRootId($aggregateRootId)
    {
        $this->aggregateRootId = $aggregateRootId;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
} 