<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2018 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */

namespace Opulence\Serialization\Encoding;

/**
 * Defines the context to use while encoding/decoding values
 */
class EncodingContext
{
    /** @var array A hash table of object hashes that have already been encoded */
    private $circularReferenceHashTable = [];

    /**
     * Checks if the input object indicates that we've hit a circular reference
     *
     * @param \object $object The object to check
     * @return bool True if the input object indicates a circular reference, otherwise false
     */
    public function isCircularReference(object $object): bool
    {
        $objectHashId = spl_object_hash($object);

        if (isset($this->circularReferenceHashTable[$objectHashId])) {
            return true;
        }

        $this->circularReferenceHashTable[$objectHashId] = true;

        return false;
    }
}