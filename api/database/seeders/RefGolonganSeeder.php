<?php

namespace Database\Seeders;

use App\Enums\JenisAsn;
use App\Models\RefGolongan;
use Illuminate\Database\Seeder;

class RefGolonganSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Golongan I
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'I',    'sub_golongan' => 'a', 'pangkat' => 'Juru Muda',                   'urutan' => 1],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'I',    'sub_golongan' => 'b', 'pangkat' => 'Juru Muda Tingkat I',           'urutan' => 2],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'I',    'sub_golongan' => 'c', 'pangkat' => 'Juru',                          'urutan' => 3],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'I',    'sub_golongan' => 'd', 'pangkat' => 'Juru Tingkat I',                 'urutan' => 4],
            // Golongan II
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'II',   'sub_golongan' => 'a', 'pangkat' => 'Pengatur Muda',                 'urutan' => 5],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'II',   'sub_golongan' => 'b', 'pangkat' => 'Pengatur Muda Tingkat I',       'urutan' => 6],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'II',   'sub_golongan' => 'c', 'pangkat' => 'Pengatur',                      'urutan' => 7],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'II',   'sub_golongan' => 'd', 'pangkat' => 'Pengatur Tingkat I',             'urutan' => 8],
            // Golongan III
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'III',  'sub_golongan' => 'a', 'pangkat' => 'Penata Muda',                  'urutan' => 9],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'III',  'sub_golongan' => 'b', 'pangkat' => 'Penata Muda Tingkat I',        'urutan' => 10],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'III',  'sub_golongan' => 'c', 'pangkat' => 'Penata',                       'urutan' => 11],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'III',  'sub_golongan' => 'd', 'pangkat' => 'Penata Tingkat I',              'urutan' => 12],
            // Golongan IV
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'IV',   'sub_golongan' => 'a', 'pangkat' => 'Pembina',                      'urutan' => 13],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'IV',   'sub_golongan' => 'b', 'pangkat' => 'Pembina Tingkat I',             'urutan' => 14],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'IV',   'sub_golongan' => 'c', 'pangkat' => 'Pembina Utama Muda',             'urutan' => 15],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'IV',   'sub_golongan' => 'd', 'pangkat' => 'Pembina Utama',                'urutan' => 16],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'IV',   'sub_golongan' => 'e', 'pangkat' => 'Pembina Utama Madya',           'urutan' => 17],
            ['jenis_asn' => JenisAsn::PNS, 'golongan' => 'IV',   'sub_golongan' => 'f', 'pangkat' => 'Pembina Utama Tinggi',          'urutan' => 18],
            // PPPK Golongan I  (19-22)
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'I',   'sub_golongan' => null, 'pangkat' => 'Juru Muda',                   'urutan' => 19],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'II',  'sub_golongan' => null, 'pangkat' => 'Juru',                        'urutan' => 20],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'III', 'sub_golongan' => null, 'pangkat' => 'Pengatur',                     'urutan' => 21],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'IV',  'sub_golongan' => null, 'pangkat' => 'Penata Muda',                 'urutan' => 22],
            // PPPK Golongan V  (23-27)
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'V',   'sub_golongan' => null, 'pangkat' => 'Penata',                      'urutan' => 23],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'VI',  'sub_golongan' => null, 'pangkat' => 'Penata Tingkat I',             'urutan' => 24],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'VII', 'sub_golongan' => null, 'pangkat' => 'Pembina',                      'urutan' => 25],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'VIII','sub_golongan' => null, 'pangkat' => 'Pembina Tingkat I',            'urutan' => 26],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'IX',  'sub_golongan' => null, 'pangkat' => 'Pembina Utama Muda',            'urutan' => 27],
            // PPPK Golongan X–XII
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'X',   'sub_golongan' => null, 'pangkat' => 'Golongan X',                   'urutan' => 28],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'XI',  'sub_golongan' => null, 'pangkat' => 'Golongan XI',                  'urutan' => 29],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'XII', 'sub_golongan' => null, 'pangkat' => 'Golongan XII',                 'urutan' => 30],
            // PPPK Golongan XIII–XVII
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'XIII','sub_golongan' => null, 'pangkat' => 'Golongan XIII',                'urutan' => 31],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'XIV', 'sub_golongan' => null, 'pangkat' => 'Golongan XIV',                'urutan' => 32],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'XV',  'sub_golongan' => null, 'pangkat' => 'Golongan XV',                 'urutan' => 33],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'XVI', 'sub_golongan' => null, 'pangkat' => 'Golongan XVI',                'urutan' => 34],
            ['jenis_asn' => JenisAsn::PPPK, 'golongan' => 'XVII','sub_golongan' => null, 'pangkat' => 'Golongan XVII',               'urutan' => 35],
        ];

        foreach ($rows as $row) {
            RefGolongan::firstOrCreate(
                ['jenis_asn' => $row['jenis_asn'], 'golongan' => $row['golongan'], 'sub_golongan' => $row['sub_golongan']],
                $row
            );
        }
    }
}
