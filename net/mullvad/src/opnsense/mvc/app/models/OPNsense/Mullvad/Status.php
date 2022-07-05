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


namespace OPNsense\Mullvad;

use OPNsense\Base\BaseModel;
use OPNsense\Core\Backend;


/**
 * Class Settings is a BaseModel class used when retriving model data
 * via getModel().
 *
 * Functionality of this class is inherited entirely from BaseModel.
 *
 * @package OPNsense\Mullvad
 */
class Status
{
    /**
     * @var null|BaseField internal model data structure, should contain Field type objects
     */
    private $internalData = null;

    /**
     * @var null source file pattern
     */
    private $internalSourceFile = '/tmp/mullvad_account_status.json';

    /**
     * @var string action to send to configd to populate the provided source
     */
    private $internalConfigdPopulateAct = null;

    /**
     * @var int execute configd command only when file is older then TTL (seconds)
     */
    private $internalConfigdPopulateActTimeout = 120;

    /**
     * @var int execute configd command only when file is older then TTL (seconds)
     */
    private $internalConfigdPopulateTTL = 60;


    /**
     * reflect setter to internalData (ContainerField)
     * @param string $name property name
     * @param string $value property value
     */
    public function setSourceFile($value)
    {
        $this->internalSourceFile = $value;
    }


    /**
     * reflect setter to internalData (ContainerField)
     * @param string $name property name
     * @param string $value property value
     */
    public function setConfigdPopulateAct($value)
    {
        $this->internalConfigdPopulateAct = $value;
    }


    /**
     * reflect setter to internalData (ContainerField)
     * @param string $name property name
     * @param string $value property value
     */
    public function setConfigdPopulateTTL($value)
    {
        $this->internalConfigdPopulateTTL = $value;
    }

    /**
     * reflect setter to internalData (ContainerField)
     * @param string $name property name
     * @param string $value property value
     */
    public function setConfigdPopulateActTimeout($value)
    {
        $this->internalConfigdPopulateActTimeout = $value;
    }

    /**
     * default setter
     * @param string $value set field value
     */
    public function setValue($value)
    {
        // Do nothing
    }

    /**
     * get nodes as array structure
     * @return array
     */
    public function getNodes()
    {
        $result = array ();
        if ($this->internalSourceFile) {
            $sourcefile = $this->internalSourceFile;
            // First let's file the file with configd if we need to.
            if (!empty($this->internalConfigdPopulateAct)) {
                // If the file exists, open it read only, else create it.
                if (is_file($sourcefile)) {
                    $sourcehandle = fopen($sourcefile, "r+");
                } else {
                    $sourcehandle = fopen($sourcefile, "w");
                }
                // Establish a file handle lock to prevent tampering.
                if (flock($sourcehandle, LOCK_EX)) {
                    // execute configd action when provided
                    $stat = fstat($sourcehandle);
                    // If the file is empty, then we don't need know when it was last modified.
                    $muttime = $stat['size'] == 0 ? 0 : $stat['mtime'];
                    // If it's been modified longer than TTL-ago then let's update it.
                    if (time() - $muttime > $this->internalConfigdPopulateTTL) {
                        $act = $this->internalConfigdPopulateAct;
                        $backend = new Backend();
                        $response = $backend->configdRun($act, false, $this->internalConfigdPopulateActTimeout);
                        // only store parsable results
                        if (!empty($response) && json_decode($response) !== null) {
                            // Write the response out to the file.
                            fseek($sourcehandle, 0);
                            ftruncate($sourcehandle, 0);
                            fwrite($sourcehandle, $response);
                            fflush($sourcehandle);
                        }
                    }
                }
                // Release the file handle lock and close the file handle.
                flock($sourcehandle, LOCK_UN);
                fclose($sourcehandle);
            }
            // Second let's get the data from the file. ConfigdPopulateAct isn't necessary.
            if (is_file($sourcefile)) {
                // Get the data that we maybe just put into the file.
                $data = json_decode(file_get_contents($sourcefile), true);
                if ($data != null) {
                    // Store the data as the option list for this field.
                    $result = $data;
                }
            }
        }
        return $result;
    }

    /**
     * Remove the source file, used to force a status update on next page refresh.
     * @return bool
     */
    public function removeSourceFile()
    {
        return @unlink($this->internalSourceFile);
    }

}
