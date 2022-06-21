#!/usr/local/bin/python3
# -*- coding: utf-8 -*-

"""
    Copyright (c) 2014-2019 Ad Schellevis <ad@opnsense.org>
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

    package : Dnscryptproxy
    function: log hanlder for reading, and clearing logs.
"""

import os
import subprocess
import re
import sre_constants
import argparse
import json
import sys

# Log directory to use for all of the log files.
LOG_DIR = '/var/log/dnscrypt-proxy'

# These are recurring patterns that are used across most of the files."""
"""
Tabbed column, matches columns in tab-separated-values file.
Explanation: A tab, and then group any character not a tab
             any number of times.
"""
PATTERN_TSV_COLUMN = r'\t([^\t]*)'
"""
Time stamp, matches the timestamp in all dnscrypt-proxy log files.
Explanation: At the start of the line, match a literal '[', and group
             any character not ']' any number of times, followed by a ']'.
"""
PATERN_TIMESTAMP = r'^\[([^\]]*)]'

"""
This dictionary contains all of the necessary
values to parse each specific log file.
It's also used to validate argument input in main().
"""
LOGS = \
    {"main":
        {"file":
            LOG_DIR + '/main.log',
         "columns":
            {'timestamp':        PATERN_TIMESTAMP + ' ',
             'severity':         r'\[([^\]]*)] ',
             'msg':              r'(.*)'
             },
         },
     "query":
         {"file":
            LOG_DIR + '/query.log',
          "columns":
            {'timestamp':        PATERN_TIMESTAMP,
             'client_ip':        PATTERN_TSV_COLUMN,
             'query_name':       PATTERN_TSV_COLUMN,
             'query_type':       PATTERN_TSV_COLUMN,
             'return_code':      PATTERN_TSV_COLUMN,
             'request_duration': PATTERN_TSV_COLUMN,
             'server_name':      PATTERN_TSV_COLUMN + '\n'
             },
          },
     "nx":
         {"file":
            LOG_DIR + '/nx.log',
          "columns":
            {'timestamp':        PATERN_TIMESTAMP,
             'client_ip':        PATTERN_TSV_COLUMN,
             'query_name':       PATTERN_TSV_COLUMN,
             'query_type':       PATTERN_TSV_COLUMN + '\n'
             },
          },
     "blockednames":
         {"file":
            LOG_DIR + '/blocked-names.log',
          "columns":
            {'timestamp':        PATERN_TIMESTAMP,
             'client_ip':        PATTERN_TSV_COLUMN,
             'query_name':       PATTERN_TSV_COLUMN,
             'matching_rule':    PATTERN_TSV_COLUMN + '\n'
             },
          },
     "blockedips":
         {"file":
            LOG_DIR + '/blocked-ips.log',
          "columns":
            {'timestamp':        PATERN_TIMESTAMP,
             'client_ip':        PATTERN_TSV_COLUMN,
             'query_name':       PATTERN_TSV_COLUMN,
             'ip':               PATTERN_TSV_COLUMN,
             'matching_rule':    PATTERN_TSV_COLUMN + '\n'
             },
          },
     "allowednames":
         {"file":
            LOG_DIR + '/allowed-names.log',
          "columns":
            {'timestamp':        PATERN_TIMESTAMP,
             'client_ip':        PATTERN_TSV_COLUMN,
             'query_name':       PATTERN_TSV_COLUMN,
             'matching_rule':    PATTERN_TSV_COLUMN + '\n'
             },
          },
     "allowedips":
         {"file":
            LOG_DIR + '/allowed-ips.log',
          "columns":
            {'timestamp':        PATERN_TIMESTAMP,
             'client_ip':        PATTERN_TSV_COLUMN,
             'query_name':       PATTERN_TSV_COLUMN,
             'ip':               PATTERN_TSV_COLUMN,
             'matching_rule':    PATTERN_TSV_COLUMN + '\n'
             },
          }
     }

