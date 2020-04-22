<?php

namespace emoji;

require_once 'Unicode.php';
use emoji\Unicode AS U;
use xml\Data;

class Groups {
    public const DELIMITER  = '$';
    public const QUALIFIED  = 'fully';

    protected const DEFAULT_DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'emoji';
    protected const EMOJI_SEQUENCE_CHAR = '-'; //characters that separates list of emoji
    protected const EMOJI_DERIVED_START = '{'; //characters that starts multi-character emoji
    protected const EMOJI_DERIVED_END = '}';   //characters that ends multi-character emoji
    protected const SEMICOLON_PLACEHOLDER = '"\u003B"'; //JSON-encoded UNICODE character that replaces semicolon (used as separator in the file)

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
    protected function getLabelFile(string $name = 'emoji-test-icons.txt', string $path = NULL) : string {
        $file = realpath($this->path . DIRECTORY_SEPARATOR . ($path ? $path . DIRECTORY_SEPARATOR : '') . $name);

        if (!file_exists($file)) {
            throw new \InvalidArgumentException("File $file not found. Please make sure the UNICODE CLDR package is extracted in data folder.");
        }

        return $file;
    }

    /**
     * Groups.txt uses semicolon as field separator so semicolon is encoded as another UNICODE character representing semicolon
     * @param string $string
     * @return string
     */
    protected static function fixSemicolon(string $string) : string {
        return str_replace(json_decode(self::SEMICOLON_PLACEHOLDER), ';', $string);
    }

    protected static function getGroupName(string $group) {
        $group = preg_replace('/\s+/', '', $group); //works as trim() but also between words
        $group = strtolower($group);
        $group = str_replace(array("&", "-"), '_', $group);
        $group = self::fixSemicolon($group);
        return $group;
    }

    /**
     * Convert PHP indexed array into ordered array compatible with JSON
     * Note: in PHP array with string keys is always ordered but in JSON it would convert to unordered object; see data\README.MD for details.
     *
     * @param array $groups
     * @return array
     */
    protected static function orderGroups(array $groups, array $icons) {
        $output = [];
        foreach ($groups as $name => $group) {
            if (is_array($group)) {
                $output[] = [
                    Data::JSON_ICON => $icons[$name],
                    Data::JSON_NAME => $name,
                    Data::JSON_LIST => self::orderGroups($group,$icons),
                ];
            }  elseif (is_numeric($name)) {
                $output[] = $group;
            }
            else {
                throw new \RuntimeException('Unexpected group key '.$name.' with value '.var_export($group, true));
            }
        }

        return $output;
    }

    protected static function getAllModifiers(array $group) {
        $output = [];
        foreach ($group[Data::JSON_LIST] as $subgroup) {
            foreach ($subgroup[Data::JSON_LIST] as $key => $value ){
                $output[$key] = trim($value[Data::JSON_MODIFIER]);
            }
        }
        return $output;
    }


