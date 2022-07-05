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

namespace OPNsense\Mullvad\Api;

use OPNsense\Base\ApiControllerBase;
use OPNsense\Mullvad\Settings;

/**
 * An ApiMutableModelControllerBase class used to perform settings related
 * actions for dnscrypt-proxy.
 *
 * This API is called by opnsense_ui.js::mapDataToFormUI() to set DOM object
 * attributes on the various config settings on this page.
 *
 * This API is accessible at the following URL endpoint:
 *
 * `/api/mullvad/settings`
 *
 * This class creates the following API endpoints:
 * ```
 *   restoreSources
 * ```
 *
 * Functions with a name ending in "Action" become API endpoints by extending
 * `ApiMutableModelControllerBase`. That class creates the following encpoints:
 * ```
 *   search
 *   get
 *   add
 *   del
 *   set
 *   toggle
 * ```
 *
 * @package OPNsense\Mullvad
 */
class StatusController extends ApiControllerBase
{
    /**
     *
     * @var string $internalModelName
     */
    protected static $internalModelName = 'status';

    /**
     *
     * @var string $internalModelClass
     */
    protected static $internalModelClass = 'OPNsense\Mullvad\Status';

    /**
     * Retrieve model settings
     * @return array settings
     * @throws \ReflectionException when not bound to a valid model
     */
    public function getAction()
    {
        // define list of configurable settings
        $result = array();
        if ($this->request->isGet()) {
            $result[static::$internalModelName] = $this->getModelNodes();
        }
        return $result;
    }

    /**
     * Override this to customize what part of the model gets exposed
     * @return array
     * @throws \ReflectionException
     */
    protected function getModelNodes()
    {
        return $this->getModel()->getNodes();
    }

    /**
     * Get (or create) model object
     * @return null|BaseModel
     * @throws \ReflectionException
     */
    protected function getModel()
    {
        // Get account number from Settings model.
        $account_number = (new Settings())->account_number;
        // Instantiate our Status model and set some values within it.
        $mdl = (new \ReflectionClass(static::$internalModelClass))->newInstance();
        // $account_number is surrouned by double quotes in case it is empty.
        $mdl->setConfigdPopulateAct('mullvad status ' . '"' . $account_number . '"');
        return $mdl;
    }

    /**
     * An API endpoint to call when no parameters are
     * provided for the API. Can be used to test the API is working.

     * API endpoint:
     *
     *   `/api/mullvad/settings`
     *
     * Usage:
     *
     *   `/api/mullvad/settings`
     *
     * Returns an array which gets converted to json in the POST response.
     *
     * @return array    includes status, saying everything is A-OK
     */
    public function indexAction()
    {
        return array('status' => 'ok');
    }

    /**
     * This function
     *
     *
     *
     *
     *
     * API endpoint:
     *   /api/mullvad/settings/info
     *
     *
     * @return array setAction() result or error message from configd.
     */
    public function infoAction($target, $uuid)
    {
        $result = array(
            "title" => gettext('Info Box Title'),
            "message" => 'Infobox message for ' . $target . 'UUID: ' . $uuid,
            "close" => 'Close'
        );
        return $result;
    }

}
