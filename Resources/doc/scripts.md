# Scripts #

To call scripts in your FileMaker solution retrieve the `fm.script-service`.

For example from a controller in Symfony2

    ```php
    $fmScript = $this->get('fm.script_service');
    $record = $fmScript->performScript('LayoutName', 'ScriptName', 'Parameters');
    ```

Or in Symfony3 in a service constructor typehint the ScriptService

    ```php
    namespace AppBundle\Service;
    
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
    