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

/* $Id: stats.inc.php,v 1.52 2005-04-11 22:35:42 decoyduck Exp $ */

include_once(BH_INCLUDE_PATH. "forum.inc.php");

function update_stats()
{
    $db_update_stats = db_connect();

    if (!$table_data = get_table_prefix()) return false;

    $num_sessions = get_num_sessions();
    $num_recent_posts = get_recent_post_count();

    $sql = "SELECT * FROM {$table_data['PREFIX']}STATS ";
    $sql.= "ORDER BY ID DESC LIMIT 0, 1";

    $result = db_query($sql, $db_update_stats);

    if (db_num_rows($result) > 0) {

        $stats_array = db_fetch_array($result);

        if ($num_sessions > $stats_array['MOST_USERS_COUNT']) {

            $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}STATS SET ";
            $sql.= "MOST_USERS_DATE = NOW(), MOST_USERS_COUNT = $num_sessions";

            $result = db_query($sql, $db_update_stats);
        }

        if ($num_recent_posts > $stats_array['MOST_POSTS_COUNT']) {

            $sql = "UPDATE LOW_PRIORITY {$table_data['PREFIX']}STATS SET ";
            $sql.= "MOST_POSTS_DATE = NOW(), MOST_POSTS_COUNT = $num_recent_posts";

            $result = db_query($sql, $db_update_stats);
        }

    }else {

        $sql = "INSERT LOW_PRIORITY INTO {$table_data['PREFIX']}STATS (MOST_USERS_DATE, ";
        $sql.= "MOST_USERS_COUNT, MOST_POSTS_DATE, MOST_POSTS_COUNT) ";
        $sql.= "VALUES (NOW(), '$num_sessions', NOW(), '$num_recent_posts')";

        $result = db_query($sql, $db_update_stats);

    }
}

function get_num_sessions()
{
    $get_num_sessions = db_connect();

    if (!$table_data = get_table_prefix()) return 0;

    $sessions_array = array();

    $session_stamp = time() - intval(forum_get_setting('active_sess_cutoff', false, 900));

    $sql = "SELECT COUNT(UID) AS USER_COUNT FROM SESSIONS ";
    $sql.= "WHERE TIME >= FROM_UNIXTIME($session_stamp) ";
    $sql.= "AND FID = '{$table_data['FID']}'";

    $result = db_query($sql, $get_num_sessions);

    if (db_num_rows($result) > 0) {

        list($user_count) = db_fetch_array($result, DB_RESULT_NUM);
        return $user_count;
    }

    return 0;
}

function get_active_users()
{
    $db_get_active_users = db_connect();

    $stats = array('GUESTS' => 0, 'NUSERS' => 0,
                   'AUSERS' => 0, 'USERS'  => array());

    if (!$table_data = get_table_prefix()) return $stats;

    $session_stamp = time() - intval(forum_get_setting('active_sess_cutoff', false, 900));

    $uid = bh_session_get_value('UID');

    // Current active users

    $sql = "SELECT SESSIONS.UID, USER.LOGON, USER.NICKNAME, ";
    $sql.= "USER_PREFS_GLOBAL.ANON_LOGON AS ANON_LOGON_GLOBAL, ";
    $sql.= "USER_PREFS.ANON_LOGON, USER_PEER.RELATIONSHIP FROM SESSIONS SESSIONS ";
    $sql.= "LEFT JOIN USER USER ON (USER.UID = SESSIONS.UID) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PEER USER_PEER ON (USER_PEER.UID = SESSIONS.UID AND USER_PEER.PEER_UID = $uid) ";
    $sql.= "LEFT JOIN {$table_data['PREFIX']}USER_PREFS USER_PREFS ON (USER_PREFS.UID = SESSIONS.UID) ";
    $sql.= "LEFT JOIN USER_PREFS USER_PREFS_GLOBAL ON (USER_PREFS_GLOBAL.UID = SESSIONS.UID) ";
    $sql.= "WHERE SESSIONS.TIME >= FROM_UNIXTIME($session_stamp) AND SESSIONS.FID = '{$table_data['FID']}' ";
    $sql.= "GROUP BY SESSIONS.UID ORDER BY USER.NICKNAME";

    $result = db_query($sql, $db_get_active_users);

    while ($row = db_fetch_array($result)) {

        $anon_logon = 0;

        if (isset($row['ANON_LOGON']) && $row['ANON_LOGON'] > 0) {
            $anon_logon = $row['ANON_LOGON'];
        }

        if (isset($row['ANON_LOGON_GLOBAL']) && $row['ANON_LOGON_GLOBAL'] > 0) {
            $anon_logon = $row['ANON_LOGON_GLOBAL'];
        }

        if ($row['UID'] == 0) {

            $stats['GUESTS']++;

        }else {

            if ($anon_logon > 0) {

                $stats['AUSERS']++;

            }else {

                $stats['NUSERS']++;
            }

            if ($anon_logon == 0 || $row['UID'] == $uid || (($row['RELATIONSHIP'] & USER_FRIEND) > 0 && $anon_logon == 2)) {

                $stats['USERS'][$row['UID']] = array('UID'      => $row['UID'],
                                                     'LOGON'    => $row['LOGON'],
                                                     'NICKNAME' => $row['NICKNAME']);
            }
        }
    }

    return $stats;
}