args = sys.argv


def error_out(message):
    """ Error handling function.
    """
    result = [{'error': message, 'status': "failed"}]
    print(json.dumps(result, indent=4))
    sys.exit()


def log_parse(log, filter_regex, limit, offset, result):
    """
    This function will parse a log given it matches a pre-defined index
    in the LOGS global static.
    """
    # Derive our values from the global static for the desired log.
    log_file = LOGS[log]['file']
    field_names = LOGS[log]['columns'].keys()
    pattern = re.compile(''.join(LOGS[log]['columns'].values()))
    try:
        with open(log_file) as file:
            row_num = 0
            # Get the total number of lines in the file for a total_row count.
            for row_count, line in enumerate(file):
                pass
            # Set the row count so the bootgrid has a correct total to display.
            try:
                result['total_rows'] = row_count + 1
            except UnboundLocalError:
                result['total_rows'] = 0
            # Re-seek to the start of the file to read the lines individually.
            file.seek(0)
            for line in reversed(file.readlines()):
                row_num += 1
                if line != "" and filter_regex.match(('%s' % line).lower()):
                    # Increment the row counter for each row to be processed.
                    if (len(result['rows']) < limit or limit == 0) \
                       and row_num > offset:
                        # Get the regex matched group values
                        # from the line into a list.
                        fields = re.match(pattern, line).groups()
                        # Append to the result a dict containing all
                        # of the key:value pairs for this row.
                        result['rows'].append({field_name: fields[idx]
                                              for idx, field_name
                                              in enumerate(field_names)})
                    elif limit > 0 and row_num > offset + limit:
                        # break out early because we've reached the limit
                        break
    except BaseException as error:
        result.append({
            'error': "Unexpected error: " + format(error),
            'status': "failed"})
        return result

    return result


def main():
    """
    This main function will check arguments against valid keys in the
    LOGS global static, and start parse if everything checks out.
    """
    output = {}
    parser = argparse.ArgumentParser()
    parser.add_argument('--log', help='log file (excluding .log extension)',
                        default='')
    parser.add_argument('--filter', help='filter results',
                        default='')
    parser.add_argument('--limit', help='limit number of results',
                        default='')
    parser.add_argument('--offset', help='begin at row number',
                        default='')
    parser.add_argument('--clear', help='clear the chosen log',
                        default='')
    inputargs = parser.parse_args()

    result = {'filters': inputargs.filter,
              'rows': [],
              'total_rows': 0,
              'origin': inputargs.log
              }

    # Check that we have args[1] to work with.
    if inputargs.log != "":
        log = inputargs.log
        if inputargs.clear == "YES":
            # XXX This is a janky way of doing this. Need to find a better way.
            if log in LOGS.keys():
                filename = os.path.basename(
                    os.path.splitext(LOGS[log]['file'])[0])
                clearlog = subprocess.run(
                    [
                        '/usr/local/sbin/configctl',
                        'system',
                        'clear',
                        'log',
                        'dnscrypt-proxy',
                        filename
                    ],
                    capture_output=True, text=True).stdout.strip("\n")
                print(clearlog)
                sys.exit()

        limit = int(inputargs.limit) if inputargs.limit.isdigit() else 0
        offset = int(inputargs.offset) if inputargs.offset.isdigit() else 0
        try:
            filter = inputargs.filter.replace('*', '.*').lower()
            if filter.find('*') == -1:
                # no wildcard operator, assume partial match
                filter = ".*%s.*" % filter
            filter_regex = re.compile(filter)
        except sre_constants.error:
            # remove illegal expression
            filter_regex = re.compile('.*')

        # Check that the desired argument is a valid log in our dictionary.
        if log in LOGS.keys():
            # Run the log parser since we know it's safe.
            output = log_parse(log, filter_regex, limit, offset, result)

    print(json.dumps(output, indent=4))


if __name__ == '__main__':
    main()
