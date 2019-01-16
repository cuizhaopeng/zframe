<?php
namespace app\osys\osys_init;

use think\Model;
use think\model\concern\SoftDelete;
use think\Db;
use app\osys\model\Ohistory;
use Carbon\Carbon;

abstract class Omodel extends Model
{

    protected $pk = "id";

    /**
     * 软删除设置
     * @var array
     */
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = "2038-01-01 00:00:00";

    protected $item;

    public $defaultItem;//array("id","super_code","procedure","status","version","owner","authority","created_by","create_time","update_time","deleted_time");

    //modifyHistory
	public $modifyHistory = true;

    public function __construct($data = []){
    	parent::__construct($data);

        $class_name = get_class($this);
        if (substr($class_name, 0, 4) != "app\\") {
            throw new \think\Exception("类命名空间错误", 99999);
        } else {
            $this->table = strtolower(str_replace("\\model\\", "_",substr($class_name, 4)));
        }

        $this->defaultItem = new ModelItem();
        $this->defaultItem->primary("id");
        $this->defaultItem->col("id")->name("ID")->type("int(11) AUTO_INCREMENT");
        $this->defaultItem->col("super_code")->name("隔离编码")->def("");
        $this->defaultItem->col("proc")->name("占用流程")->type("int(11)")->def("0");
        $this->defaultItem->col("status")->name("状态")->def("");
        $this->defaultItem->col("version")->name("版本")->def("A");
        $this->defaultItem->col("owner")->name("所有者")->type("int(11)")->def("0");
        $this->defaultItem->col("authority")->name("授权")->def("");
        $this->defaultItem->col("created_by")->name("所有者")->type("int(11)");
        $this->defaultItem->col("create_time")->name("创建时间")->type("timestamp")->def("CURRENT_TIMESTAMP");
        $this->defaultItem->col("update_time")->name("修改时间")->type("timestamp")->def("CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->defaultItem->col("delete_time")->name("删除时间")->type("timestamp")->def("2038-01-01 00:00:00");
    	
        $this->item = new ModelItem();
        $this->cols($this->item);
        
        //var_dump($this->item);
        //echo $this->defaultItem->init_string().",".$this->item->init_string();
    	Db::query("CREATE TABLE IF NOT EXISTS ".$this->table."(".$this->defaultItem->init_string().",".$this->item->init_string().") ENGINE=InnoDB");

    }

    public function cols($item){

    }

     /**
     * 删除当前的记录（软删除重写）
     * @access public
     * @return bool
     */
    /*
    public function delete($force = false)
    {
        if (!$this->isExists() || false === $this->trigger('before_delete', $this)) {
            return false;
        }

        $force = $force ?: $this->isForce();
        $name  = $this->getDeleteTimeField();

        if ($name && !$force) {
            // 软删除
            $this->data($name, $this->autoWriteTimestamp($name));
            //var_dump($this->)
            //dump($this);
            $result = $this->isUpdate()->withEvent(false)->save();
            //var_dump($this);
            //Ohistory::create([
                //"model"  =>  get_class($this),
                //"model_id" =>  is_array($this)?$this["id"]:$this->id,
                //"history" => json_encode(array("key" => "delete_time", "new" => Carbon::now(), "old" => Carbon::parse("2038-01-01 00:00:00"))),
                //"created_by" => 0
            //]);
            $this->withEvent(true);
        } else {
            // 读取更新条件
            $where = $this->getWhere();

            // 删除当前模型数据
            $result = $this->db(false)
                ->where($where)
                ->removeOption('soft_delete')
                ->delete();
        }

        // 关联删除
        if (!empty($this->relationWrite)) {
            $this->autoRelationDelete();
        }

        $this->trigger('after_delete', $this);

        $this->exists(false);

        return true;
    }
    */

/**
     * 更新写入数据
     * @access protected
     * @param  mixed   $where 更新条件
     * @return bool
     */
    protected function updateData($where)
    {
        // 自动更新
        $this->autoCompleteData($this->update);

        // 事件回调
        if (false === $this->trigger('before_update')) {
            return false;
        }

        // 获取有更新的数据
        $data = $this->getChangedData();

        if (empty($data)) {
            // 关联更新
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }

            return false;
        } elseif ($this->autoWriteTimestamp && $this->updateTime && !isset($data[$this->updateTime])) {
            // 自动写入更新时间
            //$data[$this->updateTime] = $this->autoWriteTimestamp($this->updateTime);

            //$this->data[$this->updateTime] = $data[$this->updateTime];
        }

        if (empty($where) && !empty($this->updateWhere)) {
            $where = $this->updateWhere;
        }

        // 检查允许字段
        $allowFields = $this->checkAllowFields(array_merge($this->auto, $this->update));

        // 保留主键数据
        /*
        foreach ($this->data as $key => $val) {
            if ($this->isPk($key)) {
                $data[$key] = $val;
            }
        }
        */
        $data["id"] = $this->getData("id");

        $pk    = $this->getPk();
        $array = [];

        foreach ((array) $pk as $key) {
            if (isset($data[$key])) {
                $array[] = [$key, '=', $data[$key]];
                unset($data[$key]);
            }
        }

        if (!empty($array)) {
            $where = $array;
        }

        foreach ((array) $this->relationWrite as $name => $val) {
            if (is_array($val)) {
                foreach ($val as $key) {
                    if (isset($data[$key])) {
                        unset($data[$key]);
                    }
                }
            }
        }

        // 模型更新
        $db = $this->db(false);
        $db->startTrans();

        try {
            $db->where($where)
                ->strict(false)
                ->field($allowFields)
                ->update($data);

            // 关联更新
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }

            //var_dump($this);
            
            

            $db->commit();

            try {
                if (get_class($this) != "app\\osys\\model\\Ohistory") {
                    $origin = $this->getOrigin();
                    $history = array();
                    foreach ($data as $changeKey => $changeValue) {
                        $history[] = array("key" => $changeKey, "new" => $changeValue, "old" => $origin[$changeKey]);
                    }
                    Ohistory::create([
                        "model"  =>  get_class($this),
                        "model_id" =>  is_array($this)?$this["id"]:$this->id,
                        "history" => json_encode($history),
                        "created_by" => 0
                    ]);
                }
            } catch (\Exception $e){
                throw $e;
            }
            // 更新回调
            $this->trigger('after_update');

            return true;
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }




}