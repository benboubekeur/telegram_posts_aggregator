<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class TelegramListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:listen {--daemon : Run in daemon mode} {--stop : Stop the daemon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start or stop the Telegram listener for new messages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pidFile = public_path('app/telegram_listener.php');

        // Check if stop option is provided
        if ($this->option('stop')) {
            return $this->stopListener($pidFile);
        }

        // Check if listener is already running
        if (file_exists($pidFile)) {
            $pid = (int) file_get_contents($pidFile);
            if ($this->isProcessRunning($pid)) {
                $this->error("Telegram listener is already running with PID: {$pid}");
                return 1;
            } else {
                // Clean up stale PID file
                unlink($pidFile);
            }
        }

        // Start the listener
        if ($this->option('daemon')) {
            return $this->startDaemon($pidFile);
        } else {
            return $this->startForeground();
        }
    }

    /**
     * Start the listener in foreground mode
     *
     * @return int
     */
    protected function startForeground()
    {
        $this->info('Starting Telegram listener in foreground mode. Press Ctrl+C to stop.');
        $scriptPath = public_path('scripts/telegram_listener.php');

        // Ensure the script exists
        if (!file_exists($scriptPath)) {
            $this->error("Listener script not found at: {$scriptPath}");
            return 1;
        }

        // Run the PHP script
        $process = new Process(['php', $scriptPath]);
        $process->setTimeout(null);
        $process->setTty(true);

        try {
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            return $process->isSuccessful() ? 0 : 1;
        } catch (\Exception $e) {
            $this->error("Error running listener: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Start the listener as a daemon process
     *
     * @param string $pidFile
     * @return int
     */
    protected function startDaemon($pidFile)
    {
        $this->info('Starting Telegram listener in daemon mode...');
        $scriptPath = public_path('scripts/telegram_listener.php');

        // Ensure the script exists
        if (!file_exists($scriptPath)) {
            $this->error("Listener script not found at: {$scriptPath}");
            return 1;
        }

        // Create log file
        $logFile = storage_path('logs/telegram_listener.log');

        // Build the command to run PHP as a daemon
        $command = "nohup php {$scriptPath} > {$logFile} 2>&1 & echo $!";

        // Execute the command
        exec($command, $output);

        if (empty($output)) {
            $this->error('Failed to start daemon process');
            return 1;
        }

        // Get the PID of the daemon process
        $pid = (int) $output[0];

        // Save PID to file
        file_put_contents($pidFile, $pid);

        $this->info("Telegram listener started as daemon with PID: {$pid}");
        $this->info("Logs are being written to: {$logFile}");

        return 0;
    }

    /**
     * Stop a running listener daemon
     *
     * @param string $pidFile
     * @return int
     */
    protected function stopListener($pidFile)
    {
        if (!file_exists($pidFile)) {
            $this->error('No running Telegram listener found');
            return 1;
        }

        $pid = (int) file_get_contents($pidFile);

        if (!$this->isProcessRunning($pid)) {
            $this->warn("Process with PID {$pid} is not running");
            unlink($pidFile);
            return 1;
        }

        // Send SIGTERM to the process
        if (posix_kill($pid, SIGTERM)) {
            $this->info("Sent termination signal to process {$pid}");

            // Wait a bit and check if process is still running
            sleep(3);

            if ($this->isProcessRunning($pid)) {
                $this->warn("Process {$pid} is still running. Sending SIGKILL...");
                posix_kill($pid, SIGKILL);
            }

            unlink($pidFile);
            $this->info('Telegram listener stopped successfully');
            return 0;
        } else {
            $this->error("Failed to terminate process {$pid}");
            return 1;
        }
    }

    /**
     * Check if a process with the given PID is running
     *
     * @param int $pid
     * @return bool
     */
    protected function isProcessRunning($pid)
    {
        if (empty($pid) || !is_numeric($pid)) {
            return false;
        }

        try {
            $result = shell_exec(sprintf('ps %d 2>&1', $pid));
            return count(preg_split("/\n/", $result)) > 2;
        } catch (\Exception $e) {
            return false;
        }
    }
}
