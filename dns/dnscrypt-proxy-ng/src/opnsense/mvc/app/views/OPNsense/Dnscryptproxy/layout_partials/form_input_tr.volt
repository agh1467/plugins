{#
 # Copyright (c) 2014-2015 Deciso B.V.
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

{# Set up the help, and advanced text settings for this field's row. #}

{% set field_id = this_model~'.'~this_field.id %}
<tr id="row_{{ field_id }}"
    {{ this_field.advanced|default(false) ? 'data-advanced="true"' : '' }}
    {{ this_field.hidden|default(false) ? 'style="display: none;"' : '' }}>
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
{# {%  if this_field.label %} #}
                <b>{{ this_field.label }}</b>
{# {%  endif %} #}
        </div>
    </td>
{# ------------------- Column 2 - Item field + help message. ---------------- #}
    <td>
{# {%  if this_field.type is defined %} #}
{%  if this_field.type == "text" %}
        <input
            type="text"
            class="form-control {{ this_field.style }}"
            size="{{ this_field.size|default("50") }}"
            id="{{ field_id }}"
            {{ this_field.readonly|default(false) ?
                'readonly="readonly"' : '' }}
            {{ (this_field.hint is defined) ?
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
{%  elseif this_field.type == "radio" %}
{# We define a hidden input to hold the
   value of the setting from the config #}
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
{%  elseif this_field.type == "command" %}
{# XXX need to be replaced with pre-built/built-in function. #}
{%      if this_field.style == "input" %}
            <input id="inpt_{{ field_id }}_command"
                   class="form-control"
                   type="text"
                   size="{{this_field.size|default("36")}}"
                   style="height: 34px;padding-left:11px;display: inline;"/>
{%      elseif this_field.style == "selectpicker" %}
                <select data-size="{{ this_field.size|default(10) }}"
                        id="{{ field_id }}"
                        class="selectpicker"
                        data-width="{{ this_field.width|default("334px") }}"
                        data-live-search="true"
                        {{ this_field.separator is defined ?
                        'data-separator="'~this_field.separator~'"' : '' }}>
{#              # Make sure we're dealing with a list for use in the
                # following for loop #}
{# {%          if this_field.options.option is not iterable %} #}
{# {%              set options_var = [this_field.options.option] %} #}
{# {%          elseif this_field.options.option is iterable %} #}
{# {%              set options_var = this_field.options.option %} #}
{# {%          endif %} #}
{%          for option in this_field.options.option %}
                    <option value="{{ lang._('%s')|format(option) }}">
                        {{ lang._('%s')|format(option) }}
                    </option>
{%          endfor %}
                </select>
{%      endif %}
            <button
                id="btn_{{ field_id }}_command"
                class="btn btn-primary"
                type="button"
            >
                {{ lang._('%s')|format(this_field.button_label) }}&nbsp;
                <i id="btn_{{ field_id }}_progress"></i>
            </button>
{%  elseif this_field.type == "startstoptime" %}
{# The structure and elements mostly came from the original
   firewall_schedule_edit.php #}
{# We define a hidden input to hold the
   value of the setting from the config #}
{%      if (this_field.start_hour_id is defined and
            this_field.start_min_id is defined and
            this_field.stop_hour_id is defined and
            this_field.stop_min_id is defined) %}
{# Make the background inherit from the row. #}
            <table style="background-color: inherit;">
                <tr>
                    <td>{{ lang._('%s')|format('Start Time') }}</td>
                    <td>{{ lang._('%s')|format('Stop Time') }}</td>
                </tr>
                <tr>
{%          for time, ids in {
                    "start":[
                        this_field.start_hour_id,
                        this_field.start_min_id
                    ],
                    "stop":[
                        this_field.stop_hour_id,
                        this_field.stop_min_id
                    ]
                } %}
                    <td>
                        <div>
{# Original div used input-group class, but this causes z-index issues with the dropdown menu
   appearing behind boxes below it. So it's been removed. #}
{# These <select> elements will trigger dropdown boxes getting added. #}
                            <select
                                class="selectpicker form-control"
                                data-width="55"
                                data-size="10"
                                data-live-search="true"
                                id="{{ ids[0] }}"
                            ></select>
{# The setFormData() assumes all <selects> are backed by an array datatype like an OptionField type in the model.
   When retreiving the data through the search API, it expects to receive an array. That array should
   be the OptionValues described in the model. It will then sift through the array, and locate any
   with the selected=>1 and mark them as such. When this field is erroneously backed by a
   non-array type field, it results in one option being added to the list:
   # <option value="resolve" selected="selected"></option>
   This is a result of a "bug" in jQuery in the .each() function. Attempting to iterate through
   an empty string will result in only the word 'resolve' being returned.
   The following javascript code demonstrates this behavior:
   #  var r = 0;
   #  var str = '';
   #  for (r in str) {
   #     console.log(r);
   #  } #}
                            <select
                                class="selectpicker form-control"
                                data-width="55"
                                data-size="10"
                                data-live-search="true"
                                id="{{ ids[1] }}"
                            ></select>
                        </div>
                    </td>
{%          endfor %}
                </tr>
            </table>
{%      endif %}
{%  elseif this_field.type == "variable" %}
{#      This type allows to reference string variables within the environment. #}
            <span
                class="{{ this_field.style|default('') }}">
                <code><?php echo ${$this_field->variable}; ?></code>
            </span>
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
