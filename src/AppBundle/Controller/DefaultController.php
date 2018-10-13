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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Asset\Context\NullContext;

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

        $fileDirectoryJSON = $em->getRepository('AppBundle:Drive')->getFileDirectoryJSON();

        return $fileDirectoryJSON;

        // // replace this example code with whatever you need
        // return $this->render('default/index.html.twig', [
        //     'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        // ]);
    }

    /**
     * @Route("uploadfile/", name="upload_file")
     */
    public function newAction(Request $request, FileUploader $fileuploader)
    {
        try
        {
            $file = $request->files->get('file');
            $driveDirectory = $fileuploader->getTargetDirectory();

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

            $fileLink = $newTargetDirectory . $fileName;
            
            $drive = new Drive();        
            $drive->setIcon("file");
            $drive->setTitle($fileName);
            $drive->setDateCreated(new \DateTime('now'));
            $drive->setLinkDetails($fileLink);
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
     * @Route("rename/", name="rename_item")
     */
    public function renameItem(Request $request, FileUploader $fileuploader)
    {
        try{
            $idItemToRename =  $request->get("id");
            $targetItemName = $request->get("title");
            $path = json_decode($request->get('targetDirectory'));
            
            // $idTargetDirectory = $request->get('idTargetDirectory');
            // $idItemToRename =  '7';
            // $targetItemName = 'birthdays';
            // $path = array('My drive', 'pics');
            // $idTargetDirectory = $request->get('idTargetDirectory');


            $em = $this->getDoctrine()->getManager();
            $targetItemToRename = $em->getRepository('AppBundle:Drive')->find($idItemToRename);
            $originItemName = $targetItemToRename->getTitle();
            
            $itemType = $targetItemToRename->getIcon();
            

            $fileSystem = new Filesystem();
            $driveDirectory = $fileuploader->getTargetDirectory(); //base directory
            
            $targetDirectory="";
            array_shift($path);
            foreach ($path as $dir)
            {
                $targetDirectory .=$dir . "/";
            }
                    
            $origin = $driveDirectory . '/' . $targetDirectory .  $originItemName ;
            $target = $driveDirectory . '/' . $targetDirectory .  $targetItemName ;

            // return $origin;

            if (!$fileSystem->exists($origin)){
                throw new IOException("Item path does not exist".$origin);
            }

            if (!is_dir($origin) && $itemType ==="folder"){
                throw new IOException("Item is not a directory");
            }

            if (!is_file($origin) && $itemType ==="file"){
                throw new IOException("Item is not a file");
            }
            
            $fileSystem->rename($origin, $target);
            $targetItemToRename->setTitle($targetItemName);
            $em->flush();
            $fileDirectoryJSON = $em->getRepository('AppBundle:Drive')->getFileDirectoryJSON();    
            return $fileDirectoryJSON;
            // return new JsonResponse(['response'=>'Item renamed successfully'], 200, array('content-type' => 'text/json'));
        }
        catch(\IOException $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('content-type' => 'text/json'));
        }
        catch(\Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('content-type'=>'text/json'));

        }
    }

    /**
     * @Route("movefolder/", name="move_directory")
     */
    public function moveDirectory(Request $request, FileUploader $fileuploader)
    {
        try
        {
            $driveDirectory = $fileuploader->getTargetDirectory();
            // $source = $request->get('source_directory');
            // $destination = $request->get('destination_directory');
            $currentDirectoryId = "49";
            $sourceId = "48";
            $destinationId = "0";

            $em = $this->getDoctrine()->getManager();
            
            //add new folder Id as children of destinationDirectory

            //sino se está agregando en la raíz de la carpeta, update chilren
            if ($sourceId != "0" && $destinationId != "0" && $currentDirectoryId == "0") 
            {
                $destinationDirectory = $em->getRepository('AppBundle:Drive')->find($destinationId);
                $newChild = [$sourceId];
                $currentChildren = $destinationDirectory->getChildren();
                $newChildren = array_merge($currentChildren, $newChild);
                $destinationDirectory->setHasChildren(true);
                $destinationDirectory->setChildren($newChildren);
                $em->flush($destinationDirectory);

                $sourceDirectory = $em->getRepository('AppBundle:Drive')->find($sourceId);
                $sourceDirectory->setParent(0);
                $em->flush($sourceDirectory);

            }
            //update children when it is moving to root folder (My drive)
            else if ($sourceId != "0" && $destinationId == "0" && $currentDirectoryId != "0") 
            {
                $currentDirectory = $em->getRepository('AppBundle:Drive')->find($currentDirectoryId);
                $currentChildren = $currentDirectory->getChildren();
                //search sourceId in the current directory children array
                $offSet = array_search($sourceId, $currentChildren);
                //remove the child from array
                array_splice($currentChildren, $offSet, 1);
                
                //check if no children in the array
                if (count($currentChildren) > 0) {
                    $currentDirectory->setHasChildren(true);
                    $currentDirectory->setChildren($currentChildren);
                }    
                else
                {
                    $currentDirectory->setHasChildren(false);
                    $currentDirectory->setChildren(null);
                }
                $em->flush($currentDirectory);

                $sourceDirectory = $em->getRepository('AppBundle:Drive')->find($sourceId);
                $sourceDirectory->setParent(1);
                $em->flush($sourceDirectory);
               

            }
            //update children when it is moving from folder X to folder Y
            else if ($sourceId != "0" && $destinationId != "0" && $currentDirectoryId != "0") 
            {
                $currentDirectory = $em->getRepository('AppBundle:Drive')->find($currentDirectoryId);
                $currentChildren = $currentDirectory->getChildren();
                //search sourceId in the current directory children array
                $offSet = array_search($sourceId, $currentChildren);
                //remove the child from array
                array_splice($currentChildren, $offSet, 1);
                
                //check if no children in the array
                if (count($currentChildren) > 0) {
                    $currentDirectory->setHasChildren(true);
                    $currentDirectory->setChildren($currentChildren);
                }    
                else
                {
                    $currentDirectory->setHasChildren(false);
                    $currentDirectory->setChildren(null);
                }
                $em->flush($currentDirectory);

                //Update children in destinationDirectory
                $destinationDirectory = $em->getRepository('AppBundle:Drive')->find($destinationId);
                $newChild = [$sourceId];
                $currentChildren = $destinationDirectory->getChildren();
                $newChildren = array_merge($currentChildren, $newChild);
                $destinationDirectory->setHasChildren(true);
                $destinationDirectory->setChildren($newChildren);
                $em->flush($destinationDirectory);

            }

            //Filesystem block
            // $newTargetDirectory="";
            // array_shift($targetDirectory);
            // array_pop($destination);

            // foreach ($targetDirectory as $dir)
            // {
            //     $newTargetDirectory .=$dir . "/";
            // }

            $source = "Yeah/yes/hey";
            $destination = "My drive";
            if ($destinationId == "0"){
                $destination = "";
            }
            
            $folderToMove = "hey";
            

            
            $filesystem = new Filesystem();

            $origin = $driveDirectory . DIRECTORY_SEPARATOR . $source ;
            $target = $driveDirectory . DIRECTORY_SEPARATOR . $destination ;

            //check if target directory exist, if not so create it
            if (!$filesystem->exists($target . DIRECTORY_SEPARATOR . $folderToMove )){
                $filesystem->mkdir($target . DIRECTORY_SEPARATOR . $folderToMove );
            }
            
            if (!is_dir($origin) || !is_dir($target))
            {
                throw new IOException("Source or Destination directory is not a directory");
            }
            
            // so now target is ready to get mirror of the origin
            $target = $target . DIRECTORY_SEPARATOR . $folderToMove;
            
            $filesystem->mirror($origin,$target);
            $filesystem->remove($origin);
            return new JsonResponse(['response' => 'Success on moving directory'], 200, array('content-type'=>'text/json'));
        }
        catch(IOException $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('content-type' => 'text/json'));
        }
        catch(Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('content-type' => 'text/json'));
        }
    }

        /**
     * @Route("movefile/", name="move_file")
     */
    public function moveFile(Request $request, FileUploader $fileuploader)
    {
        try
        {
            $driveDirectory = $fileuploader->getTargetDirectory();
            // $source = $request->get('source_directory');
            // $destination = $request->get('destination_directory');
            $currentDirectoryId = "0";
            $sourceId = "8";
            $destinationId = "5";

            $em = $this->getDoctrine()->getManager();
            
            //add new folder Id as children of destinationDirectory

            //sino se está agregando en la raíz de la carpeta, update chilren
            if ($sourceId != "0" && $destinationId != "0" && $currentDirectoryId == "0") 
            {
                $destinationDirectory = $em->getRepository('AppBundle:Drive')->find($destinationId);
                $newChild = [$sourceId];
                $currentChildren = $destinationDirectory->getChildren();
                $newChildren = array_merge($currentChildren, $newChild);
                $destinationDirectory->setHasChildren(true);
                $destinationDirectory->setChildren($newChildren);
                $em->flush($destinationDirectory);

                $sourceDirectory = $em->getRepository('AppBundle:Drive')->find($sourceId);
                $sourceDirectory->setParent(0);
                $em->flush($sourceDirectory);

            }
            //update children when it is moving to root folder (My drive)
            else if ($sourceId != "0" && $destinationId == "0" && $currentDirectoryId != "0") 
            {
                $currentDirectory = $em->getRepository('AppBundle:Drive')->find($currentDirectoryId);
                $currentChildren = $currentDirectory->getChildren();
                //search sourceId in the current directory children array
                $offSet = array_search($sourceId, $currentChildren);
                //remove the child from array
                array_splice($currentChildren, $offSet, 1);
                
                //check if no children in the array
                if (count($currentChildren) > 0) {
                    $currentDirectory->setHasChildren(true);
                    $currentDirectory->setChildren($currentChildren);
                }    
                else
                {
                    $currentDirectory->setHasChildren(false);
                    $currentDirectory->setChildren(null);
                }
                $em->flush($currentDirectory);

                $sourceDirectory = $em->getRepository('AppBundle:Drive')->find($sourceId);
                $sourceDirectory->setParent(1);
                $em->flush($sourceDirectory);
               

            }
            //update children when it is moving from folder X to folder Y
            else if ($sourceId != "0" && $destinationId != "0" && $currentDirectoryId != "0") 
            {
                $currentDirectory = $em->getRepository('AppBundle:Drive')->find($currentDirectoryId);
                $currentChildren = $currentDirectory->getChildren();
                //search sourceId in the current directory children array
                $offSet = array_search($sourceId, $currentChildren);
                //remove the child from array
                array_splice($currentChildren, $offSet, 1);
                
                //check if no children in the array
                if (count($currentChildren) > 0) {
                    $currentDirectory->setHasChildren(true);
                    $currentDirectory->setChildren($currentChildren);
                }    
                else
                {
                    $currentDirectory->setHasChildren(false);
                    $currentDirectory->setChildren(null);
                }
                $em->flush($currentDirectory);

                //Update children in destinationDirectory
                $destinationDirectory = $em->getRepository('AppBundle:Drive')->find($destinationId);
                $newChild = [$sourceId];
                $currentChildren = $destinationDirectory->getChildren();
                $newChildren = array_merge($currentChildren, $newChild);
                $destinationDirectory->setHasChildren(true);
                $destinationDirectory->setChildren($newChildren);
                $em->flush($destinationDirectory);

            }

            //Filesystem block
            // $newTargetDirectory="";
            // array_shift($targetDirectory);
            // array_pop($destination);

            // foreach ($targetDirectory as $dir)
            // {
            //     $newTargetDirectory .=$dir . "/";
            // }

            $source = "foto_visa.jpg";
            $destination = "pics"; //"first folder/docs/personal/cv/luis";
            if ($destinationId == "0"){
                $destination = "";
            }
            
            $fileToMove = "foto_visa.jpg";
            

            
            $filesystem = new Filesystem();

            $origin = $driveDirectory . DIRECTORY_SEPARATOR . $source ;
            $target = $driveDirectory . DIRECTORY_SEPARATOR . $destination ;

            // //check if target directory exist, if not so create it
            // if (!$filesystem->exists($target . DIRECTORY_SEPARATOR . $folderToMove )){
            //     $filesystem->mkdir($target . DIRECTORY_SEPARATOR . $folderToMove );
            // }
            
            if (!is_file($origin))
            {
                throw new IOException("Selected item is not a regular file");
            }

            if (!is_dir($target))
            {
                throw new IOException("Target directory is not a directory");
            }
            
            // // so now target is ready to get mirror of the origin
            $target = $target . DIRECTORY_SEPARATOR . $fileToMove;
            $filesystem->copy($origin, $target, true);
            $filesystem->remove($origin);
            return new JsonResponse(['response' => 'Success on moving file'], 200, array('content-type'=>'text/json'));
        }
        catch(IOException $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('content-type' => 'text/json'));
        }
        catch(Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('content-type' => 'text/json'));
        }
    }

    /**
     * @Route("star/{idTargetItem}", name="star_item", requirements={"idTargetItem"="\d+"})
     */
    public function starItemAction($idTargetItem)
    {
        try
        {
            $em = $this->getDoctrine()->getManager();
            $item = $em->getRepository('AppBundle:Drive')->find($idTargetItem);
            $item->setStar(!$item->getStar());
            $em->flush();

            $fileDirectoryJSON = $em->getRepository('AppBundle:Drive')->getFileDirectoryJSON();    
            return $fileDirectoryJSON;
            // return  new JsonResponse(['response'=>'success'], 200, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
        catch(\Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
    }

    /**
     * @Route("trash/{idTargetItem}", name="trash_item", requirements={"idTargetItem"="\d+"})
     */
    public function trashItemAction($idTargetItem)
    {
        try
        {
            $em = $this->getDoctrine()->getManager();
            $item = $em->getRepository('AppBundle:Drive')->find($idTargetItem);
            $item->setDeleted(!$item->getDeleted());
            $em->flush();

            $fileDirectoryJSON = $em->getRepository('AppBundle:Drive')->getFileDirectoryJSON();    
            return $fileDirectoryJSON;
            // return  new JsonResponse(['response'=>'success'], 200, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
        catch(\Exception $e)
        {
            return new JsonResponse(['response'=>$e->getMessage()], 500, array('Access-Control-Allow-Origin' => '*','content-type' => 'text/json' )) ;
        }
    }
}
