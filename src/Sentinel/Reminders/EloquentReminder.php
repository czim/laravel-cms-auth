<?php
namespace Czim\CmsAuth\Sentinel\Reminders;

use Cartalyst\Sentinel\Reminders\EloquentReminder as CartalystEloquentReminder;
use Czim\CmsAuth\Sentinel\CmsTablePrefixed;

class EloquentReminder extends CartalystEloquentReminder
{
    use CmsTablePrefixed;

    protected $table = 'reminders';
}
