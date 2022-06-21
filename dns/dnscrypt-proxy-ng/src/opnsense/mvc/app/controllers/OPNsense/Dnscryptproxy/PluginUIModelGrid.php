<?php

/*
 * Copyright (C) 2015 Deciso B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Dnscryptproxy;

use OPNsense\Core\Backend;
use OPNsense\Core\Config;
use OPNsense\Base\UIModelGrid;

/**
 * Class PluginUIModelGrid Grid control support functions
 * @package OPNsense\Dnscryptproxy
 */
class PluginUIModelGrid extends UIModelGrid
{
    /**
     * Execute a configd command, and return the output in a format consumable
     * by bootgrid.
     *
     * It executes configd with the given commands, and returns JSON for bootrid
     * to consume. It works similar to, and sourced mostly from `searchBase()`,
     * and UIModelGrid() but with some differences and added functionality.
     *
     * Takes most parameters via POST just like *Base() functions.
     *
     * Lots of comments because this function is a bit confusing to start.
     *
     * @param string  $configd_cmd   path to search, relative to this model
     * @param array   $fields        field names to fetch in result
     * @return array                 bootgrid compatible structured array
     */
    public function fetchConfigd($request, $configd_cmd, $fields)
    {
        $backend = new Backend();
        $sort = SORT_ASC;
        $sortBy = array($fields[0]); // set a default column to sort with as the first column defined.
        // Get the items per page, and current page from the GET sent by bootgrid.
        $itemsPerPage = $request->get('rowCount', 'int', -1);
        $currentPage = $request->get('current', 'int', 1);
        // Set up the sort stuff.
        if ($request->has('sort') && is_array($request->get('sort'))) {
            $sortBy = array_keys($request->get('sort'));
            if ($request->get('sort')[$sortBy[0]] == 'desc') {
                $sort = SORT_DESC;
            }
        }
        // Grab the search phrase from the GET call.
        $searchPhrase = $request->get('searchPhrase', 'string', '');
        $rows = array();

        //$this->sessionClose();
        $result = array('rows' => array());
        $recordIndex = 0;

        // Run the configd command and get the results put into an array.
        // Expects to receive JSON. Maybe add validation later.
        $response = json_decode($backend->configdpRun($configd_cmd), true);

        if (! empty($response)) {
            // Pivot the data to create arrays of each column of data.
            // ex. rows['description'], rows['nolog'], etc.
            // These are used to sort based on column.
            foreach ($response as $item) {
                foreach ($item as $key => $value) {
                    if (! isset($rows[$key])) { // Establish the row if it does not already exist.
                        $rows[$key] = array();
                    }
                    $rows[$key][] = $value;
                }
            }

            // This bit here is taking the desired sort column sent as param
            // and sorting it ASC, or DESC also sent as param.
            // At the same time it's taking the cooresponding index from
            // $reponse and put it in the same position as the sorted row.
            // This sorts by the desired column $rows, and rearranges $response
            // to be in the same order, thus sorting $response by the desired
            // column.
            if ($request->has('sort') && is_array($request->get('sort'))) {
                array_multisort($rows[$sortBy[0]], $sort, $response);
            }
            // ^ This will throw an error if an element is missing from one of
            // the entries. Like description being missing on static server entries.
            // $rows will be shorter than $response. This has been mitigated by
            // adding an empty description field in the script.

            // This was copied almost wholesale from UIModelGrid(), if it ain't broke.
            // I added a boolean check in the search bit.
            foreach ($response as $row) {
                // if a search phrase is provided, use it to search in all requested fields
                if (! empty($searchPhrase)) {
                    $searchFound = false;

                    foreach ($fields as $fieldname) {  //Iterate through the field list provided as function param.
                        // For each field in the row, we check to see if the searchPhrase is found, one at a time.
                        // Catch a corner case where a row is missing from the data,
                        // test for null (manually defined servers have no description).
                        $field = (isset($row[$fieldname]) ? $row[$fieldname] : null);
                        if (! is_null($field)) {
                            if (is_array($field)) {  // Only do if this is an array
                                foreach ($field as $fieldvalue) {
                                    if (strtolower($searchPhrase) == strtolower($fieldname)) {
                                        // If the field name happens to match the searchPhrase, we might be a boolean.
                                        if (is_int($fieldvalue) && ($fieldvalue == 0 || $fieldvalue == 1)) {
                                            // Guess if the field is a boolean.
                                            if ($fieldvalue == 1) {
                                                // Guessing that int(1) will mean true.
                                                // Have to use 0 or 1 here because opensense_bootgrid_plugin.js
                                                // uses that instead of true/false.
                                                $searchFound = true;

                                                break;
                                            }
                                            // $fieldvalue is 0, so we should abort the rest of the columns.
                                            // We assume that we're searching for a bool, and if this is 0
                                            // then this row should not be included. Do not set $searchFound,
                                            // but still break out of the loop, effectively skipping the row.
                                            // Not ideal as it prevents string searching for a value
                                            // the same as a field name in the rest of the colums.
                                            // Need some special syntax to signify a bool search via $searchPhrase.
                                            break;
                                        }
                                    } elseif (strpos(strtolower($fieldvalue), strtolower($searchPhrase)) !== false) {
                                        $searchFound = true;

                                        break;
                                    }
                                }
                            } else {
                                if (strtolower($searchPhrase) == strtolower($fieldname)) {
                                    // If the field name happens to match the searchPhrase, we might be a boolean.
                                    if (is_int($field) && ($field == 0 || $field == 1)) {
                                        // Guess if the field is a boolean.
                                        if ($field == 1) {
                                            # Guessing that int(1) will mean true.
                                            $searchFound = true;

                                            break;
                                        }
                                        // $field is 0, so we should abort the rest of the columns.
                                        // We assume that we're searching for a bool, and if this is 0
                                        // then this row should not be included. Do not set $searchFound,
                                        // but still break out of the loop, effectively skipping the row.
                                        // Not ideal as it prevents string searching for a value
                                        // the same as a field name in the rest of the colums.
                                        // Need some special syntax to signify a bool search via $searchPhrase.
                                        break;
                                    }
                                } elseif (strpos(strtolower($field), strtolower($searchPhrase)) !== false) {
                                    $searchFound = true;

                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // If there is no search phrase, we assume all rows are relevent.
                    $searchFound = true;
                }

                // if result is relevant, count total and add (max number of) items to result.
                // $itemsPerPage = -1 is used as wildcard for "all results"
                if ($searchFound) {
                    if (
                        (count($result['rows']) < $itemsPerPage &&
                        $recordIndex >= ($itemsPerPage * ($currentPage - 1)) || $itemsPerPage == -1)
                    ) {
                        $result['rows'][] = $row;
                    }
                    $recordIndex++;
                }
            }
        }
        // We're all done, so now return what we have in a way bootgrid expects.
        $result['rowCount'] = count($result['rows']);
        $result['total'] = $recordIndex;
        $result['current'] = (int) $currentPage;
        $result['status'] = 'ok';
        $result['POST'] = $_POST;
        $result['params'] = array('configd_cmd' => $configd_cmd, 'fields' => $fields);
        //$result['response'] = $response;

        return $result;
    }

}
