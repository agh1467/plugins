<?php

/*
* OPNsense® is Copyright © 2022 by Deciso B.V.
* Copyright (C) 2022 agh1467@protonmail.com
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

namespace OPNsense\Mullvad\Api;

use OPNsense\Core\Config;
use OPNsense\Core\Backend;
use OPNsense\Base\ApiMutableModelControllerBase;
use OPNsense\Mullvad\PluginUIModelGrid;
use OPNsense\Mullvad\Plugin;
use ReflectionClass;

 /**
  * Class ApiMutableModelControllerBase, inherit this class to implement
  * an API that exposes a model with get and set actions.
  * You need to implement a method to create new blank model
  * objecs (newModelObject) as well as a method to return
  * the name of the model.
  * @package OPNsense\Base
  */
class PluginApiMutableModelControllerBase extends ApiMutableModelControllerBase
{

    /**
     * Array for defining valid targets to use with the functions within
     * this class.
     *
     * @var array $grid_fields
     */
    public $grid_fields = array();

    /**
     * Initilize the class, populate the grid_fields with values from the
     * form XML for this form.
     *
     * This parses the form XML for bootgrid fields, and puts them into a class
     * variable for use by other functions within the class.
     *
     * This eliminates the needs to duplicate this information statically in the
     * class itself. All data is dynamically pulled from the form XML instead.
     *
     */
    public function initialize()
    {
        // Initialize the ApiMutableModelControllerBase
        parent::initialize();

        // Create an instance of our plugin local controller base.
        $plugin = new Plugin();

        // Derive which form XML we should be parsing based on the class file name.
        // XXX assuming the file name ends in "Controller.php" case sensitive.
        // XXX assuming lower case xml file name since that's the convention.
        // XXX maybe use $internalModelName instead?
        // XXX maybe use a separate variable $internalFormName?
        $class_info = new ReflectionClass($this);
        $form_name = strtolower(str_replace("Controller.php","",basename($class_info->getFileName())));

        // XXX Ad says that controllers aren't intended to be called directly like this.
        $form_xml = $plugin->getFormXml($form_name); // Pull in the form data.
        // Get bootgrids from the form data.
        $this->getGrids($form_xml);
    }


    /**
     * Get all of the bootgrid fields defined in the form XML.
     *
     * This is used by initiatize() to pull bootgrid fields,
     * and their respsective targets, and fields.
     *
     * @param   SimpleXMLObject   $form_xml The form's SimpleXMLObject XML data to be parsed.
     * @return  array                       Associtative array of targets, and their field names.
     */
    private function getGrids($form_xml)
    {
        // Get the targets for all bootgrids from the $form_xml.
        $targets = $form_xml->xpath('//*/field[type="bootgrid"]/target');
        // XXX need error handling for when xpath doesn't find results.
        foreach ($targets as $target_obj) {
            // Typecast the SimpleXMLObject as a string.
            $target_str = (string) $target_obj;

            // Identify if a toggle API is defined, and if there is one, then add an "enabled" column.
            $enabled = $form_xml->xpath('//*/field[type="bootgrid"][target="' . $target_str . '"]/api/toggle');
            if (count($enabled) > 0) {
                $this->grid_fields[$target_str]['columns'][] = 'enabled';
            }

            $mode = $form_xml->xpath('//*/field[type="bootgrid"][target="' . $target_str . '"]/mode');
            if (count($mode) > 0) {
                $this->grid_fields[$target_str]['mode'] = (string) $mode[0];
            }

            // Now we need get our columns for each target.
            $columns = $form_xml->xpath('//*/field[type="bootgrid"][target="' . $target_str . '"]/columns/column/@id');
            // XXX need error handling for when xpath returns no results
            foreach ($columns as $column) {
                // XXX naming the array columns isn't really necessary, maybe take it out later.
                $this->grid_fields[$target_str]['columns'][] = (string) $column;
            }
        }
    }

