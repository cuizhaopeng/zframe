<?php 
namespace app\osys\osys_init;

class ModelRestrict
{
	public $model;
	public $col;
	public $show;
	public $fn = false;
	public $own_col;

	public function __construct($attr_array,$own_col="id"){
		$this->model = $attr_array[0];
		$this->col = $attr_array[1];
		if (sizeof($attr_array) > 2) {
			if (is_object($attr_array[2])) {
				$this->show = $attr_array[1];
				$this->fn = $attr_array[2];
			} else {
				$this->show = $attr_array[2];
				$this->fn = $attr_array[3];
			}
		}
		$this->own_col = $own_col;
	}

	public static function create($attr_array,$own_col="id"){
		$mr = new ModelRestrict($attr_array,$own_col);
		return $mr;
	}

	public function is_used($value){
		$class_name = "\\App\\".$this->model;
		$model = new $class_name();
		$collection = $model->where(function($query) use ($value){
			$query->orWhere($this->col,$value);
			$query->orWhere($this->col,"LIKE","%{".$value."}%");
		});
		if ($this->fn) {
			$collection->where($this->fn);
		}
		//print_r($collection->get()->toArray());
		if ($collection->get()->isEmpty()) {
			return false;
		} else {
			return true;
		}
	}
}
/**
* 
*/
/**
* 
*/
class ModelItem
{
	protected $primary = false;
	protected $lock = array();
	protected $unique = false;
	protected $cal = array();
	protected $msg = "";

	public function init_string(){
		$init_string = "";
		if ($this->primary != false) {
			$init_string = ",PRIMARY KEY (".$this->primary.")";
		}
		foreach ($this as $key => $value) {
			if (in_array($key, ["lock","unique","cal","msg","primary"])) {
				continue;
			}
			$init_string .= ",".$key." ".$value->type;
			if (!isset($value->def)) {
				$init_string .= " NOT NULL";
			} else if ($value->def == "NULL" || $value->def == "null"){
				$init_string .= " DEFAULT NULL";
			} else if (strpos($value->def, "CURRENT_TIMESTAMP") === 0) {
				$init_string .= " NOT NULL DEFAULT ".$value->def;
			} else {
				$init_string .= " NOT NULL DEFAULT '".$value->def."'";
			}
			$init_string .= " COMMENT '".$value->name."'";
		}
		return strlen($init_string)==0?"":substr($init_string, 1);
	}

	public function primary($key=false){
		$this->primary = $key;
	}

	public function lock(ModelRestrict $mr){
		$this->lock[] = $mr;
	}
	
	public function col($col){
		if (!isset($this->$col)) {
			$this->$col = new TabelCol();
		}
		return $this->$col;
	}

	public function msg(){
		return $this->msg;
	}

	public function cal($para,$result,$is_cal = false,$fn = ""){
		if (is_object($is_cal)) {
			$fn = $is_cal;
			$is_cal = 1;//true for "is calculating depend on owner trigger, other value for the depending col"
		}
		$this->cal[] = array($para,$result,$is_cal,$fn);
		if (is_array($para)) {
			foreach ($para as $p) {
				$this->$p->cal_trigger = true;
			}
		} else {
			$this->$para->cal_trigger = true;
		}
		if (is_array($result)) {
			foreach ($result as $r) {
				$this->$r->cal_result = $is_cal;
			}
		} else {
			$this->$result->cal_result = $is_cal;
		}
		//if ($cal_switch != false) {
			//$this->$cal_switch->cal_switch = true;
			//$this->$cal_switch->cal_switch_item = $result;
		//}
	}

	public function get_cal(){
		return $this->cal;
	}

	public function unique(){
		$this->unique = func_get_args();
	}

	public function get_unique(){
		return $this->unique;
	}

	public function get_only(){
		foreach ($this as $key => $col) {
			if (is_object($col) && $col->only) {
				return $col->only;
			}
		}
		return false;
	}


	public function is_used($value){
		if (sizeof($this->lock) == 0) {
			return false;
		} else {
			foreach ($this->lock as $lock) {
				if ($lock->is_used($value)) {
					return true;
				}
			}
			return false;
		}
	}

	public function valid_value($col,$value){
		if (isset($this->$col)) {
			if (is_callable($this->$col->restrict)) {
				$fn = $this->$col->restrict;
				$msg = $fn($value);
				if ($msg === true) {
					return true;
				} else {
					$this->msg = $msg;
					return false;
				}
			} else if (is_array($this->$col->restrict) && sizeof($this->$col->restrict) > 0 && !in_array($value,$this->$col->restrict)) {
				$this->msg = "数据只能为【".array_to_string($this->$col->restrict)."】";
				return false;
			} else {
				if ($this->$col->is_bind($value)) {
					return true;
				} else {
					$this->msg = "数据未设置";
					return false;
				}
			}
		} else {
			$this->msg = "数据错误";
			return false;
		}
	}

	public function items(){
		$r_array = array();
		foreach ($this as $key => $value) {
			if (in_array($key, ["lock","unique","cal","msg","primary"])) {
				continue;
			}
			$r_array[] = $key;
		}
		return $r_array;
	}

	public function item_titles(){
		$r_array = array();
		foreach ($this as $key => $value) {
			if (in_array($key, ["lock","unique","cal","msg","primary"])) {
				continue;
			}
			$r_array[] = $this->key->name;
		}
		return $r_array;
	}

}


