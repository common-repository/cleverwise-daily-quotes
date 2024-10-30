<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Verify admin panel is loaded, if not fail
////////////////////////////////////////////////////////////////////////////
if (!is_admin()) {
	die();
}

////////////////////////////////////////////////////////////////////////////
//	Menu call
////////////////////////////////////////////////////////////////////////////
add_action('admin_menu', 'cw_daily_quotes_aside_mn');

////////////////////////////////////////////////////////////////////////////
//	Start A Session To Store Security Tokens
////////////////////////////////////////////////////////////////////////////
function boot_session() {
  session_start();
}
add_action('wp_loaded','boot_session');

////////////////////////////////////////////////////////////////////////////
//	Load admin menu option
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_aside_mn() {
Global $current_user,$dq_wp_option;
	$settings_mgrs='';

	if (is_user_logged_in()) {
		////////////////////////////////////////////////////////////////////////////
		//	For wp admins always verify as they have setting access
		////////////////////////////////////////////////////////////////////////////
		if (current_user_can('manage_options')) {
			$daily_quotes_mng_disp='y';
			add_submenu_page('options-general.php','Daily Quotes Panel','Daily Quotes','manage_options','cw-daily-quotes','cw_daily_quotes_aside');
		//	Check non wp admins
		} else {
			//	Load options for plugin
			$dq_wp_option_array=get_option($dq_wp_option);
			$dq_wp_option_array=unserialize($dq_wp_option_array);
			if (isset($dq_wp_option_array['settings_mgrs'])) {
				$settings_mgrs=$dq_wp_option_array['settings_mgrs'];
			}

			//	Grab current username
			$current_user_login=$current_user->user_login;

			//	If usernames are defined verify access, else clear
			if ($settings_mgrs) {
				$settings_mgrs=explode("\n",$settings_mgrs);
				if (in_array($current_user_login,$settings_mgrs)) {
					add_menu_page('Daily Quotes','Daily Quotes','publish_pages','cw-daily-quotes','cw_daily_quotes_aside','','31');
				}
			}		
		}
	}
}

