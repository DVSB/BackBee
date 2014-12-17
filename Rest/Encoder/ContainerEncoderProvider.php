<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BackBee\Rest\Encoder;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Provides encoders through the Symfony2 DIC
 *
 * @category    BackBee
 * @package     BackBee\Rest
 * @copyright   Lp digital system
 * @author      k.golovin
 */
class ContainerEncoderProvider extends ContainerAware implements IEncoderProvider
{
    /**
     * @var array
     */
    private $encoders;

    /**
     * Constructor.
     *
     * @param array $encoders List of key (format) value (service ids) of encoders
     */
    public function __construct(array $encoders)
    {
        $this->encoders = $encoders;
    }

    /**
     * @param string $format format
     *
     * @return boolean
     */
    public function supports($format)
    {
        return isset($this->encoders[$format]);
    }

    /**
     * @param string $format format
     *
     * @throws \InvalidArgumentException
     * @return FOS\RestBundle\Decoder\DecoderInterface
     */
    public function getEncoder($format)
    {
        if (!$this->supports($format)) {
            throw new \InvalidArgumentException(sprintf("Format '%s' is not supported by ContainerDecoderProvider.", $format));
        }

        return $this->container->get($this->encoders[$format]);
    }
}
