<?php

declare(strict_types=1);

use Castor\Attribute\AsTask;
use Castor\Context;

use function Castor\capture;
use function Castor\run;

const SESSION = 'bad_asso';

function rootDir(): string
{
    return dirname(__FILE__);
}

function wwwUser(): string
{
    return capture('id -u');
}

function wwwGroup(): string
{
    return capture('id -g');
}

function ctx(string $path, bool $allowFailure = false, array $env = []): Context
{
    return new Context(
        workingDirectory: $path,
        allowFailure: $allowFailure,
        environment: $env,
    );
}

#[AsTask(description: 'Génère les certificats TLS locaux (mkcert)')]
function certs(): void
{
    $root = rootDir();
    $certsDir = "{$root}/tools/dev/certs";

    run('mkcert -install', ctx($root, allowFailure: true));

    if (!file_exists("{$certsDir}/localhost.pem")) {
        run('mkcert localhost', ctx($certsDir));
    }
}

#[AsTask(description: "Lance l'environnement de développement")]
function dev(): void
{
    $root = rootDir();
    $backendEnv = "{$root}/apps/backend/.env";

    certs();

    // APP_URL en https
    if (!str_contains(file_get_contents($backendEnv), 'APP_URL=https')) {
        run("sed -i '' 's|^APP_URL=http://localhost|APP_URL=https://localhost|' {$backendEnv}", ctx($root));
    }

    // VITE_DEV_SERVER_URL
    if (!str_contains(file_get_contents($backendEnv), 'VITE_DEV_SERVER_URL')) {
        file_put_contents($backendEnv, "\nVITE_DEV_SERVER_URL=https://localhost\n", FILE_APPEND);
    }

    $baseEnv = ['WWWUSER' => wwwUser(), 'WWWGROUP' => wwwGroup()];

    run('docker compose down', ctx("{$root}/apps/backend", allowFailure: true, env: $baseEnv));
    run('docker compose down', ctx($root, allowFailure: true, env: $baseEnv));

    run('docker compose up -d', ctx($root, env: $baseEnv));
    run('docker compose up -d', ctx("{$root}/apps/backend", env: $baseEnv));
    run('docker compose --profile dev up -d', ctx("{$root}/apps/backend", env: $baseEnv));

    run('zellij delete-session ' . SESSION . ' --force', ctx($root, allowFailure: true));
    run('zellij --session ' . SESSION . ' --new-session-with-layout ' . $root . '/tools/dev/zellij/dev.kdl', new Context(workingDirectory: $root, tty: true));
}

#[AsTask(description: "Arrête l'environnement de développement")]
function stop(): void
{
    $root = rootDir();

    run('docker compose down', ctx("{$root}/apps/backend"));
    run('docker compose down', ctx($root));
    run('docker network rm bad_proxy', ctx($root, allowFailure: true));
    run('zellij kill-session ' . SESSION, ctx($root, allowFailure: true));
}