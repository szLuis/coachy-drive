<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
	
        
	return  new JsonResponse(['response'=>$request], 200, array('content-type' => 'text/json', 'Access-Control-Allow-Origin' => '*')) ;

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("uploadfile/", name="upload_file")
     */
    public function newAction(Request $request)
    {
        //$file=$request->files->get('file');
        //$fileuploader->upload($file);
        return  new JsonResponse(['response'=>'success'], 200, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
    }
}
