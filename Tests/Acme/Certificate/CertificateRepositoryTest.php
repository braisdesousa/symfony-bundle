<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Bundle\Tests\Acme\Certificate;

use AcmePhp\Bundle\Acme\Certificate\CertificateRepository;
use AcmePhp\Bundle\Acme\Certificate\Formatter\FormatterInterface;
use AcmePhp\Bundle\Acme\Certificate\Storage\CertificateStorage;
use AcmePhp\Bundle\Acme\Certificate\Storage\CertificateStorageFactory;
use AcmePhp\Bundle\Acme\Domain\DomainConfiguration;
use AcmePhp\Core\Ssl\Certificate;
use AcmePhp\Core\Ssl\CSR;
use AcmePhp\Core\Ssl\KeyPair;

class CertificateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var CertificateRepository */
    private $service;

    /** @var CertificateStorageFactory */
    private $mockStorageFactory;

    /** @var FormatterInterface */
    private $mockFormatter;

    public function setUp()
    {
        parent::setUp();

        $this->mockStorageFactory = $this->prophesize(CertificateStorageFactory::class);
        $this->mockFormatter = $this->prophesize(FormatterInterface::class);

        $this->service = new CertificateRepository(
            $this->mockStorageFactory->reveal(),
            [$this->mockFormatter->reveal()]
        );
    }

    public function test persistCertificate backups the previous certificates()
    {
        $dummyDomain = uniqid();
        $dummyCsr = $this->prophesize(CSR::class)->reveal();
        $dummyCertificate = $this->prophesize(Certificate::class)->reveal();
        $dummyDomainKeyPair = $this->prophesize(KeyPair::class)->reveal();

        $configuration = new DomainConfiguration($dummyDomain, $dummyCsr);

        $mockStorage = $this->prophesize(CertificateStorage::class);
        $this->mockStorageFactory->createCertificateStorage($dummyDomain)->willReturn($mockStorage->reveal());
        $mockStorage->backup()->shouldBeCalled();
        $mockStorage->saveCertificateFile(null, null)->shouldBeCalled();

        $this->service->persistCertificate($configuration, $dummyCertificate, $dummyDomainKeyPair);
    }

    public function test persistCertificate persists the certificate file()
    {
        $dummyDomain = uniqid();
        $dummyCsr = $this->prophesize(CSR::class)->reveal();
        $dummyCertificate = $this->prophesize(Certificate::class)->reveal();
        $dummyDomainKeyPair = $this->prophesize(KeyPair::class)->reveal();
        $dummyCertificateFileName = uniqid();
        $dummyCertificateFileContent = uniqid();

        $configuration = new DomainConfiguration($dummyDomain, $dummyCsr);

        $mockStorage = $this->prophesize(CertificateStorage::class);
        $this->mockStorageFactory->createCertificateStorage($dummyDomain)->willReturn($mockStorage->reveal());
        $mockStorage->backup()->shouldBeCalled();

        $this->mockFormatter->getName()->willReturn($dummyCertificateFileName);
        $this->mockFormatter->format($dummyCertificate, $dummyDomainKeyPair)->willReturn($dummyCertificateFileContent);
        $mockStorage->saveCertificateFile($dummyCertificateFileName, $dummyCertificateFileContent)->shouldBeCalled();

        $this->service->persistCertificate($configuration, $dummyCertificate, $dummyDomainKeyPair);
    }

    public function test clearCertificate remove persisted files()
    {
        $dummyDomain = uniqid();
        $dummyCsr = $this->prophesize(CSR::class)->reveal();
        $dummyCertificate = $this->prophesize(Certificate::class)->reveal();
        $dummyDomainKeyPair = $this->prophesize(KeyPair::class)->reveal();
        $dummyCertificateFileName = uniqid();

        $configuration = new DomainConfiguration($dummyDomain, $dummyCsr);

        $mockStorage = $this->prophesize(CertificateStorage::class);
        $this->mockStorageFactory->createCertificateStorage($dummyDomain)->willReturn($mockStorage->reveal());

        $this->mockFormatter->getName()->willReturn($dummyCertificateFileName);
        $this->mockFormatter->format()->shouldNotBeCalled();
        $mockStorage->removeCertificateFile($dummyCertificateFileName)->shouldBeCalled();

        $this->service->clearCertificate($configuration, $dummyCertificate, $dummyDomainKeyPair);
    }

    public function test hasCertificate checks if files exists()
    {
        $dummyDomain = uniqid();
        $dummyCsr = $this->prophesize(CSR::class)->reveal();
        $dummyCertificate = $this->prophesize(Certificate::class)->reveal();
        $dummyDomainKeyPair = $this->prophesize(KeyPair::class)->reveal();
        $dummyCertificateFileName = uniqid();

        $configuration = new DomainConfiguration($dummyDomain, $dummyCsr);

        $mockStorage = $this->prophesize(CertificateStorage::class);
        $this->mockStorageFactory->createCertificateStorage($dummyDomain)->willReturn($mockStorage->reveal());

        $this->mockFormatter->getName()->willReturn($dummyCertificateFileName);
        $this->mockFormatter->format()->shouldNotBeCalled();
        $mockStorage->hasCertificateFile($dummyCertificateFileName)->shouldBeCalled()->willReturn(true);

        $result = $this->service->hasCertificate($configuration, $dummyCertificate, $dummyDomainKeyPair);

        $this->assertTrue($result);
    }
}