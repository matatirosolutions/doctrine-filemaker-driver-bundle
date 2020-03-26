# Containers #

How you access containers varies slightly depending on your connection type, i.e. if you're using the PHP API for the Data API.

## Using the PHP API ##

Either request the `fm.container-service` from the container in symfony 2 e.g.

```php
$contaier = $this->get('fm.container-service');
```
    
Or in Symfony 3+ in a service constructor typehint the `ContainerAccess` utility.
    
```php
namespace AppBundle\Service;
        
use MSDev\DoctrineFileMakerDriver\Utility\ContainerAccess;
        
class MyService
{  
    /** @var ContainerAccess $fmContainer */
    protected $fmContainer;
        
    public function __construct(ContainerAccess $fmContainer)
    {
        $this->fmContainer = $fmContainer;
    }   
}
```
        
### Get content from a container ###
        
There are different methods to call depending on the container type and your connection type. 
        
For an internal container use
        
```php
$content = $container->getContainerContent($containerURL);
```
        
For a container using external storage use
        
```php
$content = $container->getExternalContainerContent($containerURL);
```

### Inserting content into a container ###
                
To insert content into a container requires that you add a script to your solution called `ImportToContainer`. This script will only work with FileMaker v16 or higher.
 
That script should contain the following
                               
Then within PHP call
```php
$container->insertIntoContainer($layout, $idField, $uuid, $field, $assetPath);
``` 

 - `layout` (string) the layout to use for the import
 - `idField` (string) the name of the field used to locate the record where the content will be imported
 - `uuid` (string) the ID to be used to search in teh above field for the correct record
 - `field` (string) the name of the container field (which obviously has to be on the layout)
 - `assetPath` (string) a fully qualified URL, able to be resolved by the FileMaker server to the asset.
  
The workflow to use this functionality would likely go something like
1. user uplaods a file to the application
2. the application temporarily saves the file into publicly accessible location
3. the above method is called
4. the application deletes the uploaded file.


## Using the Data API ##

Typehint the `ContainerAccess` utility in your service.

```php
namespace AppBundle\Service;
        
use MSDev\DoctrineFMDataAPIDriver\Utility\ContainerAccess;
        
class MyService
{  
    /** @var ContainerAccess $fmContainer */
    protected $fmContainer;
        
    public function __construct(ContainerAccess $fmContainer)
    {
        $this->fmContainer = $fmContainer;
    }   
}
```

### Get content from a container ###

The Data API returns `$containerURL` when you request a container, to retrieve the container content 

```php
$contant = $container->getStreamedContainerContent($containerURL);
```

### Inserting content into a container ###

```php
$container->performContainerInsert($layout, $recId, $field, $file, $repetition = 1);
```
 - `layout` (string) the layout to use for the import
 - `recId` (int) the FileMaker internal record ID of the record to be imported to
 - `field` (string) the name of the container field (which obviously has to be on the layout)
 - `file` (string) the path on disk to the local file to be inserted into the container
 - `repetition` (int) the field repetition if the container is a repeating field.
 
The above means that the record must already exist, so it may be necessary to generate the record (entity), including flushing entity manager changes if the record is being created, prior to making this call.