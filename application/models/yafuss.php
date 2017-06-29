<?php

class yafussModel extends  DbsqlModel {

    public function __construct()
    {
        parent::__construct();
        $this->_table = 'yaf_uss';
    }

    public function getList(){
        $filed = '*';
        return $this->setTable($this->_table)
                    //->setCondition($condition)
                    ->setField($filed)
                    ->_db_select();
        //$lastSql = $this->_db_lastSql();
    }
}