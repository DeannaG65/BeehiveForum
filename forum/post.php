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

/* $Id: post.php,v 1.177 2004-04-13 09:31:55 tribalonline Exp $ */

// Compress the output
include_once("./include/gzipenc.inc.php");

// Enable the error handler
include_once("./include/errorhandler.inc.php");

// Multiple forum support
include_once("./include/forum.inc.php");

// Fetch the forum settings
$forum_settings = get_forum_settings();

include_once("./include/config.inc.php");
include_once("./include/constants.inc.php");
include_once("./include/db.inc.php");
include_once("./include/email.inc.php");
include_once("./include/fixhtml.inc.php");
include_once("./include/folder.inc.php");
include_once("./include/form.inc.php");
include_once("./include/format.inc.php");
include_once("./include/header.inc.php");
include_once("./include/html.inc.php");
include_once("./include/htmltools.inc.php");
include_once("./include/lang.inc.php");
include_once("./include/logon.inc.php");
include_once("./include/messages.inc.php");
include_once("./include/poll.inc.php");
include_once("./include/post.inc.php");
include_once("./include/session.inc.php");
include_once("./include/thread.inc.php");
include_once("./include/user.inc.php");

if (!$user_sess = bh_session_check()) {

    if (isset($HTTP_SERVER_VARS["REQUEST_METHOD"]) && $HTTP_SERVER_VARS["REQUEST_METHOD"] == "POST") {
        
        if (perform_logon(false)) {
	    
	    html_draw_top();

            echo "<h1>{$lang['loggedinsuccessfully']}</h1>";
            echo "<div align=\"center\">\n";
	    echo "<p><b>{$lang['presscontinuetoresend']}</b></p>\n";

            $request_uri = get_request_uri();

            echo "<form method=\"post\" action=\"$request_uri\" target=\"_self\">\n";

            foreach($HTTP_POST_VARS as $key => $value) {
	        form_input_hidden($key, _htmlentities(_stripslashes($value)));
            }

	    echo form_submit(md5(uniqid(rand())), $lang['continue']), "&nbsp;";
            echo form_button(md5(uniqid(rand())), $lang['cancel'], "onclick=\"self.location.href='$request_uri'\""), "\n";
	    echo "</form>\n";
	    
	    html_draw_bottom();
	    exit;
	}

    }else {
        html_draw_top();
        draw_logon_form(false);
	html_draw_bottom();
	exit;
    }
}

// Check we have a webtag

if (!$webtag = get_webtag()) {
    $request_uri = rawurlencode(get_request_uri());
    header_redirect("./forums.php?final_uri=$request_uri");
}

// Load the wordfilter for the current user

$user_wordfilter = load_wordfilter();

if (bh_session_get_value('UID') == 0) {
    html_guest_error();
    exit;
}

// Check that there are some available folders for this thread type
if (!folder_get_by_type_allowed(FOLDER_ALLOW_NORMAL_THREAD)) {
    html_message_type_error();
    exit;
}

if (isset($HTTP_POST_VARS['cancel'])) {

    $uri = "./discussion.php?webtag=$webtag";

    if (isset($HTTP_POST_VARS['t_tid']) && is_numeric($HTTP_POST_VARS['t_tid']) && isset($HTTP_POST_VARS['t_rpid']) && is_numeric($HTTP_POST_VARS['t_rpid']) ) {
        $uri.= "&msg={$HTTP_POST_VARS['t_tid']}.{$HTTP_POST_VARS['t_rpid']}";
    }elseif (isset($HTTP_GET_VARS['replyto']) && validate_msg($HTTP_POST_VARS['replyto'])) {
        $uri.= "&msg={$HTTP_GET_VARS['replyto']}";
    }

    header_redirect($uri);
}

// Check if the user is viewing signatures.
$show_sigs = !(bh_session_get_value('VIEW_SIGS'));

$valid = true;

$newthread = false;

