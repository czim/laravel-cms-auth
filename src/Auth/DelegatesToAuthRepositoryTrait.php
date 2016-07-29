<?php
namespace Czim\CmsAuth\Auth;

use Czim\CmsCore\Contracts\Auth\RoleInterface;
use Illuminate\Support\Collection;
use Czim\CmsCore\Contracts\Auth\UserInterface;

trait DelegatesToAuthRepositoryTrait
{

    /**
     * Returns a user by their ID, if it exists.
     *
     * @param mixed $id
     */
    public function getUserById($id)
    {
        return $this->repository->getUserById($id);
    }

    /**
     * Returns a user by their username/email, if it exists.
     *
     * @param string $username
     * @return UserInterface|null
     */
    public function getUserByUserName($username)
    {
        return $this->repository->getUserByUserName($username);
    }

    /**
     * Returns all CMS users.
     *
     * @param bool $withAdmin include superadmins
     * @return array|Collection|UserInterface[]
     */
    public function getAllUsers($withAdmin = false)
    {
        return $this->repository->getAllUsers($withAdmin);
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
        return $this->repository->getUsersForRole($role, $withAdmin);
    }

    /**
     * Returns all roles known by the authenticator.
     *
     * @return string[]
     */
    public function getAllRoles()
    {
        return $this->repository->getAllRoles();
    }

    /**
     * Returns all permissions known by the authenticator.
     *
     * @return string[]
     */
    public function getAllPermissions()
    {
        return $this->repository->getAllPermissions();
    }

    /**
     * Returns all permission keys for a given role.
     *
     * @param string $role
     * @return string[]
     */
    public function getAllPermissionsForRole($role)
    {
        return $this->repository->getAllPermissionsForRole($role);
    }

    /**
     * Returns all permission keys for a given user.
     *
     * @param string|UserInterface $user user: name or instance
     * @return string[]
     */
    public function getAllPermissionsForUser($user)
    {
        return $this->repository->getAllPermissionsForUser($user);
    }

    /**
     * @param string $role
     * @return RoleInterface
     */
    public function getRole($role)
    {
        return $this->repository->getRole($role);
    }

    /**
     * Returns whether a given role exists.
     *
     * @param string $role
     * @return bool
     */
    public function roleExists($role)
    {
        return $this->repository->roleExists($role);
    }

    /**
     * Returns whether a role is currently used at all.
     *
     * @param string $role
     * @return bool
     */
    public function roleInUse($role)
    {
        return $this->repository->roleInUse($role);
    }

    /**
     * Returns whether a permission with the given (exact) name is currently used at all.
     *
     * Note that this CANNOT be used to look up permissions by wildcard (something.*).
     *
     * @param $permission
     * @return bool
     */
    public function permissionInUse($permission)
    {
        return $this->repository->permissionInUse($permission);
    }

}
