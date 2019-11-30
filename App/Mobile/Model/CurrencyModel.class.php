<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-3-8
 * Time: 下午2:23
 */

namespace Mobile\Model;
use Think\Model;

class CurrencyModel extends Model{



    public function get_cur_info($currency_id){

        $cur_info = "";
        if($currency_id){
            $cur_info = $this->where(array('currency_id'=>$currency_id))->find();
        }
        return $cur_info;
    }

    public function get_cur_name($currency_id){

        $cur_name = "";
        if($currency_id){
            $cur_name = $this->where(array('currency_id'=>$currency_id))->getField('currency_name');
        }
        return $cur_name;
    }
    public function get_cur_en($currency_id){

        $currency_en = "";
        if($currency_id){
            $currency_en = $this->where(array('currency_id'=>$currency_id))->getField('currency_en');
        }
        return $currency_en;
    }
    /*用户币种*/
    public function mem_cur($currency_id,$member_id){
        if(!$member_id){
            $member_id = session('USER_KEY_ID');

        }
        $where = array(
            'currency_id' =>$currency_id,
            'member_id' =>$member_id,
        );
        $info = M('currency_user')->where($where)->find();

        return $info;
    }
    /*用户币种余额*/
    public function mem_cur_num($currency_id,$member_id){
        if(!$member_id){
            $member_id = session('USER_KEY_ID');

        }
        $where = array(
            'currency_id' =>$currency_id,
            'member_id' =>$member_id,
        );
        $num = M('currency_user')->where($where)->getField('num');

        return $num ? $num:0;
    }


    /*用户加币*/
    public function mem_inc_cur($currency_id,$num,$member_id){
        if(!$member_id){
            $member_id = session('USER_KEY_ID');
        }
        $where = array(
            'currency_id' =>$currency_id,
            'member_id' =>$member_id,
        );
        $info = M('currency_user')->where($where)->find();

        if($info){
            $r = M('currency_user')->where($where)->setInc('num',$num);
        }else{
            $r = M('currency_user')->add(
                array(
                    'currency_id' =>$currency_id,
                    'member_id' =>$member_id,
                    'num' => $num
                )
            );
        }
        if(false === $r){
            return false;
        }
        return true;
    }
    /*用户减币*/
    public function mem_dec_cur($currency_id,$num,$member_id){
        if(!$member_id){
            $member_id = session('USER_KEY_ID');
        }
        $where = array(
            'currency_id' =>$currency_id,
            'member_id' =>$member_id,
        );
        $r = M('currency_user')->where($where)->setDec('num',$num);

        if(false === $r){
            return false;
        }
        return true;
    }
}