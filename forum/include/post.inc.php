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

/* $Id: post.inc.php,v 1.118 2005-04-03 00:56:06 tribalonline Exp $ */

include_once(BH_INCLUDE_PATH. "forum.inc.php");
include_once(BH_INCLUDE_PATH. "fixhtml.inc.php");
include_once(BH_INCLUDE_PATH. "html.inc.php");

function post_create($fid, $tid, $reply_pid, $by_uid, $fuid, $tuid, $content)
{
    $db_post_create = db_connect();

    include("./include/search_stopwords.inc.php");

    $search_min_word_length = intval(forum_get_setting('search_min_word_length', false, 3));

    $post_content = addslashes($content);

    if (!$ipaddress = get_ip_address()) $ipaddress = "";

    if (!is_numeric($tid)) return -1;
    if (!is_numeric($reply_pid)) return -1;
    if (!is_numeric($fuid)) return -1;
    if (!is_numeric($tuid)) return -1;

    if (!$table_data = get_table_prefix()) return -1;

    if (perm_check_folder_permissions($fid, USER_PERM_POST_APPROVAL) && !perm_is_moderator($fid)) {

        $sql = "INSERT INTO {$table_data['PREFIX']}POST ";
        $sql.= "(TID, REPLY_TO_PID, FROM_UID, TO_UID, CREATED, APPROVED, IPADDRESS) ";
        $sql.= "VALUES ($tid, $reply_pid, $fuid, $tuid, NOW(), 0, '$ipaddress')";

    }else {

        $sql = "INSERT INTO {$table_data['PREFIX']}POST ";
        $sql.= "(TID, REPLY_TO_PID, FROM_UID, TO_UID, CREATED, APPROVED, APPROVED_BY, IPADDRESS) ";
        $sql.= "VALUES ($tid, $reply_pid, $fuid, $tuid, NOW(), NOW(), $fuid, '$ipaddress')";
    }

    $result = db_query($sql,$db_post_create);

    if ($result) {

        $new_pid = db_insert_id($db_post_create);

        $sql = "INSERT INTO {$table_data['PREFIX']}POST_CONTENT (TID, PID, CONTENT) ";
        $sql.= "VALUES ('$tid', '$new_pid', '$post_content')";

        $result = db_query($sql, $db_post_create);

        if ($result) {

            $sql = "UPDATE {$table_data['PREFIX']}THREAD SET LENGTH = $new_pid, MODIFIED = NOW() ";
            $sql.= "WHERE TID = $tid";

            $result = db_query($sql, $db_post_create);

            search_index_post($fid, $tid, $new_pid, $by_uid, $fuid, $tuid, $post_content);

        }else {

            $new_pid = -1;
        }

    }else {

        $new_pid = -1;
    }

    return $new_pid;
}

function post_approve($tid, $pid)
{
    if (!is_numeric($tid)) return false;
    if (!is_numeric($pid)) return false;

    $db_post_approve = db_connect();

    $approve_uid = bh_session_get_value('UID');

    if (!$table_data = get_table_prefix()) return false;

    $sql = "UPDATE {$table_data['PREFIX']}POST SET APPROVED = NOW(), APPROVED_BY = '$approve_uid' ";
    $sql.= "WHERE TID = '$tid' AND PID = '$pid'";

    return db_query($sql, $db_post_approve);
}

function post_save_attachment_id($tid, $pid, $aid)
{
    if (!is_numeric($tid)) return false;
    if (!is_numeric($pid)) return false;
    if (!is_md5($aid)) return false;

    $db_post_save_attachment_id = db_connect();

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT TID FROM POST_ATTACHMENT_IDS ";
    $sql.= "WHERE FID = '{$table_data['FID']}' ";
    $sql.= "AND TID = '$tid' AND PID = '$pid'";

    $result = db_query($sql, $db_post_save_attachment_id);

    if (db_num_rows($result) > 0) {

        $sql = "UPDATE POST_ATTACHMENT_IDS SET AID = '$aid' ";
        $sql.= "WHERE FID = '{$table_data['FID']}' AND TID = '$tid' AND PID = '$pid'";

    }else {

        $sql = "INSERT INTO POST_ATTACHMENT_IDS ";
        $sql.= "(FID, TID, PID, AID) VALUES ('{$table_data['FID']}', '$tid', '$pid', '$aid')";
    }

    return db_query($sql, $db_post_save_attachment_id);
}

