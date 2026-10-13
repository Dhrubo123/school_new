<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $sections = [
            ['home', 'hero', 'Curious minds.\nCourageous hearts.', 'A joyful, rigorous education where every learner is known, Bangladeshi identity is celebrated, and tomorrow’s possibilities begin today.', 0],
            ['home', 'principal-message', '“Education is not only preparation for life—it is learning to serve life with wisdom.”', '— Dr. Samira Hossain, Principal', 10],
            ['about', 'hero', 'A school shaped by hope and high expectations.', 'Since 2002, Shobuj Ridge has helped young people grow into thoughtful learners and responsible citizens.', 0],
            ['about', 'purpose', 'Our purpose', 'We nurture curious, capable and compassionate young people who contribute confidently to Bangladesh and the world.', 10],
            ['about', 'values', 'Our values', 'Integrity, empathy, courage, curiosity and service guide how we learn and how we treat one another.', 20],
            ['about', 'leadership', 'Leadership & safeguarding', 'A qualified leadership team, trained counsellors and clear safeguarding practice keep student wellbeing at the centre.', 30],
            ['academics', 'hero', 'Depth of understanding, not a race through content.', 'Our curriculum combines National Curriculum rigour with strong English, technology, arts and co-curricular learning.', 0],
            ['academics', 'primary', 'Primary · Nursery–V', 'A warm, language-rich programme builds confident readers, mathematical thinkers and joyful investigators.', 10],
            ['academics', 'secondary', 'Secondary · VI–X', 'Subject specialists, laboratories, projects and feedback create a strong pathway towards SSC.', 20],
            ['academics', 'college', 'College · XI–XII', 'Science, Business Studies and Humanities streams are supported by mentoring and university guidance.', 30],
            ['admissions', 'hero', 'A thoughtful beginning for every family.', 'We welcome students who are ready to be curious, kind and engaged. Submit an enquiry and our team will guide you.', 0],
            ['admissions', 'enquire', '1 · Enquire', 'Tell us about your child and the class you are considering.', 10],
            ['admissions', 'visit', '2 · Visit & assess', 'Meet our team, tour the campus and complete an age-appropriate interaction.', 20],
            ['admissions', 'enrol', '3 · Offer & enrol', 'Successful families receive an offer with a clear enrolment checklist.', 30],
            ['contact', 'hero', 'Come and experience our community.', 'Our admissions and school office teams are ready to help Sunday through Thursday.', 0],
            ['contact', 'campus', 'Campus', 'Road 12, Sector 10, Uttara, Dhaka 1230', 10],
            ['contact', 'talk', 'Talk to us', '+880 2 5500 2244 · hello@shobujridge.edu.bd', 20],
            ['contact', 'office-hours', 'Office hours', 'Sunday–Thursday · 8:00 am–4:00 pm (Bangladesh time)', 30],
        ];

        DB::table('content_sections')->upsert(collect($sections)->map(fn (array $section) => [
            'page_slug' => $section[0], 'section_key' => $section[1], 'title' => $section[2], 'body' => $section[3], 'is_published' => true, 'sort_order' => $section[4], 'created_at' => $now, 'updated_at' => $now,
        ])->all(), ['page_slug', 'section_key'], []);
    }

    public function down(): void
    {
        DB::table('content_sections')->whereIn('page_slug', ['home', 'about', 'academics', 'admissions', 'contact'])->delete();
    }
};
