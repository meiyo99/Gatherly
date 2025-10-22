<?php

class ErrorHandler
{
    private static $logFile;

    public static function init()
    {
        self::$logFile = __DIR__ . '/../../logs/error.log';

        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_STRICT => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER DEPRECATED'
        ];

        $errorType = $errorTypes[$errno] ?? 'UNKNOWN ERROR';

        self::logError($errorType, $errstr, $errfile, $errline);

        if (self::isDevelopment()) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 10px; border-radius: 5px;'>";
            echo "<strong>[$errorType]</strong> $errstr<br>";
            echo "<small>File: $errfile | Line: $errline</small>";
            echo "</div>";
        }

        return true;
    }

    public static function handleException($exception)
    {
        self::logError(
            'EXCEPTION',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        // Display error page
        http_response_code(500);

        if (self::isDevelopment()) {
            echo "<!DOCTYPE html>
<html>
<head>
    <title>Exception - Gatherly</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .error-container { background: white; padding: 30px; border-radius: 8px; max-width: 1000px; margin: 0 auto; }
        h1 { color: #e74c3c; }
        .message { background: #fee; padding: 15px; border-left: 4px solid #e74c3c; margin: 20px 0; }
        .trace { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        pre { margin: 0; font-size: 12px; }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>⚠️ Uncaught Exception</h1>
        <div class='message'>
            <strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "
        </div>
        <p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>
        <p><strong>Line:</strong> " . $exception->getLine() . "</p>
        <div class='trace'>
            <strong>Stack Trace:</strong>
            <pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>
        </div>
    </div>
</body>
</html>";
        } else {
            require_once __DIR__ . '/../views/errors/500.php';
        }

        exit;
    }

    public static function handleShutdown()
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::logError('FATAL ERROR', $error['message'], $error['file'], $error['line']);

            if (!self::isDevelopment()) {
                http_response_code(500);
                require_once __DIR__ . '/../views/errors/500.php';
            }
        }
    }

    private static function logError($type, $message, $file, $line, $trace = '')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message in $file on line $line";

        if ($trace) {
            $logMessage .= "\nStack trace:\n$trace";
        }

        $logMessage .= "\n" . str_repeat('-', 80) . "\n";

        error_log($logMessage, 3, self::$logFile);
    }

    private static function isDevelopment()
    {
        return ($_ENV['APP_ENV'] ?? 'development') === 'development';
    }
}
