<?php

declare(strict_types=1);

namespace App\Services\Telegram;

// Simple example bot.
// PHP 8.2.4+ is required.

// Run via CLI (recommended: `screen php bot.php`) or via web.

// To reduce RAM usage, follow these instructions: https://docs.madelineproto.xyz/docs/DATABASE.html

use App\Models\TelegramChannel;
use App\Models\TelegramMessage;
use App\Models\TelegramMessageMedia;
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\ChannelMessage;
use danog\MadelineProto\EventHandler\Plugin\RestartPlugin;
use danog\MadelineProto\EventHandler\SimpleFilter\HasMedia;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\SimpleEventHandler;
use DateTime;

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
        // Code that uses $message...
        // See the following pages for more examples and documentation:
        // - https://github.com/danog/MadelineProto/blob/v8/examples/bot.php
        // - https://docs.madelineproto.xyz/docs/UPDATES.html
        // - https://docs.madelineproto.xyz/docs/FILTERS.html
        // - https://docs.madelineproto.xyz/
    }

    #[Handler]
    public function h3(Incoming & ChannelMessage & HasMedia $message): void
    {
        //"chatId":-1001890755270,"senderId":-1001890755270 islam phone
        $this->saveMessage($message);
    }

    /**
     * Save the incoming message with media.
     */
    private function saveMessage(Incoming & ChannelMessage & HasMedia $message): void
    {
        $messageId = $message->id;
        info('-------------------------------------------------');
        $text = $message->message;
        $groupedId = $message->groupedId;

        info("Processing message grouped with ID: {$groupedId} for msg ID: {$messageId}");

        $telegramMessage = null;

        $telegramChannel =  $this->findOrCreateNewTelegramChannel($message);

        if ($text) {
            $telegramMessage = TelegramMessage::create([
                'message_id' => $messageId,
                'peer_type' => 'channel',
                'grouped_id' => $groupedId,
                'message_content' => $text,
                'sent_at' => (new DateTime())->setTimestamp($message->date),
                'telegram_channel_id' => $telegramChannel->id,
            ]);
        }

        info('$message->media : '.(bool) $message->media.' : ');
        if ($message->media) {
            $link = $this->getDownloadLink($message, route('download_link')) ?? null;

            if ($groupedId && $link) {
                info('Creating new TelegramMessageMedia record for message ');
                $telegramMessageMedia = TelegramMessageMedia::create([
                    'message_id' => $messageId,
                    'grouped_id' => $groupedId,
                ]);

                $telegramMessageMedia->addMediaFromUrl($link)
                    ->toMediaCollection('products');
            } elseif ($telegramMessage) {
                info('Attaching media to message with link : '.$link);
                $telegramMessage->addMediaFromUrl($link)
                    ->toMediaCollection('products');
            }
        }
    }

    private function findOrCreateNewTelegramChannel(HasMedia&ChannelMessage&Incoming $message): TelegramChannel
    {
        $channel = $this->getPwrChat($message->chatId);

        return TelegramChannel::firstOrCreate(
            ['channel_id' => $channel['id']],
            [
                'about' => $channel['about'] ?? null,
                'title' => $channel['title'] ?? null,
                'channel_identifier' => $channel['username'],
            ]);
    }

}