////////////////////////////////////////////////////////////////////////////
//	Load admin functions
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_aside() {
Global $wpdb,$dq_wp_option,$cw_daily_quotes_tbl,$cw_posts_tbl,$cwfa_dq,$dq_memcached,$dq_memcached_conn,$current_user;


	////////////////////////////////////////////////////////////////////////////
	//	Load options for plugin
	////////////////////////////////////////////////////////////////////////////
	$dq_wp_option_array=get_option($dq_wp_option);
	$dq_wp_option_array=unserialize($dq_wp_option_array);

	////////////////////////////////////////////////////////////////////////////
	//	Set action value
	////////////////////////////////////////////////////////////////////////////
	if (isset($_REQUEST['cw_action'])) {
		$cw_action=$_REQUEST['cw_action'];
	} else {
		$cw_action='main';
	}
	
	
	//	Logic to deal with Cross Site Scripting attacks

	//	Server security token
	$cw_local_security=$current_user->user_login.'ChaManao';
	
	//	Check security key or set if main - update on every main access for security	
	if ($cw_action == 'main') {
		
		//	Genereate a random string
		$cws_characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $cws_randstring = '';
        for ($i = 0; $i < 64; $i++) {
            $cws_randstring .= $cws_characters[rand(0, strlen($cws_characters))];
        }
        
		$_SESSION["$cw_local_security"] = "$cws_randstring";
		$cwpChaManao=$cws_randstring;
	} else {
		if (isset($_SESSION[$cw_local_security]) and isset($_REQUEST['cwpChaManao'])) {
			if ($_SESSION[$cw_local_security] == $_REQUEST['cwpChaManao']) {
				$cwpChaManao=$_REQUEST['cwpChaManao'];
			} else {
				die('<p style="font-weight: strong; font-size: 20px;">Unable to complete your request!</p>');
			}
		} else {
			die('<p style="font-weight: strong; font-size: 20px;">Unable to complete your request!</p>');
		}
	}

	////////////////////////////////////////////////////////////////////////////
	//	Previous page link
	////////////////////////////////////////////////////////////////////////////
	$pplink='<a href="javascript:history.go(-1);">Return to previous page...</a>';

	////////////////////////////////////////////////////////////////////////////
	//	Define Variables
	////////////////////////////////////////////////////////////////////////////
	$cw_daily_quotes_action='';
	$cw_daily_quotes_html='';
	$cw_plugin_name='cleverwise-daily-quotes';
	$cw_plugin_hname='Cleverwise Daily Quotes';
	$cw_plugin_page='cw-daily-quotes';
	$wp_plugins_url=plugins_url().'/'.$cw_plugin_name;

	//	Default Layout
$daily_quotes_layout_def='';
$daily_quotes_layout_def .=<<<EOM
<div style="width: 296px; padding: 0px; margin: 0px; border: 1px solid #000000; background-color: #000000; color: #ffffff; font-family: tahoma; font-size: 14px; font-weight: bold; text-align: center; -moz-border-radius: 5px 5px 0px 0px; border-radius: 5px 5px 0px 0px;"><div style="padding: 1px;">{{quote_title}}</div></div>
<div style="width: 296px; padding: 0px; margin-bottom: 10px; border: 1px solid #000000; border-top: 0px; font-family: tahoma; color: #000000; -moz-border-radius: 0px 0px 5px 5px; border-radius: 0px 0px 5px 5px;"><div style="padding: 5px;">{{quote}}</div></div>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	View Quotes
	////////////////////////////////////////////////////////////////////////////
	if ($cw_action == 'quotesview') {
		$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		$qod_sid=$qid;
		$qtitle=stripslashes($dq_wp_option_array['section_titles'][$qod_sid]);
		$qweight='';
		$cw_daily_quotes_day='0';

		// If weight is included remove from title
		if (preg_match('/{{DQO}}/',$qtitle)) {
			list ($qweight,$qtitle)=explode('{{DQO}}',$qtitle);
		}

		$cw_daily_quotes_action='Viewing Quotes';

		$quotes='';
		if ($qod_sid > '0') {
			$myrows=$wpdb->get_results("SELECT qod_quote FROM $cw_daily_quotes_tbl where qod_sid='$qod_sid'");
			if ($myrows) {
				$day_cnt='1';
				foreach ($myrows as $myrow) {
					$qod_quote=stripslashes($myrow->qod_quote);
					if ($qod_quote) {
						$current_day=cw_daily_quotes_current_date($day_cnt);
						$quotes .='<div class="qblock"><div class="qtitle"><div class="qtitleleft">Day '.$day_cnt.'</div><div class="qtitleright">'.$current_day.'</div></div>'.$qod_quote."</div>";
					}
					$day_cnt++;
				}
			}
		}
		
$cw_daily_quotes_html .=<<<EOM
<p><b>$qtitle</b>  <img src="$wp_plugins_url/img/edit.svg" id="cws-resources-icon" name="cws-resources-icon"> <a href="?page=cw-daily-quotes&cw_action=quoteedit&qid=$qid&cwpChaManao=$cwpChaManao">Edit</a></p>
<p>Shortcode: <div style="margin-left: 20px;">Optional: The following shortcode will display this daily section, which is very useful for pages (not limited to only pages).  You should keep in mind that the shortcode will always show this daily section as the "where to display" setting has no influence.<br><br>[cw_daily_quotes cw_ds_id="$qid"]<br><br>
If you desire to limit the number of words displayed use the following:<br><br>
[cw_daily_quotes cw_ds_id="$qid" cw_ds_wcnt="10"]<br><br>
Set cw_ds_wcnt="10" to equal the number of words so 5 is for five words, 10 is for ten words, 12 is for twelve words, etc</div></p>
EOM;

		//	Display pages using shortcode:
		$myrows=$wpdb->get_results("SELECT post_title FROM $cw_posts_tbl where post_content like '%[cw_daily_quotes cw_ds_id%$qid%]%' group by post_title");
		if ($myrows) {
			$cw_sc_cnt='1';
			$post_list='';
			foreach ($myrows as $myrow) {
				$post_title=stripslashes($myrow->post_title);
				$post_list .='<b style="margin-left: 20px;">'.$cw_sc_cnt.'</b>) '.$post_title.'<br>';
				$cw_sc_cnt++;
			}
			$cw_sc_cnt--;
			$cw_daily_quotes_html .='<p style="margin-left: 20px;">'.$cw_sc_cnt.' page(s)/post(s) using this shortcode:<br><i>'.$post_list.'</i></p>';
		}

$cw_daily_quotes_html .=<<<EOM
<style>
	.qblock {
		margin-bottom: 20px;
	}
	.qtitle {
		overflow: hidden; diplay: block; width: 400px; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px dashed #000000;
	}
	.qtitleleft {
		width: 100px; float: left;
	}
	.qtitleright {
		width: 100px; float: right; text-align: right;
	}
</style>
<p>&nbsp;</p>
<p>Daily Information:</p>
$quotes
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Multipart Quotes
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'mpqguide') {

		$cw_daily_quotes_action='Multipart Quote Explanation';

$cw_daily_quotes_html .=<<<EOM
<p><a href="javascript:history.go(-1);">Previous Page</a></p>

<p>You now have the ability to create complex daily entries!  This will greatly enhance what is possible.</p>

<p>What does multipart quotes mean?  Well exactly as it sounds.  You are no longer limited to thinking about a quote as one complete entry.  Nope.  Now you are able to break up individual quotes into multiple pieces.  At present up to fifteen (15) different pieces.</p>

<p>For example let's say you want to display a daily changing image, that links to a specific product store page, and you want a different click here text for each entry.</p>

<p>Let's look at what the code would look like for a single entry:</p>

<p>&lt;a href="/store/product1.html"&gt;&lt;img src="/images/image1.jpg" width="200px" height="200px" border="0"&gt;&lt;/a&gt;&lt;p&gt;&lt;a href="/store/product1.html"&gt;Click to read about product one&lt;/a&gt;&lt;/p&gt;</p>

<p>Now the original and old way was you would have to repeat that line above for each daily entry changing product1.html, image1.jpg, and product one text.  This was necessary because you need to change the information in four places (remember there are two links that point to the same page but still need to be changed).  While repeating the code with minor changes works it is cumbersome and inefficient.</p>

<p>What we need is to break up a daily quote entry into multiple parts.  In this example the store URL, the image filename, and the product name.  We need three different pieces.  See where this going?</p>

<p>So let's see how this is done with the new system:</p>

<p>You still keep a single entry on one line (or use your custom separator) however now you use the special <b>{{DQP}}</b> token to separate the pieces for each entry.  Let's take a look:</p>

<p>product1.html{{DQP}}image1.jpg{{DQP}}product one</p>

<p>See what is going on?  We have the unique store page url as the first piece, the image name as the second piece, and the link text as the third piece.  It makes no difference the order other than it must be the same for all daily entries in the same section/name.</p>

<p>Now we just repeat for the next entries (four below) with an return between each daily quote/entry, like before:</p>

<p>product1.html{{DQP}}image1.jpg{{DQP}}product one<br>product2.html{{DQP}}image2.jpg{{DQP}}product two<br>product3.html{{DQP}}image3.jpg{{DQP}}product three<br>product4.html{{DQP}}image4.jpg{{DQP}}product four</p>

<p>So you would continue the pattern until you had at least ten (10).  That's it for the daily quotes part however we now need to edit the theme/template so all this displays, otherwise the daily entry won't look correct.</p>

<p>In the theme/template area you now have access to fifteen (15) different codes or one for each piece.  They are <b>{{DQP1}}</b>, <b>{{DQP2}}</b>, <b>{{DQP3}}</b>, <b>{{DQP4}}</b>, <b>{{DQP5}}</b>, <b>{{DQP6}}</b>, <b>{{DQP7}}</b>, <b>{{DQP8}}</b>, <b>{{DQP9}}</b>, <b>{{DQP10}}</b>, <b>{{DQP11}}</b>, <b>{{DQP12}}</b>, <b>{{DQP13}}</b>, <b>{{DQP14}}</b>, <b>{{DQP15}}</b>.  It makes sense right?  Now all we do is place these token codes in our theme/template where ever we want the specific information to be used and/or appear.</p> 

<p>In our example:</p>

<p>&lt;a href="/store/<b>{{DQP1}}</b>"&gt;&lt;img src="/images/<b>{{DQP2}}</b>" width="200px" height="200px" border="0"&gt;&lt;/a&gt;&lt;p&gt;&lt;a href="/store/<b>{{DQP1}}</b>"&gt;Click to read about <b>{{DQP3}}</b>&lt;/a&gt;&lt;/p&gt;</p>

<p>You see how {{DQP1}} is a placeholder for our unique store page?  This will be product1.html or product2.html or product3.html, etc depending on the day.  Why is {{DQP1}} the store page instead of the image?  Because that's how we created the entry.  We could have put images first but we didn't and that is all there is to it.  Also notice how we can use the same token multiple times.  It is used twice, in this example, so both links go to the correct page.</p>

<p>The same is true for the other placeholder tokens in our case {{DQP2}} will be replaced with the image and {{DQP3}} with the product name.  This again is the way we entered the information.</p>

<p>Now you are totally free to put that inside other css and html tags like div's or span's for example.</p>

<p><b>Important items:</b></p>

<ol>
<li>You don't use {{quote}} or {{quote_url}} tokens when using this method.</li>
<li>Blank (empty/nothing) will be inserted for missing pieces.  So if you forget to assign say a fourth piece to day 126 the {{DQP4}} will be removed and be empty/blank/have nothing.</li>
<li>At present if you assign more than fifteen (15) pieces the plugin will discard/remove the rest of the daily entry.</li>
</ol>

<p>You can get really creative and do some advanced daily content rotating.  The example provided is rather simple and could get even more advanced with custom width and height for the images or include the .html in the template/theme and drop it from the daily entries.  There is a lot of room for streamlined layouts.  At any rate what are you waiting for?  Get to it!</p>

<p><a href="javascript:history.go(-1);">Previous Page</a></p>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Content Check
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'contentcheck') {
		$quotes='';
		$qsetp='';
		$quotebuild='';
		$qsep='';

		if (isset($_REQUEST['quotes'])) {
			$quotes=trim($_REQUEST['quotes']);
		}
		if (isset($_REQUEST['qsep'])) {
			$qsep=$_REQUEST['qsep'];
		}

		$cw_daily_quotes_action='Content Day Check';

		if (!$qsep) {
			$qsep="\n";
		}

		if ($quotes) {
			$quotes=preg_replace('/\r/','',$quotes);
			$quotes_cnt=substr_count($quotes,"$qsep");
			if ($quotes_cnt > '0') {
				$quotes=explode("$qsep",$quotes);
				$quotenum='0';
				foreach ($quotes as $quote) {
					$quotenum++;
					$quotebuild .='<div style="width: 400px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #000000;">Day '.$quotenum.') '.$quote.'</div>';
				}
				$quotebuild='<p>Number Of Days: '.$quotenum.'</p>'.$quotebuild;
			} else {
				$quotebuild='<p>No quote information.  Check your separator.</p>';
			}
				$quotebuild='<p><a href="javascript:history.go(-1);">Previous Page</a></p>'.$quotebuild;
		} else {
			$quotebuild='';
$quotebuild .=<<<EOM
	<p>Enter your daily content to see how the plugin will separate it into the various days.</p>
	<form method="post"><input type="hidden" name="action" value="contentcheck"><input type="hidden" name="cwpChaManao" value="$cwpChaManao">
		<p>Enter the separator between your quotes.  The default is enter.  For example: Quote 1 enter key then Quote 2 enter key then Quote 3 enter key, etc.  However if you wish to use a different separator perhaps %break% you may enter this below.  If you are confused or wish to simply use enter between your quote records leave this blank.</p>
		<p>Quote Separator: <input type="text" name="qsep" value="" style="width: 200px;"></p>
		<p>Quotes:<br><br><textarea name="quotes" style="width: 400px; height: 250px;"></textarea></p><input type="submit" value="Go" class="button">
	</form>
EOM;
		}

		$cw_daily_quotes_html .="$quotebuild";

	////////////////////////////////////////////////////////////////////////////
	//	Add/Edit Quotes
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'quoteadd' or $cw_action == 'quoteedit') {
		if (isset($_REQUEST['qid'])) {
			$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		} else {
			$qid='0';
		}
		$qtype='';
		$qtitle='';
		$qsep='';
		$quotes='';
		$qcats='';
		$qweight='';
		$qlayout='';
		$ds_shortcode='';
		
		$cw_daily_quotes_action_btn='Add';
		if ($cw_action == 'quoteedit') {
			$qod_sid=$qid;

			$cw_daily_quotes_action_btn='Edit';

			//	Get separator
			$myrows=$wpdb->get_results("SELECT qod_quote FROM $cw_daily_quotes_tbl where qod_sid='$qod_sid' and qod_day='999'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$qsep=stripslashes($myrow->qod_quote);
					if ($qsep == "\n") {
						$qsep='';
					}
				}
			}

			//	Build quote list
			$myrows=$wpdb->get_results("SELECT qod_day,qod_quote FROM $cw_daily_quotes_tbl where qod_sid='$qod_sid'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					//	If not separator add to list
					if ($myrow->qod_day < '999') {
						$qod_quote=stripslashes($myrow->qod_quote);
						$quotes .=$qod_quote;
						if ($myrow->qod_day < '365') {
							$quotes .=$qsep."\n";
						}
					}
				}
			}

			$qtitle=stripslashes($dq_wp_option_array['section_titles'][$qod_sid]);
			$qtype=$dq_wp_option_array['section_types'][$qod_sid];
			$qcats=$dq_wp_option_array['section_categories'][$qod_sid];
			$qlayout=stripslashes($dq_wp_option_array['section_layouts'][$qod_sid]);

			// If weight is included remove from title
			if (preg_match('/{{DQO}}/',$qtitle)) {
				list ($qweight,$qtitle)=explode('{{DQO}}',$qtitle);
			}
		}

		//	Display Types
		$cw_qtypes_layout='<input type="radio" name="qtype" value="%s"%s> %s ';
		$quote_types=array('a'=>'All categories','e'=>'Exclude the following categories (don\'t show in)','i'=>'Include the following categories (show in)','n'=>'No categories (hide/off)');
		$qtypes='';
		foreach ($quote_types as $quote_type_id => $quote_type_name) {
			$wpcheck='';
			if ($qtype == $quote_type_id) {
				$wpcheck=' checked';
			}
			$qtypesbuild=sprintf($cw_qtypes_layout,$quote_type_id,$wpcheck,$quote_type_name);
			$qtypes .=$qtypesbuild.'<br>';
		}

		//	Get WP Categories
		$cw_category_layout='<input type="checkbox" name="qcategories[]" value="%s"%s> %s ';
		$args=array('orderby'=>'name','order'=>'ASC');
		$wp_categories=get_categories($args);
		$categories='';
		foreach ($wp_categories as $wp_category) {
			$wp_cat_data=$wp_category;
			$wpcatid=$wp_cat_data->term_id;
			$wpcatname=$wp_cat_data->name;

			$wpcheck='';
			if (substr_count("$qcats|","$wpcatid|") > '0') {
				$wpcheck=' checked';
			}
			$categorybuild=sprintf($cw_category_layout,$wpcatid,$wpcheck,$wpcatname);
			$categories .=$categorybuild.'<br>';
		}

		$cw_daily_quotes_action=$cw_daily_quotes_action_btn.'ing Quote Section';
		$cw_action .='sv';
		
		$cw_category_url=admin_url('edit-tags.php?taxonomy=category');
		
