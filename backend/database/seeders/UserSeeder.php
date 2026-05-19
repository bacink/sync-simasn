<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create OPDs
        $bkpsdm = Opd::updateOrCreate(
            ['kode' => 'BKPSDM'],
            ['nama' => 'BKPSDM Kota Bandung']
        );

        $dikdis = Opd::updateOrCreate(
            ['kode' => 'DIKDIS'],
            ['nama' => 'Dinas Pendidikan']
        );

        $dinkes = Opd::updateOrCreate(
            ['kode' => 'DINKES'],
            ['nama' => 'Dinas Kesehatan']
        );

        // Create users
        User::updateOrCreate(
            ['email' => 'admin@bpsdm.id'],
            [
                'name' => 'Admin BPSDM',
                'password' => Hash::make('password'),
                'opd_id' => $bkpsdm->id,
            ]
        )->syncRoles(['admin']);

        User::updateOrCreate(
            ['email' => 'operator@bpsdm.id'],
            [
                'name' => 'Operator DIKDIS',
                'password' => Hash::make('password'),
                'opd_id' => $dikdis->id,
            ]
        )->syncRoles(['operator']);

        User::updateOrCreate(
            ['email' => 'verifikator@bpsdm.id'],
            [
                'name' => 'Verifikator BPSDM',
                'password' => Hash::make('password'),
                'opd_id' => $bkpsdm->id,
            ]
        )->syncRoles(['verifikator']);
    }
}