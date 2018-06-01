<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace SymfonyExtBundle\EntityPropertiesExtractor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use AppBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


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
     * @var Container
     */
    public $container;

    /**
     * @var User
     */
    public $user;

    /** @var  TokenStorageInterface */
    private $tokenStorage;


    /**
     * EntityPropertiesExtractor constructor.
     * @param $serializeRule
     * @param Container $container
     * @param TokenStorageInterface $storage
     */
    public function __construct($serializeRule,Container $container,TokenStorageInterface $storage ) {

        $this->serializeRule = $serializeRule;
        $this->options = [
            'function_filter_var' => function($value, $field) {
                return $value;
            }
        ];
        $this->container = $container;
        $this->tokenStorage = $storage;
        $this->user = ($this->container->get('security.token_storage')->getToken()) ? $this->container->get('security.token_storage')->getToken()->getUser() : null;
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

        $this->user = ($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;

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

                    $eInner = (strstr($k,':user') and $this->user instanceof User) ? $e->{'get' . ucfirst(str_replace(':user','',$k))}($this->user) : $e->{'get' . ucfirst($k)}();

                    $result[str_replace(':user','',$k)] = $this->getNeed($eInner,$field);

                } else {
                    if (strstr($field,'/')) {
                        $fieldPart = explode('/',$field);
                        $obj = clone $e;
                        foreach ($fieldPart as $fp) {
                            if ($obj) {
                                $method = 'get' . ucfirst($fp);
                                $objNext = $obj;
                                $obj = (strstr($fp,':user') and $this->user instanceof User) ? $obj->{str_replace(':user','',$method)}($this->user) : $obj->{$method}();
                            } else {
                                break;
                            }
                        }

                        $result[str_replace(':user','',$fp)] = $this->normalizeResult($obj, str_replace(':user','',$fp), $objNext);


                    } else {
                        $aux = (strstr($field,':user') and $this->user instanceof User) ? $e->{'get' . ucfirst(str_replace(':user','',$field))}($this->user) : $e->{'get' . ucfirst($field)}();
                        $result[str_replace(':user','',$field)] = $this->normalizeResult($aux, str_replace(':user','',$field), $e);

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
