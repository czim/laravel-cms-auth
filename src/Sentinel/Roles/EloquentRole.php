<?php
namespace Czim\CmsAuth\Sentinel\Roles;

use Cartalyst\Sentinel\Roles\EloquentRole as CartalystEloquentRole;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Sentinel\CmsTablePrefixed;
use Czim\CmsCore\Contracts\Auth\RoleInterface;

class EloquentRole extends CartalystEloquentRole implements RoleInterface
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

    /**
     * Returns the key/slug for the role.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->getRoleSlug();
    }

    /**
     * Returns the display name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns all permissions for the role.
     *
     * @return string[]
     */
    public function getAllPermissions()
    {
        return array_keys(array_filter($this->getPermissions()));
    }
}
