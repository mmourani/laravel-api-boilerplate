<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class () extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('feature.task_creation_enabled', true);
        $this->migrator->add('feature.task_editing_enabled', true);
        $this->migrator->add('feature.task_deletion_enabled', false);
    }

    public function down(): void
    {
        $this->migrator->delete('feature.task_creation_enabled');
        $this->migrator->delete('feature.task_editing_enabled');
        $this->migrator->delete('feature.task_deletion_enabled');
    }
};
