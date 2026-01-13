<?php declare(strict_types=1);

namespace OfxParser\Entities\Investment\Transaction;

class Reinvest extends Income
{
    /**
     * @var string
     */
    public $nodeName = 'REINVEST';
}
