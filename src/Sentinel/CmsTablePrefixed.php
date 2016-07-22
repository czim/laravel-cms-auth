<?php
namespace Czim\CmsAuth\Sentinel;

trait CmsTablePrefixed
{

    /**
     * Override to add configured database prefix
     *
     * {@inheritdoc}
     */
    public function getTable()
    {
        return $this->getCmsTablePrefix() . parent::getTable();
    }

    /**
     * Override to force the database connection
     *
     * {@inheritdoc}
     */
    public function getConnectionName()
    {
        return $this->getCmsDatabaseConnection() ?: $this->connection;
    }

    /**
     * @return string
     */
    protected function getCmsTablePrefix()
    {
        return config('cms-core.database.prefix', '');
    }

    /**
     * @return string|null
     */
    protected function getCmsDatabaseConnection()
    {
        return config('cms-core.database.driver');
    }

}
