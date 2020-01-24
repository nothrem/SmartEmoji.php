<?php

//PHP 7.1 is required
try {
    if (PHP_VERSION_ID < 70100) {
        throw new Exception('PHP 7.1 is required!');
    }
} catch (\Exception $e) {
    die($e->getMessage());
}

$langs = $argv[1];

//Process emoji groups
require_once 'Groups.php';

$groups = new \emoji\Groups();
$groups = $groups->parse();

//Process emoji annotations
$annotations = [];
require_once 'Annotations.php';

$annotations = new \emoji\Annotations();
$annotations = $annotations->parse($langs);

//Process group translations
require_once 'Main.php';

$main = new \emoji\Main();
$main->load($langs);

$groupTranslations = [];

$main->getCharacterLabel('en', 'activities'); //just a random label to preload the labels from XMLs
echo PHP_EOL, 'Translating emoji groups...', PHP_EOL;

foreach ($main->getLanguages() as $lang) {
    foreach ($groups as $group => $data) {
        echo '  Translating group ', $group, ' into language ', $lang, '...';
        if (!array_key_exists($lang, $groupTranslations)) {
            $groupTranslations[$lang] = [];
        }
        $groupTranslations[$lang][$group] = $main->getCharacterLabel($lang, $group);
        echo ' Translation: "', $groupTranslations[$lang][$group], '"', PHP_EOL;
    }
}

echo PHP_EOL, 'Finished translating emoji groups.', PHP_EOL, PHP_EOL;

//Save groups into file
$groupsFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'groups.json';

echo 'Saving ', count($groups ?? []), ' groups into file ', $groupsFile, PHP_EOL;
file_put_contents($groupsFile, json_encode([
    'groups' => $groups
]));

//Save emoji and group translations into file
foreach ($main->getLanguages() as $lang) {
    $annotationFile =__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji.' . $lang . '.json';

    echo 'Saving ', count($annotations[$lang] ?? []), ' emoji and ', count($groupTranslations[$lang] ?? []), ' groups into file ', $annotationFile, PHP_EOL;
    file_put_contents($annotationFile, json_encode([
        'emoji' => $annotations[$lang] ?? [],
        'groups' => $groupTranslations[$lang] ?? [],
    ]));
}


//Everything done
echo 'Done.', PHP_EOL;
