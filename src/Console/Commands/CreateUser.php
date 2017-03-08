<?php
namespace Czim\CmsAuth\Console\Commands;

use Illuminate\Console\Command;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Czim\CmsCore\Support\Enums\Component;

class CreateUser extends Command
{

    protected $signature = 'cms:user:create {username?} {password?} 
                                {--firstName=} {--lastName=}
                                {--admin : Whether this user should have unrestricted access}';

    protected $description = 'Create CMS user';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email     = $this->argument('username') ?: $this->ask('Enter username (email address)');
        $firstName = $this->option('firstName') ?: $this->ask('Enter first name');
        $lastName  = $this->option('lastName') ?: $this->ask('Enter last name');

        if ( ! ($password = $this->argument('password'))) {
            // @codeCoverageIgnoreStart
            do {
                $password        = $this->secret('Enter password');
                $passwordConfirm = $this->secret('Confirm password');

                if ($password !== $passwordConfirm) {
                    $this->error('Passwords do not match. Try again.');
                    $password = null;
                }

            } while (empty($password));
            // @codeCoverageIgnoreEnd
        }

        $user = $this->getAuthenticator()->createUser($email, $password, [
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ]);

        if ( ! $user) {
            $this->error('Failed to create user!');
        }

        if ($this->option('admin')) {
            $user->is_superadmin = true;
            $user->save();
        }

        $this->info('User created.');
    }

    /**
     * @return AuthenticatorInterface
     */
    protected function getAuthenticator()
    {
        return app(Component::AUTH);
    }

}
