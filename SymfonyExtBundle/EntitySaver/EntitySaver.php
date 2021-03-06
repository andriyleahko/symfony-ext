<?php

namespace SymfonyExtBundle\EntitySaver;

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
     * @var
     */
    public $options;


    /**
     * EntitySaver constructor.
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
        $this->options = [
            'function_filter_var' => function($value, $field) {
                return $value;
            }
        ];
    }

    /**
     * @param $options
     * @return $this
     */
    public function setOption($options) {
        $this->options = $options;
        return $this;
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
                $entity->{$method}($this->normalizeValue($method,$value,$entity));
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
     * @param $method
     * @param $value
     * @param $obj
     * @return mixed
     */
    private function normalizeValue($method,$value,$obj) {

        return call_user_func_array($this->options['function_filter_var'],[$value, $method,$obj]);


    }



}