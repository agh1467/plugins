## Development Discussions

This plugin started out as the dnscrypt-proxy plugin written by @mimugmail. However, once I got started poking around, and digging, I found and thought of various ways to do things that were substantially different from the author's approach.  I understand the preferred approach is to make incremental, small changes to plugins to reduce the burden of code review, however, in this case the changes made are so drastic that it complicates the code review process due to the fact that the old code is replaced with entirely new code, rather than small corrections.  Thus, this plugin lives in a new directory in the repo to keep it separate from the original one. I hope that this readme, the PHP documentation, and the comments made throughout will help alleviate the burden of code review. If some of the changes/features I've included here are adopted in Core, then the footprint of this plugin will reduce significantly.

### Views

The original plugin consisted of a single view (`general.volt`) and contained almost entirely static HTML, and Javascript code. A lot of the HTML was for drawing bootgrids on the various tabs, and the Javascript was for attaching to these bootgrid elements. The static nature of this approach makes it very cumbersome to make changes due to the number of things that need to change and the ways in which they need to change. There are references to objects which reside outside the template itself, which unless it's understood intimately what those things are, it is likely things will not line up, and end up broken, especially with bootgrids.

To mitigate these misalignment issues that I encountered, a lot of the core of this plugin has been "partialized" using the Phalcon templating engine, and is now driven/defined by a combination of Volt templates, and the controller forms. I think this is in the spirit of MVC and the intent to move away from PHP-based pages (or in this case, static HTML code in Volt templates). This allows for quick UI, and model changes to occur with minimal effort.

A lot of the partialized code was built on the foundation of existing Core `layout_partials`. I expanded upon their functionality, and with experimentation and more error than trial, I addressed several bugs I found in how the partials parse the arrays. These are the reasons why there are copies of them within this plugin, as I couldn't make these changes at will to Core. I also gleaned code and ideas from other plugins such as Redis and Proxy. Some of the Javascript function structures were written by those authors, and adapted for use here. I tried to include comments where this has occurred but I may have forgotten in some places.

Partializing all of the UI structure moves almost everything out of the static Volt templates and into the controller form definitions. As is possible already with several field types in Core, many of the features can be controlled through definition in the form. Thus to change how something looks, or the order in which it appears on the page it only takes changing values or moving the fields in the controller form.

Something new I've done with the partials is utilizing more macros, with one partial including exclusively macros. This isn't entirely necessary, but it does allow for recursion when parsing the tab arrays, which eliminates some duplicative code. Also new, is the concept of partializing the Javascript attachments. None of the current layout partials do this. This is a tremendous help for the bootgrids as everything is done automatically.

One of the main features which I had massive trouble with when I originally started on this plugin is the bootgrids. The tutorials for this functionality are written such that there is a lot of HTML/Javascript code to copy and paste, and then change names of various things to make the forms, and the models, and everything line up. This is a huge effort for folks who are new to MVC (like myself). So after close evaluation of the HTML structure, and underlying bootgrid functions, it was clear to me that the entire thing could be built dynamically, and only having to define a few things.

The bootgrid has now been entirely partialized and is treated (and appears on the page) as any other field, and abides by the same constraints. There is an exception in that the columns of the HTML table are differently defined since the grid spans the entire page. It's possible to control API calls, grid functionality, row definitions, and hiding/showing of the various command buttons all within the form definition. The HTML bootgrid structure is then built completely by the partial. It's even possible to have multiple bootgrids on the same page with ease. Moving the definition of the bootgrid into the controller form, it allows the partials to build the bootgrid (HTML and Javascript), and also keep all of the elements which need to have the same name aligned correctly with no effort from the developer.

After heavy partializing all of what is seen is controlled through the controller forms. Everything from the tabs, down to the little things like element state changes (hidden/visible/enabled/disabled) is built by the `layout_partials`. This includes Javascript attachments for the various elements, which is not something done by the Core `layout_partials`. There is one underlying Javascript attachment/function which I don't think is well suited for defining in the form data, and is a unique situation to this specific plugin.

