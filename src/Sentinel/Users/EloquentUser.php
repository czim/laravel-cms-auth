<?php
namespace Czim\CmsAuth\Sentinel\Users;

use Cartalyst\Sentinel\Users\EloquentUser as CartalystEloquentUser;
use Czim\CmsAuth\Sentinel\Activations\EloquentActivation;
use Czim\CmsAuth\Sentinel\Persistences\EloquentPersistence;
use Czim\CmsAuth\Sentinel\Reminders\EloquentReminder;
use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Throttling\EloquentThrottle;
use Czim\CmsAuth\Sentinel\CmsTablePrefixed;
use Czim\CmsCore\Contracts\Auth\UserInterface as CmsUserInterface;

/**
 * Class EloquentUser
 *
 * @property string  $email
 * @property string  $password
 * @property boolean $is_superadmin
 */
class EloquentUser extends CartalystEloquentUser implements CmsUserInterface
{
    use CmsTablePrefixed;

    /**
     * @var string
     */
    protected $table = 'users';

    protected $casts = [
        'is_superadmin' => 'boolean'
    ];

    protected $fillable = [
        'email',
        'password',
        'last_name',
        'first_name',
        'permissions',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'all_roles',
        'all_permissions',
    ];

    /**
     * The Eloquent roles model name.
     *
     * @var string
     */
    protected static $rolesModel = EloquentRole::class;

    /**
     * The Eloquent persistences model name.
     *
     * @var string
     */
    protected static $persistencesModel = EloquentPersistence::class;

    /**
     * The Eloquent activations model name.
     *
     * @var string
     */
    protected static $activationsModel = EloquentActivation::class;

    /**
     * The Eloquent reminders model name.
     *
     * @var string
     */
    protected static $remindersModel = EloquentReminder::class;

    /**
     * The Eloquent throttling model name.
     *
     * @var string
     */
    protected static $throttlingModel = EloquentThrottle::class;

    /**
     * Returns the roles relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(static::$rolesModel, $this->getCmsTablePrefix() . 'role_users', 'user_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * Returns the login name of the user.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * Returns whether the user is a top-level admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->is_superadmin;
    }

    /**
     * Returns whether the user has the given role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->roles()->where('slug', $role)->count() > 0;
    }


    /**
     * Returns whether the user has the given permission.
     *
     * @param string|string[] $permission
     * @param bool            $allowAny if true, allows if any is permitted
     * @return bool
     */
    public function can($permission, $allowAny = false)
    {
        if ($allowAny) {
            return $this->hasAnyAccess(
                is_array($permission) ? $permission : [ $permission ]
            );
        }

        return $this->hasAccess(
            is_array($permission) ? $permission : [ $permission ]
        );
    }

    /**
     * Returns whether the current user has any of the given permissions.
     *
     * @param string[] $permissions
     * @return bool
     */
    public function canAnyOf(array $permissions)
    {
        return $this->can($permissions, true);
    }

    /**
     * Returns all roles for the user.
     *
     * @return string[]
     */
    public function getAllRoles()
    {
        return $this->roles()->pluck('slug')->toArray();
    }

    /**
     * Returns all permissions for the user, whether by role or for the user itself.
     *
     * @return string[]
     */
    public function getAllPermissions()
    {
        return array_keys(array_filter($this->getPermissions()));
    }

    /**
     * Getter for all_roles.
     *
     * @return string[]
     */
    public function getAllRolesAttribute()
    {
        return $this->getAllRoles();
    }

    /**
     * Getter for all_permissions.
     *
     * @return string[]
     */
    public function getAllPermissionsAttribute()
    {
        return $this->getAllPermissions();
    }
}
