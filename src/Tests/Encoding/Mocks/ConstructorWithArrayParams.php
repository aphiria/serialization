<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2018 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */

namespace Opulence\Serialization\Tests\Encoding\Mocks;

/**
 * Mocks a class with array constructor params
 */
class ConstructorWithArrayParams
{
    private $foo;

    public function __construct(array $foo)
    {
        $this->foo = $foo;
    }

    public function getFoo(): array
    {
        return $this->foo;
    }
}