    /**
     * This is a super API for Bootgrid which will do all the things.
     *
     * Instead of having many copies of the same functions over and over, this
     * function replaces all of them, and it requires only setting a couple of
     * variables and adjusting the conditional statements to add or remove
     * grids.
     *
     * API endpoint:
     *
     *   `/api/dnscryptproxy/settings/grid`
     *
     * Parameters for this function are passed in via POST/GET request in the URL like so:
     * ```
     * |-------API Endpoint (Here)-----|api|----$target---|--------------$uuid----------------|
     * api/dnscryptproxy/settings/grid/get/servers.server/9d606689-19e0-48a7-84b2-9173525255d8
     * ```
     * This handles all of the bootgrid API calls, and keeps everything in
     * a single function. Everything is controled via the `$target` and
     * pre-defined variables which include the config path, and the
     * key name for the edit dialog.
     *
     * A note on the edit dialog, the `$key_name` must match the prefix of
     * the IDs of the fields defined in the form data for that dialog.
     *
     * Example:
     * ```
     *  <field>
     *     <id>server.enabled</id>
     *     <label>Enabled</label>
     *     <type>checkbox</type>
     *     <help>This will enable or disable the server stamp.</help>
     *  </field>
     * ```
     *
     * For the case above, the `$key_name` must be: "server"
     *
     * This correlates to the config path:
     *
     * `//OPNsense/dnscrypt-proxy/servers/server`
     *
     * `servers` is the ArrayField that these bootgrid functions are designed
     *           for.
     *
     * `server`  is the final node in the config path, and are
     *           entries in the ArrayField.
     *
     * The `$key_name`, the final node in the path, and the field ids in the form
     * XML must match. The field <id> is important because when `mapDataToFormUI()`
     * runs to populate the fields with data, the scope is just the dialog
     * box (which includes the fields). It will try to match ids with the
     * data it receives, and it splits up the ids at the period, using the
     * first element as its `key_name` for matching. This is also how the main
     * form works, and why all of those ids are prefixed with the model name.
     *
     * So get/set API calls return a JSON with a key named 'server', and the
     * data gets sent to fields which have a dotted prefix of the same name.
     * This links these elements together, though they are not directly
     * linked, only merely aligned together.
     *
     * Upon saving (using `setBase()`) it sends the POST data specified
     * in the function call wholesale, that array has to overlay perfectly
     * on the model.
     *
     * @param string       $action The desired action to take for the API call.
     * @param string       $target The desired pre-defined target for the API.
     * @param string       $uuid   The UUID of the target object.
     * @return array Array to be consumed by bootgrid.
     */
    public function bootgridAction($action, $target, $uuid = null)
    {
        if (in_array($action, array(
                'search',
                'get',
                'set',
                'add',
                'del',
                'toggle',
            ))
        ) { // Check that we only operate on valid actions.
            if (array_key_exists($target, $this->grid_fields)) {  // Only operate on valid targets.
                $tmp = explode('.', $target);  // Split target on dots, have to use a temp var here.
                $key_name = end($tmp);         // Get the last node from the path, and this will be our $key_name.


                switch (true) {
                    case ($action === 'search' && isset($this->grid_fields[$target])):
                        // Take care of special mode searches first.
                        if (isset($this->grid_fields[$target]['mode'])) {
                            if ($this->grid_fields[$target]['mode'] == 'configd_cmd') {
                                // Create our own UIModelGrid object.
                                $grid = new PluginUIModelGrid(null);
                                // Create a Plugin class object to use for configd_name.
                                $plugin = new Plugin();
                                return $grid->fetchConfigd(
                                    $this->request,
                                    $plugin->getConfigdName(),
                                    $this->grid_fields[$target]['columns']
                                );
                            }
                        } elseif (isset($target)) { // All other searches, check $target is set.
                            return $this->searchBase($target, $this->grid_fields[$target]['columns']);
                        }
                        break;
                    case ($action === 'get' && isset($key_name) && isset($target)):
                        $response = $this->getBase($key_name, $target, $uuid);
                        break;
                    case ($action === 'add' && isset($key_name) && isset($target)):
                        $response = $this->addBase($key_name, $target);
                        break;
                    case ($action === 'del' && isset($target) && isset($uuid)):
                        $response = $this->delBase($target, $uuid);
                        break;
                    case ($action === 'set' && isset($key_name) && isset($target) && isset($uuid)):
                        $response = $this->setBase($key_name, $target, $uuid);
                        break;
                    case ($action === 'toggle' && isset($target) && isset($uuid)):
                        $response = $this->toggleBase($target, $uuid);
                        break;
                    default:
                        // If not, there was some other issue.
                        $response['message'] =
                            'Some parameters were missing for action "' . $action . '" on target "' . $target . '"';
                        break;
                }
                // Maybe a change was made to the config, and we need to mark it before returning.
                // Only check if there has been CRUD requested.
                if (in_array($action, array(
                    'add',
                    'del',
                    'set',
                    'toggle'
                ))) {
                    // Validate that we have a result key, and check it against known good results.
                    if (array_key_exists('result',$response)) {
                        if (in_array($response['result'], array(
                            'saved',
                            'deleted',
                            'Disabled',
                            'Enabled'
                        ))) {
                            // We know that something was saved/deleted/toggled.
                            $this->markConfig('dirty');
                            return $response;
                        }
                    }
                }
            } else {
                $response['message'] = 'Unsupported target ' . $target;
            }
        } else {
            $response['message'] = 'Action "' . $action . '" not found.';
        }
        // Since we've gotten here, no valid options were presented,
        // we need to return a valid array for the bootgrid to consume though.
        $response['rows'] = array();
        $response['rowCount'] = 0;
        $response['total'] = 0;
        $response['current'] = 1;
        $response['status'] = 'failed';

        return $response;
    }

