<?php
/*
Plugin Name: Thickbox Announcement
Plugin URI: http://www.faebusoft.ch/downloads/thickbox-announcement
Description: With fsThickboxAnnouncement plugin you can set-up thickbox announcement. Several options let you control the apperenace and the frequency of the announcement.
Author: Fabian von Allmen
Author URI: http://www.faebusoft.ch
Version: 1.0.6
License: GPL
Last Update: 07.09.2009
*/

class fsThickboxAnnouncement {
	const DAY_IN_SECONDS = 86400;

	private static $plugin_name     = 'Thickbox Announcement';
	private static $plugin_vers     = '1.0.6';
	private static $plugin_id       = 'fsDbCust'; // Unique ID
	private static $plugin_options  = '';
	private static $plugin_filename = '';
	private static $plugin_dir      = '';
	private static $plugin_url      = '';
	private static $plugin_css_url  = '';
	private static $plugin_img_url  = '';
	private static $plugin_js_url   = '';
	private static $plugin_lang_dir = '';
	private static $plugin_textdom  = '';

	private $showAnnouncement = false;

	function fsThickboxAnnouncement() {
		
		// Init Vars
		self::$plugin_options  = array('tb_title'      => 'Announcement',
										  'tb_width'        => '600',
										  'tb_height'       => '400',
										  'tb_show_type'    => '2',
										  'tb_show_page'    => '2',
										  'tb_show_freq'    => '3',
										  'tb_valid_from'   => '',
										  'tb_valid_to'     => '',
										  'tb_active'       => '0',
										  'tb_close_type'   => '1',
										  'tb_close_lbl'    => 'Close',
										  'tb_modal'        => '0',
										  'tb_postid'       => '-1',
										  'tb_content_type' => '2',
										  'tb_ext_url'      => 'http://');
		self::$plugin_filename = plugin_basename( __FILE__ );
		self::$plugin_dir      = dirname(self::$plugin_filename);
		self::$plugin_url      = trailingslashit(WP_PLUGIN_URL).self::$plugin_dir.'/';
		self::$plugin_css_url  = self::$plugin_url.'css/';
		self::$plugin_img_url  = self::$plugin_url.'images/';
		self::$plugin_js_url   = self::$plugin_url.'js/';
		self::$plugin_lang_dir = trailingslashit(self::$plugin_dir).'lang/';
		self::$plugin_textdom  = 'fsThickboxAnnouncement';
		
		// General/Frontend Hooks
		add_action('init',                 array(&$this, 'hookRegisterTextDomain'));
		add_action('init',                 array(&$this, 'hookRegisterScripts'));
		add_action('init',                 array(&$this, 'hookRegisterStyles'));
		add_action('init',                 array(&$this, 'hookCheckAnnouncementShow'), 1); // High priority
		add_action('wp_footer',            array(&$this, 'hookSendAnnouncement'));
		
		// Admin Hooks
		add_action('admin_menu',           array(&$this, 'hookAddAdminMenu'));
		add_action('admin_init',           array(&$this, 'hookRegisterScriptsAdmin'));
		add_action('admin_init',           array(&$this, 'hookRegisterStylesAdmin'));
		add_action('admin_print_scripts',  array(&$this, 'hookPrintScriptsAdmin'));
		add_filter('plugin_action_links',  array(&$this, 'hookAddPlugInSettingsLink'), 10, 2 );
		
		add_action('admin_head',           array(&$this, 'hookHidePostBoxes'));
		add_action('edit_page_form',       array(&$this, 'hookAddCustomSubmitButton'));
		add_action('save_post',            array(&$this, 'hookRemovePostRevisions'));
		
		//add_action('template_redirect',    array(&$this, 'hookOverridePageContent'));
		add_action('wp_ajax_tb_reset_cookie', array(&$this, 'hookResetCookie'));

		register_activation_hook(__FILE__, array(&$this, 'hookActivate'));
		register_uninstall_hook(__FILE__,  array(&$this, 'hookUninstall'));
	}

	/**
	 * Load text domain
	 * @return void
	 */
	function hookRegisterTextDomain() {
		load_plugin_textdomain(self::$plugin_textdom, false, self::$plugin_lang_dir);
	}
	
