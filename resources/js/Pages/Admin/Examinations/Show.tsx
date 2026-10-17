import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AddRounded from '@mui/icons-material/AddRounded';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import EventAvailableRounded from '@mui/icons-material/EventAvailableRounded';
import PublishRounded from '@mui/icons-material/PublishRounded';
import { Alert, Box, Button, Card, Chip, Divider, Stack, Typography } from '@mui/material';
import { PageProps } from '@/types';

type Schedule = { id: number; exam_date: string; starts_at?: string; duration_minutes?: number; room?: string | null; full_marks: string | number; pass_marks: string | number; marks_count: number; student_count: number; subject: { name: string; code: string }; school_class: { name: string }; school_section: { name: string } };
type Rule = { id: number; min_percentage: string; grade: string; grade_point: string };
type Exam = { id: number; title: string; type: string; status: string; starts_on?: string; ends_on?: string; routine_instructions?: string | null; routine_published_at?: string | null; academic_year: { name: string } };
const label = (value: string) => value.replace('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const date = (value: string) => new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' });

export default function Show({ examination, schedules, gradeRules }: { examination: Exam; schedules: Schedule[]; gradeRules: Rule[] }) {
    const { flash, errors } = usePage<PageProps<{ errors: Record<string, string> }>>().props;
    const updateStatus = (action: 'review' | 'publish' | 'unpublish') => router.patch(`/admin/examinations/${examination.id}/status`, { action }, { preserveScroll: true });
    const updateRoutine = (action: 'publish' | 'unpublish') => router.patch(`/admin/examinations/${examination.id}/routine-status`, { action }, { preserveScroll: true });
    const routinePublished = !!examination.routine_published_at;
    const canEdit = examination.status !== 'published' && !routinePublished;
    const statusColor = examination.status === 'published' ? 'success' : examination.status === 'reviewing' ? 'warning' : 'default';
    const error = errors.routine || errors.results || errors.examination;

    return <AdminLayout><Head title={examination.title} />
        <Button component={Link} href="/admin/examinations" color="inherit" startIcon={<ArrowBackRounded />} sx={{ mb: 3 }}>Examinations</Button>
        <Stack direction={{ xs: 'column', lg: 'row' }} justifyContent="space-between" spacing={3} mb={4}>
            <Box><Typography variant="overline" color="secondary.dark" fontWeight={800}>{label(examination.type)} · {examination.academic_year.name}</Typography><Typography variant="h2" fontFamily="Newsreader">{examination.title}</Typography><Typography color="text.secondary" mt={1}>{schedules.length} subject schedule{schedules.length === 1 ? '' : 's'} · Routine and results are published independently.</Typography></Box>
            <Stack spacing={1.25} alignSelf={{ lg: 'center' }} minWidth={{ lg: 330 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="flex-end"><Chip label={`Routine: ${routinePublished ? 'Published' : 'Draft'}`} color={routinePublished ? 'success' : 'default'} />{routinePublished ? <Button size="small" variant="outlined" color="warning" onClick={() => updateRoutine('unpublish')}>Unpublish routine</Button> : <Button size="small" variant="contained" startIcon={<EventAvailableRounded />} onClick={() => updateRoutine('publish')}>Publish routine</Button>}</Stack>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="flex-end"><Chip label={`Results: ${label(examination.status)}`} color={statusColor as any} />{canEdit && <Button component={Link} href={`/admin/examinations/${examination.id}/edit`} size="small" variant="outlined" startIcon={<EditRounded />}>Edit exam</Button>}{examination.status === 'draft' && <Button size="small" variant="outlined" onClick={() => updateStatus('review')}>Ready for review</Button>}{examination.status === 'reviewing' && <Button size="small" variant="contained" color="success" startIcon={<PublishRounded />} onClick={() => updateStatus('publish')}>Publish results</Button>}{examination.status === 'published' && <Button size="small" variant="outlined" color="warning" onClick={() => updateStatus('unpublish')}>Unpublish results</Button>}</Stack>
            </Stack>
        </Stack>
        {flash.success && <Alert severity="success" sx={{ mb: 3 }}>{flash.success}</Alert>}{flash.error && <Alert severity="error" sx={{ mb: 3 }}>{flash.error}</Alert>}{error && <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>}
        {examination.routine_instructions && <Alert severity="info" sx={{ mb: 3 }}><b>Routine instructions:</b> {examination.routine_instructions}</Alert>}
        <Card sx={{ p: { xs: 2, md: 3 } }}>
            <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" spacing={2} mb={3}><Box><Typography variant="h5" fontWeight={800}>Subject schedules</Typography><Typography color="text.secondary" variant="body2">These dates, times, and rooms appear in the published student and guardian routine.</Typography></Box>{canEdit && <Button component={Link} href={`/admin/examinations/${examination.id}/schedules/create`} variant="contained" startIcon={<AddRounded />}>Add subject</Button>}</Stack>
            {routinePublished && <Alert severity="success" sx={{ mb: 2 }}>This routine is live. Unpublish it before changing any schedule.</Alert>}
            {schedules.length ? <Stack divider={<Divider />}>{schedules.map((schedule) => <Stack key={schedule.id} direction={{ xs: 'column', md: 'row' }} alignItems={{ md: 'center' }} justifyContent="space-between" spacing={2} py={2.25}><Box><Typography fontWeight={800}>{schedule.subject.name} <Typography component="span" color="text.secondary">({schedule.subject.code})</Typography></Typography><Typography color="text.secondary" variant="body2">{schedule.school_class.name} · Section {schedule.school_section.name} · {date(schedule.exam_date)} · {schedule.starts_at ? schedule.starts_at.slice(0, 5) : 'Time not set'}</Typography><Typography variant="caption" color="text.secondary">Full marks: {schedule.full_marks} · Pass marks: {schedule.pass_marks}{schedule.duration_minutes ? ` · ${schedule.duration_minutes} minutes` : ''}{schedule.room ? ` · ${schedule.room}` : ''}</Typography></Box><Stack direction="row" spacing={1.5} alignItems="center"><Chip label={`${schedule.marks_count}/${schedule.student_count} marked`} color={schedule.marks_count === schedule.student_count && schedule.student_count > 0 ? 'success' : 'warning'} size="small" />{canEdit && <Button component={Link} href={`/admin/examinations/${examination.id}/schedules/${schedule.id}/edit`} size="small">Edit</Button>}</Stack></Stack>)}</Stack> : <Box textAlign="center" py={7}><Typography variant="h6">No subject schedules yet</Typography><Typography color="text.secondary" mt={1}>Add each subject, class, and section before publishing the routine.</Typography></Box>}
        </Card>
        <Card sx={{ p: { xs: 2.5, md: 3 }, mt: 3, maxWidth: 760 }}><Typography variant="h5" fontWeight={800}>Automatic grading scale</Typography><Typography color="text.secondary" variant="body2" mt={.5} mb={2}>The final grade is calculated from percentage. Any subject below its pass mark, or marked absent, makes the overall result F with GPA 0.</Typography><Stack direction="row" flexWrap="wrap" gap={1}>{gradeRules.map((rule) => <Chip key={rule.id} label={`${rule.min_percentage}%+ = ${rule.grade} (${rule.grade_point})`} variant="outlined" />)}</Stack></Card>
    </AdminLayout>;
}
