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

{# We define a hidden input to hold the
   value of the setting from the config #}
{# XXX Size shouldn't matter for this hidden field. #}
        <input
            type="text"
            class="form-control hidden"
            size="{{ this_field.size|default("50") }}"
            id="{{ field_id }}"
            readonly="readonly"
        >
{# Figure out if we should use a builtin style or legacy. #}
{%      if this_field.builtin in [ 'legacy', 'button-group' ] %}
{%          set builtin = this_field.builtin %}
{%      else %}
{%          set builtin = 'legacy' %}
{%      endif %}
{%      if builtin == 'legacy' %}
        <div class="radio">
{%      elseif builtin == 'button-group' %}
        <div class="btn-group btn-group-xs" data-toggle="buttons">
{%      endif %}
{%      for this_button in this_field.buttons.button|default({}) %}
{%          if builtin == 'legacy' %}
            <label>
{%          elseif builtin == 'button-group' %}
            <label class="btn btn-default">
{%          endif %}
                <input type="radio"
                       name="rdo_{{ field_id }}"
                       value="{{ this_button['value'] }}"/>
{# Use non-breakable spaces to give the label some breathing room. #}
                &nbsp;{{ lang._('%s')|format (this_button) }}&nbsp;
            </label>
{%      endfor %}
        </div>
