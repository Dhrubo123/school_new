<?php

namespace Database\Seeders;

use App\Models\AdmissionApplication;
use App\Models\Notice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolEvent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect(['manage-content', 'manage-notices', 'manage-events', 'manage-admissions', 'manage-users', 'manage-settings'])
            ->mapWithKeys(fn ($slug) => [$slug => Permission::create(['name' => str($slug)->replace('-', ' ')->title(), 'slug' => $slug])]);

        $roles = collect([
            'super-admin' => 'Super Admin', 'admin' => 'Admin', 'teacher' => 'Teacher', 'guardian' => 'Guardian', 'student' => 'Student',
        ])->mapWithKeys(fn ($name, $slug) => [$slug => Role::create(['name' => $name, 'slug' => $slug])]);
        $roles['super-admin']->permissions()->sync($permissions->pluck('id'));
        $roles['admin']->permissions()->sync($permissions->except('manage-users')->pluck('id'));

        $accounts = [
            ['super-admin', 'Amina Rahman', 'superadmin@shobujridge.edu.bd'],
            ['admin', 'Farhan Kabir', 'admin@shobujridge.edu.bd'],
            ['teacher', 'Nusrat Jahan', 'teacher@shobujridge.edu.bd'],
            ['guardian', 'Mahmud Hasan', 'guardian@shobujridge.edu.bd'],
            ['student', 'Tasnim Ahmed', 'student@shobujridge.edu.bd'],
        ];
        foreach ($accounts as [$role, $name, $email]) {
            User::create(['role_id' => $roles[$role]->id, 'name' => $name, 'email' => $email, 'email_verified_at' => now(), 'password' => Hash::make('School@123')]);
        }
        $admin = User::where('email', 'admin@shobujridge.edu.bd')->firstOrFail();
        Notice::factory(7)->for($admin, 'author')->create();
        Notice::factory()->draft()->for($admin, 'author')->create(['title' => 'Internal draft examination note']);
        SchoolEvent::factory(6)->for($admin, 'author')->create();
        SchoolEvent::factory()->draft()->for($admin, 'author')->create(['title' => 'Draft alumni gathering']);
        AdmissionApplication::create(['application_no' => 'SR-261012-A1B2C', 'student_name' => 'Nafisa Islam', 'guardian_name' => 'Rashedul Islam', 'email' => 'rashedul@example.com', 'phone' => '01711000000', 'applying_for_class' => 'Class VI']);
        AdmissionApplication::create(['application_no' => 'SR-261012-D3E4F', 'student_name' => 'Adnan Karim', 'guardian_name' => 'Samira Karim', 'phone' => '01812000000', 'applying_for_class' => 'Class III']);

        DB::table('school_settings')->insert([
            ['key' => 'school_name', 'value' => 'Shobuj Ridge School & College', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'eiin', 'value' => '134782', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'phone', 'value' => '+880 2 5500 2244', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