function post_create_thread($fid, $uid, $title, $poll = 'N', $sticky = 'N', $closed = false)
{
    if (!is_numeric($fid)) return -1;
    if (!is_numeric($uid)) return -1;

    $title  = addslashes(_htmlentities($title));

    $poll = ($poll == 'Y') ? 'Y' : 'N';
    $sticky = ($sticky == 'Y') ? 'Y' : 'N';
    $closed = $closed ? "NOW()" : "NULL";

    $db_post_create_thread = db_connect();

    if (!$table_data = get_table_prefix()) return -1;

    $sql = "INSERT INTO {$table_data['PREFIX']}THREAD " ;
    $sql.= "(FID, BY_UID, TITLE, LENGTH, POLL_FLAG, STICKY, CREATED, MODIFIED, CLOSED) ";
    $sql.= "VALUES ('$fid', '$uid', '$title', 0, '$poll', '$sticky', NOW(), NOW(), $closed)";

    $result = db_query($sql, $db_post_create_thread);

    if ($result) {
        $new_tid = db_insert_id($db_post_create_thread);
    }else {
        $new_tid = -1;
    }

    return $new_tid;
}

function post_draw_to_dropdown($default_uid, $show_all = true)
{
    $html = "<select name=\"t_to_uid\">\n";
    $db_post_draw_to_dropdown = db_connect();

    if (!is_numeric($default_uid)) $default_uid = 0;

    if (!$table_data = get_table_prefix()) return false;

    if (isset($default_uid) && $default_uid != 0){

        $top_sql = "SELECT LOGON, NICKNAME FROM USER where UID = '$default_uid'";
        $result = db_query($top_sql,$db_post_draw_to_dropdown);

        if (db_num_rows($result) > 0) {

            $top_user = db_fetch_array($result);
            $fmt_username = format_user_name($top_user['LOGON'],$top_user['NICKNAME']);
            $html .= "<option value=\"$default_uid\" selected=\"selected\">$fmt_username</option>\n";
        }
    }

    if ($show_all) {
        $html .= "<option value=\"0\">ALL</option>\n";
    }

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, ";
    $sql.= "UNIX_TIMESTAMP(VISITOR_LOG.LAST_LOGON) AS LAST_LOGON FROM USER USER ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}VISITOR_LOG VISITOR_LOG ";
    $sql.= "ON (USER.UID = VISITOR_LOG.UID) WHERE USER.UID <> '$default_uid' ";
    $sql.= "ORDER BY VISITOR_LOG.LAST_LOGON DESC ";
    $sql.= "LIMIT 0, 20";

    $result = db_query($sql, $db_post_draw_to_dropdown);

    while ($row = db_fetch_array($result)) {

        if (isset($row['LOGON'])) {
           $logon = $row['LOGON'];
        } else {
           $logon = "";
        }

        if(isset($row['NICKNAME'])){
            $nickname = $row['NICKNAME'];
        } else {
            $nickname = "";
        }

        $fmt_uid = $row['UID'];
        $fmt_username = format_user_name($logon,$nickname);

        if($fmt_uid != $default_uid && $fmt_uid != 0){
            $html .= "<option value=\"$fmt_uid\">$fmt_username</option>\n";
        }
    }

    $html .= "</select>";
    return $html;
}

