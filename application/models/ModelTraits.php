<?php
trait ModelTrait
{
    // status config
    /**
     * @var int 数据库记录的已删除状态
     */
    private $_deleted_status = 1;
    /**
     * @var int 数据库记录的未删除状态
     */
    private $_not_delete_status = 0;

    /**
     * @var string 特定方法操作时的特定表名
     */
    private $_table;
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

    /**
     * 单表条件查询
     * @author yangbao
     *
     */
    private function select()
    {
        if (! empty($this->_join)) {
            $result = $this->db->select($this->_table, $this->_join, $this->_field, $this->_condition);
        } else {
            $result = $this->db->select($this->_table, $this->_field, $this->_condition);
        }
        $this->clear();

        return $result;
    }

    private function get()
    {
        $result = $this->db->get($this->_table, $this->_field, $this->_condition);
        $this->clear();

        return $result;
    }

    private function insert()
    {
        $result = $this->db->insert($this->_table, $this->_data);
        $this->clear();

        return $result;
    }

    private function has()
    {
        if (! empty($this->_join)) {
            $result = $this->db->has($this->_table, $this->_join, $this->_condition);
        } else {
            $result = $this->db->has($this->_table, $this->_condition);
        }
        $this->clear();

        return $result;
    }

    private function count()
    {
        if (! empty($this->_join)) {
            $result = $this->db->count($this->_table, $this->_join, '*', $this->_condition);
        } else {
            $result = $this->db->count($this->_table, $this->_condition);
        }
        $this->clear();

        return $result;
    }

    private function update()
    {
        $result = $this->db->update($this->_table, $this->_data, $this->_condition);
        $this->clear();

        return $result;
    }

    private function delete()
    {
        $result = $this->db->delete($this->_table, $this->_condition);
        $this->clear();

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

    public function printError()
    {
        helper_debug::d($this->db->error());

        return $this;
    }

    public function lastSql() {
        return str_replace('"', '', $this->db->last_query());
    }

    public function printSql()
    {
        echo "\n<br\>\n" . str_replace('"', '', $this->db->last_query()) . "\n<br\>\n";

        return $this;
    }

    private function printParam()
    {
        helper_debug::d($this->_table, $this->_condition, $this->_field, $this->_data, $this->_join);

        return $this;
    }

    private function clear()
    {
        $this->_table = null;
        $this->_condition = null;
        $this->_field = null;
        $this->_data = null;
        $this->_join = null;
    }

    private function from($table)
    {
        return $this->setTable($table);
    }

    public function leftJoin($left_table, $main_table_relation, $left_table_relation = null)
    {
        return $this->setLeftJoin($left_table, $main_table_relation, $left_table_relation = null);
    }
}
