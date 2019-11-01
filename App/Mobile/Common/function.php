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
//获取全部的下级
function getAllSub($id,$id_str=''){
    $ids = M('member')->where(array('pid'=>$id,'status'=>1))->getField('member_id',true);
    if($ids){
        foreach ($ids as  $key => $val){
            $id_str .= $val.',';
            $id_str = getAllSub($val,$id_str);
        }
        return $id_str;
    }else{
        return $id_str;
    }
}


/*获取全部的上级*/
function getAllParent($id,$id_str=array()){
    $pid = M('member')->where(array('member_id'=>$id))->getField('pid');

    if($pid){
        $id_str[] = $pid;
        $id_str = getAllParent($pid,$id_str);
        return $id_str;
    }else{
        return $id_str;
    }
}
function chinanum($num){
    $china=array('零','一','二','三','四','五','六','七','八','九');
    $arr=str_split($num);
    for($i=0;$i<count($arr);$i++){
        return $china[$arr[$i]];
    }
}

function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
    if(is_array($arrays)){
        foreach ($arrays as $array){
            if(is_array($array)){
                $key_arrays[] = $array[$sort_key];
            }else{
                return false;
            }
        }
    }else{
        return false;
    }
    array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
    return $arrays;
}
function transToSecond($str) {
    $arr = array();
    if($str){
        if($str >= 60*60){
            $a = intval($str / 3600);
            if(($str - 3600*$a-60) >= 0){
                $b = intval(($str - 3600 *$a)/60);
                $c = $str - 3600 *$a - $b * 60;
            }else{
                $b = "00";
                $c = $str -3600 *$a;
            }
        }else if($str >= 60){

            $a = "00";
            $b = intval($str / 60) ;
            $c = $str - 60 * $b;

        }else{
            $a = "00";
            $b = "00";
            $c = $str;
        }
        $arr['a'] =  sprintf('%02s', $a);
        $arr['b'] =  sprintf('%02s',$b);
        $arr['c'] =  sprintf('%02s',$c);
    }
    return $arr;
}

/*随机数字与字母位数*/
function getRandNumber($len,$chars=null){
    if(is_null($chars)){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    $str = '';
    for ($i=0;$i<$len;$i++){
        $str .= substr($chars,rand(0,strlen($chars)),1);
    }
    return $str;
}

/*
   * $str:字符串
   * $num:位数
   *
   */
function split_kg($str,$num){
    $string = '';
    $j = 0;
    for ($i=1;$i<=strlen($str);$i++){
        if(!($i % $num)){
            $string .= substr($str,$j,$num)." ";
            $j = $i;
            if(strlen($str) -$i< $num){
                $string .= substr($str,$i);
            }
        }
    }
    return trim($string,' ');

}

