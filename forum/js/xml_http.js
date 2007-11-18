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

/* $Id: xml_http.js,v 1.4 2007-11-18 13:55:19 decoyduck Exp $ */

function xml_http_request()
{
    xml_http_request.prototype._handler = undefined;
    xml_http_request.prototype._request = undefined;

    xml_http_request.prototype.set_handler = function(handler_function)
    {
        this._handler = handler_function;
    }

    xml_http_request.prototype.get_url = function(url)
    {       
        this._request = this._xml_http_request(); var _this = this;
        this._request.onreadystatechange = function() { _this._on_state_change() };
        this._request.open("GET", url, true);
        this._request.send(null);
    }

    xml_http_request.prototype.check_obj = function()
    {
        for (obj in this._request) {
            alert('xml_http_request.' + obj + ' => ' + this._request[obj]);
        }
    }

    xml_http_request.prototype.get_response_xml = function()
    {
        return this._request.responseXML;
    }

    xml_http_request.prototype.close = function()
    {
        delete this._request;
    }

    xml_http_request.prototype._on_state_change = function()
    {
        if (this._request.readyState == 4) {

            if (this._request.status == '200') {

                this._handler();
            }
        }
    }

    xml_http_request.prototype._xml_http_request = function()
    {
        var xml_http; 

        try {   
            xml_http = new ActiveXObject('Msxml2.XMLHTTP');
        }catch (e) {
            try {          
                xml_http = new ActiveXObject('Microsoft.XMLHTTP');        
            }catch (e2) {          
                try {              
                    xml_http = new XMLHttpRequest();            
                }catch (e3) {              
                    xml_http = false;
                }
            }
        }

        return xml_http;
    }
}