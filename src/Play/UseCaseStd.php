<?php

// not a dci Context

namespace Play;

class UseCaseStd
{
    const baseModelClass = '\Actors\Model';

    use SelectSceneCallableTrait;
    use PerformOnStageTrait;
}
