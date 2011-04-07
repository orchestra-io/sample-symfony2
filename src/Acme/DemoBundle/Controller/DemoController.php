<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Acme\DemoBundle\Form\ContactForm;

class DemoController extends Controller
{
    /**
     * @extra:Route("/", name="_demo")
     * @extra:Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @extra:Route("/hello/{name}", name="_demo_hello")
     * @extra:Template()
     */
    public function helloAction($name)
    {
        return array('name' => $name);
    }
}
