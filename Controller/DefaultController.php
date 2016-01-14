<?php

namespace SmarterSolutions\EzComponents\EzContentTypeManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('EzContentTypeManagerBundle:Default:index.html.twig', array('name' => $name));
    }
}