if (isset($HTTP_POST_VARS['t_post_html'])) {
    $t_post_html = $HTTP_POST_VARS['t_post_html'];
    if ($t_post_html == "enabled_auto") {
        $t_post_html = true;
        $auto_linebreaks = true;
    } else if ($t_post_html == "enabled") {
        $t_post_html = true;
        $auto_linebreaks = false;
    } else {
        $t_post_html = false;
    }
}

$t_to_uid_others = "";

if (isset($HTTP_POST_VARS['to_radio'])) {
    $to_radio = $HTTP_POST_VARS['to_radio'];

    if ($to_radio == "others") {

        $t_to_uid_others = $HTTP_POST_VARS['t_to_uid_others'];

        if ($to_user = user_get_uid($t_to_uid_others)) {

            $HTTP_POST_VARS['t_to_uid'] = $to_user['UID'];
            $t_to_uid = $to_user['UID'];

        }else{

            $error_html = "<h2>{$lang['invalidusername']}</h2>";
            $valid = false;
        }
    } else if ($to_radio == "in_thread") {
        $t_to_uid = $HTTP_POST_VARS['t_to_uid_in_thread'];
        $HTTP_POST_VARS['t_to_uid'] = $t_to_uid;
    } else {
        $t_to_uid = $HTTP_POST_VARS['t_to_uid_recent'];
        $HTTP_POST_VARS['t_to_uid'] = $t_to_uid;
    }
}

if (isset($HTTP_POST_VARS['t_newthread'])) {

    $newthread = true;

    if (isset($HTTP_POST_VARS['t_threadtitle']) && trim($HTTP_POST_VARS['t_threadtitle']) != "") {
        $t_threadtitle = trim($HTTP_POST_VARS['t_threadtitle']);
    }else{
        $error_html = "<h2>{$lang['mustenterthreadtitle']}</h2>";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_fid'])) {
        if (folder_thread_type_allowed($HTTP_POST_VARS['t_fid'], FOLDER_ALLOW_NORMAL_THREAD)) {
            $t_fid = $HTTP_POST_VARS['t_fid'];
        } else {
            $error_html = "<h2>{$lang['cannotpostthisthreadtypeinfolder']}</h2>";
            $valid = false;
        }
    } else if ($valid) {
        $error_html = "<h2>{$lang['pleaseselectfolder']}</h2>";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_content']) && strlen($HTTP_POST_VARS['t_content']) > 0) {

        $t_content = $HTTP_POST_VARS['t_content'];

        if (attachment_embed_check($t_content) && isset($t_post_html) && $t_post_html) {
            $error_html = "<h2>{$lang['notallowedembedattachmentpost']}</h2>\n";
            $valid = false;
        }

    }else{
        $t_content = "";
        $error_html = "<h2>{$lang['mustenterpostcontent']}</h2>";
        $valid = false;
    }

    $t_sig = (isset($HTTP_POST_VARS['t_sig'])) ? $HTTP_POST_VARS['t_sig'] : "";
    $t_sig_html = (isset($HTTP_POST_VARS['t_sig_html'])) ? $HTTP_POST_VARS['t_sig_html'] : "N";

    if (attachment_embed_check($t_sig) && isset($t_sig_html) && $t_sig_html != "N") {
        $error_html = "<h2>{$lang['notallowedembedattachmentsignature']}</h2>\n";
        $valid = false;
    }

}else{

    if (isset($HTTP_POST_VARS['t_tid'])) {

        if (isset($HTTP_POST_VARS['t_content']) && strlen($HTTP_POST_VARS['t_content']) > 0) {

            $t_content = $HTTP_POST_VARS['t_content'];

            if (attachment_embed_check($t_content) && isset($t_post_html) && $t_post_html) {
                $error_html = "<h2>You are not allowed to embed attachments in your posts.</h2>\n";
                $valid = false;
            }

        }else{
            $error_html = "<h2>{$lang['mustenterpostcontent']}</h2>";
            $valid = false;
        }

        $t_sig = (isset($HTTP_POST_VARS['t_sig'])) ? $HTTP_POST_VARS['t_sig'] : "";
        $t_sig_html = (isset($HTTP_POST_VARS['t_sig_html'])) ? $HTTP_POST_VARS['t_sig_html'] : "N";

        if (attachment_embed_check($t_sig) && isset($t_sig_html) && $t_sig_html != "N") {
            $error_html = "<h2>You are not allowed to embed attachments in your signature.</h2>\n";
            $valid = false;
        }

    }else{

        $valid = false;

    }
}