$cw_daily_quotes_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="$cw_action">
<input type="hidden" name="cwpChaManao" value="$cwpChaManao">
<input type="hidden" name="qid" value="$qid">
<p>Quote Title: <input type="text" name="qtitle" value="$qtitle" style="width: 400px;"></p>
<p>&nbsp;</p>
<p>366 Daily Quotes - HTML Markup Supported:</p>
<p>Multipart Quotes! <a href="?page=cw-daily-quotes&cw_action=mpqguide&cwpChaManao=$cwpChaManao">Read more...</a></p>

<div style="border: 1px solid #c1c1c1; padding: 5px; margin: 0px 0px 10px 20px;"><i>Optional Quote Separator:</i> Enter the separator between your quotes.  The default is enter.  For example: Quote 1 enter key then Quote 2 enter key then Quote 3 enter key, etc.  However if you wish to use a different separator perhaps %break% you may enter this below.  If you are confused or wish to simply use enter between your quote records leave this blank.<br><br>
Custom Quote Separator: <input type="text" name="qsep" value="$qsep" style="width: 200px;"></div>

<div style="margin: 0px 0px 10px 20px;">If you don't have 366 days worth of content ready you may tell the system to reuse available content to finish missing days.  You will need at least ten (10) days worth for this option.</div>
<div style="margin-left: 20px;"><input type="checkbox" name="qcntoride" value='1'> Check if you don't have 366 days of content</div></p>

