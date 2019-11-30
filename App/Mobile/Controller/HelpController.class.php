<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class HelpController extends HomeController
{

    public function _initialize(){
        parent::_initialize();
    }
    //空操作
    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:face_to_face');
    }
    /*帮助中心*/
    public function index(){

        $art_cate_db = D('article_category');
        $art_db = D('article');

        /*帮助中心类型*/
        $art_list = array();
        $cate_ids =  $art_cate_db->where(array('parent_id'=>6))->getField('id',true);
        if($cate_ids){
            $art_where = array(
                'position_id' => array('in',$cate_ids),
                'status' => 1,
            );
            $art_list = $art_db->where($art_where)
                ->order('is_top desc,add_time desc')
                ->limit(8)
                ->select();
        }
        $this->assign('art_list',$art_list);
        $this->display();
    }
    /*详情*/
    public function detail(){
        $art_db = D('article');
        $position_id = I('position_id');
        if(!$position_id){
            $this->display('Public:404');
        }
        $art_cate_db = D('article_category');
        $art_cate_name = $art_cate_db->where(array('id'=>$position_id))->getField('name');

        $art_list = $art_db->where(array('position_id'=>$position_id,'status' => 1))
            ->order('is_top desc,sort asc')
            ->select();

        $this->assign('art_cate_name',$art_cate_name);
        $this->assign('art_list',$art_list);
        $this->display();
    }
    /*公告*/
    public function post(){
        $art_cate_db = D('article_category');
        $art_db = D('article');

        /*帮助中心类型*/
        $art_list = array();
        $cate_ids =  $art_cate_db->where(array('parent_id'=>127))->getField('id',true);
        if($cate_ids){
            $art_where = array(
                'position_id' => array('in',$cate_ids),
                'status' => 1,
            );
            $art_list = $art_db->where($art_where)
                ->order('is_top desc,add_time desc')
                ->select();
        }
        $this->assign('art_list',$art_list);
        $this->display();
    }
    public function voucher(){
        $this->display();
    }

}