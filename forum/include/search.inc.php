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

/* $Id: search.inc.php,v 1.107 2005-03-18 23:58:40 decoyduck Exp $ */

include_once(BH_INCLUDE_PATH. "forum.inc.php");
include_once(BH_INCLUDE_PATH. "lang.inc.php");
include_once(BH_INCLUDE_PATH. "user.inc.php");

function search_execute($argarray, &$urlquery, &$error)
{
    // MySQL has a list of stop words for fulltext searches.
    // We'll save ourselves some server time by checking
    // them first.

    include("./include/search_stopwords.inc.php");

    if (!$table_data = get_table_prefix()) return false;

    // Ensure the bare minimum of variables are set

    if (!isset($argarray['method']) || !is_numeric($argarray['method'])) $argarray['method'] = 1;
    if (!isset($argarray['date_from']) || !is_numeric($argarray['date_from'])) $argarray['date_from'] = 7;
    if (!isset($argarray['date_to']) || !is_numeric($argarray['date_to'])) $argarray['date_to'] = 2;
    if (!isset($argarray['order_by']) || !is_numeric($argarray['order_by'])) $argarray['order_by'] = 1;
    if (!isset($argarray['group_by_thread']) || !is_numeric($argarray['group_by_thread'])) $argarray['group_by_thread'] = "N";
    if (!isset($argarray['sstart']) || !is_numeric($argarray['sstart'])) $argarray['sstart'] = 0;
    if (!isset($argarray['fid']) || !is_numeric($argarray['fid'])) $argarray['fid'] = 0;
    if (!isset($argarray['include']) || !is_numeric($argarray['include'])) $argarray['include'] = 2;
    if (!isset($argarray['username']) || strlen(trim($argarray['username'])) < 1) $argarray['username'] = "";
    if (!isset($argarray['user_include']) || !is_numeric($argarray['user_include'])) $argarray['user_include'] = 1;
    if (!isset($argarray['forums']) || !is_numeric($argarray['forums'])) $argarray['forums'] = $table_data['FID'];

    $search_min_word_length = intval(forum_get_setting('search_min_word_length', false, 3));

    $db_search_execute = db_connect();

    $uid = bh_session_get_value('UID');

    $forum_settings = forum_get_settings();

    if ($argarray['forums'] == 0 && $forum_fids = forum_get_all_fids()) {
        $argarray['forums'] = implode(",", $forum_fids);
    }

    $sql = "SELECT SEARCH_MATCH.FID, SEARCH_MATCH.TID, SEARCH_MATCH.PID, ";
    $sql.= "SEARCH_MATCH.BY_UID, SEARCH_MATCH.FROM_UID, SEARCH_MATCH.TO_UID, ";
    $sql.= "UNIX_TIMESTAMP(SEARCH_MATCH.CREATED) AS CREATED ";
    $sql.= "FROM SEARCH_KEYWORDS SEARCH_KEYWORDS ";
    $sql.= "LEFT JOIN SEARCH_MATCH SEARCH_MATCH ";
    $sql.= "ON (SEARCH_MATCH.WID = SEARCH_KEYWORDS.WID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ";
    $sql.= "ON (USER_PEER.PEER_UID = SEARCH_MATCH.BY_UID AND USER_PEER.UID = '$uid') ";
    $sql.= "WHERE SEARCH_MATCH.FORUM IN ({$argarray['forums']}) ";
    $sql.= "AND ((USER_PEER.RELATIONSHIP & ". USER_IGNORED_COMPLETELY. ") = 0 ";
    $sql.= "OR USER_PEER.RELATIONSHIP IS NULL) ";
    $sql.= "AND ((USER_PEER.RELATIONSHIP & ". USER_IGNORED. ") = 0 ";
    $sql.= "OR USER_PEER.RELATIONSHIP IS NULL) ";

    $folders = folder_get_available();

    if (isset($argarray['fid']) && in_array($argarray['fid'], explode(",", $folders))) {
        $sql.= "AND SEARCH_MATCH.FID = {$argarray['fid']} ";
    }else{
        $sql.= "AND SEARCH_MATCH.FID IN ($folders) ";
    }

    $sql.= search_date_range($argarray['date_from'], $argarray['date_to']);

    if (isset($argarray['username']) && strlen(trim($argarray['username'])) > 0) {

        if ($user_uid = user_get_uid($argarray['username'])) {

            if ($argarray['user_include'] == 1) {

                $sql.= "AND SEARCH_MATCH.FROM_UID = '{$user_uid['UID']}'";

            }elseif ($argarray['user_include'] == 2) {

                $sql.= "AND SEARCH_MATCH.TO_UID = '{$user_uid['UID']}'";

            }else {

                $sql.= "AND (SEARCH_MATCH.FROM_UID = '{$user_uid['UID']}' ";
                $sql.= "OR SEARCH_MATCH.TO_UID = '{$user_uid['UID']}')";
            }

        }else {

            $error = SEARCH_USER_NOT_FOUND;
            return false;
        }
    }

    if (strlen(trim($argarray['search_string'])) > 0) {

        // Filter the input so the user can't do anything dangerous with it

        $argarray['search_string'] = str_replace("%", "", $argarray['search_string']);
        $argarray['search_string'] = _htmlentities($argarray['search_string']);

        // Remove any keywords which are under the minimum length.

        $keywords_array = explode(' ', trim($argarray['search_string']));

        foreach ($keywords_array as $key => $value) {

            if (strlen($value) < $search_min_word_length || strlen($value) > 64 || _in_array($value, $mysql_fulltext_stopwords)) {
                unset($keywords_array[$key]);
            }else {
                $keywords_array[$key] = strtolower($value);
            }
        }

        if (sizeof($keywords_array) > 0) {

            if ($argarray['method'] == 1) { // AND

                $sql.= "AND (SEARCH_KEYWORDS.WORD = '";
                $sql.= implode("' AND SEARCH_KEYWORDS.WORD = '", $keywords_array);
                $sql.= "') ";

            }elseif ($argarray['method'] == 2) { // OR

                $sql.= "AND (SEARCH_KEYWORDS.WORD = '";
                $sql.= implode("' OR SEARCH_KEYWORDS.WORD = '", $keywords_array);
                $sql.= "') ";
            }

        }elseif (!isset($argarray['username']) || strlen(trim($argarray['username'])) < 1) {

            $error = SEARCH_NO_KEYWORDS;
            return false;
        }

    }else {

        if (!isset($argarray['username']) || strlen(trim($argarray['username'])) < 1) {

            $error = SEARCH_NO_KEYWORDS;
            return false;
        }
    }

    if (isset($argarray['group_by_thread']) && $argarray['group_by_thread'] == 'Y') {
        $sql.= "GROUP BY SEARCH_MATCH.TID ";
    }

    if ($argarray['order_by'] == 1) {

        $sql.= "ORDER BY SEARCH_MATCH.CREATED DESC ";

    }elseif($argarray['order_by'] == 2) {

        $sql.= "ORDER BY SEARCH_MATCH.CREATED ";
    }

    $sql.= "LIMIT {$argarray['sstart']}, 50";
    $sql = preg_replace("/ +/", " ", $sql);

    echo $sql; exit;

    $result = db_query($sql, $db_search_execute);

    $uriquery = "";

    foreach($argarray as $key => $value) {
        $uriquery.= "&amp;$key=$value";
    }

    if (db_num_rows($result) > 0) {

        $search_results_array = array();

        while ($row = db_fetch_array($result)) {

            $search_results_array[] = $row;
        }

        return $search_results_array;

    }else {

        $error = SEARCH_NO_MATCHES;
        return false;
    }
}

