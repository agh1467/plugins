#!/usr/local/bin/python3
# -*- coding: utf-8 -*-

"""
    OPNsense® is Copyright © 2022 by Deciso B.V.
    Copyright (C) 2022 agh1467@protonmail.com
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
     this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
     notice, this list of conditions and the following disclaimer in the
     documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
    --------------------------------------------------------------------------------------
    package : Mullvad
    function: This provides facilities to perform login/logout acitivies for
        Mullvad.
"""

import sys
import json
import argparse
import requests
import subprocess

result = {}
MULLVAD_API = 'https://api.mullvad.net'
MULLVAD_API_ACCOUNTS = MULLVAD_API + '/www/accounts'
MULLVAD_API_RELAYS_WG = MULLVAD_API + '/www/relays/wireguard'

"""
API Samples

https://api.mullvad.net/www/accounts/
{
    "detail": "Method \"GET\" not allowed.",
    "code": "METHOD_NOT_ALLOWED"
}

https://api.mullvad.net/www/accounts/0824998250016939
{
   "account" : {
      "active" : false,
      "can_add_ports" : false,
      "can_add_wg_peers" : true,
      "city_ports" : [],
      "expires" : "2022-06-27T21:58:38+00:00",
      "expiry_unix" : 1656367118,
      "max_ports" : 5,
      "max_wg_peers" : 5,
      "ports" : [],
      "pretty_token" : "0824 9982 5001 6939",
      "subscription" : null,
      "token" : "0824998250016939",
      "wg_peers" : []
   },
   "auth_token" : "758d14fc76139f2d148ab5ed98e5bbd14f156fd3a45d4a717e9c063b343df46d"
}

https://api.mullvad.net/www/relays/wireguard (requires auth token)
[
   {
      "active" : true,
      "city_code" : "vie",
      "city_name" : "Vienna",
      "country_code" : "at",
      "country_name" : "Austria",
      "hostname" : "at4-wireguard",
      "ipv4_addr_in" : "86.107.21.50",
      "ipv6_addr_in" : "2001:ac8:29:59::a04f",
      "multihop_port" : 3311,
      "network_port_speed" : 1,
      "owned" : false,
      "provider" : "M247",
      "pubkey" : "hZpraeYrNU7Vl+UB2NSpXT2vBRM1fZ/a/gt4TTksP14=",
      "socks_name" : "at4-wg.socks5"
   },
   {
   ...
   }
]

https://api.mullvad.net/wg/ -d account=$mullvad_account --data-urlencode pubkey=$local_pubkey


# Add new WG device:
curl 'https://api.mullvad.net/www/wg-pubkeys/add/'
    -X POST
    -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:101.0) Gecko/20100101 Firefox/101.0'
    -H 'Accept: application/json, text/plain, */*'
    -H 'Accept-Language: en-US'
    -H 'Accept-Encoding: gzip, deflate, br'
    -H 'Content-Type: application/json'
    -H 'Authorization: Token 6f74739cdeddb5b7b08f631a14605a995de0aa5e3730cbc65152bdfc53f83035'
    -H 'Origin: https://mullvad.net'
    -H 'DNT: 1'
    -H 'Connection: keep-alive'
    -H 'Sec-Fetch-Dest: empty'
    -H 'Sec-Fetch-Mode: cors'
    -H 'Sec-Fetch-Site: same-site'
    -H 'Sec-GPC: 1'
    -H 'Pragma: no-cache'
    -H 'Cache-Control: no-cache'
    -H 'TE: trailers'
    --data-raw '{"pubkey":"zLVnO6DYynxeaSc0qECNJSnAqwkOvMU0xE82KqniugI="}'
Response:
{
   "app" : false,
   "can_add_ports" : false,
   "created" : "2022-06-28",
   "ipv4_address" : "10.66.141.99/32",
   "ipv6_address" : "fc00:bbbb:bbbb:bb01::3:8d62/128",
   "key" : {
      "private" : "",
      "public" : "zLVnO6DYynxeaSc0qECNJSnAqwkOvMU0xE82KqniugI="
   },
   "ports" : []
}

Query account info:
curl 'https://api.mullvad.net/www/me/'
    -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:101.0) Gecko/20100101 Firefox/101.0'
    -H 'Accept: application/json, text/plain, */*'
    -H 'Accept-Language: en-US'
    -H 'Accept-Encoding: gzip, deflate, br'
    -H 'Authorization: Token 6f74739cdeddb5b7b08f631a14605a995de0aa5e3730cbc65152bdfc53f83035'
    -H 'Origin: https://mullvad.net'
    -H 'DNT: 1'
    -H 'Connection: keep-alive'
    -H 'Sec-Fetch-Dest: empty'
    -H 'Sec-Fetch-Mode: cors'
    -H 'Sec-Fetch-Site: same-site'
    -H 'Sec-GPC: 1'
    -H 'Pragma: no-cache'
    -H 'Cache-Control: no-cache'
    -H 'TE: trailers'
Response:
{
   "account" : {
      "active" : false,
      "can_add_ports" : false,
      "can_add_wg_peers" : true,
      "city_ports" : [],
      "expires" : "2022-06-27T21:58:38+00:00",
      "expiry_unix" : 1656367118,
      "max_ports" : 5,
      "max_wg_peers" : 5,
      "ports" : [],
      "pretty_token" : "0824 9982 5001 6939",
      "subscription" : null,
      "token" : "0824998250016939",
      "wg_peers" : [
         {
            "app" : false,
            "can_add_ports" : false,
            "city_ports" : [],
            "created" : "2022-06-28",
            "device_id" : "0f8c65c1-9df4-4db5-9c4d-7a8e86dad149",
            "device_name" : "alert pup",
            "ipv4_address" : "10.66.141.99/32",
            "ipv6_address" : "fc00:bbbb:bbbb:bb01::3:8d62/128",
            "key" : {
               "private" : "",
               "public" : "zLVnO6DYynxeaSc0qECNJSnAqwkOvMU0xE82KqniugI="
            },
            "ports" : []
         }
      ]
   }
}



"""


