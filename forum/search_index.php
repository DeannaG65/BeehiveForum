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

// Use this script in a CRON job or other schedule to index
// your forum's posts automatically. How often you want the
// script to be executed is entirely up to you, but we recommend
// no more than once every minute. Whatever value you choose
// remember that this script will only index *1* post each time
// it is run so if you have a lot of posts it could be some time
// before your entire database is indexed.

include_once(BH_INCLUDE_PATH. "search.inc.php");

search_index_old_post();

?>