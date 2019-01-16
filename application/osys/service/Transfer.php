<?php
/**
 * Created by PhpStorm.
 * User: cuizhaopeng
 * Date: 18-12-16
 * Time: 下午12:18
 */

namespace app\osys\service;


class Transfer
{
    function do_Post($url, $fields, $extraheader = array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    function do_Get($url, $extraheader = array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回:
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        $output = curl_exec($ch) ;
        curl_close($ch);
        return $output;
    }

    function do_Put($url, $fields, $extraheader = array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url ) ;
        curl_setopt($ch, CURLOPT_POST, true) ;
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
        //curl_setopt($ch, CURLOPT_ENCODING, '');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    function do_Delete($url, $fields, $extraheader = array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url ) ;
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
        //curl_setopt($ch, CURLOPT_ENCODING, '');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}