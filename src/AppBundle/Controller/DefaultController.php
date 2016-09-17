<?php

namespace AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FOSRestController
{
    public function getFilesAction()
    {
        $manager = $this->get('file_manager');

        return $this->handleView($this->view($manager->listDir($this->getParameter('files_dir'))));
    }

    public function getFileAction($name)
    {
        $manager = $this->get('file_manager');
        $fullName = $this->getParameter('files_dir').DIRECTORY_SEPARATOR.$name;
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
        $fullName = $this->getParameter('files_dir').DIRECTORY_SEPARATOR.$name;
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