function search_date_range($from, $to)
{
    $year  = date('Y', mktime());
    $month = date('n', mktime());
    $day   = date('j', mktime());

    $range = "";

    switch($from) {

      case 1:  // Today

        $from_timestamp = mktime(0, 0, 0, $month, $day, $year);
        break;

      case 2:  // Yesterday

        $from_timestamp = mktime(0, 0, 0, $month, $day - 1, $year);
        break;

      case 3:  // Day before yesterday

        $from_timestamp = mktime(0, 0, 0, $month, $day - 2, $year);
        break;

      case 4:  // 1 week ago

        $from_timestamp = mktime(0, 0, 0, $month, $day - 7, $year);
        break;

      case 5:  // 2 weeks ago

        $from_timestamp = mktime(0, 0, 0, $month, $day - 14, $year);
        break;

      case 6:  // 3 weeks ago

        $from_timestamp = mktime(0, 0, 0, $month, $day - 21, $year);
        break;

      case 7:  // 1 month ago

        $from_timestamp = mktime(0, 0, 0, $month - 1, $day, $year);
        break;

      case 8:  // 2 months ago

        $from_timestamp = mktime(0, 0, 0, $month - 2, $day, $year);
        break;

      case 9:  // 3 months ago

        $from_timestamp = mktime(0, 0, 0, $month - 3, $day, $year);
        break;

      case 10: // 6 months ago

        $from_timestamp = mktime(0, 0, 0, $month - 6, $day, $year);
        break;

      case 11: // 1 year ago

        $from_timestamp = mktime(0, 0, 0, $month, $day, $year - 1);
        break;

    }

    switch($to) {

      case 1:  // Now

        $to_timestamp = mktime();
        break;

      case 2:  // Today

        $to_timestamp = mktime(23, 59, 59, $month, $day, $year);
        break;

      case 3:  // Yesterday

        $to_timestamp = mktime(23, 59, 59, $month, $day - 1, $year);
        break;

      case 4:  // Day before yesterday

        $to_timestamp = mktime(23, 59, 59, $month, $day - 2, $year);
        break;

      case 5:  // 1 week ago

        $to_timestamp = mktime(23, 59, 59, $month, $day - 7, $year);
        break;

      case 6:  // 2 weeks ago

        $to_timestamp = mktime(23, 59, 59, $month, $day - 14, $year);
        break;

      case 7:  // 3 weeks ago

        $to_timestamp = mktime(23, 59, 59, $month, $day - 21, $year);
        break;

      case 8:  // 1 month ago

        $to_timestamp = mktime(23, 59, 59, $month - 1, $day, $year);
        break;

      case 9:  // 2 months ago

        $to_timestamp = mktime(23, 59, 59, $month - 2, $day, $year);
        break;

      case 10: // 3 months ago

        $to_timestamp = mktime(23, 59, 59, $month - 3, $day, $year);
        break;

      case 11: // 6 months ago

        $to_timestamp = mktime(23, 59, 59, $month - 6, $day, $year);
        break;

      case 12: // 1 year ago

        $to_timestamp = mktime(23, 59, 59, $month, $day, $year - 1);
        break;

    }

    if (isset($from_timestamp)) $range = "AND SEARCH_MATCH.CREATED >= FROM_UNIXTIME($from_timestamp) ";
    if (isset($to_timestamp)) $range.= "AND SEARCH_MATCH.CREATED <= FROM_UNIXTIME($to_timestamp) ";

    return $range;
}