<div style="margin: 0px 0px 10px 20px;"><b>Notice (v2.0+):</b> On leap years the 60th entry will be used, while on non leap years it will be skipped.</div>

(Place an enter/return or your custom separator from above between each daily content)

<p><textarea name="quotes" style="width: 500px; height: 300px;">$quotes</textarea></p>
<p>&nbsp;</p>
<p>Where should this daily section be displayed?</p>
<p>$qtypes</p>
<p>Your WordPress Categories: [<a href="$cw_category_url">Manage Categories</a>]<br><i>-&gt; Categories require at least one article assigned to them to appear in list</i></p><p>$categories</p>
<p>&nbsp;</p>
<p>Optional Order Number:</p>
<div style="margin: 0px 20px 0px 20px;"><p>This will control the order this section is displayed when it appears with other sections.  The lower the order number the sooner (above others) this section will appear on a page.  For example if the order number is 21 this section will appear before any other sections higher than 21 but below any sections with 20 or lower.  If two daily sections have the same number the titles will be put into alphabetical order.  Confused? No worries just skip this part as it is optional.</p>
<p>Order number: <input type="text" name="qweight" value="$qweight" style="width: 100px;"></p></div>
<p>&nbsp;</p>
<p>Optional Custom Layout:</p>
<div style="margin: 0px 20px 0px 20px;"><p>This optional layout/theme/style will be used instead of the general layout.  Leave blank to use general layout.<br><br><b>{{quote_title}}</b> = Display Quote Title<br><i>The following tokens should NOT be used for multipart quotes:</i><br><b>{{quote}}</b> = Display Daily Quote<br><b>{{quote_url}}</b> = Displays Daily Quote For Use In URL Or Form<br>&#42; For example to add tweet link: &#60;a href="https://twitter.com/home?status={{quote_url}}"&#62;Tweet This&#60;/a&#62;</b>
<p><textarea name="qlayout" style="width: 400px; height: 200px;">$qlayout</textarea></p></div>
<p>&nbsp;</p>
<p><input type="submit" value="$cw_daily_quotes_action_btn" class="button"> &#171;&#171; Please be patient!</p>
</form>
EOM;

		if ($qlayout) {
			$cw_daily_quotes_html .='<p>&nbsp;</p><p>Saved custom layout preview:</p>'.$qlayout;
		}

		if ($cw_action == 'quoteeditsv') {

$cw_daily_quotes_html .=<<<EOM
<p>&nbsp;</p>
<div id="del_link" name="del_link" style="border-top: 1px solid #d6d6cf; margin-top: 20px; padding: 5px; width: 390px;"><a href="javascript:void(0);" onclick="document.getElementById('del_controls').style.display='';document.getElementById('del_link').style.display='none';">Show deletion controls</a></div>
<div name="del_controls" id="del_controls" style="display: none; width: 390px; margin-top: 20px; border: 1px solid #d6d6cf; padding: 5px;">
<a href="javascript:void(0);" onclick="document.getElementById('del_controls').style.display='none';document.getElementById('del_link').style.display='';">Hide deletion controls</a>
<form method="post">
<input type="hidden" name="cw_action" value="quotesdel"><input type="hidden" name="qid" value="$qid"><input type="hidden" name="cwpChaManao" value="$cwpChaManao">
<p><input type="checkbox" name="dq_confirm_1" value="1"> Check to delete $qtitle</p>
<p><input type="checkbox" name="dq_confirm_2" value="1"> Check to confirm deletion of $qtitle</p>
<p><span style="color: #ff0000; font-weight: bold;">Deletion is final! There is no undoing this action!</span></p>
<p style="text-align: right;"><input type="submit" value="Delete" class="button"></p>
</div>
EOM;
	}

	////////////////////////////////////////////////////////////////////////////
	//	Add/Edit Quotes Save
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'quoteaddsv' or $cw_action == 'quoteeditsv') {
		$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		$qtitle=trim($_REQUEST['qtitle']);
		$qsep=$_REQUEST['qsep'];
		$quotes=trim($_REQUEST['quotes']);
		$qtype=$cwfa_dq->cwf_san_an($_REQUEST['qtype']);
		$qweight=$cwfa_dq->cwf_san_ilist($_REQUEST['qweight']);

		if (isset($_REQUEST['qcategories'])) {
			$qcategories=$_REQUEST['qcategories'];
		} else {
			$qcategories='';
		}
		$qlayout=trim($_REQUEST['qlayout']);
		if (isset($_REQUEST['qcntoride'])) {
			$qcntoride=$cwfa_dq->cwf_san_int($_REQUEST['qcntoride']);
		} else {
			$qcntoride='';
		}
		$qcats='';

		$error='';

		if (!$qtitle) {
			$error .='<li>No Quote Title</li>';
		}

		if (!$qsep) {
			$qsep="\n";
		}

		$quotes=preg_replace('/\r/','',$quotes);
		$quote_count='0';
		if (substr_count($quotes,"$qsep")) {
			$quotes=explode("$qsep",$quotes);
			$quote_count=count($quotes);
		}
		if ($quote_count != '366') {
			if ($quote_count > '9' and $qcntoride == '1') {
				$quotecontent=$quotes;
				$qsnum=$quote_count;
				$qi='0';
				while ($quote_count < '366') {
					$quote_count++;
					array_push($quotes,$quotecontent[$qi]);
					$qi++;
					if ($qsnum <= $qi) {
						$qi='0';
					}
				}
			} else {
				$error .='<li>Need 366 Daily Quotes</li>';
			}
		}

		if (!$qtype) {
			$error .='<li>Choose where to display daily section</li>';
		}

		if ($qcategories) {
			foreach ($qcategories as $qcategory) {
				$qcats .=trim($qcategory).'|';
			}
		}

		if (!$qcats and $qtype != 'a') {
			$error .='<li>No Categories selected</li>';
		}

		if ($error) {
			$cw_daily_quotes_action='Error';
			$cw_daily_quotes_html='Please fix the following in order to save daily information:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
		} else {
			$cw_daily_quotes_action='Success';

			//	If set to all categories clear category list
			if ($qtype == 'a') {
				$qcats='';
			}

			//	Set/get quote section id
			if ($cw_action == 'quoteeditsv') {
				$qod_sid=$qid;

				//	If memcached is enabled delete possible record
				if ($dq_memcached == 'on') {
					$curday=date('z');
					$memcache_key=home_url().'-'.$qod_sid.'-'.$curday;
					$memcache_key=hash('whirlpool',$memcache_key);
					$memcached_status=$dq_memcached_conn->get($memcache_key);
					if ($memcached_status) {
						$dq_memcached_conn->delete($memcache_key);
					}
				}
			} else {
				//	Update count
				$dq_count=$dq_wp_option_array['count'];
				$dq_count++;
				$dq_wp_option_array['count']=$dq_count;
				$qod_sid=$dq_count;
			}


			//	Alter qtitle if weight
			if (isset($qweight) and $qweight != '') {
				$qtitle=$qweight.'{{DQO}}'.$qtitle;
			}

			//	Save information
			$dq_wp_option_array['section_titles'][$qod_sid]=$qtitle;
			$dq_wp_option_array['section_types'][$qod_sid]=$qtype;
			$dq_wp_option_array['section_categories'][$qod_sid]=$qcats;
			$dq_wp_option_array['section_layouts'][$qod_sid]=$qlayout;

			natsort($dq_wp_option_array['section_titles']);

			$dq_wp_option_array=serialize($dq_wp_option_array);
			$dq_wp_option_chk=get_option($dq_wp_option);

			if (!$dq_wp_option_chk) {
				add_option($dq_wp_option,$dq_wp_option_array);
			} else {
				update_option($dq_wp_option,$dq_wp_option_array);
			}

			//	Multisite - Check for table and create if missing
			if ( is_multisite() ) {
				$cw_daily_quotes_dbname=DB_NAME;
				$myrows=$wpdb->get_results("SELECT count(*) as tbl_chk FROM information_schema.TABLES WHERE (TABLE_SCHEMA = '$cw_daily_quotes_dbname') AND (TABLE_NAME = '$cw_daily_quotes_tbl1');");
				if ($myrows) {
					foreach ($myrows as $myrow) {
						if ($myrow->tbl_chk < '1') {
							//	Create table
							cw_daily_quotes_create_table();
						}
					}
				}
			}

			//	Save quotes
			$qod_day='0';
			foreach ($quotes as $qod_quote) {
				$data=array();
				if ($qod_day < '366') {
					$data['qod_quote']=trim($qod_quote);
				}

				if ($cw_action == 'quoteeditsv') {
					$where=array();
					$where['qod_sid']=$qod_sid;
					$where['qod_day']=$qod_day;
					$wpdb->update($cw_daily_quotes_tbl,$data,$where);
				} else {
					$data['qod_sid']=$qod_sid;
					$data['qod_day']=$qod_day;
					$wpdb->insert($cw_daily_quotes_tbl,$data);
					$dl_id=$wpdb->insert_id;
				}
				$qod_day++;
			}


			//	Save separator
			if ($qod_day > '365') {
				//	Clear single line break
				if ($qsep == "\n") {
					$qsep='';
				}
				$data['qod_quote']=$qsep;

				if ($cw_action == 'quoteeditsv') {
					$where['qod_sid']=$qod_sid;
					$where['qod_day']='999';
					$wpdb->update($cw_daily_quotes_tbl,$data,$where);
				} else {
					$data['qod_sid']=$qod_sid;
					$data['qod_day']='999';
					$wpdb->insert($cw_daily_quotes_tbl,$data);
					$dl_id=$wpdb->insert_id;
				}
			}

			$qtitle=stripslashes($qtitle);
			// If weight is included remove from title
			if (preg_match('/{{DQO}}/',$qtitle)) {
				list ($qweight,$qtitle)=explode('{{DQO}}',$qtitle);
			}

			$cw_daily_quotes_html='<p>'.$qtitle.' has been successfully saved!</p><p><a href="?page=cw-daily-quotes">Continue</a></p>';
		}

	////////////////////////////////////////////////////////////////////////////
	//	Delete Quote Section
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'quotesdel') {
		$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		$qod_sid=$qid;
		$qtitle=stripslashes($dq_wp_option_array['section_titles'][$qod_sid]);
		$qweight='';

		// If weight is included remove from title
		if (preg_match('/{{DQO}}/',$qtitle)) {
			list ($qweight,$qtitle)=explode('{{DQO}}',$qtitle);
		}

		if (isset($_REQUEST['dq_confirm_1'])) {
			$dq_confirm_1=$cwfa_dq->cwf_san_int($_REQUEST['dq_confirm_1']);
		} else {
			$dq_confirm_1='0';
		}
		if (isset($_REQUEST['dq_confirm_2'])) {
			$dq_confirm_2=$cwfa_dq->cwf_san_int($_REQUEST['dq_confirm_2']);
		} else {
			$dq_confirm_2='0';
		}

		$cw_daily_quotes_action='Delete Quote Section';

		if (!$qod_sid) {
			$dq_confirm_1='0';
		}

		if ($dq_confirm_1 == '1' and $dq_confirm_2 == '1') {
			$where=array();
			$where['qod_sid']=$qod_sid;
			$wpdb->delete($cw_daily_quotes_tbl,$where);

			unset($dq_wp_option_array['section_titles'][$qod_sid]);
			unset($dq_wp_option_array['section_types'][$qod_sid]);
			unset($dq_wp_option_array['section_categories'][$qod_sid]);
			unset($dq_wp_option_array['section_layouts'][$qod_sid]);

			$dq_wp_option_array=serialize($dq_wp_option_array);
			update_option($dq_wp_option,$dq_wp_option_array);

			//	If memcached is enabled delete possible record
			if ($dq_memcached == 'on') {
				$curday=date('z');
				$memcache_key=home_url().'-'.$qod_sid.'-'.$curday;
				$memcache_key=hash('whirlpool',$memcache_key);
				$memcached_status=$dq_memcached_conn->get($memcache_key);
				if ($memcached_status) {
					$dq_memcached_conn->delete($memcache_key);
				}
			}

			$cw_daily_quotes_html=$qtitle.' has been removed! <a href="?page=cw-daily-quotes">Continue...</a>';
		} else {
			$cw_daily_quotes_html='<span style="color: #ff0000;">Error! You must check both confirmation boxes!</span><br><br>'.$pplink;
		}

	////////////////////////////////////////////////////////////////////////////
	//	Settings
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settings' or $cw_action == 'settingsv') {

		if ($cw_action == 'settingsv') {
			$cw_daily_quotes_action='Sav';
			$error='';

			//	Handle layout
			$daily_quotes_layout=trim($_REQUEST['daily_quotes_layout']);
			if (!$daily_quotes_layout and $daily_quotes_layout != 'reset') {
				$error .='<li>No General Theme/Layout</li>';
			} else {
				if ($daily_quotes_layout == 'reset') {
					$daily_quotes_layout=$daily_quotes_layout_def;
				}
				$dq_wp_option_array['layout']=$daily_quotes_layout;
			}
			
			//	Handle editors
			$dq_wp_option_array['settings_mgrs']=$cwfa_dq->cwf_san_anr($_REQUEST['settings_mgrs']);

			if ($error) {
				$cw_daily_quotes_html='Please fix the following in order to save settings:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
			} else {
				$dq_wp_option_array=serialize($dq_wp_option_array);
				$dq_wp_option_chk=get_option($dq_wp_option);

				if (!$dq_wp_option_chk) {
					add_option($dq_wp_option,$dq_wp_option_array);
				} else {
					update_option($dq_wp_option,$dq_wp_option_array);
				}

				$cw_daily_quotes_html .='Settings have been saved! <a href="?page=cw-daily-quotes">Continue to Main Menu</a>';
			}

		} else {
			$cw_daily_quotes_action='Edit';
			$daily_quotes_layout=$dq_wp_option_array['layout'];

			if (!$daily_quotes_layout) {
				$daily_quotes_layout=$daily_quotes_layout_def;
			}
			$daily_quotes_layout=stripslashes($daily_quotes_layout);

			$daily_quotes_layout_def=$daily_quotes_layout;
			$daily_quotes_layout_def=preg_replace('/{{quote_title}}/','Quote Title Here',$daily_quotes_layout_def);
			$daily_quotes_layout_def=preg_replace('/{{quote}}/','Daily quote displayed here',$daily_quotes_layout_def);
			
			if (isset($dq_wp_option_array['settings_mgrs'])) {
				$settings_mgrs=$dq_wp_option_array['settings_mgrs'];
			} else {
				$settings_mgrs='';
			}
			
$cw_daily_quotes_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="settingsv"><input type="hidden" name="cwpChaManao" value="$cwpChaManao">
<p>General Theme/Layout:<div style="margin-left: 20px;">This is the layout/theme/style that will be used when no custom quote layout is provided.<br><br><b>{{quote_title}}</b> = Display Quote Title<br><b>{{quote}}</b> = Display Daily Quote<br><b>{{quote_url}}</b> = Displays Daily Quote For Use In URL Or Form<br>&#42; For example to add tweet link: &#60;a href="https://twitter.com/home?status={{quote_url}}"&#62;Tweet This&#60;/a&#62;<br><br>Enter the word "reset" without quotes to have the system set the style back to original theme/layout.</div></p>
<p><textarea name="daily_quotes_layout" style="width: 400px; height: 250px;">$daily_quotes_layout</textarea></p>
<p>Saved layout preview:</p>
$daily_quotes_layout_def
<p><i>User Access Control - Optional:</i></p>
<div style="margin-left; 20px;">You may control what editors have access to the <b>Daily Quotes Management</b> panel.  Editors will be able to add and remove other editors.  All admins have access so this section only applies to editors.  If you don't want to use this feature or don't understand simply leave the box blank (skip it).</div>
<p>Authorized Wordpress Editor Usernames:<br><b>Note: Enter usernames one per line</b></p><p><textarea name="settings_mgrs" style="width: 400px; height: 100px;">$settings_mgrs</textarea></p>
<p><input type="submit" value="Save" class="button"></p>
</form>
EOM;
		}
		$cw_daily_quotes_action .='ing Settings';

	////////////////////////////////////////////////////////////////////////////
	//	What Is New?
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingsnew') {
		$cw_daily_quotes_action='What Is New?';

		$cw_daily_quotes_whats_new=array(
			'3.4'=>'Fixed: Cross site script vulnerability patched|Fixed: Minor bug when no content was detected using content check function',
			'3.2'=>'Added ability to limit number of words when using shortcode',
			'3.0'=>'Fixed: PHP 8 undefined error in logs|Minor text updates',
			'2.8'=>'Added ability to allow editors to making changes|Added date next to day count when viewing quote section|Added warning message when no general theme is saved|Multipart quotes now support fifteen (15) parts instead of ten (10)|Fixed: Date over run bug when viewing|Moved daily quote section shortcode and pages/posts using it to view from edit|Altered theme',
			'2.5'=>'Fixed: PHP 7.2 Patch',
			'2.4'=>'Fixed: A few minor bugs|Added order (weight) numbering|Altered theme',
			'2.2'=>'Fixed: Several notice messages have been resolved|Added multipart quotes',
			'2.0'=>'Fixed: Multisite Network install (both methods supported)',
			'1.9'=>'Multisite Standard install tested|Custom separator support|Non leap year adjustment (Safe to use dates)|Minor theme changes',
			'1.8'=>'Added new theme feature that alters daily quote for URL and form safe calls',
			'1.7'=>'Fixed: Display bug when multiple daily sections where shown|Fixed: PHP error message when missing section ids',
			'1.6'=>'Day change now based on Wordpress Timezone setting',
			'1.5'=>'Plugin can now easily add missing daily content to reach 366 days',
			'1.4'=>'Added ability to check daily content for day count|Background edits to eliminate some PHP notice messages',
			'1.3'=>'Ability to hide/turn off daily sections|Added link to WordPress category area for easier management|Shortcode support to directly load a daily section; useful for pages',
			'1.2'=>'An easy to use display widget has been added',
			'1.1'=>'Fixed: Shortcode in certain areas would cause incorrect placement',
			'1.0'=>'Initial release of plugin'
		);
		$cw_daily_quotes_whats_new_build='';
		foreach ($cw_daily_quotes_whats_new as $cw_daily_quotes_whats_new_version => $cw_daily_quotes_whats_new_news) {
			$cw_daily_quotes_whats_new_build .='<p>Version: <b>'.$cw_daily_quotes_whats_new_version.'</b></p>';
			$cw_daily_quotes_whats_new_news=preg_replace('/\|/','</li><li>',$cw_daily_quotes_whats_new_news);
			$cw_daily_quotes_whats_new_build .='<ul style="list-style: disc; margin-left: 25px;"><li>'.$cw_daily_quotes_whats_new_news.'</li></ul>';
		}

$cw_daily_quotes_html .=<<<EOM
<p>The following lists the new changes from version-to-version.</p>
$cw_daily_quotes_whats_new_build
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Help Guide
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingshelp') {
		$cw_daily_quotes_action='Help Guide';

$cw_daily_quotes_html .=<<<EOM
<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Introduction:</div>
<p>This system allows you to display daily changing information such as quotes/tips/snippets on your Wordpress site.  You have total control over the layout/theme of this information.  There is also no limit to the number of daily sections you may place on your site.  In addition you may set a custom layout/theme for any daily section.  Plus you are able to control which categories a specific daily section will be displayed in when your visitors stop by your site.</p>
<p>Note: If you place the shortcode to display daily section information in a text widget and it isn't working, then you need to add a filter code.  At the bottom of this guide you will find the code to add to your theme's <b>functions.php</b> file.  Place the code on a new line and save changes.  If necessary upload the file change to your website.  If you are confused no worries there is an easy to use drag and drop display widget too.</p>
<p>Steps:</p>
<ol>
<li><p>In <b>Settings</b> edit and save the default/general theme/layout.</p></li>
<li><p>Now add your first daily quotes/tips/snippets section by clicking on <b>Add New Section</b> in the <b>Main Panel</b>.</p></li>
<li><p>There are four pieces of information when adding a daily section:</p>
<ol>
<li>Choose a daily section title.  This will be shown to your visitors unless you remove the daily title token.  When multiple daily sections are to be displayed this title will be used in determining the alphabetical order.</li>
<li>Enter 366 daily pieces of information separated by enter (line break).  This is where you enter your quotes/tips/snippets.  So quote 1 enter quote 2 enter quote 3, etc.  Why 366? This covers leap years, thus the last item will only be shown on those years.  If you don't have 366 daily pieces of content ready no problem as there is an option to have the system complete the missing days for you.</li>
<li>Choose where you want the daily section to appear and if necessary the corresponding categories.  If a post is assigned to multiple categories the first category will be used.  In addition if all categories is selected the daily section will automatically appear in any new categories added to Wordpress.</li>
<li>You may set an unique theme/layout for this daily section.  If left blank the default/general theme in <b>Settings</b> will be used.</li>
</ol>
</li>
<li>Now save the daily section, obviously fixing any errors that are displayed.</li>
<li><p>There are three methods to display the daily quote sections.  You may use one, two, or all methods.</p>
	<ol>
	<li><b>Widget method</b>: Visit "Appearance" in your Wordpress admin navigation then "Widgets".  You will find a widget called "Daily Quote Sections" which you may add to the desired section(s)/area(s) of your site.  This is the easiest method.</li>
	<li><b>General Shortcode method</b>: Add the general shortcode <b>[cw_daily_quotes]</b> to the area(s) of your Wordpress site (header, footer, widgets, sidebar, post(s), page(s), etc) where you wish the daily sections to be displayed.  This will display all daily sections for your various categories.  Do keep in mind that, by default, Wordpress doesn't process shortcodes in text widgets.  Therefore you will need to add the code below to your <b>functions.php</b> file.  This is the most versatile method and will automatically display ALL daily sections when appropriate.</li>
	<li><b>Direct Shortcode method</b>: Add the special daily section shortcode, provided in the edit section area, to display that specific section in a specific area on your site.  This could be a page, post, header, footer, sidebar, etc.  You may repeat a direct shortcode multiple times on your site.  This method is NOT category specific.  Do keep in mind that, by default, Wordpress doesn't process shortcodes in text widgets.  Therefore you will need to add the code below to your <b>functions.php</b> file.  This is the most detailed method as it will ONLY display ONE daily section per direct shortcode placement.</li>
	</ol>
</li>
<li>Now add and edit additional daily sections as needed.  Do keep in mind categories with multiple daily sections will display them in alphabetical order by section title.</li>
<li>Optional: This system supports the ability to use the Memcached storage system for optimized daily information loading.  To enable this first verify you have access to Memcached and PHP is setup correctly for this feature; ask your web hosting provider.  Now edit <b>memcached.config.sample.php</b> in the <b>cleverwise-daily-quotes</b> directory in your Wordpress plugins directory.  You'll see two options.  First set the address of the Memcached server.  Second set the port to Memcached, usually the default.  Now save the file as <b>memcached.config.php</b> and upload it to the <b>cleverwise-daily-quotes</b> directory.  To see if it is working load the <b>Main Panel</b> and look at <b>Memcached Status</b>.  It should read "On - optimized quote pulling".  If not check your Memcached settings.  If your Wordpress site displays fatal error don't panic; simply delete <b>memcached.config.php</b> and all will return to normal.  You may try again.  An incorrect Memcached address and/or port won't cause fatal errors, however misediting the file could.</li>
</ol>

<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Text widget filter code for your theme's functions.php:</div>
add_filter('widget_text', 'do_shortcode');
<p>Tip: If you only use the Widget display method you may skip the above code.</p>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Main panel
	////////////////////////////////////////////////////////////////////////////
	} else {
		// Current day
		$current_day=current_time('z');
		$current_day++;
		
		// Current date
		$current_date=date('l - F dS');
		
		// Get daily quote sections
		$daily_sections='';

		$dcnt='0';
		$daily_section_titles=$dq_wp_option_array['section_titles'];
		$qweight='';

		if ($daily_section_titles) {
			//asort($daily_section_titles);
			natsort($daily_section_titles);
			foreach ($daily_section_titles as $daily_section_title_id => $daily_section_title) {

				$daily_section_title=stripslashes($daily_section_title);
				// If weight is included remove from title
				if (preg_match('/{{DQO}}/',$daily_section_title)) {
					list ($qweight,$daily_section_title)=explode('{{DQO}}',$daily_section_title);
				}

				$dcnt++;
				$daily_sections .='<p style="padding-bottom: 15px; border-bottom: 1px dashed #000000; line-height: 1.8;">Section '.$dcnt.':<br><a href="?page=cw-daily-quotes&cw_action=quotesview&qid='.$daily_section_title_id.'&cwpChaManao='.$cwpChaManao.'"> '.$daily_section_title.'</a>&nbsp;&nbsp;&nbsp;';
				$daily_sections .='<img src="'.$wp_plugins_url.'/img/edit.svg" id="cws-resources-icon" name="cws-resources-icon"> <a href="?page=cw-daily-quotes&cw_action=quoteedit&qid='.$daily_section_title_id.'&cwpChaManao='.$cwpChaManao.'">Edit</a>  ';
				$daily_sections .='<img src="'.$wp_plugins_url.'/img/view.svg" id="cws-resources-icon" name="cws-resources-icon"> <a href="?page=cw-daily-quotes&cw_action=quotesview&qid='.$daily_section_title_id.'&cwpChaManao='.$cwpChaManao.'">View</a></p>';
			}
		}
		if (!$daily_sections) {
			$daily_sections='<p>None! What are you waiting for? Add one!</p>';
		}

		$dq_memcached_chk='Off';
		if ($dq_memcached == 'on') {
			$memcache_key=home_url().'-'.time();
			$memcache_key=hash('whirlpool',$memcache_key);
			$memcached_status=$dq_memcached_conn->set($memcache_key,'pass',0,'15');
			$memcached_status=$dq_memcached_conn->get($memcache_key);
			if ($memcached_status) {
				$dq_memcached_chk='<span style="color: #008000;">On - optimized quote pulling</span>';
			}
		}

$cw_daily_quotes_action='Main Panel';

	if (!isset($dq_wp_option_array['layout'])) {
		$cw_daily_quotes_html .='<div style="border: 1px solid #a30000; background-color: #ff5d5d; color: #FFFFFF; font-weight: bold; font-size: 16px; margin: 20px 0px 20px 0px; padding: 5px 10px 5px 10px;">WARNING!!! WARNING!!! WARNING!!! Your daily quote sections will NOT display without a theme.  Please visit settings and save a general theme!</div>';
	}
	
$cw_daily_quotes_html .=<<<EOM
	<p><img src="$wp_plugins_url/img/tools.svg" id="cws-resources-icon" name="cws-resources-icon"><b>Tools:</b> <a href="?page=$cw_plugin_page&cw_action=settings&cwpChaManao=$cwpChaManao">Settings</a>  |  <a href="?page=cw-daily-quotes&cw_action=contentcheck&cwpChaManao=$cwpChaManao">Content Day Check</a>  |  <a href="?page=cw-daily-quotes&cw_action=settingsnew&cwpChaManao=$cwpChaManao">What Is New?</a></p>

	<div style="margin: 15px 0px 20px 0px;"><b>Current Day:</b> $current_day ($current_date)</div>

	<p><a href="?page=cw-daily-quotes&cw_action=quoteadd&cwpChaManao=$cwpChaManao" id="cws-btn" name="cws-btn">Add New Section</a></p>

	<div style="margin: 20px 0px 10px 0px; border: 1px dashed #000000; border-left: 0px; border-right: 0px; padding: 5px 0px 5px 0px;"><b>Daily Quote Sections:</b> $dcnt</div>

	$daily_sections
	<div style="font-size: 11px; color: #858281;">Memcached Status: $dq_memcached_chk</div>
EOM;
	}

	////////////////////////////////////////////////////////////////////////////
	//	Send to print out
	////////////////////////////////////////////////////////////////////////////
	cw_daily_quotes_admin_browser($cw_daily_quotes_html,$cw_daily_quotes_action,$cw_plugin_name,$cw_plugin_hname,$cw_plugin_page,$wp_plugins_url,$cwpChaManao);
}

