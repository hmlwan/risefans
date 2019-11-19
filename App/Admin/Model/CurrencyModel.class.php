<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-3-8
 * Time: 下午2:23
 */

namespace Admin\Model;
use Think\Model;

class CurrencyModel extends Model{


    public function get_cur_name($currency_id){

        $cur_name = "";
        if($currency_id){
            $cur_name = $this->where(array('currency_id'=>$currency_id))->getField('currency_name');
        }
        return $cur_name;
    }

}