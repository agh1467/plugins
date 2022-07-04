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
import datetime

result = {}
DEBUG = False
MULLVAD_API = 'https://api.mullvad.net'
MULLVAD_API_ACCOUNTS = MULLVAD_API + '/www/accounts'
MULLVAD_API_ME = MULLVAD_API + '/www/me'
MULLVAD_API_RELAYS_WG = MULLVAD_API + '/www/relays/wireguard'
MULLVAD_API_WG_PEER_ADD = MULLVAD_API + '/www/wg-pubkeys/add'
MULLVAD_API_WG_PEER_REVOKE = MULLVAD_API + '/www/wg-pubkeys/revoke'

"""
API Samples

https://api.mullvad.net/www/accounts/
{
    "detail": "Method \"GET\" not allowed.",
    "code": "METHOD_NOT_ALLOWED"
}

https://api.mullvad.net/www/me/868e075e36f8b05f00aaee2e370b326d2109303165ca4a1ec413c3f2b32f6750
{
    "code": "NOT_FOUND"
}

# Get token
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

# Get relays
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

Revoke wg device:
curl 'https://api.mullvad.net/www/wg-pubkeys/revoke/'
    -X POST
    -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:101.0) Gecko/20100101 Firefox/101.0'
    -H 'Accept: application/json, text/plain, */*'
    -H 'Accept-Language: en-US'
    -H 'Accept-Encoding: gzip, deflate, br'
    -H 'Content-Type: application/json'
    -H 'Authorization: Token 91e08d969b20da8026b31a2571bf10b97ed22d34a92b38b1ea7fad91f5e72738'
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
Reponse:
204 No content
^ HTTP response code.

"""


def eprint(*args, **kwargs):
    """
    Function to print to stderr.
    """
    print(*args, file=sys.stderr, **kwargs)


def enable_debug():
    """
    Function to turn on debug messaging.
    """
    eprint("Debugging Enabled")
    global DEBUG
    DEBUG = True


def error_out(message):
    """ Error handling function.
    """
    result = {'status': message, 'result': "failed"}
    print(json.dumps(result, indent=4))
    sys.exit()


def wg(cmd, stdin=''):
    """
    Wrapper for calling wg, with arguments
    """
    try:
        result = subprocess.run(
            [
                '/usr/local/bin/wg',
                cmd
            ],
            input=stdin,
            capture_output=True, text=True, check=True)
    except subprocess.CalledProcessError as e:
        error_out('Error occurred during execution: %s' %
                  e)
    if DEBUG:
        eprint('wg(): stdout: %s' % result.stdout)
        eprint('wg(): stderr: %s' % result.stderr)
        eprint('wg(): Output from wg: %s' % result)
    return result.stdout.strip("\n")


def jq(json, query):
    """
    Wrapper for calling jq, takes json as string, and query to be used.
    """
    if DEBUG:
        eprint('jq() Calling jq with json/query: %s / %s' % (json, query))
    try:
        result = subprocess.run(
            [
                '/usr/local/bin/jq',
                '-M',
                '-cr',
                query
            ],
            input=json,
            capture_output=True, text=True, check=True)
    except subprocess.CalledProcessError as e:
        error_out('Error occurred during execution: %s' %
                  e)
    if DEBUG:
        eprint('stdout: %s' % result.stdout)
        eprint('stderr: %s' % result.stderr)
        eprint('jq() Output from jq: %s' % result)
    return result.stdout.strip("\n")


def api(method, endpoint, argument="", token=None, headers={}, data=None):
    """
    This function is a wrapper for API calls.

    Returns: Response object
    """
    if len(headers) != 0:
        if DEBUG:
            eprint('api() Headers passed: %s' % headers)

    if token:
        headers.update({'Authorization': 'Token ' + token})
        if DEBUG:
            eprint('api() Token passed, appended headers: %s' % headers)

    if data:
        if DEBUG:
            eprint('api() Data passed, setting data.')
        data = data
    else:
        data = None

    if method == 'GET':
        if DEBUG:
            eprint('api() Making GET API call for "%s/%s" and headers: %s' %
                   (endpoint, argument, headers))
        response = requests.get(endpoint + '/' + argument,
                                headers=headers)
    if method == 'POST':
        if DEBUG:
            eprint('api() Making GET API call for "%s/%s" and headers/data: %s/%s' %
                   (endpoint, argument, headers, data))
        response = requests.post(endpoint + '/' + argument,
                                 headers=headers, data=data)

    if response:
        return response
    else:
        error_out('Response from API "' + endpoint + '" was: ' + response.text)


