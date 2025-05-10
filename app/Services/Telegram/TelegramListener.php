<?php

/**
 * Telegram listener daemon
 * 
 * This script runs the MadelineProto event handler that listens for new Telegram messages
 */

// Set up autoloading for Laravel and MadelineProto
require __DIR__ . '/../vendor/autoload.php';

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Database\Mysql;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use App\Services\Telegram\UpdateHandler;
use danog\MadelineProto\Settings\Logger as SettingsLogger;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Define the session path
$session = storage_path('app/madeline/session.madeline');

// Set up logging
Logger::constructorFromSettings(new SettingsLogger);
Logger::log("Starting Telegram listener...", Logger::NOTICE);

try {
    // Create settings
    $settings = new Settings;

    // Configure database connection for session storage
    // Using MySQL/MariaDB improves reliability for long-running processes
    $dbConfig = config('database.connections.mysql');
    $settings->setDb((new Mysql)
            ->setUri("mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}")
            ->setUsername($dbConfig['username'])
            ->setPassword($dbConfig['password'])
    );

    // Configure API settings
    $settings->getConnection()
        ->setMinSleep(30) // Minimum sleep time between requests (in ms)
        ->setMaxSleep(300); // Maximum sleep time between requests (in ms)

    // Set app info
    $settings->getAppInfo()
        ->setApiId(env('TELEGRAM_API_ID'))
        ->setApiHash(env('TELEGRAM_API_HASH'));

    // Initialize event handler
    $handler = new UpdateHandler($session, $settings);

    // Start the event handler
    $handler->start();

    // Run the event handler until it's stopped
    $handler->loop();
} catch (\Throwable $e) {
    Logger::log("Error in Telegram listener: {$e->getMessage()}", Logger::ERROR);
    echo "Error: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . PHP_EOL;
}
