<?php

namespace Ghostscript;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Ghostscript\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class Transcoder extends AbstractBinary
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ghostscript-transcoder';
    }

    /**
     * Transcode a PDF to an image.
     *
     * @param string $input The path to the input file.
     * @param string $destination The path to the output file.
     * @param int $numPages
     *
     * @return Transcoder
     *
     * @throws RuntimeException In case of failure
     */
    public function toImages($input, $destination, $numPages)
    {
        $commands = array(
            '-sDEVICE=jpeg',
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dJPEGQ=75',
            '-r300x300',
            '-sOutputFile='.$destination,
            $input,
        );

        if ($numPages) {
            $commands = array_merge(['-dFirstPage=1', '-dLastPage='.$numPages], $commands);
        }

        try {
            $this->command($commands, true);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Ghostscript was unable to transcode to Image', $e->getCode(), $e);
        }

        // if (!file_exists($destination)) {
        //    throw new RuntimeException('Ghostscript was unable to transcode to Image');
        // }

        return $this;
    }

    /**
     * Transcode a PDF to an image.
     *
     * @param string $input The path to the input file.
     * @param string $destination The path to the output file.
     * @param int $pageNum
     *
     * @return Transcoder
     *
     * @throws RuntimeException In case of failure
     */
    public function toImage($input, $destination, $pageNum = 1)
    {
        $commands = array(
            '-sDEVICE=jpeg',
            '-dNOPAUSE',
            '-dBATCH',
            '-dSAFER',
            '-dJPEGQ=75',
            '-r300x300',
            '-sOutputFile='.$destination,
            '-dFirstPage='.$pageNum,
            '-dLastPage='.$pageNum,
            $input,
        );

        try {
            $this->command($commands, true);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Ghostscript was unable to transcode to Image', $e->getCode(), $e);
        }

        // if (!file_exists($destination)) {
        //    throw new RuntimeException('Ghostscript was unable to transcode to Image');
        // }

        return $this;
    }

    /**
     * Add bookmarks to pdf file
     *
     * @param string $input The path to the input file.
     * @param string $output The path to the output file.
     * @param string $bookmarks The path to the bookmarks file.
     *
     * @return Transcoder
     *
     * @throws RuntimeException In case of failure
     */
    public function addBookmarks($input, $output, $bookmarks)
    {
        try {
            $this->command(
                array(
                    '-dBATCH',
                    '-dNOPAUSE',
                    '-sDEVICE=pdfwrite',
                    '-sOutputFile='.$output,
                    $input,
                    $bookmarks,
                ),
                true
            );
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Ghostscript was unable to add bookmarks', $e->getCode(), $e);
        }

        if (!file_exists($output)) {
            throw new RuntimeException('Ghostscript was unable to add bookmarks');
        }

        return $this;
    }

    /**
     * Transcode a PDF to another PDF
     *
     * @param string $input The path to the input file.
     * @param string $destination The path to the output file.
     * @param integer $quality The number of the first page.
     *
     * @return Transcoder
     *
     * @throws RuntimeException In case of failure
     */
    public function optimizePDF($input, $destination, $quality)
    {
        try {
            $this->command(
                array(
                    '-sDEVICE=pdfwrite',
                    '-dCompatibilityLevel=1.4',
                    sprintf('-dPDFSETTINGS=/%s', $quality),
                    '-dNOPAUSE',
                    '-dBATCH',
                    '-dQUIET',
                    '-dColorConversionStrategy=/sRGB',
                    '-dProcessColorModel=/DeviceRGB',
                    '-dColorConversionStrategyForImages=/DeviceRGB',
                    '-sOutputFile='.$destination,
                    $input,
                )
            );
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Ghostscript was unable to optimize PDF', $e->getCode(), $e);
        }

        if (!file_exists($destination)) {
            throw new RuntimeException('Ghostscript was unable to optimize PDF');
        }

        return $this;
    }

    /**
     * Transcode a PDF to another PDF
     *
     * @param string $input The path to the input file.
     * @param string $destination The path to the output file.
     * @param integer $pageStart The number of the first page.
     * @param integer $pageQuantity The number of page to include.
     *
     * @return Transcoder
     *
     * @throws RuntimeException In case of failure
     */
    public function toPDF($input, $destination, $pageStart, $pageQuantity)
    {
        try {
            $this->command(
                array(
                    '-sDEVICE=pdfwrite',
                    '-dNOPAUSE',
                    '-dBATCH',
                    '-dSAFER',
                    sprintf('-dFirstPage=%d', $pageStart),
                    sprintf('-dLastPage=%d', ($pageStart + $pageQuantity - 1)),
                    '-sOutputFile='.$destination,
                    $input,
                )
            );
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Ghostscript was unable to transcode to PDF', $e->getCode(), $e);
        }

        if (!file_exists($destination)) {
            throw new RuntimeException('Ghostscript was unable to transcode to PDF');
        }

        return $this;
    }

    public function countFolder($dir)
    {
        return array_diff(scandir($input), array('..', '.'));
    }

    /**
     * @param string $input The path to the input file.
     * @param integer $pageStart The number of the first page.
     * @param integer $pageQuantity The number of page to include.
     * @return $this
     */
    public function extractText($input, $pageStart = 0, $pageQuantity = 0)
    {
        $destination = tempnam(sys_get_temp_dir(), Uuid::uuid4()->toString());
        try {
            $this->command(
                array(
                    '-sDEVICE=txtwrite',
                    '-dNOPAUSE',
                    '-dBATCH',
                    // $pageStart > 0 ? sprintf('-dFirstPage=%d', $pageStart) : '',
                    // $pageQuantity > 0 ? sprintf('-dLastPage=%d', ($pageStart + $pageQuantity - 1)) : '',
                    '-sOutputFile='.$destination.'%d',
                    $input,
                )
            );
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Ghostscript was unable to extract text from PDF', $e->getCode(), $e);
        }

        // if start and quantity not setuped
        // by default 0
        if ($pageStart == 0 && $pageQuantity == 0) {
            $isPageCounterFinished = false;
            $pageCounter = 1;
            // count all pages in temp folder
            while (!$isPageCounterFinished) {
                // if file exists increase counter and countinue
                if (file_exists($destination.$pageCounter)) {
                    $pageCounter++;
                    continue;
                } else {
                    // update start and quantity and exit from loop
                    $pageStart = 1;
                    $pageQuantity = $pageCounter - 1;
                    $isPageCounterFinished = true;
                    break;
                }
            }
        }

        $pages = array();
        for ($i = $pageStart; $i <= $pageQuantity; $i++) {
            $filePath = $destination.$i;
            $string = file_get_contents($filePath);

            // Convert \r\n to <br>
            $string = nl2br($string);

            // How to replace decoded Non-breakable space (nbsp)
            // https://stackoverflow.com/a/40724830/257815
            $string = preg_replace('/\xc2\xa0/', ' ', $string);
            // ignore https://www.fileformat.info/info/unicode/char/2588/index.htm
            $string = preg_replace('/\xe2\x96\x88/', ' ', $string);

            // Replace Zero Width Space using preg_replace
            // https://gist.github.com/ahmadazimi/b1f1b8f626d73728f7aa
            $string = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $string);

            // Convert unicode encoding to html encoding
            // https://stackoverflow.com/a/37184368/257815
            $string = preg_replace_callback(
                '/[\x{80}-\x{10FFFF}]/u',
                function ($m) {
                    $char = current($m);
                    $utf = iconv('UTF-8', 'UCS-4', $char);

                    return sprintf("&#x%s;", ltrim(strtoupper(bin2hex($utf)), "0"));
                },
                $string
            );

            // Remove extra whitepace
            $string = preg_replace('/\s+/', ' ', $string);

            array_push($pages, $string);
            unlink($filePath);
        }

        return $pages;
    }

    /**
     * Creates a Transcoder.
     *
     * @param array|ConfigurationInterface $configuration
     * @param LoggerInterface $logger
     *
     * @return Transcoder
     */
    public static function create($configuration = array(), LoggerInterface $logger = null)
    {
        if (!$configuration instanceof ConfigurationInterface) {
            $configuration = new Configuration($configuration);
        }

        $binaries = $configuration->get('gs.binaries', array('gs'));

        return static::load($binaries, $logger, $configuration);
    }
}
