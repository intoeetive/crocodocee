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


class Crocodocee {

    var $return_data	= ''; 	
    
    var $settings = array();
    
    var $perpage = 25;
    
    var $site_id		= 1;

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 
    	$this->site_id = $this->EE->config->item('site_id');
    	$query = $this->EE->db->select('settings')->from('extensions')->where('class', __CLASS__.'_ext')->limit(1)->get();
        $this->settings = unserialize($query->row('settings')); 
    }
    /* END */
    
 /* 
 * {custom_field:thumb} - thumbnail of the doc. The image will be cached
 * {custom_field:text} - extracted document's text (if your account has text extraction enabled)
 * {custom_field:download}
 */
 
 	function uuid($file_path=false)
 	{
	 	$uuid = '';
	 	
		if ($file_path===false)
 		{
 			$file_path = $this->EE->TMPL->fetch_param('file');
 			$embedded = false;
 		}
 		else
 		{
 			$embedded = true;
 		}
 		
 		if ($file_path==false || $file_path=='')
 		{
 			if ($embedded == true)
 			{
 				return false;
 			}
 			else
 			{
 				return $this->EE->TMPL->no_results();
 			}
 		}
 		
 		$this->EE->db->select('crocodoc_uuid')->from('files');
		if (is_numeric($file_path))
		{
			$this->EE->db->where('file_id', $file_path);
		}
		else
		{
			$this->EE->db->where('rel_path', $file_path);
		}
	 	$this->EE->db->limit(1);
	 	$query = $this->EE->db->get();

		if ($query->num_rows()==0)
		{
			if (!file_exists($file_path))
			{
				if ($embedded == true)
				{
					return false;
				}
				else
				{
					return $this->EE->TMPL->no_results();
				}
			}
			
			$this->EE->load->library('filemanager');
			$this->EE->load->helper('file');
			$upload_dirs = $this->EE->filemanager->fetch_upload_dirs();
			$upload_dir = false;
			foreach ($upload_dirs as $dir_id=>$dir_path)
			{
				if (strpos($file_path, $dir_path['server_path'])!==false)
				{
					$upload_dir = $dir_path['id'];
					break;
				}
			}
			
			if ($upload_dir == false)
			{
				if ($embedded == true)
				{
					return false;
				}
				else
				{
					return $this->EE->TMPL->no_results();
				}
			}
			
			$this_file = get_file_info($file_path); 

			$file_data = array(
				'upload_location_id'	=> $upload_dir,
				'site_id'				=> $this->EE->config->item('site_id'),
				'rel_path'				=> $file_path, // this will vary at some point
				'mime_type'				=> get_mime_by_extension($this_file['name']),
				'file_name'				=> $this_file['name'],
				'file_size'				=> '',
				'uploaded_by_member_id'	=> $this->EE->session->userdata('member_id'),
				'modified_by_member_id' => $this->EE->session->userdata('member_id'),
				'file_hw_original'		=> '',
				'upload_date'			=> $this->EE->localize->now,
				'modified_date'			=> $this->EE->localize->now
			);
			
			if (!empty($this->settings['upload_destinations']) && !in_array($file_data['upload_location_id'], $this->settings['upload_destinations']))
	    	{
	    		return $this->EE->TMPL->no_results();
	    	}
	    	if (!empty($this->settings['mime_types']))
	    	{
	    		if (strpos($this->settings['mime_types'], $file_data['mime_type'])===false)
	    		{
	    			return $this->EE->TMPL->no_results();
	    		}
	    	}
			
			$this->EE->db->insert('files', $file_data);
			$file_id = $this->EE->db->insert_id();
		}
		else
		{
			$uuid = $query->row('crocodoc_uuid');
		}

		//no UUID? try to get it, if needed
		if ($uuid=='')
		{
			if (!isset($file_id))
			{
				$query = $this->EE->db->select()
					->from('files')
					 ->where('rel_path', $file_path)
					 ->limit(1)
					 ->get();
			 	$file_id = $query->row('file_id');
			 	$file_data = $query->row_array();

			 	
			 	if (!empty($this->settings['upload_destinations']) && !in_array($file_data['upload_location_id'], $this->settings['upload_destinations']))
		    	{
		    		return $this->EE->TMPL->no_results();
		    	}
		    	if (!empty($this->settings['mime_types']))
		    	{
		    		if (strpos($this->settings['mime_types'], $file_data['mime_type'])===false)
		    		{
		    			return $this->EE->TMPL->no_results();
		    		}
		    	}
			 	
		 	}
		 	require_once PATH_THIRD.'crocodocee/ext.crocodocee.php';
		 	$CROXOEXT = new Crocodocee_ext();
		 	$uuid = $CROXOEXT->send_file($file_id, $file_data, $this->settings);
		}
		
		if ($embedded == true)
		{
			return $uuid;
		}
		else
		{
			$this->EE->TMPL->tagdata = $uuid;
		}
 		
 		return $this->EE->TMPL->tagdata;
 		
 	}
 	
 	function status()
 	{
 		if ($this->EE->TMPL->fetch_param('file')!='')
        {
            $uuid = $this->uuid($this->EE->TMPL->fetch_param('file'));
        }
        else
        {
            $uuid = '';
            $this->EE->db->select('crocodoc_uuid')
                ->from('crocodocee_queue')
                ->where('entry_id', $this->EE->TMPL->fetch_param('entry_id'));
            if ($this->EE->TMPL->fetch_param('field_id')!='')
            {
                $this->EE->db->where('field_id', $this->EE->TMPL->fetch_param('field_id'));
            }
            elseif ($this->EE->TMPL->fetch_param('field')!='')
            {
                $this->EE->db->join('channel_fields', 'channel_fields.field_id=crocodocee_queue.field_id', 'left');
                $this->EE->db->where('channel_fields.field_name', $this->EE->TMPL->fetch_param('field'));
            }
            $this->EE->db->limit(1);
            $q = $this->EE->db->get();
            if ($q->num_rows()>0)
            {
                $uuid = $q->row('crocodoc_uuid');
            }
        }
 		if ($uuid==false || $uuid=='')
 		{
 			return $this->EE->TMPL->no_results();
 		}
 		
 		$this->EE->load->library('curl');
		
 		$this->EE->curl->option('HTTPAUTH', CURLAUTH_BASIC);
 		$this->EE->curl->option('SSLVERSION', 3);
		$this->EE->curl->option('SSL_VERIFYPEER', FALSE);
		$this->EE->curl->option('SSL_VERIFYHOST', FALSE);
		
		$result = $this->EE->curl->simple_get("https://crocodoc.com/api/v2/document/status", array("token"=>$this->settings['api_key'], "uuids"=>$uuid));
 
 		if ( ! function_exists('json_decode'))
		{
			$this->load->library('Services_json');
		}
		$obj = json_decode($result);

		$data = array(array(
			'status'	=> $obj[0]->status,
			'error'		=> (isset($obj[0]->error))?$obj[0]->error:''
		));
		
		$tagdata = $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $data);

 		return $tagdata;
 	}
 	
 	
 	
 	
 	
 	
 	function url()
 	{
 		if ($this->EE->TMPL->fetch_param('file')!='')
        {
            $uuid = $this->uuid($this->EE->TMPL->fetch_param('file'));
        }
        else
        {
            $uuid = '';
            $this->EE->db->select('crocodoc_uuid')
                ->from('crocodocee_queue')
                ->where('entry_id', $this->EE->TMPL->fetch_param('entry_id'));
            if ($this->EE->TMPL->fetch_param('field_id')!='')
            {
                $this->EE->db->where('field_id', $this->EE->TMPL->fetch_param('field_id'));
            }
            elseif ($this->EE->TMPL->fetch_param('field')!='')
            {
                $this->EE->db->join('channel_fields', 'channel_fields.field_id=crocodocee_queue.field_id', 'left');
                $this->EE->db->where('channel_fields.field_name', $this->EE->TMPL->fetch_param('field'));
            }
            $this->EE->db->limit(1);
            $q = $this->EE->db->get();
            if ($q->num_rows()>0)
            {
                $uuid = $q->row('crocodoc_uuid');
            }
        }
 		if ($uuid==false || $uuid=='')
 		{
 			return $this->EE->TMPL->no_results();
 		}
 		
 		$this->EE->load->library('curl');
		
 		$this->EE->curl->option('HTTPAUTH', CURLAUTH_BASIC);
 		$this->EE->curl->option('SSLVERSION', 3);
		$this->EE->curl->option('SSL_VERIFYPEER', FALSE);
		$this->EE->curl->option('SSL_VERIFYHOST', FALSE);

		$params = "token=".$this->settings['api_key']."&uuid=$uuid";
		if (in_array($this->EE->TMPL->fetch_param('editable'), array('true', 'yes', 'y')))
		{
			$params .= "&editable=true";
		}
		if ($this->EE->TMPL->fetch_param('user')!==false)
		{
			$params .= "&user=".$this->EE->TMPL->fetch_param('user');
		}
		if ($this->EE->TMPL->fetch_param('filter')!==false)
		{
			$params .= "&filter=".$this->EE->TMPL->fetch_param('filter');
		}
		if (in_array($this->EE->TMPL->fetch_param('admin'), array('true', 'yes', 'y')))
		{
			$params .= "&admin=true";
		}
		if (in_array($this->EE->TMPL->fetch_param('downloadable'), array('true', 'yes', 'y')))
		{
			$params .= "&downloadable=true";
		}
		if (in_array($this->EE->TMPL->fetch_param('copyprotected'), array('true', 'yes', 'y')))
		{
			$params .= "&copyprotected=true";
		}
		if (in_array($this->EE->TMPL->fetch_param('demo'), array('true', 'yes', 'y')))
		{
			$params .= "&demo=true";
		}
		if ($this->EE->TMPL->fetch_param('sidebar')!==false)
		{
			$params .= "&sidebar=".$this->EE->TMPL->fetch_param('filter'); //none / collapse / visible / auto
		}
		
		
		$result = $this->EE->curl->simple_post("https://crocodoc.com/api/v2/session/create", $params);
 		
 		if ( ! function_exists('json_decode'))
		{
			$this->load->library('Services_json');
		}
		$obj = json_decode($result);
		
		$this->EE->TMPL->tagdata = "https://crocodoc.com/view/".$obj->session;
 		
 		return $this->EE->TMPL->tagdata;
 	}
 	
 	
 	
 	
 	
 	function thumb()
 	{
 		if ($this->EE->TMPL->fetch_param('file')!='')
        {
            $uuid = $this->uuid($this->EE->TMPL->fetch_param('file'));
        }
        else
        {
            $uuid = '';
            $this->EE->db->select('crocodoc_uuid')
                ->from('crocodocee_queue')
                ->where('entry_id', $this->EE->TMPL->fetch_param('entry_id'));
            if ($this->EE->TMPL->fetch_param('field_id')!='')
            {
                $this->EE->db->where('field_id', $this->EE->TMPL->fetch_param('field_id'));
            }
            elseif ($this->EE->TMPL->fetch_param('field')!='')
            {
                $this->EE->db->join('channel_fields', 'channel_fields.field_id=crocodocee_queue.field_id', 'left');
                $this->EE->db->where('channel_fields.field_name', $this->EE->TMPL->fetch_param('field'));
            }
            $this->EE->db->limit(1);
            $q = $this->EE->db->get();
            if ($q->num_rows()>0)
            {
                $uuid = $q->row('crocodoc_uuid');
            }
        }
 		if ($uuid==false || $uuid=='')
 		{
 			return $this->EE->TMPL->no_results();
 		}
 		
 		$thumb_path = $this->settings['cache_dir'].$uuid.'.png';
 		if (file_exists($thumb_path) && filesize($thumb_path)!=0)
 		{
 			return $this->settings['cache_url'].$uuid.'.png';
 		}
 		
 		$this->EE->load->library('curl');
		
 		$this->EE->curl->option('HTTPAUTH', CURLAUTH_BASIC);
 		$this->EE->curl->option('SSLVERSION', 3);
		$this->EE->curl->option('SSL_VERIFYPEER', FALSE);
		$this->EE->curl->option('SSL_VERIFYHOST', FALSE);
		
		$params = "token=".$this->settings['api_key']."&uuid=$uuid";
		if ($this->EE->TMPL->fetch_param('size')!==false)
		{
			$params .= "&size=".$this->EE->TMPL->fetch_param('size');
		}
		
		$result = $this->EE->curl->simple_get("https://crocodoc.com/api/v2/download/thumbnail?".$params);
 		
 		$this->EE->load->helper('file');
 		write_file($thumb_path, $result);
 		
 		return $this->settings['cache_url'].$uuid.'.png';
 	}

}
/* END */
?>