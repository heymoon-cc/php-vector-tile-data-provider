<?php

namespace HeyMoon\VectorTileDataProvider\Registry;

use HeyMoon\VectorTileDataProvider\Export\MvtExportFormat;
use HeyMoon\VectorTileDataProvider\Export\SvgExportFormat;

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
