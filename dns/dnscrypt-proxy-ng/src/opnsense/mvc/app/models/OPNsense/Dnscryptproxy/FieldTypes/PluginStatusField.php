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

namespace OPNsense\Dnscryptproxy\FieldTypes;

use OPNsense\Base\FieldTypes\BaseField;
use OPNsense\Base\FieldTypes\TextField;
use OPNsense\Core\Backend;

class PluginStatusField extends BaseField
{

    /**
     * @var bool marks if this is a data node or a container
     */
    protected $internalIsContainer = false;

    /**
     * @var string action to send to configd
     */
    private $internalConfigdCmd = "";

    /**
     * This is a setter which gets automatically executed by <<<insert file::function >>>
     *
     * @param string $value configd action to run
     */
    public function setConfigdCmd($value)
    {
        $this->internalConfigdCmd = $value;
    }

    /**
     * This is
     * translate ModelRelationFields to their proper value.
     *
     * This is used instead of the standard BaseField::getNodes() approach.
     * @param $parent_node BaseField node to reverse
     */
    public function getNodeData()
    {
        $result = '';
        if ($this->internalConfigdCmd) {
            $result = trim((new Backend())->configdRun($this->internalConfigdCmd));
        }
        return $result;
    }

    /**
     * update field (if not empty)
     * @param string $value
     */
    public function setValue($value)
    {
        // Do nothing.
    }


}
