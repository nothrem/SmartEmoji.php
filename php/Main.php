<?php

namespace emoji;

use xml\Data;

require_once 'Helper.php';
require_once 'Data.php';

class Main extends \xml\Helper {
    protected const DEFAULT_DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'core';
    protected const XML_EXT = '.xml';

    protected $files = [];
    protected $labels = [];

    public function load(string $langs) {
        $this->files = $this->getLanguageList($langs, ['main']);
    }

    protected function getLabels() {
        if (empty($this->labels)) {
            $this->labels = [];
            foreach ($this->files as $lang => $files) {
                echo PHP_EOL, 'Loading character labels for language ', $lang, PHP_EOL;
                $this->labels[$lang] = [];
                /** @var Data $xml */
                foreach ($files as $file) {
                    echo '  Processing file ', $file, PHP_EOL;
                    $xml = new Data($file);
                    try {
                        $labels = $xml->filterXml('characterLabel');
                        foreach ($labels as $label) {
//                            echo 'Processing label '; var_dump($label); echo PHP_EOL;
                            if (isset($label->attributes->TYPE, $label->value)) {
                                echo '    Found label ', $label->attributes->TYPE, ' with translation ', $label->value, PHP_EOL;
                                $this->labels[$lang][$label->attributes->TYPE] = $label->value;
                            }
                        }
                    }
                    catch (\Exception $e) {
                        echo '    No labels found.', PHP_EOL;
                        continue; //this file does not contain any labels, skip it
                    }
                }
            }
            echo 'Finished loading character labels.', PHP_EOL;
        }
        return $this->labels;
    }

    public function getLanguages() {
        return array_keys($this->files);
    }

    public function getCharacterLabel($lang, $name) : ?string {
        $name = $this->getLabels()[$lang][$name] ?? null;
        return $name ? ucfirst($name) : null;
    }
}