The `layout_partials` contain DocBlock-style comments for reference, and comments throughout to attempt to explain their functions. Most of the new fields are elaborated on or explained to some degree. I added several validations, and fixed some other minor issues I found in some of the original fields as well. I also encountered the scope issue described in `base_form.volt` when I encountered a sticky variable being set when it wasn't supposed to be. I mitigated this issue entirely by never using the "root" scope, and always passing in a named array. That's why all of the partials utilize a "this_" prefixed array instead. I also adjusted all of the volt code to be uniform and utilize indentation inside the enclosing symbols to prevent the white space from getting into the final HTML. Managing white space with the the dashes ({%-) isn't really reasonable except in a handful of cases, and the dashes don't work on comment blocks like they do in Jinja.

### Models

The original plugin included several models, which mounted to several sub-nodes within the core dnscrypt-proxy node within the config. This approach stemmed from the tutorial/documentation for bootgrids, and is understandable. However, this approach results in multiple instances of paths in the config like `//OPNsense/dnscryptproxy/cloak/cloaks/cloak`, `//OPNsense/dnscryptproxy/server/servers/server`, in addition to the model definition itself consisting of only a few fields, with two files per-model. Multiple models means multiple controllers will be necessary to accomplish the API calls for bootgrid.

All of these models aren't technically necessary though. I had a hard time justifying the redundant path definitions in the config, the additional model definitions for only a few fields, and additional controllers for something that can be done all within a single model. This reduces the plugin footprint substantially, reduces the number of files to edit for making changes, and reduces the complexity of the plugin overall. There is now only a single model, Settings. It contains all of the settings for this plugin.

The main menu has been changed to reflect the new three pages included in this plugin. The original had definitions of three log pages utilizing the Core Diagnostics UI to display these. These logs are included still, but are all on a single page with tabs instead.

There several sections within the Settings model which will benefit from some elaboration.

#### JsonKeyValueStoreField

This field type is utilized in several places to pull data from `dnscrypt-proxy` directly mostly for a list of resolvers. This approach may or may not be the best of approach for this data. Of the several methods I tried this was the most reliable, while also not requiring storing volatile data in the config or jumping through hoops with UUIDs.

#### OptionValues

Anywhere that OptionValues are used, I used the element name "option" instead of a named element as is often found in documentation. The reason for this is that the element name is not relevant, and is not used at all. Using the name "option," though truly arbitrary, it makes it easier to read, and understand what is being represented by the data. It also implies by virtue of being present the value attribute, and the value of the element itself as being important. The value attribute being what is used as the data stored in the config, and the value of the element being the text displayed to the user on the dropdown.

#### ModelRelationField

The schedules employ the ModelRelationField to reference the schedules settings within the model itself. This is not a well documented feature. There are comments that I left in the XML for reference when reading to better understand what's happening without having to dig through the functions which process these elements.

#### Schedules

These schedules are used by the allowed/blocked lists. A schedule can be associated with an entry to indicate a time period during which that entry should be active. In the `dnscrypt-proxy.toml` these settings look like this:
```
[schedules]
  [schedules.'time-to-sleep']
  mon = [{after='21:00', before='7:00'}]
  tue = [{after='21:00', before='7:00'}]
  wed = [{after='21:00', before='7:00'}]
  thu = [{after='21:00', before='7:00'}]
  fri = [{after='23:00', before='7:00'}]
  sat = [{after='23:00', before='7:00'}]
  sun = [{after='21:00', before='7:00'}]
```
Translating these into a field structure within a model given the existing constraints means that this section is rather verbose. It would be best if these could be arrays, but that won't work with the currently available field definitions (nested arrays are forbidden).

The best option would be something like a TimeField type with specific validators designed to handle time entries.

In considering the options, I found that this data could be represent in several ways:
* CSV (each array element)
  * Can't limit to two elements
  * Validation would be complicated
* Separate fields x4 (each time segment)
  * Volt templates, and mapDataToFormUI() only supports single field assignment
  * Validation would be simple
* Text (whole string in {})
  * Easiest (LOE), largest LOE for users, ask them to enter the value as a whole
  * Validation would be complicated

Ideally it would be an array for each day, with each array containing 4 values, having the fields treated as a group and having Phalcon build the UI to accommodate the group.

I decided to go with the separate fields approach because the dropdown boxes were resulting in these fields getting picked up on the save, and creating a separate array in the POST for the set API call. We'll also be able to do data validation with each field individually (hour, minute).

All of the values are padded with zeros because the "0" value here breaks selectpicker as in BaseListField/getNodeData() the "empty placeholder" will evaluate empty("0") to be true. If the selected value is 0, then it sets the empty placeholder to be selected, while also the 0 value is selected. Selectpicker doesn't like both being selected when only one is supposed to be. The extra zero padding on the rest of the numbers is so that the dropdown-select box will sort them nicely. We'll convert to integer when putting the values into the the config files for `dnscrypt-proxy`. This approach is not pretty, and not a good method of handling the data since it's getting modified on the way in and the way out, however, it was the best solution I could come up with working within the constraints of the bugs.

This whole approach results in a massive section for a relatively small amount of data. It would be cool if this could be done a different way, like defining an option list, and then referencing that option list instead of including the entire option list repeatedly (x7). It would also be nice to support nested arrays so we could have one array per-day per-schedule.

### Controllers

The documentation for the Controllers is located here:

**[PHP Documentation](https://agh1467.github.io/dnscrypt-proxy-v2.0.45/packages/OPNsense-Dnscryptproxy.html)**

The Controller footprint is expanded a little due to the additional page definitions. An additional controller not related to a page is `ControllerBase.php` this is copied from Core and is used to parse form XMLs differently when using `getForm()`. This function walks deeper into the XML, parses element attributes, and supports arrays beyond the first and second levels. This allows for much more flexibility in the XML design to be able to create more complex `layout_partials` to dynamically create elements on a page.

The controllers here aren't overly complex. The only differences from the originals are utilizing the custom `getForm()`, utilizing setVars() to set the variables instead of the magic setter (purely aesthetic reasons), changing the variable convention, and absence of calls to parse edit dialog forms.

With respect to the variable names, the example variable names here were previously camel case, but given the convention it results in excessively long names. Looking at the coding standards (https://docs.opnsense.org/development/guidelines/psr1.html) camelCase is for methods, and these are just arrays, and properties (variables) are allowed to be anything. Since it was the only place camel case was used for a variable name I changed it to be consistent with the element prefixes used throughout.

Utilizing the custom `getForm()` there isn't a need to define the dialogs as separate forms or as separate files, and these definitions now live within the definition of the bootgrid field to which they should be associated. This approach further reduces the number of separate files to maintain.

#### Forms

The forms XMLs have reduced in number due to the lack of need to have separate form definitions for dialog boxes. I found little value in doing this if the definition of the dialog could be included in the page form itself. This helps keep everything in a single place and much easier to manage with fewer files to work on.

Due to the malleable nature of the `layout_partials` being within this plugin, it makes it much easier to add features and create new field type definitions. That being said, the XML design in the forms is mostly the same, with some deviations, and additions. New field types are included for radio, command, bootgrid, managefile, startstoptime. For some other field types additional elements have been added to support more features such as the placeholder (hint) attribute for textbox. A new concept of "field control" has been added to support state changes (enable/disable/show/hide) when specific events happen with a checkbox, or radio button.

The XML structure now utilizes the tab/subtab feature rather than everything being on individual tabs. Tabs/Subtabs are grouped together based on related features. These tabs are then drawn by `layout_partials` entirely instead of needing to be defined in the volt template. Along the same vein, the edit dialogs for bootgrids are contained within `<dialog>` elements and reside within the field definition for their respective bootgrid. API calls for bootgrids are also defined here instead of statically in the volt template for the page.

One of the challenges I ran into while working with the XMLs is how nested elements are interpreted. I'm not convinced this is the best method of handling the situation. The approach that I ended up going with is accommodating the discrepancy when the data is used. A much better approach would be to fix it or process it properly in the first place, so that the data is always predictable. There are two primary configurations which are being interpreted differently, a single nested element, and multiple nested elements.

A single nested element looks like this in the XML:
```
<options>
    <option>dnscrypt-proxy.toml</option>
</options>

```
This translates to an associative array with a single named string, "option":
```
array(1) {
  ["option"]=>
  string(19) "dnscrypt-proxy.toml"
}
```

Multiple nested elements looks like this in the XML:
```
<options>
    <option>dnscrypt-proxy.toml</option>
    <option>allowed-ips-internal.txt</option>
</options>
```
This translates to an associative array with a single named array, "option":

```
array(1) {
  ["option"]=>
  array(17) {
    [0]=>
    string(19) "dnscrypt-proxy.toml"
    [1]=>
    string(24) "allowed-ips-internal.txt"
    ...
  }
}
```

This means that any time that the object named "option" is evaluated it could be a string OR an array. The issue here is that any procedure designed to process this object must accommodate both scenarios. In most cases what I decided to do was evaluate if the object is a string, and wrap it in an array if it was. This is not ideal, but seems to work, but adds a bunch of code in various places. It's also easy to forget about this condition because most of the time when this functionality is used, it is a multi-selection situation.

#### Api

With respect to the topic of multiple files for bootgrids, the primary reason that I found that the multiple model/controllers/forms approach is used is due to how the tutorial/documentation is written. The example describes creating an additional API controller, using an additional model, uses hard coded paths, hard coded array definitions, and function names like 'setItemAction()'. This all leads developers to copying the code wholesale and changing the parts that are changing, like setForwardAction(), setCloakAction(), setServerAction(), etc. Which results in the duplication of all of it.

As partially elaborated on in the Models section, all of these repetitive objects/files aren't really necessary. Even with multiple functions of various names, they can still operate within the same model. There isn't even a real need to have multiple functions for the API end-points as is shown in the tutorial. This is where I originally began after bringing all of the settings within the single Settings model. Ultimately I ended with a *single* function, gridAction(), which supports *all* of the activities of the bootgrid, and replaces *all* of the repetitive functions shown in the tutorial.

The original plugin had a `general.php` which contained no functions, and several other PHP classes which contained functions related to each bootgrid. Now all of these functions are consolidated into the relevant PHP class, `SettingsController`. There are two additional classes, `DiagnosticsController`, and `FileController` which serve their own purposes. Each class has various DocBlocks and comments to describe the different parts.

### Service Templates

Most of the changes for the templates happen within the core configuration file `dnscrypt-proxy.toml`. The original was very noisy, and contained a lot of the same data repeatedly with respect to conditional statements. Here a lot of the repetitive text is swapped out in lieu of variables. This makes the code more portable, is cleaner, and requires less effort to change something like the plugin name, or path in the config. I also included section headers similar to what is included in the default `dnscrypt-proxy.toml`. This isn't necessary, and is purely for aesthetic/convenience purposes.

Many of the more advanced settings are wrapped up in conditional statements which will include or exclude them from the configuration file entirely. Comments are included to help explain what's happening in these more complex structures. Tertiary statements are utilized in the case of boolean settings which eliminates the need to include a version of the setting for each condition. White space is tailored for presentation, both within the settings, and between settings definitions and headers. Cloaking, forwarding, and lists files have been either updated or added. Support for schedules, and comments have been added to the lists files.

For all of the files, I've added 'jinja' as a file extension because it makes it a simple matter for an IDE to understand that these files contain Jinja code. This deviates from the instructions provided in the OPNsense documentation, however, I could find no negative impact from doing this. The only impact I could find was updating the +TARGETS to reflect the new file name. The destination file name is defined there and is not affected by the template's file name. I couldn't find any other plugins using the jinja file extension, but I see more value in using it than not using it.

This is also described in the [Jinja documentation](https://github.com/pallets/jinja/pull/1083/files#diff-0f54a58b39617a700a0b750e7a8bf07eR60-R71) which was updated in 2019.

### Service Scripts

There are several new scripts here for performing back-end type activities, mostly with files or interacting with `dnscrypt-proxy` itself.

There is one script for importing lists (allowed/blocked/cloaking), another for importing some certificates out of the OPNsense config into files. The others are for getting info out of `dnscrypt-proxy` using some command parameters. These scripts are executed dynamically or on-demand from the user depending on the activity.

The setup.sh script has been replaced with a `+POST_INSTALL.post` action. References to this script in the configd conf have been removed, as they're unnecessary.

Each of the service scripts now includes attributes: message, and description

#### message

The `message` attribute is used by configd, and this appears in logs whenever the script is executed through configd. All of these messages are written in the present tense, i.e. executing, restarting, stopping, importing, etc. This keeps the information clear as to what's happen at that moment, making the logging more natural when the messages appear. Example:

```
OPNsense configd.py[33623]: [738b515d-004f-421a-ac8a-8acecdb708b9] dnscrypt-proxy: performing config check
```

#### description

The `description` attribute is used in at least one location that I found, on the System/Settings/cron page. When adding or editing a job, the Command dropdown includes all of the scripts from configd, and it uses the description attribute to populate the list. When these scripts appear in this list, it's best if they make sense, so the user understands what the "Commands" actually are. Thus, as they appear on this page, these are written in a future tense, which describes what will happen when the command is executed.

```
Get relays from configured sources files, and return json list of relays
```

### Logging

With the migration to Phalcon4, it appears to now be "camelizing" arguments for at least `/api/diagnostics/log/` endpoint. If a dash is used in an argument, the dash is getting eaten and the following letter become capitalized. This results in calls like `/api/diagnostics/log/dnscrypt-proxy/main` looking at `/var/log/dnscryptProxy/main.log` instead. Since we can't fix that API from this plugin, we can only work around this issue, with options like using a different directory, or creating a symlink to the log directory. @mimugmail addressed this issue via the symlink approach in PR [#2467](https://github.com/opnsense/plugins/pull/2467). It doesn't really matter where the directory is that the logs are located or what its named, however, moving the directory does have an impact. For example, it results in changes necessary in configuration files, install scripts, menu XMLs, and API calls. Additionally if the directory is moved, the installation/upgrade scripts must deal with moving the files from the old log directory to the new one.

Instead of doing exactly what @mimugmail did, I've chosen to leave the log directory in place, and create a symlink, `/var/log/dnscryptProxy`, and then leaving the API calls the same. This is in the case that the camelizing functionality when making the Diagnostics/Log API call is changed in the future so that the call goes back to looking in `/var/log/dnscrypt-proxy.` The only work needed then is to clean up the symlink, and remove the creation of the symlink from the install scripts.

### Configuration

Since the configuration of `dnscrypt-proxy` is made possible through the use of several files, instead of having comments, throughout all of the files, information about the configuration has been consolidated here to make it easier to understand each setting. There are several concepts being taken into consideration with each setting: min/max value, default value, and appropriate value

The min/max value depends on `dnscrypt-proxy` itself. These values depend on the variable type that backs the setting within the application, as well as any constraints defined within the application itself defined by the developer. The default value is a setting defined by the developer, this can be provided as an example in the configuration file, or maybe a static variable defined within the code of the application itself. The appropriate value depends on the setting itself, and what is a reasonable value for that setting. For example, appropriate value for a timer would not be a negative value, even though variable type backing the setting within the application supports it. With a combination of these, the values of the settings are chosen accordingly. I've included links to the `dnscrypt-proxy` source for reference, as well as an explanation of the decision around a particular setting.

For the sake of consistency the settings included here are in roughly the same order as they are in the example configuration file.

Notes:
* When application sets defaults within, the setting is not required, and no defaults provided.
* Except when boolean/checkbox, as false will always be provided, so provide false as default if false is default.
* Settings in the config shouldn't necessarily be considered "default" as sometimes they're just suggestions or examples.
* Is the default value only useful when drawing the form? For example, is it useful when saving the settings via API directly? Can you save the settings via API and not include these non-required booleans? Does that make a difference?
* Hard coding defaults and such may create more work by having to chase upstream when the defaults change.
* If a field is required it should probably 100% have a default.
* A time to set a default would be when without it, the application may perform poorly (like with low TTL settings, etc).
* With booleans, since a hint can't be given to show default, these should be defined when true.
* For boolean settings where the default is false, should we explicitly define the default value as false? When the form is saved, the unchecked state of the checkbox will set the value as false anyway. Might as well do it to be consistent?
* For booleans represent feature flags (like manual/auto server selection, enabling/disabling functionality) these should be required, and a default be supplied.
* Booleans for array entries should always be required, and have a default (probably `1`).
* Required booleans, and options should have default? Probably.

#### server_names

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L35](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L35)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `['scaleway-fr', 'google', 'yandex', 'cloudflare']` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L32](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L32)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `CSVListField`

  Model field required: No

  Model field default: None

  No defaults, not required, no hint.

#### listen_addresses

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#37](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L37)

  dnscrypt-proxy source default: `[]string{"127.0.0.1:53"}` [dnscrypt-proxy/config.go#L112](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L112)

  dnscrypt-proxy configuration default: `127.0.0.1:53` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L42](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L42)

  dnscrypt-proxy configuration state: unset

  Data type range: Not Applicable

  Model field type: `CSVListField`

  Model field default: None

  Model field required: Yes

  View field hint: `127.0.0.1:5353, [::1]:5353`

  Going to require this field since without it, `dnscrypt-proxy` won't do anything anyway. Not going to specify a default since it could be that the user wants to run it on port 53 instead of Unbound, or maybe not. The hint will show the suggestion from

#### max_clients

  dnscrypt-proxy source data type: `uint32` [dnscrypt-proxy/config.go#L84](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L84)

  dnscrypt-proxy source default: `250` [dnscrypt-proxy/config.go#L135](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L135)

  dnscrypt-proxy configuration default: `250` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L47](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L47)

  dnscrypt-proxy configuration state: set

  Data type range: `0` to `4294967295`

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value value: `0`

  Model field maximum value value: `4294967295`

  View field hint: `250`

  Since technically `0` is a valid configuration, this is being used as the minimum for this setting. If set to `0`, a message appears in the logs `[WARNING] Too many incoming connections (max=0)`. The default seems reasonable, and was historically `100`, and then raised to `250`. No default since source and configuration agree, and doesn't cause unexpected behavior. Will use default as hint.

#### ipv4_servers

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L82](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L82)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L82](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L82)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L61](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L61)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  Not required as it's set in both source, and configuration.