    /**
     * Returns all nodes in string form.
     *
     * This is simlar to BaseField::getNodes(), but doesn't call getNodeData().
     *
     * Instead it casts the $node as a string, which flattens out the value.
     *
     * This results in returning only the UUID (or default/blank value) of the
     * selected option.
     *
     * @param $parent_node BaseField node to reverse
     */
    private function getStringNodes($parent_node)
    {
        $result = array();
        foreach ($parent_node->iterateItems() as $key => $node) {
            if ($node->isContainer()) {
                $result[$key] = $this->getStringNodes($node);
            } else {
                $result[$key] = (string)$node;
            }
        }
        return $result;
    }

    /**
     * Export entries out of an ArrayField type node in the config..
     *
     * Uses a pre-defined set of targets (paths) to prevent arbitrary
     * export of data.
     *
     * API endpoint:
     *   /api/dnscryptproxy/settings/export
     *
     * Expects to receive a value "target" defined in the data in the GET
     * request.
     *
     * Example usage (in Javascript):
     * ajaxGet("/api/dnscryptproxy/settings/export",
     *          {"target": "allowed_names_internal"},
     *          function(data, status){...
     *
     * The second parameter is the data (array) where "target" is defined.
     */
    public function exportGridAction()
    {
        // Check that this function is being called by a GET request.
        if ($this->request->isGet()) {
            // Retrive the value of the target key in the GET request.
            $target = $this->request->get('target');
            if (! is_null($target)) {  // If we have a target, check it against the list.
                if (array_key_exists($target, $this->grid_fields)) {  // Only operate on valid targets.
                    // Get the model, and walk to the appropriate path.

                    $mdl = $this->getModel();
                    foreach (explode('.', $target) as $step) {
                        $mdl = $mdl->{$step};
                    }

                    // Send via HTTP response the content type.
                    $this->response->setContentType('application/json', 'UTF-8');
                    // Send via HTTP response the JSON encoded array of the node.
                    $this->response->setContent(json_encode($this->getStringNodes($mdl)));
                } else {
                    return array('status' => 'Specified target "' . $target . "' does not exist.");
                }
            } else {
                throw new UserException('Unsupported request type');
            }
        }
    }

