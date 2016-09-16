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

    public function postFilesAction(Request $request)
    {
        $form = $this->createForm(InputFileType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $manager = $this->get('file_manager');
            $name = $form->getData()['filename'];
            $fullName = self::DIR.'\\'.$name;

            if ($manager->getMeta($fullName)) {
                return $this->handleView($this->view(array(
                    'error' => 'This file already exists. Please, choose another file name.'
                ), 400));
            }

            $manager->saveFile($form->getData()['content'],
                self::DIR,
                $name);

            return $this->handleView($this->view(null, 201));
        }
        else {
            $response = array(
                'error' => 'Bad request',
                'details' => $form->getErrors(),
            );
            return $this->handleView($this->view($response, 400));
        }
    }

//    public function putFileAction($filename, Request $request)
//    {
//        $request->request->add(array('filename' => $filename));
//        $form = $this->createForm(InputFileType::class);
//        $form->handleRequest($request);
//
//        if ($form->isValid()) {
//            $manager = $this->get('file_manager');
//            $manager->saveFile($form->getData()['content'], self::DIR, $filename);
//
//            return $this->handleView($this->view(null, 201));
//        }
//        else {
//            $response = array(
//                'error' => 'Bad request',
//                'details' => $form->getErrors()
//            );
//            return $this->handleView($this->view($response, 400));
//        }
//    }
}
