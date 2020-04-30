<?php


namespace emoji;

require_once 'Helper.php';
require_once 'Data.php';
require_once 'Unicode.php';

use xml\Data;

class Annotations extends \xml\Helper {
    protected $emoji = [];
    protected const DEFAULT_DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji';
    public const CSV_HEADER = "\xEF\xBB\xBF"; // this is UTF-8 header to force Excel to recognize national characters in CSV file
    public const CSV_VALUE_SEPARATOR = ','; // ; is recommended for Excel

    public function parse(string $langs, array $emoji = []) {
        $langs = $this->getLanguageList($langs, ['annotationsDerived', 'annotations']);

        foreach ($langs as $lang => $files) {
            if (!array_key_exists($lang, $this->emoji)) {
                $this->emoji[$lang] = $emoji;
            }

            foreach ($files as $file) {
                echo 'BEGIN LANG=',$lang,'_FILES= ',$file,' COUNT= ',count($this->emoji[$lang]),PHP_EOL;
                $this->parseFile($file, $lang);
                echo 'END LANG=',$lang,'_FILES= ',$file,' COUNT= ',count($this->emoji[$lang]),PHP_EOL;
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
            echo '  =TOTAL= annotations in ',$lang,' found: ', count($this->emoji[$lang]), PHP_EOL;
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

            //Annotations may contain keycap emoji without the variation selector so we should fix it
            if (Unicode::contains($char, Unicode::chrS('20e3')) && !Unicode::contains($char, Unicode::chrS('fe0f'))) {
                $char = Unicode::char($char) . Unicode::chrS('fe0f') . Unicode::chrS('20e3');
            }

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

    /**
     * Reads CSV files and converts it into 2-dimensional array.
     *
     * @param  string $filename Path and name of a CSV file. To read input use 'php://stdin' (CLI) or 'php://input' (POST). Other protocol wrappers may be supported as well.
     * @param  bool|array[string] $keys (optional, default: false)
     *                                  False = Just convert the file to array without any additional processing.
     *                                  True = Consider first line of the file as column names and use them as array keys.
     *                                  When array given it will be used as array keys for the rows (including the first one).
     *                                  Rows with more columns than $keys will be trimmed, rows with less columns will be skipped!!!
     * @param  bool $strictIndex (optional, default: false)
     *                                  False = output array (in 1st dimension) will have numeric keys from 0 to maxRows.
     *                                  True  = output array (in 1st dimension) will have keys starting with 1
     *                                          and indexes of empty or otherwise skipped rows will be missing in the array.
     *                                  Example: [1 => [...], 2 => [...], 5 => [...]]; //rows 3 and 4 were empty
     *                                  When both $keys and $stringIndex are True the output array will start with index 2
     *                                   (because the first row with column names will be skipped).
     *                                  Strict index follows row numbering used for files in IDEs and is intended for debugging.
     * @return array[array[string]] Parsed file. Empty rows are not included. First row is not included when $keys == true.
     */
    public static function parseCsv(string $filename, $keys = false, $strictIndex = false) : array {
        $filename = self::DEFAULT_DATA_DIR . DIRECTORY_SEPARATOR . $filename;
        if (!is_readable($filename)) { //When file not exists...
            return [];
        }

        $f = fopen($filename, 'r');
        $output = [];
        $rowIndex = 0;
        $firstRow = true; //to detect when we read the very first row in the file

        if (false === $f) { //When failed to load the file...
            return [];
        }

        try {
            ini_set('auto_detect_line_endings', true); //allow to parse other OS's line endings
            if (true === $keys) {
                $keys = fgetcsv($f, 0, self::CSV_VALUE_SEPARATOR);
                ++$rowIndex;
                if (count($keys)) { //remove BOM from keys
                    if (strncmp($keys[0], self::CSV_HEADER, strlen(self::CSV_HEADER)) === 0) {
                        $pos = strpos($keys[0], self::CSV_HEADER);
                        $keys[0] = $pos === false ? false : (string) substr($keys[0], $pos + strlen(self::CSV_HEADER));
                    }
                    $firstRow = false;
                }
            }
            while ($row = fgetcsv($f, 0, self::CSV_VALUE_SEPARATOR)) {
                ++$rowIndex;
                if (empty($row)) {
                    continue; //ignore empty rows (returned as empty array)
                }
                if ($firstRow) { //remove BOM from first row
                    if (strncmp($row[0], self::CSV_HEADER, strlen(self::CSV_HEADER)) === 0) {
                        $pos = strpos($row[0], self::CSV_HEADER);
                        $row[0] = $pos === false ? false : (string) substr($row[0], $pos + strlen(self::CSV_HEADER));
                    }
                    $firstRow = false;
                }

                if (is_array($keys)) { //append keys for each row
                    $row = array_combine($keys, array_slice($row, 0, count($keys)));
                    if (false === $row) {
                        continue; //row does not have enough cells required by $keys
                    }
                }

                if ($strictIndex) {
                    $output[$rowIndex] = $row;
                }
                else {
  //                  $output[] = $row;
                    foreach($row as $lang=>$value){
                        if ($lang==='key'){
                            $thiskey = $row[$lang];
                        } else{
                            $output[$lang][$thiskey] = $value;
                        }

                    }

                }
            }
        }
        finally {
            fclose($f);
        }

        return $output;
    }


}
