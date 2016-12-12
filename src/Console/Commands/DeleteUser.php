<?php
namespace Czim\CmsAuth\Console\Commands;

use Illuminate\Console\Command;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Czim\CmsCore\Support\Enums\Component;

class DeleteUser extends Command
{

    protected $signature = 'cms:user:delete {username}';

    protected $description = 'Delete CMS user';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');

        if ( ! $this->getAuthenticator()->deleteUser($username)) {
            $this->error("Failed to find or delete user '{$username}'");
        }

        $this->info('User deleted.');
    }

    /**
     * @return AuthenticatorInterface
     */
    protected function getAuthenticator()
    {
        return app(Component::AUTH);
    }

}
