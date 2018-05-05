<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace SymfonyExtBundle\EntityPropertiesExtractor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;


/**
 * Description of JsonString
 *
 * @author andriy
 */
class EntityPropertiesExtractor {

    /**
     *
     * @var
     */
    public $serializeRule;

    /**
     * @var
     */
    public $options;


    /**
     *
     * @param $serializeRule
     */
    public function __construct($serializeRule ) {

        $this->serializeRule = $serializeRule;
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
     * @param object $entity
     * @param string | array $fields
     * @return array|mixed|null
     */
    public function getNeed($entity, $fields) {

        $fields = (is_array($fields)) ? $fields : $this->serializeRule[$fields];
        $resultAll = [];

        $multi = true;

        if (!$entity) {
            return [];
        }

        if (!$fields) {
            return [];
        }

        if (!is_array($entity) and !$entity instanceof ArrayCollection and !$entity instanceof PersistentCollection) {
            $multi = false;
            $entity = [$entity];
        }

        foreach ($entity as $e) {

            foreach ($fields as $k => $field) {

                if (is_array($field)) {

                    $eInner = $e->{'get' . ucfirst($k)}();

                    $result[$k] = $this->getNeed($eInner,$field);

                } else {
                    if (strstr($field,'/')) {
                        $fieldPart = explode('/',$field);
                        $obj = clone $e;
                        foreach ($fieldPart as $fp) {
                            if ($obj) {
                                $method = 'get' . ucfirst($fp);
                                $objNext = $obj;
                                $obj = $obj->{$method}();
                            } else {
                                break;
                            }
                        }

                        $result[$fp] = $this->normalizeResult($obj, $fp, $objNext);


                    } else {
                        $result[$field] = $this->normalizeResult($e->{'get' . ucfirst($field)}(), $field, $e);

                    }
                }

            }
            $resultAll[] = $result;
        }

        return (!$multi) ? $resultAll[0] : $resultAll;



    }

    /**
     * @param $value
     * @param $field
     * @param $obj
     * @return mixed
     */
    private function normalizeResult($value, $field, $obj) {

        return call_user_func_array($this->options['function_filter_var'],[$value, $field, $obj]);


    }







}
