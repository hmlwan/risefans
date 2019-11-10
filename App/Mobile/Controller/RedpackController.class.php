<?php
namespace Mobile\Controller;
/**
 * Created by PhpStorm.
 * User: hmlwan521
 * Date: 2019/11/10
 * Time: 下午5:19
 */
class RedpackController extends HomeController
{
    public function  index(){
//        $urk = "http://hmlwan521.com/Home/Index/teacher_list";
//        $data = curlPost($urk,array());
//        dd($data);
        $this->display();
    }

    /*观看广告*/
    public function watch_ad(){
        $this->display();
    }
    /*红包记录*/
    public function  receive_redpack_record(){
        $this->display();
    }

}