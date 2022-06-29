<?php

/**
 *    Copyright (C) 2020 Deciso B.V.
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
 */

namespace OPNsense\Mullvad\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Dnscryptproxy\Plugin;
use OPNsense\Base\ApiMutableModelControllerBase;

/**
 * This Controller extends ApiControllerBase to create API endpoints
 * for file uploading and downloading.
 *
 * This API is accessiable at the following URL endpoint:
 *
 * `/api/dnscryptproxy/file`
 *
 * This class includes the following API endpoints:
 * ```
 * upload
 * download
 * remove
 *
 * ```
 *
 * @package OPNsense\Dnscryptproxy
 */
class AccountController extends ApiMutableModelControllerBase
{

    /**
     * This variable defines what to call the <model> that is defined for this
     * Class by Phalcon. That is to say the model XML that has the same name as
     * this controller's name, "Settings".
     * In this case, it is the model XML file:
     *
     * `model/OPNsense/Mullvad/Settings.xml`
     *
     * The model name is then used as the name of the array returned by setBase()
     * and getBase(). In the form XMLs, the prefix used on the field IDs must
     * match this name as API actions use the same name in their transactions.
     * For example, the key_name in an API JSON response, will be this model
     * name. This name is also used as the API endpoint for this Controller.
     *
     * `/api/mullvad/settings`
     *
     * This locks activies of this Class to this specific model, so it won't
     * save to other models, even within the same plugin.
     *
     * @var string $internalModelName
     */
    protected static $internalModelName = 'settings';

    /**
     * Base model class to reference.
     *
     * This variable defines which class to call for getMode(). It is used in a
     * ReflectionClass call to establish the model object. This class is defined
     * in the models directory alongside the model XML, and has the same name
     * as this Controller. This class extends BaseModel which reads the model
     * XML that has the same name as the class.
     *
     * In this case, these are the model XML file, and class definition file:
     *
     * `model/OPNsense/Mullvad/Settings.xml`
     *
     * `model/OPNsense/Mullvad/Settings.php`
     *
     * These together will establish several API endpoints on this Controller's
     * endpoint including:
     *
     * `/api/mullvad/settings/get`
     *
     * `/api/mullvad/settings/set`
     *
     * These are both defined in the ApiMutableModelControllerBase Class:
     *
     * `function getAction()`
     *
     * `function setAction()`
     *
     * @var string $internalModelClass
     */
    protected static $internalModelClass = 'OPNsense\Mullvad\Settings';

