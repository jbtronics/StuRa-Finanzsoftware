<?php


namespace App\Services\Upload;


use App\Entity\PaymentOrder;
use Assert\InvalidArgumentException;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

class PaymentOrderFileNamer implements NamerInterface
{

    public function name($object, PropertyMapping $mapping): string
    {
        if (!$object instanceof PaymentOrder) {
            throw new \InvalidArgumentException('$object must be an PaymentOrder!');
        }

        $file = $mapping->getFile($object);
        $originalName = $file->getClientOriginalName();
        $originalExtension = \strtolower(\pathinfo($originalName, PATHINFO_EXTENSION));
        $originalBasename = \pathinfo($originalName, PATHINFO_FILENAME);

        $slugger = new AsciiSlugger();

        $filename = mb_strimwidth($slugger->slug($object->getDepartment()->getName() ?? 'unknown'),0, 16);

        $filename .= '_' . mb_strimwidth($slugger->slug($object->getProjectName()), 0, 16);

        $filename .= '_' . date('ymd-His');


        $filename .= '_' . bin2hex(random_bytes(5));


        //Add original extension
        $filename .= '.' . $originalExtension;

        return $filename;
    }


}