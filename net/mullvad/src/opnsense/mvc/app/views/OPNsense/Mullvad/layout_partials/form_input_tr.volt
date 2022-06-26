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
 #
 # -----------------------------------------------------------------------------
 #}

{##-
 # This is a partial for building an HTML table row within a tab (form).
 #
 # This gets called by base_form.volt, and base_dialog.volt.
 #
 # Expects to receive an array by the name of this_field.
 #
 # The following keys may be used in this partial:
 #
 # this_field.id                : unique id of the attribute
 # this_field.type              : type of input or field. Valid types are:
 #           text                    single line of text
 #           password                password field for sensitive input. The contents will not be displayed.
 #           textbox                 multiline text box
 #           checkbox                checkbox
 #           dropdown                single item selection from dropdown
 #           select_multiple         multiple item select from dropdown
 #           hidden                  hidden fields not for user interaction
 #           info                    static text (help icon, no input or editing)
 #           command                 command button, with optional input field
 #           radio                   radio buttons
 #           managefile              upload/download/remove box for a file
 #           startstoptime           time input for a start time, and stop time.
 # this_field.label             : attribute label (visible text)
 # this_field.size              : size (width in characters) attribute if applicable
 # this_field.height            : height (length in characters) attribute if applicable
 # this_field.help              : help text
 # this_field.advanced          : property "is advanced", only display in advanced mode
 # this_field.hint              : input control hint
 # this_field.style             : css class to add
 # this_field.width             : width in pixels if applicable
 # this_field.allownew          : allow new items (for list) if applicable
 # this_field.readonly          : if true, input fields will be readonly
 # this_field.start_hour_id     : id for the start hour field
 # this_field.start_min_id      : id for the start minute field
 # this_field.stop_hour_id      : id for the stop hour field
 # this_field.stop_min_id       : id for the stop minute field
 # this_field['button_label']      : label for the command button
 # this_field['input']             : boolean field to enable input on command field
 # this_field['buttons']['button'] : array of buttons for radio button field
 #}

{# XXX Need to make sure that lang() is used in all the proper places. #}

{# Set up the help, and advanced text settings for this field's row. #}
{% set field_id = this_model~'.'~this_field.id %}
<tr id="row_{{ field_id }}"
    {{ this_field.advanced ? 'data-advanced="true"' : '' }}
    {{ this_field.hidden ? 'style="display: none;"' : '' }}>
{# ----------------------- Column 1 - Item label ---------------------------- #}
    <td>
        <div class="control-label" id="control_label_{{ field_id }}">
{# Add the help icon if help is defined. #}
{%  if this_field.help %}
            <a id="help_for_{{ field_id }}"
               href="#"
               class="showhelp">
                <i class="fa fa-info-circle"></i>
            </a>
{%  else %}
{# Add a "muted" help icon which does nothing. #}
                <i class="fa fa-info-circle text-muted"></i>
{%  endif %}
                <b>{{ this_field.label }}</b>
        </div>
    </td>
{# ------------------- Column 2 - Item field + help message. ---------------- #}
    <td>
{%  if this_field.type == "text" %}
        <input
            type="text"
            class="form-control {{ this_field.style }}"
            size="{{ this_field.size|default("50") }}"
            id="{{ field_id }}"
            {{ this_field.readonly ?
                'readonly="readonly"' : '' }}
            {{ (this_field.hint) ?
                'placeholder="'~this_field.hint~'"' : '' }}
        >
{%  elseif this_field.type == "hidden" %}
        <input
            type="hidden"
            id="{{ field_id }}"
            class="{{ this_field.style|default('') }}"
        >
{%  elseif this_field.type == "checkbox" %}
        <input
            type="checkbox"
            class="{{ this_field.style|default('') }}"
            id="{{ field_id }}"
            {{ (this_field['onclick'] is defined) ?
                'onclick="'~this_field['onclick']~'"' : '' }}
        >
{%  elseif this_field.type in ["select_multiple", "dropdown"] %}
        <select
            {{ (this_field.type == 'select_multiple') ?
                'multiple="multiple"' : '' }}
            data-size="{{ this_field.size|default(10) }}"
            id="{{ field_id }}"
            class="{{ this_field.style|default('selectpicker') }}"
            {{ (this_field.hint is defined) ?
                'data-hint="'~this_field.hint~'"' : '' }}
            data-width="{{ this_field.width|default("334px") }}"
            data-allownew="{{ this_field.allownew|default("false") }}"
            data-sortable="{{ this_field.sortable|default("false") }}"
            data-live-search="true"
            {{ this_field.separator is defined ?
                'data-separator="'~this_field.separator~'"' : '' }}
        ></select>
        {{ (this_field.style|default('selectpicker') != "tokenize") ?
            '<br />' : '' }}
        <a
            href="#"
            class="text-danger"
            id="clear-options_{{ field_id }}">
            <i class="fa fa-times-circle"></i>
            <small>{{ lang._('%s')|format('Clear All') }}</small>
        </a>
{%      if this_field.style|default('selectpicker') == "tokenize" %}
        &nbsp;&nbsp;
        <a
            href="#"
            class="text-danger"
            id="copy-options_{{ field_id }}">
            <i class="fa fa-copy"></i>
            <small>{{ lang._('%s')|format('Copy') }}</small>
        </a>
        &nbsp;&nbsp;
{#  This doesn't seem to work, returns error:
    Uncaught TypeError: navigator.clipboard is undefined #}
        <a
            href="#"
            class="text-danger"
            id="paste-options_{{ field_id }}"
            style="display:none">
            <i class="fa fa-paste"></i>
            <small>{{ lang._('%s')|format('Paste') }}</small>
        </a>
{%      endif %}
{%  elseif this_field.type == "password" %}
        <input
            type="password"
            autocomplete="new-password"
            class="form-control {{ this_field.style|default('') }}"
            size="{{ this_field.size|default("43") }}"
            id="{{ field_id }}"
            {{ this_field.readonly|default(false) ?
                'readonly="readonly"' : '' }}
        >
{%  elseif this_field.type == "textbox" %}
        <textarea
            class="{{ this_field.style|default('') }}"
            rows="{{ this_field.height|default("5") }}"
            id="{{ field_id }}"
            {{ this_field.readonly|default(false) ?
                'readonly="readonly"' : '' }}
            {{ this_field.hint is defined ?
                'placeholder="'~this_field.hint~'"' : '' }}
        ></textarea>
{%  elseif this_field.type == "info" %}
        <span
            class="{{ this_field.style }}"
            id="{{ field_id }}">
        </span>
{%  elseif this_field.type == "managefile" %}
{{      partial("OPNsense/Mullvad/layout_partials/fields/managefile",['this_field':this_field, 'field_id':field_id]) }}
{%  elseif this_field.type == "radio" %}
{{      partial("OPNsense/Mullvad/layout_partials/fields/radio",['this_field':this_field, 'field_id':field_id]) }}
{%  elseif this_field.type == "command" %}
{{      partial("OPNsense/Mullvad/layout_partials/fields/command",['this_field':this_field, 'field_id':field_id]) }}
{%  elseif this_field.type == "startstoptime" %}
{{      partial("OPNsense/Mullvad/layout_partials/fields/startstoptime",['this_field':this_field, 'field_id':field_id]) }}
{%  elseif this_field.type == "onoff" %}
{{      partial("OPNsense/Mullvad/layout_partials/fields/onoff",['this_field':this_field, 'field_id':field_id]) }}
{%  elseif this_field.type == "status" %}
{{      partial("OPNsense/Mullvad/layout_partials/fields/status",['this_field':this_field, 'field_id':field_id]) }}
{%  endif %}
{# {%  endif %} #}
{%  if this_field.help %}
            <div
                class="hidden"
                data-for="help_for_{{ field_id }}"
            >
                <small>{{ lang._('%s')|format(this_field.help) }}</small>
            </div>
{%  endif %}
    </td>
{# ------------ Column 3 - Used to show validation failure messages --------- #}
    <td>
        <span
            class="help-block"
            id="help_block_{{ field_id }}"
        ></span>
    </td>
</tr>
