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

/* $Id: db_mysql.inc.php,v 1.6 2005-03-19 21:32:12 decoyduck Exp $ */

function db_connect()
{
    global $db_server, $db_username, $db_password, $db_database;

    static $connection_id = false;

    if (!$connection_id) {

        if ($connection_id = @mysql_connect($db_server, $db_username, $db_password)) {

            if (@mysql_select_db($db_database, $connection_id)) {

                db_enable_big_selects($connection_id);
                return $connection_id;
            }
        }

        trigger_error(mysql_error(), E_USER_ERROR);
    }

    return $connection_id;
}

function db_enable_big_selects($connection_id)
{
    global $mysql_big_selects;

    if (isset($mysql_big_selects) && $mysql_big_selects === true) {

        $sql = "SET OPTION SQL_BIG_SELECTS = 1";
        return db_query($sql, $connection_id);
    }

    return false;
}

function db_query($sql, $connection_id)
{
    if ($result = @mysql_query($sql, $connection_id)) {
        return $result;
    }

    db_trigger_error($sql, $connection_id);
}

function db_unbuffered_query($sql, $connection_id)
{
    if (function_exists("mysql_unbuffered_query")) {

        return @mysql_unbuffered_query($sql, $connection_id);

    }else {

        return db_query($sql, $connection_id);
    }
}

function db_num_rows($result)
{
    if ($num_rows = @mysql_num_rows($result)) {
        return $num_rows;
    }

    return 0;
}

function db_affected_rows($connection_id)
{
    if ($affected_rows = @mysql_affected_rows($connection_id)) {
        return $affected_rows;
    }

    return 0;
}

function db_fetch_array($result, $result_type = DB_RESULT_BOTH)
{
    if ($result_array = @mysql_fetch_array($result, $result_type)) {
        return $result_array;
    }

    return false;
}

function db_insert_id($result)
{
    if ($insert_id = @mysql_insert_id($result)) {
        return $insert_id;
    }

    return false;
}

function db_trigger_error($sql, $connection_id)
{
    $errstr = db_error($connection_id);
    trigger_error("$errstr\n\n$sql", E_USER_ERROR);
}

function db_error($connection_id)
{
    if ($errstr = @mysql_error($connection_id)) {
        return $errstr;
    }

    return "Unknown error";
}

function db_errno($result)
{
    if ($errno = @mysql_errno($result)) {
        return $errno;
    }

    return 0;
}

function db_fetch_mysql_version()
{
    static $mysql_version = false;

    if (!$mysql_version) {

        $db_fetch_mysql_version = db_connect();

        $sql = "SELECT VERSION() AS version";
        $result = db_query($sql, $db_fetch_mysql_version);

        if (!$row = db_fetch_array($result)) {

            $sql = "SHOW VARIABLES LIKE 'version'";
            $result = db_query($sql, $db_fetch_mysql_version);

            $row = db_fetch_array($result);
        }

        $version_array = explode(".", $row['version']);

        if (!isset($version_array) || !isset($version_array[0])) {
            $version_array[0] = 3;
        }

        if (!isset($version_array[1])) {
            $version_array[1] = 21;
        }

        if (!isset($version_array[2])) {
            $version_array[2] = 0;
        }

        $mysql_version = (int)sprintf('%d%02d%02d', $version_array[0], $version_array[1], intval($version_array[2]));
    }

    return $mysql_version;
}

?>
