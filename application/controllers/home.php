<?php


/**
 * Home
 * 
 * A Dashboard_controller for posting status updates
 * 
 * @package Social Igniter\Controllers
 * @see Dashboard_Controller
 */
class Home extends Dashboard_Controller 
{
    function __construct() 
    {
        parent::__construct();
    }
 
 	/* Home Feed - All modules when URL is 'home/blog' this method shows all activity for specified module */
 	function index()
 	{
 		if ($this->session->userdata('user_level_id') > config_item('home_view_permission')) redirect(login_redirect());

 		$timeline		= NULL;
		$timeline_view	= NULL;
		
 	   	// Load
		$this->data['home_greeting']	= random_element($this->lang->line('home_greeting'));
	 	$this->data['social_post'] 		= $this->social_igniter->get_social_post($this->session->userdata('user_id'), 'social_post_horizontal');
		$this->data['groups']	 		= $this->social_tools->make_group_dropdown($this->session->userdata('user_id'), $this->session->userdata('user_level_id'), '+ Add Group'); 
 		$this->data['group_id']			= '';
 		
 		// Pick Type of Feed
		if ($this->uri->total_segments() == 1)
		{
	 	    $this->data['page_title'] 		= 'Home';
			$timeline 						= $this->social_igniter->get_timeline(NULL, 10);
 	    }
 	    elseif ($this->uri->segment(2) == 'friends')
 	    {
	 	    $this->data['page_title'] 		= 'Friends';

			if ($friends = $this->social_tools->get_relationships_owner($this->session->userdata('user_id'), 'user', 'follow'))
			{
				$timeline = $this->social_igniter->get_timeline_friends($friends, 10);
			}
 	    }
 	    elseif ($this->uri->segment(2) == 'likes')
 	    {
	 	    $this->data['page_title'] 		= 'Likes';

			$likes							= $this->social_tools->get_ratings_likes_user($this->session->userdata('user_id'));
			$timeline 						= $this->social_igniter->get_timeline_likes($likes, 10); 
 	    }
 	    elseif ($this->uri->segment(2) == 'group')
 	    {
 	    	$group 							= $this->social_tools->get_category($this->uri->segment(3));

	 	    $this->data['page_title'] 		= $group->category;
			$this->data['group_id']			= $this->uri->segment(3);

			$timeline 						= $this->social_igniter->get_timeline_group($group->category_id, 10); 
 	    }
 	    // Fix For MODULE Checking
 	    else
 	    {
	 	    $this->data['page_title'] 		= display_nice_file_name($this->uri->segment(2));
 			$this->data['sub_title']		= 'Recent';

			$timeline 						= $this->social_igniter->get_timeline($this->uri->segment(2), 10); 
 	    }

		// Build Feed				 			
		if (!empty($timeline))
		{
			foreach ($timeline as $activity)
			{			
				// Item
				$this->data['item_id']				= $activity->activity_id;
				$this->data['item_type']			= item_type_class($activity->type);
				
				// Contributor
				$this->data['item_user_id']			= $activity->user_id;
				$this->data['item_avatar']			= $this->social_igniter->profile_image($activity->user_id, $activity->image, $activity->gravatar, 'medium', 'dashboard_theme');
				$this->data['item_contributor']		= $activity->name;
				
				// User Profile
				$this->data['item_profile']			= base_url().'profile/'.$activity->username;
				
				// Activity
				$this->data['item_content']			= $this->social_igniter->render_item($activity);
				$this->data['item_content_id']		= $activity->content_id;
				$this->data['item_date']			= format_datetime(config_item('home_date_style'), $activity->created_at);			
				$this->data['item_source']			= '';
		
				if ($activity->site_id != config_item('site_id'))
				{
					$this->data['item_source']		= ' via <a href="'.prep_url(property_exists($activity,'canonical')&&$activity->canonical?$activity->canonical:$activity->url).'" target="_blank">'.$activity->title.'</a>';
				}


		 		// Actions
			 	$this->data['item_comment']			= base_url().'comment/item/'.$activity->activity_id;
			 	$this->data['item_comment_avatar']	= $this->data['logged_image'];
			 	
			 	$this->data['item_can_modify']		= $this->social_auth->has_access_to_modify('activity', $activity, $this->session->userdata('user_id'), $this->session->userdata('user_level_id'));
				$this->data['item_edit']			= base_url().'home/'.$activity->module.'/manage/'.$activity->content_id;
				$this->data['item_delete']			= base_url().'api/activity/destroy/id/'.$activity->activity_id;

				// View
				$timeline_view .= $this->load->view(config_item('dashboard_theme').'/partials/item_timeline.php', $this->data, true);
	 		}
	 	}
	 	else
	 	{
	 		$timeline_view = '<li><p>Nothing to show from anyone!</p></li>';
 		}	

		// Final Output
		$this->data['timeline_view'] 	= $timeline_view;
		$this->render();
 	}
	
