<?php

/*======================================================================
Copyright Project BeehiveForum 2002

This file is part of BeehiveForum.

BeehiveForum is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BeehiveForum is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Beehive; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  
USA
======================================================================*/

// THREAD LIST DISPLAY

// Require functions
require_once("./include/html.inc.php"); // HTML functions
require_once("./include/threads.inc.php"); // Thread processing functions
require_once("./include/format.inc.php"); // Formatting functions
require_once("./include/form.inc.php"); // Form drawing functions
require_once("./include/header.inc.php");

header_no_cache();

// Check that required variables are set
if (!isset($HTTP_COOKIE_VARS['bh_sess_uid'])) {
    $user = 0; // default to UID 0 if no other UID specified
    if (!isset($HTTP_GET_VARS['mode'])) { 
        $mode = 0;
    } else {
        // non-logged in users can only display "All" threads or those in the past x days, since the other options would be impossible
        if ($HTTP_GET_VARS['mode'] == 0 || $HTTP_GET_VARS['mode'] == 3 || $HTTP_GET_VARS['mode'] == 4 || $HTTP_GET_VARS['mode'] == 5) {
            $mode = $HTTP_GET_VARS['mode'];
        } else {
            $mode = 0;
        }
    }
} else {
    $user = $HTTP_COOKIE_VARS['bh_sess_uid'];
    if (isset($mark_all_read)) threads_mark_all_read();
    if (!isset($HTTP_GET_VARS['mode'])) {
        if (threads_any_unread()) { // default to "Unread" messages for a logged-in user, unless there aren't any
            $mode = 1;
        } else {
            $mode = 0;
        }
    } else { 
        $mode = $HTTP_GET_VARS['mode'];
    }
}

if(isset($HTTP_GET_VARS['folder'])){
    $folder = $HTTP_GET_VARS['folder'];
    $mode = 0;
}

if(!isset($HTTP_GET_VARS['start_from'])) { $start_from = 0; } else { $start_from = $HTTP_GET_VARS['start_from']; }

// Output XHTML header
html_draw_top();

// Drop out of PHP to start the HTML table
?>

<script language="JavaScript" type="text/javascript">
// Func to change the little icon next to each discussion title
function change_current_thread (thread_id) {
	if (current_thread > 0){
		document["t" + current_thread].src = "./images/bullet.png";
		document["t" + thread_id].src = "./images/ct.png";
	}
	current_thread = thread_id;
}
// -->
</script>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="postbody">
      <img src="./images/star.png" width="14" height="14" alt="star.png" />&nbsp;<a href="post.php" target="main">New Discussion</a><br />
      <img src="./images/star.png" width="14" height="14" alt="star.png" />&nbsp;<a href="#">Create Poll</a><br />
      <img src="./images/star.png" width="14" height="14" alt="star.png" />&nbsp;<a href="#">Search</a><br />
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>
<?

$labels = array("All Discussions","Unread Discussions","Unread \"To: Me\"","Today's Discussions",
                "2 Days Back","7 Days Back","High Interest","Unread High Interest",
                "I've recently seen","I've ignored","I've subscribed to");

echo "      <form name=\"f_mode\" method=\"get\" action=\"".$HTTP_SERVER_VARS['PHP_SELF']."\">\n        ";
echo form_dropdown_array("mode",range(0,10),$labels,$mode,"onchange=\"submit()\""). "\n        ";
echo form_submit("go","Go!"). "\n";

?>
      </form>
    </td>
  </tr>
<?php

// The tricky bit - displaying the right threads for whatever mode is selected

if(isset($folder)){
	list($thread_info, $folder_order) = threads_get_folder($user,$folder,$start_from);
} else {
    switch ($mode) {
    	case 0: // All discussions
    		list($thread_info, $folder_order) = threads_get_all($user, $start_from);
    		break;
    	case 1; // Unread discussions
    		list($thread_info, $folder_order) = threads_get_unread($user);
    		break;
    	case 2; // Unread discussions To: Me
    		list($thread_info, $folder_order) = threads_get_unread_to_me($user);
    		break;
        case 3; // Today's discussions
    		list($thread_info, $folder_order) = threads_get_by_days($user, 1);
    		break;
        case 4; // 2 days back
    		list($thread_info, $folder_order) = threads_get_by_days($user, 2);
    		break;
        case 5; // 7 days back
    		list($thread_info, $folder_order) = threads_get_by_days($user, 7);
    		break;
        case 6; // High interest
    		list($thread_info, $folder_order) = threads_get_by_interest($user, 1);
    		break;
        case 7; // Unread high interest
    		list($thread_info, $folder_order) = threads_get_unread_by_interest($user, 1);
    		break;
        case 8; // Recently seen
    		list($thread_info, $folder_order) = threads_get_recently_viewed($user);
    		break;
        case 9; // Ignored
    		list($thread_info, $folder_order) = threads_get_by_interest($user, -1);
    		break;
        case 10; // Subscribed to
    		list($thread_info, $folder_order) = threads_get_by_interest($user, 2);
    		break;
    }
}

