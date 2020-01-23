<?php

//PHP 7.1 is required
try {
    if (PHP_VERSION_ID < 70100) {
        throw new Exception('PHP 7.1 is required!');
    }
} catch (\Exception $e) {
    die($e->getMessage());
}

//Process emoji groups
require_once 'Groups.php';

$groups = new \emoji\Groups();
$groups = $groups->parse();

$groupsFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'groups.json';

file_put_contents($groupsFile, json_encode([
    'groups' => $groups
]));

//Everything done
echo 'Done.', PHP_EOL;
