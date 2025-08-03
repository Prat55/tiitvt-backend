<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\User;
use App\Enums\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the center role exists
        $centerRole = Role::firstOrCreate(['name' => RolesEnum::Center->value]);

        // Create some sample centers with predefined data
        $sampleCenters = [
            [
                'name' => 'TechPro Institute',
                'phone' => '+91 98765 43210',
                'address' => '123 Tech Park, Andheri West, Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'email' => 'info@techpro.edu.in',
                'owner_name' => 'Rajesh Kumar',
                'aadhar' => '123456789012',
                'pan' => 'ABCDE1234F',
                'status' => 'active',
            ],
            [
                'name' => 'Digital Learning Academy',
                'phone' => '+91 87654 32109',
                'address' => '456 Innovation Hub, Koramangala, Bangalore',
                'state' => 'Karnataka',
                'country' => 'India',
                'email' => 'contact@digitalacademy.com',
                'owner_name' => 'Priya Sharma',
                'aadhar' => '234567890123',
                'pan' => 'BCDEF2345G',
                'status' => 'active',
            ],
            [
                'name' => 'Future Skills Training Center',
                'phone' => '+91 76543 21098',
                'address' => '789 Skill Street, T Nagar, Chennai',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'email' => 'hello@futureskills.org',
                'owner_name' => 'Arun Patel',
                'aadhar' => '345678901234',
                'pan' => 'CDEFG3456H',
                'status' => 'active',
            ],
            [
                'name' => 'CyberTech Education Hub',
                'phone' => '+91 65432 10987',
                'address' => '321 Cyber Valley, Hitech City, Hyderabad',
                'state' => 'Telangana',
                'country' => 'India',
                'email' => 'info@cybertech.edu',
                'owner_name' => 'Meera Reddy',
                'aadhar' => '456789012345',
                'pan' => 'DEFGH4567I',
                'status' => 'active',
            ],
            [
                'name' => 'Smart Learning Institute',
                'phone' => '+91 54321 09876',
                'address' => '654 Smart Plaza, Satellite, Ahmedabad',
                'state' => 'Gujarat',
                'country' => 'India',
                'email' => 'contact@smartlearning.in',
                'owner_name' => 'Vikram Singh',
                'aadhar' => '567890123456',
                'pan' => 'EFGHI5678J',
                'status' => 'inactive',
            ],
            [
                'name' => 'Global Education Center',
                'phone' => '+91 43210 98765',
                'address' => '987 Global Tower, Salt Lake, Kolkata',
                'state' => 'West Bengal',
                'country' => 'India',
                'email' => 'info@globaledu.org',
                'owner_name' => 'Sneha Das',
                'aadhar' => '678901234567',
                'pan' => 'FGHIJ6789K',
                'status' => 'active',
            ],
            [
                'name' => 'Innovation Training Academy',
                'phone' => '+91 32109 87654',
                'address' => '147 Innovation Road, Gomti Nagar, Lucknow',
                'state' => 'Uttar Pradesh',
                'country' => 'India',
                'email' => 'hello@innovationacademy.com',
                'owner_name' => 'Amit Verma',
                'aadhar' => '789012345678',
                'pan' => 'GHIJK7890L',
                'status' => 'active',
            ],
            [
                'name' => 'Royal Learning Institute',
                'phone' => '+91 21098 76543',
                'address' => '258 Royal Street, C Scheme, Jaipur',
                'state' => 'Rajasthan',
                'country' => 'India',
                'email' => 'contact@royallearning.edu',
                'owner_name' => 'Kavita Sharma',
                'aadhar' => '890123456789',
                'pan' => 'HIJKL8901M',
                'status' => 'active',
            ],
        ];

        foreach ($sampleCenters as $centerData) {
            // Create a user for each center
            $user = User::factory()->create([
                'name' => $centerData['owner_name'],
                'email' => $centerData['email'],
                'email_verified_at' => now(),
            ]);

            // Assign center role to the user
            $user->assignRole($centerRole);

            // Create the center
            Center::create([
                'user_id' => $user->id,
                'name' => $centerData['name'],
                'phone' => $centerData['phone'],
                'address' => $centerData['address'],
                'state' => $centerData['state'],
                'country' => $centerData['country'],
                'email' => $centerData['email'],
                'owner_name' => $centerData['owner_name'],
                'aadhar' => $centerData['aadhar'],
                'pan' => $centerData['pan'],
                'status' => $centerData['status'],
            ]);
        }

        // Create additional random centers using factory
        Center::factory(12)->create();

        $this->command->info('Centers seeded successfully!');
        $this->command->info('Created ' . count($sampleCenters) . ' sample centers with predefined data.');
        $this->command->info('Created 12 additional random centers.');
        $this->command->info('Total centers created: ' . Center::count());
    }
}