#### ipv6_servers

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L83](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L83)

  dnscrypt-proxy source default: `false` [dnscrypt-proxy/config.go#L131](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L131)

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L64](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L64)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Not required as it's set in both source and configuration.

#### dnscrypt_servers

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L79](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L79)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L132](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L132)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L67](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L67)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  Not required as it's set in both source and configuration.

#### doh_servers

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L80](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L80)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L133](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L133)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L67](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L67)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  Not required as it's set in both source and configuration.

#### odoh_servers

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L81](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L81)

  dnscrypt-proxy source default: `false` [dnscrypt-proxy/config.go#L134](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L134)

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L73](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L73)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Not required as it's set in both source and configuration.

#### require_dnssec

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L76](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L76)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L79](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L79)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Not required as it's set in both source and configuration.

#### require_nolog

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L77](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L77)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L128](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L128)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L82](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L82)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  Not required as it's set in both source and configuration.

#### require_nofilter

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L78](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L78)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L129](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L129)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L85](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L85)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  Not required as it's set in both source and configuration.

#### disabled_server_names

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L36](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L36)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `[]` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L88](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L88)

  dnscrypt-proxy configuration state: set

  Data type range: Not applicable

  Model field type: `CSVListField` and `JsonKeyValueStoreField`

  Model field required: No

  Model field default: None

  The default is set to an empty list, but it's not required.

