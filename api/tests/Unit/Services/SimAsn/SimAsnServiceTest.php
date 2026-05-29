<?php

namespace Tests\Unit\Services\SimAsn;

use App\Services\SimAsn\SimAsnService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use SIM_ASN\AppClient;
use SIM_ASN\Modules\Pegawai as PegawaiModule;

class SimAsnServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = $this->createMock(Application::class);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        parent::tearDown();
    }

    public function test_get_pegawai_returns_formatted_array(): void
    {
        $mockPegawai = new class
        {
            public function toArray(): array
            {
                return ['nip' => '19900101', 'nama' => 'John Doe'];
            }
        };

        $pegawaiModule = $this->getMockBuilder(PegawaiModule::class)
            ->disableOriginalConstructor()
            ->addMethods(['getDetail'])
            ->getMock();

        $pegawaiModule->expects($this->once())
            ->method('getDetail')
            ->with('12345')
            ->willReturn($mockPegawai);

        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($pegawaiModule);

        $app = $this->createMock(Application::class);
        $app->method('make')->with(AppClient::class)->willReturn($client);
        Facade::setFacadeApplication($app);

        $service = new SimAsnService;
        $result = $service->getPegawai('12345');

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['nama']);
    }

    public function test_search_pegawai_returns_items_from_paginator(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('items')->willReturn([
            ['nama' => 'User A'],
            ['nama' => 'User B'],
        ]);

        $pegawaiModule = $this->getMockBuilder(PegawaiModule::class)
            ->disableOriginalConstructor()
            ->addMethods(['getList'])
            ->getMock();

        $pegawaiModule->expects($this->once())
            ->method('getList')
            ->willReturn($paginator);

        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($pegawaiModule);

        $app = $this->createMock(Application::class);
        $app->method('make')->with(AppClient::class)->willReturn($client);
        Facade::setFacadeApplication($app);

        $service = new SimAsnService;
        $results = $service->searchPegawai('keyword');

        $this->assertCount(2, $results);
        $this->assertEquals('User A', $results[0]['nama']);
    }

    public function test_list_pegawai_paginator_returns_length_aware_paginator(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('total')->willReturn(150);
        $paginator->method('lastPage')->willReturn(3);

        $pegawaiModule = $this->getMockBuilder(PegawaiModule::class)
            ->disableOriginalConstructor()
            ->addMethods(['getList'])
            ->getMock();

        $pegawaiModule->expects($this->once())
            ->method('getList')
            ->with($this->callback(fn ($params) => $params['per_page'] === 50 && $params['page'] === 2))
            ->willReturn($paginator);

        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($pegawaiModule);

        $app = $this->createMock(Application::class);
        $app->method('make')->with(AppClient::class)->willReturn($client);
        Facade::setFacadeApplication($app);

        $service = new SimAsnService;
        $result = $service->listPegawaiPaginator('pns', 50, 2);

        $this->assertSame($paginator, $result);
    }
}
