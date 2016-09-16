<?php

namespace AppBundle\Controller;

use AppBundle\Form\InputFileType;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FOSRestController
{
    const DIR = 'C:\\test';

    public function getFilesAction()
    {
        $manager = $this->get('file_manager');

        return $this->handleView($this->view($manager->listDir(self::DIR)));
    }

    public function getFileAction($name)
    {
        $manager = $this->get('file_manager');
        $fullName = self::DIR.'\\'.$name;
        $content = $manager->getContent($fullName);

        $response = new Response();

        if ($content) {
            $response->setStatusCode(200);
            $response->setContent($content);

            $response->headers->add(array(
               'Content-Type' => $manager->getMIME($fullName)
            ));
        }
        else {
            $response->setStatusCode(404);
            $response->setContent(json_encode(array('error' => 'File not found')));
        }

        return $response;
    }

    public function getFileMetaAction($name)
    {
        $manager = $this->get('file_manager');
        $fullName = self::DIR.'\\'.$name;
        $meta = $manager->getMeta($fullName);

        if ($meta) {
            $data = $meta;
            $status = 200;
        }
        else {
            $data = array('error' => 'File not found');
            $status = 404;
        }

        return $this->handleView($this->view($data, $status));
    }
}
