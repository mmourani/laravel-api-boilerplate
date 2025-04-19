<?php

// First load the Composer autoloader
require_once __DIR__.'/../vendor/autoload.php';

// Set up SQLite database file for testing
$databaseFile = '/tmp/test.sqlite';

try {
    // Create the database file if it doesn't exist
    if (! file_exists($databaseFile)) {
        echo "Creating SQLite database file at: {$databaseFile}".PHP_EOL;

        // Ensure directory exists
        if (! is_dir(dirname($databaseFile))) {
            mkdir(dirname($databaseFile), 0755, true);
        }

        // Create the file
        if (touch($databaseFile)) {
            echo 'SQLite database file created successfully.'.PHP_EOL;
        } else {
            echo 'WARNING: Failed to create SQLite database file.'.PHP_EOL;
        }
    } else {
        echo 'Using existing SQLite database file.'.PHP_EOL;
    }

    // Set permissions to allow read/write access
    if (file_exists($databaseFile)) {
        if (chmod($databaseFile, 0666)) {
            echo 'SQLite database file permissions set to 0666.'.PHP_EOL;
        } else {
            echo 'WARNING: Failed to set SQLite database file permissions.'.PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage().PHP_EOL;
}

// Override environment variables if needed
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $databaseFile;
