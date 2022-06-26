{##
 # OPNsense® is Copyright © 2022 by Deciso B.V.
 # Copyright (C) 2022 agh1467@protonmail.com
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without modification,
 # are permitted provided that the following conditions are met:
 #
 # 1.  Redistributions of source code must retain the above copyright notice,
 #     this list of conditions and the following disclaimer.
 #
 # 2.  Redistributions in binary form must reproduce the above copyright notice,
 #     this list of conditions and the following disclaimer in the documentation
 #     and/or other materials provided with the distribution.
 #
 # THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 # INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 # AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 # AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 # OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 # SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 # INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 # CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 # ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 # POSSIBILITY OF SUCH DAMAGE.
 #}

{#
 # This is a partial for an 'onoff' field, which is very similar to a radio button
 # with the 'button-group' built-in style, however, only includes two pre-defined
 # buttons: On, Off
 #
 # Example Usage in an XML:
 #  <field>
 #      <id>status</id>
 #      <label>dnscrypt-proxy status</label>
 #      <type>status</type>
 #      <style>label-opnsense</style>
 #      <labels>
 #          <success>clean</success>
 #          <danger>dirty</danger>
 #      </labels>
 #  </field>
 #
 # Example Model definition:
 #  <status type=".\PluginStatusField">
 #      <configdcmd>dnscryptproxy state</configdcmd>
 #  </status>
 #
 # Example partial call in a Volt tempalte:
 # {{ partial("OPNsense/Dnscryptproxy/layout_partials/fields/status",[
 #     this_field':this_field,
 #     'field_id':field_id
 # ]) }}
 #
 # Expects to be passed
 # field_id         The id of the field, includes model name. Example: settings.enabled
 # this_field       The field itself.
 # this_field.style A style to use by default.
 #
 # Available CSS styles to use:
 # label-primary
 # label-success
 # label-info
 # label-warning
 # label-danger
 # label-opnsense
 # label-opnsense-sm
 # label-opnsense-xs
 #}

{%  if this_field.builtin == "input" %}
    <input id="inpt_{{ field_id }}_command"
           class="form-control"
           type="text"
           size="{{this_field.size|default("36")}}"
           style="height: 34px;padding-left:11px;display: inline;"/>
{%  elseif this_field.builtin == "selectpicker" %}
    <select data-size="{{ this_field.size|default(10) }}"
            id="{{ field_id }}"
            class="selectpicker"
            data-width="{{ this_field.width|default("334px") }}"
            data-live-search="true"
            {{ this_field.separator is defined ?
            'data-separator="'~this_field.separator~'"' : '' }}>
{%      for option in this_field.options.option %}
                    <option value="{{ lang._('%s')|format(option) }}">
                        {{ lang._('%s')|format(option) }}
                    </option>
{%      endfor %}
                </select>
{%  elseif this_field.builtin == "field" %}
    <input id="{{ field_id }}"
           type="text"
           class="form-control {{ this_field.style }}"
           size="{{ this_field.size|default("50") }}"
           {{ this_field.readonly ?
           'readonly="readonly"' : '' }}
           {{ (this_field.hint) ?
           'placeholder="'~this_field.hint~'"' : '' }}
            style="height: 34px;
                   display: inline-block;
                   width: 161px;
                   vertical-align: middle;
                   margin-left: 3px;">
{%  endif %}
{%  if this_field.buttons %}
{%      for button in this_field.buttons.children() %}
{%          if button['id'] and
               (button.label or button['label'] ) %}
{%              set button_label = button.label|default(button['label']) %}
{# https://forum.phalcon.io/discussion/19045/accessing-object-properties-whose-name-contain-a-hyphen-in-volt #}
{# Below we reference some variables which have dashes in their names, Volt has no built-in way to do this. #}
{# Using PHP to do this for now until I figure a way to get in commands to the compiler. #}
    <button id="btn_{{ field_id }}_{{ button['id'] }}_command"
            class="btn btn-primary"
            type="button"
{%              if button['type'] == "SimpleActionButton" %}
            data-label="{{ button_label }}"
            data-endpoint="{{ button.endpoint }}"
            data-error-title="<?php echo $button->{'error-title'}; ?>"
            data-service-widget="<?php echo $button->{'service-widget'}; ?>"
{%              endif %}
    >
{%              if button['type'] != "SimpleActionButton" %}
        <b>{{ lang._('%s')|format(button_label) }}&nbsp;</b>
        <i id="btn_{{ field_id }}_{{ button['id'] }}_progress"></i>
{%              endif %}
    </button>
{%          endif %}
{%      endfor %}
{%  endif %}
