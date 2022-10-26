<?php

namespace HeyMoon\MVTTools\Registry;

use HeyMoon\MVTTools\Export\SvgExportFormat;
use HeyMoon\MVTTools\Service\MvtExportFormat;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ExportFormatRegistry extends AbstractExportFormatRegistry
{
    protected function supports(): array
    {
        return [MvtExportFormat::get(), SvgExportFormat::get()];
    }
}
