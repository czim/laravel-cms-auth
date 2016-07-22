<?php

namespace Czim\CmsAuth\Sentinel\Throttling;

use Cartalyst\Sentinel\Throttling\EloquentThrottle as CartalystEloquentThrottle;
use Czim\CmsAuth\Sentinel\CmsTablePrefixed;

class EloquentThrottle extends CartalystEloquentThrottle
{
    use CmsTablePrefixed;

    protected $table = 'throttle';
}
