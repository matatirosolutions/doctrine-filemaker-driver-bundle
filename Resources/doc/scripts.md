# Scripts #

## Introduction ##

There is a difference to how you access scripts between the PHP API driver and the Data API driver because of the way the Data API goes about calling scripts. Unfortunately it requires a (FileMaker internal) recordId as well as the layout, script and parameter values. See examples below. 


### PHP API Driver ###

To call scripts in your FileMaker solution retrieve the `fm.script-service`.

For example from a controller in Symfony2

```php
$fmScript = $this->get('fm.script_service');
$record = $fmScript->performScript('LayoutName', 'ScriptName', 'Parameters');
```

Or for example in Symfony3+ in a service constructor typehint the ScriptService

```php
namespace App\Service;
    
use MSDev\DoctrineFileMakerDriver\Utility\ScriptAccess;
    
class MyService
{
    
    /**
     * @var ScriptAccess $fmScript
     */
    protected $fmScript;
    
    public function __construct(ScriptAccess $fmScript)
    {
        $this->fmScript = $fmScript;
    }

    public function callMyCoolScript(string $paramerter)
    {
        // so something
            
        $res = $this->fmScript('Layout', 'Script', $parameter);
            
        // do something else
    }
}
```

### Data API Driver ###

To call scripts in your FileMaker solution retrieve the `fm-data-api.script_service`.

For example from a controller in Symfony2

```php
$fmScript = $this->get('fm-data-api.script_service');
$record = $fmScript->performScript('LayoutName', 'RecordId', 'ScriptName', 'Parameters');
```

Or for example in Symfony3+ in a service constructor typehint the ScriptService **note** that this is a different class to that used with the PHP API above.

```php
namespace App\Service;
    
use MSDev\DoctrineFMDataAPIDriver\Utility\ScriptAccess;
    
class MyService
{
    
    /**
     * @var ScriptAccess $fmScript
     */
    protected $fmScript;
    
    public function __construct(ScriptAccess $fmScript)
    {
        $this->fmScript = $fmScript;
    }

    public function callMyCoolScript(string $paramerter)
    {
        // so something
            
        $res = $this->fmScript('Layout', 'RecordId', 'Script', $parameter);
            
        // do something else
    }
}
``` 