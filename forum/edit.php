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

/* $Id: edit.php,v 1.107 2004-04-04 21:03:39 decoyduck Exp $ */

// Compress the output
include_once("./include/gzipenc.inc.php");

// Enable the error handler
include_once("./include/errorhandler.inc.php");

// Multiple forum support
include_once("./include/forum.inc.php");

// Fetch the forum webtag and settings
$webtag = get_webtag();
$forum_settings = get_forum_settings();

include_once("./include/admin.inc.php");
include_once("./include/attachments.inc.php");
include_once("./include/config.inc.php");
include_once("./include/edit.inc.php");
include_once("./include/fixhtml.inc.php");
include_once("./include/folder.inc.php");
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

// Load the wordfilter for the current user

$user_wordfilter = load_wordfilter();

if (bh_session_get_value('UID') == 0) {
    html_guest_error();
    exit;
}

if (isset($HTTP_GET_VARS['msg']) && validate_msg($HTTP_GET_VARS['msg'])) {

  $edit_msg = $HTTP_GET_VARS['msg'];
  list($tid, $pid) = explode('.', $HTTP_GET_VARS['msg']);

}elseif (isset($HTTP_POST_VARS['t_msg']) && validate_msg($HTTP_POST_VARS['t_msg'])) {

  $edit_msg = $HTTP_POST_VARS['t_msg'];
  list($tid, $pid) = explode('.', $HTTP_POST_VARS['t_msg']);

}else {

    html_draw_top();

    echo "<h1 style=\"width: 99%\">{$lang['editmessage']}</h1>\n";
    echo "<br />\n";

    echo "<table class=\"posthead\" width=\"720\">\n";
    echo "<tr><td class=\"subhead\">".$lang['error']."</td></tr>\n";
    echo "<tr><td>\n";

    echo "<h2>".$lang['nomessagespecifiedforedit']."</h2>\n";
    echo "</td></tr>\n";

    echo "<tr><td align=\"center\">\n";
    echo form_quick_button("./discussion.php", $lang['back']);
    echo "</td></tr>\n";
    echo "</table>\n";

    html_draw_bottom();
    exit;

}

if (!is_numeric($tid) || !is_numeric($pid)) {

    html_draw_top();

    echo "<h1 style=\"width: 99%\">{$lang['editmessage']} $tid.$pid</h1>\n";
    echo "<br />\n";

    echo "<table class=\"posthead\" width=\"720\">\n";
    echo "<tr><td class=\"subhead\">".$lang['error']."</td></tr>\n";
    echo "<tr><td>\n";

    echo "<h2>".$lang['nomessagespecifiedforedit']."</h2>\n";
    echo "</td></tr>\n";

    echo "<tr><td align=\"center\">\n";
    echo form_quick_button("./discussion.php", $lang['back'], "msg", "$tid.$pid");
    echo "</td></tr>\n";
    echo "</table>\n";

    html_draw_bottom();
    exit;

}

if (thread_is_poll($tid) && $pid == 1) {

    $uri = "./edit_poll.php?webtag=$webtag";

    if (isset($HTTP_GET_VARS['msg']) && validate_msg($HTTP_GET_VARS['msg'])) {
        $uri.= "&msg=". $HTTP_GET_VARS['msg'];
    }elseif (isset($HTTP_POST_VARS['t_msg']) && validate_msg($HTTP_POST_VARS['t_msg'])) {
        $uri.= "&msg=". $HTTP_POST_VARS['t_msg'];
    }

    header_redirect($uri);
}

if (isset($HTTP_POST_VARS['cancel'])) {

    $uri = "./discussion.php?webtag=$webtag";

    if (isset($HTTP_GET_VARS['msg']) && validate_msg($HTTP_GET_VARS['msg'])) {
        $uri.= "&msg=". $HTTP_GET_VARS['msg'];
    }elseif (isset($HTTP_POST_VARS['t_msg']) && validate_msg($HTTP_POST_VARS['t_msg'])) {
        $uri.= "&msg=". $HTTP_POST_VARS['t_msg'];
    }

    header_redirect($uri);
}

