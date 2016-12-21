# ez-contenttype-manager

This is a tool that allows maanejar classes eZ Publish from the console

## Install Package

```bash
composer require smarter-solutions/ez-contenttype-manager "~2.0.0"
```
## Register Bundle

```php
// app/AppKernel.php

class EzPublishKernel extends Kernel
{
    ...
    public function registerBundles()
    {
        ...
        $bundles = array(
            ...
            new SmarterSolutions\EzComponents\EzContentTypeManagerBundle\EzContentTypeManagerBundle()
            ...
        );
        ...
    }
}
```
