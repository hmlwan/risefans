<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
class MenuController extends AdminController {
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
            $where['_string'] = "`cat_id`='{$string}' OR `nav_name` like '%{$string}%'";
        }
        $list = M('nav')->where($where)->order('cat_id asc ,nav_sort asc')->select();
        $this->assign('list',$list);
        $this->display();
     }

     public function edit(){
	    if(IS_POST){
	        $nav_id = I('post.nav_id','','');
	        $nav_name = I('post.nav_name','','');
	        $nav_e = I('post.nav_e','','');
	        $nav_url = I('post.nav_url','','');
	        $cat_id = I('post.cat_id','','');
	        $nav_sort = I('post.nav_sort','','');

	        $save_data = array(
	            'nav_name' => $nav_name,
                'nav_e' => $nav_e ? $nav_e :"&#xe6f7;",
                'nav_url' => $nav_url,
                'nav_sort' => $nav_sort,
                'cat_id' => $cat_id
            );

	        if($nav_id){ /*编辑*/
                $res = M("nav")->where(array('nav_id'=>$nav_id))->save($save_data);
            }else{ /*新增*/
                $res = M("nav")->add($save_data);
            }
            if($res){
                $this->success('操作成功',U('index'));
            }else{
                $this->error('操作失败');
            }
        }else{
            $nav_id = I('get.nav_id');
            $res = M("nav")->where(array('nav_id'=>$nav_id))->find();
            $this->assign('info',$res);
            $this->display();
        }
     }
     public function del(){
         $nav_id = I('post.nav_id');
         $res = M("nav")->where(array('nav_id'=>$nav_id))->delete();
         if($res){
             $this->success('删除成功',U('index'));
         }else{
             $this->error('删除失败');
         }

     }

}