    /**
     * Import data provided by the user in a file upload into ArrayField type
     * nodes in the config.
     *
     * API endpoint:
     *
     * `/api/dnscryptproxy/settings/import`
     *
     * Takes data from POST variables `data` and `target`, validates accordingly,
     * then updates existing objects with the same UUID, or creates new entries
     * then saves the entries into the config.
     *
     * Example usage (Javascript):
     * ```
     * ajaxCall("/api/dnscryptproxy/settings/import",
     *          {'data': import_data,'target': 'allowed_names_internal' },
     *          function(data,status) {...
     * ```
     * The second paramter is the data (array) where `data`, and `target` are
     * defined.
     *
     * No support for `CSVListField` types within an ArrayField type.
     * Attempting currently returns error:
     * ```
     * Error at /usr/local/opnsense/mvc/app/models/OPNsense/Base/FieldTypes/BaseField.php:639
     * It is not yet possible to assign complex types to properties (errno=2)
     * ```
     * This was mostly copied from the firewall plugin.
     */
    public function importGridAction()
    {
        if ($this->request->isPost()) {
            $this->sessionClose();
            $result = array('import' => 0, 'status' => '');

            // Get target, and data from the POST.
            $data = $this->request->getPost('data');
            $target = $this->request->getPost('target');

            if (! is_null($target)) {  // Only do stuff if target is actually set.
                if (is_array($data)) {  // Only do this if the data we have is an array.
                    if (array_key_exists($target, $this->grid_fields)) {  // Only operate on valid targets.
                        // Get a lock on the config.
                        Config::getInstance()->lock();
                        // Get the model for use later. (used for updating records)
                        $mdl = $this->getModel();
                        // Create a second model object that is walked to the last node. (used for new records)
                        $tmp = $mdl;
                        foreach (explode('.', $target) as $step) {
                            $tmp = $tmp->{$step};
                        }
                        $counter = 0;
                        // For each data[n], store it as uuid (string) and its content (array).
                        foreach ($data as $uuid => $content) {
                            $valMsgs = $this->getModel()->performValidation();
                            // Reset the node on each iteration.
                            $node = null;
                            // Only do if our content is the correct format.
                            if (is_array($content)) {
                                // If the node exists (by UUID), this selects the node.
                                $node = $mdl->getNodeByReference($target . '.' . $uuid);
                                // If no node is found, create a new node.
                                if ($node == null) {
                                    $node = $tmp->Add();
                                }

                                // Set the new or found node to the content.
                                $node->setNodes($content);
                                // Increment the import counter.
                                $result['import'] += 1;
                            }
                        }

                        // Create the uuid mapping for validation messaging.
                        $uuid_mapping = array();
                        $valMsgs = $mdl->performValidation();

                        // perform validation, record details.
                        foreach ($valMsgs as $msg) {
                            if (empty($result['validations'])) {
                                $result['validations'] = array();
                            }
                            $parts = explode('.', $msg->getField());
                            $uuid = $parts[count($parts) - 2];
                            $fieldname = $parts[count($parts) - 1];
                            $uuid_mapping[$uuid] = "$uuid";
                            $result['validations'][$uuid_mapping[$uuid] . '.' . $fieldname] = $msg->getMessage();
                        }

                        // possibly use save() from ApiMutableModelControllerBase
                        // only persist when valid import
                        if (empty($result['validations'])) {
                            $result['status'] = 'ok';
                            $this->save();
                        } else {
                            $result['status'] = 'failed';
                            Config::getInstance()->unlock();
                        }
                    } else {
                        return array('status' => 'Specified target "' . $target . "' does not exist.");
                    }
                }
            } else {
                throw new UserException('Unsupported request type');
            }
            // Return messages, either success or failure.
            return $result;
        }
    }

    /**
     * This function overrides the setAction() provided by
     * ApiMutableServiceControllerBase.
     *
     * In addition to calling the parent::setAction() it marks the
     * configuration as dirty using configd.
     *
     * API endpoint:
     *   /api/dnscryptproxy/settings/set
     *
     *
     * @return array setAction() result or error message from configd.
     */
    public function setAction()
    {
        // Call the reconfigure action to save our settings.
        $set_result = parent::setAction();

        $response = $this->markConfig('dirty');

        // Add the message to set_result prefixed with where the message came from for clarity.
        $set_result['message'] = 'Configd returned: ' . $response;

        if ($response != 'OK') {
            // Set the error status so API will display message to user.
            $set_result['status'] = "error";
        }

        return $set_result;
    }

    public function markConfig($action)
    {
        if (in_array($action, array(
            'dirty',
            'clean'
            ))
        ) {
            // Create a Plugin class object to get plugin variables.
            // (library/OPNsense/Dnscryptproxy/Plugin.php)
            $plugin = new Plugin();

            // Create a backend to run our activities.
            $backend = new Backend();

            return trim($backend->configdpRun($plugin->getConfigdName() . ' make ' . $action));
        }
        return array('status' => 'error', 'message' => 'Invalid action ' . $action . ' provided.');
    }

    /**
     * An API endpoint to return the clean/dirty state of the config.
     *
     * API endpoint:
     *
     *   `/api/dnscryptproxy/settings/state`
     *
     * Usage:
     *
     *   `/api/dnscryptproxy/settings/state`
     *
     * Returns an array containing the dirty state.
     *
     * @return array    [state] = dirty/clean
     */
    public function stateAction()
    {
        $result = array();

        // Create a Plugin class object to get plugin variables.
        // (library/OPNsense/Dnscryptproxy/Plugin.php)
        $plugin = new Plugin();

        // Create a backend to run our activities.
        $backend = new Backend();
        $response = trim($backend->configdpRun($plugin->getConfigdName() . ' state'));

        if (!in_array($response, array(
            'dirty',
            'clean'
            ))
        ) {
            // Return an array containing a reponse for the message box to display.
            return array('status' => 'error', 'message' => $response);
        } else {
            return array('status' => 'ok', 'state' => $response);
        }
    }

}