////////////////////////////////////////////////////////////////////////////
//	Print out to browser (wp)
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_admin_browser($cw_daily_quotes_html,$cw_daily_quotes_action,$cw_plugin_name,$cw_plugin_hname,$cw_plugin_page,$wp_plugins_url,$cwpChaManao) {
	//	<div style="margin: 15px; width: 90%; font-size: 10px; line-height: 1;">Adds the ability to display daily changing information sections to your site with total control over the layout/theme.  You may control which categories a daily section appears in and, if desired, a custom theme that is different from the default/general one.  There is no limit to the number of daily sections you may add to your site.  Also this system supports WordPress Networks (multisite), is leap year aware, and allows for custom day separators.</div>
	//	<div style="margin: 0px 15px 10px 15px; font-size: 12px;">&#9851; Share your experience with $cw_plugin_hname by leaving a review!</a> (new window).</div>

print <<<EOM
<style type="text/css">
#cws-wrap {margin: 20px 20px 20px 0px;}
#cws-wrap a {text-decoration: none; color: #3991bb;}
#cws-wrap a:hover {text-decoration: underline; color: #ce570f;}
#cws-resources {padding-top: 5px; margin: 20px 0px 20px 0px;font-size: 12px;}
#cws-title-box {overflow: hidden; border: 1px solid #ab5c23; border-radius: 5px; padding: 2px 5px 2px 5px;}
#cws-resources a:hover {text-decoration: none; font-weight:bold;}
#cws-resources-icon {vertical-align: middle; width: 20px; height: 20px;}
#cws-resources p {line-height: 1.3em;}
#cws-inner {padding: 5px;}
#btn-review {border-left: 1px solid #8dbedf; padding-left: 10px;}
#cws-btn {border: 1px solid #3991bb; border-radius: 5px; padding: 4px;}
#cws-btn:hover {border: 1px solid #ce570f; color: #ce570f; text-decoration: none;}
</style>
<div id="cws-wrap" name="cws-wrap">
	<div style="overflow: hidden; margin-bottom: 15px;">
		<h2 style="padding: 0px; margin: 0px 10px 0px 0px; float: left;"><a href="?page=$cw_plugin_page">$cw_plugin_hname</a></h2>
		<div style="float: left;"><a href="https://wordpress.org/support/view/plugin-reviews/$cw_plugin_name" target="_blank" class="btn-review" name="btn-review" id="btn-review">Leave A Review</a></a></div>
	</div>

	<div id="cws-title-box" name="cws-title-box">
		<div style="font-size: 13px; font-weight: bold; float: left;">Current: <span style="color: #ab5c23;">$cw_daily_quotes_action</span></div>
		<div style="float: left; border-left: 1px solid #8dbedf; margin-left: 10px; padding-left: 10px; font-weight: bold;"><a href="?page=$cw_plugin_page&cw_action=settingshelp&cwpChaManao=$cwpChaManao">Help Guide</a></div>
	</div>
	
	<p>$cw_daily_quotes_html</p>

	<div id="cws-resources" name="cws-resources" class="cws-resources">
		<div id="cws-title-box" name="cws-title-box"><i>Resources (open in new windows):</i></div>
		<p><img src="$wp_plugins_url/img/donate.svg" id="cws-resources-icon" name="cws-resources-icon"> <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7VJ774KB9L9Z4" target="_blank">Donate - Thank You!</a></p>
		<p><img src="$wp_plugins_url/img/consulting.svg" id="cws-resources-icon" name="cws-resources-icon"> <a href="https://www.cyberws.com/professional-technical-consulting/" target="_blank">Professional Wordpress, PHP, Server Consulting</a></p>
		<p><img src="$wp_plugins_url/img/support.svg" id="cws-resources-icon" name="cws-resources-icon"> <a href="https://wordpress.org/support/plugin/$cw_plugin_name" target="_blank">Get $cw_plugin_hname Support</a></p>
		<p><img src="$wp_plugins_url/img/plugins.svg" id="cws-resources-icon" name="cws-resources-icon"> <a href="https://www.cyberws.com/cleverwise-plugins" target="_blank">See Other Cleverwise Plugins</a></p>
	</div>
</div>
EOM;
}

