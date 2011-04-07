<?php

namespace Acme\DemoBundle\Entity;

/**
 * @orm:Entity
 */
class Guestbook
{
    /**
     * @orm:Id @orm:GeneratedValue @orm:column(type="integer")
     */
    public $id;

    /**
     * @orm:column(type="string")
     */
    public $content;
}
