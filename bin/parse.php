<?php

use xml\Data;

//PHP 7.2 is required
if (PHP_VERSION_ID < 70200) {
    die('PHP 7.2 is required!');
}
if (!function_exists('mb_chr') || !\extension_loaded('mbstring')) {
    die('PHP extension mbstring is required');
}
$langs = $argv[1] ?? [];

set_include_path(__DIR__.'/../php');

//Find all emoji defined by UNICODE
require_once 'Sequences.php';
require_once 'Unicode.php';

use emoji\Unicode AS U;
$sequence = new \emoji\Sequences();
$emoji = $sequence->parse('emoji-sequences.txt');
$emoji = array_merge($emoji, $sequence->parse('emoji-zwj-sequences.txt'));

//Process emoji groups
require_once 'Groups.php';

$groups = new \emoji\Groups();
$groups = $groups->parse();
$emojiStat = array_pop($groups);
$allEmojiInGroupList=array_pop($groups);
$filters = array_shift($groups);

array_shift($emojiStat);       // Remove filter group

//Process emoji annotations (translated names)
$annotations = [];
$modified = [];
$words = [];
if (empty($langs)) {
    echo 'Parameter with language list not found, skipping translation processing.', PHP_EOL;
}
else {
    require_once 'Annotations.php';

    $annotations = new \emoji\Annotations();
    $words = $annotations->parseCsv('sk3-emoji.csv',true);

//   echo 'SK3-CSV=',var_dump($words);

    $annotations = $annotations->parse($langs, $emoji);

//Process group translations
    require_once 'Main.php';

    $main = new \emoji\Main();
    $main->load($langs);

    $groupTranslations = [];

    $main->getCharacterLabel('en', 'activities'); //just a random label to preload the labels from XMLs
    echo PHP_EOL, 'Translating emoji groups...', PHP_EOL;

    foreach ($main->getLanguages() as $lang) {
        $thislang = explode('-',$lang);
        $curLang = mb_strtoupper($thislang[0]);
        if (!array_key_exists($lang, $groupTranslations)) {
            $groupTranslations[$lang] = $groups;
        }
//  Checking of not found emojies in Annotation block

//        $notFound = 0;
//        foreach ($annotations[$lang] as $char => $annotation) {
//            if (!in_array ( $char, $allEmojiInGroupList, true )) {
//                $notFound++;
//                $newEmoji = [
//                    'i'=>$char,
//                    'n'=>$annotation['n'],
//                ];
//                is_null($annotation['k']) ? null : $newEmoji['k']=$annotation['k'] ;
//                $groupTranslations[$lang]['other'][Data::JSON_LIST]['other'][Data::JSON_LIST][] = $newEmoji;
//                echo 'Warning: emoji ', $char, ' not found in Sequences.', PHP_EOL;
//                fwrite(STDERR, 'Warning: emoji ' . $char . ' not found in Sequences.' . PHP_EOL);
//            }
//        }

        foreach ($groupTranslations[$lang] as &$curGroup) {
            echo '  Translating group ', $curGroup[Data::JSON_NAME], ' into language ', $lang, '...';
            $transWord = $main->getCharacterLabel($lang, $curGroup[Data::JSON_NAME]);
            is_null($transWord) ? null :  $curGroup[Data::JSON_NAME] = $transWord;
            echo ' Translation: "', $curGroup[Data::JSON_NAME], '"', PHP_EOL;

//            echo '  Translating group ', $curGroup[Data::JSON_NAME], ' into language ', $lang, '...';
//            $curGroup[Data::JSON_NAME]=$words[$curLang][$curGroup[Data::JSON_NAME]];
//           is_null($words[$curLang][$curGroup[Data::JSON_NAME]]) ? null :  $curGroup[Data::JSON_NAME]=$words[$curLang][$curGroup[Data::JSON_NAME]];
//            echo ' Translation: "', $words[$curLang][$curGroup[Data::JSON_NAME]], '"', PHP_EOL;

            foreach ($curGroup[Data::JSON_LIST] as &$curSubGroup) {
                echo '  Translating subgroup ', $curSubGroup[Data::JSON_NAME], ' into language ', $lang, '...';
                is_null($words[$curLang][$curSubGroup[Data::JSON_NAME]]) ? null :  $curSubGroup[Data::JSON_NAME]=$words[$curLang][$curSubGroup[Data::JSON_NAME]];
                echo ' Translation: "', $curSubGroup[Data::JSON_NAME], '"', PHP_EOL;
            }
        }
    }
    echo PHP_EOL, 'Finished translating emoji groups.', PHP_EOL, PHP_EOL;

    //Check if Annotations and Sequences match
    foreach ($main->getLanguages() as $lang) {
        $thislang = explode('-',$lang);
        $curLang = mb_strtoupper($thislang[0]);
        $annotationL = $annotations[$lang];
        $filter[$lang]=$filters;
        foreach($groupTranslations[$lang] as &$curGroup) {
            foreach ($curGroup[Data::JSON_LIST] as &$subGroup) {
                foreach ($subGroup[Data::JSON_LIST] as $key=>&$value){
                    if ( U::codelast($key)=='fe0f') {
                        $key = U::trimlast($key);
                    }
                    is_null($annotationL[$key][Data::JSON_NAME]) ? null : $value[Data::JSON_NAME]=$annotationL[$key][Data::JSON_NAME];
                    is_null($annotationL[$key][Data::JSON_KEYWORDS]) ? null : $value[Data::JSON_KEYWORDS]=$annotationL[$key][Data::JSON_KEYWORDS];
                }
            }
        }

 // Translate of filters
        foreach ($filter[$lang][Data::JSON_LIST] as &$subGroup) {
            foreach ($subGroup[Data::JSON_LIST] as $key=>&$value){
                if (array_key_exists($value[Data::JSON_NAME], $annotationL)) {
                    $value[Data::JSON_NAME]=$annotationL[$value[Data::JSON_NAME]][Data::JSON_NAME];
                }
            }
            $subGroup[Data::JSON_NAME]=$words[$curLang][$subGroup[Data::JSON_NAME]];
        }

        // Implementing of the modifiers
        foreach ($groupTranslations[$lang] as &$curGroup) {
            foreach ($curGroup[Data::JSON_LIST] as &$subGroup) {
                foreach ($subGroup[Data::JSON_LIST] as $key => &$value) {
                    if ($value[Data::JSON_MODIFIER] !== null) {
                        $thisKey =  substr(trim($value[Data::JSON_MODIFIER]), 0,-1) ;
                        if ($thisKey !== '') {
                            if (!array_key_exists($thisKey, $modified)) {
                                $modified[$thisKey] = [
                                    Data::JSON_LIST => [],
                                ];
                            }
                            $modified[$thisKey][Data::JSON_LIST][$prevEmoji] = $key;
                            unset($subGroup[Data::JSON_LIST][$key]);
                        }
                    }else {
                        $prevEmoji = $key;
                    }
                }
            }
        }
    }
//    echo 'MODIFIER=',var_dump($modified);
    //Creation of KEYWORDS object
    $keywords = [];
    $indexmain=[];
    $indexsub=[];
    $search=[];

    foreach ($main->getLanguages() as $lang) {
        $keywords[$lang] = [];
        $dictionary[$lang] = [];
        $roots[$lang]=[];
        $i=1;
        $indexmain=array_keys($groupTranslations[$lang]);

        foreach ($groupTranslations[$lang] as &$curGroup) {
            $j=0;
            $thisKey = mb_strtolower($curGroup[Data::JSON_NAME]);
            if (!array_key_exists($thisKey, $keywords[$lang])) {
                $keywords[$lang][$thisKey] = [];
            }
            $keywords[$lang][$thisKey][] = U::codeWrapper($i,$j);
//          echo $thisKey.' i='.$i.' j='.$j.' $k='.$k.' $svertka='.$svertka.PHP_EOL;
            $j=1;
            $indexsub[$i-1]=array_keys($curGroup[Data::JSON_LIST]);

            foreach ($curGroup[Data::JSON_LIST] as &$subGroup) {
                $k=0;
                $thisKey = mb_strtolower($subGroup[Data::JSON_NAME]);
                if (!array_key_exists($thisKey, $keywords[$lang])) {
                    $keywords[$lang][$thisKey] = [];
                }
                $keywords[$lang][$thisKey][] = U::codeWrapper($i,$j);
//              echo $thisKey.' i='.$i.' j='.$j.' $k='.$k.' $svertka='.$svertka. PHP_EOL;
                $k=1;

                foreach ($subGroup[Data::JSON_LIST] as $key => &$value) {
                    $thisKeys=[];
                    if(array_key_exists(Data::JSON_KEYWORDS, $value)) $thisKeys = explode(Data::JSON_KEY_DELIM, $value[Data::JSON_KEYWORDS]);
                    $thisKeys[] = $value[Data::JSON_NAME];

                    foreach ($thisKeys as $thisKey) {
                        $thisKey = mb_strtolower($thisKey);
                        if($thisKey!=='') {
                            if (!array_key_exists($thisKey, $keywords[$lang])) {
                                $keywords[$lang][$thisKey] = [];
                            }
//                          $keywords[$lang][$thisKey][] = $key;
                            $keywords[$lang][$thisKey][] = U::codeWrapper($i,$j,$k);
                        }
                    }
                    unset($value[Data::JSON_KEYWORDS]);
                    $k++;
                }
                $j++;
            }
            $i++;
        }
        ksort($keywords[$lang], SORT_LOCALE_STRING);
        foreach($keywords[$lang] as $key=>$value){
           if(strpos($key,' ')){
               $thisKeys = explode(' ', $key);
               foreach ($thisKeys as $thisKey) {
                   $pattern = str_replace(['"','«','»','”','“'],'',$thisKey);
                   if(strlen($pattern)>=3) {
                       $dictionary[$lang][$pattern][] = $value;
                   }
               }
           }
        }
        ksort($dictionary[$lang], SORT_LOCALE_STRING);
        $search = $dictionary[$lang];
        foreach($dictionary[$lang] as $key=>$value){
            $needle = array_shift($search);
            foreach($search as $word=>$code){
                $result = U::rootFinder($key,$word);
                if ($result){
                    $howmany = count($result);
                    for($k=0;$k<$howmany;$k++){
                        if(!array_key_exists($result[$k],$roots[$lang])){
                            $roots[$lang][$result[$k]]=array_merge($roots[$lang][$result[$k]],$value);
                        }
                        $roots[$lang][$result[$k]]=array_merge($roots[$lang][$result[$k]],$code);
                    }
                }
                unset($result);
            }
        }
        echo 'ROOT=',var_dump($roots[$lang]);
    }

//    $result = U::rootFinder('mankind','kinderman');


    foreach ($emoji as $char => $annotation) {
        foreach ($main->getLanguages() as $lang) {
            if (!array_key_exists($char, $annotations[$lang])) {
                echo 'Warning: emoji ', $char, ' not found in Annotations of language ', $lang, '.', PHP_EOL;
                fwrite(STDERR, 'Warning: emoji ' . $char . ' not found in Annotations of language ' . $lang . '.' . PHP_EOL);
            }
        }
    }

//Save emoji and group translations into file
    if ($main!==null) {
        foreach ($main->getLanguages() as $lang) {
            $annotationFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'emoji' . DIRECTORY_SEPARATOR . 'groups.' . $lang . '.json';
            echo 'Saving ', count($allEmojiInGroupList ?? []), ' emoji and ', count($groupTranslations[$lang] ?? []), ' groups into file ', $annotationFile, PHP_EOL;
            file_put_contents($annotationFile, json_encode([
                'groups' => $groupTranslations[$lang] ?? [],
                'filters' => $filter[$lang][Data::JSON_LIST] ?? [],
                'keywords' => $keywords[$lang] ?? [],
                'dictionary' => $dictionary[$lang] ?? [],
                'roots' => $roots[$lang] ?? [],
                'mode' => $modified ?? [],
                'indexgroup' => $indexmain ?? [],
                'indexsub'   => $indexsub ?? [],
            ], JSON_THROW_ON_ERROR + JSON_UNESCAPED_UNICODE)); //Crash on invalid JSON instead of creating empty file

        }

// Save emoji statistic information into file emoji-statistic.txt
        $statisticFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'emoji' . DIRECTORY_SEPARATOR . 'emoji-statistics' . '.txt';
        $handle = fopen($statisticFile, 'c');
        if ($handle){
            fwrite ( $handle, PHP_EOL . 'groups: '.count($emojiStat). PHP_EOL);
            fwrite ( $handle, PHP_EOL . 'emojis: '.count($allEmojiInGroupList). PHP_EOL);
            foreach ($emojiStat as $name => $group) {
                fwrite ( $handle, PHP_EOL . $emojiStat[$name][Data::JSON_ROW].PHP_EOL);
                foreach ($group[Data::JSON_LIST] as $type => $subGroup) {
                    fwrite ( $handle, $emojiStat[$name][Data::JSON_LIST][$type][Data::JSON_ROW]);
                }
            }
            fclose($handle);
        }

    }
}

//Everything done
echo 'Done.', PHP_EOL;