// Check if the user is viewing signatures.
$show_sigs = !(bh_session_get_value('VIEW_SIGS'));

$valid = true;

html_draw_top("onUnload=clearFocus()", "basetarget=_blank", "edit.js", "openprofile.js", "htmltools.js", "emoticons.js");

$t_content = "";
$edit_type = "text";
$t_post_html = false;
$content_html_changes = false;
$sig_html_changes = false;

if (isset($HTTP_POST_VARS['edit_type'])) {
    $edit_type = $HTTP_POST_VARS['edit_type'];
}
if (isset($HTTP_POST_VARS['b_edit_html'])) {
    $edit_type = "html";
} else if (isset($HTTP_POST_VARS['b_edit_text'])) {
    $edit_type = "text";
}
if ($edit_type == "html") {
    $t_post_html = true;
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
}

if (isset($HTTP_POST_VARS['preview'])) {

    $preview_message = messages_get($tid, $pid, 1);

    if (isset($HTTP_POST_VARS['t_to_uid'])) {
        $to_uid = $HTTP_POST_VARS['t_to_uid'];
    }else {
        $error_html = "<h2>{$lang['invalidusername']}</h2>\n";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_from_uid'])) {
        $from_uid = $HTTP_POST_VARS['t_from_uid'];
    }else {
        $error_html = "<h2>{$lang['invalidusername']}</h2>\n";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_content']) && strlen(trim($HTTP_POST_VARS['t_content'])) > 0) {

        $t_content = $HTTP_POST_VARS['t_content'];
        
        if (attachment_embed_check($t_content) && $t_post_html == true) {
            $error_html = "<h2>{$lang['notallowedembedattachmentpost']}</h2>\n";
            $valid = false;
        }
    }else {
        $error_html = "<h2>{$lang['mustenterpostcontent']}</h2>";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_sig']) && strlen(trim($HTTP_POST_VARS['t_sig'])) > 0) {

        $old_t_sig = $HTTP_POST_VARS['t_sig'];

        $t_sig = fix_html($HTTP_POST_VARS['t_sig']);

        if ($old_t_sig != tidy_html($t_sig, false)) {
            $sig_html_changes = true;
        }

        if (attachment_embed_check($t_sig)) {
            $error_html = "<h2>{$lang['notallowedembedattachmentpost']}</h2>\n";
            $valid = false;
        }
    }else {
        $t_sig = "";
    }

    if ($valid) {

        if ($t_post_html == true && $edit_type == "html") {
            $old_t_content = $t_content;
            $t_content = fix_html($t_content);

            if ($old_t_content != tidy_html($t_content)) {
                $content_html_changes = true;
            }

            if ($auto_linebreaks == true) {
                $t_content = add_paragraphs($t_content);
            }
            $preview_message['CONTENT'] = $t_content;
//            $t_content = str_replace("&", "&amp;", $t_content);

/*            $t_content = fix_html($t_content);
            $preview_message['CONTENT'] = $t_content;
            $t_content = str_replace("&", "&amp;", $t_content);*/
        }else{
            $t_content = make_html($t_content);
            $preview_message['CONTENT'] = $t_content;
            $t_content = strip_tags($t_content);
          //  $t_content = ereg_replace("\n+", "\n", $t_content);
        }

        $preview_message['CONTENT'].= "<div class=\"sig\">$t_sig</div>";

        if ($to_uid == 0) {

            $preview_message['TLOGON'] = "ALL";
            $preview_message['TNICK'] = "ALL";

        }else{

            $preview_tuser = user_get($HTTP_POST_VARS['t_to_uid']);
            $preview_message['TLOGON'] = $preview_tuser['LOGON'];
            $preview_message['TNICK'] = $preview_tuser['NICKNAME'];
            $preview_message['TO_UID'] = $preview_tuser['UID'];

        }

        $preview_tuser = user_get($from_uid);
        $preview_message['FLOGON'] = $preview_tuser['LOGON'];
        $preview_message['FNICK'] = $preview_tuser['NICKNAME'];
        $preview_message['FROM_UID'] = $from_uid;
    }

}elseif (isset($HTTP_POST_VARS['submit'])) {

    $editmessage = messages_get($tid, $pid, 1);

    if (isset($HTTP_POST_VARS['t_to_uid'])) {
        $to_uid = $HTTP_POST_VARS['t_to_uid'];
    }else {
        $error_html = "<h2>{$lang['invalidusername']}</h2>\n";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_from_uid'])) {
        $from_uid = $HTTP_POST_VARS['t_from_uid'];
    }else {
        $error_html = "<h2>{$lang['invalidusername']}</h2>\n";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_content']) && strlen(trim($HTTP_POST_VARS['t_content'])) > 0) {

        $t_content = $HTTP_POST_VARS['t_content'];

        if (attachment_embed_check($t_content) && $t_post_html == true) {
            $error_html = "<h2>{$lang['notallowedembedattachmentpost']}</h2>\n";
            $valid = false;
        }
    }else {
        $error_html = "<h2>{$lang['mustenterpostcontent']}</h2>";
        $valid = false;
    }

    if (isset($HTTP_POST_VARS['t_sig']) && strlen(trim($HTTP_POST_VARS['t_sig'])) > 0) {

        $old_t_sig = $HTTP_POST_VARS['t_sig'];

        $t_sig = fix_html($HTTP_POST_VARS['t_sig']);

        if ($old_t_sig != $t_sig) {
            $sig_html_changes = true;
        }

        if (attachment_embed_check($t_sig)) {
            $error_html = "<h2>{$lang['notallowedembedattachmentpost']}</h2>\n";
            $valid = false;
        }
    }else {
        $t_sig = "";
    }

    if (((forum_get_setting('allow_post_editing', 'N', false)) || (bh_session_get_value('UID') != $editmessage['FROM_UID']) || (((time() - $editmessage['CREATED']) >= (intval(forum_get_setting('post_edit_time')) * HOUR_IN_SECONDS)) && intval(forum_get_setting('post_edit_time')) != 0)) && !perm_is_moderator()) {
    
        echo "<h1 style=\"width: 99%\">{$lang['editmessage']} $tid.$pid</h1>\n";
        echo "<br />\n";

        echo "<table class=\"posthead\" width=\"720\">\n";
        echo "<tr><td class=\"subhead\">".$lang['error']."</td></tr>\n";
        echo "<tr><td>\n";

        echo "<h2>".$lang['nopermissiontoedit']."</h2>\n";
        echo "</td></tr>\n";

        echo "<tr><td align=\"center\">\n";
        echo form_quick_button("./discussion.php", $lang['back'], "msg", "$tid.$pid");
        echo "</td></tr>\n";
        echo "</table>\n";

        html_draw_bottom();
        exit;
    }
    
    $preview_message = $editmessage;

    if ($valid) {

        if ($t_post_html == true) {
            $old_t_content = _stripslashes($t_content);
            $t_content = fix_html($t_content);

            if ($old_t_content != tidy_html($t_content)) {
                $content_html_changes = true;
            }

            if ($auto_linebreaks == true) {
                $t_content = add_paragraphs($t_content);
            }
        }else{
            $t_content = make_html($t_content);
        }

        $t_content.= "<div class=\"sig\">$t_sig</div>";

        $updated = post_update($tid, $pid, $t_content);

        if ($updated) {
        
            post_add_edit_text($tid, $pid);
            
            if (isset($HTTP_POST_VARS['aid']) && forum_get_setting('attachments_enabled', 'Y', false)) {
                if (get_num_attachments($HTTP_POST_VARS['aid']) > 0) post_save_attachment_id($tid, $pid, $HTTP_POST_VARS['aid']);
            }

            if (perm_is_moderator() && ($HTTP_POST_VARS['t_from_uid'] != bh_session_get_value('UID'))) {
                admin_addlog(0, 0, $tid, $pid, 0, 0, 23);
            }

            echo "<h1 style=\"width: 99%\">{$lang['editmessage']} $tid.$pid</h1>\n";
            echo "<br />\n";

            echo "<table class=\"posthead\" width=\"720\">\n";
            echo "<tr><td class=\"subhead\">".$lang['editmessage']."</td></tr>\n";
            echo "<tr><td>\n";

            echo "<h2>".$lang['editappliedtomessage']."</h2>\n";
            echo "</td></tr>\n";

            echo "<tr><td align=\"center\">\n";
            echo form_quick_button("discussion.php", $lang['continue'], "msg", "$tid.$pid");
            echo "</td></tr>\n";
            echo "</table>\n";

            html_draw_bottom();
            exit;

        }else{
            $error_html = "<h2>{$lang['errorupdatingpost']}</h2>";

			$t_content_temp = $t_content;
			$t_content_temp = preg_split("/<div class=\"sig\">/", $t_content_temp);

			if (count($t_content_temp) > 1) {

				$t_sig_temp = array_pop($t_content_temp);
				$t_sig_temp = preg_split("/<\/div>/", $t_sig_temp);

				$t_sig = "";

				for ($i = 0; $i < count($t_sig_temp) - 1; $i++) {
					$t_sig.= $t_sig_temp[$i];
					if ($i < count($t_sig_temp) - 2 ) {
						$t_sig.= "</div>";
					}
				}

			}else {
				$t_sig = "";
			}

			$t_content = "";

			for ($i = 0; $i < count($t_content_temp); $i++) {
				$t_content.= $t_content_temp[$i];
				if ($i < count($t_content_temp) - 1) {
					$t_content.= "<div class=\"sig\">";
				}
			}

			if (!isset($HTTP_POST_VARS['b_edit_html'])) {
				$t_content = strip_tags($t_content);
			}
        }
    }

}else{

    $editmessage = messages_get($tid, $pid, 1);

    if (count($editmessage) > 0) {

        $editmessage['CONTENT'] = message_get_content($tid, $pid);

        if (((forum_get_setting('allow_post_editing', 'N', false)) || (bh_session_get_value('UID') != $editmessage['FROM_UID']) || (((time() - $editmessage['CREATED']) >= (intval(forum_get_setting('post_edit_time')) * HOUR_IN_SECONDS)) && intval(forum_get_setting('post_edit_time')) != 0)) && !perm_is_moderator()) {
        
            echo "<h1 style=\"width: 99%\">{$lang['editmessage']} $tid.$pid</h1>\n";
            echo "<br />\n";

            echo "<table class=\"posthead\" width=\"720\">\n";
            echo "<tr><td class=\"subhead\">".$lang['error']."</td></tr>\n";
            echo "<tr><td>\n";

            echo "<h2>".$lang['nopermissiontoedit']."</h2>\n";
            echo "</td></tr>\n";

            echo "<tr><td align=\"center\">\n";
            echo form_quick_button("discussion.php", $lang['back'], "msg", "$tid.$pid");
            echo "</td></tr>\n";
            echo "</table>\n";

            html_draw_bottom();
            exit;
        }

        $preview_message = $editmessage;

        $to_uid = $editmessage['TO_UID'];
        $from_uid = $editmessage['FROM_UID'];

        $t_content_temp = $editmessage['CONTENT'];
        $t_content_temp = preg_split("/<div class=\"sig\">/", $t_content_temp);

        if (count($t_content_temp) > 1) {

            $t_sig_temp = array_pop($t_content_temp);
            $t_sig_temp = preg_split("/<\/div>/", $t_sig_temp);

            $t_sig = "";

            for ($i = 0; $i < count($t_sig_temp) - 1; $i++) {
                $t_sig.= $t_sig_temp[$i];
                if ($i < count($t_sig_temp) - 2 ) {
                    $t_sig.= "</div>";
                }
            }

        }else {
            $t_sig = "";
        }

        $t_content = "";

        for ($i = 0; $i < count($t_content_temp); $i++) {
            $t_content.= $t_content_temp[$i];
            if ($i < count($t_content_temp) - 1) {
                $t_content.= "<div class=\"sig\">";
            }
        }

        $preview_message['CONTENT'] = $t_content."<div class=\"sig\">".$t_sig."</div>";

        if (!isset($HTTP_POST_VARS['b_edit_html'])) {

//            $t_content = trim($t_content);
//            $t_content = str_replace("<p>", "\n\n<p>", $t_content);
//            $t_content = str_replace("</p>", "</p>\n", $t_content);
//            $t_content = ereg_replace("^\n\n<p>", "<p>", $t_content);
//            $t_content = ereg_replace("<br[[:space:]*]/>", "\n", $t_content);
            $t_content = strip_tags($t_content);
        }else{
//            $t_content = _htmlentities($t_content);
        }

    }else{
        $valid = false;
        $error_html = "<h2>{$lang['message']} ". $HTTP_GET_VARS['msg']. " {$lang['wasnotfound']}</h2>";
    }

    unset($editmessage);
 //   $t_post_html = isset($HTTP_POST_VARS['b_edit_html']);
}

