<?php
/**
 * Created by PhpStorm.
 * Date: 2019/6/4
 * Time: 10:10
 */

namespace Admin\Controller;


use Think\Controller;
class ApiController extends Controller
{



    /*系统配置基本信息*/
    public function getSysInfo(){

        $db = M('config');
        $config = $db->select();
        $arr = array();
        foreach ($config as $value){
            $arr[$value['key']] =  $value['value'];
        }

        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $arr;

        $this->ajaxReturn($data);
    }
    /*工作类型*/
    public  function getJobType(){
        $limit  = I('get.limit');
        $db = M('job_type');

        $list = $db->where(array('status'=>1))->limit($limit)->select();

        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $list;
        $this->ajaxReturn($data);
    }

    /*阿姨列表*/
    public function getAyiByJobType(){
        $type_id = I('get.type_id');
        $where = array();
        if($type_id){
            $where['type_id'] = $type_id;
        }
        /*阿姨*/
        $where['status'] = 1;
        $db = M('jober');
        $ai_list = $db->where($where)->select();
        $subdata['ai_list'] =  $ai_list;
        /*类型*/
        $job_type_db = M('job_type');
        $type_list = $job_type_db->where(array('status'=>1))->select();

        $subdata['type_list'] =  $type_list;
        $subdata['type_id'] =  $type_id;
        /*特色服务*/
        $s_db = M('special_service');
        $special_list = $s_db->select();
        $subdata['special_list'] =  $special_list;

        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $subdata;

        $this->ajaxReturn($data);

    }
    /*阿姨详情*/
    public function getAyiDetail(){
        $ayi_id = I('get.ayi_id');
        $db = M('jober');

        $info = $db->where(array('id'=>$ayi_id))->find();
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $info;

        $this->ajaxReturn($data);
    }
    /*特色服务内容*/
    public function getSpecialList(){
        $db = M('special_service');
        $list = $db->select();
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $list;
        $this->ajaxReturn($data);
    }

    /*培训科目列表*/
    public function getCourseList(){

        $db = M('train_course');
        $list = $db->where(array('status'=>1))->select();
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $list;
        $this->ajaxReturn($data);
    }

    /*师资列表*/
    public function getTeacherList(){

        $db = M('teacher_power');
        $list = $db->where(array('status'=>1))->select();
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $list;
        $this->ajaxReturn($data);
    }

    /*师资详情*/
    public function getTeacherDetail(){

        $id = I('get.id');
        $db = M('teacher_power');
        $info = $db->where(array('id'=>$id))->find();
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $info;
        $this->ajaxReturn($data);
    }

    /*公司列表*/
    public function getCompanyList(){

        $db = M('sub_company');
        $list = $db->where(array('status'=>1))->select();
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $list;
        $this->ajaxReturn($data);
    }
    /*公司信息*/
    public function getCompanyInfo(){
        $db = M('company_info');
        $info = $db->where(array('status'=>1))->find();
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $info;
        $this->ajaxReturn($data);
    }
    /*高单列表*/
    public function getPubTaskList(){
        $db = M('pub_task');
        $list = $db->where(array('status'=>1))->select();
        foreach ($list as &$value){
            $value['create_time'] = date("Y/m/d",$value['create_time']);
        }
        $data['status'] = 1;
        $data['info'] = "成功";
        $data['data'] = $list;
        $this->ajaxReturn($data);
    }
    /*我要应聘*/
    public function  applyOp(){
        $phone = I('get.phone');
        $name = I('get.name');
        $task_id = I('get.task_id');
        $db = M('apply_task');
        $subdata = array(
            'phone' => $phone,
            'name' => $name,
            'task_id' => $task_id,
            'create_time' => time()
        );
        $is_exist = $db->where(array('phone'=>$phone))->find();
        if($is_exist && $is_exist['create_time'] - time() < 60 ){
            $data['status'] = 2;
            $data['info'] = "请一分钟之后操作";
            $this->ajaxReturn($data);
        }

        $res = $db->add($subdata);
        if($res){
            $data['status'] = 1;
            $data['info'] = "提交成功";
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['info'] = "提交失败";
            $this->ajaxReturn($data);
        }
    }
	 /*添加会员*/
    public function  saveMember(){
        $username = I('get.username');
        $nick = I('get.nick');
        $open_id = I('get.openid');
        $db = M('member');
        $subdata = array(
            'username' => json_encode($username),
            'nick' => json_encode($nick),
            'reg_time' => time(),
            'open_id' => $open_id,
            'ip' => $_SERVER['REMOTE_ADDR'],
        );
        $is_exist = $db->where(array('open_id'=>$open_id))->find();
        if($is_exist){
			 
             $res = $db->where(array('open_id'=>$open_id))->save(array('login_time'=>time()));
        }else{
			$res = $db->add($subdata);
		}
        if($res){
            $data['status'] = 1;
            $data['info'] = "提交成功";
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['info'] = "提交失败";
            $this->ajaxReturn($data);
        }
    }

