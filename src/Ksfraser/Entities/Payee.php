<?php declare(strict_types=1);

namespace OfxParser\Entities;

class Payee extends AbstractEntity
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $address;

    /**
     * @var string
     */
    public $address1;

    /**
     * @var string
     */
    public $address2;

    /**
     * @var string
     */
    public $address3;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $state;

    /**
     * @var string
     */
    public $postalCode;

    /**
     * @var string
     */
    public $country;

    /**
     * @var string
     */
    public $phone;
}
