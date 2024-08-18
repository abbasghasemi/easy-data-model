<?php

namespace AG\DataModel\Serialize;

interface Serializable
{
    function serialize(Serialized $serialized);

    function deserialize(Serialized $serialized);

}