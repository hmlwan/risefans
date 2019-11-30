<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
use Think\Page;
class ConfigController extends AdminController {
	public function _initialize(){
		parent::_initialize();
	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	
	public function index(){

        $string = I('get.string','','');
        $string = trim($string);
        $where = array();
        if($string){
            $where['_string'] = "`key`='{$string}' OR `name` like '%{$string}%'";
        }

        $list = M('config')->where($where)->select();
        $this->assign('list',$list);
        $this->display();
     }

     public function updateCofig(){
	    if(IS_POST){
	        $type = I('post.type','1','');
	        $title = I('post.title','','');
	        $en_name = I('post.en_name','','');
	        $sub_value = I('post.sub_value','','');
	        $sub_value_img = I('post.sub_value_img','','');
	        $sub_flag = I('post.sub_flag','','');

            if(!$title || !$en_name ){
                $this->error('请输入必填项');
            }

            if($type == 2){
                if($_FILES["sub_value_img"]["tmp_name"]){
                    $sub_value_img  = $this->upload($_FILES["sub_value_img"]);
                }
            }

	        $save_data = array(
	            'key' => $en_name,
                'value' => $type == 1? $sub_value : $sub_value_img,
                'type' => $type,
                'name' => $title
            );

	        if($sub_flag){ /*编辑*/
                if($type==2 && !$_FILES["sub_value_img"]["tmp_name"]){
                    unset($save_data['value']);
                }
                $res = M("config")->where(array('key'=>$en_name))->save($save_data);
            }else{ /*新增*/
                $is_exist = M("config")->where(array('key'=>$en_name))->find();
                if($is_exist){
                    $this->error('已存在英文字段名');
                }
                $res = M("config")->add($save_data);
            }
            if($res){
                $this->success('配置修改成功',U('index'));
            }else{
                $this->error('配置修改失败');
            }
        }else{
            $sub_flag = I('get.sub_flag');
            $key = I('get.key');
            $this->assign('sub_flag',$sub_flag);

            $res = M("config")->where(array('key'=>$key))->find();
            $this->assign('info',$res);
            $this->display();
        }
     }
     public function del(){
         $key = I('post.key');
         $res = M("config")->where(array('key'=>$key))->delete();
         if($res){
             $this->success('删除成功',U('index'));
         }else{
             $this->error('删除失败');
         }
     }

     public function carousel() {

         $model = M ( 'carousel' );

         $string = I('string');

         $where = array();
         if($string){
             $where["_string"] = "username like %$string% OR phone like %$string%" ;
         }

         // 查询满足要求的总记录数
         $count = $model->where ( $where )->count ();
         // 实例化分页类 传入总记录数和每页显示的记录数
         $Page = new \Think\Page ( $count, 20 );
         //将分页（点击下一页）需要的条件保存住，带在分页中
         // 分页显示输出
         $show = $Page->show ();
         //需要的数据
         $field = "*";
         $list = $model->field ( $field )
             ->where ( $where )
             ->order ("id desc" )
             ->limit ( $Page->firstRow . ',' . $Page->listRows )
             ->select ();
        $this->assign ( 'list', $list ); // 赋值数据集
        $this->assign ( 'page', $show ); // 赋值分页输出
        $this->display (); // 输出模板
    }
    public function editcarousel(){
       $db =  M("carousel");
        if(IS_POST){
            $type = I('post.type','1','');
            $status = I('post.status');
            $id = I('post.id');
            if($_FILES["img"]["tmp_name"]){
                $img  = $this->upload($_FILES["img"]);
            }else{
                $img = I('oldimg');
            }
            $save_data = array(
                'type' => $type,
                'status' => $status,
                'img' => $img,
                'op_time' => time()
            );

            if($id){ /*编辑*/
                $res = M("carousel")->where(array('id'=>$id))->save($save_data);
            }else{ /*新增*/
                $res = M("carousel")->add($save_data);
            }
            if($res){
                $this->success('修改成功',U('carousel#0#2'));
            }else{
                $this->error('修改失败');
            }
        }else{
            $id = I('get.id');
            $res = $db->where(array('id'=>$id))->find();
            $this->assign('info',$res);
            $this->display();
        }
    }

    public function delcarousel(){
        $id = I('post.id');
        $res = M("carousel")->where(array('id'=>$id))->delete();
        if($res){
            $this->success('删除成功',U('carousel'));
        }else{
            $this->error('删除失败');
        }

    }

    public function my_ad_config() {

        $model = M ( 'my_ad' );

        $string = I('string');

        $where = array();
        if($string){
            $where["_string"] = "username like %$string% OR phone like %$string%" ;
        }

        // 查询满足要求的总记录数
        $count = $model->where ( $where )->count ();
        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new \Think\Page ( $count, 20 );
        //将分页（点击下一页）需要的条件保存住，带在分页中
        // 分页显示输出
        $show = $Page->show ();
        //需要的数据
        $field = "*";
        $list = $model->field ( $field )
            ->where ( $where )
            ->order ("id desc" )
            ->limit ( $Page->firstRow . ',' . $Page->listRows )
            ->select ();
        $pcount = M("member")->count();

        foreach ($list as &$value){
            $read_num = M("my_ad_detail")->where(array('ad_id'=>$value['id']))->count();
            $value["read_num"] = $read_num;
            $read_num_rate = $read_num /$pcount;
            $value["read_num_rate"] = round($read_num_rate,2)*100;
        }
        $this->assign ( 'list', $list ); // 赋值数据集
        $this->assign ( 'page', $show ); // 赋值分页输出
        $this->display (); // 输出模板
    }
    public function adconfig_edit(){
        $db =  M("my_ad");
        if(IS_POST){
            $status = I('post.status');
            $id = I('post.id');
            if($_FILES["img"]["tmp_name"]){
                $img  = $this->upload($_FILES["img"]);
            }else{
                $img = I('oldimg');
            }
            $save_data = array(
                'status' => $status,
                'ad_title' => I('ad_title'),
                'img' => $img,
                'op_time' => time()
            );

            if($id){ /*编辑*/
                $res = $db->where(array('id'=>$id))->save($save_data);
            }else{ /*新增*/
                $res = $db->add($save_data);
            }
            if($res){
                $this->success('修改成功',U('my_ad_config#0#3'));
            }else{
                $this->error('修改失败');
            }
        }else{
            $id = I('get.id');
            $res = $db->where(array('id'=>$id))->find();
            $this->assign('info',$res);
            $this->display();
        }
    }
    public function delmyad(){
        $id = I('post.id');
        $res = M("my_ad")->where(array('id'=>$id))->delete();
        if($res){
            $this->success('删除成功',U('index'));
        }else{
            $this->error('删除失败');
        }

    }
}