    /*立即预约*/
    public function  orderAiOp(){
        $phone = I('get.phone');
        $name = I('get.name');
        $jober_id = I('get.jober_id');
        $db = M('order_ayi');
        $subdata = array(
            'phone' => $phone,
            'name' => $name,
            'jober_id' => $jober_id,
            'create_time' => time()
        );
        $is_exist = $db->where(array('phone'=>$phone))->find();
        if($is_exist && $is_exist['create_time'] - time() < 60 ){
            $data['status'] = 2;
            $data['info'] = "请一分钟之后操作";
            $this->ajaxReturn($data);
        }

        $res = $db->add($subdata);
        if($res){
            $data['status'] = 1;
            $data['info'] = "提交成功";
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['info'] = "提交失败";
            $this->ajaxReturn($data);
        }
    }
    /*我要选课*/
    public function  joinTrainOp(){
        $phone = I('get.phone');
        $name = I('get.name');
        $courses = I('get.courses');
        $db = M('join_train');
        $subdata = array(
            'phone' => $phone,
            'name' => $name,
            'courses' => $courses,
            'create_time' => time()
        );
        $is_exist = $db->where(array('phone'=>$phone))->find();
        if($is_exist && $is_exist['create_time'] - time() < 60 ){
            $data['status'] = 2;
            $data['info'] = "请一分钟之后操作";
            $this->ajaxReturn($data);
        }

        $res = $db->add($subdata);
        if($res){
            $data['status'] = 1;
            $data['info'] = "提交成功";
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['info'] = "提交失败";
            $this->ajaxReturn($data);
        }
    }
    /*介绍操作*/
    public function subRecommendData(){
        $subdata = I('get.');
        if($subdata){
            $db = M('recommend_record');
            $subdata['sub_time'] = time();
            $subdata['op_name'] = json_encode($subdata['op_name']);

            $res = $db->add($subdata);
            if($res){
                $data['status'] = 1;
                $data['info'] = "提交成功";
                $data['data'] = $subdata;
                $this->ajaxReturn($data);
            }else{
                $data['status'] = 0;
                $data['info'] = "提交失败";
                $this->ajaxReturn($data);
            }
        }else{
            $data['status'] = 0;
            $data['info'] = "提交失败";
            $this->ajaxReturn($data);
        }
    }
    /*获取微信api*/
    public function getWxOpenId(){
        $code = I('get.code');
        $db = M('config');
        $config = $db->select();
        $arr = array();
        foreach ($config as $value){
            $arr[$value['key']] =  $value['value'];
        }

        $APPID = $arr['app_id'];
        $SECRET = $arr['app_secret'];
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$APPID}&secret={$SECRET}&js_code={$code}&grant_type=authorization_code";

        $header = array(
            'content-type'=> 'application/json'
        );
        $result = get_contents($url,$header);
        $rsp = json_decode($result,true);
        if($rsp['openid']){
            $data['status'] = 1;
            $data['info'] = "成功";
            $data['data'] = $rsp['openid'];
        }else{
            $data['status'] = 0;
            $data['info'] = "失败";
        }

        $this->ajaxReturn($data);
    }

}