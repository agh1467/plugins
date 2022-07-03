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

    target.enable_debug()

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


if __name__ == '__main__':
    main()
