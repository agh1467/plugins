#!/usr/local/bin/python3
# -*- coding: utf-8 -*-


import sys
import json

#sys.path.insert(0, '..')
target = __import__("account")


def main():
    """
    """
    json_data = """
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

    myToken = target.mullvad_get_token('0824998250016939')


if __name__ == '__main__':
    main()
