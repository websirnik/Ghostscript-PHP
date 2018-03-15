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
            '-sOutputFile=' . $destination,
            $input,
        );

        if ($numPages) {
            $commands = array_merge(['-dFirstPage=1', '-dLastPage=' . $numPages], $commands);
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
            '-sOutputFile=' . $destination,
            '-dFirstPage=' . $pageNum,
            '-dLastPage=' . $pageNum,
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
            $this->command(array(
                '-dBATCH',
                '-dNOPAUSE',
                '-sDEVICE=pdfwrite',
                '-sOutputFile=' . $output,
                $input,
                $bookmarks,
            ), true);
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
            $this->command(array(
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                sprintf('-dPDFSETTINGS=/%s', $quality),
                '-dNOPAUSE',
                '-dBATCH',
                '-dQUIET',
                '-dColorConversionStrategy=/sRGB',
                '-dProcessColorModel=/DeviceRGB',
                '-dColorConversionStrategyForImages=/DeviceRGB',
                '-sOutputFile=' . $destination,
                $input,
            ));
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
            $this->command(array(
                '-sDEVICE=pdfwrite',
                '-dNOPAUSE',
                '-dBATCH',
                '-dSAFER',
                sprintf('-dFirstPage=%d', $pageStart),
                sprintf('-dLastPage=%d', ($pageStart + $pageQuantity - 1)),
                '-sOutputFile=' . $destination,
                $input,
            ));
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
            $this->command(array(
                '-sDEVICE=txtwrite',
                '-dNOPAUSE',
                '-dBATCH',
                $pageStart > 0 ? sprintf('-dFirstPage=%d', $pageStart) : '',
                $pageQuantity > 0 ? sprintf('-dLastPage=%d', ($pageStart + $pageQuantity - 1)) : '',
                '-sOutputFile=' . $destination . '%d',
                $input,
            ));
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Ghostscript was unable to extract text from PDF', $e->getCode(), $e);
        }

        // if start and quantity not setuped 
        // by default 0
        if($pageStart == 0 && $pageQuantity == 0)
        {
            $isPageCounterFinished = false;
            $pageCounter = 1;
            // count all pages in temp folder
            while(!$isCountFinished){
                // if file exists increase counter and countinue
                if(file_exists($destination . $pageCounter)){
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
            $filePath = $destination . $i;
            $file = file_get_contents($filePath);
            $file = trim(preg_replace('/\s+/', ' ', $file));
            array_push($pages, $file);
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
