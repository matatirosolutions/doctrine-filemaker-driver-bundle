# Valuelists #

If you wish to make use of the value lists functionality add the following to 'config.yaml' (or your chosen config file) 
   
```yaml
doctrine_file_maker_driver:
    valuelist_layout: 'ValueLists'
```
    
Setting the layout which contains all of the valuelists which you wish to use within your application.
    
On that layout add a field, for example a global field from a parameters table, set the field type to valuelist and choose the list you need. Repeat for every value list.
    
## Accessing a valuelist in twig ##
    
In your Twig template when you wish to output a value list do this:
    
```yaml
{{ select_picker('valuelist', 'valuelist name', { 'option': 'value', 'optipon2': value2 }) }}
```

Replace `valuelist name` with the appropriate name from File > Configure > Valuelists
        
Options which can be passed are

 - `class` (string) one or more classes to add to the select which is generated. Default: 'selectpicker'
 - `name` (string) the name to be given to the select.
 - `id` (string)the id to be given to the select, if not provided, and a name is that will be used instead
 - `selected` (string) the value which should be selected initially
 - `disabled` (boolean) shoudd the select be disabled, adds the disabled property to the select if set
 - `requried` (boolean) adds the HTML 5 required property if true
 - `data` a Twig object of other values to be added to the select as data attributes
    - `taxonomy` the name of the valuelist (e.g. creates `data-taxonomy="my value list"`)
    - `live-search` (boolean) used with the Bootstrap Selectpicker exention to enable the 'live-search' functionality of the plugin
    - `hide-disabled` (boolean) used by Bootstrap Selectpicker (see its documentation)
    - `title` (string) used by Bootstrap Selectpicker (see its documentation)
    - `clear-button` used by Bootstrap Selectpicker (see its documentation)
    
It's also possible to use `select_picker` passing `static` as the first parameter, and then an array of values to use as the second parameter. Pass the values thus:
```yaml
[{'id': 1, 'title': 'Option one'}, {'id': 2, 'title': 'Option two'}]
``` 