function forum_search_dropdown()
{
    $lang = load_language_file();

    $db_forum_search_dropdown = db_connect();

    $uid = bh_session_get_value('UID');

    if (!$table_data = get_table_prefix()) return false;

    $forum_fid = $table_data['FID'];

    $sql = "SELECT FORUMS.FID, FORUM_SETTINGS.SVALUE FROM FORUMS FORUMS ";
    $sql.= "LEFT JOIN FORUM_SETTINGS FORUM_SETTINGS ON (FORUM_SETTINGS.FID = FORUMS.FID ";
    $sql.= "AND FORUM_SETTINGS.SNAME = 'forum_name') ";
    $sql.= "LEFT JOIN USER_FORUM USER_FORUM ON (USER_FORUM.FID = FORUMS.FID) ";
    $sql.= "WHERE FORUMS.ACCESS_LEVEL = 0 OR FORUMS.ACCESS_LEVEL = 2 ";
    $sql.= "OR (FORUMS.ACCESS_LEVEL = 1 AND USER_FORUM.ALLOWED = 1) ";

    $result = db_query($sql, $db_forum_search_dropdown);

    if (db_num_rows($result) > 0) {

        $forums_array[0] = $lang['all_caps'];

        while($row = db_fetch_array($result)) {

            $forums_array[$row['FID']] = $row['SVALUE'];
        }

        return form_dropdown_array("forums", array_keys($forums_array), array_values($forums_array), $forum_fid, false, "search_dropdown");
    }

    return false;
}