	/**
	 * Register Scripts to load
	 * @return void
	 */
	function hookRegisterScripts() {
		if ($this->showAnnouncement == false)
			return;

		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
	}
	
	/**
	 * Register Styles to Load
	 * @return void
	 */
	function hookRegisterStyles() {
		wp_enqueue_style('thickbox');	
	}
	
	/**
	 * Checks, if the announcement will be displayed
	 * All the cookie handling is done inside this method
	 * @return void
	 */
	function hookCheckAnnouncementShow() {
		if (!$this->isAnnouncementActive())
			return;

		$now = time();
		$from = get_option('tb_valid_from');
		if (empty($from)) {
			$from = $now;
		} else{ 
			$from = strtotime($from);
		}

		$to = get_option('tb_valid_to');
		if (empty($to)) {
			$to = $now;
		} else {
			$to = strtotime($to) + self::DAY_IN_SECONDS - 1; // End of the day
		}
		
		if ($now < $from || $now > $to)
			return;

		// Not in Admin Mode
		if (strpos($_SERVER['PHP_SELF'], 'wp-admin') !== false)
			return;

		// Check if announcement has to be displayed (cookie, settings)
		//if (get_option('tb_show_page') == 1 && !is_home())
		//	return;

		$tb_show_type = get_option('tb_show_type');

		// If cookie is set AND cookie string is identical, then only show when
		// in frequency mode!				
		if (isset($_COOKIE['tb_cookie']) && $_COOKIE['tb_cookie'] == get_option('tb_cookie')) {
			// Show only once per session
			if ($tb_show_type == 1) { 
				return;
			// Show every time the user enters the site
			// This is achieved by setting the cookie expiration to 0
			} elseif ($tb_show_type == 3) {
				return;
			} elseif ($tb_show_type == 2) { // More than once, check frequency
				$days = intval(get_option('tb_show_freq'));

				if ($days > 0) {
					// Get beginning of the day, when it should be displayed again
					$aexp = floor(($_COOKIE['tb_lastshown'] + $days*self::DAY_IN_SECONDS) / self::DAY_IN_SECONDS) * self::DAY_IN_SECONDS + 1;

					if (time() < $aexp)
						return;
				}
			}
		}
		else {
			// Allways display
		}

		
		if ($tb_show_type == 3) {
			$exp = 0; // Show every time the user enters the site
		} else {
			$exp = time()+self::DAY_IN_SECONDS*360; // Expires after a year
		}
		
		setcookie('tb_cookie', get_option('tb_cookie'), $exp);
		setcookie('tb_lastshown', time(), $exp);

		// Check announcement page
		$this->checkInternalContentPage();
		
		// Turn Announcement on
		$this->showAnnouncement = true;
		return;
	}
	
	/**
	 * Writes the code for displaying the thickbox
	 * @return void
	 */
	function hookSendAnnouncement() {
		if ($this->showAnnouncement == false)
			return;

		$url = $this->getAnnouncementURL();
		if ($url == false)
			return;

		// Get Parameter data for announcement
		$t = $this->getAnnouncementTitle();
		$w = $this->getAnnouncementWidth();
		$h = $this->getAnnouncementHeight();
		$m = $this->isAnnouncementModal();

		if (strpos($url,'?') === false) {
			$url .= '?';
		}
		else {
			$url .= '&';
		}
		$url .= 'height='.$h.'&';
		$url .= 'width='.$w.'&';
		if ($m) {
			$url .= 'modal=true&';
		}
		if (get_option('tb_content_type') == 1) {
			$url .= 'TB_iframe=true&';
		}

		echo '<script>jQuery(document).ready(function(){tb_show("'.$t.'","'.$url.'","");});</script>';
	}
	
	/**
	 * Creates a menu entry in the settings menu
	 * @return void
	 */
	function hookAddAdminMenu() {
		$menutitle = '<img src="'.self::$plugin_img_url.'icon.png" alt=""> '.__('TB Announcement', self::$plugin_textdom);
		add_submenu_page('options-general.php', __('Thickbox Announcement', self::$plugin_textdom ), $menutitle, 8, self::$plugin_filename, array(&$this, 'createSettingsPage'));
	}

