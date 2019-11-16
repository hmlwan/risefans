<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class SysController extends HomeController
{

    public function _initialize(){
        parent::_initialize();
    }
    //空操作
    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:404');
    }

    /*我的消息*/
    public function message(){

        $member_id = session('USER_KEY_ID');
        $db = M('message');
        if(IS_POST){
            $message_id = I('message_id');
            if($message_id){
                $res = $db->where(array('message_id'=>$message_id))->save(array('is_read'=>1));
                if($res){
                    $data['status'] = 1;
                    $data['info'] = "已读成功";
                    $this->ajaxReturn($data);
                }else{
                    $data['status'] = 0;
                    $data['info'] = "已读失败";
                    $this->ajaxReturn($data);
                }
            }

        }else{
            $list = $db->where(array('member_id'=>$member_id))
                ->order('is_read asc,add_time desc')
                ->select();
            $this->assign('list',$list);
            $this->display();
        }

    }

    /*商业合作*/
    public function business_cooperation(){

        $this->display();
    }


}