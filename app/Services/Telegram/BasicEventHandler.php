<?php

declare(strict_types=1);

namespace App\Services\Telegram;
// Simple example bot.
// PHP 8.2.4+ is required.

// Run via CLI (recommended: `screen php bot.php`) or via web.

// To reduce RAM usage, follow these instructions: https://docs.madelineproto.xyz/docs/DATABASE.html

use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Plugin\RestartPlugin;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\SimpleEventHandler;

 

class BasicEventHandler extends SimpleEventHandler
{
    // !!! Change this to your username !!!
    public const ADMIN = "@boumedyenDZ";

    /**
     * Get peer(s) where to report errors.
     */
    public function getReportPeers()
    {
        return [self::ADMIN];
    }

    /**
     * Returns a set of plugins to activate.
     *
     * See here for more info on plugins: https://docs.madelineproto.xyz/docs/PLUGINS.html
     */
    public static function getPlugins(): array
    {
        return [
            // Offers a /restart command to admins that can be used to restart the bot, applying changes.
            // Make sure to run in a bash while loop when running via CLI to allow self-restarts.
            RestartPlugin::class,
        ];
    }

    /**
     * Handle incoming updates from users, chats and channels.
     */
    #[Handler]
    public function handleMessage(Incoming&Message $message): void
    {
        info('Inside  the handleMessage method');

        info(json_encode($message));
      
        // Code that uses $message...
        // See the following pages for more examples and documentation:
        // - https://github.com/danog/MadelineProto/blob/v8/examples/bot.php
        // - https://docs.madelineproto.xyz/docs/UPDATES.html
        // - https://docs.madelineproto.xyz/docs/FILTERS.html
        // - https://docs.madelineproto.xyz/
    }
}

