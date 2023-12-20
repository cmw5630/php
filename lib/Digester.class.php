<?php
class Digester
{
    /**
     * @var string 암호화 알고리즘
     */
    static protected $_algorithm = 'sha256';

    /**
     * @var int 암호화 실행 횟수
     */
    static protected $_iteration = 100000;

    /**
     * 암호화 (Hash)
     *
     * @param multitype $mixed Raw 데이터
     * @param Array (ByteArray) $salt Salt
     * @return string
     */
    static function digest($mixed, $salt = null)
    {
        if (is_null($salt)) {
            $salt = self::generateSalt();
        }
        if (is_array($mixed)) {
            $mixed = array_merge($salt, $mixed);
            for ($i = 1; $i <= self::$_iteration; $i++) {
                $mixed = hash(self::$_algorithm, self::getString($mixed), true);
                $mixed = self::getBytes($mixed);
            }
            $mixed = array_merge($salt, $mixed);
            return base64_encode(self::getString($mixed));
        } else if (is_string($mixed)) {
            $bytes = self::getBytes(normalizer_normalize(utf8_encode($mixed)));
            return self::digest($bytes, $salt);
        }
    }

    /**
     * Salt 생성
     *
     * @return Array 16 bytes salt (ByteArray)
     */
    static function generateSalt()
    {
        $salt = bin2hex(openssl_random_pseudo_bytes(8)); // 16bytes

        return self::getBytes(utf8_encode($salt));
    }

    /**
     * Bytes 배열 생성
     *
     * @param unknown_type $string Raw 데이터
     * @return Array (ByteArray)
     */
    static function getBytes($string)
    {
        $bytes = array_slice(unpack("C*", "\0" . $string), 1);
        $bytes = array_map('decbin', $bytes);

        return $bytes;
    }

    /**
     * Bytes 배열을 문자열로 인코딩
     *
     * @param Array (ByteArray) $bytes
     * @return string (ByteString) Bytes 데이터
     */
    static function getString($bytes)
    {
        $bytes = array_map('bindec', $bytes);
        $string = call_user_func_array("pack", array_merge(["C*"], $bytes));

        return $string;
    }

    /**
     * Hash 데이터와 Raw 데이터의 비교 (검증)
     *
     * @param $hash 암호화 데이터
     * @param $string Raw 데이터
     * @return bool
     */
    static function isValid($hash, $string)
    {
        $mixed = self::getBytes(base64_decode($hash));
        $salt = array_slice($mixed, 0, 16);

        return $hash == self::digest($string, $salt);
    }
}