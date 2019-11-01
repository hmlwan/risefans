<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/3/16
 * Time: 15:44
 */

namespace Mobile\Controller;


use Think\Controller;

class UploadController extends Controller
{

    //图片处理
    public function upload(){

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath  =      './Public/Uploads/'; // 设置附件上传目录
        // 上传文件
        $info   =  $upload->upload();

        if(!$info['file']) {
            // 上传错误提示错误信息
            $this->error($upload->getError());exit();
        }else{
            // 上传成功
            $pic=$info['file']['savepath'].$info['file']['savename'];
            $url='/Uploads'.ltrim($pic,".");
            $data['pic'] = $pic;
            $data['url'] = $url;
            $data['error'] = 0;
            $this->ajaxReturn($data);
        }
    }

}