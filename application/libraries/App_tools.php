<?php  if  ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
App Tools Library

@package		Social Igniter
@subpackage		App Tools Library
@author			Brennan Novak
@link			http://social-igniter.com

This class contains all the basic install functions for core and app installs
*/
 
class App_tools
{
	protected $ci;
	protected $template_path;
	protected $install_path;
	protected $app_name;
	protected $app_url;
	protected $app_class;

	function __construct($config)
	{
		$this->ci =& get_instance();
		
		// Load Things
  		$this->ci->load->helper('file');		
		$this->ci->load->model('settings_model');
		$this->ci->load->model('sites_model');
		
		$this->template_path	= './application/modules/app-template/';
		$this->install_path		= './application/modules/'.$config['app_url'].'/';
		$this->app_name			= $config['app_name'];
		$this->app_url			= $config['app_url'];
		$this->app_class  		= $config['app_class'];
	}	
	
	function check_app_exists($app_url)
	{
    	if (file_exists(APPPATH.'modules/'.$app_url))
        {
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	function replace_tags($template, $replace_tag=NULL, $replace_value=NULL)
	{
		$template_data	= file_get_contents($template, FILE_USE_INCLUDE_PATH);
		$template_data	= str_replace('{APP_NAME}', $this->app_name, $template_data);
		$template_data	= str_replace('{APP_URL}', $this->app_url, $template_data);
		$template_data	= str_replace('{APP_CLASS}', $this->app_class, $template_data);
		$template_data	= str_replace('{SITE_NAME}', config_item('site_title'), $template_data);
		$template_data	= str_replace('{SITE_ADMIN}', config_item('site_admin_email'), $template_data);	
		$template_data	= str_replace($replace_tag, $replace_value, $template_data);
		return $template_data;
	}

	// Makes 'app-template' into a custom named App
	function create_app_template()
	{
		// Install Path
		$this->app_url		= strtolower($this->app_url);
		$this->app_class 	= strtolower($this->app_class);
		$folders			= array('assets', 'config', 'controllers', 'views');

		make_folder($this->install_path);

		foreach ($folders as $folder)
		{
			make_folder($this->install_path.$folder.'/');
		}

		// Assets
		$asset1_current	= file_get_contents($this->template_path.'assets/app-template_24.png', FILE_USE_INCLUDE_PATH);
		file_put_contents($this->install_path.'assets/'.$this->app_url.'_24.png', $asset1_current);
		$asset2_current	= file_get_contents($this->template_path.'assets/app-template_32.png', FILE_USE_INCLUDE_PATH);
		file_put_contents($this->install_path.'assets/'.$this->app_url.'_32.png', $asset2_current);		

		return TRUE;
	}
	
	function create_app_configs()
	{
		// Install Path
		$configs = array('install', 'routes', 'widgets');

		// Config
		$config_template	= $this->template_path.'config/app_template.php';
		$config_data		= $this->replace_tags($config_template);
		file_put_contents($this->install_path.'config/'.$this->app_class.'.php', $config_data);

		// Config Files
		foreach ($configs as $config)
		{
			// Install
			$config_template	= $this->template_path.'config/'.$config.'.php';
			$config_data 		= $this->replace_tags($config_template);
			file_put_contents($this->install_path.'config/'.$config.'.php', $config_data);
		}

		return TRUE;
	}

	function create_app_controllers($api_methods, $connections)
	{
		// Main Controller
		$controller_template	= $this->template_path.'controllers/app_template.php';
		$controller_data 		= $this->replace_tags($controller_template);
		file_put_contents($this->install_path.'controllers/'.$this->app_class.'.php', $controller_data);

		// API
		if ($api_methods == 'TRUE')
		{
			$api_template = $this->template_path.'controllers/api_methods.php';
		}
		else
		{
			$api_template = $this->template_path.'controllers/api.php';
		}

		$api_data = $this->replace_tags($api_template);
		file_put_contents($this->install_path.'controllers/api.php', $api_data);			

		// Connections
		if ($connections == 'oauth1')
		{
			$connections_template	= $this->template_path.'controllers/connections_oauth1.php';
			$connections_data 		= $this->replace_tags($connections_template);
			file_put_contents($this->install_path.'controllers/connections.php', $connections_data);			
		}

		// Home & Settings
		foreach (array('home', 'settings') as $controller)
		{
			$controller_template	= $this->template_path.'controllers/'.$controller.'.php';
			$controller_data 		= $this->replace_tags($controller_template);
			file_put_contents($this->install_path.'controllers/'.$controller.'.php', $controller_data);				
		}

		return TRUE;
	}

	function create_app_views()
	{
		// Install Path
		$view_folders = array($this->app_class, 'home', 'partials', 'settings');
		$views	  	  = array('home/custom', 'partials/head_dashboard', 'partials/head_site', 'partials/navigation_home', 'partials/sidebar_tools', 'settings/index', 'settings/widgets');

		// Views
		foreach ($view_folders as $folder)
		{
			make_folder($this->install_path.'views/'.$folder);
		}

		// App Index
		$config_template	= $this->template_path.'views/app_template/index.php';
		$config_data 		= $this->replace_tags($config_template);
		file_put_contents($this->install_path.'views/'.$this->app_class.'/index.php', $config_data);
		
		// Partials & Settings
		foreach ($views as $view)
		{
			$config_template	= $this->template_path.'views/'.$view.'.php';
			$config_data 		= $this->replace_tags($config_template);
			file_put_contents($this->install_path.'views/'.$view.'.php', $config_data);
		}

		return TRUE;
	}
	
	function create_helper($helper)
	{
		make_folder($this->install_path.'helpers/');

		// Helper
		$config_template	= $this->template_path.'helpers/app_template_helper.php';
		$config_data 		= $this->replace_tags($config_template);
		file_put_contents($this->install_path.'helpers/'.$this->app_class.'_helper.php', $config_data);

		return TRUE;
	}

	function create_libraries($library, $oauth)
	{
		make_folder($this->install_path.'libraries/');

		// Library
		if ($library != 'FALSE')
		{
			$library_template	= $this->template_path.'libraries/app_template_libary.php';
			$library_data 		= $this->replace_tags($library_template, '{APP_CLASS_TITLE}', ucwords($this->app_class));
			file_put_contents($this->install_path.'libraries/'.ucwords($this->app_class).'_library.php', $library_data);
		}

		// OAuth Provider
		if ($oauth != 'FALSE')
		{
			$library_template	= $this->template_path.'libraries/'.$oauth.'_provider.php';
			$library_data 		= $this->replace_tags($library_template, '{APP_CLASS_TITLE}', ucwords($this->app_class));
			file_put_contents($this->install_path.'libraries/oauth_provider.php', $library_data);
		}

		return TRUE;
	}
	
	function create_model($model)
	{
		if ($model == 'TRUE')
		{
			make_folder($this->install_path.'models/');
	
			// Make Model
			$model_template	= file_get_contents($this->template_path.'models/data_model.php', FILE_USE_INCLUDE_PATH);
			file_put_contents($this->install_path.'models/data_model.php', $model_template);
		}

		return TRUE;
	}
	
	function create_widgets($widgets)
	{
		if ($widgets == 'TRUE')
		{
			// Widgets Config
			$config_template	= $this->template_path.'config/widgets.php';
			$config_data		= $this->replace_tags($config_template);
			file_put_contents($this->install_path.'config/widgets.php', $config_data);	

			// Widget Template
			make_folder($this->install_path.'views/widgets/');
			$widget_template	= $this->template_path.'views/widgets/recent_data.php';
			$widget_data		= $this->replace_tags($widget_template);
			file_put_contents($this->install_path.'views/widgets/recent_data.php', $widget_data);
		}

		return TRUE;
	}

}