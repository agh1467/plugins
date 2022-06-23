<?php

/*
 * Copyright (C) 2016 IT-assistans Sverige AB
 * Copyright (C) 2016 Deciso B.V.
 * Copyright (C) 2018 Fabian Franz
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

namespace OPNsense\Dnscryptproxy\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Core\Backend;
use OPNsense\Dnscryptproxy\Plugin;
use OPNsense\Dnscryptproxy\PluginApiMutableModelControllerBase;

/**
 * Class AboutController, inherits ApiControllerBase
 *
 * This class offers an API interface which is designed to be able to return
 * a combination of data that does not exist in a model with data that is in a model.
 *
 * This is useful for data which is more "live" and not appropriate to store in the
 * configuration, but should instead come through an API interface on demand.
 *
 * @package OPNsense\Dnscryptproxy
 */
class AboutController extends PluginApiMutableModelControllerBase
{

    /**
     * This variable defines what to call the <model> that is defined for this
     * Class by Phalcon. That is to say the model XML that has the same name as
     * this controller's name, "Settings".
     * In this case, it is the model XML file:
     *
     * `model/OPNsense/Dnscryptproxy/Settings.xml`
     *
     * The model name is then used as the name of the array returned by setBase()
     * and getBase(). In the form XMLs, the prefix used on the field IDs must
     * match this name as API actions use the same name in their transactions.
     * For example, the key_name in an API JSON response, will be this model
     * name. This name is also used as the API endpoint for this Controller.
     *
     * `/api/dnscryptproxy/settings`
     *
     * This locks activies of this Class to this specific model, so it won't
     * save to other models, even within the same plugin.
     *
     * @var string $internalModelName
     */
    protected static $internalModelName = 'about';

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
     * `model/OPNsense/Dnscryptproxy/Settings.xml`
     *
     * `model/OPNsense/Dnscryptproxy/Settings.php`
     *
     * These together will establish several API endpoints on this Controller's
     * endpoint including:
     *
     * `/api/dnscryptproxy/settings/get`
     *
     * `/api/dnscryptproxy/settings/set`
     *
     * These are both defined in the ApiMutableModelControllerBase Class:
     *
     * `function getAction()`
     *
     * `function setAction()`
     *
     * @var string $internalModelClass
     */
    protected static $internalModelClass = 'OPNsense\Dnscryptproxy\About';

    /**
     * This is a custom getAction() which wraps a call to parent::getAction() to retreive
     * any model data that may be there, and then merge that with whatever API calls
     * are needed to return data for mapDataToFormUI() and populating fields on the page.
     *
     * @return array settings
     * @throws \ReflectionException when not bound to a valid model
     */
    public function getAction_dis()
    {
        // First, we retrieve the model data to an array.
        $get_model = parent::getAction();

        // Second, make another associative array with the same structure,
        // populate with the values from the necessary backend calls.
        $get_other = array(
            'about' => array(
                'dnscryptproxy_version' => $this->invokeConfigdRun('version'),
                'dnscryptproxy_plugin_version' => $this->invokeConfigdRun('plugin_version')
            )
        );
        // Third, return the merged arrays.
        return array_merge($get_model,$get_other);
    }

    /**
     * This function will call configd, only for pre-configured actions.
     *
     * @param string  $action   The action to call in configd
     * @return string version of this plugin
     */
    private function invokeConfigdRun($action)
    {
        if (
            in_array($action, array(
                'version',
                'plugin_version',
            ))
        ) { // Check that we only operate on valid actions.

            // Create a plugin base object with plugin settings in it.
            $plugin = new Plugin;

            $result = trim((new Backend())->configdRun($plugin->getConfigdName() . ' ' . $action));

            return $result !== null ? $result : (object) [];
        }
    }
}