////////////////////////////////////////////////////////////////////////////
//	Activate
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_activate() {
	Global $wpdb,$dq_wp_option_version_txt,$dq_wp_option_version_num,$cw_daily_quotes_tbl;
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');

	$dq_wp_option_db_version=get_option($dq_wp_option_version_txt);

	//	Create table
	cw_daily_quotes_create_table();
 
	//	Insert version number
	if (!$dq_wp_option_db_version) {
		add_option($dq_wp_option_version_txt,$dq_wp_option_version_num);
	}
}

////////////////////////////////////////////////////////////////////////////
//	Create table
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_create_table() {
	Global $wpdb,$cw_daily_quotes_tbl;
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');

//	Create category table
	$table_name=$cw_daily_quotes_tbl;
$sql .=<<<EOM
CREATE TABLE IF NOT EXISTS `$table_name` (
  `qod_id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `qod_sid` int(5) unsigned NOT NULL,
  `qod_day` int(3) unsigned NOT NULL,
  `qod_quote` text NOT NULL,
  PRIMARY KEY (`qod_id`),
  KEY `qod_sid` (`qod_sid`,`qod_day`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
EOM;
	dbDelta($sql);
}

////////////////////////////////////////////////////////////////////////////
//	Current Date
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_current_date($current_day) {
	if ($current_day < '32') {
		$current_day='Jan '.$current_day;
	} elseif ($current_day < '61') {
		$current_day=$current_day-31;
		$current_day='Feb '.$current_day;
	} elseif ($current_day < '92') {
		$current_day=$current_day-60;
		$current_day='Mar '.$current_day;
	} elseif ($current_day < '122') {
		$current_day=$current_day-91;
		$current_day='Apr '.$current_day;
	} elseif ($current_day < '153') {
		$current_day=$current_day-121;
		$current_day='May '.$current_day;
	} elseif ($current_day < '183') {
		$current_day=$current_day-152;
		$current_day='Jun '.$current_day;
	} elseif ($current_day < '214') {
		$current_day=$current_day-182;
		$current_day='Jul '.$current_day;
	} elseif ($current_day < '245') {
		$current_day=$current_day-213;
		$current_day='Aug '.$current_day;
	} elseif ($current_day < '275') {
		$current_day=$current_day-244;
		$current_day='Sep '.$current_day;
	} elseif ($current_day < '306') {
		$current_day=$current_day-274;
		$current_day='Oct '.$current_day;
	} elseif ($current_day < '336') {
		$current_day=$current_day-305;
		$current_day='Nov '.$current_day;
	} else {
		$current_day=$current_day-335;
		$current_day='Dec '.$current_day;
	}
	return $current_day;
}
