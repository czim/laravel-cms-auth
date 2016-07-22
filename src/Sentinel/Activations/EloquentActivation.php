<?php
namespace Czim\CmsAuth\Sentinel\Activations;

use Cartalyst\Sentinel\Activations\EloquentActivation as CartalystEloquentActivation;
use Czim\CmsAuth\Sentinel\CmsTablePrefixed;

class EloquentActivation extends CartalystEloquentActivation
{
    use CmsTablePrefixed;

    protected $table = 'activations';
}
