<?php
/**
 * MetadataCollection.php
 *
 * @since 05/11/16
 * @author gseidel
 */

namespace Enhavo\Bundle\TranslationBundle\Metadata;


use Enhavo\Bundle\ArticleBundle\Entity\Article;
use Enhavo\Bundle\GridBundle\Entity\Text;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\Proxy\Proxy;

class MetadataCollection
{
    const CACHE_FILE_NAME = 'translation_metadata_array.json';

    /**
     * @var array
     */
    private $metadataArray;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $metadataCache = [];

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param $entity
     * @throws \Exception
     * @return Metadata|null
     */
    public function getMetadata($entity)
    {
        if(is_string($entity)) {
            $className = $entity;
        } else if(is_object($entity)) {
            if($entity instanceof Proxy) {
                $className = get_parent_class($entity);
            } else {
                $className = get_class($entity);
            }
        } else {
            throw new \Exception('entity should be type of string or an object');
        }

        if(array_key_exists($className, $this->metadataCache)) {
            return  $this->metadataCache[$className];
        }

        $metadata = $this->createMetadata($className);
        $this->metadataCache[$className] = $metadata;
        return $metadata;
    }

    private function createMetadata($className, Metadata $metadata = null)
    {
        $parentClass = get_parent_class($className);
        if($parentClass) {
            $parentMetadata = $this->createMetadata($parentClass, $metadata);
            if($parentMetadata !== null) {
                $metadata = $parentMetadata;
            }
        }

        $metadataArray = $this->getMetadataArray();
        if(array_key_exists($className, $metadataArray)) {
            if($metadata === null) {
                $metadata = new Metadata();
            }
            $metadata->setClass($className);
            if(isset($metadataArray[$className]['properties']) && is_array($metadataArray[$className]['properties'])) {
                $properties = $metadataArray[$className]['properties'];
                foreach($properties as $name => $propertyData) {
                    $property = new Property();
                    $property->setName($name);
                    $property->setOptions($propertyData);
                    if(isset($propertyData['strategy'])) {
                        $property->setStrategy($propertyData['strategy']);
                    } else {
                        $property->setStrategy('');
                    }
                    $metadata->addProperty($property);
                }
            }
            return $metadata;
        }
        return null;
    }

    private function getMetadataArray()
    {
        if($this->metadataArray === null) {
            $cacheFilePath = sprintf('%s/%s', $this->kernel->getCacheDir(), MetadataCollection::CACHE_FILE_NAME);
            $content = file_get_contents($cacheFilePath);
            $this->metadataArray = json_decode($content, true);
        }
        return $this->metadataArray;
    }
}