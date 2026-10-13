import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import AddPhotoAlternateRounded from '@mui/icons-material/AddPhotoAlternateRounded';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import SaveOutlined from '@mui/icons-material/SaveOutlined';
import { Box, Button, Card, Checkbox, FormControlLabel, MenuItem, Stack, Switch, TextField, Typography } from '@mui/material';
import { ChangeEvent, FormEvent, useState } from 'react';

type UserOption = { id: number; name: string; email: string };
type Year = { id: number; name: string };
type Section = { id: number; name: string };
type ClassItem = { id: number; name: string; sections: Section[] };
type GuardianUser = { id: number; name: string; email: string };
type Profile = Record<string, any> & { id: number; photo_path?: string; is_public?: boolean };

export default function Form({ type, profile, users = [], years = [], classes = [], guardianUsers = [] }: { type: 'teachers' | 'students'; profile?: Profile; users?: UserOption[]; years?: Year[]; classes?: ClassItem[]; guardianUsers?: GuardianUser[] }) {
    const [photoPreview, setPhotoPreview] = useState(profile?.photo_path ? `/storage/${profile.photo_path}` : '');
    const form = useForm({
        user_id: String(profile?.user_id || users[0]?.id || ''),
        employee_no: profile?.employee_no || '',
        phone: profile?.phone || '',
        designation: profile?.designation || '',
        bio: profile?.bio || '',
        is_public: profile?.is_public ?? false,
        public_sort_order: String(profile?.public_sort_order || 0),
        photo: null as File | null,
        remove_photo: false,
        _method: '',
        student_no: profile?.student_no || '',
        date_of_birth: profile?.date_of_birth ? profile.date_of_birth.slice(0, 10) : '',
        academic_year_id: String(profile?.academic_year_id || years[0]?.id || ''),
        school_class_id: String(profile?.school_class_id || classes[0]?.id || ''),
        school_section_id: String(profile?.school_section_id || ''),
        guardian_user_ids: (profile?.guardians || []).map((guardian: GuardianUser) => String(guardian.id)),
    });
    const sections = classes.find((item) => String(item.id) === form.data.school_class_id)?.sections || [];
    const name = type === 'teachers' ? 'Teacher' : 'Student';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (profile) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/admin/${type}/${profile.id}`, { forceFormData: true });
            return;
        }

        form.post(`/admin/${type}`, { forceFormData: true });
    };

    const choosePhoto = (event: ChangeEvent<HTMLInputElement>) => {
        const photo = event.target.files?.[0] || null;
        form.setData('photo', photo);
        form.setData('remove_photo', false);
        setPhotoPreview(photo ? URL.createObjectURL(photo) : (profile?.photo_path ? `/storage/${profile.photo_path}` : ''));
    };

    const removePhoto = () => {
        form.setData('photo', null);
        form.setData('remove_photo', true);
        setPhotoPreview('');
    };

    return <AdminLayout>
        <Head title={`${profile ? 'Edit' : 'Add'} ${name} profile`} />
        <Button component={Link} href={`/admin/${type}`} color="inherit" startIcon={<ArrowBackRounded />} sx={{ mb: 3 }}>{name}s</Button>
        <Box mb={4}>
            <Typography variant="h3" fontFamily="Newsreader">{profile ? 'Edit' : 'Add'} {name} profile</Typography>
            <Typography color="text.secondary">{type === 'teachers' ? 'Link an existing teacher account to staff details and their public website profile.' : 'Link an existing student account to their current academic placement.'}</Typography>
        </Box>
        <Card component="form" onSubmit={submit} sx={{ p: { xs: 3, md: 5 }, maxWidth: 800 }}>
            <Stack spacing={2.5}>
                <TextField select label={`${name} user account`} value={form.data.user_id} onChange={(event) => form.setData('user_id', event.target.value)} error={!!form.errors.user_id} helperText={form.errors.user_id || 'Create the login account first in Users & roles.'}>
                    {users.map((user) => <MenuItem key={user.id} value={String(user.id)}>{user.name} · {user.email}</MenuItem>)}
                </TextField>
                {type === 'teachers' ? <>
                    <TextField label="Employee number (optional)" value={form.data.employee_no} onChange={(event) => form.setData('employee_no', event.target.value)} error={!!form.errors.employee_no} helperText={form.errors.employee_no} />
                    <TextField label="Designation" placeholder="Assistant Teacher" value={form.data.designation} onChange={(event) => form.setData('designation', event.target.value)} error={!!form.errors.designation} helperText={form.errors.designation} />
                    <TextField label="Mobile number" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} error={!!form.errors.phone} helperText={form.errors.phone} />
                    <Box sx={{ borderTop: '1px solid', borderColor: 'divider', pt: 3, mt: 1 }}>
                        <Typography variant="h6" fontWeight={800}>Public staff profile</Typography>
                        <Typography variant="body2" color="text.secondary" mt={.5}>Only published profiles appear on the public Teachers page.</Typography>
                    </Box>
                    <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} alignItems={{ sm: 'center' }}>
                        {photoPreview ? <Box component="img" src={photoPreview} alt="Teacher profile preview" sx={{ width: 112, height: 112, borderRadius: 3, objectFit: 'cover', bgcolor: 'primary.light' }} /> : <Box sx={{ width: 112, height: 112, borderRadius: 3, bgcolor: 'primary.light', color: 'primary.dark', display: 'grid', placeItems: 'center', fontWeight: 800 }}>PHOTO</Box>}
                        <Stack spacing={1} alignItems="flex-start">
                            <Button component="label" variant="outlined" startIcon={<AddPhotoAlternateRounded />}>Upload photo<input hidden type="file" accept="image/jpeg,image/png,image/webp" onChange={choosePhoto} /></Button>
                            {photoPreview && <Button color="error" size="small" startIcon={<DeleteOutlineRounded />} onClick={removePhoto}>Remove photo</Button>}
                            <Typography variant="caption" color={form.errors.photo ? 'error' : 'text.secondary'}>{form.errors.photo || 'JPG, PNG or WEBP · maximum 5 MB'}</Typography>
                        </Stack>
                    </Stack>
                    <TextField multiline minRows={4} label="Short biography (optional)" placeholder="A brief introduction, qualifications, teaching interests or message for families." value={form.data.bio} onChange={(event) => form.setData('bio', event.target.value)} error={!!form.errors.bio} helperText={form.errors.bio} />
                    <TextField type="number" label="Public display order" value={form.data.public_sort_order} onChange={(event) => form.setData('public_sort_order', event.target.value)} error={!!form.errors.public_sort_order} helperText={form.errors.public_sort_order || 'Lower numbers appear first. Use 0 for the normal order.'} inputProps={{ min: 0, max: 9999 }} />
                    <FormControlLabel control={<Switch checked={form.data.is_public} onChange={(event) => form.setData('is_public', event.target.checked)} />} label="Show this teacher on the public website" />
                </> : <>
                    <TextField label="Student number (optional)" value={form.data.student_no} onChange={(event) => form.setData('student_no', event.target.value)} error={!!form.errors.student_no} helperText={form.errors.student_no} />
                    <TextField type="date" label="Date of birth (optional)" InputLabelProps={{ shrink: true }} value={form.data.date_of_birth} onChange={(event) => form.setData('date_of_birth', event.target.value)} error={!!form.errors.date_of_birth} helperText={form.errors.date_of_birth} />
                    <TextField select label="Academic year" value={form.data.academic_year_id} onChange={(event) => form.setData('academic_year_id', event.target.value)} error={!!form.errors.academic_year_id} helperText={form.errors.academic_year_id}>{years.map((year) => <MenuItem key={year.id} value={String(year.id)}>{year.name}</MenuItem>)}</TextField>
                    <TextField select label="Class" value={form.data.school_class_id} onChange={(event) => { form.setData('school_class_id', event.target.value); form.setData('school_section_id', ''); }} error={!!form.errors.school_class_id} helperText={form.errors.school_class_id}>{classes.map((item) => <MenuItem key={item.id} value={String(item.id)}>{item.name}</MenuItem>)}</TextField>
                    <TextField select label="Section" value={form.data.school_section_id} onChange={(event) => form.setData('school_section_id', event.target.value)} error={!!form.errors.school_section_id} helperText={form.errors.school_section_id} disabled={!sections.length}>{sections.map((section) => <MenuItem key={section.id} value={String(section.id)}>{section.name}</MenuItem>)}</TextField>
                    <Box sx={{ borderTop: '1px solid', borderColor: 'divider', pt: 3 }}><Typography variant="h6" fontWeight={800}>Linked guardian accounts</Typography><Typography variant="body2" color="text.secondary" mt={.5} mb={1.5}>Selected guardians can view this student’s published report cards in their portal.</Typography>{guardianUsers.length ? <Stack spacing={.25}>{guardianUsers.map((guardian) => <FormControlLabel key={guardian.id} control={<Checkbox checked={form.data.guardian_user_ids.includes(String(guardian.id))} onChange={(event) => form.setData('guardian_user_ids', event.target.checked ? [...form.data.guardian_user_ids, String(guardian.id)] : form.data.guardian_user_ids.filter((id: string) => id !== String(guardian.id)))} />} label={`${guardian.name} · ${guardian.email}`} />)}</Stack> : <Typography variant="body2" color="text.secondary">Create a user with the Guardian role in Users & roles to link them here.</Typography>}<Typography variant="caption" color="error">{form.errors.guardian_user_ids}</Typography></Box>
                </>}
                <Stack direction="row" spacing={2}><Button type="submit" variant="contained" startIcon={<SaveOutlined />} disabled={form.processing}>{form.processing ? 'Saving…' : 'Save profile'}</Button><Button component={Link} href={`/admin/${type}`} color="inherit">Cancel</Button></Stack>
            </Stack>
        </Card>
    </AdminLayout>;
}
