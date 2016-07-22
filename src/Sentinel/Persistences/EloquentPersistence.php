<?php
namespace Czim\CmsAuth\Sentinel\Persistences;

use Cartalyst\Sentinel\Persistences\EloquentPersistence as CartalystEloquentPersistence;
use Czim\CmsAuth\Sentinel\CmsTablePrefixed;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;

class EloquentPersistence extends CartalystEloquentPersistence
{
    use CmsTablePrefixed;

    protected $table = 'persistences';

    protected static $usersModel = EloquentUser::class;
}