    /**
     * Allows uploading a file using a specific pre-defined list of destination
     * files within the file system.
     *
     * API endpoint:
     *
     *   `/api/dnscryptproxy/file/set`
     *
     * Usage:
     *
     *   `/api/dnscryptproxy/set/settings.allowed_names_file_manual`
     *
     * This function only accepts specific `$target` variables to prevent user
     * manipulation through the API. It stores the file in a temporary location
     * and then a configd command executes a script which parses that file and
     * validates the contents, then copies that file to the pre-defined
     * destination.
     *
     * This function is intented to be called via SimpleActionButton(), which
     * which expects the follwing in the reply:
     * (status != "success" || data['status'].toLowerCase().trim() != 'ok') &&
     * data['status']
     *
     * So the reply needs to be JSON, with a "status" node.
     *
     * The value of "status" will be displayed in the body of the message box if
     * status != 'ok'.

     * @return array          Array of the contents of the file.
     * @throws \Phalcon\Validation\Exception on validation issues
     * @throws \ReflectionException when binding to the model class fails
     * @throws UserException when denied write access
     */
    public function LoginAction()
    {
        // Use the account in a configctl call to perform the "login" procedure.
        // We'll need to get back from the call:
        // Device Name
        // Private Key
        // Login is going to be something like register the device, get the device name in return.
        // This is probably something like create wireguard private key, and then add that private
        //   key to the account.
        // Save that device name to the config.
        // Save the private key to the config?
        // Flip the bit for account_configured.
        // return status to API
        // Somehow call mapDataToFormUI(), or force a page refresh.
        //
        $result = array();
        if ($this->request->isPost()) {

            // Retrieve the account number from the model. This should be known good, but check that it's not empty anyway.
            $account_number = $this->getModel()->account_number->getNodeData('clean');
            // We won't be able to do anything without an account number.
            if (!empty($account_number)) {
                $backend = new Backend();
                $result = json_decode($backend->configdRun($plugin->configd_name . ' login ' . $account_number));
                if (json_last_error() === JSON_ERROR_NONE) {
                    // JSON is valid
                    if ($result['result'] == 'success') {

                    } else {
                        // Do nothing?
                    }
                }
            // Need to check that the result is good, look for 'result' == 'success'
            // If failed, then reflect hat in a return.
            // Might be able to pass the result array through as long as the format is consistent.
            //        $result = array('result' => 'failed', 'status' => gettext('No valid account number in config.'));
            /*
            Expecting to see something like this in json:
            "device_id" : "0f8c65c1-9df4-4db5-9c4d-7a8e86dad149",
            "device_name" : "alert pup",
            "ipv4_address" : "10.66.141.99/32",
            "ipv6_address" : "fc00:bbbb:bbbb:bb01::3:8d62/128",
            "private_key" : "",
            "public_key" : "zLVnO6DYynxeaSc0qECNJSnAqwkOvMU0xE82KqniugI="
            "action" : 'login' # is this useful?
            "result" : 'success'
            "status" : ?????

            $arr = json_decode($jsonobj, true);
            echo $arr["Peter"];
            echo $arr["Ben"];
            echo $arr["Joe"];
            // ^^^^^^^^^^^^ Interesting approach, probably do that.
            Convert json -> array: $device_data = json_decode($result);
            Transfer values out of the array into another array that's the same structure as the model.
            We need to take this data, and then save it to the config.
            Something like: setData(device_data)
            */

            } else {
                $result = array('result' => 'failed', 'status' => gettext('No valid account number in config.'));
            }
        } else {
            $result = array("status" => 'failed', 'status' => gettext('Function must be called via HTTP POST.'));
        }
        //array ("status" => 'ok', 'account_number' => $account_number);
        return $result;
        //return array("status" => 'failed');
        //--------------------------------------------------------------------------//
        $plugin = new Plugin();

        // we only care about the content, the file name will be statically configured
        // this will reduce the need to manage the file system like cleaning up if a file name changes, etc.
        // it also mitigates file name length limitations of the file system.
        // it also mitigates risk of allowing the user to specify the file name
        if ($this->request->isPost() && $this->request->hasPost('content') && $this->request->hasPost('target')) {
            // Populate variables from the keys in the POST.
            $content = $this->request->getPost('content', 'striptags', '');
            $target = $this->request->getPost('target');

            // Check the content length so we have something to do.
            if (strlen($content) > 0 && ! is_null($target)) {
                // I looked for a better way to do this, but didn't find any.
                // Due to shell command length limitations it's risky to pass this
                // directly to configdRun(), and no way to get it to send the content
                // as stdin. So a second best is to write it to the file system
                // for read by an application afterwards. CaptivePortal does this method.
                if (
                    $target == 'settings.blocked_names_file_manual' ||
                    $target == 'settings.blocked_ips_file_manual' ||
                    $target == 'settings.allowed_names_file_manual' ||
                    $target == 'settings.allowed_ips_file_manual' ||
                    $target == 'settings.cloaking_file_manual'
                ) {
                    // create a temporary file name to use
                    $temp_filename = '/tmp/' . $plugin->name . '_file_upload.tmp';
                    // let's put the file in /tmp
                    file_put_contents($temp_filename, $content);
                    $target_exp = explode('.', $target);

                    $backend = new Backend();
                    // Perform the import using configd. Executes a script which
                    // parses the content of the file for valid characters.
                    // If parse passes, the uploaded file is copied to the
                    // destination. Returns JSON of status and action.
                    $response = $backend->configdpRun(
                        $plugin->configd_name . ' import-list ' . end($target_exp) . ' ' . $temp_filename
                    );

                    // If configd reports "Execute error," then $response is NULL.
                    // This can happen if there is a misconfiguration in the action (aka missing script/command).
                    if (! is_null($response)) {
                        return $response;
                    }

                    return array('error' => 'Error encountered', 'status' => 'Execute error');
                }

                return array('status' => 'error', 'message' => 'Unsupported target ' . $target);
            }

            return array('status' => 'error', 'message' => 'Missing target, or content.');
        }
    }

    /**
     * Calls the configd backend to retrive a pre-defined file, and return its
     * contents.
     *
     * API endpoint:
     *
     *   `/api/dnscryptproxy/file/get/settings.blocked_names_file_manual`
     *
     * Usage:
     *
     *   `/api/dnscryptproxy/get/`
     *
     * This function only accepts specific `$target` variables to prevent user
     * manipulation through the API. This should be the field ID of the calling
     * object. It will then execute the appropriate configd command, and return
     * the output from that command. The output is evaluated on the return to
     * detect an error condition.
     *
     * @param  string $target The desired pre-defined target for the API.
     * @return array          Array of the contents of the file.
     */
    public function LogoutAction($target)
    {
        $plugin = new Plugin();

        if ($target != '') {
            if ($target == 'settings.blocked_names_file_manual') {
                $content_type = 'text';
                $filename = 'blocked-names-manual.txt';
            } elseif ($target == 'settings.blocked_ips_file_manual') {
                $content_type = 'text';
                $filename = 'blocked-ips-manual.txt';
            } elseif ($target == 'settings.allowed_names_file_manual') {
                $content_type = 'text';
                $filename = 'allowed-names-manual.txt';
            } elseif ($target == 'settings.allowed_ips_file_manual') {
                $content_type = 'text';
                $filename = 'allowed-ips-manual.txt';
            } elseif ($target == 'settings.cloaking_file_manual') {
                $content_type = 'text';
                $filename = 'cloaking-manual.txt';
            }
            if ($filename != '') {
                $backend = new Backend();
                $target_exp = explode('.', $target);
                $result = $backend->configdRun($plugin->configd_name . ' export-' . end($target_exp));
                if ($result != null) {
                    $this->response->setRawHeader('Content-Type: ' . $content_type);
                    $this->response->setRawHeader('Content-Disposition: attachment; filename=' . $filename);

                    return $result;
                }
                // return empty response on error, maybe Throw?
                return '';
            }
        }
    }
}
