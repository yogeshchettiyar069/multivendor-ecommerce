<?php

declare(strict_types=1);

/*
| Blocks until the configured MongoDB deployment reports a writable primary,
| which means the single-node replica set has been initiated and transactions
| are available. Uses the low-level driver (always present with ext-mongodb)
| so it has no Laravel/bootstrap dependencies.
*/

$uri = getenv('MONGODB_URI') ?: 'mongodb://mongo:27017';
$attempts = (int) (getenv('MONGO_WAIT_ATTEMPTS') ?: 60);
$delaySeconds = 2;

for ($i = 1; $i <= $attempts; $i++) {
    try {
        $manager = new MongoDB\Driver\Manager($uri, ['serverSelectionTimeoutMS' => 2000]);
        $command = new MongoDB\Driver\Command(['hello' => 1]);
        $result = $manager->executeCommand('admin', $command);
        $info = current($result->toArray());

        if (! empty($info->isWritablePrimary)) {
            echo "[wait-for-mongo] primary is ready (attempt {$i}).\n";
            exit(0);
        }

        echo "[wait-for-mongo] connected but no writable primary yet (attempt {$i}/{$attempts}).\n";
    } catch (Throwable $e) {
        echo "[wait-for-mongo] not ready (attempt {$i}/{$attempts}): {$e->getMessage()}\n";
    }

    sleep($delaySeconds);
}

fwrite(STDERR, "[wait-for-mongo] timed out waiting for a writable primary.\n");
exit(1);
