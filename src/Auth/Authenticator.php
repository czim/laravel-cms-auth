<?php
namespace Czim\CmsAuth\Auth;

use Cartalyst\Sentinel\Users\UserInterface as CartalystUserInterface;
use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Sentinel;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Czim\CmsCore\Contracts\Auth\AuthRepositoryInterface;
use Czim\CmsCore\Contracts\Auth\RoleInterface;
use Czim\CmsCore\Contracts\Auth\UserInterface;
use Czim\CmsCore\Events\Auth\CmsRolesChanged;
use Czim\CmsCore\Events\Auth\CmsUserLoggedIn;
use Czim\CmsCore\Events\Auth\CmsUserLoggedOut;
use Czim\CmsCore\Events\Auth\CmsUserPermissionsChanged;

class Authenticator implements AuthenticatorInterface
{
    use AuthRoutingTrait,
        AuthApiRoutingTrait,
        DelegatesToAuthRepositoryTrait;

    /**
     * @var Sentinel
     */
    protected $sentinel;

    /**
     * @var AuthRepositoryInterface
     */
    protected $repository;


    /**
     * @param AuthRepositoryInterface $repository
     */
    public function __construct(AuthRepositoryInterface $repository)
    {
        $this->sentinel   = app('sentinel');
        $this->repository = $repository;
    }

    /**
     * Returns whether any user is currently logged in.
     *
     * @return bool
     */
    public function check()
    {
        return (bool) $this->sentinel->check();
    }

    /**
     * Returns currently logged in user, if any.
     *
     * @return UserInterface|false
     */
    public function user()
    {
        if ( ! $this->sentinel->check()) {
            return false;
        }

        return $this->sentinel->getUser();
    }

