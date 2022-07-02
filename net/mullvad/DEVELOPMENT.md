# Development Notes

## API based data sources

There are several fields on the Account tab that I would like to populate with data from an API,  like account expiration, account status, etc. It's not practical to store this data in the config as it would be best pulled on-demand from Mullvad's API. Things like account expiration is not static and would extend when a payment is made to the service. So it's not practical to store this information.

This information can be populated on the page in any number of ways. However, a standout approach to me is to use the existing Volt framework, and the mapDataToFormUI() functionality. Some advantages of doing that would be that the user experience generally remains the same, the page layout is drawn with the same structure and style as all of the other pages, everything looks the same as all of the other pages in the UI, and the drawing of the page itself happens by the framework.

This approach means riding the model to populate the data. Instead of getting the data from the configuration, it would be pulling it from some other source. There's three ways that I've come up with in how this might be accomplished:
1. In the model, have functions which call APIs, override getNodes(), and inject our data whenever it's retrieved.
2. In the API, have functions which call APIs, override, and inject our data whenever it's retrieved.
3. In a custom field type, populate the data from an API call.

### Challenges

There are several challenges with this approach:

* Fields will be separate data elements in the model, and each with its own getNodeData(), thus to populate the data for each field, if an API call is made for each field, it would be several API calls to the same endpoint since all of the data is from the same API. This could also result in hammering the API.
* Any calls to getModel() will result in this API call happening. It might not be best to do that. Maybe these should be in a separate model? If that's the case, then the framework will have to accommodate that since currently the form declaration only occurs with the root, tabs, and boxes, and forms can't be nested in the HTML.
* Hammer mitigation, if the API call happens whenever the model is loaded there is significant risk of hammering the API. This should be mitigated, maybe through a similar approach as the JsonKeyValueStoreField using a timer, and file in the file system.
* If the API call is made only when getNodeData() is called it would reduce the frequency of the API call, and prevent that from being called when other model data is being requested (like when checking the 'enabled' field for example).

### Model approach

The model is intended to manage the data, and it doesn't matter where the data comes from, like a database, XML, or an API. The model seems like a practical location to perform this activity. Source the data from the API, then send the data to the API controller, where it then presents it as JSON to the consumer (mapDataToFormUI).

### FieldType approach

This one moves one level deeper, into the model. It would be creating a custom field, which is designed to make the API call, and present back the data based on the field being queried. This could work by using the JsonKeyValueStoreField approach of having a single API call, and a file store the latest data from the API. It would mitigate hammering, and allow each field getNodeData() to retrieve data from the file individually. It would work very similarly to the Model approach.

A FieldType like this might be able to be made pretty generic too. Like JsonKeyValueStoreField, but not focused on delivering optionvalues for a dropdown.

### Controller approach

This one was the first approach I came up with, but I feel is less appropriate since the controller's role is not to be the source of data. It could be done here, where the controller makes an API call, and then injects the data into the model array after it retrieves it, and it does work.
