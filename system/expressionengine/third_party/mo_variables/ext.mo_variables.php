<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mo_variables_ext
{
	public $settings = array();
	public $name = 'Mo\' Variables';
	public $version = '1.1.5';
	public $description = 'Adds many useful global variables and conditionals to use in your templates.';
	public $settings_exist = 'y';
	public $docs_url = 'https://git.io/mo';
	
	protected $defaults = array(
		'ajax',
		'secure',
		'get',
		'defaults_get',
		'get_post',
		'defaults_get_post',
		'post',
		'defaults_post',
		'cookie',
		'defaults_cookie',
		'flashdata',
		'defaults_flashdata',
		'page_tracker',
		'reverse_segments',
		'segments_from',
		'paginated',
		'archive',
		'categorized',
		'reserved_category_word',
		'current_url',
		'member_variables',
		'member_group_conditionals',
	);

	//only these methods will be run more than once,
	//eg. in embedded templates
	protected $run_multiple = array(
		'member_group_conditionals',
	);
	
	protected $template_data = '';
	
	public function __construct($settings = array())
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	}
	
	public function activate_extension()
	{
		//get all the default settings as enabled
		if (function_exists('array_fill_keys'))
		{
			$settings = array_fill_keys($this->defaults, '1');
		}
		else
		{
			$settings = array();
			
			foreach ($this->defaults as $key)
			{
				$settings[$key] = '1';
			}
		}
		
		$settings = serialize($settings);
		
		$this->EE->db->insert(
			'extensions',
			array(
				'class' => __CLASS__,
				'method' => 'run',
				'hook' => 'template_fetch_template',
				'settings' => $settings,
				'priority' => 10,
				'version' => $this->version,
				'enabled' => 'y',
			)
		);
		
		$this->EE->db->insert(
			'extensions',
			array(
				'class' => __CLASS__,
				'method' => 'cleanup',
				'hook' => 'template_post_parse',
				'settings' => $settings,
				'priority' => 10,
				'version' => $this->version,
				'enabled' => 'y',
			)
		);
	}
	
	public function update_extension($current = '')
	{
		if ( ! $current || $current === $this->version)
		{
			return FALSE;
		}
		
		if (version_compare($current, '1.0.7', '<='))
		{
			$this->EE->db->update(
				'extensions',
				array(
					'method' => 'run',
					'hook' => 'template_fetch_template',
				),
				array(
					'class' => __CLASS__
				)
			);
			
			$query = $this->EE->db->select('settings')
						->where('class', __CLASS__)
						->get('extensions');
			
			if ($query->num_rows() !== 0)
			{
				$this->EE->db->insert(
					'extensions',
					array(
						'class' => __CLASS__,
						'method' => 'cleanup',
						'hook' => 'template_post_parse',
						'settings' => $query->row('settings'),
						'priority' => 10,
						'version' => $this->version,
						'enabled' => 'y',
					)
				);
				
				$query->free_result();
			}
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
		$setting = array('r', array('1' => 'yes', '0' => 'no'), '0');
		$defaults_setting = array('t', array('rows' => 5), '');
		
		$settings = array();
			
		foreach ($this->defaults as $key)
		{
			// defaults settings get a textarea
			$settings[$key] = strncmp($key, 'defaults_', 9) === 0 ? $defaults_setting : $setting;
		}

		// hide the defaults fields for get/get_post/post/cookie/flashdata if not being used
		if ($this->EE->input->get('C') === 'addons_extensions' && $this->EE->input->get('M') === 'extension_settings')
		{
			$this->EE->load->library('javascript');

			$this->EE->javascript->output('
				$.each(["get", "get_post", "post", "cookie", "flashdata"], function(i, v) {
					var $input = $("input[name="+v+"]"),
							setting = $input.filter(":checked").val(),
							$defaultsRow = $("#defaults_"+v).parents("tr");

					if (setting === "0") {
						$defaultsRow.hide();
					}

					$input.change(function() {
						$defaultsRow.toggle($(this).val() === "1");
					});
				});
			');
		}
		
		return $settings;
	}
	
	/**
	 * Add all the global variables specified in $this->settings
	 * 
	 * @return void
	 */
	public function run($row)
	{
		if ( ! $this->settings)
		{
			return;
		}

		$this->uri_string = isset($this->EE->config->_global_vars['freebie_original_uri'])
			? $this->EE->config->_global_vars['freebie_original_uri']
			: $this->EE->uri->uri_string();
		
		$this->template_data = $row['template_data'];
		
		//remove settings that are zero and then loop through
		//the remaining settings
		$keys = array_keys(array_filter($this->settings));
		
		foreach ($keys as $method)
		{
			if (method_exists($this, $method))
			{
				if ($this->EE->session->cache(__CLASS__, $method) && ! in_array($method, $this->run_multiple))
				{
					//don't run this method on subsequent runs
					continue;
				}
				
				$this->EE->session->set_cache(__CLASS__, $method, TRUE);
				
				$this->{$method}();
			}
		}
	}
	
	/**
	 * Clean up unparsed variables
	 * 
	 * @param string $final_template the template content after parsing
	 * @param bool $sub            whether or not the template is an embed
	 * 
	 * @return string the final template content
	 */
	public function cleanup($final_template, $sub)
	{
		//don't parse this stuff in embeds
		if ($sub)
		{
			return $final_template;
		}
		
		if ($this->EE->extensions->last_call !== FALSE)
		{
			$final_template = $this->EE->extensions->last_call;
		}
		
		if (preg_match_all('/{(get|post|get_post|cookie|flashdata):(.*?)}/', $final_template, $matches))
		{
			foreach ($matches[0] as $destroy)
			{
				$final_template = str_replace($destroy, '', $final_template);
			}
		}
		
		return $final_template;
	}
	
	/**
	 * Set Global Variable
	 * 
	 * @param string|array $key       the key/index/tag of the variable, or an array of key/value pairs of multiple variables
	 * @param string $value     the value of the variable, or the tag prefix if the first arg is an array
	 * @param bool $xss_clean whether or not to clean (used for GET/POST/COOKIE arrays)
	 * @param bool $embed     whether or not to add the embed: prefix
	 * @param string $separator change the default colon : separator between prefix and key
	 * @param string $prefix    a prefix to add to the key/index/tag
	 * 
	 * @return void
	 */
	protected function set_global_var($key, $value = '', $xss_clean = FALSE, $embed = FALSE, $separator = ':', $prefix = '')
	{
		if (is_array($key))
		{
			// set default values for this array if defaults exist in the settings;
			if ( ! empty($this->settings['defaults_'.$value]))
			{
				$defaults = preg_split('/[\r\n]+/', $this->settings['defaults_'.$value]);

				foreach ($defaults as $default)
				{
					$default = preg_split('/\s*:\s*/', $default);

					if ( ! isset($key[$default[0]]))
					{
						$key[$default[0]] = isset($default[1]) ? $default[1] : '';
					}
				}
			}

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
	
	/**
	 * Set variables from the $_GET array
	 * 
	 * @return void
	 */
	protected function get()
	{
		$this->set_global_var($_GET, 'get', TRUE, TRUE);
	}
	
	/**
	 * Set variables from the $_GET and $_POST arrays
	 * 
	 * @return void
	 */
	protected function get_post()
	{
		$this->set_global_var(array_merge($_GET, $_POST), 'get_post', TRUE, TRUE);
	}
	
	
	/**
	 * Set variables from the $_POST array
	 * 
	 * @return void
	 */
	protected function post()
	{
		$this->set_global_var($_POST, 'post', TRUE, TRUE);
	}
	
	
	/**
	 * Set variables from the $_COOKIE array
	 * 
	 * @return void
	 */
	protected function cookie()
	{
		$this->set_global_var($_COOKIE, 'cookie', TRUE, TRUE);
	}
	
	
	/**
	 * Set variables from the $this->EE->session->flashdata array
	 * 
	 * @return void
	 */
	protected function flashdata()
	{
		$this->set_global_var($this->EE->session->flashdata, 'flashdata', TRUE, TRUE);
	}
	
	
	/**
	 * Set the {if paginated}, {if not_paginated} and {page_offset variables}
	 * 
	 * @return void
	 */
	protected function paginated()
	{
		$this->EE->load->helper('url');
		
		//fix for structure/freebie and other addons that manipulate Uri::uri_string()
		$uri_string = $this->EE->input->server('PATH_INFO') !== FALSE ? $this->EE->input->server('PATH_INFO') : '/'.$this->EE->uri->uri_string();
		
		if (preg_match('#/P(\d+)/?$#', $uri_string, $match))
		{
			$this->set_global_var('paginated', TRUE);
	
			$this->set_global_var('not_paginated', FALSE);
			
			$this->set_global_var('page_offset', $match[1]);
            
			$this->set_global_var('pagination_base_uri', substr($uri_string, 0, -strlen($match[0])));
			
			$this->set_global_var('pagination_base_url', substr($this->EE->functions->create_url($uri_string), 0, -strlen($match[0])));
		}
		else
		{
			$this->set_global_var('paginated', FALSE);
	
			$this->set_global_var('not_paginated', TRUE);
			
			$this->set_global_var('page_offset', 0);
            
			$this->set_global_var('pagination_base_uri', $uri_string);
			
			$this->set_global_var('pagination_base_url', $this->EE->functions->create_url($uri_string));
		}
	}
	
	/**
	 * Set the {if categorized} conditional
	 */
	protected function categorized()
	{
		$this->set_global_var('categorized', in_array($this->EE->config->item('reserved_category_word'), $this->EE->uri->segment_array()));
	}

	/**
	 * Set the {reserved_category_word} variable
	 */
	protected function reserved_category_word()
	{
		$this->set_global_var('reserved_category_word', $this->EE->config->item('reserved_category_word'));
	}
	
	/**
	 * Set the {current_url}, {uri_string} and {query_string} variables
	 * 
	 * @return void
	 */
	protected function current_url()
	{
		$this->EE->load->helper('url');
		
		$this->set_global_var('server_name',  $_SERVER['SERVER_NAME']);
		
		$this->set_global_var('current_url', current_url());
		
		$this->set_global_var('current_url_encoded', urlencode(current_url()));
		
		$this->set_global_var('uri_string', $this->EE->uri->uri_string());
		
		$this->set_global_var('uri_string_encoded', urlencode($this->EE->uri->uri_string()));

		if (isset($_SERVER['QUERY_STRING']))
		{
			$this->set_global_var('query_string', $_SERVER['QUERY_STRING']);
		}
		else if (isset($_SERVER['REQUEST_URI']) && FALSE !== ($pos = strpos($_SERVER['REQUEST_URI'], '?')))
		{
			$this->set_global_var('query_string', substr($_SERVER['REQUEST_URI'], $pos + 1));
		}
		else if ($_GET)
		{
			$this->set_global_var('query_string', http_build_query($_GET));
		}
		else
		{
			$this->set_global_var('query_string', '');
		}
	}
	
	/**
	 * Set the {if ajax} and {if not_ajax} variables
	 * 
	 * @return void
	 */
	protected function ajax()
	{
		$this->set_global_var('ajax', $this->EE->input->is_ajax_request());
		
		$this->set_global_var('not_ajax', ! $this->EE->input->is_ajax_request());
	}
	
	/**
	 * Set the {if secure}, {if not_secure}, {insecure_site_url}, {secure_site_url} variables
	 * 
	 * @return void
	 */
	protected function secure()
	{
		$secure = !empty($_SERVER['HTTPS']);
		
		$this->set_global_var('secure', $secure);

		$this->set_global_var('not_secure', ! $secure);

		$this->set_global_var('secure_site_url', preg_replace("/^http[s]?:/", "https:", $this->EE->config->item('site_url')));

		$this->set_global_var('insecure_site_url', preg_replace("/^http[s]?:/", "http:", $this->EE->config->item('site_url')));
	}
	
	/**
	 * Set the {if archive}, {if daily_archive}, {if monthly_archive}, {if yearly_archive},
	 * {if not_archive}, {if not_daily_archive}, {if not_monthy_archive} and
	 * {if not_yearly_archive} variables
	 * 
	 * @return void
	 */
	protected function archive()
	{
		$archive = array(
			'archive' => FALSE,
			'daily_archive' => FALSE,
			'monthly_archive' => FALSE,
			'yearly_archive' => FALSE,
			'not_archive' => TRUE,
			'not_daily_archive' => TRUE,
			'not_monthly_archive' => TRUE,
			'not_yearly_archive' => TRUE,
		);
		
		if (preg_match('#/\d{4}/\d{2}/\d{2}(/P\d+)?/?$#', $this->uri_string))
		{
			$archive['archive'] = TRUE;
			$archive['daily_archive'] = TRUE;
			$archive['not_archive'] = FALSE;
			$archive['not_daily_archive'] = FALSE;
		}
		else if (preg_match('#/\d{4}/\d{2}(/P\d+)?/?$#', $this->uri_string))
		{
			$archive['archive'] = TRUE;
			$archive['monthly_archive'] = TRUE;
			$archive['not_archive'] = FALSE;
			$archive['not_monthly_archive'] = FALSE;
		}
		else if (preg_match('#/\d{4}(/P\d+)?/?$#', $this->uri_string))
		{
			$archive['archive'] = TRUE;
			$archive['yearly_archive'] = TRUE;
			$archive['not_archive'] = FALSE;
			$archive['not_yearly_archive'] = FALSE;
		}
		
		$this->set_global_var($archive, FALSE, FALSE);
	}
	
	/**
	 * Set the {last_page_visited}, {one_pages_ago}, {two_pages_ago},
	 * {three_pages_ago}, {four_pages_ago} and {five_pages_ago} variables
	 * 
	 * @return void
	 */
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
	
	/**
	 * Set the {rev_segment_X} variables
	 * 
	 * @return void
	 */
	protected function reverse_segments()
	{
		$reverse_segments = array_reverse($this->EE->uri->segment_array());
		
		for ($i = 1; $i <= 12; $i++)
		{
			$this->set_global_var('rev_segment_'.$i, (isset($reverse_segments[$i-1])) ? $reverse_segments[$i-1] : '');
		}
	}
	
	/**
	 * Set the {segments_from_X} variables
	 * 
	 * @return void
	 */
	protected function segments_from()
	{
		for ($i = 1; $i <= 12; $i++)
		{
			$this->set_global_var('segments_from_'.$i, implode('/', array_slice($this->EE->uri->segment_array(), $i-1, count($this->EE->uri->segment_array()), TRUE)));
		}
	}
	
	/**
	 * Set the member variables as early-parsed
	 * 
	 * @return void
	 */
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
			'join_date',
			'last_visit',
			'last_activity',
			'last_entry_date',
			'last_comment_date',
			'last_forum_post_date',
			'timezone',
			'time_format',
		);
		
		foreach ($variables as $key)
		{
			$value = isset($this->EE->session->userdata[$key]) ? $this->EE->session->userdata[$key] : '';
			
			$this->set_global_var('logged_in_'.$key, $value);
		}
	}
	
	/**
	 * Set the {if in_group(1|2|3)} and {if not_in_group(1|2|3)} early-parsed conditionals
	 * 
	 * @return void
	 */
	protected function member_group_conditionals()
	{
		if (preg_match_all('/(not_)?in_group\(([\042\047]?)(.*?)\\2\)/', $this->template_data, $matches))
		{
			foreach ($matches[3] as $i => $groups)
			{
				$full_match = $matches[0][$i];

				//so you can use pipe-delimited 1|2|3 global variables
				if (strpos($groups, '{') !== FALSE)
				{
					foreach ($this->EE->config->_global_vars as $key => $value)
					{
						if (strpos($groups, '{'.$key.'}') !== FALSE)
						{
							$groups = str_replace('{'.$key.'}', $this->EE->config->_global_vars[$key], $groups);
							$full_match = str_replace('{'.$key.'}', $this->EE->config->_global_vars[$key], $full_match);
						}
					}
				}
				
				$in_group = in_array($this->EE->session->userdata('group_id'), explode('|', $groups));
				
				$not = $matches[1][$i];
				
				// sigh
				$key = str_replace('|', '\|', $full_match);
				
				if ($not)
				{
					$this->set_global_var($key, ! $in_group);
				}
				else
				{
					$this->set_global_var($key, $in_group);
				}
			}
		}
	}
	
	//for legacy purposes, so we don't break updates
	public function sessions_end()
	{
		//do nothing
	}
}

/* End of file ext.mo_variables.php */
/* Location: ./system/expressionengine/third_party/mo_variables/ext.mo_variables.php */
