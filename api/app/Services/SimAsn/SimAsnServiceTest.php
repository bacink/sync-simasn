<?php

namespace Tests\Unit\Services\SimAsn;

use App\Services\SimAsn\SimAsnService;
use PHPUnit\Framework\TestCase;
use SIM_ASN\AppClient;
use SIM_ASN\Modules\Pegawai as PegawaiModule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SimAsnServiceTest extends TestCase
{
    /**
     * Test that getPegawai returns an array when data is found.
     */
    public function test_get_pegawai_returns_formatted_array()
    {
        // 1. Mock the specific module (Pegawai)
        $pegawaiModule = $this->getMockBuilder(PegawaiModule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDetail'])
            ->getMock();

        // Mock the return object that has a toArray() method
        $mockPegawai = new class {
            public function toArray()
            {
                return ['nip' => '19900101', 'nama' => 'John Doe'];
            }
        };

        $pegawaiModule->expects($this->once())
            ->method('getDetail')
            ->with('12345')
            ->willReturn($mockPegawai);

        // 2. Mock the AppClient to return our module mock
        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($pegawaiModule);

        // 3. Inject mock into service
        $service = new SimAsnService($client);
        $result = $service->getPegawai('12345');

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['nama']);
    }

    /**
     * Test searchPegawai handling a paginated response.
     */
    public function test_search_pegawai_returns_items_from_paginator()
    {
        $pegawaiModule = $this->getMockBuilder(PegawaiModule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getList'])
            ->getMock();

        // Mock the LengthAwarePaginator returned by the SDK
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('items')->willReturn([
            ['nama' => 'User A'],
            ['nama' => 'User B']
        ]);

        $pegawaiModule->method('getList')
            ->with(['search' => 'keyword'])
            ->willReturn($paginator);

        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($pegawaiModule);

        $service = new SimAsnService($client);
        $results = $service->searchPegawai('keyword');

        $this->assertCount(2, $results);
        $this->assertEquals('User A', $results[0]['nama']);
    }
}
