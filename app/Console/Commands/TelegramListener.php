<?php

namespace App\Console\Commands;

use App\Services\Telegram\BasicEventHandler;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class TelegramListener extends Command
{ 
    protected $signature = 'telegram:listen';
 
    protected $description = 'Start or stop the Telegram listener for new messages';

    
    public function handle()
    {
        BasicEventHandler::startAndLoop('session.madeline');
    }

     
}
