{#
 # Copyright (c) 2017 Franco Fichtner <franco@opnsense.org>
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
 # THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 # INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 # AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 # AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 # OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 # SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 # INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 # CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 # ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 # POSSIBILITY OF SUCH DAMAGE.
 # }

{##
 # This is a partial used to populate the <script> section of a page.
 #
 # Expects to have in the environment (scope) an array by the name of this_form.
 # This should contain an array of form XML data, created by the controller using
 # getForm().
 #
 # Expects to have all macros available in the environment.
 # views/OPNsense/Dnscryptproxy/+macros.volt
 #
 # Includes several universal functions, and attachments for convenience.
 #
 # All comments encapsulated in Javascript friendly notation so JS syntax
 # highlighting works correctly.
 #}

{#/*
    Call the attachments macro to build all of our attachments using the form data */#}
{{  build_attachments(this_form, lang, plugin_name) }}

{#/*
    # Toggle function is for enabling or disabling field(s)
    # This will disable an entire row (make things greyed out)
    # takes care of at least text boxes, checkboxes, and dropdowns.
    # It uses the *= wildcard, so take care with the field name.
    # Field should be the id of an object or the prefix/suffix
    # for a set of objects. */#}
    function toggle (id, type, toggle) {
        var efield = $.escapeSelector(id);
        if (type == "field") {
{#/*        # This might need further refinement, selects the row matching field id,
            # uses .find() to select descendants, .addBack() to select itself. */#}
            var selected_row = $('tr[id=row_' + efield + ']')
            var selected = selected_row.find('div,[id*=' + efield + '],[data-id*=' + efield + '],[name*=' + efield + '],[class^="select-box"],[class^="btn"],ul[class^="tokens-container"]').addBack();
            if (toggle == "disabled") {
{#/*            # Disable entire row related to a field */#}
                selected.addClass("disabled");
                selected.prop({
                    "readonly": true,
                    "disabled": true
                });
{#/*            # This element needs to be specially hidden because it is for some reason
                # hidden when tokenizer creates the element. This is the target element
                # <li class="token-search" style="width: 15px; display: none;"><input autocomplete="off"></li> */#}
                selected.find('li[class="token-search"]').hide();
{#/*            # Disable the Clear All link below dropdown boxes,
                # the toggle column on grids (Enabled column),
                # and the tokens in a tokenized field. */#}
                selected.find('a[id^="clear-options_"],[class*="command-toggle"],li[class="token"]').css("pointer-events","none");
                $('input[id=' + efield + ']').trigger("change");
            } else if (toggle == "enabled") {
{#/*            # Disable entire row related to a field */#}
                selected.removeClass("disabled");
                selected.prop({
                    "readonly": false,
                    "disabled": false
                });
{#/*            # This element needs to be specially shown because it is for some reason
                # hidden when tokenizer creates the element. This is the target element
                # <li class="token-search" style="width: 15px; display: none;"><input autocomplete="off"></li>*/#}
                selected.find('li[class="token-search"]').show();
{#/*            # Enable the Clear All link below dropdown boxes,
                # the toggle column on grids (Enabled column),
                # and the tokens in a tokenized field.*/#}
                selected.find('a[id^="clear-options_"],[class*="command-toggle"],li[class="token"]').css("pointer-events","auto");
{#/*            # Trigger a field change to trigger a toggle of any dependent fields (i.e. fields that this field enables) */#}
                var selected_field = $('input[id=' + efield + ']')
                $('input[id=' + efield + ']').trigger("change");
            } else if (toggle == "hidden") {
                selected_row.hide()
                selected_row.after('<tr class="dummy_row" style="display: none"></tr>');
            } else if (toggle == "visible") {
                selected_row.show()
                selected_row.next("tr[class=dummy_row]").remove();
            }
        } else if (["tab", "box"].includes(type)) {
            if (toggle == "hidden") {
                $("#" + efield).hide()
            } else if (toggle == "visible") {
                $("#" + efield).show()
            }
        }
    }

{#/*
    # Basic function to save the form, and reconfigure after saving
    # displays a dialog if there is some issue */#}
    function saveFormAndReconfigure(element){
        const dfObj = new $.Deferred();
        var this_frm = $(element).closest("form");
        var frm_id = this_frm.attr("id");
        var frm_title = this_frm.attr("data-title");
        var frm_model = this_frm.attr("data-model");
        var api_url="/api/{{ plugin_name }}/" + frm_model + "/set";

{#/*    # set progress animation when saving */#}
        $("#" + frm_id + "_progress").addClass("fa fa-spinner fa-pulse");

        saveFormToEndpoint(url=api_url, formid=frm_id, callback_ok=function(){
            ajaxCall(url="/api/{{ plugin_name }}/service/reconfigure", sendData={}, callback=function(data,status){
{#/*            # when done, disable progress animation. */#}
                $("#" + frm_id + "_progress").removeClass("fa fa-spinner fa-pulse");

                if (status != "success" || data['status'] != 'ok' ) {
                    ajaxDataDialog(data, frm_title);
                } else {
                    ajaxCall(url="/api/{{ plugin_name }}/service/status", sendData={}, callback=function(data,status) {
                        updateServiceStatusUI(data['status']);
                        dfObj.resolve();
                    });
                }
            });
        });
        return dfObj;
    }

    function toggleApplyChanges(){
        const dfObj = new $.Deferred();
{#/*
        # Function to check if the config is dirty and display the Apply Changes box/button */#}
        var api_url = "/api/{{ plugin_name }}/settings/state";
        ajaxCall(url=api_url, sendData={}, callback=function(data,status){
{#/*            # when done, disable progress animation. */#}

            if ('state' in data) {
                if (data['state'] == "dirty"){
                    toggle("alt_{{ plugin_name }}_apply_changes", "box", "visible")
                } else if (data['state'] == "clean" ){
                    toggle("alt_{{ plugin_name }}_apply_changes", "box", "hidden")
                }
            }
            dfObj.resolve();
        });
        return dfObj;
    }

    function saveForm(form, dfObj, callback_after, params){
        var this_frm = form;
        var frm_id = this_frm.attr("id");
        var frm_title = this_frm.attr("data-title");
        var frm_model = this_frm.attr("data-model");

{#/*    # It's possible for a form to exist without a data-model, exclude them. */#}
        if (frm_model) {
            var api_url="/api/{{ plugin_name }}/" + frm_model + "/set";
            saveFormToEndpoint(url=api_url, formid=frm_id, callback_ok=function(data, status){
                if (data['result'] != 'saved' ) {
                    ajaxDataDialog(data, frm_title);
                } else {
                    if (callback_after !== undefined) {
                        callback_after.apply(this, params);
                    }
                    dfObj.resolve();
                }
            });
        } else {
                dfObj.resolve();
        }
        return dfObj;
    }


    function reconfigureService(button, dfObj, callback_after, params){
        var frm_title = '{{ plugin_label }}';

        busyButton(button);

        var api_url = "/api/{{ plugin_name }}/service/reconfigure";
        ajaxCall(url=api_url, sendData={}, callback=function(data, status){
            if (status != "success" || data['status'] != 'ok' ) {
                ajaxDataDialog(data, frm_title);
            } else {
                if (callback_after !== undefined) {
                    callback_after.apply(this, params);
                }
                var api_url = "/api/{{ plugin_name }}/service/status";
                ajaxCall(url=api_url, sendData={}, callback=function(data, status) {
                    updateServiceStatusUI(data['status']);
                    dfObj.resolve();
                });
            }
        });
        return dfObj;
    }

{#/*
    # Make a button look busy, and disable it to prevent extra clicks. */#}
    function busyButton(this_btn) {
        this_btn.find('[id$="_progress"]').addClass("fa fa-spinner fa-pulse");
        this_btn.addClass("disabled");
    }

{#/*
    # Make a button clear from busy state, re-enable it. */#}
    function clearButton(this_btn) {
        this_btn.find('[id$="_progress"]').removeClass("fa fa-spinner fa-pulse");
        this_btn.removeClass("disabled");
    }

{#/*
    # Make a button clear from busy state, re-enable it,
    # includes toggle for Apply Changes visibility. */#}
    function clearButtonAndToggle(this_btn) {
        clearButton(this_btn);
        toggleApplyChanges();
    }

    function ajaxDataDialog(data, dialog_title){
        if (data['message'] != '' ) {
            var message = data['message']
        } else {
            var message = JSON.stringify(data)
        }
        BootstrapDialog.show({
            type:BootstrapDialog.TYPE_WARNING,
            title: dialog_title,
            message: message,
            draggable: true
        });
    }


{#/*
    # Save event handlers for all defined forms
    # This uses jquery selector to match all button elements with id starting with "save_frm_" */#}
    $('button[id^="btn_frm_"][id$="_save"]').each(function(){
        $(this).click(function() {
            const dfObj = new $.Deferred();
            var this_frm = $(this).closest("form");
            var this_btn = $(this);

            busyButton(this_btn);

            saveForm(this_frm, dfObj, clearButtonAndToggle, [this_btn]);

            return dfObj;
        });
    });

{#/*
    # Save event handler for the Save All button.
    # The ID should be unique and derived from the form data. */#}
    $('button[id="btn_{{ plugin_name }}_save_all"]').click(function() {
        const dfObj = new $.Deferred();
        var this_btn = $(this);

        busyButton(this_btn);

        var models = $('form[id^="frm_"][data-model]').map(function() {
{#          # Create a deferred object to pass to the function and wait on. #}
            const model_dfObj = new $.Deferred();
            saveForm($(this), model_dfObj);
            return model_dfObj
        });
        $.when(...models.get()).then(function() {
{#/*        # when done, disable progress animation. */#}
            clearButton(this_btn);
            toggleApplyChanges();
            dfObj.resolve();
        });
        return dfObj;
    });

{#/*
    # Save event handler for the Save All button.
    # The ID should be unique and derived from the form data. */#}
    $('button[id="btn_{{ plugin_name }}_save_apply_all"]').click(function() {
        const reconObj = new $.Deferred();
        var this_btn = $(this);

        busyButton(this_btn);

        var models = $('form[id^="frm_"][data-model]').map(function() {
{#          # Create a deferred object to pass to the function and wait on. #}
            const model_dfObj = new $.Deferred();
            saveForm($(this), model_dfObj);
            return model_dfObj
        });
        $.when(...models.get()).then(function() {
{#/*        # when done, disable progress animation. */#}
            reconfigureService(this_btn, reconObj, clearButtonAndToggle, [this_btn]);
            dfObj.resolve();
        });
        return recon_dfObj;
    });

{#/*
    # Apply event handler for the Apply Changes button.
    # The ID should be unique. */#}
    $('button[id="btn_{{ plugin_name }}_apply_changes"]').click(function() {
        const dfObj = new $.Deferred();
        var this_btn = $(this);
        busyButton(this_btn);
        reconfigureService(this_btn, dfObj, clearButtonAndToggle, [this_btn]);
        return dfObj;
    });


{#/*
    # Perform save and reconfigure for single form. */#}
    $('button[id*="btn_frm_"][id$="_save_apply"]').click(function() {
        const saveObj = new $.Deferred();
        const reconObj = new $.Deferred();
        var this_btn = $(this);
        var this_frm = $(this).closest("form");
        busyButton(this_btn);
        saveForm(this_frm, saveObj, reconfigureService, [this_btn, reconObj, clearButtonAndToggle, [this_btn]]);
        return { saveObj, reconObj };
    });

{#/*
    # Adds a hash tag to the URL, for example: http://opnsense/ui/dnscryptproxy/settings#subtab_schedules
    # update history on tab state and implement navigation
    # From the firewall plugin */#}
    if (window.location.hash != "") {
        $('a[href="' + window.location.hash + '"]').click();
    }

    $('.nav-tabs a').on('shown.bs.tab', function (e) {
        history.pushState(null, null, e.target.hash);
    });

{#/*
    # Update the service controls any time the page is loaded. */#}
    updateServiceControlUI('{{ plugin_name }}');