$content_html_changes = false;
$sig_html_changes = false;

if ($valid) {

    if (isset($t_post_html) && $t_post_html == "Y") {
        $old_t_content = _stripslashes($t_content);
        $t_content = fix_html($t_content);

        if ($old_t_content != tidy_html($t_content)) {
            $content_html_changes = true;
        }

        if ($auto_linebreaks == true) {
            $t_content = add_paragraphs($t_content);
        }
    }

    if (isset($t_sig) && $t_sig_html == "Y") {
        $old_t_sig = _stripslashes($t_sig);
        $t_sig = fix_html($t_sig);

        if ($old_t_sig != $t_sig) {
            $sig_html_changes = true;
        }
    }

}else {

    if (isset($t_post_html) && $t_post_html == "Y") {
        $t_content = isset($t_content) ? _stripslashes($t_content) : "";
    }

    if (isset($t_sig)) {
        if ($t_sig_html == "Y") {
            $t_sig = _stripslashes($t_sig);
        }
    }
}

if (isset($HTTP_GET_VARS['replyto']) && validate_msg($HTTP_GET_VARS['replyto'])) {

    $replyto = $HTTP_GET_VARS['replyto'];
    list($reply_to_tid, $reply_to_pid) = explode(".", $replyto);
    $newthread = false;

}elseif (isset($HTTP_POST_VARS['t_tid'])) {

    $reply_to_tid = $HTTP_POST_VARS['t_tid'];
    $reply_to_pid = $HTTP_POST_VARS['t_rpid'];
    $newthread = false;

}else{

    $newthread = true;

    if (isset($HTTP_GET_VARS['fid']) && is_numeric($HTTP_GET_VARS['fid'])) {

        $t_fid = $HTTP_GET_VARS['fid'];

    }elseif (isset($HTTP_POST_VARS['t_fid']) && is_numeric($HTTP_POST_VARS['t_fid'])) {

        $t_fid = $HTTP_POST_VARS['t_fid'];
    }

}

if (!$newthread) {

    $reply_message = messages_get($reply_to_tid, $reply_to_pid);
    $reply_message['CONTENT'] = message_get_content($reply_to_tid, $reply_to_pid);
    $threaddata = thread_get($reply_to_tid);

    if (((user_get_status($reply_message['FROM_UID']) & USER_PERM_WORM) && !perm_is_moderator()) || ((!isset($reply_message['CONTENT']) || $reply_message['CONTENT'] == "") && $threaddata['POLL_FLAG'] != 'Y')) {

        html_draw_top();

        echo "<h1 style=\"width: 99%\">".$lang['postmessage']."</h1>\n";
        echo "<form name=\"f_post\" action=\"" . get_request_uri() . "\" method=\"post\" target=\"_self\">\n";

        echo "<table class=\"posthead\" width=\"720\">\n";
        echo "<tr><td class=\"subhead\">".$lang['error']."</td></tr>\n";
        echo "<tr><td>\n";

        echo "<h2>".$lang['messagehasbeendeleted']."</h2>\n";
        echo "</td></tr>\n";

        echo "<tr><td align=\"center\">\n";
        echo form_submit('cancel', $lang['cancel']);
        echo "</td></tr>\n";
        echo "</table></form>\n";

        html_draw_bottom();
        exit;

    }
}

