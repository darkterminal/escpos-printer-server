<?php
declare(strict_types=1);

namespace Darkterminal\EscposPrinterServer\Contracts;

use Workerman\Connection\TcpConnection;

interface PrinterServer
{
    public function onConnect(TcpConnection $connection): void;
    public function onMessage(TcpConnection $connection, mixed $payload): void;
    public function onClose(TcpConnection $connection): void;
    public function run(): void;
}