def mullvad_get_token(account_number):
    """
    Function to get a session token for the provided account number.

    Returns string
    """
    result = api(method='GET', endpoint=MULLVAD_API_ACCOUNTS,
                 argument=account_number)
    if result:
        #token = jq(result.text, '.auth_token')
        if 'auth_token' in result.json():
            token = result.json()['auth_token']
        if DEBUG:
            eprint('mullvad_get_token(): Token get for account number %s: %s' %
                   (account_number, token))
        if len(token) != 0:
            return token
        else:
            error_out('No token was found in the API response: %s' %
                      result.text)
    else:
        error_out('API result was empty')


def mullvad_get_wireguard_relays(token):
    """
    Function to get a list of wireguard relays

    Returns json dict
    """
    result = api(method='GET', endpoint=MULLVAD_API_RELAYS_WG, token=token)
    if result:
        if DEBUG:
            eprint('mullvad_get_wireguard_relays(): Relays get using token: %s' %
                   (token))
        return result
    else:
        error_out('Unable to get wireguard list')


def mullvad_get_status(token):
    """
    Function to get the current account status from Mullvad.

    Returns json in string format
    """
    result = api(method='GET', endpoint=MULLVAD_API_ME, token=token)
    if result:
        if DEBUG:
            eprint('mullvad_get_status(): Status get using token: %s' %
                   token)
        if 'account' in result.json():
            return result.json()['account']
        else:
            error_out('Account information not found in response: %s' %
                      result.text)
    else:
        error_out('Unable to get status from Mullvad')


def mullvad_add_wg_peer(pubkey, token):
    """
    Function to add a wireguard peer.
    --data-raw '{"pubkey":"zLVnO6DYynxeaSc0qECNJSnAqwkOvMU0xE82KqniugI="}'

    Returns json in string format
    """
    if pubkey:
        if DEBUG:
            eprint('mullvad_add_wg_peer(): Public key provided, setting data: %s' %
                   pubkey)
        data = '{"pubkey": "%s"}' % pubkey
    else:
        error_out('Public key is required, but not provided in API call.')

    headers = {'Content-type': 'application/json'}
    result = api(method='POST', endpoint=MULLVAD_API_WG_PEER_ADD,
                 token=token, headers=headers, data=data)

    if 'key' in result.json():
        if 'public' in result.json()['key']:
            if result.json()['key']['public'] == pubkey:
                return result.json()
            else:
                error_out('Unable to add WireGuard peer: %s' % result.text)
        else:
            error_out('Unable to add WireGuard peer: %s' % result.text)
    else:
        error_out('Unable to add WireGuard peer: %s' % result.text)


def mullvad_exist_wg_peer(json_status, pubkey):
    """
    Function to check if a wg peer exists in a given json (string).
    """
    if DEBUG:
        eprint('mullvad_exist_wg_peer(): Checking if wg peer exists with public key: %s' % pubkey)
    jq_get_peer = '.wg_peers | .[] | select( .key.public=="%s")' % pubkey
    wg_peer = json.loads(
        jq(json.dumps(json_status), jq_get_peer))
    if len(wg_peer) != 0:
        if DEBUG:
            eprint('mullvad_exist_wg_peer(): Matching peer found for: %s' % pubkey)
        return True
    else:
        if DEBUG:
            eprint('mullvad_exist_wg_peer(): No matching peer found for: %s' % pubkey)
        return False


