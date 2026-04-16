<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use Illuminate\Http\JsonResponse;

class BridgeProcessController extends Controller
{
    private string $nodeDir;
    private string $pidFile;
    private string $logFile;

    public function __construct()
    {
        $this->nodeDir = base_path('app/Modules/WhatsAppWeb/node');
        $this->pidFile = $this->nodeDir . DIRECTORY_SEPARATOR . '.bridge.pid';
        $this->logFile = storage_path('logs/wa-bridge.log');
    }

    public function start(): JsonResponse
    {
        if (!function_exists('exec')) {
            return response()->json([
                'ok'      => false,
                'message' => 'Fungsi exec() PHP dinonaktifkan di server ini. Hubungi hosting/sysadmin untuk start bridge secara manual.',
            ]);
        }

        if (!is_dir($this->nodeDir . DIRECTORY_SEPARATOR . 'node_modules')) {
            return response()->json([
                'ok'      => false,
                'message' => 'Dependensi Node.js belum terinstall. Jalankan perintah berikut terlebih dahulu: cd ' . $this->nodeDir . ' && npm install',
            ]);
        }

        if ($this->isRunningByPid()) {
            return response()->json([
                'ok'             => true,
                'already_running' => true,
                'message'        => 'Bridge sudah berjalan. Tunggu beberapa detik agar merespons.',
            ]);
        }

        if ($this->pm2Available()) {
            return $this->startViaPm2();
        }

        return $this->startViaBackground();
    }

    public function stop(): JsonResponse
    {
        if (!function_exists('exec')) {
            return response()->json(['ok' => false, 'message' => 'Fungsi exec() tidak tersedia.']);
        }

        if ($this->pm2Available()) {
            exec('pm2 stop wa-bridge 2>&1', $output, $code);
            @unlink($this->pidFile);
            return response()->json([
                'ok'      => $code === 0,
                'message' => $code === 0 ? 'Bridge dihentikan via PM2.' : 'PM2: ' . implode(' ', array_slice($output, 0, 3)),
            ]);
        }

        if (!file_exists($this->pidFile)) {
            return response()->json(['ok' => false, 'message' => 'PID file tidak ditemukan. Bridge mungkin sudah berhenti.']);
        }

        $pid = (int) trim((string) file_get_contents($this->pidFile));

        if ($pid <= 0) {
            @unlink($this->pidFile);
            return response()->json(['ok' => false, 'message' => 'PID tidak valid.']);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /PID $pid /F 2>&1", $output, $code);
        } else {
            exec("kill $pid 2>&1", $output, $code);
        }

        @unlink($this->pidFile);

        return response()->json([
            'ok'      => $code === 0,
            'message' => $code === 0
                ? "Bridge dihentikan (PID: $pid)."
                : 'Gagal menghentikan: ' . implode(' ', $output),
        ]);
    }

    // -------------------------------------------------------------------------

    private function startViaPm2(): JsonResponse
    {
        $cmd = 'pm2 start ' . escapeshellarg($this->nodeDir . DIRECTORY_SEPARATOR . 'server.js')
            . ' --name wa-bridge'
            . ' --cwd ' . escapeshellarg($this->nodeDir)
            . ' 2>&1';

        exec($cmd, $output, $code);

        return response()->json([
            'ok'      => $code === 0,
            'method'  => 'pm2',
            'message' => $code === 0
                ? 'Bridge distart via PM2. Menunggu bridge aktif...'
                : 'PM2 gagal: ' . implode(' ', array_slice($output, 0, 5)),
        ]);
    }

    private function startViaBackground(): JsonResponse
    {
        $env = $this->buildEnvPrefix();

        if (PHP_OS_FAMILY === 'Windows') {
            $logDir = dirname($this->logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            // Windows: start detached process
            $cmd = 'cmd /C "cd /D ' . $this->nodeDir
                . ' && start /B node server.js > ' . $this->logFile . ' 2>&1"';
            exec($cmd, $output, $code);

            return response()->json([
                'ok'      => true,
                'method'  => 'background',
                'message' => 'Perintah start dikirim. Menunggu bridge aktif...',
            ]);
        }

        // Linux / macOS
        $cmd = 'cd ' . escapeshellarg($this->nodeDir)
            . ' && ' . $env
            . 'nohup node server.js >> ' . escapeshellarg($this->logFile) . ' 2>&1 & echo $!';

        exec($cmd, $output, $code);
        $pid = (int) ($output[0] ?? 0);

        if ($pid > 0) {
            file_put_contents($this->pidFile, $pid);
        }

        return response()->json([
            'ok'      => $code === 0 || $pid > 0,
            'method'  => 'nohup',
            'pid'     => $pid ?: null,
            'message' => $pid > 0
                ? "Bridge distart (PID: $pid). Log tersimpan di: {$this->logFile}"
                : 'Perintah dikirim, cek log: ' . $this->logFile,
        ]);
    }

    private function buildEnvPrefix(): string
    {
        $parts = [];

        $token = RuntimeSettings::waWebWebhookToken();
        if ($token) {
            $parts[] = 'WHATSAPP_WEB_BRIDGE_TOKEN=' . escapeshellarg($token);
        }

        $bridgeUrl = RuntimeSettings::waWebBridgeUrl();
        $port      = (string) (parse_url($bridgeUrl, PHP_URL_PORT) ?: 3020);
        $parts[]   = 'WHATSAPP_WEB_PORT=' . escapeshellarg($port);

        return $parts ? implode(' ', $parts) . ' ' : '';
    }

    private function pm2Available(): bool
    {
        exec(PHP_OS_FAMILY === 'Windows' ? 'where pm2 2>&1' : 'which pm2 2>&1', $output, $code);
        return $code === 0;
    }

    private function isRunningByPid(): bool
    {
        if (!file_exists($this->pidFile)) {
            return false;
        }

        $pid = (int) trim((string) file_get_contents($this->pidFile));
        if ($pid <= 0) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq $pid\" /NH 2>&1", $lines);
            return !empty(array_filter($lines, fn ($l) => str_contains($l, (string) $pid)));
        }

        // posix_kill(pid, 0) returns true if process exists
        return function_exists('posix_kill') && posix_kill($pid, 0);
    }
}
