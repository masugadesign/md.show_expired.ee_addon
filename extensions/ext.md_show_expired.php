<?php
/* ===========================================================================
ext.md_show_expired.php ---------------------------
Show expired entries on the edit page, and allow for filtering searches 
for expired entries.
            
INFO ---------------------------
Developed by: Ryan Masuga, masugadesign.com
Created:   Oct 15 2008
Last Mod:  Oct 15 2008

Related Thread: http://expressionengine.com/forums/viewthread/94004/
http://expressionengine.com/docs/development/extensions.html
=============================================================================== */
if ( ! defined('EXT')) { exit('Invalid file request'); }


if ( ! defined('MD_SE_version')){
	define("MD_SE_version",			"1.0.0");
	define("MD_SE_docs_url",		"http://www.masugadesign.com/the-lab/scripts/md-show-expired/");
	define("MD_SE_addon_id",		"MD Show Expired");
	define("MD_SE_extension_class",	"Md_show_expired");
	define("MD_SE_cache_name",		"mdesign_cache");
}

class Md_show_expired
{
	var $settings		= array();
	var $name           = 'MD Show Expired';
	// var $type           = 'md_notes'; // only used for custom field extensions
	var $version        = MD_SE_version;
	var $description    = 'Show expired entries on the edit page, and allow for filtering searches for expired entries.';
	var $settings_exist = 'y';
	var $docs_url       = MD_SE_docs_url;

// --------------------------------
//  PHP 4 Constructor
// --------------------------------
	function Md_show_expired($settings='')
	{
		$this->__construct($settings);
	}

// --------------------------------
//  PHP 5 Constructor
// --------------------------------
	function __construct($settings='')
	{
		global $IN, $SESS;
		if(isset($SESS->cache['mdesign']) === FALSE){ $SESS->cache['mdesign'] = array();}
		$this->settings = $this->_get_settings();
		$this->debug = $IN->GBL('debug');
	}


	function _get_settings($force_refresh = FALSE, $return_all = FALSE)
	{
		global $SESS, $DB, $REGX, $LANG, $PREFS;

		// assume there are no settings
		$settings = FALSE;

		// Get the settings for the extension
		if(isset($SESS->cache['mdesign'][MD_SE_addon_id]['settings']) === FALSE || $force_refresh === TRUE)
		{
			// check the db for extension settings
			$query = $DB->query("SELECT settings FROM exp_extensions WHERE enabled = 'y' AND class = '" . MD_SE_extension_class . "' LIMIT 1");

			// if there is a row and the row has settings
			if ($query->num_rows > 0 && $query->row['settings'] != '')
			{
				// save them to the cache
				$SESS->cache['mdesign'][MD_SE_addon_id]['settings'] = $REGX->array_stripslashes(unserialize($query->row['settings']));
			}
		}

		// check to see if the session has been set
		// if it has return the session
		// if not return false
		if(empty($SESS->cache['mdesign'][MD_SE_addon_id]['settings']) !== TRUE)
		{
			$settings = ($return_all === TRUE) ?  $SESS->cache['mdesign'][MD_SE_addon_id]['settings'] : $SESS->cache['mdesign'][MD_SE_addon_id]['settings'][$PREFS->ini('site_id')];
		}
		return $settings;
	}


