<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mo_variables_ext
{
	public $settings = array();
	public $name = 'Mo\' Variables';
	public $version = '1.0.0';
	public $description = 'Adds more early-parsed global variables to your EE installation.';
	public $settings_exist = 'y';
	public $docs_url = 'http://github.com/rsanchez/mo_variables';
	
	public function __construct($settings = array())
	{
		$this->EE = get_instance();
		
		$this->settings = $settings;
	}
	
	public function activate_extension()
	{
		$this->EE->db->insert(
			'extensions',
			array(
				'class' => __CLASS__,
				'method' => 'sessions_end',
				'hook' => 'sessions_end',
				'settings' => '',
				'priority' => 10,
				'version' => $this->version,
				'enabled' => 'y'
			)
		);
	}
	
	public function update_extension($current = '')
	{
		if ( ! $current || $current === $this->version)
		{
			return FALSE;
		}
		
		$this->EE->db->update(
			'extensions',
			array('version' => $this->version),
			array('class' => __CLASS__)
		);
	}
	
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	
	public function settings()
	{
		$settings = array(
			'ajax' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'get' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'get_post' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'post' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'cookie' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			//'page_tracker' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'reverse_segments' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'segments_from' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'paginated' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'archive' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
		);
		
		return $settings;
	}
	
	public function sessions_end()
	{
		if ( ! $this->settings)
		{
			return;
		}
		
		foreach (array_keys(array_filter($this->settings)) as $method)
		{
			$this->{$method}();
		}
	}
	
	private function set_global_var($data, $prefix = '', $xss_clean = FALSE, $embed = FALSE, $separator = ':')
	{
		if (is_array($data))
		{
			$prefix = ($prefix) ? $prefix.$separator : '';
			
			foreach ($data as $key => $value)
			{
				if ( ! is_array($value) && ! is_object($value))
				{
					$value = ($xss_clean) ? $this->EE->security->xss_clean($value) : $value;
					
					$this->EE->config->_global_vars[$prefix.$key] = $value;
					
					if ($embed)
					{
						$this->EE->config->_global_vars['embed:'.$prefix.$key] = $value;
					}
				}
			}
		}
		else
		{
			$key = $data;
			$value = $prefix;
			
			$value = ($xss_clean) ? $this->EE->security->xss_clean($value) : $value;
			
			$this->EE->config->_global_vars[$data] = $value;
			
			if ($embed)
			{
				$this->EE->config->_global_vars['embed:'.$prefix.$key] = $value;
			}
		}
	}
	
	private function create_url($path)
	{
		return ($path === 'index') ? $this->EE->functions->fetch_site_index(1) : $this->EE->functions->create_url($path);
	}
	
	private function get()
	{
		$this->set_global_var($_GET, 'get', TRUE, TRUE);
	}
	
	private function get_post()
	{
		$this->set_global_var(array_merge($_GET, $_POST), 'get_post', TRUE, TRUE);
	}
	
	private function post()
	{
		$this->set_global_var($_POST, 'post', TRUE, TRUE);
	}
	
	private function cookie()
	{
		$this->set_global_var($_COOKIE, 'cookie', TRUE, TRUE);
	}
	
	private function paginated()
	{
		$this->set_global_var('paginated', (count($this->EE->uri->segment_array()) && preg_match('/P\d+/', end($this->EE->uri->segment_array()))) ? '1' : 0);
	}
	
	private function ajax()
	{
		$this->set_global_var('ajax', ($this->EE->input->is_ajax_request()) ? '1' : 0);
	}
	
	private function archive()
	{
		$archive = array(
			'archive' => 0,
			'daily_archive' => 0,
			'monthly_archive' => 0,
			'yearly_archive' => 0
		);
		
		if (preg_match('#/\d{4}/\d{2}/\d{2}/?$#', $this->EE->uri->uri_string()))
		{
			$archive['archive'] = '1';
			$archive['daily_archive'] = '1';
		}
		else if (preg_match('#/\d{4}/\d{2}/?$#', $this->EE->uri->uri_string()))
		{
			$archive['archive'] = '1';
			$archive['monthly_archive'] = '1';
		}
		else if (preg_match('#/\d{4}/?$#', $this->EE->uri->uri_string()))
		{
			$archive['archive'] = '1';
			$archive['yearly_archive'] = '1';
		}
		
		$this->set_global_var($archive, FALSE, FALSE);
	}
	
	//does not work
	private function page_tracker()
	{
		$this->set_global_var('current_page', (isset($this->EE->session->tracker[0])) ? $this->create_url($this->EE->session->tracker[0]) : '');
		
		$this->set_global_var('last_page', (isset($this->EE->session->tracker[1])) ? $this->create_url($this->EE->session->tracker[1]) : '');
		
		$this->set_global_var('one_page_ago', (isset($this->EE->session->tracker[1])) ? $this->create_url($this->EE->session->tracker[1]) : '');
		
		$this->set_global_var('two_pages_ago', (isset($this->EE->session->tracker[2])) ? $this->create_url($this->EE->session->tracker[2]) : '');
		
		$this->set_global_var('three_pages_ago', (isset($this->EE->session->tracker[3])) ? $this->create_url($this->EE->session->tracker[3]) : '');
		
		$this->set_global_var('four_pages_ago', (isset($this->EE->session->tracker[4])) ? $this->create_url($this->EE->session->tracker[4]) : '');
		
		$this->set_global_var('five_pages_ago', (isset($this->EE->session->tracker[5])) ? $this->create_url($this->EE->session->tracker[5]) : '');
	}
	
	private function reverse_segments()
	{
		$reverse_segments = array_reverse($this->EE->uri->segment_array());
		
		for ($i = 1; $i <= 12; $i++)
		{
			$this->set_global_var('rev_segment_'.$i, (isset($reverse_segments[$i-1])) ? $reverse_segments[$i-1] : '');
		}
	}
	
	private function segments_from()
	{
		for ($i = 1; $i <= 12; $i++)
		{
			$this->set_global_var('segments_from_'.$i, implode('/', array_slice($this->EE->uri->segment_array(), $i-1, count($this->EE->uri->segment_array()), TRUE)));
		}
	}
}

/* End of file ext.mo_variables.php */
/* Location: ./system/expressionengine/third_party/mo_variables/ext.mo_variables.php */