#### force_tcp

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L40](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L40)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L97](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L97)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Though there is no definition in the code, it is included in the configuration file, and it won't harm anything to just explicitly set this as false. This setting is not required, however, the nature of the field type means that it will always been included when saving via the UI, since when unselected the value is set to 0.

#### proxy

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L43](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L43)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `socks5://127.0.0.1:9050` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L104](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L104)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  View field hint: `socks5://127.0.0.1:9050`

  Though the configuration populates this setting with a value, the setting itself is not enabled by default. Having this be a default doesn't make much sense, and it would be better to provide what is included in the configuration as a hint, i.e. an example.

#### http_proxy

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L97](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L97)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `http://127.0.0.1:8888` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L110](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L110)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  View field hint: `http://127.0.0.1:8888`

  Though the configuration populates this setting with a value, the setting itself is not enabled by default. Having this be a default doesn't make much sense, and it would be better to provide what is included in the configuration as a hint, i.e. an example.

#### timeout

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L41](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L41)

  dnscrypt-proxy source default: `5000` [dnscrypt-proxy/config.go#L114](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L114)

  dnscrypt-proxy configuration default: `5000` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L118](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L118)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Model field required: No

  Model field default: `5000`

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `5000`

  The author suggests that 10000 is the highest reasonable value. The range is platform dependent, so it depends on if the system is 32 or 64 bit. Using the upper limit of 32-bit in as max. The hint will be included for convenience, though with default configured, it's not necessary. Though negative numbers are technically in the range for `int` they don't make sense here. The application accepts zero as a setting.

#### keepalive

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L42](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L42)

  dnscrypt-proxy source default: `5` [dnscrypt-proxy/config.go#L115](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L115)

  dnscrypt-proxy configuration default: `30` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L123](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L123)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Plugin required: No

  Model field default: `30`

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `30`

  Here this is a discrepancy between the code, and the configuration file. Probably 30 is a more reasonable setting, as 5 may be a little too low. The integer field requires a positive integer (or zero). For the maximum value, using the high end of the 32-bit integer range. The application accepts zero.

#### edns_client_subnet

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L105](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L105)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `'0.0.0.0/0', '2001:db8::/32'` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L131](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L131)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `CSVListField`

  Model field required: No

  Model field default: None

  View field hint: `0.0.0.0/0, 2001:db8::/32`

#### blocked_query_response

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L99](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L99)

  dnscrypt-proxy source default: `hinfo` [dnscrypt-proxy/config.go#L99](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L99)

  dnscrypt-proxy configuration default: `refused` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L139](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L139)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No`

  Model field default: None

  View field hint: `hinfo`

  This is a free form text field, and the example configuration uses 'refused', but does state that 'hinfo' is the default. Since this is not required and the default is set within the application itself, we'll pass on setting it explicitly.

#### lb_strategy

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L47](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L47)

  dnscrypt-proxy source default: `p2` [dnscrypt-proxy/serversInfo.go#L102](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/serversInfo.go#L102)

  dnscrypt-proxy configuration default: `p2` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L146](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L146)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  View field hint: `p2`

  This one is a bit odd and the source compares the configuration setting to a default, and uses the default if the configuration isn't set. It's kind of a roundabout method, but works in the end. Thus the setting is actually based on a static variable defined in another file. Since it's not required, and the application sets a default anyway, we'll skip setting the default.

#### lb_estimator

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L48](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L48)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L146](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L146)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L152](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L152)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  Since this is a boolean field, the default must be configured, otherwise, this setting would get explicitly set to `false`, and the default should be `true`.

#### log_level

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L31](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L31)

  dnscrypt-proxy source default: ?????

  dnscrypt-proxy configuration default: `2` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L157](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L157)

  dnscrypt-proxy configuration state: unset

  Data type range: platform dependent

  Model field type: `OptionField`

  Model field required: No

  Model field default: None

  Model field minimum value: `1`

  Model field maximum value: `6`

  I looked at the source for a while and I couldn't figure out where the application would be getting `2` as the default. I couldn't find where this value was being set at all. I tested this and indeed INFO messages don't show in the logs when no log level is defined, but NOTICE does. Setting minimum debug level to 1 because DEBUG is effectively disabled by the developer intentionally, and is technically unavailable in the pre-compiled binary. See: https://github.com/DNSCrypt/dnscrypt-proxy/issues/297 Log level 1 is the lowest that the application will go to.

  I'm not sure about the required aspect of this since the field is an `OptionField`, if `required` is `N` then a "blank" option shows up on the menu as "none". This word can be changed to something else using the `BlankDesc` element, but I'm not sure it adds value to be able to explicitly NOT set this value. It might be better to just set this as required, not have the extra option on the menu that doesn't actually do anything, and set the default to 2, and instead just always have log_level explicitly set to some value.

#### log_file

  dnscrypt-proxy source data type: `*string` [dnscrypt-proxy/config.go#L32](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L32)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `dnscrypt-proxy.log` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L166](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L166)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting isn't in the model, and is defined in the dnscrypt-proxy.toml.jinja service template.

#### log_file_latest

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L33](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L33)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L111](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L111)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L171](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L171)

  dnscrypt-proxy configuration state: unset

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  The value only matters when log_file is configured, and it's not required. Setting as default `true` to keep it in line with the code and configuration file, and also because the field is a `BooleanField`.

#### use_syslog

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L34](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L34)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L176](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L176)

  dnscrypt-proxy configuration state: unset

  Data type range: `boolean`

  This setting isn't in the model, and unless explicitly set as true will be false. The user won't have the option to use syslog on OPNsense.

#### cert_refresh_delay

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L44](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L44)

  dnscrypt-proxy source default: `240` [dnscrypt-proxy/config.go#L116](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L116)

  dnscrypt-proxy configuration default: `240` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L181](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L181)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `240`

  I tested `0` and `2147483647` as a setting for this, and both are valid. Using the 32-bit max as the high end just in case.

#### dnscrypt_ephemeral_keys

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L46](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L46)

  dnscrypt-proxy source default: `false` [dnscrypt-proxy/config.go#L118](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L118)

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L188](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L188)

  dnscrypt-proxy configuration state: unset

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Using false as default as it will be a `BooleanField` checkbox.

#### tls_disable_session_tickets

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L92](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L92)

  dnscrypt-proxy source default: `false` [dnscrypt-proxy/config.go#L141](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L141)

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L188](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L188)

  dnscrypt-proxy configuration state: unset

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Using false as the default as this is a `BooleanField` checkbox.

#### tls_cipher_suite

  dnscrypt-proxy source data type: `[]uint16` [dnscrypt-proxy/config.go#L93](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L93)

  dnscrypt-proxy source default: `nil` [dnscrypt-proxy/config.go#L142](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L142)

  dnscrypt-proxy configuration default: `[52392, 49199]` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L211](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L211)

  dnscrypt-proxy configuration state: unset

  Data type range: `0` to `32767`

  Model field type: `OptionField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `32767`

  From the example config:
