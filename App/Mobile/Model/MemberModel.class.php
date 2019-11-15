<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-3-8
 * Time: 下午2:23
 */

namespace Mobile\Model;
use Think\Model;

class MemberModel extends Model{

    /**
     * 验证密码长度在6-20个字符之间
     * @param $pwd
     * @return bool
     */
    public function checkPwd($pwd){
//         $pattern="/^[1-9|a-z|A-Z]{6,20}$/";
//         $pattern2="/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-])+/";
//         if(preg_match($pattern, $pwd)||preg_match($pattern2, $pwd)){
//             return true;
//         }else{
//             return false;
//         }

    	if(strlen($_POST['pwd'])<6){
    		return false;
    	}
    	if(strlen($_POST['pwd'])>20){
    		 return false;
    	}
    	return true;
    }

    public function logCheckEmail($email){
        $where['email'] = $email;
        $info = $this->where($where)->find();
        if($info){
            return $info;
        }else{
            return false;
        }
    }
    public function logCheckUsername($username){
        $where['username'] = $username;
        $info = $this->where($where)->find();
        if($info){
            return $info;
        }else{
            return false;
        }
    }
    public function logCheckPhone($phone){
        $where['phone'] = $phone;
        $info = $this->where($where)->find();
        if($info){
            return $info;
        }else{
            return false;
        }
    }
    public function logCheckMo($mo){
        $where['phone'] = $mo;
        $info = $this->where($where)->find();
        if($info){
            return $info;
        }else{
            return false;
        }
    }

    function checkPhoneCode($code){
        if (session('code')!=$code){
            return  false;
        }else {
            return true;
        }
    }
    /*获取用户信息*/
    public function get_info_by_id($id){
        $where['m.member_id'] = $id;
        $data = $this->alias('m')
            ->field('m.*,mi.*,m.username as m_user')
            ->join("LEFT JOIN blue_member_info mi ON mi.member_id=m.member_id")
            ->where($where)
            ->find();
        return $data;
    }
    /*获取用户信息*/
    public function get_allinfo($where,$order){

        $data = $this->alias('m')
            ->field('m.*,mi.*')
            ->join("LEFT JOIN blue_member_info mi ON mi.member_id=m.member_id")
            ->where($where)
            ->order($order)
            ->select();
        return $data;
    }

    public function childs ($id){
        $str = '';
        $ids = $this->where(array('pid'=>$id))->field('member_id')->select();
        if($ids){
            foreach ($ids as $value){
                $str .= "," . $value['member_id'];
                $str .= $this->childs($value['id']);
            }
        }
        return $str;
    }
    public function rechilds ($id){
        $str = '';
        $i = 0;
        $ids = $this->where(array('pid'=>$id))->field('member_id,unique_code')->select();
        if($ids){
            $i = $i+1;
            foreach ($ids as $value){
                $str .= "," . $value['member_id'];
                if($i < 2){
                    $str .= $this->childs($value['unique_code']);
                }

            }
        }
        return $str;
    }

}