<?php

declare(strict_types=1);

class AccountController
{
    private const SESSION_KEY = 'accounts';
    private const SESSION_NEXT_NUMBER_KEY = 'next_number';

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY], $_SESSION[self::SESSION_NEXT_NUMBER_KEY])) {
            $this->resetState();
        }
    }

    public function reset(): void
    {
        $this->resetState();
        $this->sendResponse([], 205);
    }

    public function list(): void
    {
        $accounts = array_values($_SESSION[self::SESSION_KEY]);

        if (empty($accounts)) {
            $this->sendResponse([], 404);
            return;
        }

        $list = array_map(static fn (array $account) => ['number' => $account['number']], $accounts);
        $this->sendResponse($list, 200);
    }

    public function create(): void
    {
        $payload = $this->readJsonPayload();

        if ($payload === null || !array_key_exists('balance', $payload)) {
            $this->sendResponse(['error' => 'Missing "balance" value.'], 400);
            return;
        }

        if (!is_numeric($payload['balance'])) {
            $this->sendResponse(['error' => 'The "balance" must be numeric.'], 422);
            return;
        }

        $number = $_SESSION[self::SESSION_NEXT_NUMBER_KEY]++;
        $balance = (float) $payload['balance'];

        $_SESSION[self::SESSION_KEY][$number] = [
            'number' => $number,
            'balance' => $balance,
        ];

        $this->sendResponse(['number' => $number], 201);
    }

    public function get(int $number): void
    {
        $account = $this->findAccount($number);

        if ($account === null) {
            $this->sendResponse([], 404);
            return;
        }

        $this->sendResponse($account, 200);
    }

    public function update(int $number): void
    {
        $account = $this->findAccount($number);

        if ($account === null) {
            $this->sendResponse([], 404);
            return;
        }

        $payload = $this->readJsonPayload();

        if ($payload === null || !array_key_exists('balance', $payload)) {
            $this->sendResponse(['error' => 'Missing "balance" value.'], 400);
            return;
        }

        if (!is_numeric($payload['balance'])) {
            $this->sendResponse(['error' => 'The "balance" must be numeric.'], 422);
            return;
        }

        $account['balance'] = (float) $payload['balance'];
        $_SESSION[self::SESSION_KEY][$number] = $account;

        $this->sendResponse($account, 201);
    }

    private function resetState(): void
    {
        $_SESSION[self::SESSION_KEY] = [];
        $_SESSION[self::SESSION_NEXT_NUMBER_KEY] = 1;
    }

    private function findAccount(int $number): ?array
    {
        return $_SESSION[self::SESSION_KEY][$number] ?? null;
    }

    private function readJsonPayload(): ?array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            return null;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function sendResponse($data, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
