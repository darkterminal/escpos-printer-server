<?php
declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer;

use Darkterminal\EscposPrinterServer\Contracts\PrinterServer;
use Darkterminal\EscposPrinterServer\Utils\LoggerTrait;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class Server implements PrinterServer
{
    use LoggerTrait;

    public function __construct(
        public ?string $host = '0.0.0.0',
        public ?int $port = 1945
    )
    {
        $this->host = $host;
        $this->port = $port;
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
            $this->info("Received data: " . ($data ? json_encode($data) : "unknown"));

            if (!isset($data['from'], $data['printer_name'], $data['printer_settings'])) {
                throw new \InvalidArgumentException('Invalid payload: Missing required fields (from, printer_name, printer_settings)');
            }

            $receiptPrinter = new ReceiptPrinter($data, $data['printer_settings']);

            switch ($data['from']) {
                case 'postclient':
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
        } catch (\Exception $e) {
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

    public function run(): void
    {
        // Create a Websocket server
        $ws_worker = new Worker("websocket://{$this->host}:{$this->port}");

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