if ($valid && isset($HTTP_POST_VARS['submit'])) {

    if (check_ddkey($HTTP_POST_VARS['t_dedupe'])) {

        if ($newthread) {
        
            $folderdata = folder_get($t_fid);
            
            if ($folderdata['ACCESS_LEVEL'] == 2 && !folder_is_accessible($t_fid) && !perm_is_moderator()) {
        
                html_draw_top();
                
                echo "<form name=\"f_post\" action=\"" . get_request_uri() . "\" method=\"post\" target=\"_self\">\n";
                echo "<table class=\"posthead\" width=\"720\">\n";
                echo "<tr><td class=\"subhead\">".$lang['threadclosed']."</td></tr>\n";
                echo "<tr><td>\n";
                echo "<h2>".$lang['threadisclosedforposting']."</h2>\n";
                echo "</td></tr>\n";
 
                echo "<tr><td align=\"center\">\n";
                echo form_submit('cancel', $lang['cancel']);
                echo "</td></tr>\n";
                echo "</table></form>\n";
 
                html_draw_bottom();
                exit;
            }        

            if (isset($HTTP_POST_VARS['t_closed'])) $t_closed = $HTTP_POST_VARS['t_closed'];
            if (isset($HTTP_POST_VARS['old_t_closed'])) $old_t_closed = $HTTP_POST_VARS['old_t_closed'];
            if (isset($HTTP_POST_VARS['t_sticky'])) $t_sticky = $HTTP_POST_VARS['t_sticky'];
            if (isset($HTTP_POST_VARS['old_t_sticky'])) $old_t_sticky = $HTTP_POST_VARS['old_t_sticky'];

            if (bh_session_get_value("STATUS") & PERM_CHECK_WORKER) {
                $t_closed = isset($t_closed) && $t_closed == "Y" ? true : false;
                $t_sticky = isset($t_sticky) && $t_sticky == "Y" ? "Y" : "N";
            } else {
                $t_closed = false;
                $t_sticky = "N";
            }

            $t_tid = post_create_thread($t_fid, ($t_threadtitle), "N", $t_sticky, $t_closed);
            $t_rpid = 0;

        }else{

            $t_tid = $HTTP_POST_VARS['t_tid'];
            $t_rpid = $HTTP_POST_VARS['t_rpid'];
            
            if (isset($threaddata['CLOSED']) && $threaddata['CLOSED'] > 0 && (!(bh_session_get_value('STATUS') & PERM_CHECK_WORKER))) {

                html_draw_top();
                
                echo "<form name=\"f_post\" action=\"" . get_request_uri() . "\" method=\"post\" target=\"_self\">\n";
                echo "<table class=\"posthead\" width=\"720\">\n";
                echo "<tr><td class=\"subhead\">".$lang['threadclosed']."</td></tr>\n";
                echo "<tr><td>\n";
                echo "<h2>".$lang['threadisclosedforposting']."</h2>\n";
                echo "</td></tr>\n";

                echo "<tr><td align=\"center\">\n";
		echo form_input_hidden('t_tid', $t_tid);
		echo form_input_hidden('t_rpid', $t_rpid);
                echo form_submit('cancel', $lang['cancel']);
                echo "</td></tr>\n";
                echo "</table></form>\n";

                html_draw_bottom();
                exit;
            }

            if (bh_session_get_value("STATUS") & PERM_CHECK_WORKER) {

                if (isset($HTTP_POST_VARS['t_closed'])) $t_closed = $HTTP_POST_VARS['t_closed'];
                if (isset($HTTP_POST_VARS['old_t_closed'])) $old_t_closed = $HTTP_POST_VARS['old_t_closed'];
                if (isset($HTTP_POST_VARS['t_sticky'])) $t_sticky = $HTTP_POST_VARS['t_sticky'];
                if (isset($HTTP_POST_VARS['old_t_sticky'])) $old_t_sticky = $HTTP_POST_VARS['old_t_sticky'];

                if (isset($t_closed) && isset($old_t_closed) && $t_closed != $old_t_closed && $t_closed == "Y") {
                    thread_set_closed($t_tid, true);
                } elseif ((!isset($t_closed) || (isset($t_closed) && $t_closed != "Y")) && $old_t_closed == "Y") {
                    thread_set_closed($t_tid, false);
                }
                if (isset($t_sticky) && isset($old_t_sticky) && $t_sticky != $old_t_sticky && $t_sticky == "Y") {
                    thread_set_sticky($t_tid, true);
                } elseif ((!isset($t_sticky) || (isset($t_sticky) && $t_sticky != "Y")) && $old_t_sticky == "Y") {
                    thread_set_sticky($t_tid, false);
                }
            }
        }

        if ($t_tid > 0) {

            if (!isset($t_post_html) || (isset($t_post_html) && $t_post_html != "Y")) {
                $t_content = make_html($t_content);
            }

            if (isset($t_sig) && trim($t_sig) != "") {

                if ($t_sig_html != "Y") $t_sig = make_html($t_sig);
                $t_content.= "\n<div class=\"sig\">". $t_sig. "</div>";

            }

            $new_pid = post_create($t_tid, $t_rpid, bh_session_get_value('UID'), $HTTP_POST_VARS['t_to_uid'], $t_content);

            if (bh_session_get_value('MARK_AS_OF_INT')) thread_set_interest($t_tid, 1, $newthread);

            if (!(user_get_status(bh_session_get_value('UID')) & USER_PERM_WORM)) {
                email_sendnotification($HTTP_POST_VARS['t_to_uid'], "$t_tid.$new_pid", bh_session_get_value('UID'));
                email_sendsubscription($HTTP_POST_VARS['t_to_uid'], "$t_tid.$new_pid", bh_session_get_value('UID'));
            }
            
            if (isset($HTTP_POST_VARS['aid']) && forum_get_setting('attachments_enabled', 'Y', false)) {
                if (get_num_attachments($HTTP_POST_VARS['aid']) > 0) post_save_attachment_id($t_tid, $new_pid, $HTTP_POST_VARS['aid']);
            }             
        }      

    }else {

        $new_pid = 0;
        
        if ($newthread) {

            $t_tid  = 0;
            $t_rpid = 0;

        }else {

            $t_tid  = (isset($HTTP_POST_VARS['t_tid'])) ? $HTTP_POST_VARS['t_tid'] : 0;
            $t_rpid = (isset($HTTP_POST_VARS['t_rpid'])) ? $HTTP_POST_VARS['t_rpid'] : 0;
        }
    }

    if ($new_pid > -1) {

        if ($t_tid > 0 && $t_rpid > 0) {

          $uri = "./discussion.php?webtag=$webtag&msg=$t_tid.$t_rpid";

        }else{

          $uri = "./discussion.php?webtag=$webtag";

        }

        header_redirect($uri);
        exit;

    }else{

        $error_html = "<h2>{$lang['errorcreatingpost']}</h2>";

    }

}

