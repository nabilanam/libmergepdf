<?php

declare(strict_types=1);

namespace nabilanam\libmergepdf\Driver;

use nabilanam\libmergepdf\Exception;
use nabilanam\libmergepdf\Source\SourceInterface;

final class TcpdiDriver implements DriverInterface
{
    /**
     * @var \TCPDI
     */
    private $tcpdi;

    private $pageCounts = [];

    public function __construct(\TCPDI $tcpdi = null)
    {
        $this->tcpdi = $tcpdi ?: new \TCPDI;
    }

    public function merge(SourceInterface ...$sources): string
    {
        $sourceName = '';

        try {
            $tcpdi = clone $this->tcpdi;

            foreach ($sources as $source) {
                $sourceName = $source->getName();
                $pageCount = $tcpdi->setSourceData($source->getContents());
                $pageNumbers = $source->getPages()->getPageNumbers() ?: range(1, $pageCount);
                $this->pageCounts[$sourceName] = count($pageNumbers);

                foreach ($pageNumbers as $pageNr) {
                    $template = $tcpdi->importPage($pageNr);
                    $size = $tcpdi->getTemplateSize($template);
                    $tcpdi->SetPrintHeader(false);
                    $tcpdi->SetPrintFooter(false);
                    $tcpdi->AddPage(
                        $size['w'] > $size['h'] ? 'L' : 'P',
                        [$size['w'], $size['h']]
                    );
                    $tcpdi->useTemplate($template);
                }
            }

            return $tcpdi->Output('', 'S');
        } catch (\Exception $e) {
            throw new Exception("'{$e->getMessage()}' in '$sourceName'", 0, $e);
        }
    }

    public function getPageCounts(): array
    {
        return $this->pageCounts;
    }
}