function get_thread_count()
{
    $db_get_thread_count = db_connect();

    if (!$table_data = get_table_prefix()) return 0;

    $sql = "SELECT COUNT(THREAD.TID) AS THREAD_COUNT ";
    $sql.= "FROM {$table_data['PREFIX']}THREAD THREAD";

    $result = db_query($sql, $db_get_thread_count);

    if (db_num_rows($result) > 0) {

        list($thread_count) = db_fetch_array($result, DB_RESULT_NUM);
        return $thread_count;
    }

    return 0;
}

function get_post_count()
{
    $db_get_post_count = db_connect();

    $table_data = get_table_prefix();

    $sql = "SELECT COUNT(POST.PID) AS POST_COUNT FROM {$table_data['PREFIX']}POST POST";
    $result = db_query($sql, $db_get_post_count);

    if (db_num_rows($result) > 0) {

        list($post_count) = db_fetch_array($result, DB_RESULT_NUM);
        return $post_count;
    }

    return 0;
}

function get_recent_post_count()
{
    $db_get_post_count = db_connect();

    $table_data = get_table_prefix();

    $post_stamp = time() - HOUR_IN_SECONDS;

    $sql = "SELECT COUNT(POST.PID) AS POSTS FROM {$table_data['PREFIX']}POST POST ";
    $sql.= "WHERE CREATED >= FROM_UNIXTIME($post_stamp)";

    $result = db_query($sql, $db_get_post_count);

    if (db_num_rows($result) > 0) {

        list($post_count) = db_fetch_array($result, DB_RESULT_NUM);
        return $post_count;
    }

    return 0;
}

function get_longest_thread()
{
    $db_get_longest_thread = db_connect();

    $table_data = get_table_prefix();

    $sql = "SELECT THREAD.TITLE, THREAD.TID, THREAD.LENGTH FROM {$table_data['PREFIX']}THREAD THREAD ";
    $sql.= "ORDER BY THREAD.LENGTH DESC LIMIT 0, 1";

    $result = db_query($sql, $db_get_longest_thread);

    if (db_num_rows($result) > 0) {

        $row = db_fetch_array($result);
        return $row;
    }

    return false;
}

function get_most_users()
{
    $db_get_most_users = db_connect();

    $table_data = get_table_prefix();

    $sql = "SELECT MOST_USERS_COUNT, UNIX_TIMESTAMP(MOST_USERS_DATE) AS MOST_USERS_DATE ";
    $sql.= "FROM {$table_data['PREFIX']}STATS";

    $result = db_query($sql, $db_get_most_users);

    if (db_num_rows($result) > 0) {

        $row = db_fetch_array($result);
        return $row;
    }

    return false;
}

function get_most_posts()
{
    $db_get_most_posts = db_connect();

    $table_data = get_table_prefix();

    $sql = "SELECT MOST_POSTS_COUNT, UNIX_TIMESTAMP(MOST_POSTS_DATE) AS MOST_POSTS_DATE ";
    $sql.= "FROM {$table_data['PREFIX']}STATS";

    $result = db_query($sql, $db_get_most_posts);

    if (db_num_rows($result) > 0) {

        $row = db_fetch_array($result);
        return $row;
    }

    return false;
}

function get_newest_user()
{
    $db_get_newest_user = db_connect();

    $table_data = get_table_prefix();

    $sql = "SELECT UID, LOGON, NICKNAME FROM USER ";
    $sql.= "ORDER BY UID DESC LIMIT 0, 1";

    $result = db_query($sql, $db_get_newest_user);

    if (db_num_rows($result) > 0) {

        $row = db_fetch_array($result);
        return $row;
    }

    return false;
}

function get_post_tallys($start_stamp, $end_stamp)
{
    $db_get_month_post_tallys = db_connect();

    if (!$table_data = get_table_prefix()) return false;

    $post_tallys = array('user_stats' => array(), 'post_count' => 0);

    $sql = "SELECT COUNT(POST.PID) AS TOTAL_POST_COUNT FROM {$table_data['PREFIX']}POST POST ";
    $sql.= "WHERE POST.CREATED > FROM_UNIXTIME($start_stamp) AND POST.CREATED < FROM_UNIXTIME($end_stamp)";

    $result = db_query($sql, $db_get_month_post_tallys);
    list($post_tallys['post_count']) = db_fetch_array($result, DB_RESULT_NUM);

    $sql = "SELECT USER.UID, USER.LOGON, USER.NICKNAME, COUNT(POST.PID) AS POST_COUNT ";
    $sql.= "FROM {$table_data['PREFIX']}POST POST LEFT JOIN USER USER ON (USER.UID = POST.FROM_UID) ";
    $sql.= "WHERE POST.CREATED >= FROM_UNIXTIME($start_stamp) AND POST.CREATED <= FROM_UNIXTIME($end_stamp) ";
    $sql.= "GROUP BY USER.UID ORDER BY POST_COUNT DESC ";
    $sql.= "LIMIT 0, 20";

    $result = db_query($sql, $db_get_month_post_tallys);

    if (db_num_rows($result) > 0) {

        while ($row = db_fetch_array($result)) {
            $post_tallys['user_stats'][] = $row;
        }
    }

    return $post_tallys;
}

?>