def mullvad_revoke_wg_peer(token, pubkey):
    """
    Function to revoke a wireguard peer.
    """
    if pubkey:
        data = '{"pubkey": "%s"}' % pubkey
        if DEBUG:
            eprint('mullvad_add_wg_peer(): Public key provided, data set to: %s' %
                   data)
    else:
        error_out('mullvad_add_wg_peer(): Public key is required, but not provided in API call.')

    headers = {'Content-type': 'application/json'}
    result = api(method='POST', endpoint=MULLVAD_API_WG_PEER_REVOKE,
                 token=token, headers=headers, data=data)

    if result.status_code != 204:
        error_out('Unable to confirm peer revoke: %s' % result.text)


def mullvad_get_wg_peer(json_status, pubkey):
    """
    Function to get wireguard peer info given a json (string), and public key.
    """
    if DEBUG:
        eprint('mullvad_get_wg_peer(): Checking for peer with pubkey %s in : %s' %
               (pubkey, json_status))
    jq_get_peer = '.wg_peers | .[] | select( .key.public=="%s")' % pubkey
    jq_output = jq(json.dumps(json_status), jq_get_peer)
    wg_peer = json.loads(jq_output)
    if len(wg_peer) > 0:
        return wg_peer
    else:
        error_out('mullvad_get_wg_peer(): peer not found in jq output: %s' % jq_output)


def wg_genkey():
    """
    Function to get a private key from wireguard.
    XXX Can probably add some key validation here.

    Returns json
    """
    privkey = wg('genkey')
    if len(privkey) != 0:
        return privkey
    else:
        error_out('Private key generation failed.')


def wg_pubkey(privkey):
    """
    Function to get a private key from wireguard.
    XXX Can probably add some key validation here.

    Returns json
    """
    if len(privkey) != 0:
        pubkey = wg('pubkey', privkey)
        if len(pubkey) != 0:
            return pubkey
        else:
            error_out('Public key generation failed: %s' % pubkey)
    else:
        error_out('Invalid private key provided: %s' % privkey)


def do_status(account_number):
    """
    This is a primary function which will return a json formated output for consumption.
        This will query the account info API given an account number, and return:
        account_status = Paid until <insert date>| Expired on <insert date>
    """
    result = {}
    token = mullvad_get_token(account_number)
    status_output = mullvad_get_status(token)
    if ('active' in status_output
            and 'expires' in status_output):
        if status_output['active'] == False:
            account_status = "Expired on "
        elif status_output['active'] == True:
            account_status = "Paid until "
        else:
            account_status = "Unknown"

        if account_status != "Unknown":
            # "2022-06-27T21:58:38+00:00"
            #    %Y-%m-%dT%H:%M:%S+00:00
            expires_format = '%Y-%m-%dT%H:%M:%S+00:00'
            paid_until_format = '%b %-d, %Y at %-I:%M %p'
            expires_datetime = datetime.datetime.strptime(
                status_output['expires'], expires_format)
            paid_until = expires_datetime.strftime(
                paid_until_format)
        else:
            paid_until = "unknown"

        result['account_status'] = account_status + paid_until
        return result


