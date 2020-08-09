<?php


namespace App\Services\Upload;


use App\Entity\PaymentOrder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\FileSystemStorage;

/**
 * We need to extend the original filesystemstorage as we have stored our payment_order files privately and want to use
 * the normal interface to access them via URI...
 * @package App\Services\Upload
 */
class ExtendedFileSystemStorage extends FileSystemStorage
{
    private $router;

    public function __construct(PropertyMappingFactory $factory, UrlGeneratorInterface $router)
    {
        parent::__construct($factory);
        $this->router = $router;
    }

    public function resolveUri($obj, ?string $fieldName = null, ?string $className = null): ?string
    {
        $tmp = parent::resolveUri($obj, $fieldName, $className);

        if ($tmp !== null && $obj instanceof PaymentOrder) {
            if ($fieldName === 'printed_form' || $fieldName === 'printed_form_file') {
                return $this->router->generate('file_payment_order_form', ['id' => $obj->getId()]);
            }

            if ($fieldName === 'references' || $fieldName === 'references_file') {
                return $this->router->generate('file_payment_order_references', ['id' => $obj->getId()]);
            }
        }


        return $tmp;
    }
}