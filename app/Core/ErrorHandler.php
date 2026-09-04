<?php
namespace App\Core;

final class ErrorHandler
{
    private static bool $rendering = false;

    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        set_exception_handler([self::class, 'render']);
        register_shutdown_function([self::class, 'shutdown']);
    }

    public static function shutdown(): void
    {
        $last = error_get_last();
        if (!$last || !in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;

        self::render(new \ErrorException($last['message'], 0, $last['type'], $last['file'], $last['line']));
    }

    public static function render(\Throwable $error): never
    {
        if (self::$rendering) {
            http_response_code(500);
            exit('A secondary error occurred while rendering the diagnostic page. Check the PHP error log.');
        }
        self::$rendering = true;

        $requestId = self::requestId();
        error_log("[{$requestId}] ".$error->__toString());
        $debug = self::debugEnabled();
        $details = self::details($error, $requestId);

        if (self::expectsJson()) {
            $payload = ['ok' => false, 'error' => $debug ? $error->getMessage() : 'The request could not be completed.', 'request_id' => $requestId];
            if ($debug) $payload['debug'] = $details;
            Response::json($payload, 500);
        }

        http_response_code(500);
        $status = 500;
        $message = $debug ? 'The exact development error is shown below.' : 'An unexpected error occurred. Please try again or contact the administrator.';
        View::render('errors/http', compact('status', 'message', 'debug', 'details'));
        exit;
    }

    private static function debugEnabled(): bool
    {
        if (defined('APP_DEBUG')) return APP_DEBUG === true;
        $environment = getenv('MEP_APP_ENV') ?: 'production';
        $configured = getenv('MEP_APP_DEBUG');
        return $environment === 'development' && $configured !== false && filter_var($configured, FILTER_VALIDATE_BOOL);
    }

    private static function expectsJson(): bool
    {
        $route = (string)($_GET['route'] ?? '');
        return str_starts_with($route, 'api/') || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private static function details(\Throwable $error, string $requestId): array
    {
        $action = trim((string)($_POST['action'] ?? ''));
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? 'unknown');
        $route = (string)($_GET['route'] ?? pathinfo($script, PATHINFO_FILENAME));
        $handler = (string)($_SERVER['MEP_HANDLER'] ?? 'Legacy form/action or bootstrap');
        $actionFile = (string)($_SERVER['MEP_ACTION_FILE'] ?? 'Not entered');
        $fields = array_values(array_filter(array_keys($_POST), static fn($key): bool => !in_array(strtolower((string)$key), ['csrf_token', 'password', 'password_confirmation'], true)));

        return [
            'request_id' => $requestId,
            'exception' => get_class($error),
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'method' => strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            'route' => $route !== '' ? $route : 'unknown',
            'script' => $script,
            'handler' => $handler,
            'action_file' => $actionFile,
            'form_action' => $action !== '' ? $action : ($_SERVER['REQUEST_METHOD'] ?? 'GET').' request (no action field)',
            'submitted_fields' => $fields,
            'source' => self::sourceExcerpt($error->getFile(), $error->getLine()),
            'possible_cause' => self::possibleCause($error),
            'trace' => $error->getTraceAsString(),
        ];
    }

    private static function sourceExcerpt(string $file, int $line): array
    {
        $projectRoot = realpath(dirname(__DIR__, 2));
        $realFile = realpath($file);
        if (!$projectRoot || !$realFile || !str_starts_with($realFile, $projectRoot.DIRECTORY_SEPARATOR) || !is_readable($realFile)) return [];

        $lines = file($realFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) return [];
        $start = max(1, $line - 4);
        $end = min(count($lines), $line + 4);
        $excerpt = [];
        for ($number = $start; $number <= $end; $number++) {
            $excerpt[] = ['number' => $number, 'text' => $lines[$number - 1], 'error' => $number === $line];
        }
        return $excerpt;
    }

    private static function possibleCause(\Throwable $error): string
    {
        if ($error instanceof \ParseError || ($error instanceof \ErrorException && $error->getSeverity() === E_PARSE)) return 'PHP syntax error. Inspect the highlighted line and the preceding lines for a missing semicolon, bracket, parenthesis, quote, or an extra closing brace.';
        if ($error instanceof \PDOException) return 'Database/SQL failure. Check the exact PDO message, schema migration, table or column names, placeholders, constraints, and transaction state.';
        if ($error instanceof \TypeError) return 'Type mismatch. Compare the value passed at the first application stack frame with the parameter or return type declared by that function.';
        if ($error instanceof \ErrorException) return 'Fatal PHP runtime error. Inspect the exact message and highlighted source line; also confirm required files, extensions, and memory limits.';
        if ($error instanceof \Error) return 'PHP runtime/programming error, such as an undefined method, missing class, invalid property access, or duplicate declaration.';
        return 'Application exception. Start with the exact message and first application file in the stack trace.';
    }

    private static function requestId(): string
    {
        try { return strtoupper(bin2hex(random_bytes(6))); }
        catch (\Throwable) { return strtoupper(substr(uniqid('', true), -12)); }
    }
}
