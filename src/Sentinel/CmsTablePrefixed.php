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
        $tablePrefix = $this->getCmsTablePrefix();
        $tableName   = parent::getTable();

        if ($this->isPrefixed($tablePrefix, $tableName)) {
            return $tableName;
        }

        return $tablePrefix . $tableName;
    }

    /**
     * @param string $tablePrefix
     * @param string $tableName
     * @return bool
     */
    protected function isPrefixed(string $tablePrefix, string $tableName)
    {
        return (bool) preg_match("/^{$tablePrefix}/", $tableName);
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
