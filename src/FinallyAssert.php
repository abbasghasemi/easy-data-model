<?php

namespace AG\DataModel;

/**
 * It checks the assertion in the last step
 */
interface FinallyAssert
{
    /**
     * If not empty, an exception is thrown
     * @return ?string
     */
    function onAssert(): ?string;
}