class TabelCol
{
	public $name = "N/A";
	public $type = "varchar(255)";
	public $type_para = array();
	//public $def = false;//"null" or other value
	public $restrict = array();
	public $input = "init";
	public $bind = array();
	public $bind_addition = array();//附加数据
	public $multiple = false;
	public $only = false;
	public $seperate_input = false;//分项输入，级别最低，其他有条件生效时，则该项不生效
	//*************************************
	public $placeholder = false;
	//input placeholder
	//*************************************
	public $history = true;
	//true for all history in this column,
	//false for no history
	//function for where condition
	//*************************************
	//size setting
	//false for default
	public $size = false;
	public $textarea = false;
	//*************************************
	//calculate setting
	public $cal_trigger = false;
	public $cal_result = false;
	public $cal_switch = false;
	public $cal_switch_item = "";
	//tip setting
	public $tip = false;
	
	public function __construct()
	{
		# code...
	}

	//建议的中文名
	public function name($name){
		$this->name = $name;
		return $this;
	}

	public function only($value){
		$this->only = $value;
		return $this;
	}


	public function type($type){
		$this->type = $type;
		$this->type_para = func_get_args();
		//删除第一个字段名称
		array_shift($this->type_para);
		return $this;
	}


	public function def($def){
		$this->def = $def;
		return $this;
	}

	/*
	restrict的几种设定：
	1、直接并列设置各个限制参数，函数通过func_get_args读取；
	2、设置为数组，直接读取。注意：设置key值，可以在录入界面中显示key（key不能设置为数字，否则不显示），实际值仍是value值；
	restrict限制的值会体现在录入的select框中，同时在写入时进行验证。
	*/
	public function restrict($res){
		if (is_callable($res)) {
			$this->restrict = $res;
		} else {
			$res = is_array($res) ? $res : func_get_args();
			$this->restrict = array_merge($this->restrict,$res);
		}
		return $this;
	}

	public function input($status="init"){
		$this->input = $status;//"init","cal","exec"
		return $this;
	}

	/*
	seperate_input设定：
	1、设置为数组；
	2、用数组的每个值在输入时进行拆分，即多个框对应一个字段，写入时多个字段转换为multiple格式，即{}{}；
	3、seperate_input级别最低，当其他有条件生效时（如bind、restrict），将不生效
	*/
	public function seperate_input($seperate_array){
		$seperate_array = is_array($seperate_array) ? $seperate_array : func_get_args();
		$this->seperate_input = $seperate_array;
		$this->history(false);//关闭历史记录
		return $this;
	}

	public function size($size=false){
		$this->size = $size;
		return $this;
	}

	public function textarea($textarea=2){
		$this->textarea = $textarea;
		return $this;
	}

	public function placeholder($placeholder=false){
		$this->placeholder = $placeholder;
		return $this;
	}

	public function tip($tip){
		if (substr($tip,0,1) != "<") {
			$tip = "<span style='position:absolute;bottom:3px;right:5px;'>".$tip."</span>";
		}
		$this->tip = $tip;
		return $this;
	}

	public function bind($model,$col,$show="",$fn=""){
		if ($show == "") {
			$show = $col;
		}
		if (is_object($show)) {
			$this->bind = array($model,$col,$col,$show);
		} else if ($fn != "") {
			$this->bind = array($model,$col,$show,$fn);
		} else {
			$this->bind = array($model,$col,$show);
		}
		return $this;
	}

	public function bind_addition($addition){
		if (is_array($addition)) {
			$this->bind_addition = $addition;
		} else {
			$this->bind_addition = func_get_args();
		}
		return $this;
	}

	public function is_bind($value){
		if (sizeof($this->bind) == 0) {
			return true;
		} else if (sizeof($this->bind_addition) > 0 && in_array($value,$this->bind_addition)) {
			return true;
		} else {
			$class_name = "\\App\\".$this->bind[0];
			$bind_model = new $class_name();
			if ($this->multiple === false) {
				$result = $bind_model->where($this->bind[1],$value);
				if (isset($this->bind[3])) {
					$result->where($this->bind[3]);
				}
				if ($result->get()->isEmpty()) {
					return false;
				} else {
					return true;
				}
			} else {
				$value = multiple_to_array($value);
				$result = $bind_model->whereIn($this->bind[1],$value);
				if (isset($this->bind[3])) {
					$result->where($this->bind[3]);
				}
				if (sizeof($result->get()) != sizeof($value)) {
					return false;
				} else {
					return true;
				}
			}
			
		}
	}

	public function history($history=true){
		$this->history = $history;
		return $this;
	}

	public function multiple($num=1){
		$this->multiple = $num;
		return $this;
	}

}

/*
* 权限定义对象
*/
class AuthObj
{
	public $name = "";
	public $code = "";
	public $func = "";
	public $prerequisite = "";
	public $upper = "";
	public $children = array();

	public function __construct($data = array()){
		if (sizeof($data) > 0) {
			$this->setAttr($data);
		}
	}

	public function setAttr($attrArray){
		foreach ($attrArray as $key => $value) {
			if (in_array($key, array("name", "code", "func", "prerequisite", "upper"))) {
				$this->$key = $value;
			}
		}
	}
}