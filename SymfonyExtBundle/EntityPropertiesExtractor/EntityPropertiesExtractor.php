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
     * @param $options
     * @param $serializeRule
     */
    public function __construct($options, $serializeRule ) {
        $this->options = $options;
        $this->serializeRule = $serializeRule;
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
            return null;
        }

        if (!$fields) {
            return null;
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
                                $obj = $obj->{$method}();
                            } else {
                                break;
                            }
                        }

                        $result[$fp] = $this->normalizeResult($obj, $fp);


                    } else {
                        $result[$field] = $this->normalizeResult($e->{'get' . ucfirst($field)}(), $field);

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
     * @return mixed
     */
    private function normalizeResult($value, $field) {

        return call_user_func_array($this->options['function_filter_var'],[$value, $field]);


    }







}
