<?php

namespace Webkernel\Platform\Actions\Exports\Jobs;

use Closure;
use Webkernel\Platform\Actions\Exports\Exporter;
use Webkernel\Platform\Actions\Exports\Models\Export;
use \Webkernel\Queue\Bus\Queueable;
use Webkernel\Filesystem\Contracts\Filesystem;
use \Webkernel\Queue\Contracts\ShouldQueue;
use \Webkernel\Queue\Dispatch\Dispatchable;
use \Webkernel\HttpFoundation\File;
use \Webkernel\Queue\InteractsWithQueue;
use \Webkernel\Queue\SerializesModels;
use Webkernel\Exporters\Csv\Reader as CsvReader;
use Webkernel\Exporters\Csv\Statement;
use League\Flysystem\UnableToWriteFile;
use Webkernel\Exporters\Xlsx\Common\Entity\Style\Style;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Writer;

class CreateXlsxFile implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public ?int $tries = 3;

    public ?int $maxExceptions = 0;

    /** @var array<int> */
    public array $backoff = [30, 60, 300];

    protected Exporter $exporter;

    /**
     * @param  array<string, string>  $columnMap
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        protected Export $export,
        protected array $columnMap,
        protected array $options = [],
    ) {
        $this->exporter = $this->export->getExporter(
            $this->columnMap,
            $this->options,
        );
    }

    public function handle(): void
    {
        $disk = $this->export->getFileDisk();

        $writer = app(Writer::class, ['options' => $this->exporter->getXlsxWriterOptions()]);
        $writer->openToFile($temporaryFile = tempnam(sys_get_temp_dir(), $this->export->file_name));

        $this->exporter->configureXlsxWriterAfterOpen($writer);

        $csvDelimiter = $this->exporter::getCsvDelimiter();

        $writeRowsFromFile = function (string $file, ?Style $style, ?Closure $makeRow) use ($csvDelimiter, $disk, $writer): void {
            $csvReader = CsvReader::from($disk->readStream($file));
            $csvReader->setDelimiter($csvDelimiter);
            $csvResults = (new Statement)->process($csvReader);

            foreach ($csvResults->getRecords() as $values) {
                $writer->addRow($makeRow($values, $style));
            }
        };

        $cellStyle = $this->exporter->getXlsxCellStyle();

        $writeRowsFromFile(
            $this->export->getFileDirectory() . DIRECTORY_SEPARATOR . 'headers.csv',
            $this->exporter->getXlsxHeaderCellStyle() ?? $cellStyle,
            $this->exporter->makeXlsxHeaderRow(...),
        );

        $makeRow = $this->exporter->makeXlsxRow(...);

        foreach ($disk->files($this->export->getFileDirectory()) as $file) {
            if (str($file)->endsWith('headers.csv')) {
                continue;
            }

            if (! str($file)->endsWith('.csv')) {
                continue;
            }

            $writeRowsFromFile($file, $cellStyle, $makeRow);
        }

        $this->exporter->configureXlsxWriterBeforeClose($writer);

        $writer->close();

        try {
            if ($disk->putFileAs(
                $this->export->getFileDirectory(),
                new File($temporaryFile),
                "{$this->export->file_name}.xlsx",
                Filesystem::VISIBILITY_PRIVATE,
            ) === false) {
                throw UnableToWriteFile::atLocation($this->export->getFileDirectory() . DIRECTORY_SEPARATOR . "{$this->export->file_name}.xlsx");
            }
        } finally {
            unlink($temporaryFile);
        }
    }
}
