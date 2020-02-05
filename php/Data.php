<?php


namespace xml;

class Data {

    //Keys to make JSON shorter (each value must be unique
    public const JSON_LIST = 'l';
    public const JSON_TYPE = 't';
    public const JSON_NAME = 'n';
    public const JSON_MODIFIER = 'm';
    public const JSON_KEYWORDS = 'k';

    public const JSON_TYPES = [
        'Basic_Emoji' => null, //basic type is default so no need to print it
        'Emoji_Keycap_Sequence' => 'k',
        'Emoji_Flag_Sequence' => 'f',
        'Emoji_Tag_Sequence' => 't',
        'Emoji_Modifier_Sequence' => 'm', //emoji with modifier
        'Emoji_ZWJ_Sequence' => 'j', //emoji with joiner
    ];

    protected $file;
    protected $data;
    protected $index;

    public function __construct(string $file) {
        $this->file = $file;

        $parser = xml_parser_create(cp); //make sure we process the file with correct encoding

        //Note: using parse into struct is better for our use because it automatically sorts XML nodes by their tag
        //      so we don't need to go through the XML tree and can directly access nodes of types we need
        xml_parse_into_struct($parser, file_get_contents($file), $this->data, $this->index);
    }

    public function getName() {
        return $this->file;
    }

    /**
     * Convert emoji type to shortened version
     *
     * @param string $type Emoji type
     * @return string|null Shortened emoji type, full emoji type (when shorter is not available) or NULL for default type
     */
    public static function getType(string $type) : ?string {
        return array_key_exists($type, self::JSON_TYPES) ? self::JSON_TYPES[$type] : $type;
    }

    public function filterXml(string $tag, array $attributes = []) : array {
        $tag = strtoupper($tag); //tags are always Upper-cased
        if (!array_key_exists($tag, $this->index) || empty($this->index[$tag])) {
            throw new \RuntimeException("Tag $tag not found in the file {$this->file}.");
        }

        $found = [];

        foreach ($this->index[$tag] as $i) {
            $item = (object)$this->data[$i];
            $item->attributes = (object)($item->attributes ?? []);

            foreach ($attributes as $attribute => $value) {
                $attribute = strtoupper($attribute); //Attributes are always returned in Upper-case
                if (!isset($item->attributes->$attribute) || $value !== $item->attributes->$attribute) {
                    continue 2; //no match, continue to next item
                }
            }

            //all attributes match
            $found[] = $item;
        }

        return $found;
    }

    public function getXmlValue(string $tag, array $attributes = []) : ?string {
        $found = $this->filterXml($tag, $attributes);

        if (empty($found)) {
            return false;
        }

        $found = reset($found); //get first item

        return $found->value ?? null;
    }
}
