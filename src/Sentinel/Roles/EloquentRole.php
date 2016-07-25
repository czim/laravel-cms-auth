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

    /**
     * {@inheritdoc}
     */
    public function users()
    {
        return $this->belongsToMany(static::$usersModel, $this->getCmsTablePrefix() . 'role_users', 'role_id', 'user_id')
            ->withTimestamps();
    }
}
