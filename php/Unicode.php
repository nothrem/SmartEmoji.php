<?php


namespace emoji;

const cp = 'UTF-8'; //PHP code for UTF-8 encoding (for syntactic sugar)

class Unicode {

    public const UNICODE_GROUP_SKIN_TONE = 'skin-tone'; //name of group that contains skin tones

    //Genders are used in emoji with professions (jobs) and other emoji dependent on genders
    public const EMOJI_GENDER_MAN = '1f468';
    public const EMOJI_GENDER_WOMAN = '1f469';
    public const EMOJI_GENDER_ADULT = '1f9d1'; //gender-neutral person
    public const EMOJI_GENDER_ALT_ADULT = '1f9d1'; //gender-neutral person

    public const EMOJI_GENDER_OLD = '1f9d2'; //alternative for gender-neutral filter
    public const EMOJI_GENDER_OLD_MAN = '1f474'; //alternative for male filter
    public const EMOJI_GENDER_OLD_WOMAN = '1f475'; //alternative for female filter

    //Child genders are used in family emoji and can be used for filter
    public const EMOJI_GENDER_CHILD = '1f9d2'; //gender-neutral child
    public const EMOJI_GENDER_ALT_BABY = '1f9d3'; //alternative for gender-neutral child (alternative for child filter)
    public const EMOJI_GENDER_BABY = '1f476'; //gender-neutral baby (alternative for child filter)
    public const EMOJI_GENDER_BOY = '1f466';   //male child
    public const EMOJI_GENDER_GIRL = '1f467';  //female child

    //Filters define what emoji to hide when given filter is selected
    public const EMOJI_FILTER_MAN = [
        self::EMOJI_GENDER_WOMAN,
        self::EMOJI_GENDER_OLD_WOMAN,
        self::EMOJI_GENDER_OLD,
        self::EMOJI_GENDER_ADULT,
        self::EMOJI_GENDER_ALT_ADULT,
        self::EMOJI_GENDER_GIRL,
        self::EMOJI_GENDER_CHILD,
        self::EMOJI_GENDER_BABY,
        self::EMOJI_GENDER_ALT_BABY,
    ];
    public const EMOJI_FILTER_WOMAN = [
        self::EMOJI_GENDER_MAN,
        self::EMOJI_GENDER_OLD_MAN,
        self::EMOJI_GENDER_OLD,
        self::EMOJI_GENDER_ADULT,
        self::EMOJI_GENDER_ALT_ADULT,
        self::EMOJI_GENDER_BOY,
        self::EMOJI_GENDER_CHILD,
        self::EMOJI_GENDER_BABY,
        self::EMOJI_GENDER_ALT_BABY,
    ];
    public const EMOJI_FILTER_NEUTRAL = [
        self::EMOJI_GENDER_MAN,
        self::EMOJI_GENDER_OLD_MAN,
        self::EMOJI_GENDER_WOMAN,
        self::EMOJI_GENDER_OLD_WOMAN,
        self::EMOJI_GENDER_BOY,
        self::EMOJI_GENDER_GIRL,
    ];
    public const EMOJI_FILTER_CHILD = [
        self::EMOJI_GENDER_MAN,
        self::EMOJI_GENDER_OLD_MAN,
        self::EMOJI_GENDER_WOMAN,
        self::EMOJI_GENDER_OLD_WOMAN,
        self::EMOJI_GENDER_OLD,
        self::EMOJI_GENDER_BOY,
        self::EMOJI_GENDER_GIRL,
        self::EMOJI_GENDER_ADULT,
        self::EMOJI_GENDER_ALT_ADULT,
    ];
    public const EMOJI_FILTER_BOY =  [
        self::EMOJI_GENDER_MAN,
        self::EMOJI_GENDER_OLD_MAN,
        self::EMOJI_GENDER_WOMAN,
        self::EMOJI_GENDER_OLD_WOMAN,
        self::EMOJI_GENDER_OLD,
        self::EMOJI_GENDER_ADULT,
        self::EMOJI_GENDER_ALT_ADULT,
        self::EMOJI_GENDER_GIRL,
        self::EMOJI_GENDER_CHILD,
        self::EMOJI_GENDER_BABY,
        self::EMOJI_GENDER_ALT_BABY,
    ];
    public const EMOJI_FILTER_GIRL =  [
        self::EMOJI_GENDER_MAN,
        self::EMOJI_GENDER_OLD_MAN,
        self::EMOJI_GENDER_WOMAN,
        self::EMOJI_GENDER_OLD_WOMAN,
        self::EMOJI_GENDER_OLD,
        self::EMOJI_GENDER_ADULT,
        self::EMOJI_GENDER_ALT_ADULT,
        self::EMOJI_GENDER_BOY,
        self::EMOJI_GENDER_CHILD,
        self::EMOJI_GENDER_BABY,
        self::EMOJI_GENDER_ALT_BABY,
    ];

