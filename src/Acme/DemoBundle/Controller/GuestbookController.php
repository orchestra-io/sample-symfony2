<?php

namespace Acme\DemoBundle\Controller;

/**
 * @extra:Route("/guestbook")
 */
class GuestbookController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
    /**
     * @extra:Route("")
     * @extra:Template
     */
    public function indexAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $rep = $em->getRepository('AcmeDemoBundle:Guestbook');

        return array(
            'guestbooks' => $rep->findAll(),
        );
    }
}
