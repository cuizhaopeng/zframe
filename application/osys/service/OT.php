<?php
/**
 * DateTime: 2018/8/2 10:05
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\service;


use app\osys\lib\exception\ExpireException;
use Faker\Provider\DateTime;
use http\Exception\InvalidArgumentException;

use test\Mockery\Adapter\Phpunit\MockeryPHPUnitIntegrationTest;

/**
 * Class OT  TOKEN生成与TOKEN验证类
 * @package app\api\controller
 *
 * 生成token
 * 使用方法如下
 * static::encode($payload, $key, $alg = 'HS256');
 * 返回结果：一个加密后的字符串
 * 参数解释
 * $payload 需要生成token的对象或数组
 * $key 生成token所需要的密钥
 * $alg 加密格式 默认：HS256
 *
 * 解密token
 * 使用方法如下
 * static::decode($token，$key, $allowed_algs);
 * 返回结果：一个解密后的数组或者对象
 * 参数解释
 * $token 需要解密的字符串
 * $key 解密密钥
 * $allowed_algs 验证加密格式
 */
class OT
{

    //时间偏差
    public static $leeway = 0;
    /**
     * 允许指定当前时间戳
     * 对于在当前的单元测试中修复一个值很有用
     * @var null 当前默认值设为null
     */
    public static $timestamp = null;

