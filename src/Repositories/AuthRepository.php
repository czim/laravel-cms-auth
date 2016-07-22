<?php
namespace Czim\CmsAuth\Repositories;

use Illuminate\Support\Collection;
use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Sentinel;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsCore\Contracts\Auth\AuthRepositoryInterface;
use Czim\CmsCore\Contracts\Auth\UserInterface;

class AuthRepository implements AuthRepositoryInterface
{

    /**
     * @var Sentinel
     */
    protected $sentinel;

    /**
     * @var UserInterface|EloquentUser
     */
    protected $userModel;

    /**
     * @var EloquentRole
     */
    protected $roleModel;


    public function __construct()
    {
        $this->sentinel  = app('sentinel');

        $userModelClass = $this->sentinel->getUserRepository()->getModel();
        $this->userModel = new $userModelClass;

        $roleModelClass = $this->sentinel->getRoleRepository()->getModel();
        $this->roleModel = new $roleModelClass;
    }


    /**
     * Returns all CMS users.
     *
     * @param bool $withAdmin include superadmins
     * @return array|Collection|UserInterface[]
     */
    public function getAllUsers($withAdmin = false)
    {
        $query = $this->userModel->query()
            ->orderBy('email');

        if ( ! $withAdmin) {
            $query->where('is_superadmin', false);
        }

        return $query->get();
    }

    /**
     * Returns all CMS users with the given role.
     *
     * @param string $role
     * @param bool   $withAdmin include superadmins
     * @return array|Collection|UserInterface[]
     */
    public function getUsersForRole($role, $withAdmin = false)
    {
        $query = $this->userModel->query()
            ->orderBy('email')
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('slug', $role);
            });

        if ( ! $withAdmin) {
            $query->where('is_superadmin', false);
        }

        return $query->get();
    }

    /**
     * Returns all roles known by the authenticator.
     *
     * @return string[]
     */
    public function getAllRoles()
    {
        $roles = $this->roleModel->orderBy('slug')->pluck('slug')->toArray();

        return $roles;
    }

    /**
     * Returns all permissions known by the authenticator.
     *
     * @todo This should be cached
     *
     * @return string[]
     */
    public function getAllPermissions()
    {
        $permissions = [];

        // Gather and combine all permissions for roles
        foreach ($this->roleModel->all() as $role) {
            $permissions = array_merge(
                $permissions,
                array_keys(array_filter($role->getPermissions()))
            );
        }

        // Gather permissions set specifically for users
        foreach ($this->userModel->all() as $user) {
            $permissions = array_merge(
                $permissions,
                array_keys(array_filter($user->getPermissions()))
            );
        }

        sort($permissions);

        return array_unique($permissions);
    }

    /**
     * Returns all permission keys for a given role.
     *
     * @param string $role
     * @return string[]
     */
    public function getAllPermissionsForRole($role)
    {
        /** @var EloquentRole $role */
        $role = $this->sentinel->findRoleBySlug($role);

        if ( ! $role) {
            return [];
        }

        $permissions = array_keys($role->getPermissions());
        sort($permissions);

        return $permissions;
    }

    /**
     * Returns all permission keys for a given user.
     *
     * @param string|UserInterface $user user: name or instance
     * @return string[]
     */
    public function getAllPermissionsForUser($user)
    {
        $user = $this->resolveUser($user);

        if ( ! $user) {
            return [];
        }

        $permissions = [];

        // Get all permissions for the roles this user belongs to
        foreach ($user->getRoles() as $role) {
            /** @var EloquentRole $role */
            $permissions = array_merge(
                $permissions,
                array_keys(array_filter($role->getPermissions()))
            );
        }

        // Add permissions set specifically for the user
        $permissions = array_merge(
            $permissions,
            array_keys(array_filter($user->getPermissions()))
        );

        sort($permissions);

        return array_unique($permissions);
    }

    /**
     * Resolves user to UserInterface, if possible
     *
     * @param mixed $user
     * @return UserInterface|EloquentUser|false
     */
    protected function resolveUser($user)
    {
        if ($user instanceof UserInterface) {
            return $user;
        }

        if (is_integer($user)) {
            $user = $this->sentinel->findById($user);
        } else {
            $user = $this->sentinel->findByCredentials([
                'email' => $user
            ]);
        }

        return $user ?: false;
    }

}