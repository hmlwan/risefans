<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2018/7/23
 * Time: 21:36
 */
function dd($data){
    if($data){
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
    die();
}
function get_contents($url, $header = array()) {
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
    $ret = curl_exec ( $ch );


    curl_close ( $ch );
    return $ret;
}
