<?php

namespace App\Console\Commands;

use App\Models\TelegramChannel;
use App\Models\TelegramMessage;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

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
                        'limit' => 25,
                    ]);
                    $this->info('Messages number  : ' . count($messages));


                    foreach ($messages['messages'] as $msg) {
                        $groupedId = $msg['grouped_id'] ?? null;

                        $this->info("Processing media group with ID: {$groupedId} for msg ID: {$msg['id']}");

                        $isMedia = empty($msg['message']);
                        $link = null;

                        if ($isMedia) {
                            $link =  $isMedia ? $madelineProto->getDownloadLink($msg['media'], route('download_link')) : null;

                            $this->info("Media link  " . $link);
                        }


                        $t = TelegramMessage::find($msg['id']);

                        if ($t) {
                            $this->info("Message already exists, skipping: {$msg['id']}");
                            continue;
                        }

                        if ( $t =  TelegramMessage::where('grouped_id', $groupedId)->first()) {

                            if($link){
                                $t->addMediaFromUrl($link)
                                    ->toMediaCollection('products');
                            }else{
                                $t->message_content = $msg['message'] ?? '';
                                $t->save();
                            }

                            continue;
                        }


                        $t = TelegramMessage::create(
                            [
                                'id' => $msg['id'],
                                'telegram_channel_id' => $channel->id,
                                'message_content' => $msg['message'] ?? '',
                                'sent_at' => (new DateTime())->setTimestamp($msg['date']),
                                'grouped_id' => $groupedId,
                            ]
                        );

                        if ($link) {
                            $t->addMediaFromUrl($link)
                                ->toMediaCollection('products');
                        }
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