    public static $supported_algs = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512')
    );

    /**
     * 创建一个Token字符串
     * @param object|array $payload  对象或者数组
     * @param string $key 密钥
     * @param string $alg 加密格式
     * @return string 返回一个生成的Token
     */
    public static function encode($payload, $key, $alg = 'HS256')
    {
        $header = array('typ' => 'OT','alg' => $alg);
        $segments = array();
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($header));
        $segments[] = static::urlsafeB64Encode(static::jsonEncode($payload));
        $signing_input = implode('.', $segments);
        $signature = static::sign($signing_input, $key, $alg);
        $segments[] = static::urlsafeB64Encode($signature);
        return implode('.', $segments);
    }

    /**
     * 解密token
     * @param string $token 需要解密的字符串
     * @param string $key 密钥
     * @param array $allowed_algs 允许的加解密格式
     * @return object|array 返回解密后的数据
     */
    public static function decode($token, $key, array $allowed_algs = array())
    {
        $timestamp = is_null(static::$timestamp) ? time() : static::$timestamp;
        if (empty($timestamp))
        {
            throw new InvalidArgumentException('Key may not be empty');
        }
        $otoken = explode('.',$token);
        if (count($otoken) != 3)
        {
            throw new \UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $otoken;
        if (null === ($header = static::jsonDecode(static::urlsafeB64Decode($headb64))))
        {
            throw new \UnexpectedValueException('Invalid header encoding');
        }
        if (null === ($payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64))))
        {
            throw new \UnexpectedValueException('Invalid claims encoding');
        }
        if (null === ($sig = static::urlsafeB64Decode($cryptob64)))
        {
            throw new \UnexpectedValueException('Invalid signature encoding');
        }
        if (empty($header->alg))
        {
            throw new \UnexpectedValueException('Empty algorithm');
        }
        if (empty(static::$supported_algs[$header->alg]))
        {
            throw new \UnexpectedValueException('Algorithm not supported');
        }
        if (!in_array($header->alg,$allowed_algs))
        {
            throw new \UnexpectedValueException('Algorithm not allowed');
        }
        if (is_array($key) || $key instanceof \ArrayAccess)
        {
            if (isset($header->kid))
            {
                if(!isset($key[$header->kid]))
                {
                    throw new \UnexpectedValueException('"kid" invalid, unable to lookup correct key');
                }
                $key = $key[$header->kid];
            } else {
                throw new \UnexpectedValueException('"kid" empty, unable to lookup correct key');
            }
        }

        //检查signature是否一致
        if (!static::verify("$headb64.$bodyb64", $sig, $key, $header->alg))
        {
            throw new \SignatureInvalidException('Signature verification failed');
        }

        //检查参数 预留
        if (isset($payload->nbf) && $payload->nbf > ($timestamp + static::$leeway))
        {
            throw new \BeforeValidException(
                'Cannot handle token prior to ' . date(DateTime::IS08601, $payload->nbf)
            );
        }

        //检查参数 预留
        if (isset($payload->exp) && ($timestamp + static::$leeway) >= $payload->exp)
        {
            throw new ExpireException();
        }

        return $payload;

    }

    /**
     * 将json字符串转换为对象
     * @param string $input  输入的json字符串
     * @return object $obj  将json字符串转换为对象
     *
     * @throws \DomainException 结果为空
     */
    public static function jsonDecode($input)
    {
        $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        if (function_exists('json_last_error') && $errno = json_last_error())
        {
            static::handleJsonError($errno);
        } elseif ($obj === null && input !== 'null')
        {
            throw new \DomainException('Null result with non-null input');
        }
        return $obj;
    }

    /**
     * 解码一个Base64编码的字符串
     * @param string $input  一个Base64编码的字符串
     * @return string  返回Base64解码的字符串
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder)
        {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input,'-_','+/'));
    }

    /**
     * 签名验证
     * @param string $msg   原始的信息（头部和身体）
     * @param string $signature  原始的签名
     * @param string|resource $key
     * @param string $alg  验证参数
     * @return bool
     */
    public static function verify($msg, $signature, $key, $alg)
    {
        if (empty(static::$supported_algs[$alg]))
        {
            throw new \DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function)
        {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $algorithm);
                if($success === 1)
                {
                    return true;
                } elseif ($success === 0)
                {
                    return false;
                }
                //返回1成功，0失败，-1错误
                throw new \DomainException(
                    'OpenSSL error:' . openssl_error_string()
                );
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $msg, $key, false);
                //echo("<script>console.log('".$hash."'):</script>");
                //echo("<script>console.log('".$signature."'):</script>");
                if (function_exists('hash_equals'))
                {
                    return hash_equals($signature, $hash);
                }
                $len = min(static::safeStrlen($signature),static::safeStrlen($hash));

                $status = 0;
                for ($i=0; $i < $len; $i++)
                {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (static::safeStrlen($signature) ^ static::safeStrlen($hash));

                return ($status === 0);
        }
    }

    public static function safeStrlen($str)
    {
        if(function_exists('mb_strlen'))
        {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }
    /**
     * 对字符串做base64编码
     *
     * @param string $input 需要进行base64编码的字符串
     *
     * @return string 返回编码后的字符串
     */
    public static function urlsafeB64Encode($input)
    {
        //base64传统编码中会出现+, /两个会被url直接转义的符号，
        //因此如果希望通过url传输这些编码字符串，
        //我们需要先做传统base64编码，随后将+和/分别替换为- _两个字符，在接收端则做相反的动作解码
        //标准中是要求用=来补尾,需要去掉
        return str_replace('=','',strtr(base64_encode($input),'+/','-_'));
    }

    /**
     * 将一个对象或者数组转换为一个json字符串
     *
     * @param  object|array $input 需要转换为json的对象或者数组
     *
     * @return string $json 返回转成json的字符串
     *
     * @throws \DomainException 如果生成json数据时报抛出异常
     */
    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error())
        {
            static::handleJsonError($errno);
        }elseif ($json === 'null' && $input !== null)
        {
            throw new \DomainException('Null result with non-null input');
        }
        return $json;
    }

    /**
     * 获取创建json失败的具体原因
     *
     * @param  int $errno  从json_last_error()中获取一个错误码
     *
     * @return  void
     */
    public static function handleJsonError($errno)
    {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed Json',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error,malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters'
        );
        throw new \DomainException(
            isset($messages[$errno])?$messages[$errno]:'Unknown JSON error: ' . $errno
        );
    }

    /**
     * 根据密钥以及加密格式生成一个签名
     * @param string $msg  需要生成的签名的信息
     * @param sting|resource $key  密钥
     * @param string $alg 需要加密的格式：支持   'HS256', 'HS384', 'HS512' 和 'RS256'
     * @return string $signature  返回一个需要被加密的信息
     *
     * @throws \DomainException 需要加密的格式不被支持
     */
    public static function sign($msg, $key, $alg = 'HS256')
    {
        if (empty(static::$supported_algs[$alg]))
        {
            throw new \DomainException('Algorithm not supported');
        }
        list($function,$algorithm) = static::$supported_algs[$alg];
        switch ($function)
        {
            case 'hash_hmac':
                return hash_hmac($algorithm,$msg,$key,false);
            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg,$signature,$key,$algorithm);
                if (!$success)
                {
                    throw new \DomainException('OpenSSL unable to sign data');
                }else
                {
                    return $signature;
                }
        }
    }
}


