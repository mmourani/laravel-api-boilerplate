<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class FeatureSettings extends Settings
{
    public bool $task_creation_enabled;
    public bool $task_editing_enabled;
    public bool $task_deletion_enabled;

    public static function group(): string
    {
        return 'feature';
    }
}