function folder_search_dropdown()
{
    $lang = load_language_file();

    $db_folder_search_dropdown = db_connect();

    $uid = bh_session_get_value('UID');

    if (!$table_data = get_table_prefix()) return false;

    $forum_fid = $table_data['FID'];

    $folders['FIDS'] = array();
    $folders['TITLES'] = array();

    $access_allowed = USER_PERM_POST_READ;

    $sql = "SELECT FOLDER.FID, FOLDER.TITLE, ";
    $sql.= "BIT_OR(GROUP_PERMS.PERM) AS USER_STATUS, ";
    $sql.= "COUNT(GROUP_PERMS.GID) AS USER_PERM_COUNT, ";
    $sql.= "BIT_OR(FOLDER_PERMS.PERM) AS FOLDER_PERMS, ";
    $sql.= "COUNT(FOLDER_PERMS.PERM) AS FOLDER_PERM_COUNT ";
    $sql.= "FROM {$table_data['PREFIX']}FOLDER FOLDER ";
    $sql.= "LEFT JOIN GROUP_USERS GROUP_USERS ON (GROUP_USERS.UID = '$uid') ";
    $sql.= "LEFT JOIN GROUP_PERMS GROUP_PERMS ON (GROUP_PERMS.FID = FOLDER.FID ";
    $sql.= "AND GROUP_PERMS.GID = GROUP_USERS.GID AND GROUP_PERMS.FORUM IN (0, $forum_fid)) ";
    $sql.= "LEFT JOIN GROUP_PERMS FOLDER_PERMS ON (FOLDER_PERMS.FID = FOLDER.FID ";
    $sql.= "AND FOLDER_PERMS.GID = 0 AND FOLDER_PERMS.FORUM IN (0, $forum_fid)) ";
    $sql.= "GROUP BY FOLDER.FID ";
    $sql.= "ORDER BY FOLDER.FID";

    $result = db_query($sql, $db_folder_search_dropdown);

    if (db_num_rows($result) > 0) {

        while($row = db_fetch_array($result)) {

            if (($row['FOLDER_PERMS'] & USER_PERM_GUEST_ACCESS) > 0 || !user_is_guest()) {

                if ($row['USER_PERM_COUNT'] > 0 && ($row['USER_STATUS'] & $access_allowed) > 0) {

                    $folders['FIDS'][] = $row['FID'];
                    $folders['TITLES'][] = $row['TITLE'];

                }elseif ($row['FOLDER_PERM_COUNT'] > 0 && ($row['FOLDER_PERMS'] & $access_allowed) > 0) {

                    $folders['FIDS'][] = $row['FID'];
                    $folders['TITLES'][] = $row['TITLE'];

                }elseif ($row['FOLDER_PERM_COUNT'] == 0 && $row['USER_PERM_COUNT'] == 0) {

                    $folders['FIDS'][] = $row['FID'];
                    $folders['TITLES'][] = $row['TITLE'];
                }
            }
        }

        if (sizeof($folders['FIDS']) > 0 && sizeof($folders['TITLES']) > 0) {

            array_unshift($folders['FIDS'], 0);
            array_unshift($folders['TITLES'], $lang['all_caps']);

            return form_dropdown_array("fid", $folders['FIDS'], $folders['TITLES'], 0, false, "search_dropdown");
        }
    }

    return false;
}

function search_draw_user_dropdown($name)
{
    $lang = load_language_file();

    $db_search_draw_user_dropdown = db_connect();

    $uid = bh_session_get_value('UID');

    if (!$table_data = get_table_prefix()) return "";

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, ";
    $sql.= "UNIX_TIMESTAMP(VISITOR_LOG.LAST_LOGON) AS LAST_LOGON FROM USER USER ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}VISITOR_LOG VISITOR_LOG ON ";
    $sql.= "(USER.UID = VISITOR_LOG.UID) WHERE USER.UID <> '$uid' ";
    $sql.= "ORDER BY VISITOR_LOG.LAST_LOGON DESC ";
    $sql.= "LIMIT 0, 20";

    $result = db_query($sql, $db_search_draw_user_dropdown);

    $uids[]  = 0;
    $names[] = $lang['all_caps'];

    if ($uid > 0) {

        $uids[]  = $uid;
        $names[] = $lang['me_caps'];
    }

    while($row = db_fetch_array($result)) {

      $uids[]  = $row['UID'];
      $names[] = format_user_name($row['LOGON'], $row['NICKNAME']);

    }

    return form_dropdown_array($name, $uids, $names, 0, false, "search_dropdown");
}

