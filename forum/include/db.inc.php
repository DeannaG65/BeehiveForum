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

/* $Id: db.inc.php,v 1.64 2005-03-09 23:26:52 decoyduck Exp $ */

if (@file_exists("./include/config.inc.php")) {
    include_once("./include/config.inc.php");
}

include_once("./include/constants.inc.php");
include_once("./include/server.inc.php");

// Connects to the database and returns the connection ID

function db_connect ()
{
    global $db_server, $db_username, $db_password, $db_database, $show_friendly_errors;

    static $connection_id = false;

    if (@extension_loaded('mysql')) {

        if (!$connection_id) {

            if (@$connection_id = mysql_connect($db_server, $db_username, $db_password)) {

                if (@mysql_select_db($db_database, $connection_id)) {

                    db_enable_big_selects($connection_id);
                    return $connection_id;

                }else {

                    trigger_error(DB_ER_NO_SUCH_DBASE, E_USER_ERROR);
                }

            }else {

                trigger_error(DB_ER_NO_SUCH_HOST, E_USER_ERROR);
            }
        }

        return $connection_id;
    }

    if (@extension_loaded('mysqli')) {

        if (!$connection_id) {

            if (@$connection_id = mysqli_connect($db_server, $db_username, $db_password)) {

                if (@mysqli_select_db($connection_id, $db_database)) {

                    db_enable_big_selects($connection_id);
                    return $connection_id;

                }else {

                    trigger_error(DB_ER_NO_SUCH_DBASE, E_USER_ERROR);
                }

            }else {

                 trigger_error(DB_ER_NO_SUCH_HOST, E_USER_ERROR);
            }
        }

        return $connection_id;
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
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

// Executes a query on the database and returns a resource ID

function db_query ($sql, $connection_id)
{
    if (@extension_loaded('mysql')) {

        if ($result = mysql_query($sql, $connection_id)) {

            return $result;

        }else {

            $mysql_error = mysql_error($connection_id);
            trigger_error("<p>SQL: $sql</p><p>MySQL Said: $mysql_error</p>", E_USER_ERROR);
        }
    }

    if (@extension_loaded('mysqli')) {

        if ($result = mysqli_query($connection_id, $sql)) {

            return $result;

        }else {

            $mysql_error = mysqli_error($connection_id);
            trigger_error("<p>SQL: $sql</p><p>MySQL Said: $mysql_error</p>", E_USER_ERROR);
        }
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
}

// Executes a query on the database and returns a resource ID

function db_unbuffered_query ($sql, $connection_id)
{
    if (@extension_loaded('mysql')) {

        if (function_exists("mysql_unbuffered_query")) {

            if ($result = mysql_unbuffered_query($sql, $connection_id)) {

                return $result;

            }else {

                $mysql_error = mysql_error($connection_id);
                trigger_error("<p>SQL: $sql</p><p>MySQL Said: $mysql_error</p>", E_USER_ERROR);
            }

        }else {

            $result = db_query($sql, $connection_id);
            return $result;
        }
    }

    if (@extension_loaded('mysqli')) {

        $result = db_query($sql, $connection_id);
        return $result;
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
}

// Returns the number of rows affected by a SELECT query when passed the resource ID
function db_num_rows ($result)
{
    if (@extension_loaded('mysql')) {

        $num_rows = mysql_num_rows($result);
        return $num_rows;
    }

    if (@extension_loaded('mysqli')) {

        $num_rows = mysqli_num_rows($result);
        return $num_rows;
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
}

// Returns the number of rows affected by a query when passed the connection ID
function db_affected_rows($connection_id)
{
    if (@extension_loaded('mysql')) {

        $results = mysql_affected_rows($connection_id);
        return $results;
    }

    if (@extension_loaded('mysqli')) {

        $results = mysqli_affected_rows($connection_id);
        return $results;
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
}

function db_fetch_array ($result, $result_type = DB_RESULT_BOTH)
{
    if (@extension_loaded('mysql')) {

        $results = mysql_fetch_array($result, $result_type);
        return $results;
    }

    if (@extension_loaded('mysqli')) {

       $results = mysqli_fetch_array($result, $result_type);
       return $results;
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
}

// Seeks to the specified row in a SELECT query (0 based)
function db_data_seek ($result, $row_number)
{
    if (@extension_loaded('mysql')) {

        $seek_result = @mysql_data_seek($result, $row_number);
        return $seek_result;
    }

    if (@extension_loaded('mysqli')) {

        $seek_result = @mysqli_data_seek($result, $row_number);
        return $seek_result;
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
}

// Returns the AUTO_INCREMENT ID from the last insert statement
function db_insert_id($result)
{
    if (@extension_loaded('mysql')) {

        $insert_id = mysql_insert_id($result);
        return $insert_id;
    }

    if (@extension_loaded('mysqli')) {

        $insert_id = mysqli_insert_id($result);
        return $insert_id;
    }

    trigger_error(DB_ER_NO_EXTENSION, E_USER_ERROR);
}

function db_error($result)
{
    if (@extension_loaded('mysql')) {

        return mysql_error($result);
    }

    if (@extension_loaded('mysqli')) {

        return mysqli_error($result);
    }

    return "Error unknown";
}

function db_errno($result)
{
    if (@extension_loaded('mysql')) {

        return mysql_errno($result);
    }

    if (@extension_loaded('mysqli')) {

        return mysqli_errno($result);
    }

    return 0;
}

// Return the MySQL Server Version.
// Adapted from phpMyAdmin (ahem!)

function db_fetch_mysql_version()
{
    static $mysql_version = false;

    if (!$mysql_version) {

        $db_fetch_mysql_version = db_connect();

        $sql = "SELECT VERSION() AS version";
        $result = @db_query($sql, $db_fetch_mysql_version);

        if (!$row = db_fetch_array($result)) {

            $sql = "SHOW VARIABLES LIKE 'version'";
            $result = @db_query($sql, $db_fetch_mysql_version);

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
