<?php

namespace AG\DataModel;

/**
 * All features of the Safe class are applied to the input before delivering the data to you
 * @see Safe
 */
interface PropertyValueConvertor
{
    /**
     * This function gives you the input and prompts you for the new value.
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return mixed
     */
    function onConvert(string $propertyName, mixed $propertyValue): mixed;

}