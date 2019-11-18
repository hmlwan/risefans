<?php
namespace Mobile\Controller;
use Common\Controller\CommonController;
class OrderController extends HomeController {
 	public function _initialize(){
 		parent::_initialize();

 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	/*充值*/
	public function recharge(){

	    $this->display();
    }
    /*充值记录*/
    public function recharge_record(){

        $this->display();
    }
    /*提现*/
    public function withdraw(){

        $this->display();
    }
    /*提现记录*/
    public function withdraw_record(){

        $this->display();
    }












}