html_draw_top("onUnload=clearFocus()", "basetarget=_blank", "post.js", "openprofile.js", "htmltools.js", "emoticons.js");

if (!isset($HTTP_POST_VARS['aid'])) {
    $aid = md5(uniqid(rand()));
}else{
    $aid = $HTTP_POST_VARS['aid'];
}

echo "<h1 style=\"width: 99%\">".$lang['postmessage']."</h1>\n";
echo "<br /><form name=\"f_post\" action=\"post.php?webtag=$webtag\" method=\"post\" target=\"_self\">\n";

if (!$newthread) {

    if (isset($threaddata['CLOSED']) && $threaddata['CLOSED'] > 0) {

        echo "<table class=\"posthead\" width=\"720\">\n";
        echo "<tr><td class=\"subhead\">".$lang['threadclosed']."</td></tr>\n";
        echo "<tr><td>\n";

        if (bh_session_get_value('STATUS') & PERM_CHECK_WORKER) {
            echo "<h2>".$lang['moderatorthreadclosed']."</h2>\n";
            echo "</td></tr>\n";

        } else {
            echo "<h2>".$lang['threadisclosedforposting']."</h2>\n";
            echo "</td></tr>\n";

            echo "<tr><td align=\"center\">\n";
            echo form_submit('cancel', $lang['cancel']);
            echo "</td></tr>\n";
            echo "</table></form>\n";

            html_draw_bottom();
            exit;
        }
    }
}

