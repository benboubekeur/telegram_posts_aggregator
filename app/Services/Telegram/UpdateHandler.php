<?php

namespace App\Services\Telegram;

use danog\MadelineProto\EventHandler;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Tools;
use App\Models\TelegramMessage;
use App\Models\TelegramMedia;
use App\Models\TelegramMessageMedia;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

/**
 * Event handler for Telegram messages
 */
class UpdateHandler extends EventHandler
{
    /**
     * Get peer(s) to listen to for updates
     *
     * @return array|string|int
     */
    public function getReportPeers()
    {
        // You can return specific chat IDs to listen to
        // or return an empty array to listen to all chats
        return [];
    }

    /**
     * Handle updates from channels/chats
     *
     * @param array $update Update data
     * @return \Generator
     */
    public function onUpdateNewChannelMessage(array $update)
    {
        yield $this->onUpdateNewMessage($update);
    }

    /**
     * Handle updates from private chats
     *
     * @param array $update Update data
     * @return \Generator
     */
    public function onUpdateNewMessage(array $update)
    {
        if ($update['message']['_'] === 'messageEmpty') {
            return;
        }

        try {
            $message = $update['message'];

            // Process the message
            $messageData = [
                'id' => $message['id'],
                'text' => $message['message'] ?? '',
                'date' => $message['date'],
                'media' => []
            ];

            // Extract peer info
            if (isset($message['peer_id'])) {
                $peerType = $message['peer_id']['_'];

                if ($peerType === 'peerChannel') {
                    $messageData['peer_type'] = 'channel';
                    $messageData['peer_id'] = $message['peer_id']['channel_id'];
                } elseif ($peerType === 'peerChat') {
                    $messageData['peer_type'] = 'chat';
                    $messageData['peer_id'] = $message['peer_id']['chat_id'];
                } elseif ($peerType === 'peerUser') {
                    $messageData['peer_type'] = 'user';
                    $messageData['peer_id'] = $message['peer_id']['user_id'];
                }
            }

            // Extract from_id if available
            if (isset($message['from_id'])) {
                $fromType = $message['from_id']['_'];

                if ($fromType === 'peerUser') {
                    $messageData['from_id'] = $message['from_id']['user_id'];
                }
            }

            // Process media if available
            if (isset($message['media'])) {
                //$mediaData = $this->processMedia($message['media']);
                if (!empty($mediaData)) {
                  //  $messageData['media'] = $mediaData;
                }
            }

            // Handle grouped media (albums)
            if (isset($message['grouped_id'])) {
                $messageData['grouped_id'] = $message['grouped_id'];
            }

            // Save to database
            $this->saveMessageToDatabase($messageData);

            Logger::log("Processed new message: {$message['id']}", Logger::NOTICE);
        } catch (\Throwable $e) {
            Logger::log("Error processing message: {$e->getMessage()}", Logger::ERROR);
            Log::error("Telegram message processing error: {$e->getMessage()}");
        }
    }

