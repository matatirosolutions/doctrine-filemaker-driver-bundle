# Doctrine FileMaker driver bundle #

A Symfony bundle to implement the FileMaker Doctrine driver to allow the use the FileMaker CWP API in a Symfony application.

## Installation ##

Install through composer

    ```php
    composer require matatirosoln/doctrine-filemaker-driver-bundle
    ```
    
For right now you'll need to add `dev-master` to that as there isn't a tagged version yet - expect the first tag in late August 2017 when the first implementation of this goes into a production environment.
    
Add the bundle to 'AppKernal.php'
    
    ```php
    new MSDev\DoctrineFileMakerDriverBundle\DoctrineFileMakerDriverBundle()
    ```

Configure Doctrine to use the FileMaker driver. In your Doctrine configuration comment out 

    ```yaml
    driver: xxxx
    ```
and replace it with

    ```yaml
    driver_class: MSDev\DoctrineFileMakerDriver\FMDriver
    ```
    
If you wish to make use of the value lists functionality add the following to 'config.yaml' (or your chosen config file) 
   
    ```yaml
    doctrine_file_maker_driver:
        valuelist_layout: 'ValueLists'
     ```
    
See the notes on 'conventions' with regard to creating entities for use with FileMaker in the [Doctrine FileMaker driver](https://github.com/matatirosolutions/doctrine-filemaker-driver "Doctrine FileMaker bundle") readme.
 
## Services ##

There are a number of useful services etc which this bundle adds to your project.

1. Access to [scripts](Resources/doc/scripts.md "scripts") within your FileMaker solution
2. Interaction with [containers](Resources/doc/containers.md "containers")
3. Using [valuelists](Resources/doc/valuelists.md "valuelists") through a twig extension.