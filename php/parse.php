<?php

//PHP 7.1 is required
try {
    if (PHP_VERSION_ID < 70100) {
        throw new Exception('PHP 7.1 is required!');
    }
} catch (\Exception $e) {
    die($e->getMessage());
}

$langs = $argv[1] ?? [];

//Find all emoji defined by UNICODE
require_once 'Sequences.php';

$sequence = new \emoji\Sequences();
$emoji = $sequence->parse('emoji-sequences.txt');
$emoji = array_merge($emoji, $sequence->parse('emoji-zwj-sequences.txt'));

//Process emoji groups
require_once 'Groups.php';

$groups = new \emoji\Groups();
$groups = $groups->parse();

//Move filter into separate key
foreach ($groups as $key => $group) {
    if ('filter' === $group['id']) {
        $filter = $group;
        unset($groups[$key]);
        break;
    }
}
$groups = array_values($groups); //reset keys, otherwise JSON would encoded it as object instead of array

//Process emoji annotations (translated names)
$annotations = [];
if (empty($langs)) {
    echo 'Parameter with language list not found, skipping translation processing.', PHP_EOL;
}
else {
    require_once 'Annotations.php';

    $annotations = new \emoji\Annotations();
    $annotations = $annotations->parse($langs, $emoji);

//Process group translations
    require_once 'Main.php';

    $main = new \emoji\Main();
    $main->load($langs);

    $groupTranslations = [];

    //Check if Annotations and Sequences match
    foreach ($main->getLanguages() as $lang) {
        foreach ($annotations[$lang] as $char => $annotation) {
            if (!array_key_exists($char, $emoji)) {
                echo 'Warning: emoji ', $char, ' not found in Sequences.', PHP_EOL;
                fwrite(STDERR, 'Warning: emoji ' . $char . ' not found in Sequences.' . PHP_EOL);
            }
        }
    }
    foreach ($emoji as $char => $annotation) {
        foreach ($main->getLanguages() as $lang) {
            if (!array_key_exists($char, $annotations[$lang])) {
                echo 'Warning: emoji ', $char, ' not found in Annotations of language ', $lang, '.', PHP_EOL;
                fwrite(STDERR, 'Warning: emoji ' . $char . ' not found in Annotations of language ' . $lang . '.' . PHP_EOL);
            }
        }
    }

    $main->getCharacterLabel('en', 'activities'); //just a random label to preload the labels from XMLs
    echo PHP_EOL, 'Translating emoji groups...', PHP_EOL;

    foreach ($main->getLanguages() as $lang) {
        foreach ($groups as $group) {
            echo '  Translating group ', $group['id'], ' into language ', $lang, '...';
            if (!array_key_exists($lang, $groupTranslations)) {
                $groupTranslations[$lang] = [];
            }
            $groupTranslations[$lang][$group['id']] = $main->getCharacterLabel($lang, $group['id']);
            echo ' Translation: "', $groupTranslations[$lang][$group['id']], '"', PHP_EOL;
        }
    }

    echo PHP_EOL, 'Finished translating emoji groups.', PHP_EOL, PHP_EOL;

}
//Save groups into file
$groupsFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'groups.json';

echo 'Saving ', count($groups ?? []), ' groups and ', count($filter ?? []), ' filters into file ', $groupsFile, PHP_EOL;
file_put_contents($groupsFile, json_encode([
    'filter' => $filter[\xml\Data::JSON_LIST],
    'groups' => $groups,
]));

//Save emoji and group translations into file
if (!empty($main)) {
    foreach ($main->getLanguages() as $lang) {
        $annotationFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji.' . $lang . '.json';

        echo 'Saving ', count($annotations[$lang] ?? []), ' emoji and ', count($groupTranslations[$lang] ?? []), ' groups into file ', $annotationFile, PHP_EOL;
        file_put_contents($annotationFile, json_encode([
            'emoji'  => $annotations[$lang] ?? [],
            'groups' => $groupTranslations[$lang] ?? [],
        ], JSON_THROW_ON_ERROR)); //Crash on invalid JSON instead of creating empty file
    }
}


//Everything done
echo 'Done.', PHP_EOL;