	function settings_form($current)
	{
		global $DB, $DSP, $LANG, $IN, $PREFS, $SESS;

		// create a local variable for the site settings
		$settings = $this->_get_settings();

		$DSP->crumbline = TRUE;

		$DSP->title  = $LANG->line('extension_settings');
		$DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
		$DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));

		$DSP->crumb .= $DSP->crumb_item($LANG->line('extension_title') . " {$this->version}");

		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));

		$DSP->body = '';
		$DSP->body .= $DSP->heading($LANG->line('extension_title') . " <small>{$this->version}</small>");
		$DSP->body .= $DSP->form_open(
								array(
									'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings'
								),
								array('name' => strtolower(MD_SE_extension_class))
		);
	
	// EXTENSION ACCESS
	$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableHeading', '', '2')
		. $LANG->line("access_rights")
		. $DSP->td_c()
		. $DSP->tr_c();

	$DSP->body .= $DSP->tr()
		. $DSP->td('tableCellOne', '30%')
		. $DSP->qdiv('defaultBold', $LANG->line('enable_extension_for_this_site'))
		. $DSP->td_c();

	$DSP->body .= $DSP->td('tableCellOne')
		. "<select name='enable'>"
					. $DSP->input_select_option('y', "Yes", (($settings['enable'] == 'y') ? 'y' : '' ))
					. $DSP->input_select_option('n', "No", (($settings['enable'] == 'n') ? 'y' : '' ))
					. $DSP->input_select_footer()
		. $DSP->td_c()
		. $DSP->tr_c()
		. $DSP->table_c();

		// UPDATES
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'border' => '0', 'style' => 'margin-top:18px; width:100%'));

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableHeading', '', '2')
			. $LANG->line("check_for_updates_title")
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('', '', '2')
			. "<div class='box' style='border-width:0 0 1px 0; margin:0; padding:10px 5px'><p>" . $LANG->line('check_for_updates_info') . "</p></div>"
			. $DSP->td_c()
			. $DSP->tr_c();

		$DSP->body .= $DSP->tr()
			. $DSP->td('tableCellOne', '40%')
			. $DSP->qdiv('defaultBold', $LANG->line("check_for_updates_label"))
			. $DSP->td_c();

		$DSP->body .= $DSP->td('tableCellOne')
			. "<select name='check_for_updates'>"
				. $DSP->input_select_option('y', "Yes", (($settings['check_for_updates'] == 'y') ? 'y' : '' ))
				. $DSP->input_select_option('n', "No", (($settings['check_for_updates'] == 'n') ? 'y' : '' ))
				. $DSP->input_select_footer()
			. $DSP->td_c()
			. $DSP->tr_c();
			
			$DSP->body .= $DSP->table_c();

	

		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit("Submit"))
					. $DSP->form_c();
	}



	function save_settings()
	{
		global $DB, $IN, $LANG, $OUT, $PREFS, $REGX, $SESS;

		$LANG->fetch_language_file("md_show_expired");

		// create a default settings array
		$default_settings = array(
		//	"allowed_member_groups" => array(),
		//	"weblogs" => array()
		);

		// merge the defaults with our $_POST vars
		$site_settings = array_merge($default_settings, $_POST);

		// unset the name
		unset($site_settings['name']);

		// load the settings from cache or DB
		// force a refresh and return the full site settings
		$settings = $this->_get_settings(TRUE, TRUE);

		// add the posted values to the settings
		$settings[$PREFS->ini('site_id')] = $site_settings;

		// update the settings
		$query = $DB->query($sql = "UPDATE exp_extensions SET settings = '" . addslashes(serialize($settings)) . "' WHERE class = '" . MD_SE_extension_class . "'");

		$this->settings = $settings[$PREFS->ini('site_id')];

		if($this->settings['enable'] == 'y')
		{
			if (session_id() == "") session_start(); // if no active session we start a new one
		}
	}



	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;
		
	  $default_settings = array(
        'enable' 			=> 'y',
        'check_for_updates' => 'y'
        );


		// get the list of installed sites
		$query = $DB->query("SELECT * FROM exp_sites");

		// if there are sites - we know there will be at least one but do it anyway
		if ($query->num_rows > 0)
		{
			// for each of the sites
			foreach($query->result as $row)
			{
				// build a multi dimensional array for the settings
				$settings[$row['site_id']] = $default_settings;
			}
		}	
		
		$hooks = array(
		  'edit_entries_search_form'            => 'edit_entries_search_form',
		  'edit_entries_search_where'           => 'edit_entries_search_where',
		  
		  'edit_entries_additional_tableheader' => 'edit_entries_additional_tableheader',
		  'edit_entries_additional_celldata'    => 'edit_entries_additional_celldata',
		  
		  'lg_addon_update_register_source'     => 'lg_addon_update_register_source',
			'lg_addon_update_register_addon'      => 'lg_addon_update_register_addon'
		);
		
		foreach ($hooks as $hook => $method)
		{
			$sql[] = $DB->insert_string( 'exp_extensions', 
				array('extension_id' 	=> '',
					'class'			=> get_class($this),
					'method'		=> $method,
					'hook'			=> $hook,
					'settings'	=> addslashes(serialize($settings)),
					'priority'	=> 10,
					'version'		=> $this->version,
					'enabled'		=> "y"
				)
			);
		}

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}	
	
	// --------------------------------
	//  Disable Extension
	// -------------------------------- 
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}
	// END