if ($valid && isset($HTTP_POST_VARS['preview'])) {

    echo "<table class=\"posthead\" width=\"720\">\n";
    echo "<tr><td class=\"subhead\">{$lang['messagepreview']}</td></tr>";

    if ($HTTP_POST_VARS['t_to_uid'] == 0) {

        $preview_message['TLOGON'] = "ALL";
        $preview_message['TNICK'] = "ALL";

    }else{

        $preview_tuser = user_get($HTTP_POST_VARS['t_to_uid']);
        $preview_message['TLOGON'] = $preview_tuser['LOGON'];
        $preview_message['TNICK'] = $preview_tuser['NICKNAME'];
        $preview_message['TO_UID'] = $preview_tuser['UID'];

    }

    $preview_tuser = user_get(bh_session_get_value('UID'));
    $preview_message['FLOGON'] = $preview_tuser['LOGON'];
    $preview_message['FNICK'] = $preview_tuser['NICKNAME'];
    $preview_message['FROM_UID'] = $preview_tuser['UID'];

    if (!isset($t_post_html) || (isset($t_post_html) && $t_post_html != "Y")) {

        $preview_message['CONTENT'] = make_html($t_content);

    }else{

        $preview_message['CONTENT'] = $t_content;

    }

    if (isset($t_sig) && trim($t_sig) != "") {

        if ($t_sig_html != "Y") {

            $preview_sig = make_html($t_sig);

        }else{

            $preview_sig = $t_sig;

        }

        $preview_message['CONTENT'] = $preview_message['CONTENT']. "<div class=\"sig\">". $preview_sig. "</div>";

    }else{

        $t_sig = " ";

    }

    $preview_message['CREATED'] = mktime();
    $preview_message['AID'] = $aid;

    echo "<tr><td>\n";
    message_display(0, $preview_message, 0, 0, true, false, false, false, $show_sigs, true);
    echo "</td></tr>\n";

    echo "<tr><td>&nbsp;</td></tr>\n";
    echo "</table>\n";
}

if ($valid && isset($HTTP_POST_VARS['convert_html'])) {

    $t_content = nl2br(_htmlentities(_stripslashes($t_content)));
    $t_post_html = "Y";
}

if (!$newthread) {

    if (!isset($HTTP_POST_VARS['t_to_uid'])) {
        $t_to_uid = message_get_user($reply_to_tid, $reply_to_pid);
    }else {
        $t_to_uid = $HTTP_POST_VARS['t_to_uid'];
    }

}

if (!isset($t_sig)) {
    $has_sig = user_get_sig(bh_session_get_value('UID'), $t_sig, $t_sig_html);
}else{
    $has_sig = true;
}

if (isset($error_html)) {
    echo "<table class=\"posthead\" width=\"720\">\n";
    echo "<tr><td class=\"subhead\">{$lang['error']}</td></tr>";
    echo "<tr><td>\n";
    echo $error_html . "\n";
    echo "</td></tr>\n";
    echo "</table>\n";
}

if (!isset($t_threadtitle)) {
    $t_threadtitle = "";
}

if (!isset($t_fid)) {
    $t_fid = 0;
}

echo "<table class=\"posthead\" width=\"720\">\n";
echo "<tr><td class=\"subhead\" colspan=\"2\">";

if ($newthread) {
    echo $lang['createnewthread'];
}else{
    echo $lang['postreply'];
}

echo "</td></tr>\n";
echo "<tr>\n";


// ======================================
// =========== OPTIONS COLUMN ===========
echo "<td valign=\"top\" width=\"210\">\n";
echo "<table class=\"posthead\" width=\"210\">\n";
echo "<tr><td>\n";

