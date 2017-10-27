<?php

class Default_WalkController extends Zend_Controller_Action
{
    protected $auth;
    protected $loginUserId;
    protected $department_id;
    /**
     * Function that would be called before every action
     *
     * @return void
     */
    public function init()
    {
        $this->auth = Zend_Auth::getInstance();
        if ($this->auth->hasIdentity()) {
            $this->loginUserId = $this->auth->getStorage()->read()->id;
            $this->department_id = $this->auth->getStorage()->read()->department_id;
        }
    }

    /**
     * Index Action for Walk Controller
     *
     * @return void
     */
    public function indexAction()
    {
        // Get Currently logged in user
        $auth = $this->auth;

        // All the employees under logged in user's department.
        $employees = $this->fetchEmployeesForDepartment($this->department_id);

        // Give option handles for executive decisions.

        // Pass data to the view
        $this->view->assign('employees', $employees);
    }

    /**
     * Process resetting of leaves for all employees.
     *
     * @return void
     */
    public function leavesAction()
    {
        $employeeleavesModel = new Default_Model_Employeeleaves();
        $loginUserId = $this->loginUserId;

        $year = date('Y');

        $postedArr = [
            'leave_limit' => "1", // Leaves getting assigned to the user
            'alloted_year' => $year, // Year of allotment
        ];

        $employees = $this->fetchEmployeesForDepartment($this->department_id);

        array_walk(
            $employees, function ($employee) use ($employeeleavesModel, $loginUserId) {
                $leaveLimit = $employee['leaves']['emp_leave_limit'];
                $usedLeaves = $employee['leaves']['used_leaves'];
                $user_id = $employee['user_id'];

                $remainingLeaves = $leaveLimit - $usedLeaves;

                $assignedLeaves = $this->appropriateLeaves($remainingLeaves);
                $postedArr['leave_limit'] = $assignedLeaves;
                $totalLeaves = $remainingLeaves + $assignedLeaves;

                // Logging the Allotment
                $saveID = $employeeleavesModel->saveallotedleaves(
                    $postedArr,
                    $totalLeaves,
                    $user_id,
                    $loginUserId
                );

                // Updating Actual table
                $empLeave = $this->resetEmployeeLeaves(
                    $user_id,
                    $totalLeaves,
                    0,
                    $loginUserId
                );
            }
        );

        $this->_redirect('walk');
    }

    public function appropriateLeaves($remainingLeaves)
    {
        if ($remainingLeaves > 0) {
            return 1;
        }
        return abs($remainingLeaves) + 1;
    }

    public function resetEmployeeLeaves($user_id,$emp_leave_limit,$isleavetrasnfer,$loginUserId)
    {
        $date= gmdate("Y-m-d H:i:s");

        $db = Zend_Db_Table::getDefaultAdapter();

        $query = "INSERT INTO `main_employeeleaves`"
                . " (user_id,emp_leave_limit,used_leaves,alloted_year,createdby,modifiedby,createddate,modifieddate,isactive,isleavetrasnferset)"
                . " VALUES (".$user_id.",".$emp_leave_limit.",'0',year(now()),".$loginUserId.",".$loginUserId.",'".$date."','".$date."',1,".$isleavetrasnfer.")"
                . " ON DUPLICATE KEY"
                . " UPDATE emp_leave_limit='".$emp_leave_limit."',modifiedby='".$loginUserId."',used_leaves='0',modifieddate='".$date."',isactive = 1,isleavetrasnferset=".$isleavetrasnfer." ";

        $rows = $db->query($query);
    }


    public function fetchEmployeesForDepartment($department_id)
    {
        $department = new Default_Model_Departments();
        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "select count(*) total_employees"
                    . " from main_employees_summary"
                    . " where department_id = " . $department_id
                    . " ";
        $result = $db->query($query)->fetch();
        $empCount = $result['total_employees'];

        $employees = $department->getEmpForDepartment($department_id, 1, $empCount);

        // Check leaves for every employee
        $leaveModel = new Default_Model_Employeeleaves();
        $emps = array_map(
            function ($employee) use ($leaveModel) {
                $leaves = $leaveModel->getsingleEmployeeleaveData($employee['user_id']);
                $employee['leaves'] = array_pop(
                    $leaves
                );
                return $employee;
            },
            $employees
        );

        return $emps;
    }
}
