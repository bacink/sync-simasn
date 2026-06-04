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

    private function makePegawaiStub(array $methods): PegawaiModule
    {
        // PegawaiModule uses __call for getDetail/getList, so we use an
        // anonymous class that extends PegawaiModule to provide test doubles.
        return new class($methods) extends PegawaiModule
        {
            public function __construct(private array $methods) {}

            public function setAccessToken(string $token): static
            {
                return $this;
            }

            public function __call(string $name, array $args)
            {
                if (isset($this->methods[$name])) {
                    return ($this->methods[$name])(...$args);
                }
                throw new \Exception("Stub method {$name} not configured");
            }
        };
    }

    public function test_get_pegawai_returns_formatted_array(): void
    {
        $mockDetail = new class
        {
            public function toArray(): array
            {
                return ['nip' => '19900101', 'nama' => 'John Doe', 'jabatan' => 'Staff'];
            }
        };

        $stub = $this->makePegawaiStub([
            'getDetail' => fn ($id) => $mockDetail,
        ]);

        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($stub);

        $app = $this->createMock(Application::class);
        $app->method('make')->with(AppClient::class)->willReturn($client);
        Facade::setFacadeApplication($app);

        $service = new SimAsnService;
        $result = $service->getPegawai('12345');

        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['nama']);
        $this->assertEquals('19900101', $result['nip']);
    }

    public function test_search_pegawai_returns_items_from_paginator(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('items')->willReturn([
            ['nama' => 'User A', 'nip' => '1'],
            ['nama' => 'User B', 'nip' => '2'],
        ]);

        $stub = $this->makePegawaiStub([
            'getList' => fn ($params) => $paginator,
        ]);

        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($stub);

        $app = $this->createMock(Application::class);
        $app->method('make')->with(AppClient::class)->willReturn($client);
        Facade::setFacadeApplication($app);

        $service = new SimAsnService;
        $results = $service->searchPegawai('keyword');

        $this->assertCount(2, $results);
        $this->assertEquals('User A', $results[0]['nama']);
        $this->assertEquals('User B', $results[1]['nama']);
    }

    public function test_list_pegawai_paginator_returns_length_aware_paginator(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('total')->willReturn(150);
        $paginator->method('lastPage')->willReturn(3);

        $stub = $this->makePegawaiStub([
            'getList' => function ($params) use ($paginator) {
                $this->assertEquals(50, $params['per_page']);
                $this->assertEquals(2, $params['page']);

                return $paginator;
            },
        ]);

        $client = $this->createMock(AppClient::class);
        $client->method('pegawai')->willReturn($stub);

        $app = $this->createMock(Application::class);
        $app->method('make')->with(AppClient::class)->willReturn($client);
        Facade::setFacadeApplication($app);

        $service = new SimAsnService;
        $result = $service->listPegawaiPaginator('pns', 50, 2);

        $this->assertSame($paginator, $result);
    }
}