def do_login(account_number, pubkey=''):
    """
    This is a primary function which will return a json formated output for consumption.
        This is going to be basically adding a device to the account.
            Re-get account info, and grab the device name (using the pubkey as reference).
                (Use the status function.)
            Needs to return an array (json) with:
                "device_id" : "0f8c65c1-9df4-4db5-9c4d-7a8e86dad149", mabye?
                "device_name" : "alert pup",
                "ipv4_address" : "10.66.141.99/32",
                "ipv6_address" : "fc00:bbbb:bbbb:bb01::3:8d62/128",
                "private_key" : "",
                "public_key" : "zLVnO6DYynxeaSc0qECNJSnAqwkOvMU0xE82KqniugI="
                "action" : 'login' # is this useful?
                "result" : 'success'
                "status" : ?????
    """
    result = {}
    token = mullvad_get_token(account_number)
    status_output_pre = mullvad_get_status(token)
    if ('can_add_wg_peers' in status_output_pre):
        # Check that the pubkey isn't in use first, if so error out.
        if pubkey != '':
            if mullvad_exist_wg_peer(status_output_pre, pubkey):
                error_out(
                    'Matching wireguard peer found using public key: %s' % pubkey)

        # Cehck that we able to add peers.
        if status_output_pre['can_add_wg_peers'] == True:
            # We're good to add a peer, so let's prep for that.
            privkey = wg_genkey()
            pubkey = wg_pubkey(privkey)
            add_wg_peer = mullvad_add_wg_peer(pubkey, token)
            if add_wg_peer:
                status_output_post = mullvad_get_status(token)
                if mullvad_exist_wg_peer(status_output_post, pubkey):
                    wg_peer = mullvad_get_wg_peer(
                        status_output_post, pubkey)
                    if wg_peer:
                        result['device_name'] = wg_peer['device_name']
                        result['ipv4_address'] = wg_peer['ipv4_address']
                        result['ipv6_address'] = wg_peer['ipv6_address']
                        result['private_key'] = privkey
                        result['public_key'] = pubkey
                        result['result'] = 'success'
                        return result
                    else:
                        error_out('No matching wg peer found.')
                else:
                    # XXX should this error out?
                    error_out(
                        'WireGuard peer not found after add procedure.')
        elif status_output_pre['can_add_wg_peers'] == False:
            error_out('[KEY_LIMIT_REACHED] You have reached the maximum number of WireGuard keys. Go to https://mullvad.net/account/#/ports to revoke one of your keys.')
        else:
            error_out('can_add_wg_peers in unknown state in response: %s' %
                      json.dumps(status_output1))


def do_logout(account_number, pubkey):
    """
    This is a primary function which will return a json formated output for consumption.
    """
    token = mullvad_get_token(account_number)
    mullvad_revoke_wg_peer(token, pubkey)
    return {'result': 'revoked'}


def main():
    """
    This main function will retrieve command line parameters,
    begin activies, and the print the output to stderr.

    Needs to take command line arguments, three modes:
    login
    logout
    status

    Logout:
        This is going to be basically removing the device from the account.
            This will use the public key.
            The private key that was use to login should be deleted.
            The device name should be cleared.

    """
    output = {}
    parser = argparse.ArgumentParser()
    subparsers = parser.add_subparsers(dest='subparser') # give a name for a place to put the name of the parser being executed.
    parser_status = subparsers.add_parser('status', description='status parser description')
    parser_status.add_argument('--account', help='account number to pass',
                               default='', nargs=1, required=True)
    parser_login = subparsers.add_parser('login', description='login parser description')
    parser_login.add_argument('--account', help='account number to pass',
                               default='', nargs=1, required=True)
    parser_login.add_argument('--pubkey', help='public key to pass',
                               default='', nargs=1)
    parser_logout = subparsers.add_parser('logout', description='logout parser description')
    parser_logout.add_argument('--account', help='account number to pass',
                               default='', nargs=1, required=True)
    parser_logout.add_argument('--pubkey', help='public key to pass',
                               default='', nargs=1, required=True)
    parser.add_argument('--debug', help='enable debug logging',
                        action='store_true')
    inputargs = parser.parse_args()
    if inputargs.debug:
        enable_debug()

    if DEBUG:
        eprint('main(): args: %s' % inputargs)

    # need to make this required with argparse
    this_account_number = inputargs.account[0]
    if 'pubkey' in inputargs:
        this_pubkey = inputargs.pubkey[0]
        if DEBUG:
            eprint('main(): set public key to: %s' % this_pubkey)

    # Get the status of the account.
    if inputargs.subparser == 'status':
        if DEBUG:
            eprint('main(): command status')
        mullvad_status = do_status(this_account_number)
        if 'account_status' in mullvad_status:
            output = mullvad_status

    # Login to the account.
    if inputargs.subparser == 'login':
        if DEBUG:
            eprint('main(): command login')
        mullvad_login = do_login(this_account_number, this_pubkey)
        if 'result' in mullvad_login:
            output = mullvad_login

   # Log out of the account.
    if inputargs.subparser == 'logout':
        if DEBUG:
            eprint('main(): command logout')
        mullvad_login = do_logout(this_account_number, this_pubkey)
        if 'result' in mullvad_login:
            output = mullvad_login

    # Print out the json, nicely formated.
    print(json.dumps(output, indent=4))


if __name__ == '__main__':
    main()
