import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import AddRounded from '@mui/icons-material/AddRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import OpenInNewRounded from '@mui/icons-material/OpenInNewRounded';
import { Box, Button, Card, Chip, FormControl, InputLabel, MenuItem, Pagination, Select, Stack, Typography } from '@mui/material';
import { useState } from 'react';

type Exam = { id: number; title: string; type: string; status: string; routine_published_at?: string | null; starts_on?: string; ends_on?: string; schedules_count: number; academic_year: { name: string } };
type Props = { status?: string; statusOptions: string[]; examinations: { data: Exam[]; current_page: number; last_page: number } };
const label = (value: string) => value.replace('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const color = (status: string) => status === 'published' ? 'success' : status === 'reviewing' ? 'warning' : 'default';

export default function Index({ status, statusOptions, examinations }: Props) {
    const [selectedStatus, setSelectedStatus] = useState(status || '');
    const filter = (next: string, page = 1) => router.get('/admin/examinations', { status: next || undefined, page }, { preserveState: true, replace: true });

    return <AdminLayout><Head title="Examinations" />
        <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" spacing={2} mb={4}><Box><Typography variant="h3" fontFamily="Newsreader">Examinations</Typography><Typography color="text.secondary">Set up exam schedules, review marks, and publish report cards only when every subject is ready.</Typography></Box><Button component={Link} href="/admin/examinations/create" variant="contained" startIcon={<AddRounded />}>Create examination</Button></Stack>
        <Card sx={{ p: { xs: 2, md: 3 } }}><FormControl sx={{ minWidth: 210, mb: 3 }}><InputLabel id="exam-status-label">Status</InputLabel><Select labelId="exam-status-label" label="Status" value={selectedStatus} onChange={(event) => { setSelectedStatus(event.target.value); filter(event.target.value); }}><MenuItem value="">All statuses</MenuItem>{statusOptions.map((option) => <MenuItem key={option} value={option}>{label(option)}</MenuItem>)}</Select></FormControl>
            {examinations.data.length ? <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 2.5 }}>{examinations.data.map((exam) => <Card key={exam.id} variant="outlined" sx={{ p: 3, boxShadow: 'none' }}><Stack direction="row" justifyContent="space-between" spacing={2}><Box minWidth={0}><Stack direction="row" spacing={1} flexWrap="wrap" alignItems="center"><Typography variant="h5" fontFamily="Newsreader">{exam.title}</Typography><Chip size="small" label={`Results: ${label(exam.status)}`} color={color(exam.status) as any} /><Chip size="small" label={`Routine: ${exam.routine_published_at ? 'Published' : 'Draft'}`} color={exam.routine_published_at ? 'success' : 'default'} variant="outlined" /></Stack><Typography color="text.secondary" variant="body2" mt={1}>{label(exam.type)} · {exam.academic_year.name}</Typography><Typography color="text.secondary" variant="caption">{exam.starts_on ? new Date(exam.starts_on).toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Dates to be confirmed'} · {exam.schedules_count} subject schedule{exam.schedules_count === 1 ? '' : 's'}</Typography></Box><Stack direction="row"><Button component={Link} href={`/admin/examinations/${exam.id}`} size="small" endIcon={<OpenInNewRounded />}>Review</Button></Stack></Stack><Stack direction="row" justifyContent="flex-end" mt={2}>{exam.status !== 'published' && !exam.routine_published_at && <Button component={Link} href={`/admin/examinations/${exam.id}/edit`} size="small" startIcon={<EditRounded />}>Edit</Button>}{exam.status !== 'published' && !exam.routine_published_at && <Button color="error" size="small" startIcon={<DeleteOutlineRounded />} onClick={() => confirm(`Delete “${exam.title}” and all marks?`) && router.delete(`/admin/examinations/${exam.id}`)}>Delete</Button>}</Stack></Card>)}</Box> : <Box textAlign="center" py={9}><Typography variant="h6">No examinations found</Typography><Typography color="text.secondary" mt={1}>Create an examination, then add subject schedules for each class and section.</Typography></Box>}
            {examinations.last_page > 1 && <Pagination count={examinations.last_page} page={examinations.current_page} onChange={(_, page) => filter(selectedStatus, page)} sx={{ mt: 3 }} />}
        </Card>
    </AdminLayout>;
}
