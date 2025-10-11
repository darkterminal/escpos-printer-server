<?php
declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer\Utils;

trait LoggerTrait
{
    /**
     * Log directory path
     * @var string
     */
    private $logDirectory = __DIR__ . '/../logs'; // Adjusted path relative to src

    /**
     * Log a message with specified type
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    private function log(string $message, string $type = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$type] $message" . PHP_EOL;

        $baseDir = class_exists('Phar') && \Phar::running(false) ? dirname(\Phar::running(false)) : __DIR__;

        $this->logDirectory = $baseDir;

        // Create log directory if it doesn't exist
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
        }

        // Create log filename with current date
        $filename = $this->logDirectory . '/app_' . date('Y-m-d') . '.log';

        // Write to log file
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);

        // Also output to console for real-time monitoring
        echo $logEntry;
    }

    /**
     * Log error message
     *
     * @param string $message
     * @return void
     */
    public function error(string $message): void
    {
        $this->log($message, 'ERROR');
    }

    /**
     * Log info message
     *
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        $this->log($message, 'INFO');
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @return void
     */
    public function warning(string $message): void
    {
        $this->log($message, 'WARNING');
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @return void
     */
    public function debug(string $message): void
    {
        $this->log($message, 'DEBUG');
    }

    /**
     * Set custom log directory
     *
     * @param string $directory
     * @return void
     */
    public function setLogDirectory(string $directory): void
    {
        $this->logDirectory = rtrim($directory, '/');
    }
}
