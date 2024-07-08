<?php

namespace AG\DataModel;

interface AllowsPropertyNull
{
    /**
     * @param string $propertyName
     * @return bool
     */
    function onAllowsNull(string $propertyName): bool;

}