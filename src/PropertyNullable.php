<?php

namespace AG\DataModel;

/**
 * Maybe you want to prevent the variable you defined as nullable
 * from becoming null in certain situations.
 */
interface PropertyNullable
{
    /**
     * Return false if the null value is not acceptable
     * @param string $propertyName
     * @return bool
     */
    function onNullable(string $propertyName): bool;
}