function search_index_old_post()
{
    $db_search_index_old_post = db_connect();

    if (!$table_data = get_table_prefix()) return false;

    $sql = "SELECT THREAD.FID, POST.TID, POST.PID, THREAD.BY_UID, POST.FROM_UID, ";
    $sql.= "POST.TO_UID, POST_CONTENT.CONTENT, UNIX_TIMESTAMP(POST.CREATED) AS CREATED ";
    $sql.= "FROM {$table_data['PREFIX']}POST_CONTENT POST_CONTENT ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}POST POST ";
    $sql.= "ON (POST.TID = POST_CONTENT.TID AND POST.PID = POST_CONTENT.PID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}THREAD THREAD ";
    $sql.= "ON (THREAD.TID = POST_CONTENT.TID) ";
    $sql.= "WHERE POST_CONTENT.INDEXED = 0 LIMIT 0, 1";

    $result = db_query($sql, $db_search_index_old_post);

    if (db_num_rows($result) > 0) {

        list($fid, $tid, $pid, $by_uid, $fuid, $tuid, $content, $created) = db_fetch_array($result, DB_RESULT_NUM);

        $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}POST_CONTENT SET INDEXED = 1 ";
        $sql.= "WHERE TID = '$tid' AND PID = '$pid'";

        $result = db_query($sql, $db_search_index_old_post);

        return search_index_post($fid, $tid, $pid, $by_uid, $fuid, $tuid, $content, $created);
    }

    return false;
}

function search_index_post($fid, $tid, $pid, $by_uid, $fuid, $tuid, $content, $created = 0)
{
    $db_search_index_post = db_connect();

    include("./include/search_stopwords.inc.php");

    if (!is_numeric($fid)) return false;
    if (!is_numeric($tid)) return false;
    if (!is_numeric($pid)) return false;
    if (!is_numeric($fuid)) return false;
    if (!is_numeric($tuid)) return false;

    if (!is_numeric($created)) $created = "NOW()";

    if (!$table_data = get_table_prefix()) return false;

    $forum_fid = $table_data['FID'];

    $search_min_word_length = intval(forum_get_setting('search_min_word_length', false, 3));

    // Tidy the content up (remove URLs, new lines, HTML and invalid chars)

    $drop_char_match = array("/\^/", "/\$/", "/&/", "/\(/", "/\)/", "/\</",
                             "/\>/", "/`/", "/\"/", "/\|/", "/,/", "/@/",
                             "/_/", "/\?/", "/%/", "/-/", "/~/", "/\+/",
                             "/\./", "/\[/", "/\]/", "/\{/", "/\}/",
                             "/\:/", "/\\\/", "/\//", "/\=/", "/#/",
                             "/'/", "/;/", "/\!/");

    $content = preg_replace("/[\n\r]/is", " ", strip_tags($content));
    $content = preg_replace("/&[a-z]+;/", " ", $content);
    $content = preg_replace("/[a-z0-9]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/]+)?/", " ", $content);
    $content = preg_replace($drop_char_match, " ", $content);
    $content = preg_replace("/ +/", " ", $content);

    preg_match_all("/([\w']+)/i", $content, $content_array);

    $content_array = $content_array[0];

    $keyword_array = array();
    $keyword_query = array();

    foreach ($content_array as $key => $keyword_add) {

        $keyword_add = trim(strtolower($keyword_add));
        $keyword_sql = addslashes(trim(strtolower($keyword_add)));

        if (strlen($keyword_add) > ($search_min_word_length - 1) && strlen($keyword_add) < 50 && !_in_array($keyword_add, $mysql_fulltext_stopwords)) {

            if (!_in_array($keyword_add, $keyword_array)) {

                $keyword_array[] = $keyword_add;
                $keyword_query[] = "('$keyword_sql')";
            }
        }
    }

    if (sizeof($keyword_query) > 0) {

        $sql_values = implode(", ", $keyword_query);
        $keyword_list = implode("', '", $keyword_array);

        $sql = "INSERT IGNORE INTO SEARCH_KEYWORDS ";
        $sql.= "(WORD) VALUES $sql_values ";

        $result = db_query($sql, $db_search_index_post);

        $sql = "INSERT IGNORE INTO SEARCH_MATCH (WID, FORUM, FID, TID, PID, BY_UID, FROM_UID, TO_UID, CREATED) ";
        $sql.= "SELECT WID, $forum_fid, $fid, $tid, $pid, $by_uid, $fuid, $tuid, $created FROM ";
        $sql.= "SEARCH_KEYWORDS WHERE WORD IN ('$keyword_list')";

        return db_query($sql, $db_search_index_post);
    }

    return false;
}

?>