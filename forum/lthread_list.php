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

/* $Id: lthread_list.php,v 1.61 2005-02-04 00:21:53 decoyduck Exp $ */

// Light Mode Detection
define("BEEHIVEMODE_LIGHT", true);

// Compress the output
include_once("./include/gzipenc.inc.php");

// Enable the error handler
include_once("./include/errorhandler.inc.php");

// Installation checking functions
include_once("./include/install.inc.php");

// Check that Beehive is installed correctly
check_install();

// Multiple forum support
include_once("./include/forum.inc.php");

// Fetch the forum settings
$forum_settings = get_forum_settings();

include_once("./include/constants.inc.php");
include_once("./include/folder.inc.php");
include_once("./include/format.inc.php");
include_once("./include/html.inc.php");
include_once("./include/lang.inc.php");
include_once("./include/light.inc.php");
include_once("./include/messages.inc.php");
include_once("./include/session.inc.php");
include_once("./include/thread.inc.php");
include_once("./include/threads.inc.php");
include_once("./include/word_filter.inc.php");

if (!$user_sess = bh_session_check()) {
    $request_uri = rawurlencode(get_request_uri(true));
    $webtag = get_webtag($webtag_search);
    header_redirect("./llogon.php?webtag=$webtag&final_uri=$request_uri");
}

// Check we have a webtag

if (!$webtag = get_webtag($webtag_search)) {
    $request_uri = rawurlencode(get_request_uri(true));
    header_redirect("./lforums.php?final_uri=$request_uri");
}

// Load language file

$lang = load_language_file();

// Check that we have access to this forum

if (!forum_check_access_level()) {
    header_redirect("./lforums.php");
}

// Check that required variables are set

$uid = bh_session_get_value('UID');

if (isset($_GET['markread'])) {

    if ($_GET['markread'] == 2 && isset($_GET['tids']) && is_array($_GET['tids'])) {
        threads_mark_read(explode(',', $_GET['tids']));
    }elseif ($_GET['markread'] == 0) {
        threads_mark_all_read();
    }elseif ($_GET['markread'] == 1) {
        threads_mark_50_read();
    }
}

if (!isset($_GET['mode'])) {
    if (!isset($_COOKIE['bh_thread_mode'])) {
        if (threads_any_unread()) { // default to "Unread" messages for a logged-in user, unless there aren't any
            $mode = 1;
        }else {
            $mode = 0;
        }
    }else {
        $mode = (is_numeric($_COOKIE['bh_thread_mode'])) ? $_COOKIE['bh_thread_mode'] : 0;
    }
}else {
    $mode = (is_numeric($_GET['mode'])) ? $_GET['mode'] : 0;
}

if (isset($_GET['folder']) && is_numeric($_GET['folder'])) {
    $folder = $_GET['folder'];
    $mode = 0;
}

bh_setcookie('bh_thread_mode', $mode);

if (isset($_GET['start_from']) && is_numeric($_GET['start_form'])) {
    $start_from = $_GET['start_from'];
}else {
    $start_from = 0;
}

// Output XHTML header
light_html_draw_top();

echo "<form name=\"f_mode\" method=\"get\" action=\"lthread_list.php\">\n";
echo "  ", form_input_hidden("webtag", $webtag), "\n";
echo "  ", light_threads_draw_discussions_dropdown($mode), "\n";
echo "  ", light_form_submit("go",$lang['goexcmark']), "\n";
echo "</form>\n";

// The tricky bit - displaying the right threads for whatever mode is selected

