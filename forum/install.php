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

/* $Id: install.php,v 1.35 2005-04-02 22:39:17 decoyduck Exp $ */

// Constant to define where the include files are
define("BH_INCLUDE_PATH", "./include/");

// Installation checking functions
include_once(BH_INCLUDE_PATH. "install.inc.php");

// Multiple forum support
include_once(BH_INCLUDE_PATH. "forum.inc.php");

if (@file_exists("./include/config.inc.php")) {
    include_once(BH_INCLUDE_PATH. "config.inc.php");
}

include_once(BH_INCLUDE_PATH. "constants.inc.php");
include_once(BH_INCLUDE_PATH. "db.inc.php");

if (isset($_GET['force_install']) && $_GET['force_install'] == 'yes') {
    $force_install = true;
}elseif (isset($_POST['force_install']) && $_POST['force_install'] == 'yes') {
    $force_install = true;
}else {
    $force_install = false;
}

if (isset($_POST['install_method']) && (!defined('BEEHIVE_INSTALED') || $force_install)) {

    $valid = true;
    $config_saved = false;

    $error_array = array();

    if (isset($_POST['install_method']) && strlen(trim(_stripslashes($_POST['install_method']))) > 0) {

        if (trim(_stripslashes($_POST['install_method']) == 'install')) {
            $install_method = 0;
        }else if (trim(_stripslashes($_POST['install_method']) == 'upgrade05')) {
            $install_method = 1;
        }else if (trim(_stripslashes($_POST['install_method']) == 'upgrade06')) {
            $install_method = 2;
        }else {
            $error_array[] = "You must choose an installation method.\n";
            $valid = false;
        }

    }else {

        $error_array[] = "You must choose an installation method.\n";
        $valid = false;
    }

    if (isset($_POST['forum_webtag']) && strlen(trim(_stripslashes($_POST['forum_webtag']))) > 0) {

        $forum_webtag = strtoupper(trim(_stripslashes($_POST['forum_webtag'])));

        if (!preg_match("/^[A-Z0-9_-]+$/", $forum_webtag)) {

            $error_array[] = "The forum webtag can only conatin uppercase A-Z, 0-9 and hyphen and underscore characters\n";
            $valid = false;
        }

    }else {

        if (isset($install_method) && $install_method < 2) {

            $error_array[] = "You must specify a forum webtag for this type of installation.\n";
            $valid = false;
        }
    }

    if (isset($_POST['db_server']) && strlen(trim(_stripslashes($_POST['db_server']))) > 0) {
        $db_server = trim(_stripslashes($_POST['db_server']));
    }else {
        $error_array[] = "You must supply the hostname of your MySQL database.\n";
        $valid = false;
    }

    if (isset($_POST['db_database']) && strlen(trim(_stripslashes($_POST['db_database']))) > 0) {
        $db_database = trim(_stripslashes($_POST['db_database']));
    }else {
        $error_array[] = "You must supply the name of your MySQL database.\n";
        $valid = false;
    }

    if (isset($_POST['db_username']) && strlen(trim(_stripslashes($_POST['db_username']))) > 0) {
        $db_username = trim(_stripslashes($_POST['db_username']));
    }else {
        $error_array[] = "You must enter your username for your MySQL database.\n";
        $valid = false;
    }

    if (isset($_POST['db_password']) && strlen(trim(_stripslashes($_POST['db_password']))) > 0) {
        $db_password = trim(_stripslashes($_POST['db_password']));
    }else {
        $error_array[] = "You must enter your password for your MySQL database.\n";
        $valid = false;
    }

    if (isset($_POST['db_cpassword']) && strlen(trim(_stripslashes($_POST['db_cpassword']))) > 0) {
        $db_cpassword = trim(_stripslashes($_POST['db_cpassword']));
    }else {
        $db_cpassword = "";
    }

    if (isset($install_method) && $install_method == 0) {

        if (isset($_POST['admin_username']) && strlen(trim(_stripslashes($_POST['admin_username']))) > 0) {
            $admin_username = trim(_stripslashes($_POST['admin_username']));
        }else {
            $error_array[] = "You must supply a username for your administrator account.\n";
            $valid = false;
        }

        if (isset($_POST['admin_password']) && strlen(trim(_stripslashes($_POST['admin_password']))) > 0) {
            $admin_password = trim(_stripslashes($_POST['admin_password']));
        }else {
            $error_array[] = "You must supply a password for your administrator account.\n";
            $valid = false;
        }

        if (isset($_POST['admin_cpassword']) && strlen(trim(_stripslashes($_POST['admin_cpassword']))) > 0) {
            $admin_cpassword = trim(_stripslashes($_POST['admin_cpassword']));
        }else {
            $admin_cpassword = "";
        }

        if (isset($_POST['admin_email']) && strlen(trim(_stripslashes($_POST['admin_email']))) > 0) {
            $admin_email = trim(_stripslashes($_POST['admin_email']));
        }else {
            $admin_email = "";
        }
    }

    if (isset($_POST['remove_conflicts']) && $_POST['remove_conflicts'] == 'Y') {
        $remove_conflicts = true;
    }else {
        $remove_conflicts = false;
    }

    if (isset($_POST['skip_dictionary']) && $_POST['skip_dictionary'] == 'Y') {
        $skip_dictionary = true;
    }else {
        $skip_dictionary = false;
    }

    if ($valid) {

        if ($install_method == 0 && ($admin_password != $admin_cpassword)) {
            $error_array[] = "Administrator account passwords do not match.\n";
            $valid = false;
        }

        if ($db_password != $db_cpassword) {
            $error_array[] = "MySQL database passwords do not match.\n";
            $valid = false;
        }
    }

    if ($valid) {

        if ($db_install = db_connect()) {

            if (($install_method == 2) && (@file_exists('./install/upgrade-05-to-06.php'))) {

                include_once("./install/upgrade-05-to-06.php");

            }elseif (($install_method == 1) && (@file_exists('./install/upgrade-04-to-05.php'))) {

                include_once("./install/upgrade-04-to-05.php");

            }elseif (($install_method == 0) && (@file_exists('./install/new-install.php'))) {

                include_once("./install/new-install.php");

            }else {

                $error_array[] = "Could not find the required script.\n";
                $valid = false;
            }

            if ($valid) {

                $config_file = "";

                if (@$fp = fopen('./install/config.inc.php', 'r')) {

                    while (!feof($fp)) {

                        $config_file.= fgets($fp, 100);
                    }

                    fclose($fp);

                    // Database details

                    $config_file = str_replace('{db_server}',   $db_server,   $config_file);
                    $config_file = str_replace('{db_username}', $db_username, $config_file);
                    $config_file = str_replace('{db_password}', $db_password, $config_file);
                    $config_file = str_replace('{db_database}', $db_database, $config_file);

                    // Constant that says we're installed.

                    $config_file = str_replace("// define('BEEHIVE_INSTALLED', 1);", "define('BEEHIVE_INSTALLED', 1);", $config_file);

                    if (@$fp = fopen("./include/config.inc.php", "w")) {

                        fwrite($fp, $config_file);
                        fclose($fp);

                        $config_saved = true;
                    }

                    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
                    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
                    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n";
                    echo "<head>\n";
                    echo "<title>BeehiveForum ", BEEHIVE_VERSION, " Installation</title>\n";
                    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
                    echo "<link rel=\"icon\" href=\"./images/favicon.ico\" type=\"image/ico\" />\n";
                    echo "<link rel=\"stylesheet\" href=\"./styles/style.css\" type=\"text/css\" />\n";
                    echo "</head>\n";
                    echo "<h1>BeehiveForum ", BEEHIVE_VERSION, " Installation</h1>\n";
                    echo "<br />\n";
                    echo "<div align=\"center\">\n";

                    if ($config_saved) {

                        echo "<form method=\"post\" action=\"./install.php\">\n";
                        echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
                        echo "    <tr>\n";
                        echo "      <td width=\"500\">\n";
                        echo "        <table class=\"box\" width=\"100%\">\n";
                        echo "          <tr>\n";
                        echo "            <td class=\"posthead\">\n";
                        echo "              <table class=\"posthead\" width=\"100%\">\n";
                        echo "                <tr>\n";
                        echo "                  <td class=\"subhead\">Installation Complete.</td>\n";
                        echo "                </tr>\n";
                        echo "                <tr>\n";
                        echo "                  <td>Installation of your Beehive Forum has completed successfully, but before you can use it you must delete both the install folder and install.php. Once this has been done you can click Continue below to start using your Beehive Forum.</td>\n";
                        echo "                </tr>\n";
                        echo "                <tr>\n";
                        echo "                  <td>&nbsp;</td>\n";
                        echo "                </tr>\n";
                        echo "                <tr>\n";
                        echo "                  <td width=\"500\"><span class=\"bhinputcheckbox\"><input type=\"checkbox\" name=\"install_remove_files\" id=\"install_remove_files\" value=\"Y\" checked=\"checked\"><label for=\"install_remove_files\">Attempt automatic removal of installation files (recommended)</label></span></td>\n";
                        echo "                </tr>\n";
                        echo "              </table>\n";
                        echo "            </td>\n";
                        echo "          </tr>\n";
                        echo "        </table>\n";
                        echo "      </td>\n";
                        echo "    </tr>\n";
                        echo "    <tr>\n";
                        echo "      <td>&nbsp;</td>\n";
                        echo "    </tr>\n";
                        echo "    <tr>\n";
                        echo "      <td align=\"center\"><input type=\"submit\" name=\"finish_install\" value=\"Continue\" class=\"button\" /></td>\n";
                        echo "    </tr>\n";
                        echo "  </table>\n";
                        echo "</form>\n";

                    }else {

                        echo "<form method=\"post\" action=\"install.php\">\n";
                        echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
                        echo "    <tr>\n";
                        echo "      <td width=\"500\">\n";
                        echo "        <table class=\"box\" width=\"100%\">\n";
                        echo "          <tr>\n";
                        echo "            <td class=\"posthead\">\n";
                        echo "              <table class=\"posthead\" width=\"100%\">\n";
                        echo "                <tr>\n";
                        echo "                  <td class=\"subhead\">Database Setup Completed</td>\n";
                        echo "                </tr>\n";
                        echo "                <tr>\n";
                        echo "                  <td>Your database has been succesfully setup for use with Beehive. However we were unable to apply the changes to your config.inc.php.</td>\n";
                        echo "                <tr>\n";
                        echo "                  <td>&nbsp;</td>\n";
                        echo "                </tr>\n";
                        echo "                <tr>\n";
                        echo "                  <td>Don't worry this is can be perfectly normal on some systems. In order to complete the installation you will need to download the config data by clicking the 'Download Config' button below to save the config.inc.php to your hard disk drive. From there you will need to upload it to your server, into Beehive's 'include' folder. Once this is done you can click the Continue button below to start using your Beehive Forum.</td>\n";
                        echo "                </tr>\n";
                        echo "                <tr>\n";
                        echo "                  <td>&nbsp;</td>\n";
                        echo "                </tr>\n";
                        echo "                <tr>\n";
                        echo "                  <td><span class=\"bhinputcheckbox\"><input type=\"checkbox\" name=\"install_remove_files\" id=\"install_remove_files\" value=\"Y\" checked=\"checked\"><label for=\"install_remove_files\">Attempt automatic removal of installation files (recommended)</label></span></td>\n";
                        echo "                </tr>\n";
                        echo "              </table>\n";
                        echo "            </td>\n";
                        echo "          </tr>\n";
                        echo "        </table>\n";
                        echo "      </td>\n";
                        echo "    </tr>\n";
                        echo "    <tr>\n";
                        echo "      <td width=\"500\">&nbsp;</td>\n";
                        echo "    </tr>\n";
                        echo "    <tr>\n";
                        echo "      <td align=\"center\">\n";
                        echo "        <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
                        echo "          <tr>\n";
                        echo "            <td width=\"55%\" align=\"right\">\n";
                        echo "              <input type=\"hidden\" name=\"db_server\" value=\"$db_server\">\n";
                        echo "              <input type=\"hidden\" name=\"db_username\" value=\"$db_username\">\n";
                        echo "              <input type=\"hidden\" name=\"db_password\" value=\"$db_password\">\n";
                        echo "              <input type=\"hidden\" name=\"db_database\" value=\"$db_database\">\n";
                        echo "              <input type=\"submit\" name=\"download_config\" value=\"Download Config\" class=\"button\" />&nbsp;\n";
                        echo "            </td>\n";
                        echo "            <td width=\"45%\">\n";
                        echo "              <input type=\"submit\" name=\"finish_install\" value=\"Continue\" class=\"button\" />\n";
                        echo "            </td>\n";
                        echo "          </tr>\n";
                        echo "        </table>\n";
                        echo "      </td>\n";
                        echo "    </tr>\n";
                        echo "  </table>\n";
                        echo "</form>\n";
                    }

                    echo "</div>\n";
                    echo "</body>\n";
                    echo "</html>\n";
                    exit;

                }else {

                    $error_array[] = "Could not complete installation. Error was: failed to read config.inc.php\n";
                    $valid = false;
                }

            }else {

                if (($errno = db_errno($db_install)) > 0) {

                    $error_array[] = "<h2>Could not complete installation. Error was: ". db_error($db_install). "</h2>\n";
                    $valid = false;
                }
            }

        }elseif ($valid) {

            $error_array[] = "Database connection to '$db_server' could not be established or permission is denied.\n";
            $valid = false;
        }
    }

}elseif (isset($_POST['download_config']) && !defined('BEEHIVE_INSTALLED')) {

    $config_file = "";

    if (@$fp = fopen('./install/config.inc.php', 'r')) {

        while (!feof($fp)) {

            $config_file.= fgets($fp, 100);
        }

        fclose($fp);

        if (isset($_POST['db_server']) && strlen(trim(_stripslashes($_POST['db_server']))) > 0) {
            $db_server = trim(_stripslashes($_POST['db_server']));
        }

        if (isset($_POST['db_database']) && strlen(trim(_stripslashes($_POST['db_database']))) > 0) {
            $db_database = trim(_stripslashes($_POST['db_database']));
        }

        if (isset($_POST['db_username']) && strlen(trim(_stripslashes($_POST['db_username']))) > 0) {
            $db_username = trim(_stripslashes($_POST['db_username']));
        }

        if (isset($_POST['db_password']) && strlen(trim(_stripslashes($_POST['db_password']))) > 0) {
            $db_password = trim(_stripslashes($_POST['db_password']));
        }

        if (isset($db_server) && isset($db_database) && isset($db_username) && isset($db_password)) {

            // Database details

            $config_file = str_replace('{db_server}',   $db_server,   $config_file);
            $config_file = str_replace('{db_database}', $db_database, $config_file);
            $config_file = str_replace('{db_username}', $db_username, $config_file);
            $config_file = str_replace('{db_password}', $db_password, $config_file);

            // Constant that says we're installed.

            $config_file = str_replace("// define('BEEHIVE_INSTALLED', 1);", "define('BEEHIVE_INSTALLED', 1);", $config_file);

            header("Content-Type: text/plain; name=\"config.inc.php\"");
            header("Content-disposition: attachment; filename=\"config.inc.php\"");

            echo $config_file;
            exit;

        }else {

            // Database details

            $config_file = str_replace('{db_server}',   "", $config_file);
            $config_file = str_replace('{db_database}', "", $config_file);
            $config_file = str_replace('{db_username}', "", $config_file);
            $config_file = str_replace('{db_password}', "", $config_file);

            // Constant that says we're installed.

            $config_file = str_replace("// define('BEEHIVE_INSTALLED', 1);", "define('BEEHIVE_INSTALLED', 1);", $config_file);

            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n";
            echo "<head>\n";
            echo "<title>BeehiveForum ", BEEHIVE_VERSION, " - Installation</title>\n";
            echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
            echo "<link rel=\"icon\" href=\"./images/favicon.ico\" type=\"image/ico\" />\n";
            echo "<link rel=\"stylesheet\" href=\"./styles/style.css\" type=\"text/css\" />\n";
            echo "</head>\n";

            echo "<h1>BeehiveForum ", BEEHIVE_VERSION, " Installation</h1>\n";
            echo "<br />\n";
            echo "<div align=\"center\">\n";
            echo "<table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
            echo "  <tr>\n";
            echo "    <td width=\"500\">\n";
            echo "      <table class=\"box\" width=\"100%\">\n";
            echo "        <tr>\n";
            echo "          <td class=\"posthead\">\n";
            echo "            <table class=\"posthead\" width=\"100%\">\n";
            echo "              <tr>\n";
            echo "                <td class=\"subhead\">Config Download Failed</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td>Oops! It would appear that we don't have enough information to be able to send you your config.inc.php. This would only have happened if the previous page didn't send us the right information.</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td>&nbsp;</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td>Fortunately you can still get your Beehive Forum functional by following these simple instructions:</td>\n";
            echo "              <tr>\n";
            echo "                <td>\n";
            echo "                  <ol>\n";
            echo "                    <li><p>Copy and paste the text in the box below into a text editor.</p></li>\n";
            echo "                    <li><p>Edit the \$db_server, \$db_database, \$db_username and \$db_password entries near the top of the script to match those that you entered in the first step of this installation</p></li>\n";
            echo "                    <li><p>Save the file as config.inc.php (all in lowercase) and upload it to the 'include' folder of your Beehive installation.</p></li>\n";
            echo "                    <li><p>Delete the 'install' folder from the Beehive ditribution on your server.</p></li>\n";
            echo "                  </ol>\n";
            echo "                </td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td>&nbsp;</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td>Once you've done all of that you can click the Continue button below to start using your Beehive Forum.</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td>&nbsp;</td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td>&nbsp;<b>config.inc.php:</b></td>\n";
            echo "              </tr>\n";
            echo "              <tr>\n";
            echo "                <td align=\"center\"><textarea name=\"config_file\" rows=\"20\" cols=\"56\" wrap=\"off\">$config_file</textarea></td>\n";
            echo "              </tr>\n";
            echo "            </table>\n";
            echo "          </td>\n";
            echo "        </tr>\n";
            echo "      </table>\n";
            echo "    </td>\n";
            echo "  </tr>\n";
            echo "</table>\n";
            echo "<form method=\"post\" action=\"./install.php\">\n";
            echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
            echo "    <tr>\n";
            echo "      <td width=\"500\">&nbsp;</td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "      <td align=\"center\"><input type=\"submit\" name=\"finish_install\" value=\"Continue\" class=\"button\" /></td>\n";
            echo "    </tr>\n";
            echo "  </table>\n";
            echo "</form>\n";
            echo "</div>\n";
            echo "</body>\n";
            echo "</html>\n";
            exit;
        }

    }else {

        $error_array[] = "Could not complete installation. Error was: failed to read config.inc.php\n";
        $valid = false;
    }

}elseif (isset($_POST['finish_install'])) {

    if (isset($_POST['install_remove_files']) && $_POST['install_remove_files'] == 'Y') {

        install_remove_files();
    }

    header_redirect('index.php');
}

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n";
echo "<head>\n";
echo "<title>BeehiveForum ", BEEHIVE_VERSION, " - Installation</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
echo "<link rel=\"icon\" href=\"./images/favicon.ico\" type=\"image/ico\" />\n";
echo "<link rel=\"stylesheet\" href=\"./styles/style.css\" type=\"text/css\" />\n";
echo "<script language=\"javascript\" type=\"text/javascript\">\n";
echo "<!--\n\n";
echo "function disable_button (button) {\n";
echo "    if (document.all || document.getElementById) {\n";
echo "        button.disabled = true;\n";
echo "    } else if (button) {\n";
echo "        button.onclick = null;\n";
echo "    }\n";
echo "    return true;\n";
echo "}\n\n";
echo "function show_install_help(topic) {\n";
echo "    if (topic == 0) {\n";
echo "      topic_text = 'For new installations please select \'New Install\' from the drop down and enter a webtag.\\n';\n";
echo "      topic_text+= 'Your webtag can be anything you want as long as it only contains the characters A-Z, 0-9, underscore and hyphen. If you enter any other characters an error will occur.\\n\\n';\n";
echo "      topic_text+= 'For upgrades please select the correct upgrade process. The webtag field is ignored.\\n\\n';\n";
echo "    } else if (topic == 1) {\n";
echo "      topic_text = 'These are the MySQL database details required by to install and run your BeehiveForum.\\n\\n';\n";
echo "      topic_text+= 'Hostname: The address of the MySQL server. This may be an IP or a DNS for example 127.0.0.1 or localhost or mysql.myhosting.com\\n';\n";
echo "      topic_text+= 'Database name: The name of the database you want your BeehiveForum to use. The database must already exist and you must have at least SELECT, INSERT, UPDATE, CREATE, ALTER, INDEX and DROP privilleges on the database for the installation and your BeehiveForum to work correctly.\\n';\n";
echo "      topic_text+= 'Username: The username needed to connect to the MySQL server.\\n';\n";
echo "      topic_text+= 'Password: The password needed to connect to the MySQL server.\\n\\n';\n";
echo "      topic_text+= 'If you do not know what these settings are please contact your hosting provider.';\n";
echo "    } else if (topic == 2) {\n";
echo "      topic_text = 'The credentials of the user you want to have initial Admin access. This information is only required for new installations. Upgrades will leave the existing user accounts intact.';\n";
echo "    } else if (topic == 3) {\n";
echo "      topic_text = 'These options are recommended for advanced users only. There use can have a detrimental effect on the functionality of your BeehiveForum and other software you may have installed. Use with extreme caution!\\n\\n';\n";
echo "    }\n";
echo "    alert(topic_text);\n";
echo "    return true;\n";
echo "}\n\n";
echo "//-->\n";
echo "</script>\n";
echo "</head>\n";
echo "<body>\n";