// Now, the actual bit that displays the threads...

// Get folder FIDs and titles
$folder_info = threads_get_folders();
if (!$folder_info) die ("Could not retrieve folder information");

// Get total number of messages for each folder
$folder_msgs = threads_get_folder_msgs();

// Check to see if $folder_order is an array, and define it as one if not
if (!is_array($folder_order)) $folder_order = array();


// Sort the folders and threads correctly as per the URL query for the TID

if (isset($HTTP_GET_VARS['msg'])) {

    $threadvisible = false;

    list($tid, $pid) = explode('.', $HTTP_GET_VARS['msg']);
    list($thread['tid'], $thread['fid'], $thread['title'], $thread['length'], $thread['poll_flag'], $thread['modified'], $thread['closed'])  = thread_get($tid);
    
    $thread['title'] = stripslashes($thread['title']);    
    
    if ($thread['tid'] == $tid) {
    
      array_splice($folder_order, array_search($thread['fid'], $folder_order), 1);
      array_unshift($folder_order, $thread['fid']);
      
      for ($i = 0; $i < sizeof($thread_info); $i++) {
      
        if ($thread_info[$i]['tid'] == $tid) {
          $thread_info = array_merge(array_splice($thread_info, $i, 1), $thread_info);
          $threadvisible = true;
        }
        
      }
      
      if (!$threadvisible && is_array($thread_info)) array_unshift($thread_info, $thread);
      
    }
    
}

// Work out if any folders have no messages - if so, they still need to be displayed, so add them to $folder_order
while (list($fid, $title) = each($folder_info)) {
	if (!in_array($fid, $folder_order)) $folder_order[] = $fid;
}

// If no threads are returned, say something to that effect

if (!$thread_info) {
    echo "<tr>\n";
    echo "<td class=\"smalltext\">\n";
    echo "No messages in this category. Please select another, or <a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0\">click here</a> for all threads.\n";
    echo "</td>\n";
    echo "</tr>\n<tr>\n<td>&nbsp;</td>\n<tr>\n";
}

if ($start_from != 0 && $mode == 0 && !isset($folder)) echo "<tr><td class=\"smalltext\"><a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&start_from=".($start_from - 50)."\">Previous 50 threads</a></td></tr>\n";

