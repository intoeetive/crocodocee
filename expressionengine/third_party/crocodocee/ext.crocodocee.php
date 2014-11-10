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

class Crocodocee_ext {

	var $name	     	= CROCODOCEE_ADDON_NAME;
	var $version 		= CROCODOCEE_ADDON_VERSION;
	var $description	= 'Integrate Crocodoc with ExpressionEngine';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://www.intoeetive.com/docs/crocodocee.html';
    
    var $settings 		= array();
    var $site_id		= 1;
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
		$this->site_id = $this->EE->config->item('site_id');
	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		array(
    			'hook'		=> 'file_after_save',
    			'method'	=> 'send_file',
    			'priority'	=> 10
    		),
    		array(
    			'hook'		=> 'entry_submission_absolute_end',
    			'method'	=> 'entry_sibmitted',
    			'priority'	=> 10
    		)/*,
            
    		array(
    			'hook'		=> 'assets_file_meta_add_row',
    			'method'	=> 'assets_file_added',
    			'priority'	=> 10
    		),*/
    		
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
        
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current < 2)
        {
            $hooks = array(
        		array(
        			'hook'		=> 'entry_submission_absolute_end',
        			'method'	=> 'entry_sibmitted',
        			'priority'	=> 10
        		)
        		
        	);
        	
            foreach ($hooks AS $hook)
        	{
        		$data = array(
            		'class'		=> __CLASS__,
            		'method'	=> $hook['method'],
            		'hook'		=> $hook['hook'],
            		'settings'	=> '',
            		'priority'	=> $hook['priority'],
            		'version'	=> $this->version,
            		'enabled'	=> 'y'
            	);
                $this->EE->db->insert('extensions', $data);
        	}	
        }
        
        if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
    }
    
    
    
    function settings_form($current)
    {
    	$this->EE->load->helper('form');
    	$this->EE->load->library('table');
        $this->EE->load->library('filemanager');
        
        $channels = array();
		$this->EE->db->select('channel_id, channel_title');
        $this->EE->db->from('channels');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $q = $this->EE->db->get();
        foreach ($q->result_array() as $row)
        {
            $channels[$row['channel_id']] = $row['channel_title'];
        }
        
        if (empty($current))
        {
        	$current = array(
				'api_key'	=> '',
				//'mime_types'	=> 'application/pdf, application/x-pdf, application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint',
				//'mime_types'	=> '',
				//'upload_destinations'	=> array(),
                'channels'  => array(),
				'cache_dir'	=> $_SERVER['DOCUMENT_ROOT'].'images/crocodoc/',
				'cache_url'	=> $this->EE->config->slash_item('site_url').'images/crocodoc/'
			);
        }
        
        $dirs = $this->EE->filemanager->directories();

        $vars = array();
        
        $vars['settings']['api_key'] = form_input('api_key', $current['api_key']);
        
        /*$vars['settings']['upload_destinations'] = '';
        foreach ($dirs as $dir_id=>$dir_name)
        {
            $vars['settings']['upload_destinations'] .= form_checkbox(array('name' => 'upload_destinations[]', 'id' => 'dir_'.$dir_id, 'value' => $dir_id, 'checked' => in_array($dir_id, $current['upload_destinations']))).NBS.NBS.form_label($dir_name, 'dir_'.$dir_id).BR;
        }

        $vars['settings']['mime_types'] = form_input('mime_types', $current['mime_types']);*/
        
        $vars['settings']['channels'] = form_multiselect('channels[]', $channels, $current['channels']);
        
        $vars['settings']['cache_dir'] = form_input('cache_dir', $current['cache_dir']);	
		
		$vars['settings']['cache_url'] = form_input('cache_url', $current['cache_url']);		        

    	return $this->EE->load->view('settings', $vars, TRUE);			
    }
    
    
    
    
    function save_settings()
    {
    	if (empty($_POST))
    	{
    		show_error($this->EE->lang->line('unauthorized_access'));
    	}

        unset($_POST['submit']);
        
        $this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update('extensions', array('settings' => serialize($_POST)));
    	
    	if ( ! @is_dir($this->EE->input->post('cache_dir')))
		{
			if (@mkdir($this->EE->input->post('cache_dir'), DIR_WRITE_MODE))
			{
				@chmod($this->EE->input->post('cache_dir'), DIR_WRITE_MODE);
			}
		}
    	
    	$this->EE->session->set_flashdata(
    		'message_success',
    	 	$this->EE->lang->line('preferences_updated')
    	);
    }
    
    
    function assets_file_added($file)
    {
    	//var_dump($file);
    	//exit();
    }
    
    
    
    
    function entry_sibmitted($entry_id, $meta, $data, $orig_var)
    {
		//loop though all fields
        //if the field type is Assets or Channel Images, and the file is PDF - send it for conversion

        if (!in_array($data['channel_id'], $this->settings['channels'])) return false;
        
        foreach ($data as $field_name=>$val)
        {
            if (strpos($field_name, 'field_id_')!==false)
            {
                $field_id = substr($field_name, 9);
                $src = $this->get_scr_from_field($entry_id, $field_id);
                //echo " ".$src;
                if ($src!==false)
                {
                    $ch = curl_init("https://crocodoc.com/api/v2/document/upload");
            		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            		curl_setopt($ch, CURLOPT_POST, true);
            		curl_setopt($ch, CURLOPT_POSTFIELDS, array("token"=>$this->settings['api_key'], "url"=>$src));
            		$result = curl_exec($ch);
            		curl_close($ch);
            
            		if ( ! function_exists('json_decode'))
            		{
            			$this->load->library('Services_json');
            		}
            		$obj = json_decode($result);
                    
                    if (isset($obj->uuid) && $obj->uuid!='')
                    {
                        $insert = array(
                            'site_id'		            => $this->EE->config->item('site_id'),
                            'author_id'		         	=> $this->EE->session->userdata('member_id'),
                            'entry_id'		         	=> $entry_id,
                            'field_id'		         	=> $field_id,
                            'crocodoc_uuid'	            => $obj->uuid
                        );
                        $this->EE->db->insert('crocodocee_queue', $insert);
                    }
                }
            }
        }
		
    }
    
    
    function send_file($file_id, $data, $settings=false)
    {
    	if ($settings===false)
    	{
    		$settings = $this->settings;
    	}
		if (!empty($settings['upload_destinations']) && !in_array($data['upload_location_id'], $settings['upload_destinations']))
    	{
    		return false;
    	}
    	if (!empty($settings['mime_types']))
    	{
    		if (strpos($settings['mime_types'], $data['mime_type'])===false)
    		{
    			return false;
    		}
    	}
		/*
		$this->EE->load->model('file_upload_preferences_model');
		$upload_prefs = $this->EE->file_upload_preferences_model->get_file_upload_preferences($this->EE->session->userdata('group_id'), $data['upload_location_id'], TRUE);
    	
    	$public_url = reduce_double_slashes($upload_prefs['url'].'/'.$data['file_name']);
   		*/
		$ch = curl_init("https://crocodoc.com/api/v2/document/upload");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("token"=>$settings['api_key'], "file"=>"@".$data['rel_path']));
		$result = curl_exec($ch);
		curl_close($ch);

		if ( ! function_exists('json_decode'))
		{
			$this->load->library('Services_json');
		}
		$obj = json_decode($result);
		
		$this->EE->db->where('file_id', $file_id);
		$this->EE->db->update('files', array('crocodoc_uuid'=>$obj->uuid));
		
		return $obj->uuid;

    }
    
    
    
    function get_scr_from_field($entry_id, $field_id)
    {
        $src = '';
        
        //get field type
        $q = $this->EE->db->select('field_type')
                ->from('channel_fields')
                ->where('field_id', $field_id)
                ->get();
        if ($q->num_rows()==0) return false;
        
        $field_type = $q->row('field_type');
        //echo $field_type;
        //depending on type, get field value
        switch ($field_type)
        {
            case 'assets':
                $this->EE->load->add_package_path(PATH_THIRD.'assets/');
                $this->EE->load->library('assets_lib');
                $this->EE->db->select('exp_assets_selections.file_id, source_type, source_id, file_name')
                        ->from('exp_assets_selections')
                        ->join('exp_assets_files', 'exp_assets_selections.file_id=exp_assets_files.file_id', 'left')
                        ->where('exp_assets_selections.entry_id', $entry_id)
                        ->where('exp_assets_selections.field_id', $field_id);
                //echo $this->EE->db->_compile_select();
                $q = $this->EE->db->get();
                
                $ext = substr($q->row('file_name'), strrpos($q->row('file_name'), '.')+1);
                if (!in_array($ext, array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'ppsx', 'pdf')))
                {
                    return false;
                }
                
                $src = $this->EE->assets_lib->get_file_url($q->row('file_id'));
                /*
                if ($q->row('source_type')=='s3')
                {
                    $source = $this->EE->assets_lib->instantiate_source_type((object) array('source_type' => 's3', 'source_id' => $q->row('source_id')));
                    $bucket_settings = $source->settings();
                    
                    if ($bucket_settings->access_key_id!='' && $bucket_settings->secret_access_key!='')
                    {
                        $src_split = explode("/", $src);
                        //http://s3.amazon.com/buccket_name/sdfdfdgdg                        
                        $src = $src_split[0]."//".urlencode($bucket_settings->access_key_id).":".urlencode($bucket_settings->secret_access_key)."@".$src_split[3].".".$src_split[2];
                        for ($i=4; $i<count($src_split); $i++)
                        {
                            $src .= '/'.$src_split[$i];
                        }
                        //echo $src;
                    }
                }*/
                $this->EE->load->remove_package_path(PATH_THIRD.'assets/');
                break;
                
            case 'channel_files':
                $this->EE->db->select('field_id, filename, extension')
                        ->from('channel_files')
                        ->where('entry_id', $entry_id)
                        ->where('field_id', $field_id);
                //echo $this->EE->db->_compile_select();
                $q = $this->EE->db->get();
                
                if (!in_array($q->row('extension'), array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'ppsx', 'pdf')))
                {
                    return false;
                }
                
                $this->EE->load->add_package_path(PATH_THIRD.'channel_files/');
                $this->EE->load->library('channel_files_helper');
                $cf_settings = $this->EE->channel_files_helper->grab_field_settings($field_id);
        		$cf_settings = $cf_settings['channel_files'];
        
        		$location_type = $cf_settings['upload_location'];
        		$location_class = 'CF_Location_'.$location_type;
        		$location_settings = $cf_settings['locations'][$location_type];
        
        		// Entry_id FOLDER?
        		if (isset($cf_settings['entry_id_folder']) && $cf_settings['entry_id_folder'] == 'no')
        		{
        			$dir = FALSE;
        		}
                else
                {
                    $dir = $entry_id;
                }
        
        		// Load Main Class
        		if (class_exists('Cfile_Location') == FALSE) require PATH_THIRD.'channel_files/locations/cfile_location.php';
        
        		// Try to load Location Class
        		if (class_exists($location_class) == FALSE)
        		{
        			$location_file = PATH_THIRD.'channel_files/locations/'.$location_type.'/'.$location_type.'.php';
        
        			require $location_file;
        		}
        
        		// Init!
        		$LOC = new $location_class($location_settings);

        		$src = $LOC->parse_file_url($dir, $q->row('filename'));
                
                $this->EE->load->remove_package_path(PATH_THIRD.'channel_files/');
                        
            
                break;
            
            case 'text':
            case 'textarea':
            default:
                return false;
                break;
        }
        
        return $src;
    }
    
    
  

}
// END CLASS