echo "<h1 style=\"width: 99%\">{$lang['editmessage']} $tid.$pid</h1>\n";
echo "<br /><form name=\"f_edit\" action=\"edit.php?webtag=$webtag\" method=\"post\" target=\"_self\">\n";

if (isset($error_html)) {
    echo "<table class=\"posthead\" width=\"720\">\n";
    echo "<tr><td class=\"subhead\">{$lang['error']}</td></tr>";
    echo "<tr><td>\n";
    echo $error_html . "\n";
    echo "</td></tr>\n";
    echo "</table>\n";
}

$threaddata = thread_get($tid);

if ($valid && isset($HTTP_POST_VARS['preview'])) {
    echo "<table class=\"posthead\" width=\"720\">\n";
    echo "<tr><td class=\"subhead\">{$lang['messagepreview']}</td></tr>";

    echo "<tr><td>\n";
    message_display($tid, $preview_message, $threaddata['LENGTH'], $pid, true, false, false, false, $show_sigs, true);
    echo "</td></tr>\n";

    echo "<tr><td>&nbsp;</td></tr>\n";
    echo "</table>\n";
}

echo "<table class=\"posthead\" width=\"720\">\n";
echo "<tr><td class=\"subhead\" colspan=\"2\">";
echo $lang['editmessage'];
echo "</td></tr>\n";
echo "<tr>\n";


