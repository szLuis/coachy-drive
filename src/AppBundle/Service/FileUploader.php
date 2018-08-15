<?php
namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDirectory;

    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file)
    {
        // $fileName = md5(uniqid()).'.'.$file->guessExtension();
        $fileName = $file->getClientOriginalName();

        //$file->move($this->getTargetDirectory(), $fileName);
        $file->move($this->getTargetDirectory(), $fileName );

        return $fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    public function setTargetDirectory($targetDirectory)
    {
        //se concatena el directorio establecido en el servicio 
        //con el directorio actual donde se sube el archvio
        $this->targetDirectory = $this->getTargetDirectory() . '/' . $targetDirectory;
    }
}