    public const EMOJI_FILTER_GENDER = [
        self::EMOJI_GENDER_MAN => self::EMOJI_FILTER_MAN,
        self::EMOJI_GENDER_WOMAN => self::EMOJI_FILTER_WOMAN,
        self::EMOJI_GENDER_ADULT => self::EMOJI_FILTER_NEUTRAL,
        self::EMOJI_GENDER_CHILD => self::EMOJI_FILTER_CHILD,
        self::EMOJI_GENDER_BOY => self::EMOJI_FILTER_BOY,
        self::EMOJI_GENDER_GIRL => self::EMOJI_FILTER_GIRL,
    ];

    /**
     * Calculate length of UNICODE string (encoded in UTF-8)
     */
    public static function len(string $string) : int {
        return mb_strlen($string, cp);
    }

    /**
     * Search one string in another one considering UTF-8 encoding
     *
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return false|int
     */
    public static function pos(string $haystack, string $needle, int $offset = 0) {
        return mb_strpos($haystack, $needle, $offset, cp);
    }

    /**
     * Check if one string contains the other one considering UTF-8 encoding.
     *
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return bool
     */
    public static function contains(string $haystack, string $needle, int $offset = 0) {
        return false !== self::pos($haystack, $needle, $offset);
    }

    /**
     * Returns substring based on UNICODE characters (encoded in UTF-8)
     * @param string $string
     * @param int|string $start Starting character. When string is given, it's length is used to find starting character.
     * @param int|string|null $len Number of characters to return. When string is given the same number of characters is returned.
     * @return string
     */
    public static function sub(string $string, $start, $len = null) : string {
        if (is_string($start)) {
            $start = self::len($start);
        }
        if (is_string($len)) {
            $string = self::len($string);
        }
        return mb_substr($string, $start, $len, cp);
    }


    /**
     * Returns UNICODE character at given position of string (encoded in UTF-8)
     *
     * @param string $string
     * @param int|string $pos (optional, default: 0) When defined, uses `sub($string, $pos)` to get a char at given position.
     * @return string
     */
    public static function char(string $string, $pos = 0) : string {
        return self::sub($string, $pos, 1);
    }

    /**
     * Returns numerical representation of a UNICODE character (code point).
     *
     * @param string $char One character or longer string (encoded in UTF-8).
     * @param int $pos (optional, default: 0) When defined, combines ord() and char() methods.
     * @return false|int
     */
    public static function ord(string $char, int $pos = 0) {
        if (0 <> $pos) {
            return mb_ord(self::sub($char, $pos), cp);
        }
        return mb_ord($char, cp);
    }

    /**
     * Returns UNICODE character (encoded to UTF-8) represented by its UNICODE code point.
     *
     * @param int $code
     * @return false|string
     */
    public static function chr(int $code) {
        return mb_chr($code, cp);
    }

    /**
     * Returns UNICODE character (encoded to UTF-8) represented by a hexadecimal sequence (space separated) of code points.
     *
     * @param string $code
     * @return false|string
     */
    public static function chrS(string $code) {
        $code = explode(' ', $code); //may contain more code points separated by a space
        $output = '';
        foreach ($code as $char) {
            $output .= self::chr(hexdec($char));
        }
        return $output;
    }

    /**
     * Returns human-readable (hexadecimal) code point of a UNICODE character (given as UTF-8 string or numerical code point).
     *s
     * @param string|int $char One character or longer string (encoded in UTF-8).
     * @param int $pos (optional, default: 0) When defined, combines ord() and char() methods. Ignored when $char is a number.
     * @return string
     */
    public static function codepoint($char, int $pos = 0) {
        if (is_string($char)) {
            $char = self::ord($char, $pos);
        }
        return 'U+' . trim(dechex($char)) . ' (' . trim(json_encode(self::chr($char)), '\'"') . ')';
    }

    /**
     * Convert UTF-8 encoded string to lower case letters.
     *
     * @param string $string
     * @return string
     */
    public static function low(string $string) {
        return mb_strtolower($string, cp);
    }

    /**
     * Convert UTF-8 encoded string to upper case letters.
     *
     * @param string $string
     * @return string
     */
    public static function up(string $string) {
        return mb_strtoupper($string, cp);
    }

    /**
     * Convert first letter of string to upper case considering UTF-8 encoding.
     *
     * @param string $string
     * @return string
     */
    public static function upFirst(string $string) {
        return mb_strtoupper(self::char($string)) . self::sub($string, 1);
    }
}
