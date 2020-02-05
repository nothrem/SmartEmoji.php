<?php

namespace emoji;

require_once 'Unicode.php';
require_once 'Data.php';

use emoji\Unicode AS U;
use xml\Data;

class Sequences {
    protected const DEFAULT_DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji';
    protected const EMOJI_SEQUENCE_CHAR = '..'; //characters that separates list of emoji
    protected const EMOJI_MODIFIER_CHAR = ':'; //character that marks modified emoji (short name separator)

    protected const SEMICOLON_PLACEHOLDER = '"\u003B"'; //JSON-encoded UNICODE character that replaces semicolon (used as separator in the file)
    protected const HASHTAG_PLACEHOLDER = '\x{23}'; //encoded UNICODE character that replaces hashtag which is otherwise used for comments in the file; this can be used for any character but only the hashtag needs escaping

    protected $path;

    /**
     * Groups constructor.
     * @param string|null $path (optional; default: self::DEFAULT_DATA_DIR) Path where CLDR data are stored.
     */
    public function __construct(string $path = null) {
        $this->path = $path ?? self::DEFAULT_DATA_DIR;
    }

    /**
     * Load file with emoji categories
     *
     * @param string $name (optional, default: labels.txt) Change if you want to load and test different file.
     * @param string $path (optional, default: common\properties) Change if you want to load file from a different folder.
     * @return string Name of the file
     */
    protected function getFile(string $name, string $path = '') : string {
        $file = realpath($this->path . DIRECTORY_SEPARATOR . (empty($path) ? '' : $path . DIRECTORY_SEPARATOR) . $name);

        if (!file_exists($file)) {
            throw new \InvalidArgumentException("File $file not found. Please make sure the UNICODE sequences files are downloaded in data folder.");
        }

        return $file;
    }

    /**
     * Groups.txt uses semicolon as field separator so semicolon is encoded as another UNICODE character representing semicolon
     * @param string $string
     * @return string
     */
    protected static function fixSemicolon(string $string) : string {
        return str_replace(self::HASHTAG_PLACEHOLDER, '#', str_replace(json_decode(self::SEMICOLON_PLACEHOLDER), ';', trim($string)));
    }

    public static function getModifiedName(string $string) {
        $string = self::fixSemicolon($string);
        $string = explode(self::EMOJI_MODIFIER_CHAR, $string);
        foreach ($string as &$name) {
            $name = trim($name);
        }
        if (2 > count($string)) {
            $string[1] = null;
        }
        return $string;
    }

    public function parse(string $file, string $path = null) {
        $file = $this->getFile($file, $path ?? '');

        $data = file($file); //read whole file and parse it into array or rows
        $emoji = [];

        echo 'Processing emoji in file ', $file, PHP_EOL;

        foreach ($data as $row) {
            $row = trim($row);
            if ('' === $row || '#' === $row[0]) {
                continue; //this is empty row or a comment, ignore it
            }

            //Remove comments on the end of rows
            if ($pos = strpos($row, '#')) { //note: strpos() may return 0 as valid value but we have skipped such rows above so this condition is OK for non-FALSE values
                $row = substr($row, 0, $pos - 1); //copy only data, remove the comment
            }

            $matches = explode(';', $row);
            if (3 !== count($matches)) {
                echo 'Row has unexpected format: ', $row, PHP_EOL;
                continue;
            }
            $codepoint = trim($matches[0]);
            $type = self::fixSemicolon($matches[1]);
            [$name, $modifier] = self::getModifiedName($matches[2]);

            if (strpos($codepoint, ' ') && strpos($codepoint, self::EMOJI_SEQUENCE_CHAR)) {
                throw new \RuntimeException('Row containing sequence with multi-character emoji is not supported yet: ' . $row);
            }

            if ($pos = strpos($codepoint, self::EMOJI_SEQUENCE_CHAR)) { //note: strpos() may return 0 as valid value but sequence separator cannot be first so this condition is OK
                [$char, $end] = explode(self::EMOJI_SEQUENCE_CHAR, $codepoint);
                $char = U::ord(U::chrS($char)); //convert from hexa number to UTF-8 and then back to decimal number
                $end = U::ord(U::chrS($end));

                echo '  * Processing emoji sequence ', $char, ' - ', $end, ' (', U::codepoint($char), ' - ', U::codepoint($end), ') with name ', $name, ' ', $modifier, PHP_EOL;

                for (;$char <= $end; ++$char) {
                    echo '    * Found emoji ', U::chr($char), ' (', U::codepoint($char), ')', PHP_EOL;
                    $emoji[U::chr($char)] = [
                        Data::JSON_TYPE => Data::getType($type),
                        Data::JSON_NAME => $name,
                        Data::JSON_MODIFIER => $modifier,
                    ];
                }
            }
            else {
                $char = U::chrS($codepoint);

                if (1 < U::len($char)) {
                    echo '  * Found multi-character emoji ', $char, ' (', U::len($char), ' chars: ';
                    $split = [];
                    for ($j = 0, $count = U::len($char); $j < $count; ++$j) {
                        $split[] = U::codepoint($char, $j);
                    }
                    echo implode(', ', $split), ') with name ', $name, ' ', $modifier, PHP_EOL;
                }
                else {
                    echo '  * Found emoji ', $char, ' (', U::codepoint($char), ') with name ', $name, ' ', $modifier, PHP_EOL;
                }
                $emoji[$char] = [
                    Data::JSON_TYPE => Data::getType($type),
                    Data::JSON_NAME => $name,
                    Data::JSON_MODIFIER => $modifier,
                ];
            }
        }

        echo PHP_EOL, 'Found ', count($emoji) . ' emoji.', PHP_EOL;

        $isset = function($a) { return isset($a); }; //by default array_filter() removes all false-like elements but we need to keep values "0"
        foreach ($emoji as &$char) {
            $char = array_filter($char, $isset); //remove NULL values which represent default ones
        }

        echo PHP_EOL, 'Finished processing sequence', PHP_EOL;

        return $emoji;
    }
}
