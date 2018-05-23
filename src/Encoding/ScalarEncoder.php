<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2018 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */

namespace Opulence\Serialization\Encoding;

use InvalidArgumentException;

/**
 * Defines a scalar encoder
 */
class ScalarEncoder implements IEncoder
{
    /**
     * @inheritdoc
     */
    public function decode($value, string $type)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                return (bool)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'int':
            case 'integer':
                return (int)$value;
            case 'string':
                return (string)$value;
            default:
                throw new InvalidArgumentException("Type $type is an invalid scalar");
        }
    }

    /**
     * @inheritdoc
     */
    public function encode($value)
    {
        if (!\is_scalar($value)) {
            throw new InvalidArgumentException('Value must be scalar');
        }

        return $value;
    }
}