<?php
namespace BackBuilder\Importer;

use BackBuilder\BBApplication;

/**
 * @category    BackBuilder
 * @package     BackBuilder\BddExport
 * @copyright   Lp system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 */
interface IImporterConnector
{
    public function __construct(BBApplication $application, array $values);

    public function find($string);
}