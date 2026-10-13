import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import SaveOutlined from '@mui/icons-material/SaveOutlined';
import { Box, Button, Card, MenuItem, Stack, TextField, Typography } from '@mui/material';
import { FormEvent } from 'react';

type Year = { id: number; name: string };
type Exam = { id: number; academic_year_id: number; title: string; type: string; starts_on?: string; ends_on?: string };
const label = (value: string) => value.replace('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function Form({ examination, years, types }: { examination?: Exam; years: Year[]; types: string[] }) {
    const form = useForm({ academic_year_id: String(examination?.academic_year_id || years[0]?.id || ''), title: examination?.title || '', type: examination?.type || 'monthly', starts_on: examination?.starts_on?.slice(0, 10) || '', ends_on: examination?.ends_on?.slice(0, 10) || '' });
    const submit = (event: FormEvent) => { event.preventDefault(); examination ? form.put(`/admin/examinations/${examination.id}`) : form.post('/admin/examinations'); };
    return <AdminLayout><Head title={examination ? `Edit ${examination.title}` : 'Create examination'} />
        <Button component={Link} href="/admin/examinations" color="inherit" startIcon={<ArrowBackRounded />} sx={{ mb: 3 }}>Examinations</Button><Box mb={4}><Typography variant="h3" fontFamily="Newsreader">{examination ? 'Edit examination' : 'Create examination'}</Typography><Typography color="text.secondary">Create the exam first. You will then add a subject schedule for every class and section.</Typography></Box>
        <Card component="form" onSubmit={submit} sx={{ p: { xs: 3, md: 5 }, maxWidth: 800 }}><Stack spacing={2.5}><TextField select label="Academic year" value={form.data.academic_year_id} onChange={(event) => form.setData('academic_year_id', event.target.value)} error={!!form.errors.academic_year_id} helperText={form.errors.academic_year_id}>{years.map((year) => <MenuItem key={year.id} value={String(year.id)}>{year.name}</MenuItem>)}</TextField><TextField label="Examination title" placeholder="Half-Yearly Examination 2027" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} error={!!form.errors.title} helperText={form.errors.title} /><TextField select label="Exam type" value={form.data.type} onChange={(event) => form.setData('type', event.target.value)} error={!!form.errors.type} helperText={form.errors.type}>{types.map((type) => <MenuItem key={type} value={type}>{label(type)}</MenuItem>)}</TextField><Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}><TextField type="date" label="Start date (optional)" InputLabelProps={{ shrink: true }} fullWidth value={form.data.starts_on} onChange={(event) => form.setData('starts_on', event.target.value)} error={!!form.errors.starts_on} helperText={form.errors.starts_on} /><TextField type="date" label="End date (optional)" InputLabelProps={{ shrink: true }} fullWidth value={form.data.ends_on} onChange={(event) => form.setData('ends_on', event.target.value)} error={!!form.errors.ends_on} helperText={form.errors.ends_on} /></Stack><Stack direction="row" spacing={2}><Button type="submit" variant="contained" startIcon={<SaveOutlined />} disabled={form.processing}>{form.processing ? 'Saving…' : 'Save examination'}</Button><Button component={Link} href="/admin/examinations" color="inherit">Cancel</Button></Stack></Stack></Card>
    </AdminLayout>;
}
