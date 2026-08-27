<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'push:vapid';

    protected $description = 'Generate VAPID keys for browser push notifications';

    public function handle(): int
    {
        if (PHP_OS_FAMILY === 'Windows' && ! getenv('OPENSSL_CONF')) {
            $phpDirectory = dirname((string) php_ini_loaded_file());
            foreach ([$phpDirectory.'/extras/openssl/openssl.cnf', $phpDirectory.'/extras/ssl/openssl.cnf'] as $config) {
                if (is_file($config)) {
                    putenv('OPENSSL_CONF='.$config);
                    break;
                }
            }
        }

        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $exception) {
            $script = <<<'JS'
const crypto = require('crypto');
const { publicKey, privateKey } = crypto.generateKeyPairSync('ec', { namedCurve: 'prime256v1' });
const publicJwk = publicKey.export({ format: 'jwk' });
const privateJwk = privateKey.export({ format: 'jwk' });
const rawPublicKey = Buffer.concat([
  Buffer.from([4]),
  Buffer.from(publicJwk.x, 'base64url'),
  Buffer.from(publicJwk.y, 'base64url'),
]);
process.stdout.write(JSON.stringify({ publicKey: rawPublicKey.toString('base64url'), privateKey: privateJwk.d }));
JS;
            $result = Process::run(['node', '-e', $script]);
            if ($result->failed()) {
                throw $exception;
            }
            $keys = json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
        }
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);

        return self::SUCCESS;
    }
}
