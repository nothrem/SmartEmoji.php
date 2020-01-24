<?php


namespace emoji;

const cp = 'UTF-8'; //PHP code for UTF-8 encoding (for syntactic sugar)

class Unicode {

    public static function len($string) {
        return mb_strlen($string, cp);
    }

    public static function sub($string, $start, $len = null) : string {
        if (is_string($start)) {
            $start = self::len($start);
        }
        if (is_string($len)) {
            $string = self::len($string);
        }
        return mb_substr($string, $start, $len, cp);
    }

    public static function char($string, $pos = 0) {
        return self::sub($string, $pos, 1);
    }

    public static function ord($char, $pos = 0) {
        if (0 <> $pos) {
            return mb_ord(self::sub($char, $pos), cp);
        }
        return mb_ord($char, cp);
    }

    public  static function chr($code) {
        return mb_chr($code, cp);
    }

    public static function codepoint($char, $pos = 0) {
        if (is_string($char)) {
            $char = self::ord($char, $pos);
        }
        return 'U+' . trim(dechex($char));
    }
}