function post_draw_to_dropdown_recent($default_uid, $show_all = true)
{
    $html = "<select name=\"t_to_uid_recent\" class=\"recent_user_dropdown\" onclick=\"checkToRadio(". ($default_uid == 0 ? 1 : 0).")\">\n";
    $db_post_draw_to_dropdown = db_connect();

    if (!$table_data = get_table_prefix()) return "";

    if (!is_numeric($default_uid)) $default_uid = 0;

    if (isset($default_uid) && $default_uid != 0) {

        $top_sql = "SELECT LOGON, NICKNAME FROM USER WHERE UID = '$default_uid'";
        $result = db_query($top_sql,$db_post_draw_to_dropdown);

        if (db_num_rows($result) > 0) {

            $top_user = db_fetch_array($result);
            $fmt_username = format_user_name($top_user['LOGON'],$top_user['NICKNAME']);
            $html .= "<option value=\"$default_uid\" selected=\"selected\">$fmt_username</option>\n";
        }
    }

    if ($show_all) {
        $html .= "<option value=\"0\">ALL</option>\n";
    }

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, ";
    $sql.= "UNIX_TIMESTAMP(VISITOR_LOG.LAST_LOGON) AS LAST_LOGON FROM USER USER ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}VISITOR_LOG VISITOR_LOG ";
    $sql.= "ON (USER.UID = VISITOR_LOG.UID) WHERE USER.UID <> '$default_uid' ";
    $sql.= "ORDER BY VISITOR_LOG.LAST_LOGON DESC ";
    $sql.= "LIMIT 0, 20";

    $result = db_query($sql, $db_post_draw_to_dropdown);

    while ($row = db_fetch_array($result)) {

        if (isset($row['LOGON'])) {
           $logon = $row['LOGON'];
        } else {
           $logon = "";
        }

        if(isset($row['NICKNAME'])){
            $nickname = $row['NICKNAME'];
        } else {
            $nickname = "";
        }

        $fmt_uid = $row['UID'];
        $fmt_username = format_user_name($logon,$nickname);

        if($fmt_uid != $default_uid && $fmt_uid != 0){
            $html .= "<option value=\"$fmt_uid\">$fmt_username</option>\n";
        }
    }

    $html .= "</select>";
    return $html;
}

function post_draw_to_dropdown_in_thread($tid, $default_uid, $show_all = true, $inc_blank = false, $custom_html = "")
{
    $html = "<select name=\"t_to_uid_in_thread\" class=\"user_in_thread_dropdown\" ".$custom_html.">\n";
    $db_post_draw_to_dropdown = db_connect();

    if (!is_numeric($tid)) return false;
    if (!is_numeric($default_uid)) $default_uid = 0;

    if (!$table_data = get_table_prefix()) return "";

    if (isset($default_uid) && $default_uid != 0) {

        $top_sql = "SELECT LOGON, NICKNAME FROM USER WHERE UID = '$default_uid'";
        $result = db_query($top_sql,$db_post_draw_to_dropdown);

        if (db_num_rows($result) > 0) {

            $top_user = db_fetch_array($result);
            $fmt_username = format_user_name($top_user['LOGON'],$top_user['NICKNAME']);
            $html.= "<option value=\"$default_uid\" selected=\"selected\">$fmt_username</option>\n";
        }
    }

    if ($show_all) {

        $html.= "<option value=\"0\">ALL</option>\n";

    } else if ($inc_blank) {

        if (isset($default_uid) && $default_uid != 0) {
            $html.= "<option value=\"0\"></option>\n";
                }else {
            $html.= "<option value=\"0\" selected=\"selected\"></option>\n";
                }
    }

    $sql = "SELECT P.FROM_UID AS UID, U.LOGON, U.NICKNAME ";
    $sql.= "FROM {$table_data['PREFIX']}POST P ";
    $sql.= "LEFT JOIN USER U ON (P.FROM_UID = U.UID) ";
    $sql.= "WHERE P.TID = '$tid' ";
    $sql.= "GROUP BY P.FROM_UID LIMIT 0, 20";

    $result = db_query($sql, $db_post_draw_to_dropdown);

    while ($row = db_fetch_array($result)) {

        if (isset($row['LOGON'])) {
           $logon = $row['LOGON'];
        } else {
           $logon = "";
        }

        if(isset($row['NICKNAME'])){
            $nickname = $row['NICKNAME'];
        } else {
            $nickname = "";
        }

        $fmt_uid = $row['UID'];
        $fmt_username = format_user_name($logon,$nickname);

        if ($fmt_uid != $default_uid && $fmt_uid != 0) {
            $html .= "<option value=\"$fmt_uid\">$fmt_username</option>\n";
        }
    }

    $html .= "</select>";
    return $html;
}

