<?php

class Default_WalkController extends Zend_Controller_Action
{

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

        // All the employees under logged in user.
        $department_id = $this->department_id;
        $department = new Default_Model_Departments();

        $db = Zend_Db_Table::getDefaultAdapter();
        $query = "select count(*) employees from main_employees_summary where department_id = " . $department_id . " ";
        $result = $db->query($query)->fetch();
        $cd = $result['employees'];

        $employees = $department->getEmpForDepartment($department_id, 1, $cd);

        // Check leaves for every employee
        // Display the leaves taken by individual employees
        $leaveModel = new Default_Model_Employeeleaves();
        foreach ($employees as $employee) {
            $leaves = $leaveModel->getsingleEmployeeleaveData($employee['user_id']);
            $employee['leaves'] = array_pop(
                $leaves
            );
            // $limit = $empLeaves['emp_leave_limit'];
            // $usedLeaves = $empLeaves['used_leaves'];
            $emps[] = $employee;
        }

        // Give option handles for executive decisions.


        // Pass data to the view
        $this->view->assign('employees', $emps);
    }

    /**
     * Process leaves for all employees.
     *
     * @return void
     */
    public function leavesAction()
    {
    }
}
