<?php

class Default_AnotherController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        die("HERE you are!");
        // action body
    }

    public function heyAction()
    {
        echo "HEY THERE";
    }


}