    /**
     * Returns whether currently logged in user, if any, is (super) admin.
     *
     * @return bool
     */
    public function admin()
    {
        if ( ! ($user = $this->user())) {
            return false;
        }

        return $user->isAdmin();
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool   $remember
     * @return bool
     */
    public function login($username, $password, $remember = true)
    {
        $user = $this->sentinel->authenticate(
            [
                'email'    => $username,
                'password' => $password,
            ],
            $remember
        );

        if ( ! ($user instanceof CartalystUserInterface)) {
            return false;
        }

        event( new CmsUserLoggedIn($user) );
        return true;
    }

    /**
     * Performs stateless login.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function stateless($username, $password)
    {
        $user = $this->sentinel->stateless(
            [
                'email'    => $username,
                'password' => $password,
            ]
        );

        if ( ! ($user instanceof CartalystUserInterface)) {
            return false;
        }

        // This is still a login, and while we don't want persistence, we do want it to be recorded
        $this->sentinel->getUserRepository()->recordLogin($user);

        event( new CmsUserLoggedIn($user, true) );
        return true;
    }

    /**
     * Forces a user to be logged in without credentials verification.
     *
     * @param UserInterface|CartalystUserInterface $user
     * @param bool                                 $remember
     * @return bool
     */
    public function forceUser(UserInterface $user, $remember = true)
    {
        $user = $this->sentinel->authenticate($user, $remember);

        if ( ! ($user instanceof CartalystUserInterface)) {
            return false;
        }

        event( new CmsUserLoggedIn($user, false, true) );
        return true;
    }

    /**
     * Forces a user to be logged in without credentials verification,
     * without persistence, and without marking it as a login.
     *
     * @param UserInterface|CartalystUserInterface $user
     * @return bool
     */
    public function forceUserStateless(UserInterface $user)
    {
        $user = $this->sentinel->authenticate($user, false, false);

        if ( ! ($user instanceof CartalystUserInterface)) {
            return false;
        }

        event( new CmsUserLoggedIn($user, true, true) );
        return true;
    }

    /**
     * @return bool
     */
    public function logout()
    {
        $user = $this->sentinel->getUser();

        if ( ! $user || ! $this->sentinel->logout($user, true)) {
            return false;
        }

        event( new CmsUserLoggedOut($user) );
        return true;
    }

    /**
     * Returns whether the current user has the given role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        if ( ! ($user = $this->user())) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Returns whether the current user has the given permission(s).
     *
     * @param string|string $permission
     * @param bool            $allowAny     if true, allows if any is permitted
     * @return bool
     */
    public function can($permission, $allowAny = false)
    {
        if ( ! ($user = $this->user())) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->can($permission, $allowAny);
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
     * Assigns one or several roles to a user.
     *
     * @param string|string[] $role
     * @param UserInterface   $user
     * @return bool
     */
    public function assign($role, UserInterface $user)
    {
        $roles = is_array($role) ? $role : [ $role ];

        $count = 0;

        foreach ($roles as $singleRole) {
            $count += $this->assignSingleRole($singleRole, $user) ? 1 : 0;
        }

        if ($count !== count($roles)) {
            return false;
        }

        $this->fireUserPermissionChangeEvent($user);
        return true;
    }

    /**
     * @param string                     $role
     * @param UserInterface|EloquentUser $user
     * @return bool
     */
    protected function assignSingleRole($role, UserInterface $user)
    {
        if ($user->hasRole($role)) {
            return true;
        }

        /** @var EloquentRole $roleModel */
        if ( ! ($roleModel = $this->sentinel->findRoleBySlug($role))) {
            return false;
        }

        $roleModel->users()->attach($user->id);

        return true;
    }

    /**
     * Removes one or several roles from a user.
     *
     * @param string|string[] $role
     * @param UserInterface   $user
     * @return bool
     */
    public function unassign($role, UserInterface $user)
    {
        $roles = is_array($role) ? $role : [ $role ];

        $count = 0;

        foreach ($roles as $singleRole) {
            $count += $this->unassignSingleRole($singleRole, $user) ? 1 : 0;
        }

        if ($count !== count($roles)) {
            return false;
        }

        $this->fireUserPermissionChangeEvent($user);
        return false;
    }

    /**
     * @param string                     $role
     * @param UserInterface|EloquentUser $user
     * @return bool
     */
    protected function unassignSingleRole($role, UserInterface $user)
    {
        if ( ! $user->hasRole($role)) {
            return true;
        }

        /** @var EloquentRole $roleModel */
        if ( ! ($roleModel = $this->sentinel->findRoleBySlug($role))) {
            return false;
        }

        $roleModel->users()->detach($user->id);

        return true;
    }

    /**
     * @param string                     $permission
     * @param UserInterface|EloquentUser $user
     * @return bool
     */
    public function grant($permission, UserInterface $user)
    {
        $user->addPermission($permission);

        if ( ! $user->save()) {
            return false;
        }

        $this->fireUserPermissionChangeEvent($user);
        return true;
    }

    /**
     * @param string[]                   $permissions
     * @param UserInterface|EloquentUser $user
     * @return bool
     */
    public function grantMany(array $permissions, UserInterface $user)
    {
        foreach ($permissions as $permission) {
            $user->addPermission($permission);
        }

        if ( ! $user->save()) {
            return false;
        }

        $this->fireUserPermissionChangeEvent($user);
        return true;
    }

    /**
     * @param string                     $permission
     * @param UserInterface|EloquentUser $user
     * @return bool
     */
    public function revoke($permission, UserInterface $user)
    {
        $user->removePermission($permission);

        if ( ! $user->save()) {
            return false;
        }

        $this->fireUserPermissionChangeEvent($user);
        return true;
    }

    /**
     * @param string[]                   $permissions
     * @param UserInterface|EloquentUser $user
     * @return bool
     */
    public function revokeMany(array $permissions, UserInterface $user)
    {
        foreach ($permissions as $permission) {
            $user->removePermission($permission);
        }

        if ( ! $user->save()) {
            return false;
        }

        $this->fireUserPermissionChangeEvent($user);
        return true;
    }

    /**
     * Creates a role.
     *
     * @param string      $role
     * @param string|null $name
     * @return bool
     */
    public function createRole($role, $name = null)
    {
        if ($this->sentinel->findRoleBySlug($role)) {
            return false;
        }

        $this->sentinel->getRoleRepository()->createModel()
             ->create([
                'name' => $name ?: $this->convertRoleSlugToName($role),
                'slug' => $role,
             ]);

        $this->fireRoleChangeEvent();
        return true;
    }

    /**
     * Removes a role.
     *
     * @param string $role
     * @return bool
     */
    public function removeRole($role)
    {
        if ( ! ($roleModel = $this->sentinel->findRoleBySlug($role))) {
            return false;
        }

        /** @var EloquentRole $roleModel */
        $roleModel->delete();

        $this->fireRoleChangeEvent();
        return true;
    }

    /**
     * Grants one or more permissions to a role.
     *
     * @param string|string[] $permission
     * @param string          $role
     * @return bool
     */
    public function grantToRole($permission, $role)
    {
        /** @var EloquentRole $roleModel */
        if ( ! ($roleModel = $this->sentinel->findRoleBySlug($role))) {
            return false;
        }

        $permissions = is_array($permission) ? $permission : [ $permission ];

        foreach ($permissions as $singlePermission) {
            $this->grantSinglePermissionToRole($singlePermission, $roleModel);
        }

        if ( ! $roleModel->save()) {
            return false;
        }

        $this->fireRoleChangeEvent();
        return true;
    }

    /**
     * @param string       $permission
     * @param EloquentRole $role
     */
    protected function grantSinglePermissionToRole($permission, EloquentRole $role)
    {
        $role->addPermission($permission);
    }

    /**
     * Revokes one or more permissions of a role.
     *
     * @param string|string[] $permission
     * @param string          $role
     * @return bool
     */
    public function revokeFromRole($permission, $role)
    {
        /** @var EloquentRole $roleModel */
        if ( ! ($roleModel = $this->sentinel->findRoleBySlug($role))) {
            return false;
        }

        $permissions = is_array($permission) ? $permission : [ $permission ];

        foreach ($permissions as $singlePermission) {
            $this->revokeSinglePermissionFromRole($singlePermission, $roleModel);
        }

        if ( ! $roleModel->save()) {
            return false;
        }

        $this->fireRoleChangeEvent();
        return true;
    }

    /**
     * @param string       $permission
     * @param EloquentRole $role
     */
    protected function revokeSinglePermissionFromRole($permission, EloquentRole $role)
    {
        $role->removePermission($permission);
    }


    // ------------------------------------------------------------------------------
    //      Events
    // ------------------------------------------------------------------------------

    /**
     * Fires event indicating permissions have changed for a user.
     *
     * @param UserInterface $user
     * @return $this
     */
    protected function fireUserPermissionChangeEvent(UserInterface $user)
    {
        event( new CmsUserPermissionsChanged($user) );

        return $this;
    }

    /**
     * Fires event indicating roles have changed.
     *
     * @return $this
     */
    protected function fireRoleChangeEvent()
    {
        event( new CmsRolesChanged() );

        return $this;
    }

    /**
     * Create new CMS user.
     *
     * @param string $username
     * @param string $password
     * @param array  $data
     * @return UserInterface
     * @throws \Exception
     */
    public function createUser($username, $password, array $data = [])
    {
        /** @var UserInterface|EloquentUser $user */
        $user = $this->sentinel->registerAndActivate([
            'email'    => $username,
            'password' => $password,
        ]);

        if ( ! $user) {
            throw new \Exception("Failed to create user '{$username}'");
        }

        $user->update($data);

        return $user;
    }

    /**
     * Removes a user from the CMS.
     *
     * @param $username
     * @return bool
     */
    public function deleteUser($username)
    {
        /** @var UserInterface|EloquentUser $user */
        $user = $this->sentinel->findByCredentials([ 'email' => $username ]);

        if ( ! $user) {
            return false;
        }

        // The super admin may not be deleted.
        if ($user->isAdmin()) {
            return false;
        }

        return (bool) $user->delete();
    }

    /**
     * Sets a new password for an existing CMS user.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function updatePassword($username, $password)
    {
        /** @var UserInterface|EloquentUser $user */
        $user = $this->sentinel->findByCredentials([ 'email' => $username ]);

        if ( ! $user) {
            return false;
        }

        return $this->sentinel->update($user, [ 'password' => $password ]) instanceof UserInterface;
    }

    /**
     * Updates a CMS user's (extra) data.
     *
     * @param string $username
     * @param array $data
     * @return bool
     */
    public function updateUser($username, array $data)
    {
        /** @var UserInterface|EloquentUser $user */
        $user = $this->sentinel->findByCredentials([ 'email' => $username ]);

        if ( ! $user) {
            return false;
        }

        $user->fill($data);

        return $user->save();
    }

    /**
     * Converts a role slug to a displayable name
     *
     * @param string $slug
     * @return string
     */
    protected function convertRoleSlugToName($slug)
    {
        return ucfirst(str_replace('.', ' ', $slug));
    }

}
