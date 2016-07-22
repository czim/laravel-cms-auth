<?php
namespace Czim\CmsAuth\Sentinel\Roles;

use Cartalyst\Sentinel\Roles\EloquentRole as CartalystEloquentRole;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Sentinel\CmsTablePrefixed;

class EloquentRole extends CartalystEloquentRole
{
    use CmsTablePrefixed;

    protected $table = 'roles';

    protected static $usersModel = EloquentUser::class;

}