// ======================================
// =========== OPTIONS COLUMN ===========
echo "<td valign=\"top\" width=\"210\">\n";
echo "<table class=\"posthead\" width=\"210\">\n";
echo "<tr><td>\n";

echo "<h2>".$lang['folder'].":</h2>\n";
echo _stripslashes($threaddata['FOLDER_TITLE'])."\n";
echo "<h2>".$lang['threadtitle'].":</h2>\n";
echo _stripslashes($threaddata['TITLE'])."\n";

echo form_input_hidden("t_msg", $edit_msg);
echo form_input_hidden("t_to_uid", $to_uid);
echo form_input_hidden("t_from_uid", $from_uid);

echo "<h2>".$lang['to'].":</h2>\n";
echo "<a href=\"javascript:void(0);\" onclick=\"openProfile($from_uid, '$webtag')\" target=\"_self\">";
echo _stripslashes(format_user_name($preview_message['FLOGON'], $preview_message['FNICK']));
echo "</a><br />\n";

echo "<br /><h2><a href=\"javascript:void(0);\" onclick=\"openEmoticons('user','$webtag')\" target=\"_self\">{$lang['emoticons']}</a></h2><br />\n";

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

echo "<h2>". $lang['message'] .":</h2>\n";

if ($edit_type == "html") {
	$tools = new TextAreaHTML("f_edit");

	echo $tools->toolbar(form_submit('submit',$lang['apply'], 'onclick="closeAttachWin(); clearFocus()"'));

    $t_content = tidy_html($t_content, isset($auto_linebreaks) ? $auto_linebreaks : false);
    $t_content = _htmlentities($t_content);

    echo $tools->textarea("t_content", $t_content, 20, 0, "virtual", "style=\"width: 480px\" tabindex=\"1\"")."\n";

    if ($content_html_changes == true) {

		echo $tools->compare_original("t_content", $old_t_content);

        echo "<br /><br />\n";
    }

    echo "<h2>". $lang['htmlinmessage'] .":</h2>\n";

    $tph_radio = 1;
    if ($t_post_html) {
        $tph_radio = 3;
        if (isset($auto_linebreaks) && $auto_linebreaks == true) {
            $tph_radio = 2;
        }
    }

    echo form_radio("t_post_html", "disabled", $lang['disabled'], $tph_radio == 1, "tabindex=\"6\"")." \n";
    echo form_radio("t_post_html", "enabled_auto", $lang['enabledwithautolinebreaks'], $tph_radio == 2)." \n";
    echo form_radio("t_post_html", "enabled", $lang['enabled'], $tph_radio == 3)." \n";

	echo $tools->assign_checkbox("t_post_html[1]", "t_post_html[0]");

    echo "<br /><br />\n";

} else {
    echo form_textarea("t_content", $t_content, 20, 0, "virtual", "style=\"width: 480px\" tabindex=\"1\"")."\n";
}

