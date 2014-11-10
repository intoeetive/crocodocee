<?php

/*
=====================================================
 CrocodocEE
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ext.crocodocee.php
-----------------------------------------------------
 Purpose: Integrate Crocodoc with ExpressionEngine
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'crocodocee/config.php';

class Crocodocee_mcp {

    var $version = CROCODOCEE_ADDON_VERSION;
    
    var $settings = array();
    
    var $perpage = 25;
    
    var $site_id = 1;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
        $this->site_id = $this->EE->config->item('site_id');
    } 
    
    
    function index()
    {
        $this->EE->functions->redirect(BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=crocodocee');
    }    

  

}
/* END */
?>