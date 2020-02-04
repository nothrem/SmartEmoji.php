<?php


namespace emoji;

require_once 'Helper.php';
require_once 'Data.php';
require_once 'Unicode.php';

use xml\Data;

class Annotations extends \xml\Helper {
    protected $emoji = [];

    public function parse(string $langs, array $emoji = []) {
        $langs = $this->getLanguageList($langs, ['annotationsDerived', 'annotations']);

        foreach ($langs as $lang => $files) {
            if (!array_key_exists($lang, $this->emoji)) {
                $this->emoji[$lang] = $emoji;
            }

            foreach ($files as $file) {
                $this->parseFile($file, $lang);
            }

            foreach ($this->emoji[$lang] as &$data) {
                if (array_key_exists(Data::JSON_KEYWORDS, $data)) {
                    $keywords = explode('|', $data[Data::JSON_KEYWORDS]);
                    $data[Data::JSON_KEYWORDS] = [];
                    $name = Unicode::low($data[Data::JSON_NAME]);
                    foreach ($keywords as $i => $keyword) {
                        $keyword = Unicode::low($keyword); //use lower case keywords (here and also in JSON)
                        if (false === strpos($name, $keyword)) { //skip keywords that are already container in name
                            $data[Data::JSON_KEYWORDS][$keyword] = $keyword; //remove duplicate keywords (e.g. with different case)
                        }
                    }
                    if (empty($data[Data::JSON_KEYWORDS])) {
                        unset($data[Data::JSON_KEYWORDS]);
                    }
                    else {
                        $data[Data::JSON_KEYWORDS] = implode('|', array_values($data[Data::JSON_KEYWORDS]));
                    }
                }
            }

        }

        return $this->emoji;
    }

    protected function parseFile(string $file, string $lang) {
        echo 'Looking for annotations in file ', $file, PHP_EOL;
        $xml = new Data($file);

        try {
            $annotations = $xml->filterXml('ANNOTATION');
        }
        catch (\RuntimeException $e) {
            echo '  No annotations found: ', $e->getMessage(), PHP_EOL;
            return; //no annotations in the file, skip it
        }

        $emoji = &$this->emoji[$lang]; //get reference into the emoji sub-array

        foreach ($annotations as $i => $annotation) {
            if (empty($annotation->attributes->CP) || empty($annotation->value)) {
                throw new \RuntimeException("Invalid annotation $i.");
            }

            $char = $annotation->attributes->CP;
            echo '  Processing annotation for emoji ', $char, '... ';

            if (!array_key_exists($char, $emoji)) {
                $emoji[$char] = [];
            }

            if (isset($annotation->attributes->TYPE) && 'tts' === $annotation->attributes->TYPE) {
                echo ' emoji name is "', $annotation->value, '"';
                [$name, $modifier] = Sequences::getModifiedName($annotation->value);
                $emoji[$char][Data::JSON_NAME] = Unicode::upFirst($name);
                if (!empty($modifier)) {
                    $emoji[$char][Data::JSON_MODIFIER] = $modifier;
                }
            }
            else {
                $keywords = array_map('trim', explode('|', $annotation->value)); //remove leading and trailing spaces
                echo ' emoji keywords are "', implode('|', $keywords), '"';
                $emoji[$char][Data::JSON_KEYWORDS] = implode('|', $keywords);
            }
            echo PHP_EOL;
        }
    }
}
