<?php
	//quick version check - PHP <5.3 is obsolete
	if (!defined('PHP_VERSION_ID')){
		$version=explode('.', PHP_VERSION);
		define('PHP_VERSION_ID', ($version[0]*10000+$version[1]*100+$version[2]));
	}
	if(PHP_VERSION_ID<50300) die("PHP >=5.3 is required");

	class WCException extends Exception{
		public function __construct(){
			$args=func_get_args();
			if(is_numeric($args[0]))
				$code=array_shift($args);
			else
				$code=0;
			if(is_a($args[0], "Exception"))
				$previous=array_shift($args);
			else
				$previous=null;
			$message=array_shift($args);
			parent::__construct(vsprintf($message, $args), $code, $previous);
		}
	}

	class HTTPError extends WCException{
		public function __construct(){
			$args=func_get_args();
			$code=array_shift($args);
			$title=array_shift($args);
			array_unshift($args, $code);
			$this->title=$title;
			call_user_func_array("WCException::__construct", $args);
		}

		public function getTitle(){
			return $this->title;
		}
	}

	class App{
		public function __construct(){
			$this->routes=array();
		}

		private static function _render_template($_template, $_params){
			extract($_params);
			include $_template;
		}

		public function template($template, $params=array()){
			static $tpl_stack=array(), $call_in_progress=false;
			$params["_app"]=$this;
			if($call_in_progress){
				array_push($tpl_stack, array($template, $params));
			}else{
				$call_in_progress=true;
				ob_start();
				self::_render_template($template, $params);
				$out=ob_get_contents();
				ob_end_clean();
				$call_in_progress=false;
				if($tpl_stack){
					list($template, $params)=array_pop($tpl_stack);
					$params["_content"]=$out;
					$this->template($template, $params);
				}else{
					echo $out;
				}
			}
		}

		public function route($route, $function){
			$this->routes[]=array(sprintf("#^%s\$#", $route), $function);
		}

		public function mount($route, $app){
			$this->routes[]=array(sprintf("#^%s#", $route), $app);
		}

		public function runWith($uri){
			foreach($this->routes as $routeData){
				list($route, $function)=$routeData;
				if(preg_match($route, $uri, $params)){
					$params[0]=$this;
					if(is_a($function, "App")){
						$function->runWith(preg_replace($route, "", $uri));
					}else{
						call_user_func_array($function, $params);
					}
				}
			}
		}

		public function run(){
			$this->runWith(isset($_SERVER["PATH_INFO"])?$_SERVER["PATH_INFO"]:"/");
		}
	}