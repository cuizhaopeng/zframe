<?php
/**
 * Created by PhpStorm.
 * User: cuizhaopeng
 * Date: 18-12-5
 * Time: 下午5:37
 */

namespace app\osys\service;



/**
 * [AesSecurity aes加密，支持PHP7.1]
 */
class AesSecurity
{

    /**
     * encrypt aes加密
     * @param string $input 要加密的数据
     * @param string $key   加密key
     * @return string       加密后的数据
     */
    public static function encrypt($input, $key)
    {
        $data = openssl_encrypt($input, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);
        return $data;
    }
    /**
     * decrypt aes解密
     * @param  string  $sStr   要解密的数据
     * @param  string  $sKey   加密key
     * @return string    解密后的数据
     */
    public static function decrypt($sStr, $sKey)
    {
        $decrypted = openssl_decrypt(base64_decode($sStr), 'AES-128-ECB', $sKey, OPENSSL_RAW_DATA);
        return $decrypted;
    }
}