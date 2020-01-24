<?php


namespace emoji;

use xml\Data;

require_once 'Helper.php';
require_once 'Data.php';

class Annotations extends \xml\Helper {
    protected $emoji = [];

    public function parse(string $langs) {
        $langs = $this->getLanguageList($langs, ['annotationsDerived', 'annotations']);
        $emoji = [];

        foreach ($langs as $lang => $files) {
            if (!array_key_exists($lang, $this->emoji)) {
                $this->emoji[$lang] = [];
            }

            foreach ($files as $file) {
                $this->parseFile($file, $lang);
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
                $emoji[$char]['name'] = $annotation->value;
            }
            else {
                $keywords = array_map('trim', explode('|', $annotation->value)); //remove leading and trailing spaces
                echo ' emoji keywords are "', implode(', ', $keywords), '"';
                $emoji[$char]['keywords'] = implode('|', $keywords);
            }
            echo PHP_EOL;
        }
    }
}
