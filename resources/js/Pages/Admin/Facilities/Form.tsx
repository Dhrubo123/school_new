import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import AddPhotoAlternateRounded from '@mui/icons-material/AddPhotoAlternateRounded';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import SaveOutlined from '@mui/icons-material/SaveOutlined';
import { Box, Button, Card, Checkbox, FormControlLabel, Stack, TextField, Typography } from '@mui/material';
import { ChangeEvent, FormEvent, useState } from 'react';

type Facility = { id: number; title: string; description: string; photo_path?: string; is_published: boolean; sort_order: number };

export default function Form({ facility }: { facility?: Facility }) {
    const [photoPreview, setPhotoPreview] = useState(facility?.photo_path ? `/storage/${facility.photo_path}` : '');
    const form = useForm({
        title: facility?.title || '',
        description: facility?.description || '',
        is_published: facility?.is_published || false,
        sort_order: String(facility?.sort_order || 0),
        photo: null as File | null,
        remove_photo: false,
        _method: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (facility) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/admin/facilities/${facility.id}`, { forceFormData: true });
            return;
        }

        form.post('/admin/facilities', { forceFormData: true });
    };

    const choosePhoto = (event: ChangeEvent<HTMLInputElement>) => {
        const photo = event.target.files?.[0] || null;
        form.setData('photo', photo);
        form.setData('remove_photo', false);
        setPhotoPreview(photo ? URL.createObjectURL(photo) : (facility?.photo_path ? `/storage/${facility.photo_path}` : ''));
    };

    const removePhoto = () => {
        form.setData('photo', null);
        form.setData('remove_photo', true);
        setPhotoPreview('');
    };

    return <AdminLayout>
        <Head title={facility ? `Edit ${facility.title}` : 'Add facility'} />
        <Button component={Link} href="/admin/facilities" color="inherit" startIcon={<ArrowBackRounded />} sx={{ mb: 3 }}>Facilities</Button>
        <Box mb={4}><Typography variant="h3" fontFamily="Newsreader">{facility ? 'Edit facility' : 'Add facility'}</Typography><Typography color="text.secondary">Add a public-facing description and photograph for a school space or service.</Typography></Box>
        <Card component="form" onSubmit={submit} sx={{ p: { xs: 3, md: 5 }, maxWidth: 850 }}>
            <Stack spacing={2.5}>
                <TextField autoFocus label="Facility title" placeholder="Science laboratory" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} error={!!form.errors.title} helperText={form.errors.title} />
                <TextField multiline minRows={5} label="Description" placeholder="Explain what students can do here, the equipment available, and how the facility supports learning." value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} error={!!form.errors.description} helperText={form.errors.description} />
                <Box sx={{ borderTop: '1px solid', borderColor: 'divider', pt: 3 }}><Typography fontWeight={800}>Feature photograph</Typography><Typography variant="body2" color="text.secondary" mt={.5}>A landscape photograph works best. This is optional, but helps the public page feel more alive.</Typography></Box>
                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} alignItems={{ sm: 'center' }}>
                    {photoPreview ? <Box component="img" src={photoPreview} alt="Facility preview" sx={{ width: 180, height: 112, borderRadius: 2.5, objectFit: 'cover', bgcolor: 'primary.light' }} /> : <Box sx={{ width: 180, height: 112, borderRadius: 2.5, bgcolor: 'primary.light', color: 'primary.dark', display: 'grid', placeItems: 'center', fontWeight: 800 }}>FACILITY PHOTO</Box>}
                    <Stack spacing={1} alignItems="flex-start"><Button component="label" variant="outlined" startIcon={<AddPhotoAlternateRounded />}>Upload photo<input hidden type="file" accept="image/jpeg,image/png,image/webp" onChange={choosePhoto} /></Button>{photoPreview && <Button type="button" color="error" size="small" startIcon={<DeleteOutlineRounded />} onClick={removePhoto}>Remove photo</Button>}<Typography variant="caption" color={form.errors.photo ? 'error' : 'text.secondary'}>{form.errors.photo || 'JPG, PNG or WEBP · maximum 5 MB'}</Typography></Stack>
                </Stack>
                <TextField type="number" label="Display order" value={form.data.sort_order} onChange={(event) => form.setData('sort_order', event.target.value)} error={!!form.errors.sort_order} helperText={form.errors.sort_order || 'Lower numbers appear first. Use 0 for the normal order.'} inputProps={{ min: 0, max: 9999 }} />
                <FormControlLabel control={<Checkbox checked={form.data.is_published} onChange={(event) => form.setData('is_published', event.target.checked)} />} label="Publish this facility on the public website" />
                <Stack direction="row" spacing={2}><Button type="submit" variant="contained" startIcon={<SaveOutlined />} disabled={form.processing}>{form.processing ? 'Saving…' : 'Save facility'}</Button><Button component={Link} href="/admin/facilities" color="inherit">Cancel</Button></Stack>
            </Stack>
        </Card>
    </AdminLayout>;
}