	/**
	 * Loads all necesarry scripts for the settings page
	 * @return void
	 */
	function hookRegisterScriptsAdmin() {
		wp_enqueue_script('thickbox');
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('sack');
		wp_enqueue_script('fs-datepicker', self::$plugin_js_url.'ui.datepicker.js');
		wp_enqueue_script('fs-date', self::$plugin_js_url.'date.js');
		wp_enqueue_script(self::$plugin_id, self::$plugin_js_url.'helper.js');
	}

	/**
	 * Loads all necessary stylesheets for the admin interface
	 * @return void
	 */
	function hookRegisterStylesAdmin() {
		wp_enqueue_style('thickbox');
		wp_enqueue_style('dashboard');
		wp_enqueue_style('fs-styles-dp', self::$plugin_css_url.'jquery-ui-1.7.2.custom.css');
		wp_enqueue_style('fs-styles', self::$plugin_css_url.'default.css');
	}
	
	function hookPrintScriptsAdmin() {
		?>
	<script type="text/javascript">
		function regenerate_cookie() {
			var mysack = new sack("<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php");
			mysack.execute = 1;
			mysack.method = "POST";
			mysack.setVar("action", "tb_reset_cookie");
			mysack.onError = function() { alert("<?php _e('Cookie could not be regenrated due a communication error', self::$plugin_textdom); ?>");};
			mysack.runAJAX();
		}
		function sendMessage(type, msg) {
			if (document.getElementById('message')) {
				document.getElementById('otc').removeChild(document.getElementById('message'));
			}
			var el = document.createElement('div');
			el.setAttribute('id', 'message');
			el.setAttribute('class', (type == 'E' ? 'error' : 'updated')+' fade');
			el.innerHTML = '<p><strong>'+msg+'</strong></p>'
			document.getElementById('otc').appendChild(el);
		}
		function tb_preview() {
			var wpurl = '<?php echo $this->getAnnouncementURL(2); ?>';
			var f = document.forms['tb_post'];
			var clt = tb_getRadioValue('tb_close_type');
			var cot = tb_getRadioValue('tb_content_type');
			if (f.tb_modal.checked == true && clt == 1) {
				if (!confirm("<?php _e("Your thickbox announcement is modal, but you have not choosen a close object. If you haven\'t implemented it by yourself, you will not be able to close the announcement. Continue?", self::$plugin_textdom); ?>")) {
					return;
				}
			}
			if (cot == 1) {
				var url = f.tb_ext_url.value;
				// Add ? at the end...
				url += '?';
			} else {
				var url = wpurl+='?';
			}
			url += 'height='+f.tb_height.value+'&';
			url += 'width='+f.tb_width.value+'&';
			url += 'modal='+(f.tb_modal.checked == true ? 'true' : 'false')+'&';
			url += 'admin_preview=true&'
			url += 'close_type='+clt+'&';
			if (cot == 1) {
				url += 'TB_iframe=true&';
			}

			tb_show(f.tb_title.value,url,'');
		}
		function tb_getRadioValue(elname) {
			for(var i=0; i<document.forms['tb_post'].elements[elname].length; i++) {
				if (document.forms['tb_post'].elements[elname][i].checked == true) {
					return document.forms['tb_post'].elements[elname][i].value;
				}
			}
		}
		function toogleCondOptions(els, field, condition) {
			var obj = els.split(",");
			for (var i=0; i<obj.length; i++) {
				var element = obj[i];
				var f = document.forms['tb_post'].elements[field];

				if (f.type) {
					if (f.type.indexOf('select') >= 0) {
						var val = f.options[f.selectedIndex].value;
					} else {
						var val = f.value;
					}
				} else {
					var val = tb_getRadioValue(field);
				}
				if (val == condition) {
					jQuery('#'+element).show();
				} else {
					jQuery('#'+element).hide();
				}
			}
		}
		</script>
		<?php 
	}
	
	/**
	 * Adds a "Settings" link for this plug-in in the plug-in overview
	 * @return void
	 */
	function hookAddPlugInSettingsLink($links, $file) {
		if ($file == self::$plugin_filename) {
			array_unshift($links, '<a href="options-general.php?page='.$file.'">'.__('Settings', self::$plugin_textdom).'</a>');
		}
		return $links;
	}
	
	/**
	 * Hides all unnecessary boxes while editing the announcement content
	 * @return void
	 */
	function hookHidePostBoxes() {
		global $post_ID;

		if (!$this->isAnnouncementPost($post_ID))
			return;

		// Hide unecessary boxes
		echo '<style type="text/css">#normal-sortables, #advanced-sortables, #pagesubmitdiv, #pageparentdiv, #edit-slug-box, #delete-action, .edit-visibility, .edit-timestamp {display: none !important}</style>'."\n";
	}
	
	/**
	 * Adds a submit button in the page edit form
	 * @todo: Enable "save and back" functionality
	 * @return void
	 */
	function hookAddCustomSubmitButton() {
		global $post_ID;
		if (!$this->isAnnouncementPost($post_ID))
			return;

		echo '<div class="wrap"><input type="submit" name="save" class="button-primary" value="'.__('Save', self::$plugin_textdom).'" /></div>';
	}
	
	/**
	 * Removes all revisions for the announcement post
	 * @return void
	 */
	function hookRemovePostRevisions($post_id) {
		if (!$this->isAnnouncementPost($post_id))
			return;

		// Check if post_id is *not* a revision id
		if (wp_is_post_revision($post_id) === false) {
			$revisions = wp_get_post_revisions( $post_id, array( 'order' => 'ASC' ) );
			foreach($revisions as $rev) {
				/*if (strpos($rev->post_name, 'autosave') !== false) {
					continue;
				}*/
				// Don't delete current revision?!?
				if ($rev->ID != $post_id) {
					wp_delete_post_revision($rev->ID);
				}
			}
		}
	}
	

	/**
	 * Regenerates the cookie string and sends a message back
	 * This is realized using ajax, so we just die inside with a javascript function
	 * @return void
	 */
	function hookResetCookie() {
		$this->generateCookieString();
		die('sendMessage("I", "'.__('Cookie successfully re-generated', self::$plugin_textdom).'");');
	}
	
	/**
	 * Checks if a post is the announcement page
	 * @return true, if post is announcement page
	 */
	function isAnnouncementPost($post_id) {
		return ($post_id == $this->getAnnouncementPostId());
	}

	/**
	 * Returns the announcement page id
	 * @return id of the announcement page
	 */
	function getAnnouncementPostId() {
		return get_option('tb_postid');
	}


	/**
	 * Returns the announcement valid from date
	 * @return valid from date
	 */
	function getValidFromDate() {
		return get_option('tb_valid_from');
	}

	/**
	 * Returns the announcement valid to date
	 * @return valid to date
	 */
	function getValidToDate() {
		return get_option('tb_valid_to');
	}

	/**
	 * Checks if announcement are active (by settings)
	 * @return True, if announcements are active
	 */
	function isAnnouncementActive() {
		return get_option('tb_active') == 1 ? true : false;
	}

	/**
	 * Returns the url to display in the thickbox
	 * @return URL
	 */
	function getAnnouncementURL($type = '') {
		if ($type == '') {
			$type = get_option('tb_content_type');
		}
		switch($type) {
			case 1:
				$url = get_option('tb_ext_url');
				if ($url == 'http://') {
					return false;
				}
				break;
			case 2:
				$url = self::$plugin_url.'fsContent.php';
				break;
			default:
				return false;
		}
		return $url;
	}

	/**
	 * Returns the thickbox's width
	 * @return Width in pixel
	 */
	function getAnnouncementWidth() {
		$w = intval(get_option('tb_width'));
		if ($w <= 0) {
			$w = self::$plugin_options['tb_width'];
		}
		return $w;
	}

	/**
	 * Returns the thickbox's height
	 * @return Height in pixel
	 */
	function getAnnouncementHeight() {
		$h = intval(get_option('tb_height'));
		if ($h <= 0) {
			$h = self::$plugin_options['tb_height'];
		}
		return $h;
	}

	/**
	 * Returns the thickbox's title
	 * @return Title
	 */
	function getAnnouncementTitle() {
		return get_option('tb_title');
	}

	/**
	 * Returns the thickbox's content
	 * @return Announcement "inline" content
	 */
	function getAnnouncementContent() {
		$post = get_post($this->getAnnouncementPostId());
		return $post->post_content;
	}

	/**
	 * Returns true, if thickbox is modal
	 * @return True, if thickbox is modal
	 */
	function isAnnouncementModal() {
		return get_option('tb_modal') == 1 ? true : false;
	}

	/**
	 * Returns the "close type"
	 * @return 1 = none, 2 = button, 3 = hyperlink
	 */
	function getAnnouncementCloseType() {
		return intval(get_option('tb_close_type'));
	}

	/**
	 * Returns the label of the close object
	 * @return Label
	 */
	function getAnnouncementCloseLabel() {
		return get_option('tb_close_lbl');
	}

	/**
	 * Generates a random cookie string
	 * @return void
	 */
	function generateCookieString() {
		$cs = substr(md5(uniqid(mt_rand(), true)), 0, 16);
		update_option('tb_cookie', $cs);
	}

	/**
	 * Checks if the internal content page exists
	 * and creates a new one, if missing
	 * @return True, if a page has beend created
	 */
	function checkInternalContentPage() {
		// This should only happen on activation
		if (get_option('tb_postid') === false || intval(get_option('tb_postid')) <= 0) {
			// Add a new Page
			$post_id = wp_insert_post(
				array('post_title'=>'Announcement',
					'post_content'=>'Sample announcement',
					'post_status' =>'private',
					'post_type'   =>'page',
					'post_author' => '1',
					'comment_status' => 'closed'
				)
			);
			add_option('tb_postid', $post_id);
			return true;
		}
		else {
			$post_id = get_option('tb_postid');
			$post = get_post($post_id);
			if (is_null($post)) {
				$post_id = wp_insert_post(
					array('post_title'=>'Announcement',
						'post_content'=>'Sample announcement',
						'post_status' =>'private',
						'post_type'   =>'page',
						'post_author' => '1',
						'comment_status' => 'closed'
					)
				);
				update_option('tb_postid', $post_id);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Creates the announcement settings page
	 * @return void
	 */
	function createSettingsPage() {

		if (!current_user_can('manage_options'))
			wp_die(__('Cheatin&#8217; uh?', self::$plugin_textdom));
		
		if ($this->checkInternalContentPage()) {
			$message = __('Internal content page is missing and has been recreated', self::$plugin_textdom);
		} else {
			$message = '';	
		}
			
		?>
		<?php echo $this->pageStart(__('Thickbox Announcement Settings', self::$plugin_textdom), $message); ?>
			<?php echo $this->pagePostContainerStart(75); ?>
			
			<form name="tb_post" method="post" action="options.php">
			<?php wp_nonce_field('update-options'); ?>
		
			<?php echo $this->pagePostBoxStart('pb_global', __('Global Setting', self::$plugin_textdom)); ?>
				<table class="fs-table">
				<tr><th colspan="2"><input type="checkbox" id="tb_active" name="tb_active" value="1" <?php echo ($this->isAnnouncementActive() ? 'checked="checked" ' : ''); ?>/> <label for="tb_active"><?php _e('Announcements active', self::$plugin_textdom); ?></label></th></tr>
				<tr><th class="label"><?php _e('Valid from', self::$plugin_textdom); ?></th><td>
					<input type="text"
					       id="datepicker_from" 
					       name="tb_valid_from"
					       value="<?php echo $this->getValidFromDate(); ?>" 
					       readonly="readonly" />
					<a href="#" class="fs-dp-clear" alt="<?php _e('Clear date', self::$plugin_textdom); ?>" onClick="document.tb_post.tb_valid_from.value = ''; return false;"><span><?php _e('Clear date', self::$plugin_textdom); ?></span></a>
				<br /><small><?php _e('Click in the input field to choose a date. Leave empty if no date vailidity needed.', self::$plugin_textdom); ?></small></td></tr>
				<tr><th class="label"><?php _e('Valid to', self::$plugin_textdom); ?></th><td>
					<input type="text" 
					       id="datepicker_to" 
					       name="tb_valid_to" 
					       value="<?php echo $this->getValidToDate(); ?>" 
					       readonly="readonly" />
					<a href="#" class="fs-dp-clear" alt="<?php _e('Clear date', self::$plugin_textdom); ?>" onClick="document.tb_post.tb_valid_to.value = ''; return false;"><span><?php _e('Clear date', self::$plugin_textdom); ?></span></a>
				<br /><small><?php _e('Click in the input field to choose a date. Leave empty if no date vailidity needed.', self::$plugin_textdom); ?></small></td></tr>
				</table>
			<?php echo $this->pagePostBoxEnd(); ?>

			<?php echo $this->pagePostBoxStart('pb-announcement', __('Announcement Behaviour', self::$plugin_textdom)); ?>
				<table class="fs-table">
				<tr><th class="label"><?php _e('Announcement Settings', self::$plugin_textdom); ?></th>
				<td>
					<?php _e('Show when user enters', self::$plugin_textdom); ?> <select name="tb_show_page">
					<!--<option value="1" <?php echo(get_option('tb_show_page') == 1 ? 'selected="selected" ' : ''); ?>><?php _e('Homepage', self::$plugin_textdom); ?></option>//-->
					<option value="2" <?php echo(get_option('tb_show_page') == 2 ? 'selected="selected" ' : ''); ?>><?php _e('Any page', self::$plugin_textdom); ?></option></select>
				</td>
				</tr>
				<tr><th>&nbsp;</th>
				<td>
					<?php _e('Show announcement', self::$plugin_textdom); ?> <select name="tb_show_type" onClick="toogleCondOptions('tb_freq','tb_show_type','2')">
					<option value="1" <?php echo (get_option('tb_show_type') == 1 ? 'selected="selected" ' : ''); ?>><?php _e('once', self::$plugin_textdom); ?></option>
					<option value="3" <?php echo (get_option('tb_show_type') == 3 ? 'selected="selected" ' : ''); ?>><?php _e('every time the user enters the site', self::$plugin_textdom); ?></option>
					<option value="2" <?php echo (get_option('tb_show_type') == 2 ? 'selected="selected" ' : ''); ?>><?php _e('periodically', self::$plugin_textdom); ?></option></select><br /> 
					<small><?php _e('if you choose "once" and you setup a new announcement, which has to be displayed again for every user, please', self::$plugin_textdom); ?> 
					<a href="javascript: regenerate_cookie();"><?php _e('reset the cookie string', self::$plugin_textdom); ?></a>.</small>
				</td>
				</tr>
				<tr id="tb_freq" style="display: <?php echo (get_option('tb_show_type') == 2 ? 'table-row' : 'none'); ?>;">
				<td>&nbsp;</td>
				<td>
					<?php _e('Show announcement every', self::$plugin_textdom); ?> <input type="text" name="tb_show_freq" value="<?php echo get_option('tb_show_freq');?>" size="2" /> <?php _e('day(s)', self::$plugin_textdom); ?>
				</td>
				</tr>
				</table>
			<?php echo $this->pagePostBoxEnd(); ?>

			<?php echo $this->pagePostBoxStart('pb-tbset', __('Thickbox Settings', self::$plugin_textdom)); ?>
				<table class="fs-table">
				<tr><th class="label"><?php _e('Title', self::$plugin_textdom); ?></th><td><input type="text" id="tb_title" name="tb_title" value="<?php echo $this->getAnnouncementTitle(); ?>" size="60" /></td></tr>
				<tr><th><?php _e('Width', self::$plugin_textdom); ?></th><td><input type="text" id="tb_width" name="tb_width" value="<?php echo $this->getAnnouncementWidth(); ?>" size="6" /> px</td></tr>
				<tr><th><?php _e('Height', self::$plugin_textdom); ?></th><td><input type="text" id="tb_height" name="tb_height" value="<?php echo $this->getAnnouncementHeight(); ?>" size="6" /> px</td></tr>
				<tr><th>&nbsp;</th><td>
				<input type="checkbox" id="tb_modal" name="tb_modal" value="1" <?php echo ($this->isAnnouncementModal() ? 'checked="checked" ' : ''); ?>/> <label for="tb_modal"><?php _e('Modal', self::$plugin_textdom); ?></label><br />
				<small><?php _e('Close button/link needed and no title is displayed!', self::$plugin_textdom); ?></small></td></tr>
				</table>
			<?php echo $this->pagePostBoxEnd(); ?>

			<?php echo $this->pagePostBoxStart('pb-content', __('Announcement Content', self::$plugin_textdom)); ?>
				<table class="fs-table">
				<tr><th class="label"><?php _e('Content', self::$plugin_textdom); ?></th><td>
				<input type="radio" id="tb_content_type_1" name="tb_content_type" value="1" <?php echo (get_option('tb_content_type') == 1 ? 'checked="checked" ' : ''); ?>onClick="toogleCondOptions('tb_cobj,tb_clbl','tb_content_type','2')"/> 
				<label for="tb_content_type_1"><?php _e('External Resource', self::$plugin_textdom); ?></label> 
				<input type="text" id="tb_ext_url" name="tb_ext_url" value="<?php echo get_option('tb_ext_url'); ?>" size="60" /></td></tr>
				<tr><th>&nbsp;</th><td>
				<input type="radio" id="tb_content_type_2" name="tb_content_type" value="2" <?php echo (get_option('tb_content_type') == 2 ? 'checked="checked" ' : ''); ?>onClick="toogleCondOptions('tb_cobj,tb_clbl','tb_content_type','2')"/> 
				<label for="tb_content_type_2"><?php _e('Inline Content', self::$plugin_textdom); ?> <a href="<?php echo get_option('siteurl').'/wp-admin/page.php?action=edit&post='.$this->getAnnouncementPostId(); ?>"><?php _e('Edit now', self::$plugin_textdom); ?></a></label>
				</td>
				<tr id="tb_cobj" style="display: <?php echo (get_option('tb_content_type') == 2 ? 'table-row' : 'none'); ?>;">
				<th class="label"><?php _e('Close object', self::$plugin_textdom); ?></th> 
				<td>
				<input type="radio" id="tb_close_type1" name="tb_close_type" value="1" <?php echo (get_option('tb_close_type') == 1 ? 'checked="checked" ' : ''); ?>/> 
				<label for="tb_close_type1"><?php _e('None', self::$plugin_textdom); ?></label><br />
				<input type="radio" id="tb_close_type2" name="tb_close_type" value="2" <?php echo (get_option('tb_close_type') == 2 ? 'checked="checked" ' : ''); ?>/> 
				<label for="tb_close_type2"><?php _e('Button', self::$plugin_textdom); ?></label><br />
				<input type="radio" id="tb_close_type3" name="tb_close_type" value="3" <?php echo (get_option('tb_close_type') == 3 ? 'checked="checked" ' : ''); ?>/> 
				<label for="tb_close_type3"><?php _e('Link', self::$plugin_textdom); ?></label>
				</td>
				</tr>
				<tr id="tb_clbl" style="display: <?php echo (get_option('tb_content_type') == 2 ? 'table-row' : 'none'); ?>;">
				<th><?php _e('Label', self::$plugin_textdom); ?></th>
				<td>
				<input type="text" id="tb_close_lbl" name="tb_close_lbl" value="<?php echo get_option('tb_close_lbl'); ?>" size="20" />
				</td></tr>
				</table>
			<?php echo $this->pagePostBoxEnd(); ?>
							
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', self::$plugin_textdom); ?>" /> 
			<input type="button" class="button-primary" value="<?php _e('Preview', self::$plugin_textdom); ?>" onClick="tb_preview()" /> 
			</p>
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="tb_action" value="tb_save_options" />
			<?php echo '<input type="hidden" name="page_options" value="';
			foreach(self::$plugin_options as $k => $v) {
				if ($k != 'tb_postid') {
					echo $k.',';
				}
			}
			echo '" />'; ?>
			</form>
			<?php echo $this->pagePostContainerEnd(); ?>
			
			<?php echo $this->pagePostContainerStart(20); ?>				
				<?php echo $this->pagePostBoxStart('pb_about', __('About', self::$plugin_textdom)); ?>
					<p><?php _e('For further information please visit the', self::$plugin_textdom); ?> <a href="http://www.faebusoft.ch/downloads/thickbox-announcement"><?php _e('plugin homepage', self::$plugin_textdom);?></a>.<br /> 
				<?php echo $this->pagePostBoxEnd(); ?>
								
				<?php echo $this->pagePostBoxStart('pb_donate', __('Donation', self::$plugin_textdom)); ?>
					<p><?php _e('If you like my work please consider a small donation', self::$plugin_textdom); ?></p>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
					</form>
				<?php echo $this->pagePostBoxEnd(); ?>
			<?php echo $this->pagePostContainerEnd(); ?>
		<?php echo $this->pageEnd(); ?>

		<?php
	}

	/**
	 * Overrides the output, when requesting the announcement page
	 * This is necessary, because we only want the page content without header
	 * or footer and we have to grab the content manually because the page is
	 * private by default
	 * @TODO: At the moment this function is not used, because the announcement
	 * output is done in fsContent.php. Perhaps this has to be changed one time (why?)
	 */
	/*function hookOverridePageContent() {
		$preview = isset($_GET['admin_preview']);

		if (!$this->isAnnouncementActive() && !$preview)
			return;

		$post_id = url_to_postid($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);

		if ($post_id == $this->getAnnouncementPostId()) {

			$post = get_post($post_id);
			echo $post->post_content;

			if ($preview) {
				$close = $_GET['close_type'];
			}
			else {
				$close = $this->getAnnouncementCloseType();
			}
			if ($close > 1) {
				echo '<p style="text-align:center;" id="fsTBA_Close">';
				if ($close == 2) { // Button
					echo '<input type="button" onClick="tb_remove();" value="<?php $this->getAnnouncementCloseLabel(); ?>" />';
				} elseif ($close == 3) { // Link
					echo '<a href="javascript:void(0)" onClick="tb_remove();">'.$this->getAnnouncementCloseLabel(); ?></a>';
				}
				echo '</p>';
			}
			exit;
		}
	}
	*/

	/**
	 * Returns the page start html code
	 * @param $title Postbox Title
	 * @return String Page start html
	 */
	function pageStart($title, $message = '') {
		$ret =  '<div class="wrap">
				<div id="icon-options-general" class="icon32"><br /></div>
				<div id="otc"><h2>'.$title.'</h2>';
		if (!empty($message)) 
			$ret .= '<div id="message" class="updated fade"><p><strong>'.$message.'</strong></p></div>';
		$ret .= '</div>';
		return $ret;
	}
	
	/**
	 * Returns the page end html code
	 * @return String Page end html
	 */
	function pageEnd() {
		return '</div>';
	}
	
	/**
	 * Returns the code for a widget container
	 * @param $width Width of Container (percent)
	 * @return String Container start html
	 */
	function pagePostContainerStart($width) {
		return '<div class="postbox-container" style="width:'.$width.'%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">';
	}
	
	/**
	 * Returns the code for the end of a widget container
	 * @return String Container end html
	 */
	function pagePostContainerEnd() {
		return '</div></div></div>';
	}
	
	/**
	 * Returns the code for the start of a postbox
	 * @param $id Unique Id
	 * @param $title Title of pagebox
	 * @return String Postbox start html
	 */
	function pagePostBoxStart($id, $title) {
		return '<div id="'.$id.'" class="postbox">
			<h3 class="hndle"><span>'.$title.'</span></h3>
			<div class="inside">';
	}
	
	/**
	 * Returns the code for the end of a postbox
	 * @return String Postbox end html
	 */
	function pagePostBoxEnd() {
		return '</div></div>';
	}
	
	/**
	 * Adds all necessary options when activating the plugin and creates a new
	 * page for the announcement
	 */
	function hookActivate() {
		// Add all options
		foreach(self::$plugin_options as $k => $v) {
			if ($k <> 'tb_postid' && get_option($k) === false) {
				add_option($k, $v);
			}
		}

		if (get_option('tb_cookie') === false) {
			add_option('tb_cookie', '');
			$this->generateCookieString();
		}

		$this->checkInternalContentPage();
	}

	
	/**
	 * Deletes the announcement page and all options
	 */
	function hookUninstall()  {
		// Delete Anoucement
		wp_delete_post(get_option('tb_postid'));

		// Remove all options
		foreach(self::$plugin_options as $k => $v) {
			remove_option($k);
		}

		remove_option('tb_cookie');
	}
}

if (class_exists('fsThickboxAnnouncement')) {
	$fsThickboxAnnouncement = new fsThickboxAnnouncement();
}
?>