<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FeeInvoice;
use App\Models\FeeType;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolSection;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_invoices_record_payments_and_share_the_correct_balance_with_family_portals(): void
    {
        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $studentRole = Role::create(['name' => 'Student', 'slug' => 'student']);
        $guardianRole = Role::create(['name' => 'Guardian', 'slug' => 'guardian']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $studentUser = User::factory()->create(['role_id' => $studentRole->id, 'name' => 'Nafisa Islam']);
        $guardian = User::factory()->create(['role_id' => $guardianRole->id]);
        $year = AcademicYear::create(['name' => '2027', 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'is_current' => true]);
        $class = SchoolClass::create(['name' => 'Class VI', 'code' => 'VI']);
        $section = SchoolSection::create(['school_class_id' => $class->id, 'name' => 'A']);
        $student = StudentProfile::create(['user_id' => $studentUser->id, 'student_no' => 'ST-1001', 'academic_year_id' => $year->id, 'school_class_id' => $class->id, 'school_section_id' => $section->id]);
        $student->guardians()->attach($guardian->id);
        $tuition = FeeType::create(['name' => 'Monthly tuition', 'frequency' => 'monthly', 'default_amount' => 1000, 'is_active' => true]);
        $transport = FeeType::create(['name' => 'Transport', 'frequency' => 'monthly', 'default_amount' => 500, 'is_active' => true]);

        $this->actingAs($admin)->post('/admin/fee-invoices/generate', [
            'academic_year_id' => $year->id,
            'school_class_id' => $class->id,
            'school_section_id' => $section->id,
            'title' => 'January school fees',
            'billing_period' => '2027-01',
            'issued_on' => '2027-01-01',
            'due_date' => '2027-01-10',
            'fee_type_ids' => [$tuition->id, $transport->id],
        ])->assertRedirect('/admin/fee-invoices')->assertSessionHasNoErrors();

        $invoice = FeeInvoice::firstOrFail();
        $this->assertDatabaseHas('fee_invoices', ['id' => $invoice->id, 'student_profile_id' => $student->id, 'total_amount' => 1500, 'paid_amount' => 0, 'balance_amount' => 1500, 'status' => 'unpaid']);
        $this->assertDatabaseCount('fee_invoice_items', 2);

        $this->actingAs($admin)->post("/admin/fee-invoices/{$invoice->id}/payments", ['amount' => 600, 'payment_date' => '2027-01-05', 'method' => 'cash', 'reference' => 'CASH-1'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fee_invoices', ['id' => $invoice->id, 'paid_amount' => 600, 'balance_amount' => 900, 'status' => 'partial']);
        $this->assertDatabaseCount('fee_payments', 1);

        $this->actingAs($admin)->post("/admin/fee-invoices/{$invoice->id}/payments", ['amount' => 900, 'payment_date' => '2027-01-06', 'method' => 'mobile_banking'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fee_invoices', ['id' => $invoice->id, 'paid_amount' => 1500, 'balance_amount' => 0, 'status' => 'paid']);

        $this->actingAs($studentUser)->get('/student/fees')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/Fees')->has('invoices', 1)->where('invoices.0.id', $invoice->id));
        $this->actingAs($guardian)->get('/guardian/fees')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Portal/Fees')->has('students', 1)->has('invoices', 1)->where('invoices.0.id', $invoice->id));
    }
}
