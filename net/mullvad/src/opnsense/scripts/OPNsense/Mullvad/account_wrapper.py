#!/usr/local/bin/python3
# -*- coding: utf-8 -*-


import sys
import json

#sys.path.insert(0, '..')
target = __import__("account")


def main():
    """
    """
    json_data1 = """
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
    }"""

    json_data2 = '{"token": "0824998250016939", "pretty_token": "0824 9982 5001 6939", "active": false, "expires": "2022-06-27T21:58:38+00:00", "expiry_unix": 1656367118, "ports": [], "city_ports": [], "max_ports": 5, "can_add_ports": false, "wg_peers": [{"key": {"public": "+0EVppW7rsljmCT2i7RhWKIIf4nd/4UfwG3M0hhhtxg=", "private": ""}, "app": false, "ipv4_address": "10.67.196.108/32", "ipv6_address": "fc00:bbbb:bbbb:bb01::4:c46b/128", "ports": [], "city_ports": [], "can_add_ports": false, "created": "2022-07-02", "device_id": "4f336543-2c0e-4bac-bbf7-cb8534a0c4e6", "device_name": "wealthy pelican"}, {"key": {"public": "CnYth6a6P1BOwxZSNuI6mjadiweU5lCYILJhNfmE3hQ=", "private": ""}, "app": false, "ipv4_address": "10.64.185.37/32", "ipv6_address": "fc00:bbbb:bbbb:bb01::1:b924/128", "ports": [], "city_ports": [], "can_add_ports": false, "created": "2022-07-02", "device_id": "bf19c683-7a98-425e-81ba-77fb90da5dad", "device_name": "massive tuna"}], "max_wg_peers": 5, "can_add_wg_peers": true, "subscription": null}'

    json_account_status1 = '{"token":"0824998250016939","pretty_token":"0824 9982 5001 6939","active":false,"expires":"2022-06-27T21:58:38+00:00","expiry_unix":1656367118,"ports":[],"city_ports":[],"max_ports":5,"can_add_ports":false,"wg_peers":[{"key":{"public":"lilp+FHojtfwR9GH8+3TPdCuYso/Q4uez4zgwk8Emnw=","private":""},"app":false,"ipv4_address":"10.65.42.120/32","ipv6_address":"fc00:bbbb:bbbb:bb01::2:2a77/128","ports":[],"city_ports":[],"can_add_ports":false,"created":"2022-07-03","device_id":"f683350a-1362-4479-94bb-575a284c875d","device_name":"enchanted squid"},{"key":{"public":"198Cu+3CUbhd+J/pVKEGeRIdEd4kTMNpc55HCwKvthg=","private":""},"app":false,"ipv4_address":"10.65.91.218/32","ipv6_address":"fc00:bbbb:bbbb:bb01::2:5bd9/128","ports":[],"city_ports":[],"can_add_ports":false,"created":"2022-07-03","device_id":"0f89d533-1473-4318-93e1-3636a25d7251","device_name":"fantastic elf"},{"key":{"public":"q4Dob3gGGLcnYAMs4Twg+yb7pxaPddLv+Pr9nB+98BA=","private":""},"app":false,"ipv4_address":"10.65.184.21/32","ipv6_address":"fc00:bbbb:bbbb:bb01::2:b814/128","ports":[],"city_ports":[],"can_add_ports":false,"created":"2022-07-03","device_id":"3b5dc99e-c98a-4348-85ef-99e226618133","device_name":"live mole"},{"key":{"public":"2P0xqpTAsfaVT8PC2aa5QbMR7JcYpa3xlx3yh+nlNg0=","private":""},"app":false,"ipv4_address":"10.65.217.166/32","ipv6_address":"fc00:bbbb:bbbb:bb01::2:d9a5/128","ports":[],"city_ports":[],"can_add_ports":false,"created":"2022-07-03","device_id":"87ebb8b9-69ee-40d3-be1d-7b4fd7b8ee6a","device_name":"stellar boa"}],"max_wg_peers":5,"can_add_wg_peers":true,"subscription":null}'

    target.enable_debug()
    # myToken = target.mullvad_get_token('0824998250016939')

    # print(target.jq(json_data, '.auth_token'))

    # printt(target.mullvad_get_token('0824998250016939'))

    # myToken = target.mullvad_get_token('0824998250016939')
    # print(json.dumps(target.mullvad_get_wireguard_relays(myToken), indent=4))

    # print(target.wg_genkey())

    # privkey = target.wg_genkey()
    # print(target.wg_pubkey(privkey))

    # myToken = target.mullvad_get_token('0824998250016939')
    # print(target.mullvad_get_status(myToken))

    #pubkey = """CnYth6a6P1BOwxZSNuI6mjadiweU5lCYILJhNfmE3hQ="""
    #query = """.wg_peers | .[] | select( .key.public=="%s")""" % pubkey
    #print(target.jq(json_data2, query))

    # print(target.do_status('0824998250016939'))

    # print(json.dumps(target.do_login('0824998250016939'), indent=4))

    #print(target.mullvad_exist_wg_peer(json_account_status1,
    #      'lilp+FHojtfwR9GH8+3TPdCuYso/Q4uez4zgwk8Emnw='))

    print(target.mullvad_get_wg_peer(json_account_status1,
          'lilp+FHojtfwR9GH8+3TPdCuYso/Q4uez4zgwk8Emnw='))


if __name__ == '__main__':
    main()