if (isset($folder)) {
    list($thread_info, $folder_order) = threads_get_folder($uid, $folder, $start_from);
} else {
    switch ($mode) {
        case 0: // All discussions
            list($thread_info, $folder_order) = threads_get_all($uid, $start_from);
            break;
        case 1; // Unread discussions
            list($thread_info, $folder_order) = threads_get_unread($uid);
            break;
        case 2; // Unread discussions To: Me
            list($thread_info, $folder_order) = threads_get_unread_to_me($uid);
            break;
        case 3; // Today's discussions
            list($thread_info, $folder_order) = threads_get_by_days($uid, 1);
            break;
        case 4: // Unread today
            list($thread_info, $folder_order) = threads_get_unread_by_days($uid);
            break;
        case 5; // 2 days back
            list($thread_info, $folder_order) = threads_get_by_days($uid, 2);
            break;
        case 6; // 7 days back
            list($thread_info, $folder_order) = threads_get_by_days($uid, 7);
            break;
        case 7; // High interest
            list($thread_info, $folder_order) = threads_get_by_interest($uid, 1);
            break;
        case 8; // Unread high interest
            list($thread_info, $folder_order) = threads_get_unread_by_interest($uid, 1);
            break;
        case 9; // Recently seen
            list($thread_info, $folder_order) = threads_get_recently_viewed($uid);
            break;
        case 10; // Ignored
            list($thread_info, $folder_order) = threads_get_by_interest($uid, -1);
            break;
        case 11; // By Ignored Users
            list($thread_info, $folder_order) = threads_get_by_relationship($uid, USER_IGNORED_COMPLETELY);
            break;
        case 12; // Subscribed to
            list($thread_info, $folder_order) = threads_get_by_interest($uid, 2);
            break;
        case 13: // Started by friend
            list($thread_info, $folder_order) = threads_get_by_relationship($uid, USER_FRIEND);
            break;
        case 14: // Unread started by friend
            list($thread_info, $folder_order) = threads_get_unread_by_relationship($uid, USER_FRIEND);
            break;
        case 15: // Started by me
            list($thread_info, $folder_order) = threads_get_started_by_me($uid);
            break;
        case 16: // Polls
            list($thread_info, $folder_order) = threads_get_polls($uid);
            break;
        case 17: // Sticky threads
            list($thread_info, $folder_order) = threads_get_sticky($uid);
            break;
        case 18: // Most unread posts
            list($thread_info, $folder_order) = threads_get_longest_unread($uid);
            break;
        default: // Default to all threads
            list($thread_info, $folder_order) = threads_get_all($uid, $start_from);
            break;
    }
}

// Now, the actual bit that displays the threads...

// Get folder FIDs and titles
$folder_info = threads_get_folders();
if (!$folder_info) die ("<p>{$lang['couldnotretrievefolderinformation']}</p>");

// Get total number of messages for each folder
$folder_msgs = threads_get_folder_msgs();

// Check to see if $folder_order is an array, and define it as one if not
if (!is_array($folder_order)) $folder_order = array();

// Sort the folders and threads correctly as per the URL query for the TID

if (isset($_GET['msg']) && validate_msg($_GET['msg'])) {

    $threadvisible = false;

    list($tid, $pid) = explode('.', $_GET['msg']);

    if (thread_can_view($tid, bh_session_get_value('UID'))) {

        if ($thread = thread_get($tid)) {

            foreach ($thread as $key => $value) {
                $thread[strtolower($key)] = $value;
                unset($thread[$key]);
            }

            if (!isset($thread['relationship'])) $thread['relationship'] = 0;

            if ($thread['tid'] == $tid) {

                if (in_array($thread['fid'], $folder_order)) {
                    array_splice($folder_order, array_search($thread['fid'], $folder_order), 1);
                }

                array_unshift($folder_order, $thread['fid']);

                if (!is_array($thread_info)) $thread_info = array();

                foreach ($thread_info as $key => $thread_data) {
                    if ($thread_data['tid'] == $tid) {
                        unset($thread_info[$key]);
                        break;
                    }
                }

                array_unshift($thread_info, $thread);
            }
        }
    }
}

// Work out if any folders have no messages and add them.
// Seperate them by INTEREST level

