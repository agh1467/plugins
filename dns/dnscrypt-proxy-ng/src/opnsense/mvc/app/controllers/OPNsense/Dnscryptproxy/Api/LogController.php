<?php

/**
 *    Copyright (C) 2018 Michael Muenz <m.muenz@gmail.com>
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\Dnscryptproxy\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Dnscryptproxy\Plugin;
use OPNsense\Phalcon\Filter\Filter;

/**
 * An ApiControllerBase class used to read log files for dnscrypt-proxy.
 *
 * This class includes the following API actions:
 *
 * `log`
 *
 * This API is accessible at the following URL endpoint:
 *
 * `/api/dnscryptproxy/log/main`
 * `/api/dnscryptproxy/log/blockedips`
 *
 * It supports two actions, clear, and export. These actions can be
 * called after the desired log, like so:
 *
 * `/api/dnscryptproxy/log/nx/clear`
 * `/api/dnscryptproxy/log/query/export`
 *
 * @package OPNsense\Dnscryptproxy
 */
class LogController extends ApiControllerBase
{
    /**
     * Function indexAction() is an API endpoint to call when no parameters are
     * provided for the API. Can be used to test the is working.

     * API endpoint:
     *   /api/dnscryptproxy/logs
     *
     * Usage:
     *   /api/dnscryptproxy/logs
     *
     * Returns an array which gets converted to json.
     *
     * @return array    includes status, saying everything is A-OK
     */
    public function __call($name, $arguments)
    {
        // Example Call: /api/diagnostics/log/core/audit/clear
        //               | this  api     |module|scope|action
        // --limit %s --offset %s --filter %s  --module %s --filename %s --severity %s
        //$itemsPerPage,                        limit     'limit number of results'
        //($currentPage - 1) * $itemsPerPage,   offset    'begin at row number'
        //$searchPhrase,                        filter    'filter results'
        //$module,                              module    'module' aka directory name
        //$scope,                               filename  'log file name (excluding .log extension)'
        //$severities                           severity  'comma separated list of severities'

        // $log name shouldn't have dash in it. the API consumes the dash and capitalizes the next letter.
        // Example: blocked-names becomes blockedNames
        // So either no dashes, or accommodate them down the line.
        $log = substr($name, 0, strlen($name) - 6);
        $action = count($arguments) > 0 ? $arguments[0] : "";

        $searchPhrase = '';

        // create filter to sanitize input data
        $filter = new Filter([
            'query' => function ($value) {
                return preg_replace("/[^0-9,a-z,A-Z, ,*,\-,_,.,\#]/", "", $value);
            }
        ]);

        $plugin = new Plugin();
        $configd_name = $plugin->getConfigdName();
        $configd_action = "log read";

        $backend = new Backend();

        // Prep our result array and variables in case there are no log files to populate it.
        $result = array();
        $result['rows'] = array();
        $result['rowCount'] = 0;
        $result['total'] = 0;
        $result['current'] = 0;
        $result['status'] = 'ok';
        $result['POST'] = $_POST;

        if ($this->request->isPost() && substr($name, -6) == 'Action') {
            $this->sessionClose();
            if ($action == "clear") {
                return ["status" => $backend->configdpRun($configd_name . " log clear " . $log . " YES")];
            } else {
                // fetch query parameters (limit results to prevent out of memory issues)
                $itemsPerPage = $this->request->getPost('rowCount', 'int', 9999);
                $currentPage = $this->request->getPost('current', 'int', 1);

                // Get the search phrase from the POST data.
                if ($this->request->getPost('searchPhrase', 'string', '') != "") {
                    $searchPhrase = $filter->sanitize($this->request->getPost('searchPhrase'), "query");
                }

                // Call the backend, include all of the parameters.
                $response = $backend->configdpRun($configd_name . " " . $configd_action, [
                    $log,
                    $searchPhrase,
                    $itemsPerPage,
                    ($currentPage - 1) * $itemsPerPage
                ]);

                // Parse the output, and copy over relevant data.
                // This might not be efficient, maybe an array merge?
                $response_json = json_decode($response, true);
                if ($response_json != null) {
                    $result['rows'] = $response_json['rows'];
                    $result['rowCount'] = count($response_json['rows']);
                    $result['total'] = $response_json['total_rows'];
                    $result['current'] = (int)$currentPage;
                    //return $result;
                }
            }
        } elseif ($this->request->isGet() && substr($name, -6) == 'Action') {
            if ($action == "export") {

                // Get the search phrase from the GET call.
                if ($this->request->get('searchPhrase', 'string', '') != "") {
                    $searchPhrase = $filter->sanitize($this->request->get('searchPhrase'), "query");
                }

                // Call the backend, but don't specify limit or offset so to get the whole file.
                $response = $backend->configdpRun($configd_name . " " . $configd_action, [
                    $log,
                    $searchPhrase,
                    0,
                    0
                ]);

                // Set some header values so the browser will prompt the user to download the file.
                $this->response->setRawHeader("Content-Type: text/csv");
                $this->response->setRawHeader("Content-Disposition: attachment; filename=" . $log . ".log");
                // Iterate through each row, and format the output to be tab delimited.
                // Works for however many rows there may be.
                foreach (json_decode($response, true)['rows'] as $row) {
                    printf("%s\n", join("\t", array_values($row)));
                }
                return;
            }
        }

        // We're all done, so now return what we have in a way bootgrid expects.
        return $result;
    }

}