```
  ## 49199 = TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256
  ## 49195 = TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256
  ## 52392 = TLS_ECDHE_RSA_WITH_CHACHA20_POLY1305
  ## 52393 = TLS_ECDHE_ECDSA_WITH_CHACHA20_POLY1305
  ##  4865 = TLS_AES_128_GCM_SHA256
  ##  4867 = TLS_CHACHA20_POLY1305_SHA256
```
  Using the range of the uint16 for min/max, starting at 0 since it's technically a valid suite (TLS_NULL_WITH_NULL_NULL).

  Looking into this, I found that there are more suites than what's included in the example configuration. This documentation here lists several more, and are defined as constants for a tls go lang package: https://pkg.go.dev/crypto/tls#pkg-constants

  I'm not sure if this exact package is used by dnscrypt-proxy as I couldn't find any direct references, but I'm assuming another package is importing it or something like that.

  The source references a much more comprehensive list at: https://www.iana.org/assignments/tls-parameters/tls-parameters.xml

  The table on the page has a Value column with two hexidecimal numbers, example: 0x13,0x03	TLS_CHACHA20_POLY1305_SHA256

  Combine the two values together into a single hexidecimal number: 0x1303 = 4867 (matches config example)

  Maybe there is an industry agreement to use these hexidecimal numbers to represent the suites across implementations of TLS.

  Since, the go lang TLS module only implements a subset of these suites, only the ones implemented will be useful. Also, inspecting my logs, I found that some servers are using suites not found in the example configuration list. So we should add all of the current ones available through the go TLS module.

```
  const (
	// TLS 1.0 - 1.2 cipher suites.
	TLS_RSA_WITH_RC4_128_SHA                      uint16 = 0x0005
	TLS_RSA_WITH_3DES_EDE_CBC_SHA                 uint16 = 0x000a
	TLS_RSA_WITH_AES_128_CBC_SHA                  uint16 = 0x002f
	TLS_RSA_WITH_AES_256_CBC_SHA                  uint16 = 0x0035
	TLS_RSA_WITH_AES_128_CBC_SHA256               uint16 = 0x003c
	TLS_RSA_WITH_AES_128_GCM_SHA256               uint16 = 0x009c
	TLS_RSA_WITH_AES_256_GCM_SHA384               uint16 = 0x009d
	TLS_ECDHE_ECDSA_WITH_RC4_128_SHA              uint16 = 0xc007
	TLS_ECDHE_ECDSA_WITH_AES_128_CBC_SHA          uint16 = 0xc009
	TLS_ECDHE_ECDSA_WITH_AES_256_CBC_SHA          uint16 = 0xc00a
	TLS_ECDHE_RSA_WITH_RC4_128_SHA                uint16 = 0xc011
	TLS_ECDHE_RSA_WITH_3DES_EDE_CBC_SHA           uint16 = 0xc012
	TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA            uint16 = 0xc013
	TLS_ECDHE_RSA_WITH_AES_256_CBC_SHA            uint16 = 0xc014
	TLS_ECDHE_ECDSA_WITH_AES_128_CBC_SHA256       uint16 = 0xc023
	TLS_ECDHE_RSA_WITH_AES_128_CBC_SHA256         uint16 = 0xc027
	TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256         uint16 = 0xc02f
	TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256       uint16 = 0xc02b
	TLS_ECDHE_RSA_WITH_AES_256_GCM_SHA384         uint16 = 0xc030
	TLS_ECDHE_ECDSA_WITH_AES_256_GCM_SHA384       uint16 = 0xc02c
	TLS_ECDHE_RSA_WITH_CHACHA20_POLY1305_SHA256   uint16 = 0xcca8
	TLS_ECDHE_ECDSA_WITH_CHACHA20_POLY1305_SHA256 uint16 = 0xcca9

	// TLS 1.3 cipher suites.
	TLS_AES_128_GCM_SHA256       uint16 = 0x1301
	TLS_AES_256_GCM_SHA384       uint16 = 0x1302
	TLS_CHACHA20_POLY1305_SHA256 uint16 = 0x1303
```

  We'll pre-populate this list with an option value drop down, allowing multi-select.

#### bootstrap_resolvers

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L86](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L86)

  dnscrypt-proxy source default: `9.9.9.9:53` [dnscrypt-proxy/xtransport.go#L31](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/xtransport.go#L31)

  dnscrypt-proxy configuration default: `['9.9.9.9:53', '8.8.8.8:53']` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L244](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L244)

  dnscrypt-proxy configuration state: set

  Data type range: Not applicable

  Model field type: `CSVListField`

  Model field required: No

  Model field default: None

  View field hint: `9.9.9.9:53,8.8.8.8:53`

  Here the example configuration has an additional server configured as default, while the source only as the first. I tested, and it will start without this value defined, so it's not required, but the configuration comes with this setting set as an example. We'll go with the source approach, and not require it, and provide the hint to the user as examples.

#### ignore_system_dns

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L87](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L87)

  dnscrypt-proxy source default: `false` [dnscrypt-proxy/config.go#L137](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L137)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L249](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L249)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`


  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  The defaults conflict, sort of. There is a module called xTransport with a struct which when created will set a value called `ignoreSystemDNS` to `true`. However, that setting may not be used, and can be overridden by the configured ignore_system_dns setting.

  Evaluating the source, I'm assuming that bootstrap_resolvers will always be defined, either by the source, or configuration file. It will always have at least one entry defined. So the scenarios in the code where bootstrap_resolvers > 0 will always happen (I'm assuming).
  If ignore_system_dns is undefined, and bootstrap_resolvers is defined, then xTransport.ignoreSystemDNS is set to config.go's default of `false`.
  If ignore_system_dns is defined, and bootstrap_resolvers is defined, then xTransport.ignoreSystemDNS is set to toml's ignore_system_dns.

  I don't think these scenarios can happen since bootstrap_resolvers is never undefined:
  If ignore_system_dns is undefined, and bootstrap_resolvers is undefined, then xTransport.ignoreSystemDNS keeps the xtransport.go default of `true`.
  If ignore_system_dns is defined, and bootstrap_resolvers is undefined, then xTransport.ignoreSystemDNS is set to toml's ignore_system_dns.

  I think that if bootstrap_resolvers are explicitly defined in the configuration file, then the default for ignore_system_dns should be set to true as it will force dnscrypt_proxy to use the bootstrap resolver first, and use the system as last resort.

  I think that if bootstrap_resolvers are not explicitly defined in the configuration file, then the default for ignore_system_dns should be set to false, as it should try the system resolvers first because the user didn't explicitly say to use 9.9.9.9:53.

  We can't easily do a conditional default though. Possible scenarios:

  bootstrap_resolvers unset by user, ignore_system_dns unchecked  | 9.9.9.9:53, false | expected as dnscrypt-proxy would use system DNS first

  bootstrap_resolvers unset by user, ignore_system_dns checked    | 9.9.9.9:53, true  | expected as dnscrypt-proxy would use built in default

  bootstrap_resolvers set by user, ignore_system_dns unchecked    | x.x.x.x:53, false | expected as dnscrypt-proxy would use system DNS first

  bootstrap_resolvers set by user, ignore_system_dns checked      | x.x.x.x:52, true  | expected as dnscrypt-proxy would use bootstrap_resolvers first

  This is a bit of a strange one, but maybe we should look at it from the perspective of what would be most commonly desirable. I think it might be generally preferred to configure this to be `true` to mitigate accidentally using the system resolver over using the bootstrap resolver. I think it's up for debate on which it should be. It's set to `true` in the configuration as well which means that most people already have this setting enabled.

