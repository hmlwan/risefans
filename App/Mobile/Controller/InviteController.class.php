<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class InviteController extends HomeController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    //空操作
    public function _empty()
    {
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:face_to_face');
    }
    public function index(){

        $member_id = session('USER_KEY_ID');
        $db = D('member');
        $member_info = $db->get_info_by_id($member_id);

        /*直邀链接*/
        $direct_invite_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."?pid={$member_info['phone']}";

        $invite_record_db = D('invite_record');
        /*一级下线*/
        $one_level_num = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>1))
            ->count();
        /*二级下线*/
        $two_level_num = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>2))
            ->count();
        /*今日赚金币*/
        $td_start = strtotime(date("Y-m-d 0:0:0",time()));
        $td_end = time();
        $td_where = array(
            'currency_id' =>3,
            'member_id'=>$member_id,
            'add_time'=>array('between',array($td_start,$td_end))
        );
        $td_sum_cur_num =  $invite_record_db
            ->where($td_where)
            ->sum('num');
        /*累积赚金币*/
        $sum_where = array(
            'member_id'=>$member_id,
            'currency_id' =>3,
        );
        $sum_cur_num =  $invite_record_db
            ->where($sum_where)
            ->sum('num');
        /*累积排行榜*/
        $invite_record_list =  $invite_record_db
            ->field('member_id,currency_id,num')
            ->where(array( 'currency_id' =>3))
            ->select();

        $top3 = array();
        foreach ($invite_record_list as $key=>$value){
            $top3[$value['member_id']][] = $value['num'];
        }

        $top3_sum = array();
        foreach ($top3 as $k => $v){
            $phone = M("member")->where(array('member_id'=>$k))->getField('phone');
            $phone_str = substr_replace($phone,'****',3,4);
            $top3_sum[$phone_str] = array_sum($v);
        }

        arsort($top3_sum);
        $top3_sum = array_slice($top3_sum,0,3);

        $this->assign('one_level_num',  $one_level_num );
        $this->assign('two_level_num',  $two_level_num );
        $this->assign('td_sum_cur_num',  number_format($td_sum_cur_num,0,'','') );
        $this->assign('sum_cur_num',  number_format($sum_cur_num,0,'','') );
        $this->assign('top3_sum',  $top3_sum );
        $this->assign('direct_invite_url',  $direct_invite_url );
        $this->assign('member_info',  $member_info );
        $this->display();

    }
    /*一级*/
    public function one_level_detail(){

        $data = $this->get_sub_data(1);

        $level_list = $data['level_list'];
        $cert_level_num = $data['cert_level_num'];
        $level_sum_num = $data['level_sum_num'];
        $this->assign('level_list', $level_list );
        $this->assign('level_sum_num', $level_sum_num );
        $this->assign('cert_level_num', $cert_level_num);
        $this->assign('count_level_num', count($level_list));
        $this->display();
    }
    /*二级*/
    public function two_level_detail(){
        $data = $this->get_sub_data(2);
        $level_list = $data['level_list'];
        $cert_level_num = $data['cert_level_num'];
        $level_sum_num = $data['level_sum_num'];
        $this->assign('level_list', $level_list );
        $this->assign('level_sum_num', $level_sum_num );
        $this->assign('cert_level_num', $cert_level_num);
        $this->assign('count_level_num', count($level_list));

        $this->display();
    }
    /*今日收益记录*/
    public function td_income_record(){
        $invite_record_db = D('invite_record');
        $member_id = session('USER_KEY_ID');
        $td_start = strtotime(date("Y-m-d 0:0:0",time()));
        $td_end = time();
        $td_where = array(
            'currency_id' =>3,
            'member_id'=>$member_id,
            'add_time'=>array('between',array($td_start,$td_end)),
            'is_cert'=>2
        );
        $list = $invite_record_db->where($td_where)->order("add_time desc")->select();
        foreach ($list as &$value){
            $value['sub_phone'] = M('member')->where(array('member_id'=>$value['sub_member_id']))->getField('phone');
        }
        $this->assign('list', $list );
        $this->display();
    }
    /*收益记录*/
    public function income_record(){
        $invite_record_db = D('invite_record');
        $member_id = session('USER_KEY_ID');

        $list = $invite_record_db->where(array('currency_id' =>3,'member_id'=>$member_id,'is_cert'=>2))->order("add_time desc")->select();
        foreach ($list as &$value){
            $value['sub_phone'] = M('member')->where(array('member_id'=>$value['sub_member_id']))->getField('phone');
        }
        $this->assign('list', $list );
        $this->display();
    }
    public function get_sub_data($level){
        $member_id = session('USER_KEY_ID');
        $invite_record_db = D('invite_record');
        $db = D('member');
        $level_list = $invite_record_db
            ->where(array(
                'member_id'=>$member_id,
                'level'=>$level,
//                'currency_id'=>3,
                )
            )
            ->select();

        $level_sum_num = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>$level))
            ->sum('num');
        /*已认证*/
        $cert_level_num = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>$level,'is_cert'=>2))
            ->count();
        $sum_num = array();
        foreach ($level_list as $key => $value){
            if($value['currency_id'] == 3){
                $sum_num[$value['sub_member_id']][] = $value['num'];
            }else{
                $sum_num[$value['sub_member_id']][] = 0;
            }
        }

        $level_list_arr = array();
        foreach ($sum_num as $k => $v){
            $info = $db->get_info_by_id($k);

            $show_data = array(
                'head_url' => $info['head_url'],
                'sub_phone' => $info['phone'],
                'vip_level' => $info['vip_level'],
                'is_cert' => $info['is_cert'],
                'add_time' => $info['reg_time'],
                'sum_num' => number_format(array_sum($v),0,'','')
            );
            $level_list_arr[] = $show_data;
        }

        $arr = array(
            'level_list' => $level_list_arr,
            'cert_level_num' => $cert_level_num,
            'level_sum_num' => number_format($level_sum_num,0,'',''),
        );
        return $arr;
    }
    /*累积排行榜*/
    public function rank_record(){
        $invite_record_db = D('invite_record');
        $db = D('member');

        /*累积排行榜*/
        $level_list =  $invite_record_db
            ->field('member_id,currency_id,num')
            ->where(array( 'currency_id' =>3))
            ->select();

        foreach ($level_list as $key => $value){
            $sum_num[$value['member_id']][] = $value['num'];
        }

        $sum_num_list = $sum_num_arr = array();

        foreach ($sum_num as $k => $v){
            $phone = $db->where(array('member_id'=>$k))->getField('phone');
            $phone_str = substr_replace($phone,'****',3,4);
            if(array_sum($v)>0){
                $sum_num_arr[$phone_str] =  array_sum($v);
            }
        }

        arsort($sum_num_arr);
        $sum_num_arr = array_slice($sum_num_arr,0,20);
        $tpo3_list = array_slice($sum_num_arr,0,3);
        $tpo3_arr = array();
        foreach ($tpo3_list as $kk=>$vv){
            $tpo3_arr[] = array(
                'phone' => $kk,
                'num' => number_format($vv,0,'',''),
            );
        }
        $this->assign('sum_num_arr',$sum_num_arr);
        $this->assign('tpo3_arr',$tpo3_arr);
        $this->display();
    }
    public function rule_view(){
        $this->display();
    }
    public function face_to_face(){
        $tjm = I('pid');

        $this->assign('tjm',$tjm);
        $this->display();
    }
    /*二维码*/
    public function qrcodeimg(){

        Vendor('phpqrcode.phpqrcode');
        $QRcode = new \QRcode ();
        $errorCorrectionLevel = 'M';
        $matrixPointSize = 4;
        $member_id = session('USER_KEY_ID');
        $phone = M('member')->where(array('member_id'=>$member_id))->getField('phone');
        $http = $_SERVER['HTTP_HOST'];
        $url = 'http://'.$http.'/Mobile/Reg/reg/pid/'.$phone;
        $QRcode::png($url,false,$errorCorrectionLevel,$matrixPointSize);
    }
    /*二维码*/
    public function face_to_face_img(){

        Vendor('phpqrcode.phpqrcode');
        $QRcode = new \QRcode ();
        $errorCorrectionLevel = 'M';
        $matrixPointSize = 4;
        $member_id = session('USER_KEY_ID');
        $phone = M('member')->where(array('member_id'=>$member_id))->getField('phone');
        $http = $_SERVER['HTTP_HOST'];
        $url = 'http://'.$http.'/Mobile/Invite/face_to_face/pid/'.$phone;
        $QRcode::png($url,false,$errorCorrectionLevel,$matrixPointSize);
    }
}