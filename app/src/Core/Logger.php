<?php

class Logger
{
    private static string $logFile = __DIR__ . '/../logs/app.log';

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $date = date('Y-m-d H:i:s');

        if (!empty($context)) {
            $message .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $line = "[{$date}] {$level}: {$message}" . PHP_EOL;

        @file_put_contents(self::$logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
