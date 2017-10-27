<?php

class Default_Model_MonthlyLeavesLog extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_monthly_leaves_log';
    protected $_primary = 'id';


    public function resetConfirmation($user_id, $remainingLeaves)
    {
        $logDate = date('Y-m-d');
        $logMonth = date('m');

        if ($this->hasLog($user_id, $logMonth)) {
            return false;
        } else {
            $this->insert(
                [
                    'user_id' => $user_id,
                    'logged_on' => $logDate,
                    'remaining_leaves' => $remainingLeaves
                ]
            );

            return true;
        }
    }

    public function hasLog($user_id, $logMonth)
    {
        $select = $this->select()
            ->where('user_id = ?', $user_id)
            ->where('month(logged_on) = ?', $logMonth);
        $data = $this->fetchAll($select)->toArray();

        if ($data) {
            return true;
        }

        return false;   // Till fetching is possible.
    }
}