def error_out(message):
    """ Error handling function.
    """
    result = [{'status': message, 'result': "failed"}]
    print(json.dumps(result, indent=4))
    sys.exit()


def jq(json, query):
    """
    Wrapper for calling jq, takes json as string, and query to be used.
    """
    jq_result = subprocess.run(
        [
            '/usr/local/bin/jq',
            '-Mcr',
            query
        ],
        input=json,
        capture_output=True, text=True).stdout.strip("\n")
    return jq_result


def api(endpoint, argument="", token=None, data=None):
    """
    This function is a wrapper for API calls.
    """
    if token:
        response = requests.get(endpoint + '/' + argument,
                                headers={'Authorization': 'Token ' + token})
    else:
        response = requests.get(endpoint + '/' + argument)

    if response:
        return response.text
    else:
        error_out('Response from API "' + endpoint + '" was: ' + response)


def mullvad_get_token(account_number):
    """
    Function to get a session token for the provided account number.
    """
    result = api(MULLVAD_API_ACCOUNTS, account_number)
    if result:
        return jq(result, '.auth_token')
    else:
        error_out('API result was empty')


def mullvad_get_wireguard_relays(token):
    """
    Function to get a list of wireguard relays

    Returns json
    """
    result = api(MULLVAD_API_RELAYS_WG, token=token)
    if result:
        return result
    else:
        error_out('Unable to get wireguard list')


def main():
    """
    This main function will retrieve command line parameters,
    begin activies, and the print the output to stderr.

    Needs to take command line arguments, two primary modes:
    login
    logout

    Login:
        This is going to be basically adding a device to the account.
            Maybe check that the key limit hasn't been reached first.
            Need to create a private key, and store it in the config.
            When the device is created it will appear in wg_peers array.
    Logout:
        This is going to be basically removing the device from the account.
            This will use the public key.
            The private key that was use to login should be deleted.
            The device name should be cleared.
    """


if __name__ == '__main__':
    main()