#### netprobe_timeout

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L95](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L95)

  dnscrypt-proxy source default: `60` [dnscrypt-proxy/config.go#L143](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L143)

  dnscrypt-proxy configuration default: `60` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L259](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L259)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `60`

  I tested this with `0` and `2147483647` and no errors appeared in the logs. Assuming everything is fine. Not using a default as it's set by the application, using a hint instead to suggest.

#### netprobe_address

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L94](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L94)

  dnscrypt-proxy source default: `9.9.9.9:53` [dnscrypt-proxy/config.go#L27](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L27)

  dnscrypt-proxy configuration default: `9.9.9.9:53` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L270](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L270)

  dnscrypt-proxy configuration state: set

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  View field hint: `9.9.9.9:53`

  Since this is set to a default within the application, field not required, and no default configured. Using hint to show suggested/default setting.

#### offline_mode

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L96](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L96)

  dnscrypt-proxy source default: `false` [dnscrypt-proxy/config.go#L144](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L144)

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L277](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L277)

  dnscrypt-proxy configuration state: unset

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Using default `false` since it is a checkbox just in case.

#### query_meta

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L100](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L100)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `['key1:value1', 'key2:value2', 'token:MySecretToken']` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L287](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L287)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `CSVListField`

  Model field required: No

  Model field default: None

  View field hint: `key1:value1, key2:value2`

  No defaults, not required, using only hint and help text.

#### log_files_max_size

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L89](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L89)

  dnscrypt-proxy source default: `10` [dnscrypt-proxy/config.go#L138](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L138)

  dnscrypt-proxy configuration default: `10` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L293](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L293)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `10`

  Using 32-bit max int value. Config says use 0 for "unlimited."

#### log_files_max_age

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L90](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L90)

  dnscrypt-proxy source default: `7` [dnscrypt-proxy/config.go#L139](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L139)

  dnscrypt-proxy configuration default: `7` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L296](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L296)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `7`

  Using 32-bit max int value. Assuming 0 would end up being forever.

#### log_files_max_backups

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L91](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L91)

  dnscrypt-proxy source default: `1` [dnscrypt-proxy/config.go#L140](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L140)

  dnscrypt-proxy configuration default: `1` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L299](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L299)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `1`

  Using 32-bit max int value. Config says use 0 to keep all.

#### block_ipv6

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L49](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L49)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L317](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L317)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  This doesn't get explicitly set within the source, however, the default value of a non-initialized boolean is false. Since this is a checkbox, the default state is false, and that's what will get saved.

#### block_unqualified

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L50](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L50)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L322](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L322)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  This doesn't get explicitly set within the source, however, the default value of a non-initialized boolean is `false`. Since it's not explicitly set in the source, we'll set it here, mimicking the example configuration.

#### block_undelegated

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L51](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L51)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L328](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L328)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  This doesn't get explicitly set within the source, however, the default value of a non-initialized boolean is `false`. The configuration file says the following: Immediately respond to queries for local zones instead of leaking them to upstream resolvers (always causing errors or timeouts).

  I think generally it would be preferred to set this to `true` to prevent accidental leaking. Since it's not explicitly set in the source, we'll set it here, mimicking the example configuration.

#### reject_ttl

  dnscrypt-proxy source data type: `uint32` [dnscrypt-proxy/config.go#L59](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L59)

  dnscrypt-proxy source default: `600` [dnscrypt-proxy/config.go#L126](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L126)

  dnscrypt-proxy configuration default: `10` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L334](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L334)

  dnscrypt-proxy configuration state: unset

  Data type range: `0` to `4294967295`

  Model field type: `IntegerField`

  Model field required: No

  Model field default: `10`

  Model field minimum value: `0`

  Model field maximum value: `4294967295`

  View field hint: `10`

  This setting was changed [recently](https://github.com/DNSCrypt/dnscrypt-proxy/commit/d35c1c3cb2085cb2e97aa76c87bcede4db26f688) from 60 to 10. I think we should go with the example config default, and use what's in the configuration as the hint to the user. Maximum is the max value for a uint32.

#### forwarding_rules

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L70](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L70)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `forwarding-rules.txt` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L344](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L344)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is explicitly defined in `dnscrypt-proxy.toml.jinja` template.

#### cloaking_rules

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L71](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L71)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `cloaking-rules.txt` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L358](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L358)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is explicitely defined in `dnscrypt-proxy.toml.jinja` template.

#### cloak_ttl

  dnscrypt-proxy source data type: `uint32` [dnscrypt-proxy/config.go#L60](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L60)

  dnscrypt-proxy source default: `600` [dnscrypt-proxy/config.go#L127](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L127)

  dnscrypt-proxy configuration default: `600` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L362](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L362)

  dnscrypt-proxy configuration state: unset

  Data type range: `0` to `4294967295`

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `4294967295`

  View field hint: `600`

  Use hint as default, tested with `0`, and `4294967295`, no errors.

#### cache

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L52](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L52)

  dnscrypt-proxy source default: `true` [dnscrypt-proxy/config.go#L119](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L119)

  dnscrypt-proxy configuration default: `true` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L372](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L372)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `true`

  Since this is a checkbox, and both source and example have this as true, and set, we'll set this as `true` for default as well.

