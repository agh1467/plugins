<?php

/**
 *    Copyright (C) 2020 Deciso B.V.
 *    Copyright (C) 2022 agh1467@protonmail.com
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
use OPNsense\Core\Config;
use OPNsense\Mullvad\Plugin;
use OPNsense\Mullvad\Settings;

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
class AccountController extends PluginApiMutableModelControllerBase
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
    public function loginAction()
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
        //$result = array('status'=> 'ok');
        //$result = array('status'=> 'notok');
        $result = array();
        if ($this->request->isPost()) {

            // Retrieve the account number from the model.
            $account_number = $this->getModel()->account_number->getNodeData('clean');
            // Retrive public key from the config.
            $public_key = $this->getModel()->public_key;
            // We won't be able to do anything without an account number.
            if (!empty($account_number)) {
                $plugin = new Plugin();
                $backend = new Backend();
                $configd_command = implode(' ', array($plugin->getConfigdName(), 'login', $account_number, $public_key));
                $login_result_json = trim($backend->configdRun($configd_command));
                $login_result = json_decode($login_result_json, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // JSON is valid
                    /*
                    {
                        'device_name': 'winged buffalo',
                        'ipv4_address': '10.67.122.255/32',
                        'ipv6_address': 'fc00:bbbb:bbbb:bb01::4:7afe/128',
                        'private_key': 'SDd3s/22ixkhCMNjewibkPUE/Rj17JoS8dmfxsx9Y0o=',
                        'public_key': 'ccHOGyAuDW7sgaNa8o6UyiLFr2M52RjkZWwnMLUqFi4=',
                        'result': 'login'
                    }
                    */
                    if (array_key_exists('result', $login_result)) {
                        if ($login_result['result'] == 'success') {
                            // Create an array for the settings that we want to save.
                            $data = array();
                            $data['device_name'] = $login_result['device_name'];
                            $data['ipv4_address'] = $login_result['ipv4_address'];
                            $data['ipv6_address'] = $login_result['ipv6_address'];
                            $data['private_key'] = $login_result['private_key'];
                            $data['public_key'] = $login_result['public_key'];
                            $data['account_configured'] = "1";

                            /// XXX save to model procedure, see if this can be safely functionalized.
                            // Need to pass in the model
                            // The array
                            /*
                            $mdl = new Settings();
                            $mdl->setNodes($data);

                            // perform validation
                            $valMsgs = $mdl->performValidation();
                            foreach ($valMsgs as $field => $msg) {
                                if (!array_key_exists("validations", $result)) {
                                    $result["validations"] = array();
                                }
                                $result["validations"]["settings.".$msg->getField()] = $msg->getMessage();
                            }

                            // serialize model to config and save
                            if ($valMsgs->count() == 0) {
                                $mdl->serializeToConfig();
                                Config::getInstance()->save();
                                $result['result'] = "saved";
                            }
                            // Don't need this, as it forces a popup.
                            //if (array_key_exists('result', $result)) {
                            //    if ($result['result'] == 'saved') {
                            //        $result['status'] = gettext('Login successful, and configured.');
                            //    }
                            //}
                            */
                            $result = array_merge($result, $this->setData($data));
                        } else {
                            $msg = gettext('Login result was not successful.');
                            $result['status'] = $msg . ' $logout_result: ' . json_encode($login_result);
                            $result['result'] = 'failed';
                        }
                    } else {
                        $msg = gettext('Login results array did not include result key.');
                        $result['status'] = $msg . ' $logout_result: ' . json_encode($login_result);
                        $result['result'] = 'failed';
                    }
                } else {
                    $msg = gettext('Error encountered parsing JSON');
                    $result['status'] = $msg . ' $login_result: ' . json_encode($login_result_json);
                    $result['result'] = 'failed';
                }
            } else {
                $result['status'] = gettext('No valid account number in config.');
                $result['result'] = 'failed';
            }
        } else {
            // XXX should this be a throw?
            $result['status'] = gettext('Function must be called via HTTP POST.');
            $result['result'] = 'failed';
        }
        //array ("status" => 'ok', 'account_number' => $account_number);
        return $result;
    }

    /**
     *
     * @return array setAction() result or error message from configd.
     */
    public function logoutAction()
    {
        //$result = array('status'=> 'ok');
        //$result = array('status'=> 'notok');
        $result = array();
        if ($this->request->isPost()) {
            // Retrieve the account number from the model.
            $account_number = $this->getModel()->account_number->getNodeData('clean');
            // Retrive public key from the config.
            $public_key = $this->getModel()->public_key;
            if (!empty($account_number) && !empty($public_key)) {
                $plugin = new Plugin();
                $backend = new Backend();
                $configd_command = implode(' ', array($plugin->getConfigdName(), 'logout', $account_number, $public_key));
                $logout_result_json = trim($backend->configdRun($configd_command));
                $logout_result = json_decode($logout_result_json, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    if (array_key_exists('result', $logout_result)) {
                        if ($logout_result['result'] == 'revoked') {
                            // Create an array for the settings that we want to save.
                            $data = array(
                                'device_name' => '',
                                'ipv4_address' => '',
                                'ipv6_address' => '',
                                'private_key' => '',
                                'public_key' => '',
                                'account_configured' => "0"
                            );
                            // Set the data accordingly.
                            $result = array_merge($result, $this->setData($data));
                        } else {
                            $msg = gettext('Logout result was not successful.');
                            $result['status'] = $msg . ' $logout_result: ' . json_encode($logout_result) ;
                            $result['result'] = 'failed';
                        }
                    } else {
                        $msg = gettext('Logout results array did not include result key.');
                        $result['status'] = $msg . ' $logout_result: ' . json_encode($logout_result);
                        $result['result'] = 'failed';
                    }
                } else {
                    $msg = gettext('Error encountered parsing JSON');
                    $result['status'] = $msg . ' $logout_result: ' . json_encode($logout_result_json);
                    $result['result'] = 'failed';
                }
            } else {
                $result['status'] = gettext('No valid account number in config.');
                $result['result'] = 'failed';
            }
        } else {
            // XXX should this be a throw?
            $result['status'] = gettext('Function must be called via HTTP POST.');
            $result['result'] = 'failed';
        }

    return $result;
    }
}
