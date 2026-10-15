<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Smalot\PdfParser\Parser;
use App\Models\Associate;
use App\Models\AssociateGroup;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $file = public_path('storage/extracted_customers.txt');

        if (!file_exists($file)) {
            $this->command->error("File not found: $file");
            return;
        }

        $content = file_get_contents($file);
        // Normalize spaces and newlines
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Split by ID pattern (AS followed by 4 digits)
        // We use preg_split with captured delimiter to keep the ID
        $parts = preg_split('/(AS\d{4})/', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $data = [];
        $currentId = null;

        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/^AS\d{4}$/', $part)) {
                $currentId = $part;
            } elseif ($currentId) {
                // This part is the body for the currentId
                $data[] = $this->parseRecord($currentId, $part);
                $currentId = null;
            }
        }

        $this->saveToDatabase($data);
    }

    private function parseRecord($id, $text)
    {
        // Extract Phone (8 digits, maybe with +65)
        $phone = null;
        if (preg_match('/(\+?65\s?)?[689]\d{7}/', $text, $matches)) {
            $phone = str_replace(' ', '', $matches[0]);
            $text = trim(str_replace($matches[0], '', $text));
        }

        // Extract Email
        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            $email = $matches[0];
            $text = trim(str_replace($matches[0], '', $text));
        }

        // Extract Website (simple check)
        $website = null;
        if (preg_match('/(www\.[a-z0-9.-]+|https?:\/\/[a-z0-9.-]+)/i', $text, $matches)) {
            $website = $matches[0];
            $text = trim(str_replace($matches[0], '', $text));
        }

        // Name and Address split
        // Heuristic: Look for company suffix. If found, split after it.
        // If multiple suffixes, take the first one or the one earliest in string.
        $suffixes = [
            'PTE.LTD', 'PTE LTD', 'PTE. LTD', 'PVT LTD', 'SDN BHD',
            'TRADING', 'MART', 'SUPERMARKET', 'SUPERMART', 'STORE', 'STORES',
            'ENTERPRISE', 'CONSTRUCTION', 'ENGINEERING', 'GROCERIES', 'FLOWER SHOP',
            'MINIMART', 'MINI MART', 'SUPPLIES', 'RESTAURANT', 'CATERING',
            'LTD', 'PLC', 'LLC', 'SOCIETY', 'HOSPITAL', 'CLINIC'
        ];

        $name = $text;
        $address = '';

        foreach ($suffixes as $suffix) {
            // Case insensitive search
            $pos = stripos($text, $suffix);
            if ($pos !== false) {
                // Found a suffix. 
                // The split point should be end of suffix.
                $splitPos = $pos + strlen($suffix);
                
                // Check if there are other chars like '.' or ' ' after suffix that usually belong to name
                // e.g. "PTE. LTD."
                if (isset($text[$splitPos]) && $text[$splitPos] === '.') {
                    $splitPos++;
                }

                $name = trim(substr($text, 0, $splitPos));
                $address = trim(substr($text, $splitPos));
                
                // Remove leading punctuation from address often left over from copy paste like ","
                $address = ltrim($address, ',. -');
                
                // If address is empty, maybe the split was too aggressive or it's just name?
                // But we prefer a split.
                break; 
            }
        }
        
        // Fallback: If no suffix found, look for "BLK" or digit at start of address
        if (empty($address) && ($name === $text)) {
             // Try to split by "BLK"
             $blkPos = stripos($text, 'BLK ');
             if ($blkPos !== false && $blkPos > 5) { // Ensure BLK is not start of name
                 $name = trim(substr($text, 0, $blkPos));
                 $address = trim(substr($text, $blkPos));
             } else {
                 // Try to split by first number that looks like unit or block?
                 // Might be risky. Let's just assume first 4 words are name?
                 // No, let's just put everything in name if we can't tell, user can fix.
                 // Or put everything in address? No.
             }
        }

        return [
            'id' => $id,
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'website' => $website
        ];
    }

    private function saveToDatabase($data)
    {
        $currency = Currency::first();
        $currencyId = $currency ? $currency->id : 1;
        
        $subscriberId = DB::table('subscribers')->value('id') ?? 1;
        $group = AssociateGroup::first();
        $groupId = $group ? $group->id : null;

        $count = 0;
        foreach ($data as $row) {
            try {
                // Skip page numbers or headers captured by mistake
                if (stripos($row['name'], 'Page No') !== false) continue;
                if (stripos($row['name'], 'Associate Report') !== false) continue;

                Associate::updateOrCreate(
                    [
                        'subscriber_id' => $subscriberId,
                        'code' => $row['id']
                    ],
                    [
                        'username' => $row['id'],
                        'name' => substr($row['name'], 0, 255),
                        'address_line_1' => !empty($row['address']) ? substr($row['address'], 0, 255) : 'Unknown',
                        'website' => $row['website'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'type' => 'Customer',
                        'status' => true,
                        'currency_id' => $currencyId,
                        'associate_group_id' => $groupId,
                        'password' => Hash::make('password'),
                        'credit_limit' => 0,
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $this->command->error("Error importing {$row['id']}: " . $e->getMessage());
            }
        }
        $this->command->info("Successfully imported $count customers from text file.");
    }

}