#### cache_size

  dnscrypt-proxy source data type: `int` [dnscrypt-proxy/config.go#L53](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L53)

  dnscrypt-proxy source default: `512` [dnscrypt-proxy/config.go#L120](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L120)

  dnscrypt-proxy configuration default: `4096` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L372](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L372)

  dnscrypt-proxy configuration state: set

  Data type range: platform dependent

  Model field type: `IntegerField`

  Model field required: No

  Model field default: `4096`

  Model field minimum value: `0`

  Model field maximum value: `2147483647`

  View field hint: `4096`

  Using `0` as minimum since it doesn't make sense to have a negative size though the data type supports it. Using `2147483647`, the 32-bit max just in case. I'm guessing that this unit is a quantity of bytes, which would put 4096 at 4 KiB, and the max at 2 GiB which is probably fine. This setting has been progressively increased over time, from 512, to 1024, etc. Since the difference between the source and config is so drastically different, lets use the config example as default.

#### cache_min_ttl

  dnscrypt-proxy source data type: `uint32` [dnscrypt-proxy/config.go#L57](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L57)

  dnscrypt-proxy source default: `60` [dnscrypt-proxy/config.go#L124](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L124)

  dnscrypt-proxy configuration default: `2400` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L382](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L382)

  dnscrypt-proxy configuration state: set

  Data type range: `0` to `4294967295`

  Model field type: `IntegerField`

  Model field required: No

  Model field default: `2400`

  Model field minimum value: `0`

  Model field maximum value: `4294967295`

  View field hint: `2400`

  Use hint as default, tested with `0`, and `4294967295`, no errors. This was increased in the configuraiton file from 600 to 2400 a couple of years ago. Using `2400` as hint. Since the difference between the default in source, the example configuration file is so drastic, let's explicitly define the default here to match.

#### cache_max_ttl

  dnscrypt-proxy source data type: `uint32` [dnscrypt-proxy/config.go#L58](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L58)

  dnscrypt-proxy source default: `86400` [dnscrypt-proxy/config.go#L125](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L125)

  dnscrypt-proxy configuration default: `86400` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L387](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L387)

  dnscrypt-proxy configuration state: set

  Data type range: `0` to `4294967295`

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `4294967295`

  View field hint: `86400`

  Use hint as default, tested with `0`, and `4294967295`, no errors.

#### cache_neg_min_ttl

  dnscrypt-proxy source data type: `uint32` [dnscrypt-proxy/config.go#L55](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L55)

  dnscrypt-proxy source default: `60` [dnscrypt-proxy/config.go#L122](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L122)

  dnscrypt-proxy configuration default: `60` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L392](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L392)

  dnscrypt-proxy configuration state: set

  Data type range: `0` to `4294967295`

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `4294967295`

  View field hint: `60`

  Use hint as default, tested with `0`, and `4294967295`, no errors. Since there is no difference between source and config, we won't explicitly set a default.

#### cache_neg_max_ttl

  dnscrypt-proxy source data type: `uint32` [dnscrypt-proxy/config.go#L56](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L56)

  dnscrypt-proxy source default: `600` [dnscrypt-proxy/config.go#L123](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L123)

  dnscrypt-proxy configuration default: `600` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L397](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L397)

  dnscrypt-proxy configuration state: set

  Data type range: `0` to `4294967295`

  Model field type: `IntegerField`

  Model field required: No

  Model field default: None

  Model field minimum value: `0`

  Model field maximum value: `4294967295`

  View field hint: `600`

  Use hint as default, tested with `0`, and `4294967295`, no errors. Since there is no difference between source and config, we won't explicitly set a default.

#### [captive_portals] map_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L280](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L280)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `example-captive-portals.txt` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L411](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L411)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [local_doh] listen_addresses

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L244](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L244)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `['127.0.0.1:3000']` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L427](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L427)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `CSVListField`

  Model field required: No

  Model field default: None

  View field hint: `127.0.0.1:3000`

  No default, but use example as hint. Really these local_doh fields should be conditionally required if the local_doh server setting is enabled, but can't do that at the moment.

#### [local_doh] path

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L245](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L245)

  dnscrypt-proxy source default: `/dns-query` [](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L113)

  dnscrypt-proxy configuration default: `/dns-query` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L435](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L435)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  View field hint: `/dns-query`

  Since this should only be set if explicitly using local DOH, leave as no default, and hint instead. Really these local_doh fields should be conditionally required if the local_doh server setting is enabled, but can't do that at the moment.

#### [local_doh] cert_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L246](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L246)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `localhost.pem` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L441](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L441)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template. Really these local_doh fields should be conditionally required if the local_doh server setting is enabled, but can't do that at the moment.

#### [local_doh] cert_key_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L247](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L247)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `localhost.pem` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L442](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L442)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template. Really these local_doh fields should be conditionally required if the local_doh server setting is enabled, but can't do that at the moment.

#### [query_log] file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L175](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L175)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `query.log` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L457](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L457)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [query_log] format

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L175](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L175)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `tsv` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L462](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L462)

  dnscrypt-proxy configuration state: set

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [query_log] ignored_qtypes

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L179](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L179)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `['DNSKEY', 'NS']` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L467](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L467)

  dnscrypt-proxy configuration state: set

  Data type range: Not applicable

  Model field type: `CSVListField`

  Model field required: No

  Model field default: None

  View field hint: `DNSKEY,NS`

  No default defined in the source, configuration default provided only as example.

#### [nx_log] file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L181](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L181)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `nx.log` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L483](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L483)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [nx_log] format

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L182](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L182)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `tsv` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L488](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L488)

  dnscrypt-proxy configuration state: set

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [blocked_names] blocked_names_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L186](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L186)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `blocked-names.txt` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L513](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L513)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  This will either be set by the user to use an external file, or by the jinja template with some static file names.

#### [blocked_names] log_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L187](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L187)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `blocked-names.log` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L518](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L518)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [blocked_names] log_format

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L188](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L188)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `tsv` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L523](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L523)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [blocked_ips] blocked_ips_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L210](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L210)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `blocked-ips.txt` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L541](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L541)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  This will either be set by the user to use an external file, or by the jinja template with some static file names.

#### [blocked_ips] log_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L211](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L211)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `blocked-ips.log` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L546](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L546)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [blocked_ips] log_format

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L212](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#212)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `tsv` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L551](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L551)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [allowed_names] allowed_names_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L204](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L204)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `allowed-names.txt` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L569](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L569)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  This will either be set by the user to use an external file, or by the jinja template with some static file names.

#### [allowed_names] log_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L205](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L205)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `allowed-names.log` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L574](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L574)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [allowed_names] log_format

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L206](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L206)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `tsv` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L579](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L579)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [allowed_ips] allowed_ips_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L222](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L222)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `allowed-ips.txt` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L597](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L597)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `TextField`

  Model field required: No

  Model field default: None

  This will either be set by the user to use an external file, or by the jinja template with some static file names.

#### [allowed_ips] log_file

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L223](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L223)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `allowed-ips.log` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L602](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L602)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [allowed_ips] log_format

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L224](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L224)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `tsv` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L606](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L606)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This setting is not stored in OPNsense, and is defined statically in the `dnscrypt-proxy.toml.jinja` template.

