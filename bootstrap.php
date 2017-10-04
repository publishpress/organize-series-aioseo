<?php

use OrganizeSeries\application\Root;
use OrganizeSeries\domain\model\ClassOrInterfaceFullyQualifiedName;
use OrganizeSeries\AioSeoAddon\domain\Meta;
use OrganizeSeries\AioSeoAddon\domain\services\Bootstrap;


Root::initializeExtensionMeta(
    __FILE__,
    OS_AISEO_VERSION,
    new ClassOrInterfaceFullyQualifiedName(
        Meta::class
    )
);
$fully_qualified_bootstrap_class = new ClassOrInterfaceFullyQualifiedName(Bootstrap::class);
Root::registerAndLoadExtensionBootstrap($fully_qualified_bootstrap_class);
