# Containers #

Either request the `fm.container-service` from the container in symfony2 e.g.

    ```php
        $contaier = $this->get('fm.container-service');
    ```
    
Or in Symfony3 in a service constructor typehint the ContainerAccess service
    
        ```php
        namespace AppBundle\Service;
        
        use MSDev\DoctrineFileMakerDriver\Utility\ContainerAccess;
        
        class MyService
        {
        
            /**
             * @var ContainerAccess $fmScript
             */
            protected $fmContainer;
        
            public function __construct(ContainerAccess $fmContainer)
            {
                $this->fmContainer = $fmContainer;
            }   
        }
        ```
        
## Get content from a container ##
        
There are two methods to call depending on the container type. 
        
For an internal container use
        
        ```php
            $content = $container->getContainerContent($containerURL);
        ```
        
For a container using external storage use
        
        ```php
            $content = $container->getExternalContainerContent($containerURL);
        ```
                
## Inserting content into a container ##
                
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