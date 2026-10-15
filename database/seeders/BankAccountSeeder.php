<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Paymode;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default Cash account (no bank required)
        BankAccount::firstOrCreate(
            ['account_name' => 'Cash on Hand', 'account_type' => 'cash'],
            [
                'account_number' => null,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_default' => true,
                'status' => true,
            ]
        );

        // Create Petty Cash account
        BankAccount::firstOrCreate(
            ['account_name' => 'Petty Cash', 'account_type' => 'cash'],
            [
                'account_number' => null,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_default' => false,
                'status' => true,
            ]
        );

        // Create default paymodes if they don't exist
        $paymodes = ['Cash', 'Cheque', 'Bank Transfer', 'Credit Card', 'Online Payment'];
        foreach ($paymodes as $mode) {
            Paymode::firstOrCreate(
                ['name' => $mode],
                ['status' => true]
            );
        }

        // Create example bank accounts if banks exist
        $banks = Bank::where('status', true)->get();
        foreach ($banks as $bank) {
            BankAccount::firstOrCreate(
                ['bank_id' => $bank->id, 'account_name' => $bank->name . ' - Current Account'],
                [
                    'account_type' => 'bank',
                    'account_number' => 'XXXX-' . rand(1000, 9999),
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'status' => true,
                ]
            );
        }
    }
}