function get_user_posts($uid)
{
    $db_get_user_posts = db_connect();

    if (!is_numeric($uid)) return false;

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT TID, PID FROM {$table_data['PREFIX']}POST WHERE FROM_UID = '$uid'";
    $result = db_query($sql, $db_get_user_posts);

    if (db_num_rows($result)) {
        $user_post_array = array();
        while ($row = db_fetch_array($result)) {
            $user_post_array[] = $row;
        }
        return $user_post_array;
    }else {
        return false;
    }
}

function check_ddkey($ddkey)
{
    $db_check_ddkey = db_connect();

    $uid = bh_session_get_value('UID');

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT UNIX_TIMESTAMP(DDKEY) FROM USER_TRACK WHERE UID = '$uid'";
    $result = db_query($sql, $db_check_ddkey);

    if (db_num_rows($result)) {

        list($ddkey_check) = db_fetch_array($result);

        $sql = "UPDATE USER_TRACK SET DDKEY = FROM_UNIXTIME($ddkey) WHERE UID = '$uid'";
        $result = db_query($sql, $db_check_ddkey);

    }else{

        $ddkey_check = "";

        $sql = "INSERT INTO USER_TRACK (UID, DDKEY) ";
        $sql.= "VALUES ('$uid', FROM_UNIXTIME($ddkey))";

        $result = db_query($sql, $db_check_ddkey);
    }

    return !($ddkey == $ddkey_check);
}

function check_post_frequency()
{
    $db_check_post_frequency = db_connect();

    $uid = bh_session_get_value('UID');

    if (!$table_data = get_table_prefix()) return false;

    $search_stamp = time() - intval(forum_get_setting('minimum_post_frequency', false, 0));

    $sql = "SELECT UNIX_TIMESTAMP(LAST_POST) FROM USER_TRACK WHERE UID = '$uid'";
    $result = db_query($sql, $db_check_post_frequency);

    if (db_num_rows($result) > 0) {

        list($last_search_check) = db_fetch_array($result);

        if ($last_search_check < $search_stamp) {

            $sql = "UPDATE USER_TRACK SET LAST_POST = NOW() WHERE UID = '$uid'";
            $result = db_query($sql, $db_check_post_frequency);

            return true;
        }

    }else{

        $sql = "INSERT INTO USER_TRACK (UID, LAST_POST) ";
        $sql.= "VALUES ('$uid', NOW())";

        $result = db_query($sql, $db_check_post_frequency);

        return true;
    }

    return false;
}

class MessageText {

    // Note: PHP/5.0 introduces new public, private and protected
    // modifiers whilst removing the var modifier. However it only
    // causes problems if PHP/5.0's new STRICT error reporting
    // is also enabled, hence we're (for the mean while) going to
    // stick with PHP/4.x's old var modifiers, because for now
    // it is going to be more compatible with our 'audience'

    var $html = "";
    var $text = "";
    var $original_text = "";
    var $diff = false;
    var $emoticons = true;
    var $links = true;

    function MessageText ($html = 0, $content = "", $emoticons = true, $links = true) {
        $this->diff = false;
        $this->original_text = "";
        $this->links = $links;
        $this->setEmoticons($emoticons);
        $this->setHTML($html);
        $this->setContent($content);
    }

