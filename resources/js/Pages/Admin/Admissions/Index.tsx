import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import OpenInNewRounded from '@mui/icons-material/OpenInNewRounded';
import { Box, Card, Chip, FormControl, InputLabel, MenuItem, Pagination, Select, Stack, TextField, Typography } from '@mui/material';
import { FormEvent, useState } from 'react';

type Application = { id:number; application_no:string; student_name:string; guardian_name:string; phone:string; email?:string; applying_for_class:string; status:string; created_at:string };
type Props = { search:string; status?:string; statusOptions:string[]; applications:{data:Application[];current_page:number;last_page:number} };
const displayStatus = (status:string) => status.charAt(0).toUpperCase() + status.slice(1);
const chipColor = (status:string) => status === 'accepted' ? 'success' : status === 'declined' ? 'error' : status === 'reviewing' ? 'secondary' : status === 'contacted' ? 'primary' : 'default';

export default function Index({ search, status, statusOptions, applications }: Props) {
    const [query, setQuery] = useState(search);
    const [selectedStatus, setSelectedStatus] = useState(status || '');
    const filters = (page = 1) => ({ search: query, status: selectedStatus || undefined, page });
    const submit = (event: FormEvent) => { event.preventDefault(); router.get('/admin/admissions', filters(), { preserveState: true, replace: true }); };

    return <AdminLayout><Head title="Admissions" />
        <Stack direction={{xs:'column',sm:'row'}} justifyContent="space-between" mb={4} spacing={2}><Box><Typography variant="h3" fontFamily="Newsreader">Admissions</Typography><Typography color="text.secondary">Review family enquiries and keep every next step clear.</Typography></Box><Chip label={`${applications.data.length} shown`} variant="outlined" sx={{alignSelf:{sm:'center'}}} /></Stack>
        <Card sx={{p:{xs:2,md:3}}}><Stack component="form" onSubmit={submit} direction={{xs:'column',md:'row'}} spacing={2} mb={3}><TextField value={query} onChange={event => setQuery(event.target.value)} placeholder="Search name, phone or application no…" fullWidth /><FormControl sx={{minWidth:{md:190}}}><InputLabel id="admission-status-label">Status</InputLabel><Select labelId="admission-status-label" label="Status" value={selectedStatus} onChange={event => { setSelectedStatus(event.target.value); router.get('/admin/admissions', { search: query, status: event.target.value || undefined }, { preserveState: true, replace: true }); }}><MenuItem value="">All statuses</MenuItem>{statusOptions.map(option => <MenuItem key={option} value={option}>{displayStatus(option)}</MenuItem>)}</Select></FormControl></Stack>
            {applications.data.length ? <Stack divider={<Box borderBottom="1px solid #e6e6e6" />}>{applications.data.map(application => <Stack key={application.id} direction={{xs:'column',md:'row'}} spacing={2} alignItems={{md:'center'}} py={2.25}><Box flexGrow={1}><Stack direction="row" alignItems="center" spacing={1} flexWrap="wrap"><Typography fontWeight={800}>{application.student_name}</Typography><Chip size="small" label={displayStatus(application.status)} color={chipColor(application.status) as any} /></Stack><Typography variant="body2" color="text.secondary" mt={.5}>{application.applying_for_class} · Guardian: {application.guardian_name}</Typography><Typography variant="caption" color="text.secondary">{application.application_no} · {application.phone} · Received {new Date(application.created_at).toLocaleDateString('en-BD', { day:'numeric', month:'short', year:'numeric' })}</Typography></Box><Chip component={Link} href={`/admin/admissions/${application.id}`} clickable icon={<OpenInNewRounded />} label="Review" color="primary" variant="outlined" /></Stack>)}</Stack> : <Box textAlign="center" py={9}><Typography variant="h6">No applications found</Typography><Typography color="text.secondary" mt={1}>Try changing your search or status filter.</Typography></Box>}
            {applications.last_page > 1 && <Pagination count={applications.last_page} page={applications.current_page} onChange={(_, page) => router.get('/admin/admissions', filters(page), { preserveState:true })} sx={{mt:3}} />}
        </Card>
    </AdminLayout>;
}
