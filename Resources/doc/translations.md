# Translations #

It can be very convenient to store the translations for your site in FileMaker. A Doctrine entity is provided by this bundle to support that. There are a number of requirements
 1. You must create a layout in your interface file called `WebContent`
 2. There must be a field on that layout called `ID` which contains the translations key which you wish to use
 3. There must be a field on that layout called `Content` which contains the content to display for that translation key.

At present only a single language is supported. Future versions are expected to add multi-language support.

If you also wish to use these translations in JavaScript through the BazingaJsTranslationBundle then ensure that bundle is installed (`composer require willdurand/js-translation-bundle`) and then add the following to 'config.yaml' (or your chosen config file) 
```yaml
doctrine_file_maker_driver:
   javascript_translations: true
```

### Alternatively create your own entity

If you already have content stored in FileMaker and wish to create your own entity to export content
 1. Create an entity which implements `MSDev\DoctrineFileMakerDriverBundle\Entity\WebContentInterface`
 2. Configure the bundle to use your new entity
```yaml
doctrine_file_maker_driver:
   content_class: \App\Entity\CustomContentEntity
```

## Command ##

There is a command which is provided to export the translations from FileMaker into the correct location for Symfony to access them. Use `bin/console translation:export`.

It would be wise to include running this step in your deployment process so that translations will always be updated on a deployment. If you're using the JavaScript translations, and WebPack then you'll need to ensure you perform the export command on your build server prior to running WebPack. 

## Controller access ##

It's also possible to enable the update of translations through the use of a controller `/translation/export`.

To enable this create `filemaker.yml` in `config/routes` with
```yaml
translation:
    resource: ../../vendor/matatirosoln/doctrine-filemaker-driver-bundle/Resources/config/routing.yml
```

You'll also need to add a rule to your Symfony firewall to allow appropriate access to this controller. 