if (bh_session_get_value('UID') > 0) {

    if (isset($_GET['msg']) && validate_msg($_GET['msg'])) {

        list($tid, $pid) = explode('.', $_GET['msg']);

        if (thread_can_view($tid, bh_session_get_value('UID'))) {

            list(,$selectedfolder) = thread_get($tid);
        }

    }elseif (isset($_GET['folder'])) {

        $selectedfolder = $_GET['folder'];

    }else {

        $selectedfolder = 0;
    }

    $ignored_folders = array();

    while (list($fid, $folder_data) = each($folder_info)) {
        if ($folder_data['INTEREST'] == 0 || (isset($selectedfolder) && $selectedfolder == $fid)) {
            if ((!in_array($fid, $folder_order)) && (!in_array($fid, $ignored_folders))) $folder_order[] = $fid;
        }else {
            if ((!in_array($fid, $folder_order)) && (!in_array($fid, $ignored_folders))) $ignored_folders[] = $fid;
        }
    }

    // Append ignored folders onto the end of the folder list.
    // This will make them appear at the bottom of the thread list.

    $folder_order = array_merge($folder_order, $ignored_folders);

}else {

    while (list($fid, $folder_data) = each($folder_info)) {
        if (!in_array($fid, $folder_order)) $folder_order[] = $fid;
    }
}

// If no threads are returned, say something to that effect

if (!$thread_info) {

    echo "<p>{$lang['nomessagesinthiscategory']} <a href=\"lthread_list.php?webtag=$webtag&amp;mode=0\">{$lang['clickhere']}</a> {$lang['forallthreads']}.</p>\n";
}

if ($start_from != 0 && $mode == 0 && !isset($folder)) echo "<p><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;start_from=".($start_from - 50)."\">{$lang['prev50threads']}</a></p>\n";

// Iterate through the information we've just got and display it in the right order

foreach ($folder_order as $key1 => $folder_number) {

    if (isset($folder_info[$folder_number]) && is_array($folder_info[$folder_number])) {

        echo "<h3><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;folder=".$folder_number. "\">". apply_wordfilter($folder_info[$folder_number]['TITLE']) . "</a></h3>";

        if ((!$folder_info[$folder_number]['INTEREST']) || ($mode == 2) || (isset($selectedfolder) && $selectedfolder == $folder_number)) {

            if (is_array($thread_info)) {

                echo "<p>";

                if (isset($folder_msgs[$folder_number])) {
                    echo $folder_msgs[$folder_number];
                }else {
                    echo "0";
                }

                echo " {$lang['threads']}";

                if (is_null($folder_info[$folder_number]['STATUS']) || $folder_info[$folder_number]['STATUS'] & USER_PERM_THREAD_CREATE) {

                    if ($folder_info[$folder_number]['ALLOWED_TYPES'] & FOLDER_ALLOW_NORMAL_THREAD) echo " - <b><a href=\"lpost.php?webtag=$webtag&amp;fid=".$folder_number."\">{$lang['postnew']}</a></b>";
                }

                echo "</p>\n";

                if ($start_from != 0 && isset($folder) && $folder_number == $folder) echo "<p><i><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;folder=$folder&amp;start_from=".($start_from - 50)."\">{$lang['prev50threads']}</a></i></p>\n";

                echo "<ul>\n";

                foreach($thread_info as $key2 => $thread) {

                    if (!isset($visiblethreads) || !is_array($visiblethreads)) $visiblethreads = array();
                    if (!in_array($thread['tid'], $visiblethreads)) $visiblethreads[] = $thread['tid'];

                    if ($thread['fid'] == $folder_number) {

                        echo "<li>\n";

                        if ($thread['last_read'] == 0) {

                            $number = "[".$thread['length']."&nbsp;new]";
                            $latest_post = 1;

                        }elseif ($thread['last_read'] < $thread['length']) {

                            $new_posts = $thread['length'] - $thread['last_read'];
                            $number = "[".$new_posts."&nbsp;new&nbsp;of&nbsp;".$thread['length']."]";
                            $latest_post = $thread['last_read'] + 1;

                        } else {

                            $number = "[".$thread['length']."]";
                            $latest_post = 1;

                        }

                        // work out how long ago the thread was posted and format the time to display
                        $thread_time = format_time($thread['modified']);

                        echo "<a href=\"lmessages.php?webtag=$webtag&amp;msg=".$thread['tid'].".".$latest_post."\" title=\"#".$thread['tid']. " {$lang['startedby']} ". format_user_name($thread['logon'], $thread['nickname']) . "\">".apply_wordfilter($thread['title'])."</a> ";
                        if ($thread['interest'] == 1) echo "<font color=\"#FF0000\">(HI)</font> ";
                        if ($thread['interest'] == 2) echo "<font color=\"#FF0000\">(Sub)</font> ";
                        if ($thread['poll_flag'] == 'Y') echo "(P) ";
                        if ($thread['sticky'] == 'Y') echo "(St) ";
                        if ($thread['relationship']&USER_FRIEND) echo "(Fr) ";
                        echo $number." ";
                        echo $thread_time." ";
                        echo "</li>\n";
                    }
                }

                echo "</ul>\n";

                if (isset($folder) && $folder_number == $folder) {

                    $more_threads = $folder_msgs[$folder] - $start_from - 50;

                    if ($more_threads > 0 && $more_threads <= 50) echo "<p><i><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;folder=$folder&amp;start_from=".($start_from + 50)."\">{$lang['next']} $more_threads {$lang['threads']}</a></i></p>\n";
                    if ($more_threads > 50) echo "<p><i><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;folder=$folder&amp;start_from=".($start_from + 50)."\">{$lang['next50threads']}</a></i></p>\n";

                }

            }elseif ($folder_info[$folder_number]['INTEREST'] != -1) {

                echo "<p><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;folder=".$folder_number."\">";

                if (isset($folder_msgs[$folder_number])) {
                    echo $folder_msgs[$folder_number];
                }else {
                    echo "0";
                }

                echo " {$lang['threads']}</a>";
                if ($folder_info[$folder_number]['ALLOWED_TYPES']&FOLDER_ALLOW_NORMAL_THREAD) echo " - <b><a href=\"lpost.php?webtag=$webtag&amp;fid=".$folder_number."\">{$lang['postnew']}</a></b>";
                echo "</p>\n";
            }

        }

        if (is_array($thread_info)) reset($thread_info);
    }
}

