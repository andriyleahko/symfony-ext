<?php

namespace LA\SymfonyExtBundle\EntitySaver;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Class Saver
 * @package AppBundle\Entity
 */
class EntitySaver {

    /**
     *
     * @var Container
     */
    public $container;




    /**
     *
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * @param $entity
     * @param $data
     * @param bool $persist
     * @param bool $force
     * @return array
     * @throws \Exception
     */
    public function save($entity, $data, $persist = false, $force = true) {

        $notField = [];
        $saveFields = [];
        foreach ($data as $key => $value) {

            $method = $this->getMethodName($key);
            if (method_exists($entity,$method)) {
                $entity->{$method}($this->normalizeValue($method,$value));
                $saveFields[] = $key;
            } else {
                $notField[] = $key;
            }

        }

        if (!$force and count($notField)) {
            throw new \Exception('data has wrong fields: ' . implode(',',$notField), 400);
        }

        if ($persist) {
            $this->container->get('doctrine')->getManager()->persist($entity);
        }

        $this->container->get('doctrine')->getManager()->flush();

        return ['success' => true, 'wrongFields' => $notField, 'saveFields' => $saveFields, 'entity' => $entity];

    }

    /**
     * @param $entity
     * @param $data
     */
    public function fillField($entity, $data) {

        foreach ($data as $key => $value) {

            $method = $this->getMethodName($key);
            if (method_exists($entity,$method)) {
                $entity->{$method}($value);
            }

        }

    }

    /**
     * @param $field
     * @return string
     */
    private function getMethodName($field) {
        $partsName = explode('_',$field);
        $partsName = array_map('ucfirst',$partsName);
        return 'set' . implode('',$partsName);
    }

    /**
     *
     * @param string $method
     * @param string $value
     * @return \DateTime
     */
    private function normalizeValue($method,$value) {

        if (strstr(strtolower($method), 'date')) {
            return (!$value) ? $value : new \DateTime($value);
        }

        return $value;

    }



}