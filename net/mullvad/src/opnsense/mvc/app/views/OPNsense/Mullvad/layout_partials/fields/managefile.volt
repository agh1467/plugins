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

        <input
            id="{{ field_id }}"
            type="text"
            class="form-control hidden">
            {{ (this_field.style|default('') == "classic") ?
            '<label id="lbl_'~this_field.id~'"></label><br>' : '' }}
        <label class="input-group-btn form-control"
               style="display: inline;">
            <label class="btn btn-default"
                   id="btn_{{ field_id }}_select"
{# XXX replace this with a builtin functionality. #}
{%      if this_field.style == "classic" %}
                    style="
                        padding: 2px;
                        padding-bottom: 3px;
                        width: 100%;"
{%      endif %}>
{# XXX Figure out how to attach a tooltip here #}
{# if we're using classic style, don't add icons. field may be overloaded,
    supposed to be css class(es) for other fields #}
{# XXX should be replaced with "builtin" functionality. #}
{%      if this_field.style|default("") != "classic" %}
                <i class="fa fa-fw fa-folder-o"
                   id="inpt_{{ field_id }}_icon">
                </i>
                <i id="inpt_{{ field_id }}_progress">
                </i>
{%      endif %}
                <input
                    type="file"
                    class="form-control
                        {{ (this_field.style|default("") != "classic") ?
                            'hidden' : '' }}"
                    for="{{ field_id }}"
                    accept="text/plain">
            </label>
        </label>
{%      if this_field.style|default("") != "classic" %}
{# if we're using classic style, no need to display this box
   Explicit style is used here for alignment with the downloadbox
   button, and matching the size of the button.
   This input element gets no id to prevent getFormData() from
   picking it up, using for attr to identify. #}
{# XXX should replace with a pre-built/built-in style. #}
        <input
            class="form-control"
            type="text"
            readonly=""
            for="{{ field_id }}"
            style="height: 34px;
                   display: inline-block;
                   width: 161px;
                   vertical-align: middle;
                   margin-left: 3px;"
        >
{%      endif %}
{# This if statement is just to get the spacing between the
   download/upload buttons to be consistent #}
{# XXX should replace with a pre-built/built-in style. #}
{%      if this_field.style|default("") == "classic" %}
        &nbsp
{%      endif %}
        <button
            class="btn btn-default"
            type="button"
            id="btn_{{ field_id }}_upload"
            title="{{ lang._('%s')|format('Upload selected file')}}"
            data-toggle="tooltip"
        >
            <i class="fa fa-fw fa-upload"></i>
        </button>
        <button
            class="btn btn-default"
            type="button"
            id="btn_{{ field_id }}_download"
            title="{{ lang._('%s')|format('Download')}}"
            data-toggle="tooltip"
        >
            <i class="fa fa-fw fa-download"></i>
        </button>
        <button
            class="btn btn-danger"
            type="button"
            id="btn_{{ field_id }}_remove"
            title="{{ lang._('%s')|format('Remove')}}"
            data-toggle="tooltip"
        >
            <i class="fa fa-fw fa-trash-o"></i>
        </button>