#### [schedules]

  dnscrypt-proxy source data type: `map[string]WeeklyRangesStr` [dnscrypt-proxy/config.go#L88](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L88)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: ????

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  This one is complicated. The main limitations around handling this (and sub settings) is how the data is stored in the OPNsense config, and how it's represented in the UI. It's a bit of a messy round-about approach right now.

#### [sources]

  dnscrypt-proxy source data type: `map[string]SourceConfig` [dnscrypt-proxy/config.go#L164](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L164)
```
type SourceConfig struct {
  	URL            string
  	URLs           []string
  	MinisignKeyStr string `toml:"minisign_key"`
  	CacheFile      string `toml:"cache_file"`
  	FormatStr      string `toml:"format"`
  	RefreshDelay   int    `toml:"refresh_delay"`
  	Prefix         string
  }
```
  This one is complicated because each node is named, and each node has settings.

#### [sources] [sources.???]

  Model field type: `TextField`

  Model field required: Yes

  This is the name of the node itself. The node can't be created without it.

#### [sources] [sources.???] urls

  Model field type: `CSVListFIeld`

  Model field required: Yes

  There is a specific error condition in the source if there are no URLs.

#### [sources] [sources.???] cache_file

  Model field type: `TextField`

  Model field required: Yes

  There is a specific error condition in the source if there is no cache file defined.

#### [sources] [sources.???] minisign_key

  Model field type: `TextField`

  Model field required: Yes

  There is a specific error condition in the source if there is no minisign key defined.

#### [sources] [sources.???] refresh_delay

  Model field type: `IntegerField`

  Model field required: No

  Model field minimum: `1`

  Model field maximum: `168`

  View field hint: `72`

  There is specific code which will conditionally force specific values.
```
if cfgSource.RefreshDelay <= 0 {
    cfgSource.RefreshDelay = 72
} else if cfgSource.RefreshDelay > 168 {
    cfgSource.RefreshDelay = 168
}
```
  As I understand this, the values can be from `1` to `168`.

#### [sources] [sources.???] prefix

  Model field type: `TextField`

  Model field required: No

  This will prepend any servers added by this source, with this prefix. Not well documented.

#### [broken_implementations] fragments_blocked

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L240](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L240)

  dnscrypt-proxy source default:
```
  "cisco",
  "cisco-ipv6",
  "cisco-familyshield",
  "cisco-familyshield-ipv6",
  "cleanbrowsing-adult",
  "cleanbrowsing-adult-ipv6",
  "cleanbrowsing-family",
  "cleanbrowsing-family-ipv6",
  "cleanbrowsing-security",
  "cleanbrowsing-security-ipv6",
```
  [dnscrypt-proxy/config.go#L148](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L148)

  dnscrypt-proxy configuration default:
```
  'cisco',
  'cisco-ipv6',
  'cisco-familyshield',
  'cisco-familyshield-ipv6',
  'cleanbrowsing-adult',
  'cleanbrowsing-adult-ipv6',
  'cleanbrowsing-family',
  'cleanbrowsing-family-ipv6',
  'cleanbrowsing-security',
  'cleanbrowsing-security-ipv6'
```
  [dnscrypt-proxy/example-dnscrypt-proxy.toml#L737](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L737)

  dnscrypt-proxy configuration state: unset

  Data type range: Not applicable

  Model field type: `JsonKeyValueStoreField`

  Model field required: No

  Model field default: N/A

  This one is a bit complicated since the field type is a Json Key store. This allows the user to select from the list which servers they want to flag as broken. The selections are then stored in OPNsense config as a comma separated list which then gets put into the dnscrypt-proxy config.

  Setting a default for a `JsonKeyValueStoreField` may not be an option. I haven't tried it yet, nor looked at the source.

  It might be good to do an "Extra" field on this like with the Disabled Server Names.

#### [broken_implementations] broken_query_padding

  This is an undocumented setting. Found this in the source, but it's not present in the example config. Going to leave it alone for now.

#### [doh_client_x509_auth] creds

```
type TLSClientAuthCredsConfig struct {
	ServerName string `toml:"server_name"`
	ClientCert string `toml:"client_cert"`
	ClientKey  string `toml:"client_key"`
	RootCA     string `toml:"root_ca"`
}
```

  Model field type: `ArrayField`

  Using an `ArrayField` to then store each part as a separate field.

#### [doh_client_x509_auth] creds {server_name}

  Model field type: `JsonKeyValueStoreField`

  This limits selection to only servers configured. Maybe need to do something different in the future.

#### [doh_client_x509_auth] creds {client_cert}

  Model field type: `TextField`

  Model field required: Yes

#### [doh_client_x509_auth] creds {client_key}

  Model field type: `TextField`

  Model field required: Yes

#### [doh_client_x509_auth] creds {root_ca}

  Model field type: `TextField`

  Model field required: No

#### [anonymized_dns] routes

```
type AnonymizedDNSConfig struct {
	Routes             []AnonymizedDNSRouteConfig `toml:"routes"`
	SkipIncompatible   bool                       `toml:"skip_incompatible"`
	DirectCertFallback bool                       `toml:"direct_cert_fallback"`
}
```

  Model field type: `ArrayField`

  This field is handled with an `ArrayField` type.

```
type AnonymizedDNSRouteConfig struct {
	ServerName string   `toml:"server_name"`
	RelayNames []string `toml:"via"`
}
```
#### [anonymized_dns] routes {server_name}

  Model field type: `JsonKeyValueStoreField`

  Model field required: Yes

  This field is used to get a list of servers to allow the user to select from the list which server to apply the routing rule to.

#### [anonymized_dns] routes {via}

  Model field type: `JsonKeyValueStoreField`

  Model field required: Yes

  This field is used to get a list of relays to allow the user to select from the list which server to route by for this server.

#### [anonymized_dns] skip_incompatible

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L234](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L234)

  dnscrypt-proxy source default:

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L803](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L803)

  dnscrypt-proxy configuration state: set

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Using default `false` since it is a checkbox just in case. Even though it's not required, it will always be included, and set.

#### [anonymized_dns] direct_cert_fallback

  dnscrypt-proxy source data type: `bool` [dnscrypt-proxy/config.go#L235](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L235)

  dnscrypt-proxy source default:

  dnscrypt-proxy configuration default: `false` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L810](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L810)

  dnscrypt-proxy configuration state: unset

  Data type range: `boolean`

  Model field type: `BooleanField`

  Model field required: No

  Model field default: `false`

  Using default `false` since it is a checkbox just in case. Even though it's not required, it will always be included, and set.


#### [dns64]

  Model field type: `ArrayField`

  Both of these nodes are handled with an `ArrayField` type, and using individual `TextFields` for each entry in the list for each node.

#### [dns64] prefix

  dnscrypt-proxy source data type: `[]string` [](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L275)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `['64:ff9b::/96']` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L837](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L837)

  dnscrypt-proxy configuration state: unset

  Model field type: `TextField`

  Model field required: No

  View field hint: `64:ff9b::/96`

  Even though this setting is a list, the field itself is a `TextField` housed within an `ArrayField` so each `TextField` entry compiles together into a list.

#### [dns64] resolver

  dnscrypt-proxy source data type: `[]string` [dnscrypt-proxy/config.go#L276](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L276)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `['[2606:4700:4700::64]:53', '[2001:4860:4860::64]:53']` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L843](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L843)

  dnscrypt-proxy configuration state: unset

  Model field type: `TextField`

  Model field required: No

  View field hint: `[2606:4700:4700::64]:53`

  Even though this setting is a list, the field itself is a `TextField` housed within an `ArrayField` so each `TextField` entry compiles together into a list.

#### [static]

  Model field type: `ArrayField`

  Each static entry gets an individual named entry.

#### [static] [static.???]

  Model field type: `TextField`

  Model field required: Yes

  Just the name of the node. Only required within the context of each entry.

#### [static] [static.???] stamp

  dnscrypt-proxy source data type: `string` [dnscrypt-proxy/config.go#L161](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/config.go#L161)

  dnscrypt-proxy source default: None

  dnscrypt-proxy configuration default: `sdns://AQcAAAAAAAAAAAAQMi5kbnNjcnlwdC1jZXJ0Lg` [dnscrypt-proxy/example-dnscrypt-proxy.toml#L857](https://github.com/DNSCrypt/dnscrypt-proxy/blob/master/dnscrypt-proxy/example-dnscrypt-proxy.toml#L857)

  dnscrypt-proxy configuration state: unset

  Model field type: `TextField`

  Model field required: Yes

  Only required within the context of an individual entry.

### Notes from the DNSCrypt Proxy installation

The installation of dnscrypt-proxy2 has some notes about functionality which may be useful. They're included here for reference.

=====
Message from dnscrypt-proxy2-2.0.45:

```
Version 2 of dnscrypt-proxy is written in Go and therefore isn't capable
of dropping privileges after binding to a low port on FreeBSD.

By default, this port's daemon will listen on port 5353 (TCP/UDP) as the
_dnscrypt-proxy user.

It's possible to bind it and listen on port 53 (TCP/UDP) with mac_portacl(4)
kernel module (network port access control policy). For this add
dnscrypt_proxy_mac_portacl_enable=YES in your rc.conf. The dnscrypt-proxy
startup script will load mac_portacl and add a rule where _dnscrypt-proxy user will
be able to bind on port 53 (TCP/UDP). This port can be changed by
dnscrypt_proxy_mac_portacl_port variable in your rc.conf. You also need to
change dnscrypt-proxy config file to use port 53.

Below are a few examples on how to redirect local connections from port
5353 to 53.

[ipfw]

  ipfw nat 1 config if lo0 reset same_ports \
    redirect_port tcp 127.0.0.1:5353 53 \
    redirect_port udp 127.0.0.1:5353 53
  ipfw add nat 1 ip from any to 127.0.0.1 via lo0

  /etc/rc.conf:
    firewall_enable="YES"
    firewall_nat_enable="YES"

  /etc/sysctl.conf:
    net.inet.ip.fw.one_pass=0

[pf]

  set skip on lo0
  rdr pass on lo0 proto { tcp udp } from any to port 53 -> 127.0.0.1 port 5353

  /etc/rc.conf:
    pf_enable="YES"

[unbound]

  /etc/rc.conf:
    local_unbound_enable="YES"

  /var/unbound/unbound.conf:
    server:
      interface: 127.0.0.1
      do-not-query-localhost: no

  /var/unbound/forward.conf:
    forward-zone:
      name: "."
      forward-addr: 127.0.0.1@5353

  If you are using local_unbound, DNSSEC is enabled by default. You should
  comment the "auto-trust-anchor-file" line or change dnscrypt-proxy to use
  servers with DNSSEC support only.
```