echo "<h2>". $lang['messageoptions'] .":</h2>\n";

echo form_submit('submit',$lang['apply'], 'tabindex="2" onclick="closeAttachWin(); clearFocus()"');
echo "&nbsp;".form_submit('preview', $lang['preview'], 'tabindex="3" onClick="clearFocus()"');
echo "&nbsp;".form_submit('cancel', $lang['cancel'], 'tabindex="4" onclick="closeAttachWin(); clearFocus()"');

if ($edit_type == "html") {
    echo "&nbsp;".form_submit("b_edit_text", $lang['edittext']);
    echo form_input_hidden("edit_type", "html");

} else {
    echo "&nbsp;".form_submit("b_edit_html", $lang['editHTML']);
    echo form_input_hidden("edit_type", "text");
}

if ($aid = get_attachment_id($tid, $pid)) {
    echo "&nbsp;", form_button("attachments", $lang['attachments'], "onclick=\"launchAttachEditWin('$aid', '$webtag');\"");
    echo form_input_hidden('aid', $aid);
}else {
    $aid = md5(uniqid(rand()));
    echo "&nbsp;", form_button("attachments", $lang['attachments'], "onclick=\"launchAttachEditWin('$aid', '$webtag');\"");
    echo form_input_hidden('aid', $aid);
}

// ---- SIGNATURE ----
echo "<br /><br /><h2>". $lang['signature'] .":</h2>\n";

$t_sig = tidy_html($t_sig, false);

if ($edit_type == "html") {
	echo $tools->textarea("t_sig", _htmlentities($t_sig), 5, 0, "virtual", "tabindex=\"7\" style=\"width: 480px\"")."\n";

	echo $tools->js();
} else {
	echo form_textarea("t_sig", _htmlentities($t_sig), 5, 0, "virtual", "tabindex=\"7\" style=\"width: 480px\"")."\n";
}

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
echo "</form>";

html_draw_bottom();

?>