    /**
     * Process media from a message
     *
     * @param array $media
     * @return array
     */
    protected function processMedia(array $media)
    {
        $result = [];

        try {
            // Handle photo media
            if ($media['_'] === 'messageMediaPhoto' && isset($media['photo'])) {
                $photo = $media['photo'];
                $photoSizes = $photo['sizes'];

                // Usually, the last size is the largest one
                $largestPhoto = end($photoSizes);

                // Download the photo
                $photoPath = yield $this->downloadToDir($photo, storage_path('app/public/telegram_photos/'));

                // Add photo info to result
                $result[] = [
                    'type' => 'photo',
                    'path' => $photoPath,
                    'size' => [
                        'width' => $largestPhoto['w'],
                        'height' => $largestPhoto['h']
                    ],
                    'mime_type' => 'image/jpeg', // Default for Telegram photos
                    'file_size' => $photo['file_size'] ?? null,
                    'thumbnail_path' => null
                ];
            }
            // Handle document media (files, including photos sent as files)
            elseif ($media['_'] === 'messageMediaDocument' && isset($media['document'])) {
                $document = $media['document'];
                $attributes = $document['attributes'];

            

                // Determine media type from attributes and mime type
                $mediaType = 'document'; // Default
                $width = null;
                $height = null;

                foreach ($attributes as $attr) {
                    if ($attr['_'] === 'documentAttributeImageSize') {
                        $mediaType = 'photo';
                        $width = $attr['w'];
                        $height = $attr['h'];
                    } elseif ($attr['_'] === 'documentAttributeVideo') {
                        $mediaType = 'video';
                        $width = $attr['w'];
                        $height = $attr['h'];
                    } elseif ($attr['_'] === 'documentAttributeAudio') {
                        $mediaType = 'audio';
                    }
                }

                // Download the document
                $filePath = yield $this->downloadToDir($document, storage_path('app/public/telegram_files/'));

                // Add document info to result
                $result[] = [
                    'type' => $mediaType,
                    'path' => $filePath,
                    'size' => [
                        'width' => $width,
                        'height' => $height
                    ],
                    'mime_type' => $document['mime_type'] ?? null,
                    'file_size' => $document['size'] ?? null,
                    'thumbnail_path' => null
                ];
            }
            // Handle web page previews with images
            elseif (
                $media['_'] === 'messageMediaWebPage' &&
                isset($media['webpage']['photo'])
            ) {
                $photo = $media['webpage']['photo'];
                $photoSizes = $photo['sizes'];
                $largestPhoto = end($photoSizes);

                $photoPath = yield $this->downloadToDir($photo, storage_path('app/public/telegram_photos/'));

                $result[] = [
                    'type' => 'photo',
                    'path' => $photoPath,
                    'size' => [
                        'width' => $largestPhoto['w'],
                        'height' => $largestPhoto['h']
                    ],
                    'mime_type' => 'image/jpeg',
                    'file_size' => $photo['file_size'] ?? null,
                    'thumbnail_path' => null
                ];
            }
        } catch (\Throwable $e) {
            Logger::log("Error processing media: {$e->getMessage()}", Logger::ERROR);
            Log::error("Telegram media processing error: {$e->getMessage()}");
        }

        return $result;
    }

    /**
     * Save a message to the database
     *
     * @param array $messageData
     * @return void
     */
    protected function saveMessageToDatabase(array $messageData)
    {
        try {
            // First check if message already exists in database
            $existingMessage = TelegramMessage::where('message_id', $messageData['id'])
                ->where('peer_type', $messageData['peer_type'] ?? '')
                ->where('peer_id', $messageData['peer_id'] ?? 0)
                ->first();

            if ($existingMessage) {
                // Update existing message
                $existingMessage->update([
                    'text' => $messageData['text'],
                    'date' => date('Y-m-d H:i:s', $messageData['date']),
                    'from_id' => $messageData['from_id'] ?? null,
                    'grouped_id' => $messageData['grouped_id'] ?? null
                ]);

                $messageId = $existingMessage->id;
            } else {
                // Create new message
                $newMessage = TelegramMessage::create([
                    'message_id' => $messageData['id'],
                    'peer_type' => $messageData['peer_type'] ?? null,
                    'peer_id' => $messageData['peer_id'] ?? null,
                    'from_id' => $messageData['from_id'] ?? null,
                    'text' => $messageData['text'],
                    'date' => date('Y-m-d H:i:s', $messageData['date']),
                    'grouped_id' => $messageData['grouped_id'] ?? null
                ]);

                $messageId = $newMessage->id;
            }

            // Process media for this message
            if (false) {
                foreach ($messageData['media'] as $mediaItem) {
                    // Check for existing media with the same path to avoid duplicates
                    $existingMedia = TelegramMessageMedia::where('message_id', $messageId)
                        ->where('path', $mediaItem['path'])
                        ->first();

                    if (!$existingMedia) {
                        // Create new media record
                        TelegramMessageMedia::create([
                            'message_id' => $messageId,
                            'type' => $mediaItem['type'],
                            'path' => $mediaItem['path'],
                            'width' => $mediaItem['size']['width'],
                            'height' => $mediaItem['size']['height'],
                            'mime_type' => $mediaItem['mime_type'] ?? null,
                            'file_size' => $mediaItem['file_size'] ?? null,
                            'thumbnail_path' => $mediaItem['thumbnail_path'] ?? null
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Logger::log("Error saving message to database: {$e->getMessage()}", Logger::ERROR);
            Log::error("Database save error: {$e->getMessage()}");
        }
    }
}
