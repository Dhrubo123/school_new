import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import AddRounded from '@mui/icons-material/AddRounded';
import BusinessRounded from '@mui/icons-material/BusinessRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import { Box, Button, Card, Chip, IconButton, Pagination, Stack, TextField, Typography } from '@mui/material';
import { FormEvent, useState } from 'react';

type Facility = { id: number; title: string; description: string; photo_path?: string; is_published: boolean; sort_order: number };
type Props = { search: string; facilities: { data: Facility[]; current_page: number; last_page: number } };

export default function Index({ search, facilities }: Props) {
    const [query, setQuery] = useState(search);
    const submit = (event: FormEvent) => { event.preventDefault(); router.get('/admin/facilities', { search: query }, { preserveState: true, replace: true }); };

    return <AdminLayout>
        <Head title="Facilities" />
        <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" spacing={2} mb={4}>
            <Box><Typography variant="h3" fontFamily="Newsreader">Facilities</Typography><Typography color="text.secondary">Show families the spaces, resources and services that support student life.</Typography></Box>
            <Button component={Link} href="/admin/facilities/create" variant="contained" startIcon={<AddRounded />}>Add facility</Button>
        </Stack>
        <Card sx={{ p: { xs: 2, md: 3 } }}>
            <Box component="form" onSubmit={submit} mb={3}><TextField value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search facilities…" sx={{ width: { xs: '100%', sm: 380 } }} /></Box>
            {facilities.data.length ? <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 2.5 }}>
                {facilities.data.map((facility) => <Card key={facility.id} variant="outlined" sx={{ p: 2, boxShadow: 'none' }}>
                    <Stack direction="row" spacing={2} alignItems="flex-start">
                        {facility.photo_path ? <Box component="img" src={`/storage/${facility.photo_path}`} alt="" sx={{ width: 86, height: 70, objectFit: 'cover', borderRadius: 2, bgcolor: 'primary.light', flexShrink: 0 }} /> : <Box sx={{ width: 86, height: 70, borderRadius: 2, bgcolor: 'primary.light', color: 'primary.main', display: 'grid', placeItems: 'center', flexShrink: 0 }}><BusinessRounded /></Box>}
                        <Box minWidth={0} flexGrow={1}>
                            <Stack direction="row" spacing={1} alignItems="center" flexWrap="wrap"><Typography variant="h6" fontWeight={750}>{facility.title}</Typography><Chip size="small" color={facility.is_published ? 'success' : 'default'} label={facility.is_published ? 'Published' : 'Draft'} /></Stack>
                            <Typography color="text.secondary" variant="body2" mt={.75} sx={{ display: '-webkit-box', WebkitLineClamp: 2, WebkitBoxOrient: 'vertical', overflow: 'hidden' }}>{facility.description}</Typography>
                        </Box>
                    </Stack>
                    <Stack direction="row" justifyContent="space-between" alignItems="center" mt={2}><Typography variant="caption" color="text.secondary">Display order: {facility.sort_order}</Typography><Stack direction="row"><IconButton component={Link} href={`/admin/facilities/${facility.id}/edit`} aria-label={`Edit ${facility.title}`}><EditRounded /></IconButton><IconButton color="error" aria-label={`Delete ${facility.title}`} onClick={() => confirm(`Delete “${facility.title}”?`) && router.delete(`/admin/facilities/${facility.id}`)}><DeleteOutlineRounded /></IconButton></Stack></Stack>
                </Card>)}
            </Box> : <Box textAlign="center" py={8}><BusinessRounded color="disabled" sx={{ fontSize: 48 }} /><Typography variant="h6" mt={2}>No facilities yet</Typography><Typography color="text.secondary">Add your first facility, then publish it when it is ready for the website.</Typography></Box>}
            {facilities.last_page > 1 && <Pagination count={facilities.last_page} page={facilities.current_page} onChange={(_, page) => router.get('/admin/facilities', { search: query, page }, { preserveState: true })} sx={{ mt: 3 }} />}
        </Card>
    </AdminLayout>;
}