if ($mode == 0 && !isset($folder)) {

    $total_threads = 0;

    if (is_array($folder_msgs)) {

      while (list($fid, $num_threads) = each($folder_msgs)) {
        $total_threads += $num_threads;
      }

      $more_threads = $total_threads - $start_from - 50;
      if ($more_threads > 0 && $more_threads <= 50) echo "<p><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;start_from=".($start_from + 50)."\">{$lang['next']} $more_threads {$lang['threads']}</p>\n";
      if ($more_threads > 50) echo "<p><a href=\"lthread_list.php?webtag=$webtag&amp;mode=0&amp;start_from=".($start_from + 50)."\">{$lang['next50threads']}</a></p>\n";

    }
}

if ($uid != 0) {

    echo "  <h5>{$lang['markasread']}:</h5>\n";
    echo "    <form name=\"f_mark\" method=\"get\" action=\"lthread_list.php\">\n";
    echo "      ", form_input_hidden("webtag", $webtag), "\n";

    $labels = array($lang['alldiscussions'], $lang['next50discussions']);

    if (isset($visiblethreads) && is_array($visiblethreads)) {

        $labels[] = $lang['visiblediscussions'];
        echo form_input_hidden("tids", implode(',', $visiblethreads));
    }

    echo light_form_dropdown_array("markread", range(0, sizeof($labels) -1), $labels, 0). "\n        ";
    echo light_form_submit("go",$lang['goexcmark']). "\n";
    echo "    </form>\n";

}

echo "<h4><a href=\"lforums.php?webtag=$webtag\">My Forums</a> | <a href=\"llogout.php?webtag=$webtag\">{$lang['logout']}</a></h4>\n";
light_html_draw_bottom();

?>