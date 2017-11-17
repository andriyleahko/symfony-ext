<?php


namespace LA\SymfonyExtBundle\Validator;



use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validation;




class Validator
{

    /**
     * @var array
     */
    private $alias = [
        'email' => '\Symfony\Component\Validator\Constraints\Email',
        'empty' => '\Symfony\Component\Validator\Constraints\NotBlank',
        'callback' => '\Symfony\Component\Validator\Constraints\Callback',
        'choice'  => '\Symfony\Component\Validator\Constraints\Choice',
        'file' => '\Symfony\Component\Validator\Constraints\File',
        'collection' => '\Symfony\Component\Validator\Constraints\Collection'
    ];

    private $defaultMessage = [
        'email' => '%field% must be email',
        'empty' => '%field% must be',
        'callback' => '%field% not correct',
        'choice'  => '%field% not correct',
        'collection' => '%field% not correct',

    ];



    /**
     * @var array
     */
    private $errors = [];





    /**
     * @param $rules
     * @param $data
     */
    public function validate($rules, $data) {


        $this->errors = [];

        $fields = [];
        $rulesForValidator = [];
        if (count($rules)) {
            foreach ($rules as $key => $value) {

                $fields[$key] = isset($data[$key]) ? $data[$key] : null;
                foreach ($value as $k => $v) {

                    if (is_array($v)) {
                        if (!isset($this->alias[$k])) {
                            continue;
                        }
                        if (!isset($v['message']) and isset($this->defaultMessage[$k])) {
                            $v['message'] = str_replace('%field%', $key, $this->defaultMessage[$k]);
                        }

                        $rulesForValidator[$key][] = ($k == 'callback') ? new $this->alias[$k]($v['function']) : new $this->alias[$k]($v);

                    } else {
                        if (!isset($this->alias[$v])) {
                            continue;
                        }
                        if (isset($this->defaultMessage[$v])) {
                            $option['message'] = isset($this->defaultMessage[$v]) ? str_replace('%field%', $key, $this->defaultMessage[$v]) : null;
                        }

                        $rulesForValidator[$key][] = (isset($option)) ? new $this->alias[$v]($option) : new $this->alias[$v]();

                    }

                }

            }
        }

        $validatorObject = Validation::createValidator();

        $constraint = new Collection($rulesForValidator);

        $violations = $validatorObject->validate($fields, $constraint);

        if (count($violations)) {

            foreach ($violations as $error) {
                $this->errors[] = $error->getMessage();
            }
        }

    }

    /**
     * @return array
     */
    public function getErrors() {
        return (count($this->errors)) ? implode(', ',$this->errors) : false;
    }





}

