<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use AppBundle\Entity\Drive;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class DefaultController extends FOSRestController
{
    /**
     * @Route("filedirectory/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $encoders = array(new JsonEncoder());
        $normalizers = array(new DateTimeNormalizer('Y-m-d'),new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

    
        $em = $this->getDoctrine()->getManager();

        $parentItems = $em->getRepository('AppBundle:Drive')->getParentItems();
        // $child = json_encode($em->getRepository('AppBundle:Drive')->getItem(7));
        // var_dump($child);
        // exit();
        $fileDirectory = $em->getRepository('AppBundle:Drive')->getFileDirectory($parentItems);
         
        
        // exit();
        // $fileDirectoryJSON = $serializer->serialize($fileDirectory, 'json');
        // var_dump($fileDirectoryJSON);
        // $fileDirectoryJSON = json_encode($fileDirectory);
        $fileDirectory = array("id" => 0,"icon" => "folder", "title" => "My drive", 
        "dateCreated" =>  "2018-06-15","detailsLink"=>  "#", "star"=>  true, "deleted"=>  false,
        "hasChildren"=> true, "children"=>  $fileDirectory);

	    return  new JsonResponse([$fileDirectory], 200, array('content-type' => 'text/json', 'Access-Control-Allow-Origin' => '*')) ;

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("uploadfile/", name="upload_file")
     */
    public function newAction(Request $request, FileUploader $fileuploader)
    {
        try
        {
            $file = $request->files->get('file');

            $targetDirectory = json_decode($request->get('targetDirectory'));
            $newTargetDirectory="";
            array_shift($targetDirectory);
            foreach ($targetDirectory as $dir)
            {
                $newTargetDirectory .=$dir . "/";
            }

            $idTargetDirectory = $request->get('idTargetDirectory');
            $fileuploader->setTargetDirectory($newTargetDirectory);
            $fileName = $fileuploader->upload($file);
            
            $drive = new Drive();        
            $drive->setIcon("file");
            $drive->setTitle($fileName);
            $drive->setDateCreated(new \DateTime('now'));
            $drive->setLinkDetails("#");
            $drive->setStar(false);
            $drive->setDeleted(false);
            $drive->setHasChildren(false);
            $drive->setChildren(null);
            $drive->setParent(0);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($drive);
            $em->flush();

            //add new folder Id as children of targetDirectory
            $parentDirectory = $em->getRepository('AppBundle:Drive')->find($idTargetDirectory);
            $newChild = [$drive->getId()];
            $currentChildren = $parentDirectory->getChildren();
            $newChildren = array_merge($currentChildren, $newChild);
            $parentDirectory->setHasChildren(true);
            $parentDirectory->setChildren($newChildren);
            $em->flush();

            return  new JsonResponse(['response'=>'success', 'id'=>$drive->getId()], 200, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
        catch(\Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
        
    }

    /**
     * @Route("createdirectory/", name="create_directory")
     */
    public function newDirectoryAction(Request $request, FileUploader $fileuploader)
    {
        try
        {
            $filesystem  = new Filesystem();       

            $driveDirectory = $fileuploader->getTargetDirectory();

            $newdir = $request->get('directoryName');
            $targetDirectory = $request->get('targetDirectory');
            $newTargetDirectory="";
            array_shift($targetDirectory);
            foreach ($targetDirectory as $dir)
            {
                $newTargetDirectory .=$dir . "/";
            }
            $idTargetDirectory = $request->get('idTargetDirectory');
            // var_dump($newdir);
            // var_dump($targetDirectory);
            // var_dump($idTargetDirectory);

            if ($filesystem->exists($driveDirectory . '/' . $newTargetDirectory .  $newdir )){
                return new JsonResponse(['response'=>'Directory already exists, please rename it before submit'], 301, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
            }
            //create new directory under target directory
            $dir = $filesystem->mkdir($driveDirectory . '/' . $newTargetDirectory .  $newdir);

            // add new folder to database
            $drive = new Drive();        
            $drive->setIcon("folder");
            $drive->setTitle($newdir);
            $drive->setDateCreated(new \DateTime('now'));
            $drive->setLinkDetails("#");
            $drive->setStar(false);
            $drive->setDeleted(false);
            $drive->setHasChildren(false);
            $drive->setChildren(null);
            if ($idTargetDirectory == "0") //se está agregando en la raíz de la carpeta
                $drive->setParent(1);
            else {
                $drive->setParent(0);
            }
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($drive);
            $em->flush();

            //add new folder Id as children of targetDirectory
            if ($idTargetDirectory != "0") //sino se está agregando en la raíz de la carpeta, update chilren
            {
                $parentDirectory = $em->getRepository('AppBundle:Drive')->find($idTargetDirectory);
                $newChild = [$drive->getId()];
                $currentChildren = $parentDirectory->getChildren();
                $newChildren = array_merge($currentChildren, $newChild);
                $parentDirectory->setHasChildren(true);
                $parentDirectory->setChildren($newChildren);
                $em->flush();
            }
            

            return  new JsonResponse(['response'=>'success', 'id'=>$drive->getId()], 200, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
            
        }
        catch(\Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
        
    }

    /**
     * @Route("star/", name="star_item")
     */
    public function starItemAction(Request $request)
    {
        try
        {
            $idTargetItem = $request->get('idTargetItem');
            $em = $this->getDoctrine()->getManager();
            $item = $em->getRepository('AppBundle:Drive')->find($idTargetItem);
            $item->setStar(!$item->getStar());
            $em->flush();
            return  new JsonResponse(['response'=>'success'], 200, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
        catch(\Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
    }

    /**
     * @Route("trash/", name="trash_item")
     */
    public function trashItemAction(Request $request)
    {
        try
        {
            $idTargetItem = $request->get('idTargetItem');
            $em = $this->getDoctrine()->getManager();
            $item = $em->getRepository('AppBundle:Drive')->find($idTargetItem);
            $item->setDeleted(!$item->getDeleted());
            $em->flush();
            return  new JsonResponse(['response'=>'success'], 200, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
        catch(\Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
    }
}
