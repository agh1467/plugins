<?php

/*
 * Copyright (C) 2015-2019 Deciso B.V.
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

namespace OPNsense\Mullvad\FieldTypes;

use OPNsense\Base\FieldTypes\BaseField;
use OPNsense\Core\Backend;

/**
 * Class JsonField, use a json encoded file as selection list
 * @package OPNsense\Base\FieldTypes
 */
class JsonField extends BaseField
{
    /**
     * @var null source field
     */
    private $internalData = null;

    /**
     * @var null source field
     */
    private $internalSourceField = null;

    /**
     * @var bool marks if this is a data node or a container
     */
    protected $internalIsContainer = false;

    /**
     * @var null source file pattern
     */
    private $internalSourceFile = null;

    /**
     * @var string action to send to configd to populate the provided source
     */
    private $internalConfigdPopulateAct = "";

    /**
     * @var int execute configd command only when file is older then TTL (seconds)
     */
    private $internalConfigdPopulateTTL = 3600;

    /**
     * @param string $value source field, pattern for source file
     */
    public function setSourceField($value)
    {
        $this->internalSourceField = basename($this->internalParentNode->$value);
    }

    /**
     * @param string $value optionlist content to use
     */
    public function setSourceFile($value)
    {
        $this->internalSourceFile = $value;
    }

    /**
     * @param string $value configd action to run
     */
    public function setConfigdPopulateAct($value)
    {
        $this->internalConfigdPopulateAct = $value;
    }

    /**
     * @param string $value set TTL for config action
     */
    public function setConfigdPopulateTTL($value)
    {
        if (is_numeric($value)) {
            $this->internalConfigdPopulateTTL = $value;
        }
    }

    /**
     * populate json data into internalData
     */
    public function initialize()
    {

    }
}
