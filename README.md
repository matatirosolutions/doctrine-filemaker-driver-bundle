# Doctrine FileMaker driver bundle #

A Symfony bundle to implement one of the FileMaker Doctrine drivers to allow the use of either FileMaker CWP API or the FileMaker Data API in a Symfony application.

## Installation ##

Install through composer

```bash 
composer require matatirosoln/doctrine-filemaker-driver-bundle
```

**Important Note**: You will also need to install the appropriate driver now that we have also released a driver for the Data API  (this is a breaking change in v1.0. Originally the CWP driver was automatically installed by this bundle, however that doesn't now happen because you may not want it ;-).

If you wish to interact with FileMaker using the CWP API

```bash 
composer require matatirosoln/doctrine-filemaker-driver
```

Alternatively to use the Data API

```bash 
composer require matatirosoln/doctrine-fm-data-api-driver
```

## Configuration ##

For symfony less than v4.0 add the bundle to `AppKernal.php`
```php 
    new MSDev\DoctrineFileMakerDriverBundle\DoctrineFileMakerDriverBundle()
```

For Symfony v4+ add the bundle to `bundles.php`
```php
    MSDev\DoctrineFileMakerDriverBundle\DoctrineFileMakerDriverBundle::class => ['all' => true],
```



Configure Doctrine to use the FileMaker driver. In your Doctrine configuration comment out 
```yaml 
driver: xxxx
```
and replace it with

```yaml 
driver_class: MSDev\DoctrineFileMakerDriver\FMDriver

    or

driver_class: MSDev\DoctrineFMDataAPIDriver\FMDriver
```
    
If you wish to make use of the value lists functionality (currently only supported when using the PHP API driver because the Data API doesn't yet offer access to value lists) add the following to 'config.yaml' (or your chosen config file) 
   
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