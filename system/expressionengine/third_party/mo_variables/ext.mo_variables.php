<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mo_variables_ext
{
	public $settings = array();
	public $name = 'Mo\' Variables';
	public $version = '1.0.6';
	public $description = 'Adds many useful global variables and conditionals to use in your templates.';
	public $settings_exist = 'y';
	public $docs_url = 'https://github.com/rsanchez/mo_variables';
	
	protected $session;
	
	public function __construct($settings = array())
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	}
	
	public function activate_extension()
	{
		$defaults = $this->settings();
		
		foreach ($defaults as &$default)
		{
			$default = '1';
		}
		
		$this->EE->db->insert(
			'extensions',
			array(
				'class' => __CLASS__,
				'method' => 'sessions_end',
				'hook' => 'sessions_end',
				'settings' => serialize($defaults),
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
		$this->EE->db->delete('extensions', array('class' => __CLASS__));
	}
	
	public function settings()
	{
		$settings = array(
			'ajax' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'secure' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'get' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'get_post' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'post' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'cookie' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'page_tracker' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'reverse_segments' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'segments_from' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'paginated' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'archive' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'theme_folder_url' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			'current_url' => array('r', array('1' => 'yes', '0' => 'no'), '0'),
			//'member_variables' => array('r', array('1' => 'yes', '0' => 'no'), '0'),//this can cause problems with other addons, scrapped for now
		);
		
		if (version_compare('2.1.5', APP_VER, '<='))
		{
			unset($settings['theme_folder_url']);
		}
		
		return $settings;
	}
	
	public function sessions_end(&$session)
	{
		$this->session =& $session;
		
		$this->run();
	}
	
	public function run()
	{
		if ( ! $this->settings)
		{
			return;
		}
		
		if (version_compare('2.1.5', APP_VER, '<='))
		{
			unset($this->settings['theme_folder_url']);
		}
		
		$keys = array_keys(array_filter($this->settings));
		
		foreach ($keys as $method)
		{
			if (method_exists($this, $method))
			{
				$this->{$method}();
			}
		}
	}
	
	protected function set_global_var($key, $value = '', $xss_clean = FALSE, $embed = FALSE, $separator = ':', $prefix = '')
	{
		if (is_array($key))
		{
			foreach ($key as $_key => $_value)
			{
				$this->set_global_var($_key, $_value, $xss_clean, $embed, $separator, $value);//we use the second param, $value, as the prefix in the case of an array
			}
		}
		else if ( ! is_array($value) && ! is_object($value))
		{
			$key = ($prefix) ? $prefix.$separator.$key : $key;
			
			//this way of handling conditionals works best in the EE template parser
			if (is_bool($value))
			{
				$value = ($value) ? '1' : 0;
			}
			else
			{
				$value = ($xss_clean) ? $this->EE->security->xss_clean($value) : $value;
			}
			
			$this->EE->config->_global_vars[$key] = $value;
			
			if ($embed)
			{
				$this->EE->config->_global_vars['embed:'.$key] = $value;
			}
		}
	}
	
	protected function get()
	{
		$this->set_global_var($_GET, 'get', TRUE, TRUE);
	}
	
	protected function get_post()
	{
		$this->set_global_var(array_merge($_GET, $_POST), 'get_post', TRUE, TRUE);
	}
	
	protected function post()
	{
		$this->set_global_var($_POST, 'post', TRUE, TRUE);
	}
	
	protected function cookie()
	{
		$this->set_global_var($_COOKIE, 'cookie', TRUE, TRUE);
	}
	
	protected function paginated()
	{
		$this->set_global_var('paginated', count($this->EE->uri->segment_array()) > 0 && preg_match('/^P(\d+)$/', end($this->EE->uri->segment_array()), $match));
		
		$this->set_global_var('page_offset', (isset($match[1])) ? $match[1] : 0);
	}
	
	protected function theme_folder_url()
	{
		$this->set_global_var('theme_folder_url', $this->EE->config->item('theme_folder_url'));
	}
	
	protected function current_url()
	{
		$this->EE->load->helper('url');
		
		$this->set_global_var('current_url', current_url());
		
		$this->set_global_var('uri_string', $this->EE->uri->uri_string());
	}
	
	protected function ajax()
	{
		$this->set_global_var('ajax', $this->EE->input->is_ajax_request());
	}
	
	protected function secure()
	{
		$this->set_global_var('secure', isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off');
		
		$this->set_global_var('secure_site_url', str_replace('http://', 'https://', $this->EE->config->item('site_url')));
	}
	
	protected function archive()
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
	
	protected function page_tracker()
	{
		$variables = array(
			//'current_page' => 0,
			'last_page_visited' => 1,
			'one_page_ago' => 1,
			'two_pages_ago' => 2,
			'three_pages_ago' => 3,
			'four_pages_ago' => 4,
			'five_pages_ago' => 5,
		);
		
		//Functions::fetch_site_index won't work without this
		$this->EE->session =& $this->session;
		
		foreach ($variables as $variable => $tracker)
		{
			if (isset($this->EE->session->tracker[$tracker]))
			{
				if ($this->EE->session->tracker[$tracker] === 'index')
				{
					$this->set_global_var($variable, $this->EE->functions->fetch_site_index(TRUE));
				}
				else
				{
					$this->set_global_var($variable, $this->EE->functions->create_url($this->EE->session->tracker[$tracker]));
				}
			}
			else
			{
				$this->set_global_var($variable);
			}
		}
	}
	
	protected function reverse_segments()
	{
		$reverse_segments = array_reverse($this->EE->uri->segment_array());
		
		for ($i = 1; $i <= 12; $i++)
		{
			$this->set_global_var('rev_segment_'.$i, (isset($reverse_segments[$i-1])) ? $reverse_segments[$i-1] : '');
		}
	}
	
	protected function segments_from()
	{
		for ($i = 1; $i <= 12; $i++)
		{
			$this->set_global_var('segments_from_'.$i, implode('/', array_slice($this->EE->uri->segment_array(), $i-1, count($this->EE->uri->segment_array()), TRUE)));
		}
	}
	
	protected function member_variables()
	{
		$variables = array(
			'member_id',
			'group_id',
			'group_description',
			'group_title',
			'username',
			'screen_name',
			'email',
			'ip_address',
			'location',
			'total_entries',
			'total_comments',
			'private_messages',
			'total_forum_posts',
			'total_forum_topics',
			'total_forum_replies',
		);
		
		foreach ($variables as $variable)
		{
			if (isset($this->session->userdata[$variable]))
			{
				$this->set_global_var($variable, $this->session->userdata[$variable]);
				
				$this->set_global_var('logged_in_'.$variable, $this->session->userdata[$variable]);
			}
		}
	}
}

/* End of file ext.mo_variables.php */
/* Location: ./system/expressionengine/third_party/mo_variables/ext.mo_variables.php */