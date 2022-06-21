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

use OPNsense\Dnscryptproxy\ControllerBase;
use OPNsense\Dnscryptproxy\PluginApiMutableModelControllerBase;

/**
 * An ApiMutableModelControllerBase class used to perform settings related
 * actions for dnscrypt-proxy.
 *
 * This API is called by opnsense_ui.js::mapDataToFormUI() to set DOM object
 * attributes on the various config settings on this page.
 *
 * This API is accessible at the following URL endpoint:
 *
 * `/api/dnscryptproxy/settings`
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
 * @package OPNsense\Dnscryptproxy
 */
class SettingsController extends PluginApiMutableModelControllerBase
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
    protected static $internalModelClass = 'OPNsense\Dnscryptproxy\Settings';

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
        // Initialize the PluginApiMutableModelControllerBase
        // XXX I wonder if there is a way to make this automatic.
        parent::initialize();
    }

    /**
     * An API endpoint to call when no parameters are
     * provided for the API. Can be used to test the API is working.

     * API endpoint:
     *
     *   `/api/dnscryptproxy/settings`
     *
     * Usage:
     *
     *   `/api/dnscryptproxy/settings`
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
     * Special function to restore the default sources into the config.
     *
     * **WARNING**: This function **WIPES** ALL existing sources.source entires.
     *
     * API endpoint:
     *
     * `/api/dnscryptproxy/settings/restoreSources`
     *
     * This function will restore the sources back to the ones defined by
     * the application author inlcluded in the example `dnscrypt-proxy.toml`.
     *
     * This is probably not ideal with this data being hardcoded in the source.
     * I'll probably visit this later to see if I can come up with another way.
     *
     * It uses a bit of a janky approach but was the best approach I could
     * figure out with the fewest lines of code, while also being fairly clear
     * about what is happening.
     *
     *  @return array   `addBase()` results
     */
    public function restoreSourcesAction()
    {
        // Hard code the target.
        $target = 'sources.source';
        // First we get the current sources to use the UUIDs of each to delete them.
        $sources = $this->searchBase($target, $this->grid_fields[$target]['columns']);

        // Deleting each rows in the sources node. This is inefficient,
        // but negligable as most wont add more than these two anyway.
        foreach ($sources['rows'] as $source) {
            $this->delBase($target, $source['uuid']);
        }
        $this->sessionClose();

        // This was the cleanest way I could find to do this since addBase() has a check that there is a POST.
        // So we inject into the POST variable beforehand.
        $_POST['public_resolvers'] = array(
            'enabled' => 1,
            'name' => 'public-resolvers',
            'urls' => implode(',', array(
                'https://raw.githubusercontent.com/DNSCrypt/dnscrypt-resolvers/master/v3/public-resolvers.md',
                'https://download.dnscrypt.info/resolvers-list/v3/public-resolvers.md',
            )),
            'cache_file' => 'public-resolvers.md',
            'minisign_key' => 'RWQf6LRCGA9i53mlYecO4IzT51TGPpvWucNSCh1CBM0QTaLn73Y7GFO3',
            'refresh_delay' => (int) '',  //Have to expicitley cast int for validiation, default is undefined.
            'prefix' => '',
        );
        $_POST['relays'] = array(
            'enabled' => 1,
            'name' => 'relays',
            'urls' => implode(',', array(
                'https://raw.githubusercontent.com/DNSCrypt/dnscrypt-resolvers/master/v3/relays.md',
                'https://download.dnscrypt.info/resolvers-list/v3/relays.md',
            )),
            'cache_file' => 'relays.md',
            'minisign_key' => 'RWQf6LRCGA9i53mlYecO4IzT51TGPpvWucNSCh1CBM0QTaLn73Y7GFO3',
            'refresh_delay' => 72,
            'prefix' => '',
        );

        // Add our settings, and put the settings into the results variable. Also inefficient.
        $result[1] = $this->addBase('public_resolvers', $target);
        $result[2] = $this->addBase('relays', $target);
        // Set the config dirty flag since it's changed.
        $this->markConfig('dirty');
        // Setting our status to ok for SimpleActionButton()
        $result['status'] = 'ok';

        return $result;
    }
}
