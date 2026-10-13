import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import OpenInNewRounded from '@mui/icons-material/OpenInNewRounded';
import { Box, Card, Chip, FormControl, InputLabel, MenuItem, Pagination, Select, Stack, TextField, Typography } from '@mui/material';
import { FormEvent, useState } from 'react';

type Enquiry = { id: number; name: string; email: string; phone?: string; subject: string; status: string; created_at: string };
type Props = { search: string; status?: string; statusOptions: string[]; enquiries: { data: Enquiry[]; current_page: number; last_page: number } };
const label = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);
const color = (status: string) => status === 'resolved' ? 'success' : status === 'archived' ? 'default' : status === 'contacted' ? 'primary' : 'warning';

export default function Index({ search, status, statusOptions, enquiries }: Props) {
    const [query, setQuery] = useState(search);
    const [selectedStatus, setSelectedStatus] = useState(status || '');
    const filters = (page = 1) => ({ search: query, status: selectedStatus || undefined, page });
    const submit = (event: FormEvent) => { event.preventDefault(); router.get('/admin/contact-enquiries', filters(), { preserveState: true, replace: true }); };

    return <AdminLayout><Head title="Contact inbox" />
        <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" mb={4} spacing={2}><Box><Typography variant="h3" fontFamily="Newsreader">Contact inbox</Typography><Typography color="text.secondary">Review questions sent through the public Contact page and keep every reply on track.</Typography></Box><Chip label={`${enquiries.data.length} shown`} variant="outlined" sx={{ alignSelf: { sm: 'center' } }} /></Stack>
        <Card sx={{ p: { xs: 2, md: 3 } }}><Stack component="form" onSubmit={submit} direction={{ xs: 'column', md: 'row' }} spacing={2} mb={3}><TextField value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search name, email, phone or subject…" fullWidth /><FormControl sx={{ minWidth: { md: 190 } }}><InputLabel id="enquiry-status-label">Status</InputLabel><Select labelId="enquiry-status-label" label="Status" value={selectedStatus} onChange={(event) => { setSelectedStatus(event.target.value); router.get('/admin/contact-enquiries', { search: query, status: event.target.value || undefined }, { preserveState: true, replace: true }); }}><MenuItem value="">All statuses</MenuItem>{statusOptions.map((option) => <MenuItem key={option} value={option}>{label(option)}</MenuItem>)}</Select></FormControl></Stack>
            {enquiries.data.length ? <Stack divider={<Box borderBottom="1px solid #e6e6e6" />}>{enquiries.data.map((enquiry) => <Stack key={enquiry.id} direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems={{ md: 'center' }} py={2.25}><Box flexGrow={1}><Stack direction="row" alignItems="center" spacing={1} flexWrap="wrap"><Typography fontWeight={800}>{enquiry.subject}</Typography><Chip size="small" label={label(enquiry.status)} color={color(enquiry.status) as any} /></Stack><Typography variant="body2" color="text.secondary" mt={.5}>{enquiry.name} · {enquiry.email}{enquiry.phone ? ` · ${enquiry.phone}` : ''}</Typography><Typography variant="caption" color="text.secondary">Received {new Date(enquiry.created_at).toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' })}</Typography></Box><Chip component={Link} href={`/admin/contact-enquiries/${enquiry.id}`} clickable icon={<OpenInNewRounded />} label="Review" color="primary" variant="outlined" /></Stack>)}</Stack> : <Box textAlign="center" py={9}><Typography variant="h6">No enquiries found</Typography><Typography color="text.secondary" mt={1}>Try changing your search or status filter.</Typography></Box>}
            {enquiries.last_page > 1 && <Pagination count={enquiries.last_page} page={enquiries.current_page} onChange={(_, page) => router.get('/admin/contact-enquiries', filters(page), { preserveState: true })} sx={{ mt: 3 }} />}
        </Card>
    </AdminLayout>;
}
