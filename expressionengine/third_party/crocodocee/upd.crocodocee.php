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

class Crocodocee_upd {

    var $version = CROCODOCEE_ADDON_VERSION;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
    } 
    
    function install() { 
  
        $this->EE->lang->loadfile('crocodocee');  
		
		$this->EE->load->dbforge(); 
        
        //----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if ($this->EE->db->field_exists('settings', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}
        
        $settings = array();

        $data = array( 'module_name' => 'Crocodocee' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'has_publish_fields' => 'n', 'settings'=> serialize($settings) ); 
        $this->EE->db->insert('modules', $data); 
        
        //$data = array( 'class' => 'Crocodocee' , 'method' => 'check' ); 
        //$this->EE->db->insert('actions', $data); 
        
        if ($this->EE->db->field_exists('crocodoc_uuid', 'files') == FALSE)
		{
			$this->EE->dbforge->add_column('files', array('crocodoc_uuid' => array('type' => 'VARCHAR', 'constraint'=> 255, 'default' => '') ) );
		}
        
        //exp_crocodocee_queue
        $fields = array(
			'queue_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
            'site_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
            'author_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
            'entry_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
            'field_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
            'crocodoc_uuid'	      	    => array('type' => 'VARCHAR',	'constraint'=> 150,	'default' => '')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('queue_id', TRUE);
        $this->EE->dbforge->add_key('field_id');
        $this->EE->dbforge->add_key('entry_id');
        $this->EE->dbforge->add_key('author_id');
        $this->EE->dbforge->add_key('site_id');
		$this->EE->dbforge->create_table('crocodocee_queue', TRUE);
        
        
        
        return TRUE; 
        
    } 
    
    function uninstall() { 

        $this->EE->db->select('module_id'); 
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Crocodocee')); 
        
        $this->EE->db->where('module_id', $query->row('module_id')); 
        $this->EE->db->delete('module_member_groups'); 
        
        $this->EE->db->where('module_name', 'Crocodocee'); 
        $this->EE->db->delete('modules'); 
        
        $this->EE->db->where('class', 'Crocodocee'); 
        $this->EE->db->delete('actions'); 
        
        return TRUE; 
    } 
    
    
    function update($current='') 
	{ 
        if ($current < 2)
        {
            //exp_crocodocee_queue
            $fields = array(
    			'queue_id'			=> array('type' => 'INT',		'unsigned' => TRUE, 'auto_increment' => TRUE),
                'site_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
                'author_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
                'entry_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
                'field_id'		         	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
                'crocodoc_uuid'	      	    => array('type' => 'VARCHAR',	'constraint'=> 150,	'default' => '')
    		);
    
    		$this->EE->dbforge->add_field($fields);
    		$this->EE->dbforge->add_key('queue_id', TRUE);
            $this->EE->dbforge->add_key('field_id');
            $this->EE->dbforge->add_key('entry_id');
            $this->EE->dbforge->add_key('author_id');
            $this->EE->dbforge->add_key('site_id');
    		$this->EE->dbforge->create_table('crocodocee_queue', TRUE);
        }
        
        return TRUE; 
    } 
	

}
/* END */
?>