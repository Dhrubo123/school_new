import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import SaveOutlined from '@mui/icons-material/SaveOutlined';
import { Alert, Box, Button, Card, Chip, Divider, MenuItem, Stack, TextField, Typography } from '@mui/material';
import { FormEvent } from 'react';
import { PageProps } from '@/types';

type Enquiry = { id: number; name: string; email: string; phone?: string; subject: string; message: string; status: string; admin_notes?: string; handled_at?: string; handler?: { name: string }; created_at: string };
const label = (value: string) => value.charAt(0).toUpperCase() + value.slice(1);

export default function Show({ enquiry, statusOptions }: { enquiry: Enquiry; statusOptions: string[] }) {
    const flash = usePage<PageProps>().props.flash;
    const form = useForm({ status: enquiry.status, admin_notes: enquiry.admin_notes || '' });
    const save = (event: FormEvent) => { event.preventDefault(); form.put(`/admin/contact-enquiries/${enquiry.id}`, { preserveScroll: true }); };
    const details = [['Email', enquiry.email], ['Mobile', enquiry.phone || 'Not provided'], ['Received', new Date(enquiry.created_at).toLocaleString('en-BD', { dateStyle: 'medium', timeStyle: 'short' })]];

    return <AdminLayout><Head title={`Enquiry: ${enquiry.subject}`} />
        <Button component={Link} href="/admin/contact-enquiries" startIcon={<ArrowBackRounded />} color="inherit" sx={{ mb: 3 }}>Contact inbox</Button>
        <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" spacing={2} mb={4}><Box><Typography variant="overline" color="secondary.dark" fontWeight={800}>GENERAL ENQUIRY</Typography><Typography variant="h3" fontFamily="Newsreader">{enquiry.subject}</Typography><Typography color="text.secondary">From {enquiry.name}</Typography></Box><Chip label={label(enquiry.status)} color={enquiry.status === 'resolved' ? 'success' : enquiry.status === 'new' ? 'warning' : 'primary'} sx={{ alignSelf: { md: 'center' }, fontWeight: 750 }} /></Stack>
        {flash.success && <Alert severity="success" sx={{ mb: 3 }}>{flash.success}</Alert>}
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', lg: '1fr 1fr' }, gap: 3, maxWidth: 1100 }}><Card sx={{ p: { xs: 3, md: 4 } }}><Typography variant="h5" fontWeight={800} mb={3}>Sender details</Typography><Stack spacing={2}>{details.map(([name, value]) => <Box key={name}><Typography variant="caption" color="text.secondary" fontWeight={800} textTransform="uppercase">{name}</Typography><Typography>{value}</Typography></Box>)}</Stack><Divider sx={{ my: 3 }} /><Typography variant="caption" color="text.secondary" fontWeight={800} textTransform="uppercase">Message</Typography><Typography color="text.secondary" mt={.75} whiteSpace="pre-wrap">{enquiry.message}</Typography></Card>
            <Card component="form" onSubmit={save} sx={{ p: { xs: 3, md: 4 } }}><Typography variant="h5" fontWeight={800} mb={1}>Follow-up</Typography><Typography color="text.secondary" variant="body2" mb={3}>Update the message status after calling or emailing the sender. Notes are visible only to administrators.</Typography><Stack spacing={2.5}><TextField select label="Enquiry status" value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} error={!!form.errors.status} helperText={form.errors.status}>{statusOptions.map((option) => <MenuItem key={option} value={option}>{label(option)}</MenuItem>)}</TextField><TextField label="Internal notes" multiline rows={7} value={form.data.admin_notes} onChange={(event) => form.setData('admin_notes', event.target.value)} error={!!form.errors.admin_notes} helperText={form.errors.admin_notes || 'Record calls, emails, visits, or the next action.'} /><Button type="submit" variant="contained" disabled={form.processing} startIcon={<SaveOutlined />}>{form.processing ? 'Saving…' : 'Save follow-up'}</Button>{enquiry.handled_at && <Typography variant="caption" color="text.secondary">Last updated by {enquiry.handler?.name || 'an administrator'} on {new Date(enquiry.handled_at).toLocaleString('en-BD', { dateStyle: 'medium', timeStyle: 'short' })}.</Typography>}<Button type="button" color="error" startIcon={<DeleteOutlineRounded />} onClick={() => confirm('Delete this enquiry? This cannot be undone.') && router.delete(`/admin/contact-enquiries/${enquiry.id}`)}>Delete enquiry</Button></Stack></Card>
        </Box>
    </AdminLayout>;
}
