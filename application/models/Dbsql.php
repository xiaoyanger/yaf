<?php
class DbsqlModel extends  BaseModel {
    /**
     * 定义当前的表名
     * @var string
     */
    protected $_table;
    /**
     * @var string 一般表示插入或修改的数据,符合Medoo格式
     */
    private $_data;
    /**
     * @var array 符合Medoo格式的查询条件
     */
    private $_condition = [];
    /**
     * @var array 符合Medoo格式的查询字段
     */
    private $_field = [];
    /**
     * @var array 符合Medoo格式的left_join
     */
    private $_join = [];

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(substr(get_class($this), 0, -6));
    }

    /**
     * 单表条件查询
     * @author yangbao
     *
     */
    public function _db_select()
    {
        if (! empty($this->_join)) {
            $result = $this->db->select($this->_table, $this->_join, $this->_field, $this->_condition);
        } else {
            $result = $this->db->select($this->_table, $this->_field, $this->_condition);
        }
        $this->_db_clear();

        return $result;
    }

    public function _db_get()
    {
        $result = $this->db->get($this->_table, $this->_field, $this->_condition);
        $this->_db_clear();

        return $result;
    }

    public function _db_insert()
    {
        $result = $this->db->insert($this->_table, $this->_data);
        $this->clear();

        return $result;
    }

    public function _db_has()
    {
        if (! empty($this->_join)) {
            $result = $this->db->has($this->_table, $this->_join, $this->_condition);
        } else {
            $result = $this->db->has($this->_table, $this->_condition);
        }
        $this->_db_clear();

        return $result;
    }

    public function _db_count()
    {
        if (! empty($this->_join)) {
            $result = $this->db->count($this->_table, $this->_join, '*', $this->_condition);
        } else {
            $result = $this->db->count($this->_table, $this->_condition);
        }
        $this->_db_clear();

        return $result;
    }

    public function _db_update()
    {
        $result = $this->db->update($this->_table, $this->_data, $this->_condition);
        $this->_db_clear();

        return $result;
    }

    public function _db_delete()
    {
        $result = $this->db->delete($this->_table, $this->_condition);
        $this->_db_clear();

        return $result;
    }

    public function setData($data)
    {
        $this->_data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function setField($field)
    {
        $this->_field = $field;

        return $this;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function setTable($table)
    {
        $this->_table = $table;

        return $this;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function setCondition($condition = [])
    {
        $this->_condition = $condition;

        return $this;
    }

    public function getCondition()
    {
        return $this->_condition;
    }

    public function getJoin()
    {
        return $this->_join;
    }

    public function setJoin($join)
    {
        $this->_join = $join;

        return $this;
    }

    public function setLeftJoin($left_table, $main_table_relation, $left_table_relation = null)
    {
        if (empty($left_table_relation)) {
            $relation = $main_table_relation;
        } else {
            $relation = [$main_table_relation => $left_table_relation];
        }
        $this->_join["[>]{$left_table}"] = $relation;

        return $this;
    }

    public function _db_printError()
    {
        core::dump($this->db->error());

        return $this;
    }

    public function _db_lastSql() {
        return str_replace('"', '', $this->db->last_query());
    }

    public function _db_printSql()
    {
        echo "\n<br\>\n" . str_replace('"', '', $this->db->last_query()) . "\n<br\>\n";

        return $this;
    }

    public function _db_printParam()
    {
        core::dump($this->_table, $this->_condition, $this->_field, $this->_data, $this->_join);

        return $this;
    }

    public function _db_clear()
    {
        $this->_table = null;
        $this->_condition = null;
        $this->_field = null;
        $this->_data = null;
        $this->_join = null;
    }

    public function _db_from($table)
    {
        return $this->setTable($table);
    }

    public function leftJoin($left_table, $main_table_relation, $left_table_relation = null)
    {
        return $this->setLeftJoin($left_table, $main_table_relation, $left_table_relation = null);
    }



}