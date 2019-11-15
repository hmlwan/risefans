<?php
namespace Mobile\Controller;
use Common\Controller\CommonController;
class ExchangeController extends HomeController {
 	public function _initialize(){
 		parent::_initialize();
 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	/*我要兑换*/
	public function buyview(){



	    $this->display();
    }
    /*兑换记录*/
    public function exrecord(){
        $this->display();

    }
}
