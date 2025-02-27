#!/usr/bin/env php
<?php

if (array_key_exists(1, $argv) && 'fix' === $argv[1]) {
    $fix = true;
} else {
    $fix = false;
}

if ($fix) {
    $cmd = __DIR__.'/vendor/bin/php-cs-fixer --allow-risky=yes --config=\.php-cs-fixer.dist.php '.
        '--diff --verbose --using-cache=no fix ';
} else {
    $cmd = __DIR__.'/vendor/bin/php-cs-fixer --allow-risky=yes --config=\.php-cs-fixer.dist.php --dry-run '.
        '--diff --verbose --using-cache=no fix ';
}

exec($cmd.'./', $output, $resultCode);
if (0 !== $resultCode) {
    foreach ($output as $value) {
        echo $value."\n";
    }
}
exit($resultCode);