// Iterate through the information we've just got and display it in the right order
while (list($key1, $folder_number) = each($folder_order)) {
	echo "<tr>\n";
	echo "<td class=\"foldername\">\n";
	echo "<img src=\"./images/folder.png\" alt=\"folder\" />\n";
	echo "<a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&folder=".$folder_number. "\">".$folder_info[$folder_number]."</a>";
	echo "</td>\n";
	echo "</tr>\n";
	if (is_array($thread_info)) {
		echo "<tr>\n";
		echo "<td class=\"threads\" style=\"border-bottom: 0;\">\n";
		echo "<a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&folder=".$folder_number."\" class=\"folderinfo\">".$folder_msgs[$folder_number]." threads</a>\n";
		echo "<a href=\"post.php?fid=".$folder_number."\" target=\"main\" class=\"folderpostnew\">Post New</a>\n";
		echo "</td></tr>\n";
		if ($start_from != 0 && isset($folder) && $folder_number == $folder) echo "<tr><td class=\"threads\" style=\"border-top: 0px; border-bottom: 0px;\"><a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&folder=$folder&start_from=".($start_from - 50)."\" class=\"folderinfo\">Previous 50 threads</a></td></tr>\n";
		echo "<tr><td class=\"threads\" style=\"border-top: 0px;\">\n";
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
		while (list($key2, $thread) = each($thread_info)) {
			if ($thread['fid'] == $folder_number) {
				echo "<tr><td valign=\"top\" align=\"center\" nowrap=\"nowrap\">";
				
                                if ($thread['last_read'] == 0) {
					$number = "[".$thread['length']."&nbsp;new]";
					$latest_post = 1;

					if(!isset($first_thread)){
						$first_thread = $thread['tid'];
						echo "<img src=\"./images/ct.png\" name=\"t".$thread['tid']."\" align=\"middle\" alt=\"ct.png\" />";
					} else {                                                                                                                                                                            
						echo "<img src=\"./images/star.png\" name=\"t".$thread['tid']."\" align=\"middle\" alt=\"star.png\"/>";
					}				

				} elseif ($thread['last_read'] < $thread['length']) {
					$new_posts = $thread['length'] - $thread['last_read'];
					$number = "[".$new_posts."&nbsp;new&nbsp;of&nbsp;".$thread['length']."]";
					$latest_post = $thread['last_read'] + 1;

					if(!isset($first_thread)){
						$first_thread = $thread['tid'];
						echo "<img src=\"./images/ct.png\" name=\"t".$thread['tid']."\" align=\"middle\" alt=\"ct.png\" />";
					} else {
						echo "<img src=\"./images/star.png\" name=\"t".$thread['tid']."\" align=\"middle\" alt=\"star.png\" />";
					}

				} else {
					$number = "[".$thread['length']."]";
					$latest_post = 1;

					if(!isset($first_thread)){
						$first_thread = $thread['tid'];
						echo "<img src=\"./images/ct.png\" name=\"t".$thread['tid']."\" align=\"middle\" alt=\"ct.png\" />";
					} else {
						echo "<img src=\"./images/bullet.png\" name=\"t".$thread['tid']."\" align=\"middle\" alt=\"bullet.png\" />";
					}
				}
				// work out how long ago the thread was posted and format the time to display
				$thread_time = format_time($thread['modified']);
				$thread_author = thread_get_author($thread['tid']);

				echo "&nbsp;</td><td valign=\"top\">";
				// With mouseover status message: echo "<a href=\"messages.php?msg=".$thread['tid'].".".$latest_post."\" target=\"right\" class=\"threadname\" onClick=\"change_current_thread('".$thread['tid']."');\" onmouseOver=\"status='#".$thread['tid']." Started by ". $thread_author ."';return true\" onmouseOut=\"window.status='';return true\" title=\"#".$thread['tid']. " Started by ". $thread_author. "\">".$thread['title']."</a>&nbsp;";
				echo "<a href=\"messages.php?msg=".$thread['tid'].".".$latest_post."\" target=\"right\" class=\"threadname\" onClick=\"change_current_thread('".$thread['tid']."');\" title=\"#".$thread['tid']. " Started by ". $thread_author. "\">".$thread['title']."</a>&nbsp;";
				if ($thread['interest'] == 1) echo "<img src=\"./images/high_interest.png\" alt=\"High Interest\" align=\"middle\"> ";
				if ($thread['interest'] == 2) echo "<img src=\"./images/subscribe.png\" alt=\"Subscribed\" align=\"middle\"> ";
				echo "<span class=\"threadxnewofy\">".$number."</span>";
				echo "</td><td valign=\"top\" nowrap=\"nowrap\">";
				echo "<span class=\"threadtime\">".$thread_time."&nbsp;</span>";
				echo "</td></tr>\n";
			}
		}
		
		if (isset($folder) && $folder_number == $folder) {
		
			$more_threads = $folder_msgs[$folder] - $start_from - 50;
			
			if ($more_threads > 0 && $more_threads <= 50) echo "<tr><td colspan=\"3\"><a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&folder=$folder&start_from=".($start_from + 50)."\" class=\"folderinfo\">Next $more_threads threads</a></td></tr>\n";
			if ($more_threads > 50) echo "<tr><td colspan=\"3\"><a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&folder=$folder&start_from=".($start_from + 50)."\" class=\"folderinfo\">Next 50 threads</a></td></tr>\n";
        
        	}		
		
		echo "</table>\n</td>\n</tr>\n";		
		
	} else {
	
		echo "<tr>\n";
		echo "<td class=\"threads\">\n";
		echo "<a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&folder=".$folder_number."\" class=\"folderinfo\">".$folder_msgs[$folder_number]." msgs</a>\n";
		echo "<a href=\"post.php?fid=".$folder_number."\" target=\"main\" class=\"folderpostnew\">Post New</a>\n";
		echo "</td></tr>\n";
		echo "<tr><td class=\"threads\" style=\"border-top: 0px; background-color: transparent;\">";
		
	}

	if (is_array($thread_info)) reset($thread_info);
}
if ($mode == 0 && !isset($folder)) {
    $total_threads = 0;
    while (list($fid, $num_threads) = each($folder_msgs)) {
        $total_threads += $num_threads;
    }
    $more_threads = $total_threads - $start_from - 50;
  if ($more_threads > 0 && $more_threads <= 50) echo "<tr><td class=\"smalltext\"><a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&start_from=".($start_from + 50)."\">Next $more_threads threads</td></tr>\n";
  if ($more_threads > 50) echo "<tr><td class=\"smalltext\"><a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mode=0&start_from=".($start_from + 50)."\">Next 50 threads</a></td></tr>\n";
}
echo "<tr>\n<td>&nbsp;</td></tr>\n<tr>\n<td class=\"smalltext\"><a href=\"".$HTTP_SERVER_VARS['PHP_SELF']."?mark_all_read=1\">Mark all as read</a></td></tr>\n";
echo "</table>\n";
echo "<script language=\"JavaScript\">\n";
echo "<!--\n";
if(isset($first_thread)){
	echo "current_thread = ".$first_thread.";\n";
} else {
	echo "current_thread = 0;\n";
}
echo "// -->";
echo "</script>\n";

html_draw_bottom();

?>