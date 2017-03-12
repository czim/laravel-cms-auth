<?php
namespace Czim\CmsAuth\Api\OAuth\Storage;

use Czim\OAuth2Server\Storage\AbstractFluentAdapter as LucaDegasperiAbstractFluentAdapter;

abstract class AbstractFluentAdapter extends LucaDegasperiAbstractFluentAdapter
{

    /**
     * Prefixes table name with configured prefix for CMS.
     *
     * @param string $name
     * @return string
     */
    protected function prefixTable($name)
    {
        return config('cms-core.database.prefix', '') . $name;
    }

}
