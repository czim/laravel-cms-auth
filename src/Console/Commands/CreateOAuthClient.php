<?php
namespace Czim\CmsAuth\Console\Commands;

use Carbon\Carbon;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;

class CreateOAuthClient extends Command
{
    const OAUTH_CLIENTS_TABLE = 'oauth_clients';

    protected $signature = 'cms:oauth:client {name?} {id?} {secret?}';

    protected $description = 'Create new OAuth2 client (sets given or generates random client ID and secret)';

    /**
     * @var CoreInterface
     */
    protected $core;

    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * Execute the console command.
     *
     * @param CoreInterface   $core
     * @param DatabaseManager $db
     */
    public function handle(CoreInterface $core, DatabaseManager $db)
    {
        $this->core = $core;
        $this->db   = $db;

        $name         = $this->argument('name')   ?: $this->ask('Enter client name');
        $clientId     = $this->argument('id')     ?: $this->ask('Enter client ID (empty to generate)', str_random(32));
        $clientSecret = $this->argument('secret') ?: $this->ask('Enter client secret (empty to generate)', str_random(32));


        if ($this->checkClientExists($clientId)) {
            $this->error('Client with this ID already exists!');
            return;
        }

        if ($this->checkClientNameExists($name)) {
            if ( ! $this->confirm('Client with this name already exists. Use anyway?')) {
                $this->error('Aborted.');
                return;
            }
        }

        $this->createClient($clientId, $clientSecret, $name);


        if ( ! $this->checkClientExists($clientId)) {
            $this->error('Failed to create new client record');
            return;
        }

        $this->info('OAuth client generated:');
        $this->comment('   client_id     : ' . $clientId);
        $this->comment('   client_secret : ' . $clientSecret);
    }

    /**
     * Returns whether a client with the given ID exists.
     *
     * @param string $id
     * @return bool
     */
    protected function checkClientExists($id)
    {
        return (bool) $this->db->table($this->getTable())
            ->where('id', $id)
            ->count();
    }

    /**
     * Returns whether a client with the given name exists.
     *
     * @param string $name
     * @return bool
     */
    protected function checkClientNameExists($name)
    {
        return (bool) $this->db->table($this->getTable())
            ->where('name', $name)
            ->count();
    }

    /**
     * @param string $id
     * @param string $secret
     * @param string $name
     */
    protected function createClient($id, $secret, $name)
    {
        $this->db->table($this->getTable())
            ->insert([
                'id'         => $id,
                'secret'     => $secret,
                'name'       => $name,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * @return string
     */
    protected function getTable()
    {
        return $this->core->config('database.prefix') . static::OAUTH_CLIENTS_TABLE;
    }

}
