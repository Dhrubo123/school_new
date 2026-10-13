import PublicLayout from '@/Layouts/PublicLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import CallRounded from '@mui/icons-material/CallRounded';
import EmailRounded from '@mui/icons-material/EmailRounded';
import LocationOnRounded from '@mui/icons-material/LocationOnRounded';
import OpenInNewRounded from '@mui/icons-material/OpenInNewRounded';
import ScheduleRounded from '@mui/icons-material/ScheduleRounded';
import { Alert, Box, Button, Card, Container, Stack, TextField, Typography } from '@mui/material';
import { FormEvent } from 'react';
import { PageProps } from '@/types';

export default function Contact() {
    const { school, flash } = usePage<PageProps>().props;
    const form = useForm({ name: '', email: '', phone: '', subject: '', message: '' });
    const mapsLink = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(school.address || '')}`;
    const socialLinks = [['Facebook', school.facebook_url], ['Instagram', school.instagram_url], ['YouTube', school.youtube_url]].filter(([, href]) => href);
    const submit = (event: FormEvent) => { event.preventDefault(); form.post('/contact/enquiries', { preserveScroll: true, onSuccess: () => form.reset() }); };

    return <PublicLayout>
        <Head title="Contact us"><meta name="description" content="Contact our school office, find the campus, or send a message to our team." /></Head>
        <Box sx={{ bgcolor: 'primary.dark', color: 'white', py: { xs: 9, md: 14 } }}><Container maxWidth="lg"><Typography variant="overline" color="#e1c16e" fontWeight={800}>CONTACT US</Typography><Typography variant="h1" maxWidth={800} sx={{ fontSize: { xs: 48, md: 76 }, mt: 2 }}>We would love to hear from you.</Typography><Typography color="#c2d5ce" fontSize={20} maxWidth={680} mt={3}>Ask a question, arrange a visit, or find the information you need. Our school office is here to help.</Typography></Container></Box>
        <Container maxWidth="lg" sx={{ py: { xs: 8, md: 12 } }}>
            <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', lg: 'minmax(0, .9fr) minmax(0, 1.1fr)' }, gap: { xs: 5, md: 8 } }}>
                <Stack spacing={2.5}>
                    <Box><Typography variant="overline" color="secondary.dark" fontWeight={800}>SCHOOL OFFICE</Typography><Typography variant="h2" fontFamily="Newsreader" sx={{ fontSize: { xs: 38, md: 50 }, mt: 1 }}>Visit, call, or send a message.</Typography></Box>
                    {[[<LocationOnRounded />, 'Visit us', school.address], [<CallRounded />, 'Call us', school.phone], [<EmailRounded />, 'Email us', school.email], [<ScheduleRounded />, 'Office hours', school.office_hours]].map(([icon, title, detail]) => <Card key={String(title)} sx={{ p: 2.5, display: 'flex', gap: 2, boxShadow: 'none', border: '1px solid', borderColor: 'divider' }}><Box sx={{ color: 'primary.main', pt: .25 }}>{icon}</Box><Box><Typography fontWeight={800}>{title}</Typography><Typography color="text.secondary" whiteSpace="pre-line">{detail || 'Not available'}</Typography></Box></Card>)}
                    {socialLinks.length > 0 && <Box pt={1}><Typography fontWeight={800} mb={1}>Follow our community</Typography><Stack direction="row" flexWrap="wrap" gap={1}>{socialLinks.map(([label, href]) => <Button key={label} component="a" href={href} target="_blank" rel="noreferrer" variant="outlined" size="small">{label}</Button>)}</Stack></Box>}
                </Stack>
                <Card component="form" onSubmit={submit} sx={{ p: { xs: 3, md: 5 }, alignSelf: 'start' }}>
                    <Typography variant="h4" fontFamily="Newsreader">Send us a message</Typography><Typography color="text.secondary" mt={1} mb={3}>We will use your details only to respond to your enquiry.</Typography>
                    {flash.success && <Alert severity="success" sx={{ mb: 2 }}>{flash.success}</Alert>}
                    <Stack spacing={2}><TextField label="Your name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} error={!!form.errors.name} helperText={form.errors.name} autoComplete="name" /><TextField label="Email address" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} error={!!form.errors.email} helperText={form.errors.email} autoComplete="email" /><TextField label="Mobile number (optional)" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} error={!!form.errors.phone} helperText={form.errors.phone} autoComplete="tel" /><TextField label="Subject" value={form.data.subject} onChange={(event) => form.setData('subject', event.target.value)} error={!!form.errors.subject} helperText={form.errors.subject} /><TextField label="How can we help?" multiline minRows={5} value={form.data.message} onChange={(event) => form.setData('message', event.target.value)} error={!!form.errors.message} helperText={form.errors.message} /><Button type="submit" variant="contained" size="large" disabled={form.processing}>{form.processing ? 'Sending…' : 'Send message'}</Button></Stack>
                </Card>
            </Box>
            <Box mt={{ xs: 7, md: 10 }}><Typography variant="h3" fontFamily="Newsreader" mb={3}>Find our campus</Typography>{school.map_embed_url ? <Box component="iframe" title="School location map" src={school.map_embed_url} loading="lazy" referrerPolicy="no-referrer-when-downgrade" sx={{ border: 0, borderRadius: 3, width: '100%', height: { xs: 320, md: 460 } }} /> : <Card sx={{ p: { xs: 4, md: 6 }, bgcolor: '#f4f1e8', boxShadow: 'none', display: 'flex', flexDirection: { xs: 'column', sm: 'row' }, justifyContent: 'space-between', gap: 3, alignItems: { sm: 'center' } }}><Box><Typography variant="h5" fontWeight={800}>Map location will appear here.</Typography><Typography color="text.secondary" mt={1}>The school administrator can add a Google Maps embed link in Settings. You can still open the campus address in Google Maps.</Typography></Box><Button component="a" href={mapsLink} target="_blank" rel="noreferrer" variant="contained" endIcon={<OpenInNewRounded />} sx={{ whiteSpace: 'nowrap' }}>Open Google Maps</Button></Card>}</Box>
        </Container>
    </PublicLayout>;
}