    public function parse() {
        $file = $this->getLabelFile();

        $data = file($file); //read whole file and parse it into array or rows
        $groups = [];
 //       $icons = [];
        $token = [];
        $tokenEmoji = [];
        $emojiStat = [];
        $tokens = [];
        $groupName = '';
        $token = (object)$groups;

        foreach ($data as $row) {
            $row = trim($row);
            if ('' === $row) {
                continue; //this is empty row or a comment, ignore it
            }

            if (self::DELIMITER === $row[0]) {
                $titleLine = $row;
                if (preg_match('/^\$(?<keyword>[^$:]+):(?<name>[^;]+);(?<icon>.*)$/', $row, $token)) {
                    $token = (object)$token;                                                 //allow to access as object
                    if ('group' === trim($token->keyword)) {
                        $groupName = self::getGroupName($token->name);
                            $groups[$groupName] = [
                                Data::JSON_ICON => $token->icon,
                                Data::JSON_NAME => $groupName,
                                Data::JSON_LIST => [],
                            ];
                            $emojiStat[$groupName][Data::JSON_ROW]=trim($titleLine);
                    } elseif ('subgroup' === trim($token->keyword)) {
                        $subGroupName  = self::getGroupName($token->name);
                            $groups[$groupName][Data::JSON_LIST][$subGroupName] = [
                                Data::JSON_ICON => $token->icon,
                                Data::JSON_NAME => $subGroupName,
                                Data::JSON_LIST => [],
                            ];
                            $emojiStat[$groupName][Data::JSON_LIST][$subGroupName][Data::JSON_ROW]=str_repeat(' ',4).trim($titleLine);
                    }
                }
                continue;                                                                   //this is a comment, ignore it
            } else {
                $tokenEmoji = explode(self::DELIMITER,$row);
                if (!strpos($tokenEmoji[0], self::QUALIFIED)) continue;
                $tokenEmoji = explode(' ',$tokenEmoji[1]);
                $emojiIcon = $tokenEmoji[1];
                $emojiName = trim( implode ( ' ', array_slice( $tokenEmoji, 3 )));
//-----------------------------------------------------------------------------------------------------------
                if ($groupName !=='filters') {
                    if (!is_array($allModifiers)){
                        $allModifiers = self::getAllModifiers($groups['filters']);
                    }
                    $groups[$groupName][Data::JSON_LIST][$subGroupName][Data::JSON_LIST][$emojiIcon] = [
                        Data::JSON_NAME => $emojiName,
                    ];
                    if (strpos($emojiName, Data::EMOJI_MODIFIER_CHAR)) {
                        $tokens = explode(Data::EMOJI_MODIFIER_CHAR, $emojiName);
                        if (!strpos($tokens[1], ',')){
                            $tokens[1] = $tokens[1] . ',';
                        }
                        $thisKeys = explode(',', $tokens[1]);
                        $emojiModifier = '';
                        foreach ($thisKeys as $thisKey) {
                            $thisKey=trim($thisKey);
                            if($thisKey!==''){
                                is_null($allModifiers[$thisKey]) ? null : $emojiModifier = $emojiModifier . $allModifiers[$thisKey] . '_';
                            }
                        }
                        if($emojiModifier !== '') {
                            $groups[$groupName][Data::JSON_LIST][$subGroupName][Data::JSON_LIST][$emojiIcon] = [
                                Data::JSON_MODIFIER => $emojiModifier,
                            ];
                        }
                    }
                } else {
                    $emojiNames =  explode(Data::EMOJI_MODIFIER_CHAR, $emojiName);
                    $groups[$groupName][Data::JSON_LIST][$subGroupName][Data::JSON_LIST][trim($emojiNames[0])] = [
                        Data::JSON_ICON => $emojiIcon,
                        Data::JSON_NAME => trim($emojiNames[1]),
                        Data::JSON_MODIFIER => trim($emojiNames[2]),
                    ];
                }
            }
        }
        echo PHP_EOL . 'Group recapitulation:' . PHP_EOL;

        $allGroupEmoji=[];
        $countEmojis = 0;
        foreach ($groups as $name => $group) {
            $countEmojisInGroup = 0;
            $countSubGroups = count($group[Data::JSON_LIST]);
            foreach ($group[Data::JSON_LIST] as $type => $subGroup) {
                $countEmojisInSubGroup = count($subGroup[Data::JSON_LIST]);
                foreach ($subGroup[Data::JSON_LIST] as $key => $value) {
                    $allGroupEmoji[]=$key;
                }
                $countEmojisInGroup += $countEmojisInSubGroup;
                $emojiStat[$name][Data::JSON_LIST][$type][Data::JSON_ROW] .= '; ';
                $emojiStat[$name][Data::JSON_LIST][$type][Data::JSON_ROW] .= str_repeat('.',50-mb_strlen($emojiStat[$name][Data::JSON_LIST][$type][Data::JSON_ROW]));
                $emojiStat[$name][Data::JSON_LIST][$type][Data::JSON_ROW] .= 'emoji: ' . $countEmojisInSubGroup . PHP_EOL;
             }
            $emojiStat[$name][Data::JSON_ROW] .= '; subgroups: ' . $countSubGroups . '; ';
            $emojiStat[$name][Data::JSON_ROW] .= str_repeat ('.',50-mb_strlen($emojiStat[$name][Data::JSON_ROW]));
            $emojiStat[$name][Data::JSON_ROW] .= 'emoji: ' . $countEmojisInGroup . PHP_EOL;
            $countEmojis += $countEmojisInGroup;
            echo '  Group ', $name, ' contains ', $countEmojisInGroup, ' emoji' . PHP_EOL;
        }
//      $groups = self::orderGroups($groups,$icons);
//        $groups['other'] = [
//            Data::JSON_ICON => U::chrS('"\u2620"'),
//            Data::JSON_NAME => 'other',
//            Data::JSON_LIST => [],
//        ];
//        $groups['other'][Data::JSON_LIST]['other'] = [
//            Data::JSON_ICON => U::chrS('"\u2620"'),
//            Data::JSON_NAME => 'other',
//            Data::JSON_LIST => [],
//        ];
        $groups['ALL'] = $allGroupEmoji;
        $groups['STAT'] = $emojiStat;

        echo '====================TOTAL EMOJI EQUAL= ',$countEmojis;
        echo PHP_EOL, 'Finished processing groups', PHP_EOL, 'hint: if your console does not support UTF-8 you can dump the output into a file and then open it with UTF-8 encoding to see the emoji.', PHP_EOL;

        return $groups;
    }
}