if ($newthread) {

    echo "<h2>".$lang['folder'].":</h2>\n";
    echo folder_draw_dropdown($t_fid, "t_fid", "", FOLDER_ALLOW_NORMAL_THREAD, "style=\"width: 190px\"")."\n";
    echo "<h2>".$lang['threadtitle'].":</h2>\n";
    echo form_input_text("t_threadtitle", _htmlentities(_stripslashes($t_threadtitle)), 0, 0, "style=\"width: 190px\"")."\n";

    echo form_input_hidden("t_newthread", "Y")."\n";
    echo "<br />\n";

}else {

    echo "<h2>".$lang['folder'].":</h2>\n";
    echo _stripslashes($threaddata['FOLDER_TITLE'])."\n";
    echo "<h2>".$lang['threadtitle'].":</h2>\n";
    echo _stripslashes($threaddata['TITLE'])."\n";

    echo form_input_hidden("t_tid", $reply_to_tid);
    echo form_input_hidden("t_rpid", $reply_to_pid)."\n";
    echo "<br /><br />\n";
}

echo "<h2>".$lang['to'].":</h2>\n";

if (!$newthread) {
    echo form_radio("to_radio", "in_thread", $lang['usersinthread'], true)."<br />\n";
    echo post_draw_to_dropdown_in_thread($reply_to_tid, $t_to_uid, true, false, 'onClick="checkToRadio(0)"')."<br />\n";
}

echo form_radio("to_radio", "recent", $lang['recentvisitors'], $newthread ? true : false)."<br />\n";
echo post_draw_to_dropdown_recent($newthread && isset($t_to_uid) ? $t_to_uid : ($newthread ? -1 : 0))."<br />\n";

echo form_radio("to_radio", "others", $lang['others'])."<br />\n";
echo form_input_text("t_to_uid_others", "", 0, 0, "style=\"width: 190px\" onClick=\"checkToRadio(".($newthread ? 1 : 2).")\"")."<br /><br />\n";

$emot_user = bh_session_get_value('EMOTICONS');
$emot_prev = emoticons_preview($emot_user);
if ($emot_prev != "") {
	echo "<h2>".$lang['emoticons'].":</h2>\n";
	echo $emot_prev."<br />\n";
}

if (bh_session_get_value("STATUS") & PERM_CHECK_WORKER) {

    echo "<h2>".$lang['admin'].":</h2>\n";

    echo form_checkbox("t_closed", "Y", $lang['closeforposting'], isset($threaddata['CLOSED']) && $threaddata['CLOSED'] > 0 ? true : false);
    echo "<br />".form_checkbox("t_sticky", "Y", $lang['makesticky'], isset($threaddata['STICKY']) && $threaddata['STICKY'] == "Y" ? true : false)."</p>\n";
    echo form_input_hidden("old_t_closed", isset($threaddata['CLOSED']) && $threaddata['CLOSED'] > 0 ? "Y" : "N");
    echo form_input_hidden("old_t_sticky", isset($threaddata['STICKY']) && $threaddata['STICKY'] == "Y" ? "Y" : "N");
}

echo "</td></tr>\n";
echo "</table>\n";
echo "</td>\n";
// ======================================


//echo "<td valign=\"top\" width=\"1\">&nbsp;</td>\n";


// ======================================
// =========== MESSAGE COLUMN ===========
echo "<td valign=\"top\" width=\"500\">\n";
echo "<table class=\"posthead\" width=\"500\">\n";
echo "<tr><td>\n";

if (!isset($t_post_html) || (isset($t_post_html) && $t_post_html != "Y")) {
    $t_content = isset($t_content) ? _stripslashes($t_content) : "";
}

$t_sig = _stripslashes($t_sig);
if (!isset($t_to_uid)) $t_to_uid = -1;

echo "<h2>". $lang['message'] .":</h2>\n";

$tools = new TextAreaHTML("f_post");

echo $tools->toolbar(false, form_submit('submit', $lang['post'], 'onclick="closeAttachWin(); clearFocus()"'));

