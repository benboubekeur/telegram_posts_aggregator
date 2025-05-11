<?php

namespace App\Console\Commands;

use App\Models\TelegramChannel;
use App\Models\TelegramMessage;
use App\Models\TelegramMessageMedia;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FetchTelegramMessages extends Command
{
    protected $signature = 'telegram:fetch';
    protected $description = 'Fetch new messages from registered Telegram channels';

    public function handle(): int
    {
        try {
            $settings = (new AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'));

            $this->info('Starting madelineProto...');

            // Add error handling and timeout settings
            $madelineProto = new API('session.madeline', $settings);

            // Start and connect with proper error handling
            if (!$madelineProto->getSelf()) {
                $this->info('Logging in...');
                $madelineProto->start();
            }

            $this->info('Successfully connected to Telegram!');

            $channels = TelegramChannel::all();

            foreach ($channels as $channel) {
                $this->info('Processing channel: ' . $channel->channel_identifier);
                try {
                    //$entity = $madelineProto->getPwrChat($channel->channel_identifier);


                    $messages = $madelineProto->messages->getHistory([
                        'peer' => $channel->channel_identifier,
                        'limit' => 30,
                    ]);

                    $this->info('Messages number  : ' . count($messages['messages']));


                    info('Messages: ' . json_encode($messages['messages']));


                    foreach ($messages['messages'] as $msg) {
                        $groupedId = $msg['grouped_id'] ?? null;

                        $this->info("Processing media group with ID: {$groupedId} for msg ID: {$msg['id']}");


                        $link =    $madelineProto->getDownloadLink($msg, route('download_link')) ?? null;

                        if ($link && $groupedId) {


                            $this->info("Media link  " . $link);

                            $tmm = TelegramMessageMedia::create([
                                'grouped_id' => $groupedId,
                                'message_id' => $msg['id'],
                            ]);

                            $tmm->addMediaFromUrl($link)
                                ->toMediaCollection('products');
                        }


                        $t = TelegramMessage::find($msg['id']);

                        if ($t) {
                            $this->info("Message already exists, skipping: {$msg['id']}");
                            continue;
                        }

                        if ($msg['message']) {
                           $tm =  TelegramMessage::create([
                                'id' => $msg['id'],
                                'telegram_channel_id' => $channel->id,
                                'grouped_id' => $groupedId,
                                'message_content' => $msg['message'] ?? null,
                                'sent_at' => (new DateTime())->setTimestamp($msg['date']),
                            ]);

                            if ($link && is_null($groupedId)) {


                                $tm->addMediaFromUrl($link)
                                    ->toMediaCollection('products');
                            }
                        }




                        $this->info("Message created: {$msg['id']}");
                    }

                    $this->info("Successfully processed channel: {$channel->channel_identifier}");
                } catch (\Exception $e) {
                    $this->error("Error processing channel {$channel->channel_identifier}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            $this->error('Fatal error: ' . $e->getMessage() . ' Line ' . $e->getLine());
            return 0;
        }

        return 1;
    }
}