if (!defined('BEEHIVE_INSTALLED') || $force_install) {

    echo "<form id=\"install_form\" method=\"post\" action=\"install.php\">\n";
    echo "<input type=\"hidden\" name=\"force_install\" value=\"", ($force_install) ? "yes" : "no", "\" />\n";
    echo "<h1>BeehiveForum ", BEEHIVE_VERSION, " Installation</h1>\n";
    echo "<div align=\"center\">\n";
    echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
    echo "    <tr>\n";
    echo "      <td colspan=\"2\">\n";
    echo "        <p>Welcome to the BeehiveForum installation script. To get everything kicking off to a great start please fill out the details below and click the Install button!</p>\n";
    echo "        <p><b>WARNING</b>: Proceed only if you have performed a backup of your database! Failure to do so could result in loss of your forum. You have been warned!</p>\n";
    echo "      </td>\n";
    echo "    </tr>\n";

    if (isset($error_array) && sizeof($error_array) > 0) {

        echo "    <tr>\n";
        echo "      <td colspan=\"2\"><hr /></td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td><img src=\"./images/warning.png\" /></td>\n";
        echo "      <td><h2>The following errors need correcting before you continue:</h2></td>\n";
        echo "    </tr>\n";
        echo "    <tr>\n";
        echo "      <td colspan=\"2\">\n";
        echo "        <ul>\n";

        foreach ($error_array as $error_text) {
            echo "      <li>$error_text</li>\n";
        }

        echo "        </ul>\n";
        echo "      </td>\n";
        echo "    </tr>\n";
    }

    echo "  </table>\n";
    echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
    echo "    <tr>\n";
    echo "      <td width=\"500\">\n";
    echo "        <table class=\"box\" width=\"100%\">\n";
    echo "          <tr>\n";
    echo "            <td class=\"posthead\">\n";
    echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
    echo "                <tr>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\">Basic Configuration</td>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\" align=\"right\"><a href=\"javascript:void(0)\" onclick=\"return show_install_help(0)\"><img src=\"./images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" /></a></td>\n";
    echo "                </tr>\n";
    echo "                <tr>\n";
    echo "                  <td align=\"center\" colspan=\"2\">\n";
    echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Choose Installation Method:</td>\n";
    echo "                        <td width=\"250\"><select name=\"install_method\" class=\"bhselect\" dir=\"ltr\"><option value=\"install\" ", (isset($install_method) && $install_method == 0) ? "selected=\"selected\"" : "", ">New Install</option><option value=\"upgrade\" ", (isset($install_method) && $install_method == 1) ? "selected=\"selected\"" : "", ">Upgrade 0.4 to 0.5</option><option value=\"upgrade06\" ", (isset($install_method) && $install_method == 2) ? "selected=\"selected\"" : "", ">Upgrade 0.5 to 0.6</option></select></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\" valign=\"top\">Default Forum Webtag:</td>\n";
    echo "                        <td width=\"250\"><input type=\"text\" name=\"forum_webtag\" class=\"bhinputtext\" value=\"", (isset($forum_webtag) ? $forum_webtag : ""), "\" size=\"24\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td colspan=\"2\">&nbsp;</td>\n";
    echo "                      </tr>\n";
    echo "                    </table>\n";
    echo "                  </td>\n";
    echo "                </tr>\n";
    echo "              </table>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "  <br />\n";
    echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
    echo "    <tr>\n";
    echo "      <td width=\"500\">\n";
    echo "        <table class=\"box\" width=\"100%\">\n";
    echo "          <tr>\n";
    echo "            <td class=\"posthead\">\n";
    echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
    echo "                <tr>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\" colspan=\"3\">MySQL Database Configuration</td>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\" align=\"right\"><a href=\"javascript:void(0)\" onclick=\"return show_install_help(1)\"><img src=\"./images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" /></a></td>\n";
    echo "                </tr>\n";
    echo "                <tr>\n";
    echo "                  <td align=\"center\" colspan=\"2\">\n";
    echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Hostname:</td>\n";
    echo "                        <td width=\"250\"><input type=\"text\" name=\"db_server\" class=\"bhinputtext\" value=\"", (isset($db_server) ? $db_server : "localhost"), "\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Database Name:</td>\n";
    echo "                        <td width=\"250\"><input type=\"text\" name=\"db_database\" class=\"bhinputtext\" value=\"", (isset($db_database) ? $db_database : ""), "\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Username:</td>\n";
    echo "                        <td width=\"250\"><input type=\"text\" name=\"db_username\" class=\"bhinputtext\" value=\"", (isset($db_username) ? $db_username : ""), "\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Password:</td>\n";
    echo "                        <td width=\"250\"><input type=\"password\" name=\"db_password\" class=\"bhinputtext\" value=\"\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Confirm Password:</td>\n";
    echo "                        <td width=\"250\"><input type=\"password\" name=\"db_cpassword\" class=\"bhinputtext\" value=\"\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td colspan=\"2\">&nbsp;</td>\n";
    echo "                      </tr>\n";
    echo "                    </table>\n";
    echo "                  </td>\n";
    echo "                </tr>\n";
    echo "              </table>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "  <br />\n";
    echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
    echo "    <tr>\n";
    echo "      <td width=\"500\">\n";
    echo "        <table class=\"box\" width=\"100%\">\n";
    echo "          <tr>\n";
    echo "            <td class=\"posthead\">\n";
    echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
    echo "                <tr>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\" colspan=\"3\">Admin Account (New installations only)</td>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\" align=\"right\"><a href=\"javascript:void(0)\" onclick=\"return show_install_help(2)\"><img src=\"./images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" /></a></td>\n";
    echo "                </tr>\n";
    echo "                <tr>\n";
    echo "                  <td align=\"center\" colspan=\"2\">\n";
    echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Admin Username:</td>\n";
    echo "                        <td width=\"250\"><input type=\"text\" name=\"admin_username\" class=\"bhinputtext\" value=\"", (isset($admin_username) ? $admin_username : ""), "\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Admin Email Address:</td>\n";
    echo "                        <td width=\"250\"><input type=\"text\" name=\"admin_email\" class=\"bhinputtext\" value=\"", (isset($admin_email) ? $admin_email : ""), "\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Admin Password:</td>\n";
    echo "                        <td width=\"250\"><input type=\"password\" name=\"admin_password\" class=\"bhinputtext\" value=\"\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td width=\"250\">Confirm Password:</td>\n";
    echo "                        <td width=\"250\"><input type=\"password\" name=\"admin_cpassword\" class=\"bhinputtext\" value=\"\" size=\"36\" maxlength=\"64\" dir=\"ltr\" /></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td colspan=\"2\">&nbsp;</td>\n";
    echo "                      </tr>\n";
    echo "                    </table>\n";
    echo "                  </td>\n";
    echo "                </tr>\n";
    echo "              </table>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "  <br />\n";
    echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
    echo "    <tr>\n";
    echo "      <td width=\"500\">\n";
    echo "        <table class=\"box\" width=\"100%\">\n";
    echo "          <tr>\n";
    echo "            <td class=\"posthead\">\n";
    echo "              <table cellpadding=\"2\" cellspacing=\"0\" class=\"posthead\" width=\"100%\">\n";
    echo "                <tr>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\" colspan=\"3\">Advanced Options</td>\n";
    echo "                  <td nowrap=\"nowrap\" class=\"subhead\" align=\"right\"><a href=\"javascript:void(0)\" onclick=\"return show_install_help(3)\"><img src=\"./images/help.png\" border=\"0\" alt=\"Help!\" title=\"Help!\" /></a></td>\n";
    echo "                </tr>\n";
    echo "                <tr>\n";
    echo "                  <td align=\"center\" colspan=\"2\">\n";
    echo "                    <table cellpadding=\"2\" cellspacing=\"0\" width=\"95%\">\n";
    echo "                      <tr>\n";
    echo "                        <td><span class=\"bhinputcheckbox\"><input type=\"checkbox\" name=\"remove_conflicts\" id=\"remove_conflicts\" value=\"Y\" /><label for=\"remove_conflicts\">Automatically remove tables that conflict with BeehiveForum's own.</label></span></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td><span class=\"bhinputcheckbox\"><input type=\"checkbox\" name=\"skip_dictionary\" id=\"skip_dictionary\" value=\"Y\" /><label for=\"skip_dictionary\">Skip dictionary setup (recommended only if install fails to complete).</label></span></td>\n";
    echo "                      </tr>\n";
    echo "                      <tr>\n";
    echo "                        <td colspan=\"2\">&nbsp;</td>\n";
    echo "                      </tr>\n";
    echo "                    </table>\n";
    echo "                  </td>\n";
    echo "                </tr>\n";
    echo "              </table>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>&nbsp;</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td><p>The installation process may take several minutes to complete. Please click the Install button once and once only. Clicking it multiple times may cause your installation to become corrupted.</p></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>&nbsp;</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td align=\"center\"><input type=\"submit\" name=\"install\" value=\"Install\" class=\"button\" onclick=\"disable_button(this); install_form.submit()\" /></td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "</form>\n";
    echo "</div>\n";

}else {

    echo "<br />\n";
    echo "<div align=\"center\">\n";
    echo "<form id=\"install_form\" method=\"get\" action=\"install.php\">\n";
    echo "  <input type=\"hidden\" name=\"force_install\" value=\"yes\" />\n";
    echo "  <table cellpadding=\"0\" cellspacing=\"0\" width=\"500\">\n";
    echo "    <tr>\n";
    echo "      <td>\n";
    echo "        <table class=\"box\">\n";
    echo "          <tr>\n";
    echo "            <td class=\"posthead\">\n";
    echo "              <table class=\"posthead\" width=\"500\">\n";
    echo "                <tr>\n";
    echo "                  <td class=\"subhead\">Installation Already Complete</td>\n";
    echo "                </tr>\n";
    echo "                <tr>\n";
    echo "                  <td>Your BeehiveForum would appear to be already installed. If this is not the case or you need to perform an upgrade please click the ignore button below.</td>\n";
    echo "                </tr>\n";
    echo "                <tr>\n";
    echo "                  <td>&nbsp;</td>\n";
    echo "                </tr>\n";
    echo "              </table>\n";
    echo "            </td>\n";
    echo "          </tr>\n";
    echo "        </table>\n";
    echo "      </td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>&nbsp;</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td align=\"center\"><input type=\"submit\" name=\"install\" value=\"Ignore\" class=\"button\" /></td>\n";
    echo "    </tr>\n";
    echo "  </table>\n";
    echo "</form>\n";
    echo "</div>\n";
}

echo "</body>\n";
echo "</html>\n";

?>