<?php


namespace emoji;

const cp = 'UTF-8'; //PHP code for UTF-8 encoding (for syntactic sugar)

class Unicode {

    /**
     * Calculate length of UNICODE string (encoded in UTF-8)
     */
    public static function len(string $string) : int {
        return mb_strlen($string, cp);
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
}