    function setHTML ($html) {
        if ($html == false || $html == "N") {
            $this->html = 0;
        } else if ($html == 1 || $html == "A") {
            $this->html = 1;
        } else {
            $this->html = 2;
        }

        $this->setContent($this->getOriginalContent());
    }

    function getHTML () {
        return $this->html;
    }

    function setEmoticons ($bool) {
        $this->emoticons = false; //($bool == true) ? true : false;
        $this->setContent($this->getOriginalContent());
    }

    function getEmoticons () {
        return $this->emoticons;
    }

    function getLinks () {
        return $this->links;
    }

    function setLinks ($bool) {
        $this->links = ($bool == true) ? true : false;
        $this->setContent($this->getOriginalContent());
    }

    function setContent ($text) {

        $this->original_text = $text;

        if ($this->html == 0) {
            $text = make_html($text, false, $this->emoticons, $this->links);
        } else if ($this->html > 0) {
            $text = fix_html($text, $this->emoticons, $this->links);

            if (trim($this->original_text) != trim(tidy_html($text, ($this->html == 1) ? true : false))) {
                $this->diff = true;
            }

            if ($this->html == 1) {
                $text = add_paragraphs($text);
            }
        }

        $this->text = $text;
    }

    function getContent () {
        return $this->text;
    }

    function getTidyContent () {
        if ($this->html == 0) {
            return strip_tags(clean_emoticons($this->text));
        } else if ($this->html > 0) {
            return _htmlentities(tidy_html($this->text, ($this->html == 1) ? true : false, $this->links));
        }
    }

    function getOriginalContent () {
        return $this->original_text;
    }

    function isDiff () {
        return $this->diff;
    }
}

class MessageTextParse {

    var $html = "";
    var $emoticons = "";
    var $links = "";
    var $message = "";
    var $sig = "";
    var $original = "";

    function MessageTextParse ($message, $emots_default = true, $links_enabled = true) {

        $this->original = $message;

        $message = explode('<div class="sig">', $message);

        if (count($message) > 1 && substr($message[count($message)-1], -6) == '</div>') {

            $sig = '<div class="sig">' . array_pop($message);
            do {
                $sig = '<div class="sig">' . array_pop($message) . $sig;
            } while (count(explode('<div', $sig)) != count(explode('</div>', $sig)));
            $sig = preg_replace("/^<div class=\"sig\">(.*)<\/div>$/s", '$1', $sig);

        } else {
            $sig = "";
        }

        $message = implode('<div class="sig">', $message);

        $sig = clean_emoticons($sig);
        $message_temp = clean_emoticons($message);

        $emoticons = $emots_default;

        if ($message_temp == $message && emoticons_convert(strip_tags($message, '<span>')) != strip_tags($message, '<span>')) {
            $emoticons = false;
        } else if ($message_temp != $message) {
            $emoticons = true;
        }

        $message = trim($message_temp);

        $html = 0;
        $message_temp = preg_replace("/<a href=\"(http:\/\/)?([^\"]*)\">((http:\/\/)?\\2)<\/a>/", "\\3", $message);
        if ($message_temp != $message) {
            $links = true;
        } else {
            $links = $links_enabled;
        }
        $message = $message_temp;

        if (strip_tags($message, '<p><br>') != $message_temp) {
            $html = 2;
            if (add_paragraphs($message) == $message) {
                $html = 1;
            }
        } else {
            $message = _htmlentities_decode(strip_tags($message));
        }

        $this->message = $message;
        $this->sig = $sig;
        $this->html = $html;
        $this->emoticons = $emoticons;
        $this->links = $links;
    }

    function getMessage () {
        return $this->message;
    }

    function getSig () {
        return $this->sig;
    }

    function getMessageHTML () {
        return $this->html;
    }

    function getEmoticons () {
        return $this->emoticons;
    }

    function getLinks () {
        return $this->links;
    }

    function getOriginal () {
        return $this->original;
    }
}

?>