// ============================================================================


	// --------------------------------
	//  Add Table Heading
	// --------------------------------
	
	function edit_entries_additional_tableheader()
	{
		global $DSP, $LANG, $EXT;
		$LANG->fetch_language_file('md_show_expired');
		$extra = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';
		
		if( $this->settings['enable'] == 'n')
		{
		  return $extra;
		}
		
		return $extra.$DSP->table_qcell('tableHeadingAlt', $LANG->line('table_heading'));
	}
	// END



	// ---------------------------------
	//  Add thumbnail for Entries
	// ---------------------------------
	
	function edit_entries_additional_celldata($row)
	{	
		global $DSP, $LANG, $EXT, $DB, $LOC, $TMPL, $exp_i;
		
		
		if (empty($exp_i)){ $exp_i = 0; }

		$extra = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';
		
		if( $this->settings['enable'] == 'n')
		{
			return $extra;
		}
		
		
		
    $ret = "";
    $timestamp = $LOC->now;
    $query = $DB->query("SELECT expiration_date AS expd FROM exp_weblog_titles t WHERE entry_id='".$row['entry_id']."'");
		
		foreach($query->result as $item)
	  {
      if (  ($item['expd'] != 0)  && ($item['expd'] < $timestamp)  )
	    {
        $ret .= '<span class="lightLinks" style="color:#ccc;">Expired:<br />'.$LOC->set_human_time($item['expd']).'</span>';
	    } elseif (  ($item['expd'] != 0)  && ($item['expd'] > $timestamp)  ) {
        $ret .= '<span class="lightLinks" style="color:#999;">Will Expire:<br />'.$LOC->set_human_time($item['expd']).'</span>';
      } else {
        $ret .= "&nbsp;";
	    }
	  }
										
		$style = ($exp_i % 2) ? 'tableCellOne' : 'tableCellTwo'; $exp_i++;

		return $extra.$DSP->table_qcell($style, $ret);

		
	}
	// END	



function edit_entries_search_form($s)
	{	
		global $DSP, $LANG, $EXT;
		
		$s = ($EXT->last_call !== FALSE) ? $EXT->last_call : '';
	
	if( $this->settings['enable'] == 'n')
		{
		  return $s;
		  }

		
		$date_select = '<select name=\'date_range\' class=\'select\' >';
		
		 $exp_select = NBS.$DSP->input_select_header('expiration').
        	      $DSP->input_select_option('', "Filter Expiration").
        	      $DSP->input_select_option('1', "Expired").
        	      $DSP->input_select_option('2', 'Set To Expire').
        	      $DSP->input_select_option('3', 'All Expires').
        	      $DSP->input_select_footer();
		
		$s = preg_replace ('/'.$date_select.'/', $exp_select."\n".$date_select , $s);
		return $s;
	}


function edit_entries_search_where()
	{	
		global $DSP, $LANG, $EXT, $IN, $LOC;
		
		$expiry = $IN->GBL('expiration', 'POST');
		
		$timestamp = $LOC->now;
		
		if ($expiry != '' && is_numeric($expiry))
		{
		  if ($expiry == '1') {
		    return " AND (exp_weblog_titles.expiration_date != '0' && exp_weblog_titles.expiration_date < '".$timestamp."')";
		  } elseif ($expiry == '2') {
		     return " AND (exp_weblog_titles.expiration_date != '0' && exp_weblog_titles.expiration_date > '".$timestamp."')";
		     
		  } elseif ($expiry == '3'){
		     return " AND exp_weblog_titles.expiration_date != '0'";
		  } else {
		    return;
		  }
		}
	}


	/**
	* Register a new Addon Source
	*/
	function lg_addon_update_register_source($sources)
	{
		global $EXT;
		if($EXT->last_call !== FALSE)
			$sources = $EXT->last_call;
		/*
		<versions>
			<addon id='LG Addon Updater' version='2.0.0' last_updated="1218852797" docs_url="http://leevigraham.com/" />
		</versions>
		*/
		if($this->settings['check_for_updates'] == 'y')
		{
			$sources[] = 'http://masugadesign.com/versions/';
		}
		return $sources;
	}

	/**
	* Register a new Addon
	*/
	function lg_addon_update_register_addon($addons)
	{
		global $EXT;
		if($EXT->last_call !== FALSE)
			$addons = $EXT->last_call;
		if($this->settings['check_for_updates'] == 'y')
		{
			$addons[MD_SE_addon_id] = $this->version;
		}
		return $addons;
	}



/* END class */
}
/* End of file ext.md_show_expired.php */
/* Location: ./system/extensions/ext.md_show_expired.php */ 