 	/* Manage - All modules base manage page when URL is 'home/blog/manage' this method shows all 'content' from specified module */
	function manage()
	{
		if ($this->session->userdata('user_level_id') > 3) redirect(base_url().'home');
	
		$content_module		= $this->social_igniter->get_content_view('module', $this->uri->segment(2), 'all', 150);
		$manage_view 		= NULL;
		$filter_categories	= array('none' => 'By Category');
		$filter_users		= array('none' => 'By User');
		$filter_details		= array('none' => 'By Details');


		// Title Stuff
		$this->data['page_title']	= ucwords($this->uri->segment(2));
		$this->data['sub_title']	= 'Manage';
		
		if (!empty($content_module))
		{		 
			foreach($content_module as $content)
			{
				$this->data['content']				= $content;
				$this->data['item_id'] 				= $content->content_id;
				$this->data['item_type']			= $content->type;
				$this->data['item_viewed']			= item_viewed('item_manage', $content->viewed);
	
				$this->data['title']				= item_title($content->title, $content->type);
				$this->data['title_link']			= base_url().$content->module.'/view/'.$content->content_id;
				$this->data['comments_count']		= manage_comments_count($content->comments_count);
				$this->data['publish_date']			= manage_published_date($content->created_at, $content->updated_at);
				
				// MAKE FOR CHECK RELVANT TO USER_LEVEL
				$this->data['item_status']			= display_content_status($content->status);
				$this->data['item_approval']		= $content->approval;
	
				// Alerts
				$this->data['item_alerts']			= item_alerts_content($content);			
				
				// Actions
				$this->data['item_approve']			= base_url().'api/content/approve/id/'.$content->content_id;
				$this->data['item_edit']			= base_url().'home/'.$content->module.'/manage/'.$content->content_id;
				$this->data['item_delete']			= base_url().'api/content/destroy/id/'.$content->content_id;
				
				// View
				$manage_view .= $this->load->view(config_item('dashboard_theme').'/partials/item_manage.php', $this->data, true);
			
			
				// Build Filter Values
				$filter_categories[$content->category_id]	= $content->category_id;
				$filter_users[$content->user_id] 			= $content->name;
				$filter_details[$content->details]			= $content->details;
			}	
		}
	 	else
	 	{
	 		$manage_view = '<li>Nothing to manage from anyone!</li>';
 		}

		// Final Output
		$this->data['timeline_view'] 		= $manage_view;
		$this->data['module']				= ucwords($this->uri->segment(2));
		$this->data['all_categories']	 	= $this->social_tools->get_categories_view('module', $this->uri->segment(2));
		$this->data['filter_categories'] 	= $filter_categories;
		$this->data['filter_users']			= $filter_users;
		$this->data['filter_details']		= $filter_details;

		$this->render('dashboard_wide');
	}	
 	
	/* Error */
    function error()
	{
		$this->data['page_title'] = 'Oops, Page Not Found';	
		$this->render();	
	}
	
	/* Partials */
	function item_timeline()
	{
		$this->data['item_id']				= '{ITEM_ID}';
		$this->data['item_type']			= '{ACTIVITY_TYPE}';
		
		// Contributor
		$this->data['item_user_id']			= '{ITEM_USER_ID}';
		$this->data['item_avatar']			= '{ITEM_AVATAR}';
		$this->data['item_contributor']		= '{ITEM_CONTRIBUTOR}';
		$this->data['item_profile']			= base_url().'profiles/{ITEM_PROFILE}';
		
		// Activity
		$this->data['item_content']			= '{ITEM_CONTENT}';
		$this->data['item_content_id']		= '{ITEM_CONTENT_ID}';
		$this->data['item_date']			= '{ITEM_DATE}';

 		// Actions
		$this->data['item_comment']			= base_url().'comment/item/{ACTIVITY_ID}';
		$this->data['item_comment_avatar']	= '{ITEM_COMMENT_AVATAR}';

	 	$this->data['item_can_modify']		= '{ITEM_CAN EDIT}';
		$this->data['item_edit']			= base_url().'home/{ACTIVITY_MODULE}/manage/{ITEM_CONTENT_ID}';
		$this->data['item_delete']			= base_url().'status/delete/{ACTIVITY_ID}';			
	
		$this->load->view(config_item('dashboard_theme').'/partials/item_timeline', $this->data);
	}
	
	function item_manage()
	{
		$this->data['item_id'] 				= '{ITEM_ID}';
		$this->data['comments_count']		= '{COMMENTS_COUNT}';
		$this->data['item_type']			= '{ACTIVITY_TYPE}';
		$this->data['title']				= '{ITEM_TITLE}';
		$this->data['title_link']			= base_url().'{MODULE}/view/{ITEM_ID}';
		$this->data['publish_date']			= '{PUBLISHED_DATE}';
		$this->data['status']				= '{ITEM_STATUS}';

		$this->data['item_approval']		= '{ITEM_APPROVAL}';
	
		// Actions
		$this->data['item_approve']			= base_url().'api/content/approve/id/{ITEM_ID}';
		$this->data['item_edit']			= base_url().'home/{MODULE}/manage/{ITEM_ID}';
		$this->data['item_delete']			= base_url().'home/{MODULE}/manage/{ITEM_ID}';	
	
		$this->load->view(config_item('dashboard_theme').'/partials/item_manage', $this->data);
	}
	
	function category_manager()
	{
		// Is Edit or Create
		if ($this->uri->segment(3) != 'create')
		{
			$category = $this->social_tools->get_category($this->uri->segment(3));

			$this->data['category']			= $category->category;
			$this->data['category_url']		= $category->category_url;
			$this->data['description']		= $category->description;
			$this->data['access']			= $category->access;
		}
		else
		{
			$this->data['category']			= '';
			$this->data['category_url']		= '';
			$this->data['description']		= '';
			$this->data['access']			= '';
		}
		
		// Do Parents or Not
		if ($this->uri->segment(4) == 'parent')
		{
			$this->data['category_parents'] = $this->social_tools->make_categories_dropdown(array('categories.type' => $this->uri->segment(5)), $this->session->userdata('user_id'), $this->session->userdata('user_level_id'), '');
		}
		else
		{
			$this->data['category_parents'] = '';		
		}

		$this->load->view(config_item('dashboard_theme').'/partials/category_manager', $this->data);
	}
	
}