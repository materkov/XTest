<?php
/**
 * Created by PhpStorm.
 * User: Maksim
 * Date: 16.09.2016
 * Time: 15:33
 */

namespace AppBundle\Controller;


use AppBundle\Form\InputFileType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadController extends Controller
{
    /**
     * @Route("/files/{filename}")
     * @Method("PUT")
     * @param $filename
     * @param Request $request
     * @return array|Response
     */
    public function uploadFileAction($filename, Request $request)
    {
        $request->request->add(array('filename' => $filename));
        $form = $this->createForm(InputFileType::class);
        $form->handleRequest($request);

        $content = $request->getContent();
        if (empty($content)) {
            $response = new Response(
                json_encode(array('error' => 'Bad request (body is empty)')),
                400, array('Content-Type' => 'application/json')
            );
            return $response;
        }

        if ($form->isValid()) {
            $manager = $this->get('file_manager');

            $fullName = $this->getParameter('files_dir').DIRECTORY_SEPARATOR.$form->getData()['filename'];
            $meta = $manager->getMeta($fullName);

            $manager->saveFile($fullName, $request->getContent());

            $response = new Response('',
                $meta ? 200 : 201,
                array('Content-Type' => 'application/json')
            );
            return $response;
        }
        else {
            $response = array('error' => 'Bad request (wrong filename)');
            $response = new Response(json_encode($response),
                400,
                array('Content-Type' => 'application/json')
            );
            return $response;
        }
    }
}