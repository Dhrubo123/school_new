import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import SaveOutlined from '@mui/icons-material/SaveOutlined';
import { Alert, Box, Button, Card, Chip, Divider, MenuItem, Stack, TextField, Typography } from '@mui/material';
import { FormEvent } from 'react';
import { PageProps } from '@/types';

type Admission = { id:number; application_no:string; student_name:string; guardian_name:string; email?:string; phone:string; applying_for_class:string; message?:string; status:string; review_notes?:string; reviewed_at?:string; reviewer?:{name:string}; created_at:string };
const label = (value:string) => value.charAt(0).toUpperCase() + value.slice(1);

export default function Show({ admission, statusOptions }: { admission:Admission; statusOptions:string[] }) {
    const flash = usePage<PageProps>().props.flash;
    const form = useForm({ status: admission.status, review_notes: admission.review_notes || '' });
    const save = (event:FormEvent) => { event.preventDefault(); form.put(`/admin/admissions/${admission.id}`, { preserveScroll:true }); };
    const details = [['Application no.', admission.application_no], ['Applying for', admission.applying_for_class], ['Guardian', admission.guardian_name], ['Mobile', admission.phone], ['Email', admission.email || 'Not provided'], ['Received', new Date(admission.created_at).toLocaleString('en-BD', { dateStyle:'medium', timeStyle:'short' })]];

    return <AdminLayout><Head title={`Review ${admission.application_no}`} />
        <Button component={Link} href="/admin/admissions" startIcon={<ArrowBackRounded />} color="inherit" sx={{mb:3}}>All applications</Button>
        <Stack direction={{xs:'column',md:'row'}} justifyContent="space-between" spacing={2} mb={4}><Box><Typography variant="overline" color="secondary.dark" fontWeight={800}>ADMISSION ENQUIRY</Typography><Typography variant="h3" fontFamily="Newsreader">{admission.student_name}</Typography><Typography color="text.secondary">Review the enquiry, record your note, and update its progress.</Typography></Box><Chip label={label(admission.status)} color={admission.status === 'accepted' ? 'success' : admission.status === 'declined' ? 'error' : 'primary'} sx={{alignSelf:{md:'center'},fontWeight:750}} /></Stack>
        {flash.success && <Alert severity="success" sx={{mb:3}}>{flash.success}</Alert>}
        <Box sx={{display:'grid',gridTemplateColumns:{xs:'1fr',lg:'1fr 1fr'},gap:3,maxWidth:1100}}><Card sx={{p:{xs:3,md:4}}}><Typography variant="h5" fontWeight={800} mb={3}>Family details</Typography><Stack spacing={2}>{details.map(([name,value])=><Box key={name}><Typography variant="caption" color="text.secondary" fontWeight={800} textTransform="uppercase">{name}</Typography><Typography>{value}</Typography></Box>)}</Stack><Divider sx={{my:3}}/><Typography variant="caption" color="text.secondary" fontWeight={800} textTransform="uppercase">Family message</Typography><Typography color="text.secondary" mt={.75} whiteSpace="pre-wrap">{admission.message || 'No additional message was provided.'}</Typography></Card>
            <Card component="form" onSubmit={save} sx={{p:{xs:3,md:4}}}><Typography variant="h5" fontWeight={800} mb={1}>Review</Typography><Typography color="text.secondary" variant="body2" mb={3}>Only administrators can see these notes.</Typography><Stack spacing={2.5}><TextField select label="Application status" value={form.data.status} onChange={event => form.setData('status', event.target.value)} error={!!form.errors.status} helperText={form.errors.status}>{statusOptions.map(option => <MenuItem key={option} value={option}>{label(option)}</MenuItem>)}</TextField><TextField label="Internal review notes" multiline rows={7} value={form.data.review_notes} onChange={event => form.setData('review_notes', event.target.value)} error={!!form.errors.review_notes} helperText={form.errors.review_notes || 'Record calls, required documents, meeting dates, or a decision.'} /><Button type="submit" variant="contained" disabled={form.processing} startIcon={<SaveOutlined />}>{form.processing ? 'Saving…' : 'Save review'}</Button>{admission.reviewed_at && <Typography variant="caption" color="text.secondary">Last reviewed by {admission.reviewer?.name || 'an administrator'} on {new Date(admission.reviewed_at).toLocaleString('en-BD', {dateStyle:'medium',timeStyle:'short'})}.</Typography>}</Stack></Card>
        </Box>
    </AdminLayout>;
}