if (isset($t_content)) {
	if (isset($HTTP_POST_VARS['preview']) && isset($HTTP_POST_VARS['t_post_html']) && $t_post_html) {
		$t_content = tidy_html($t_content, $auto_linebreaks);
	}
	$t_content = _htmlentities($t_content);
} else {
	$t_content = "";
}
echo $tools->textarea("t_content", $t_content, 20, 0, "virtual", "style=\"width: 480px\" tabindex=\"1\"")."\n";



if ($content_html_changes == true) {

	echo $tools->compare_original("t_content", $old_t_content);

    echo "<br /><br />\n";
}

echo "<h2>". $lang['htmlinmessage'] .":</h2>\n";

$tph_radio = 1;

if (isset($HTTP_POST_VARS['t_post_html'])) {
    if ($t_post_html) {
        $tph_radio = 3;
        if ($auto_linebreaks == true) {
            $tph_radio = 2;
        }
    }
}

echo form_radio("t_post_html", "disabled", $lang['disabled'], $tph_radio == 1, "tabindex=\"6\"")." \n";
echo form_radio("t_post_html", "enabled_auto", $lang['enabledwithautolinebreaks'], $tph_radio == 2)." \n";
echo form_radio("t_post_html", "enabled", $lang['enabled'], $tph_radio == 3)." \n";

echo $tools->assign_checkbox("t_post_html[1]", "t_post_html[0]");

echo "<br /><br /><h2>". $lang['messageoptions'] .":</h2>\n";

echo form_submit('submit', $lang['post'], 'tabindex="2" onclick="closeAttachWin(); clearFocus()"');
echo "&nbsp;".form_submit('preview', $lang['preview'], 'tabindex="3" onClick="clearFocus()"');
echo "&nbsp;".form_submit('cancel', $lang['cancel'], 'tabindex="4" onclick="closeAttachWin(); clearFocus()"');

if (forum_get_setting('attachments_enabled', 'Y', false)) {

    echo "&nbsp;".form_button("attachments", $lang['attachments'], "tabindex=\"5\" onclick=\"launchAttachWin('{$aid}', '$webtag')\"");
    echo form_input_hidden("aid", $aid);
}


// ---- SIGNATURE ----
echo "<br /><br /><h2>". $lang['signature'] .":</h2>\n";

$t_sig = tidy_html($t_sig, false);

echo $tools->textarea("t_sig", _htmlentities($t_sig), 5, 0, "virtual", "tabindex=\"7\" style=\"width: 480px\"")."\n";

echo form_input_hidden("t_sig_html", $t_sig_html)."\n";

if ($sig_html_changes == true) {

	echo $tools->compare_original("t_sig", $old_t_sig);

}

echo "</td></tr>\n";
echo "</table>";
echo "</td>\n";
// ======================================


echo "</tr>\n";
echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
echo "</table>\n";


echo $tools->js();


if (isset($HTTP_POST_VARS['t_dedupe'])) {
    echo form_input_hidden("t_dedupe", $HTTP_POST_VARS['t_dedupe']);
}else{
    echo form_input_hidden("t_dedupe", date("YmdHis"));
}

if (!$newthread) {

    echo "<table class=\"posthead\" width=\"720\">\n";
    echo "<tr><td class=\"subhead\">". $lang['inreplyto'] .":</td></tr>\n";
    echo "<tr><td>\n";

    if (($threaddata['POLL_FLAG'] == 'Y') && ($reply_message['PID'] == 1)) {

      poll_display($reply_to_tid, $threaddata['LENGTH'], $reply_to_pid, false, false, false, true, $show_sigs, true);

    }else {

      message_display($reply_to_tid, $reply_message, $threaddata['LENGTH'], $reply_to_pid, true, false, false, false, $show_sigs, true);

    }

    echo "<br /></td></tr>\n";
    echo "</table>\n";
}

echo "</form>\n";

html_draw_bottom();

?>