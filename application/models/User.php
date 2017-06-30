<?php
/**
 * 后台用户模型
 * Class UserModel
 */
class UserModel extends DbsqlModel {
    public function __construct()
    {
        parent::__construct();
        $this->_table = 'cp_user';
    }


    public function getInfoByEmail($email){
        return $this->setTable($this->_table)
                    ->setCondition(['email' => $email])
                    ->setField('*')
                    ->_db_get();
    }

    public function setToken($userId, $token){
        return $this->setTable($this->_table)
                    ->setCondition(['id' => $userId])
                    ->setData(['token' => $token])
                    ->_db_update();
    }


    public function getInfoByToken($token){
        return $this->setTable($this->_table)
                    ->setCondition(['token' => $token])
                    ->setField('*')
                    ->_db_get();
    }

}