<?php
declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer;

use Darkterminal\EscposPrinterServer\Contracts\PrinterServer;
use Darkterminal\EscposPrinterServer\Support\Auth;
use Darkterminal\EscposPrinterServer\Support\PayloadValidator;
use Darkterminal\EscposPrinterServer\Utils\LoggerTrait;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

class Server implements PrinterServer
{
    use LoggerTrait;

    protected string $configDir;
    protected string $configFile;
    protected string $settingsPage;
    protected string $assetDir;

    public function __construct(
        public ?string $host = '0.0.0.0',
        public ?int $port = 1945,
        ?string $configDir = null
    )
    {
        $this->host = $host;
        $this->port = $port;

        $phar = \Phar::running(false);
        $base = $phar !== '' ? dirname($phar) : dirname(__DIR__);
        $this->configDir = $configDir ?? $base . DIRECTORY_SEPARATOR . 'config';
        $this->assetDir = $phar !== '' ? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' : $this->configDir;
        $this->configFile = $this->configDir . DIRECTORY_SEPARATOR . 'printer-settings.json';
        $this->settingsPage = $this->assetDir . DIRECTORY_SEPARATOR . 'settings.html';

        $this->createConfigurationSettings();
    }

    private function createConfigurationSettings(): void
    {
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0755, true);
        }

        if (!file_exists($this->configFile)) {
            $defaultPrinterSetting = [
                'from' => 'testprinter',
                'printer_name' => 'IW-8001',
                'printer_settings' => [
                    'printer_name' => 'IW-8001',
                    'interface' => 'ethernet',
                    'printer_host' => '192.168.1.150',
                    'printer_port' => 9100,
                    'template' => 'epson',
                    'pull_cash_drawer' => true,
                    'line_feed_each_in_items' => 1,
                    'more_new_line' => 0,
                    'custom_print_header' => [
                        'DARKTERMINAL MART',
                        'Jl. Merdeka No. 45, Tegal',
                        'Open 07:30 - 16:30',
                    ],
                    'custom_print_footer' => [
                        'Darkterminal Mart',
                        'Belanja Nyaman, Harga Aman',
                    ],
                    'custom_language' => [
                        'operator' => 'Kasir',
                        'time' => 'Waktu',
                        'transaction_number' => 'No TRX',
                        'customer_name' => 'Nama Pelanggan',
                        'tax' => 'Pajak',
                        'member' => 'Diskon Member',
                        'total' => 'Total Belanja',
                        'paid' => 'Tunai',
                        'return' => 'Kembalian',
                        'due_date' => 'Jatuh Tempo',
                        'saving' => 'Tabungan',
                        'loan' => 'Piutang',
                    ],
                ],
            ];

            $tempFile = $this->configFile . '.tmp.' . bin2hex(random_bytes(4));
            file_put_contents($tempFile, json_encode($defaultPrinterSetting, JSON_PRETTY_PRINT), LOCK_EX);
            rename($tempFile, $this->configFile);
        }
    }

    public function onConnect(TcpConnection $connection): void
    {
        echo "Server started on {$connection->getRemoteIp()}:{$connection->getRemotePort()}" . PHP_EOL;
        $this->debug("Server started on {$connection->getRemoteIp()}:{$connection->getRemotePort()}");
    }

    public function onMessage(TcpConnection $connection, mixed $payload): void
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            if (!Auth::authorize(Auth::token(), $connection->getRemoteIp(), $data['auth_token'] ?? null)) {
                $this->warning('Unauthorized websocket request from ' . $connection->getRemoteIp());
                $connection->send($this->createMessage('error', 'Unauthorized'));
                $connection->close();
                return;
            }

            $loggedData = is_array($data) ? array_diff_key($data, ['auth_token' => null]) : $data;
            $this->info("Received data: " . ($loggedData ? json_encode($loggedData) : "unknown"));

            $data = PayloadValidator::validate($data);

            $receiptPrinter = new ReceiptPrinter();
            $receiptPrinter->receiptData = $data['receipt_data'];
            $receiptPrinter->printerSettings = $data['printer_settings'];

            switch ($data['from']) {
                case 'posclient':
                    $receiptPrinter->printReceipt();
                    $responseMesage = $this->createMessage('success', 'Receipt printed successfully');
                    break;
                case 'testprinter':
                    $receiptPrinter->testPrinter();
                    $responseMesage = $this->createMessage('success', 'Printer tested successfully');
                    break;
                case 'pullcashdrawer':
                    $receiptPrinter->pullCashDrawer();
                    $responseMesage = $this->createMessage('success', 'Cash drawer pulled successfully');
                    break;

                default:
                    $warningMessage = "Unknown source: {$data['from']}";
                    $this->warning($warningMessage);
                    $responseMesage = $this->createMessage('warning', $warningMessage);
                    break;
            }

            $connection->send($responseMesage);
        } catch (\JsonException $e) {
            $errorMessage = "Invalid JSON payload: {$e->getMessage()}";
            $this->error($errorMessage);
            $connection->send($this->createMessage('error', $errorMessage));
        } catch (\InvalidArgumentException $e) {
            $errorMessage = "Invalid payload format: {$e->getMessage()}";
            $this->error($errorMessage);
            $connection->send($this->createMessage('error', $errorMessage));
        } catch (\Throwable $e) {
            $errorMessage = "An error occurred: {$e->getMessage()}";
            $this->error($errorMessage);
            $connection->send($this->createMessage('error', $errorMessage));
        }
    }

    public function onClose(TcpConnection $connection): void
    {
        $message = "Client disconnected from {$connection->getRemoteIp()}:{$connection->getRemotePort()}";
        $this->info($message);
        echo $message . PHP_EOL;
        $connection->close();
    }

    public function runWebsocketOnly(): void
    {
        $ws = new Worker("websocket://{$this->host}:{$this->port}");
        $ws->name = 'EscposPrinterServer';
        $ws->onConnect = fn($conn) => $this->onConnect($conn);
        $ws->onMessage = fn($conn, $data) => $this->onMessage($conn, $data);
        $ws->onClose = fn($conn) => $this->onClose($conn);

        Worker::$stdoutFile = "{$this->logDirectory}". DIRECTORY_SEPARATOR ."stdout_ws.log";
        Worker::$logFile = "{$this->logDirectory}". DIRECTORY_SEPARATOR ."workerman_ws.log";
        Worker::runAll();
    }

    public function runHttpOnly(): void
    {
        $http = new Worker("http://{$this->host}:1100");
        $http->name = 'EscposHTTP';
        $http->count = 1;

        $configFile = $this->configFile;
        $settingsPage = $this->settingsPage;

        $http->onMessage = function ($connection, Request $req) use ($configFile, $settingsPage) {
            $path = $req->path();
            if ($path === '/' || $path === '/settings' || $path === '/index.html') {
                if (!file_exists($settingsPage)) {
                    $connection->send(new Response(503, ['Content-Type' => 'text/plain'], 'Web GUI asset missing: config/settings.html was not bundled.'));
                    return;
                }
                $html = file_get_contents($settingsPage);
                $html = str_replace('__EPS_TOKEN_JSON__', json_encode(Auth::token() ?? ''), $html);
                $connection->send(new Response(200, ['Content-Type' => 'text/html'], $html));
                return;
            }
            if ($path === '/api/config') {
                $provided = $req->header('X-EPS-Token');
                $provided = $provided === null ? null : (string) $provided;

                if (!Auth::authorize(Auth::token(), $connection->getRemoteIp(), $provided)) {
                    $connection->send(new Response(401, ['Content-Type' => 'text/plain'], 'Unauthorized'));
                    return;
                }

                if ($req->method() === 'GET') {
                    $data = file_exists($configFile) ? file_get_contents($configFile) : '{}';
                    $connection->send(new Response(200, ['Content-Type' => 'application/json'], $data));
                } elseif ($req->method() === 'POST') {
                    file_put_contents($configFile, $req->rawBody());
                    $connection->send(new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'saved'])));
                } else {
                    $connection->send(new Response(405, [], 'Method Not Allowed'));
                }
                return;
            }
            $connection->send(new Response(404, [], 'Not Found'));
        };

        Worker::$stdoutFile = "{$this->logDirectory}/stdout_http.log";
        Worker::$logFile = "{$this->logDirectory}/workerman_http.log";
        Worker::runAll();
    }

    public function run(): void
    {
        // Create a Websocket server
        $ws_worker = new Worker("websocket://{$this->host}:{$this->port}");
        $ws_worker->name = "EscposPrinterServer";

        Worker::$stdoutFile = $this->logDirectory . DIRECTORY_SEPARATOR . 'stdout.log';
        Worker::$logFile = $this->logDirectory . DIRECTORY_SEPARATOR . 'workerman.log';
        Worker::$pidFile = $this->logDirectory . DIRECTORY_SEPARATOR . 'workerman.pid';

        // Emitted when new connection come
        $ws_worker->onConnect = function ($connection): void {
            $this->onConnect($connection);
        };

        // Emitted when data received
        $ws_worker->onMessage = function ($connection, $data): void {
            $this->onMessage($connection, $data);
        };

        // Emitted when connection closed
        $ws_worker->onClose = function ($connection): void {
            $this->onClose($connection);
        };
        
        // === HTTP SERVER ===
        $http_worker = new Worker("http://{$this->host}:1100");
        $http_worker->name = 'EscposWebGui';
        $http_worker->count = 1;

        $configFile = $this->configFile;
        $settingsPage = $this->settingsPage;

        $http_worker->onMessage = function ($connection, Request $request) use ($configFile, $settingsPage) {
            $path = $request->path();
            $method = $request->method();

            // Serve the HTML UI at root
            if ($path === '/' || $path === '/settings' || $path === '/index.html') {
                if (!file_exists($settingsPage)) {
                    $connection->send(new Response(503, ['Content-Type' => 'text/plain'], 'Web GUI asset missing: config/settings.html was not bundled.'));
                    return;
                }
                $html = file_get_contents($settingsPage);
                $html = str_replace('__EPS_TOKEN_JSON__', json_encode(Auth::token() ?? ''), $html);
                $connection->send(new Response(200, ['Content-Type' => 'text/html'], $html));
                return;
            }

            // Serve API endpoints
            if ($path === '/api/config') {
                $provided = $request->header('X-EPS-Token');
                $provided = $provided === null ? null : (string) $provided;

                if (!Auth::authorize(Auth::token(), $connection->getRemoteIp(), $provided)) {
                    $connection->send(new Response(401, ['Content-Type' => 'text/plain'], 'Unauthorized'));
                    return;
                }

                if ($method === 'GET') {
                    $data = file_exists($configFile) ? file_get_contents($configFile) : '{}';
                    $connection->send(new Response(200, ['Content-Type' => 'application/json'], $data));
                    return;
                }

                if ($method === 'POST') {
                    $body = $request->rawBody();
                    file_put_contents($configFile, $body);
                    $connection->send(new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'saved'])));
                    return;
                }

                $connection->send(new Response(405, [], 'Method Not Allowed'));
                return;
            }

            // 404 fallback
            $connection->send(new Response(404, ['Content-Type' => 'text/plain'], 'Not Found'));
        };

        // Run worker
        Worker::runAll();
    }

    private function createMessage(string $type = 'info', string $message): string
    {
        $timestamps = date('Y/m/d H:i:s');
        $log = strtoupper($type);
        return "[$timestamps][$log] $message";
    }
}
